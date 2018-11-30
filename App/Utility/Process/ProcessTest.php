<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/23
 * Time: 下午8:35
 */

namespace App\Utility\Process;

use EasySwoole\EasySwoole\Swoole\Process\AbstractProcess;
use Swoole\Process;

class ProcessTest extends AbstractProcess
{
    public function run(Process $process)
    {
        // TODO: Implement run() method.
        echo "process is run.\n";

        $this->addTick(5000, function () {
            echo date('Y-m-d H:i:s') . PHP_EOL;
        });
    }

    public function onShutDown()
    {
        echo "process is onShutDown.\n";
        // TODO: Implement onShutDown() method.
    }

    public function onReceive(string $str)
    {
        echo "process is onReceive.\n";
        // TODO: Implement onReceive() method.
    }
}