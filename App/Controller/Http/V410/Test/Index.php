<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\Controller\Http\V410\Test;

use App\Cache\Session;
use App\Service\Adviser;
use App\Service\Community;
use App\Service\InnerVip;
use App\Service\User\UserInfo;
use App\Service\User\UserPassport;
use App\Utility\Abst\Controller;
use App\Utility\Code;
use App\Utility\Pub;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Process;

class Index extends Controller
{
    public function banben()
    {
        /*var_dump(date('Y-m-d H:i:s'));
        $ps = new Process(function(){
            sleep(3);
            var_dump(date('Y-m-d H:i:s').':  333');
        }, false, false);
        $ps->start();*/
        $this->writeJson(Code::OK, ['uri' => $this->request()->getUri()->__toString(), 't' => 2]);
    }

    public function routers()
    {
        $routers = Config::getInstance()->getConf('router');
        $this->writeJson(Code::OK, ['routers' => $routers]);
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
        var_dump(date('Y.m.d H:i:s') . ': start!!!');
        $chan = new Channel(3);
        \go(function () use ($chan) {
            Coroutine::sleep(3);
            $chan->push(['www.qq.com' => '1']);
        });

        \go(function () use ($chan) {
            Coroutine::sleep(3);
            $chan->push(['www.163.com' => '2']);
        });

        \go(function () use ($chan) {
            Coroutine::sleep(3);
            $chan->push(['www.126.com' => '3']);
        });

        $data = [];
        for ($i = 0; $i < 3; $i++) {
            var_dump(date('Y.m.d H:i:s') . ': begin ' . $i);
            $resp = $chan->pop(4);
            var_dump(date('Y.m.d H:i:s') . ': end ' . $i);
            var_dump(date('Y.m.d H:i:s') . ': ' . var_export($resp, true));
            if (is_array($resp)) {
                $data[] = $resp;
            }
        }

        $this->writeJson(Code::OK, $data);
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
        $this->writeJson(Code::OK, ['data' => $data]);
    }

    public function getSessionData()
    {
        $sid = $this->request()->getRequestParam('sess_token');
        $data = (new Session())->getData($sid, 'userinfo');
        $this->writeJson(Code::OK, [
            'uri' => $this->request()->getUri()->__toString(),
            'data' => $data,
        ]);
    }

    public function isLogin()
    {
        $this->checkLogin();
        $this->writeJson();
    }

    public function utest()
    {
        $user = (new UserInfo())->getUserInfo(434570);
        $this->writeJson(200, ['user' => $user]);
    }

    public function userinfo()
    {
        $userIdStr = $this->request()->getRequestParam('user_id');
        if (empty($userIdStr)) {
            return $this->writeJson(Code::PARAM_INVAL);
        }

        $userType = $this->request()->getRequestParam('user_type');
        $fromWhere = $this->request()->getRequestParam('from_where');
        $loginUserId = $this->checkLogin(true);
        $userIds = explode(',', $userIdStr);
        $needMobile = ($this->checkMd5Token(true) || $fromWhere == 'admin') ? true : false;

        if (isset($userType) && intval($userType) != 1) {
            return $this->writeJson(Code::OK);
        }

        $len = count($userIds);
        $userChan = new Channel($len);
        $brmChan = new Channel($len);
        foreach ($userIds as $userId) {
            \go(function () use ($userChan, $userId) {
                $userChan->push((new UserInfo())->getUserInfo($userId));
            });
            \go(function () use ($brmChan, $userId) {
                $brmChan->push([$userId => (new \App\Service\Brm\UserInfo())->getByUserId($userId)]);
            });
        }
        $userInfos = [];
        $brmInfos = [];
        if ($fromWhere != 'admin') {
            $comInfos = [];
        }
        for ($i = 0; $i < $len; $i++) {
            $userInfos[] = $userChan->pop();
            $brmInfos[] = $brmChan->pop();
        }

        $advChan = new Channel($len);
        $innChan = new Channel($len);
        if ($fromWhere != 'admin') {
            $comChan = new Channel($len);
        }
        foreach ($userInfos as $info) {
            if (is_null($info) || !isset($info['user_id'])) {
                continue;
            }
            $userId = $info['user_id'];
            \go(function () use ($advChan, $userId) {
                $advChan->push([$userId => (new Adviser())->getInfoByUserId($userId)]);
            });
            \go(function () use ($innChan, $userId) {
                $innChan->push([$userId => (new InnerVip())->getInfoByUserId($userId)]);
            });
            if ($fromWhere != 'admin') {
                \go(function () use ($comChan, $userId) {
                    $comChan->push([$userId => (new Community())->getInfoByUserId($userId)]);
                });
            }
        }
        $advInfos = [];
        $innInfos = [];
        if ($fromWhere != 'admin') {
            $comInfos = [];
        }
        for ($i = 0; $i < $len; $i++) {
            $advInfos[] = $advChan->pop();
            $innInfos[] = $innChan->pop();
            if ($fromWhere != 'admin') {
                $comInfos[] = $comChan->pop();
            }
        }

        $len = count($userInfos);
        $infoChan = new Channel($len);
        foreach ($userInfos as $info) {
            \go(function () use ($infoChan, $info, $loginUserId, $fromWhere, $needMobile, $brmInfos, $comInfos, $advInfos, $innInfos) {
                $infoUserId = $info['user_id'];
                if (isset($info['mobile']) && !$needMobile) {
                    unset($info['mobile']);
                }
                if (isset($info['is_shield']) && $info['is_shield'] == 1) {
                    $info['nickname'] = 'hxs' . $infoUserId;
                    $info['head_img'] = Config::getInstance()->getConf('param.default_head_img');
                }
                if (isset($info['scale_name']) && $info['scale_name'] == -1) {
                    $info['scale_name'] = '';
                }
                if (isset($info['scale_descr']) && $info['scale_descr'] == -1) {
                    $info['scale_descr'] = '';
                }
                if (isset($info['shake_name']) && $info['shake_name'] == -1) {
                    $info['shake_name'] = '';
                }
                if (isset($info['shake_descr']) && $info['shake_descr'] == -1) {
                    $info['shake_descr'] = '';
                }
                $info['age'] = ($age = Pub::birthdayToAge($info['birthday'])) != null ? $age . '' : '';
                if (isset($advInfos[$infoUserId])) {
                    $info['adviser_info'] = $advInfos[$infoUserId];
                    if (isset($advInfos[$infoUserId]['show_index']) && $advInfos[$infoUserId]['show_index'] == 0 && isset($advInfos[$infoUserId]['head_img'])) {
                        $info['head_img'] = $advInfos[$infoUserId]['head_img'];
                    }
                }
                if (isset($innInfos[$infoUserId])) {
                    $info['inner_v_info'] = $innInfos[$infoUserId];
                }
                if (isset($brmInfos[$infoUserId])) {
                    $info['brm_user_id'] = isset($brmInfos[$infoUserId]['brm_user_id']) ? $brmInfos[$infoUserId]['brm_user_id'] : '0';
                    $info['brm_username'] = isset($brmInfos[$infoUserId]['realname']) ? $brmInfos[$infoUserId]['realname'] : '';
                    $info['encode_phone'] = isset($brmInfos[$infoUserId]['encode_phone']) ? $brmInfos[$infoUserId]['encode_phone'] : '';
                }
                if ($fromWhere != 'admin') {
                    $info['fans_num'] = isset($comInfos[$infoUserId]['zFansCount']) ? $comInfos[$infoUserId]['zFansCount'] . '' : '0';
                    $info['follows_num'] = isset($comInfos[$infoUserId]['zFollowsCount']) ? $comInfos[$infoUserId]['zFollowsCount'] . '' : '0';
                    $info['is_follow'] = isset($comInfos[$infoUserId]['isFollow']) ? $comInfos[$infoUserId]['isFollow'] . '' : 'no';
                    $info['pullblacked'] = isset($comInfos[$infoUserId]['pullblacked']) ? $comInfos[$infoUserId]['pullblacked'] . '' : 'no';
                    $info['is_pullblack'] = isset($comInfos[$infoUserId]['is_pullblack']) ? $comInfos[$infoUserId]['is_pullblack'] . '' : 'no';

                    // 是不是当前登录用户的顾问
                    $info['is_my_adviser'] = '0';
                    if (isset($info['adviser_info']['brm_id']) && $loginUserId > 0 && $loginUserId != $infoUserId) {
                        $loginUserBrmInfo = (new \App\Service\Brm\UserInfo())->getByUserId($loginUserId);
                        if (isset($loginUserBrmInfo) && $loginUserBrmInfo['brm_adviser_id'] == $info['adviser_info']['brm_id']) {
                            $loginUserAdvInfo = (new Adviser())->getInfoByUserId($loginUserId);
                            if (!isset($loginUserAdvInfo)) {
                                $info['is_my_adviser'] = '1';
                            }
                        }
                    }

                    // TODO::是否能聊天， 未完成迁移
                    $info['can_im'] = '0';
                } else {
                    $advInfo = (new Adviser())->getInfoByUserId($infoUserId);
                    if (isset($advInfo['brm_id'])) {
                        $info['chat_account'] = 'brm-' . $advInfo['brm_id'];
                    } else {
                        $upInfo = (new UserPassport())->getByUserId($infoUserId);
                        $info['chat_account'] = isset($upInfo['openim_account']) ? $upInfo['openim_account'] : '';
                    }
                }
                $infoChan->push([$infoUserId => $info]);
            });
        }

        $users = [];
        for ($i = 0; $i < $len; $i++) {
            $users[] = $infoChan->pop();
        }

        $this->writeJson(Code::OK, ['users' => $users]);
    }
}