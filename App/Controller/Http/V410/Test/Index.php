<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\Controller\Http\V410\Test;

use App\Controller\Http\Base;
use App\Utility\Status;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Process;

class Index extends Base
{
    public function banben()
    {
        $this->writeJson(Status::CODE_OK, ['uri' => $this->request()->getUri()->__toString()]);
    }

    public function routers()
    {
        $routers = Config::getInstance()->getConf('router');
        $this->writeJson(Status::CODE_OK, ['routers' => $routers]);
    }

    public function asyncdemo()
    {
        $uri = $this->request()->getUri()->__toString();
        TaskManager::async(function () use ($uri) {
            sleep(5);
            Logger::getInstance()->log($uri, 'asyncdemo');
        });
    }

    public function gotest()
    {
        $chan = new Channel(2);
        \go(function () use ($chan) {
            Coroutine::sleep(4);
            $chan->push(['www.qq.com' => '1']);
        });

        \go(function () use ($chan) {
            Coroutine::sleep(4);
            $chan->push(['www.163.com' => '2']);
        });

        \go(function () use ($chan) {
            Coroutine::sleep(4);
            $chan->push(['www.126.com' => '3']);
        });

        $data = [];
        for ($i = 3; $i > 0; $i--) {
            $resp = $chan->pop();
            var_dump(date('Y.m.d H:i:s') . ': ' . var_export($resp, true));
            if (is_array($resp)) {
                $data[] = $resp;
            }
        }

        $chan->close();
        $this->writeJson(200, $data);
    }

    private static function ptss()
    {
        $p1 = new Process(function (Process $worker) {
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " started....." . PHP_EOL;
            sleep(1);
            $d = '11111';
            $worker->push($d);
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " push data: {$d}" . PHP_EOL;

            $worker->exit(1);
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " exit!!!" . PHP_EOL;
        }, false, false);
        $p1->useQueue(1, 2 | Process::IPC_NOWAIT);
        $p1->start();

        $p2 = new Process(function (Process $worker) {
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " started....." . PHP_EOL;
            sleep(2);
            $d = '22222';
            $worker->push($d);
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " push data: {$d}" . PHP_EOL;

            $worker->exit(1);
            echo date('Y.m.d H:i:s') . ": worker " . $worker->pid . " exit!!!" . PHP_EOL;
        }, false, false);
        $p2->useQueue(2, 2 | Process::IPC_NOWAIT);
        $p2->start();

        echo date('Y.m.d H:i:s') . ": wait....." . PHP_EOL;
        for ($i = 0; $i < 2; $i++) {
            $arr = Process::wait();
            echo date('Y.m.d H:i:s') . ": worker " . $arr['pid'] . " end!" . PHP_EOL;
        }

        $rev = $p1->pop();
        echo date('Y.m.d H:i:s') . ": [" . $p1->pid . "] rev : " . var_export($rev, 1) . PHP_EOL;
        $ret[] = $rev;

        $rev = $p2->pop();
        echo date('Y.m.d H:i:s') . ": [" . $p2->pid . "] rev : " . var_export($rev, 1) . PHP_EOL;
        $ret[] = $rev;

        return $ret;
    }

    public function ptest()
    {
        $data = [];
        $data[] = self::ptss();
        $this->writeJson(200, ['data' => $data]);
    }
}