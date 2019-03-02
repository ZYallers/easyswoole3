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

    private function udate(string $format = 'Y-m-d H:i:s.u', ?float $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);
        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

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
        $changeInodeList = [];

        /* @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $ext = $file->getExtension();
            if (in_array($ext, $this->monitorExt)) {
                // 由于修改文件名称，并不需要重新载入，可用基于inode进行监视
                $inode = $file->getInode();
                $mtime = $file->getMTime();
                $path = $file->getPath() . '/' . $file->getFilename();
                array_push($inodeList, $inode);
                if ($this->table->exist($inode)) {
                    // 修改文件，但未发生inode变更
                    $oldTime = $this->table->get($inode)['mtime'];
                    if ($oldTime != $mtime) {
                        $this->table->set($inode, ['mtime' => $mtime, 'file' => $path]);
                        $changeInodeList[] = 'update|' . $inode . '|' . $path;
                        $doReload = true;
                    }
                } else {
                    // 新建文件，变更了inode
                    $this->table->set($inode, ['mtime' => $mtime, 'file' => $path]);
                    $changeInodeList[] = 'add|' . $inode . '|' . $path;
                    $doReload = true;
                }
            }
        }

        foreach ($this->table as $inode => $value) {
            // 迭代table寻找需要删除的inode
            if (!in_array($inode, $inodeList)) {
                $this->table->del($inode);
                $changeInodeList[] = 'delete|' . $inode . '|' . $value['file'];
                $doReload = true;
            }
        }

        if ($doReload) {
            $count = $this->table->count();
            $changeCount = count($changeInodeList);
            $usage = round(microtime(true) - $startTime, 3);
            if ($this->isReady) {
                // 监视到需要进行热重启
                $changeFiles = var_export($changeInodeList, true);
                echo "[{$this->udate()}]  NOTICE  Server hotReload: use {$usage}s, total: {$count} files, change: {$changeCount} files: {$changeFiles}.\n";
                // 如果开启了Zend OPCache，对改变了的文件进行缓存清除
                if (extension_loaded('Zend OPcache')) {
                    $config = opcache_get_configuration()['directives'];
                    if ($config['opcache.enable']) {
                        if ($config['opcache.enable_cli']) {
                            $count = 0;
                            foreach ($changeInodeList as $item) {
                                $file = explode('|', $item)[2];
                                if (opcache_is_script_cached($file) && opcache_invalidate($file, true)) {
                                    $count++;
                                }
                            }
                            echo "[{$this->udate()}]  NOTICE  Zend OPcache invalidate {$count} files.\n";
                        } else {
                            echo "[{$this->udate()}]  NOTICE  Zend OPcache cli unenable.\n";
                        }
                    } else {
                        echo "[{$this->udate()}]  NOTICE  Zend OPcache unenable.\n";
                    }
                }
                ServerManager::getInstance()->getSwooleServer()->reload();
            } else {
                // 首次扫描不需要进行重启操作
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
        $rate = $this->getArg('rate');
        $disableInotify = $this->getArg('disableInotify');
        $monitorDir = $this->getArg('monitorDir');
        $monitorExt = $this->getArg('monitorExt');

        // 指定多久执行检测一次文件变动
        $rate = $rate ? intval($rate) : 20;
        // 指定是否禁用inotify扩展
        $disableInotify = $disableInotify ? boolval($disableInotify) : true;
        // 指定需要监视的目录，建议只监视App目录下的文件变更
        $this->monitorDir = $monitorDir ? $monitorDir : EASYSWOOLE_ROOT . '/App';
        // 指定需要监视的文件后缀，不属于指定后缀类型的文件无视变更不重启
        $this->monitorExt = $monitorExt && is_array($monitorExt) ? $monitorExt : ['php'];

        if (extension_loaded('inotify') && !$disableInotify) {
            // 扩展可用，优先使用扩展进行处理
            $this->registerInotifyEvent();
            echo "[" . $this->udate() . "]  NOTICE  Server hotReload start: use inotify.\n";
        } else {
            $this->table = new Table(512);
            $this->table->column('mtime', Table::TYPE_INT, 4);
            $this->table->column('file', Table::TYPE_STRING, 100);
            $this->table->create();
            $this->runComparison();
            \Swoole\Timer::tick($rate, function () {
                $this->runComparison();
            });
            echo "[" . $this->udate() . "]  NOTICE  Server hotReload start: use timer tick comparison.\n";
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