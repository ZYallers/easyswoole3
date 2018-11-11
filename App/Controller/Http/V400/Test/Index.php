<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\Controller\Http\V400\Test;


use App\Cache\User\UserInfo;
use App\Controller\Http\Base;
use App\Service\Note\Notes;
use App\Utility\Curl;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\Http\Message\Status;

class Index extends Base
{
    public function allconfig()
    {
        $config = Config::getInstance()->toArray();
        $this->writeJson(Status::CODE_OK, $config);
    }

    public function userinfo()
    {
        $User = (new UserInfo())->getUserInfo('13670896425');
        $this->writeJson(200, $User);
    }

    public function notedetail()
    {
        $id = intval($this->request()->getRequestParam('id'));
        if ($id > 0) {
            $detail = Notes::getInstance()->getNoteById($id);
            $this->writeJson(Status::CODE_OK, ['detail' => $detail]);
        } else {
            $this->writeJson(Status::CODE_BAD_REQUEST);
        }
    }

    public function sleep()
    {
        $time = intval($this->request()->getRequestParam('time'));
        sleep($time);
        $this->writeJson(Status::CODE_OK, null, "sleep {$time}s.");
    }

    public function bingfa()
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

    public function curl()
    {
        $resp = Curl::getInstance()->request('get', 'http://ip.taobao.com/service/getIpInfo.php',
            ['query' => ['ip' => '121.40.81.149']]);
        $this->writeJson(Status::CODE_OK, ['body' => $resp->getBody(), 'error' => $resp->getError()]);
    }

    public function banben()
    {
        $this->response()->write($this->request()->getUri()->getPath());
    }
}