<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service\User;

use App\Utility\Abst\Service;
use App\Utility\Pub;
use EasySwoole\EasySwoole\Config;

class UserInfo extends Service
{
    use \EasySwoole\Component\Singleton;

    public function getUserInfo(int $userId): ?array
    {
        $user = (new \App\Cache\User\UserInfo())->getByUserId($userId);
        if (isset($user)) {
            $girl = Config::getInstance()->getConf('param.default_head_img');
            $boy = Config::getInstance()->getConf('param.default_head_img_boy');
            if (!empty($user['head_img']) && strpos($user['head_img'], 'http://') === false && strpos($user['head_img'], 'https://') === false) {
                $user['head_img'] = Pub::getOssUri($user['head_img']);
            } else if (empty($user['head_img']) || $user['head_img'] == $boy || $user['head_img'] == $girl) {
                $user['head_img'] = ($user['sex'] == 1) ? $boy : $girl;
            }
        }
        return $user;
    }
}