<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\HttpController\V410\Test;

use App\HttpController\Base;
use App\Utility\Curl;
use App\Utility\Status;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Swoole\Memory\ChannelManager;
use EasySwoole\EasySwoole\Swoole\Memory\TableManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\Spl\SplArray;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Swoole\Table;

class Index extends Base
{
    public function banben()
    {
        $this->writeJson(Status::CODE_OK, ['uri' => $this->request()->getUri()->getPath()]);
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
        $chan = new Channel();
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

    public function gotest2()
    {
        $chan = new Channel(1);
        \go(function () use ($chan) {
            $resp = Curl::getInstance()->request('get', 'http://127.0.0.1:9501/test/index/sleep?version=4.0.0&time=2');
            $chan->push(['1' => $resp->getBody()]);
            $chan->close();
        });
        $chan2 = new Channel(1);
        \go(function () use ($chan2) {
            $resp = Curl::getInstance()->request('get', 'http://127.0.0.1:9501/test/index/sleep?version=4.0.0&time=2');
            $chan2->push(['2' => $resp->getBody()]);
            $chan2->close();
        });

        $chan3 = new Channel(1);
        \go(function () use ($chan, $chan2, $chan3) {
            $data = [];
            $data[] = $chan->pop();
            $data[] = $chan2->pop();
            $chan3->push(['data' => $data]);
        });

        $this->writeJson(200, ['data' => $chan3->pop()]);
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