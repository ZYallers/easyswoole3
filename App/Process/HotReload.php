<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/12/20
 * Time: 下午1:44
 */

namespace App\Process;

use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Utility\File;
use Swoole\Table;

/**
 * 暴力热重载
 * Class HotReload
 * @package App\Process
 */
class HotReload extends AbstractProcess
{
    /**
     * @var \swoole_table $table
     */
    protected $table;
    /**
     * @var bool $isReady
     */
    protected $isReady = false;
    /**
     * @var string $monitorDir 需要监控的目录
     */
    protected $monitorDir;
    /**
     * @var array $monitorExt 需要监控的文件后缀
     */
    protected $monitorExt;

    /**
     * 注册Inotify监听事件
     */
    private function registerInotifyEvent()
    {
        /*
         * 因为进程独立 且当前是自定义进程 全局变量只有该进程使用
         * 在确定不会造成污染的情况下 也可以合理使用全局变量
         */
        global $lastReloadTime;
        global $inotifyResource;

        $lastReloadTime = 0;
        $files = File::scanDirectory($this->monitorDir);
        $files = array_merge($files['files'], $files['dirs']);

        $inotifyResource = inotify_init();

        // 为当前所有的目录和文件添加事件监听
        foreach ($files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $this->monitorExt)) {
                inotify_add_watch($inotifyResource, $file, IN_CREATE | IN_DELETE | IN_MODIFY);
            }
        }

        // 加入事件循环
        swoole_event_add($inotifyResource, function () {
            global $lastReloadTime;
            global $inotifyResource;
            $events = inotify_read($inotifyResource);
            // 限制1s内不能进行重复reload
            if ($lastReloadTime < time() && !empty($events)) {
                $lastReloadTime = time();
                ServerManager::getInstance()->getSwooleServer()->reload();
            }
        });
    }

    /**
     * 扫描文件变更
     */
    private function runComparison()
    {
        $startTime = microtime(true);
        $doReload = false;

        $dirIterator = new \RecursiveDirectoryIterator($this->monitorDir);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        $inodeList = [];

        /* @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $ext = $file->getExtension();
            if (in_array($ext, $this->monitorExt)) {
                // 由于修改文件名称，并不需要重新载入，可用基于inode进行监视
                $inode = $file->getInode();
                $mtime = $file->getMTime();
                array_push($inodeList, $inode);
                if ($this->table->exist($inode)) {
                    // 修改文件，但未发生inode变更
                    $oldTime = $this->table->get($inode)['mtime'];
                    if ($oldTime != $mtime) {
                        $this->table->set($inode, ['mtime' => $mtime]);
                        $doReload = true;
                    }
                } else {
                    // 新建文件或修改文件，变更了inode
                    $this->table->set($inode, ['mtime' => $mtime]);
                    $doReload = true;
                }
            }
        }

        foreach ($this->table as $inode => $value) {
            // 迭代table寻找需要删除的inode
            if (!in_array(intval($inode), $inodeList)) {
                $this->table->del($inode);
                $doReload = true;
            }
        }

        if ($doReload) {
            $count = $this->table->count();
            $time = date('Y-m-d H:i:s');
            $usage = round(microtime(true) - $startTime, 3);
            if (!$this->isReady == false) {
                // 监视到需要进行热重启
                echo "Server hot reload at {$time} use: {$usage} s, total: {$count} files." . PHP_EOL;
                ServerManager::getInstance()->getSwooleServer()->reload();
            } else {
                // 首次扫描不需要进行重启操作
                echo "Server hot reload  ready at {$time} use: {$usage} s, total: {$count} files." . PHP_EOL;
                $this->isReady = true;
            }
        }
    }

    /**
     * 启动定时器进行循环扫描
     */
    public function run($arg)
    {
        // TODO: Implement run() method.
        $disableInotify = $this->getArg('disableInotify');
        $monitorDir = $this->getArg('monitorDir');
        $monitorExt = $this->getArg('monitorExt');

        // 指定需要监视的目录，建议只监视App目录下的文件变更
        $this->monitorDir = $monitorDir ? $monitorDir : EASYSWOOLE_ROOT . '/App';
        // 指定需要监视的文件后缀，不属于指定后缀类型的文件无视变更不重启
        $this->monitorExt = $monitorExt && is_array($monitorExt) ? $monitorExt : ['php'];

        if (extension_loaded('inotify') && !$disableInotify) {
            // 扩展可用，优先使用扩展进行处理
            $this->registerInotifyEvent();
            echo 'Server hot reload start: use inotify.' . PHP_EOL;
        } else {
            $this->table = new Table(512);
            $this->table->column('mtime', Table::TYPE_INT, 4);
            $this->table->create();
            $this->runComparison();
            \Swoole\Timer::tick(5000, function () {
                $this->runComparison();
            });
            echo 'Server hot reload start: use timer tick comparison.' . PHP_EOL;
        }

    }

    public function onReceive(string $str)
    {
        // TODO: Implement onReceive() method.
    }

    public function onShutDown()
    {
        // TODO: Implement onShutDown() method.
    }
}