<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/23
 * Time: 下午8:35
 */

namespace App\Process;

use EasySwoole\Component\Process\AbstractProcess;

class ProcessTest extends AbstractProcess
{
    public function run($arg)
    {
        // TODO: Implement run() method.
        echo "process is run.\n";

        // 每5秒执行一次
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