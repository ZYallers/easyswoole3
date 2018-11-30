<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/1
 * Time: 上午10:33
 */

namespace App\Utility\Process;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\Process\AbstractProcess;
use Swoole\Process;

class Inotify extends AbstractProcess
{
    public function run(Process $process)
    {
        // TODO: Implement run() method.
        if (extension_loaded('inotify')) {
            Logger::getInstance()->console('Auto reload enable');
            // 监控应用目录，当应用目录有变动的时候，自动热重启。
            // 注意，并不是全部更改热重启都会生效，如：不受主进程管理（比如自定义进程），不在回调函数内的代码，比如在全局的Event中的代码。
            $a = function ($dir) use (&$a) {
                $data = [];
                if (is_dir($dir)) {
                    // 是目录的话，先增当前目录进去
                    $data[] = $dir;
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $data = array_merge($data, $a($dir . '/' . $file));
                    }
                } else {
                    $data[] = $dir;
                }
                return $data;
            };
            $list = $a(EASYSWOOLE_ROOT . '/App');
            $inotify = inotify_init();
            // 为所有目录和文件添加inotify监视
            foreach ($list as $item) {
                inotify_add_watch($inotify, $item, IN_CREATE | IN_DELETE | IN_MODIFY);
            }
            //  加入EventLoop
            swoole_event_add($inotify, function () use ($inotify) {
                $events = inotify_read($inotify);
                if (!empty($events)) {
                    // TODO::注意更新多个文件的间隔时间处理,防止一次更新了10个文件，重启了10次，懒得做了，反正原理在这里
                    Logger::getInstance()->log('[' . join(',', $events) . '] Service is going to reload', 'inotify_reload');
                    ServerManager::getInstance()->getSwooleServer()->reload();
                }
            });
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