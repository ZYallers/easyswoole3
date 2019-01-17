<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\Controller\Http\V410\Account;

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
use Swoole\Coroutine\Channel;

class User extends Controller
{
    function getSessionData()
    {
        $sid = $this->request()->getRequestParam('sess_token');
        $data = (new Session())->getData($sid, 'userinfo');
        $this->writeJson(Code::OK, [
            'uri' => $this->request()->getUri()->__toString(),
            'data' => $data,
        ]);
    }

    function isLogin()
    {
        $userId = $this->checkLogin();
        if (is_null($userId)) {
            return $this->writeJson(Code::NOT_LOGIN);
        }
        $this->writeJson();
    }

    function info()
    {
        $userId = $this->request()->getRequestParam('id');
        if (!intval($userId) > 0) {
            return $this->writeJson(Code::PARAM_INVAL);
        }
        $userInfo = UserInfo::getInstance()->getUserInfo(intval($userId));
        $this->writeJson(Code::OK, ['userinfo' => $userInfo]);
    }

    function infos()
    {
        $userIdStr = $this->request()->getRequestParam('ids');
        if (empty($userIdStr)) {
            return $this->writeJson(Code::PARAM_INVAL);
        }

        $userType = $this->request()->getRequestParam('user_type');
        $fromWhere = $this->request()->getRequestParam('from_where');
        $loginUserId = $this->checkLogin();
        $userIds = explode(',', $userIdStr);
        $needMobile = ($this->checkMd5Token() == Code::OK || $fromWhere == 'admin') ? true : false;

        if (isset($userType) && intval($userType) != 1) {
            return $this->writeJson(Code::OK);
        }

        $len = count($userIds);
        $userChan = new Channel($len);
        $brmChan = new Channel($len);
        foreach ($userIds as $userId) {
            go(function () use ($userChan, $userId) {
                $userChan->push(UserInfo::getInstance()->getUserInfo($userId));
            });
            go(function () use ($brmChan, $userId) {
                $brmChan->push([$userId => \App\Service\Brm\UserInfo::getInstance()->getByUserId($userId)]);
            });
        }
        $userInfos = [];
        $brmInfos = [];
        if ($fromWhere != 'admin') {
            $comInfos = [];
        }
        for ($i = 0; $i < $len; $i++) {
            if ($rev = $userChan->pop()) {
                $userInfos[] = $rev;
            }
            if ($rev = $brmChan->pop()) {
                $brmInfos[] = $rev;
            }
        }
        $userChan->close();
        $brmChan->close();

        if (($len = count($userInfos)) === 0) {
            return $this->writeJson(Code::OK);
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
            go(function () use ($advChan, $userId) {
                $advChan->push([$userId => Adviser::getInstance()->getInfoByUserId($userId)]);
            });
            go(function () use ($innChan, $userId) {
                $innChan->push([$userId => InnerVip::getInstance()->getInfoByUserId($userId)]);
            });
            if ($fromWhere != 'admin') {
                go(function () use ($comChan, $userId) {
                    $comChan->push([$userId => Community::getInstance()->getInfoByUserId($userId)]);
                });
            }
        }
        $advInfos = [];
        $innInfos = [];
        if ($fromWhere != 'admin') {
            $comInfos = [];
        }
        for ($i = 0; $i < $len; $i++) {
            if ($rev = $advChan->pop()) {
                $advInfos[] = $rev;
            }
            if ($rev = $innChan->pop()) {
                $innInfos[] = $rev;
            }
            if ($fromWhere != 'admin') {
                if ($rev = $comChan->pop()) {
                    $comInfos[] = $rev;
                }
            }
        }
        $advChan->close();
        $innChan->close();
        if ($fromWhere != 'admin') {
            $comChan->close();
        }

        $infoChan = new Channel($len);
        foreach ($userInfos as $info) {
            go(function () use ($infoChan, $info, $loginUserId, $fromWhere, $needMobile, $brmInfos, $comInfos, $advInfos, $innInfos) {
                $infoUserId = $info['user_id'];

                $chan = new Channel(1);
                go(function () use ($chan, $info, $infoUserId, $loginUserId, $fromWhere) {
                    if ($fromWhere != 'admin') {
                        // 是不是当前登录用户的顾问
                        $info['is_my_adviser'] = '0';
                        if (isset($info['adviser_info']['brm_id']) && $loginUserId > 0 && $loginUserId != $infoUserId) {
                            $loginUserBrmInfo = \App\Service\Brm\UserInfo::getInstance()->getByUserId($loginUserId);
                            if (isset($loginUserBrmInfo) && $loginUserBrmInfo['brm_adviser_id'] == $info['adviser_info']['brm_id']) {
                                $loginUserAdvInfo = Adviser::getInstance()->getInfoByUserId($loginUserId);
                                if (!isset($loginUserAdvInfo)) {
                                    $info['is_my_adviser'] = '1';
                                }
                            }
                        }
                        // TODO::是否能聊天，未完成迁移
                        $info['can_im'] = '0';
                    } else {
                        $advInfo = Adviser::getInstance()->getInfoByUserId($infoUserId);
                        if (isset($advInfo['brm_id'])) {
                            $info['chat_account'] = 'brm-' . $advInfo['brm_id'];
                        } else {
                            $upInfo = UserPassport::getInstance()->getByUserId($infoUserId);
                            $info['chat_account'] = isset($upInfo['openim_account']) ? $upInfo['openim_account'] : '';
                        }
                    }
                    $chan->push($info);
                });

                if ($fromWhere != 'admin') {
                    $info['fans_num'] = isset($comInfos[$infoUserId]['zFansCount']) ? $comInfos[$infoUserId]['zFansCount'] . '' : '0';
                    $info['follows_num'] = isset($comInfos[$infoUserId]['zFollowsCount']) ? $comInfos[$infoUserId]['zFollowsCount'] . '' : '0';
                    $info['is_follow'] = isset($comInfos[$infoUserId]['isFollow']) ? $comInfos[$infoUserId]['isFollow'] . '' : 'no';
                    $info['pullblacked'] = isset($comInfos[$infoUserId]['pullblacked']) ? $comInfos[$infoUserId]['pullblacked'] . '' : 'no';
                    $info['is_pullblack'] = isset($comInfos[$infoUserId]['is_pullblack']) ? $comInfos[$infoUserId]['is_pullblack'] . '' : 'no';
                }

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

                if ($rev = $chan->pop()) {
                    $info = array_merge($info, $rev);
                    $chan->close();
                }

                $infoChan->push([$infoUserId => $info]);
            });
        }

        $users = [];
        for ($i = 0; $i < $len; $i++) {
            $users[] = $infoChan->pop();
        }
        $infoChan->close();

        $this->writeJson(Code::OK, ['users' => $users]);
    }
}