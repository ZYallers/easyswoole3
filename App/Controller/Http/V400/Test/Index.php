<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\Controller\Http\V400\Test;

use App\Utility\Abst\Controller;
use App\Utility\Code;
use App\Utility\HttpClient;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

class Index extends Controller
{
    function banben()
    {
        $this->response()->write($this->request()->getUri()->getPath());
    }

    function allconfig()
    {
        $config = Config::getInstance()->toArray();
        $this->writeJson(Code::OK, $config);
    }

    function sleep()
    {
        $time = intval($this->request()->getRequestParam('time'));
        sleep($time);
        $this->writeJson(Code::OK, null, "sleep {$time}s.");
    }

    function bingfa()
    {
        $tasks[] = function () {
            sleep(1);
            return 'task1';
        }; // 任务1
        $tasks[] = function () {
            sleep(2);
            return 'task2';
        };     // 任务2
        $tasks[] = function () {
            sleep(3);
            return 'task3';
        }; // 任务3

        $start = microtime(true);
        $results = TaskManager::barrier($tasks, 5);
        $data = ['spent' => sprintf('%.6f', microtime(true) - $start), 'result' => $results];
        $this->writeJson(200, $data);
    }

    function curl()
    {
        $resp = HttpClient::getInstance()->get('https://www.kuaidi100.com/query', ['timeout' => 3,
            'query' => ['postid' => '800125432030318719', 'type' => 'yuantong']]);
        $this->writeJson(Code::OK, ['body' => $resp->getBody(), 'code' => $resp->getStatusCode(), 'error' => $resp->getErrMsg()]);
    }
}