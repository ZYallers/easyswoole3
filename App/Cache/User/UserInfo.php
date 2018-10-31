<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午8:25
 */

namespace App\Cache\User;


use App\Cache\Base;

class UserInfo extends Base
{
    public function getUserInfo(string $mobile)
    {
        $key = 'eshxs:user:getuserinfo3:' . $mobile;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $User = (new \App\Model\User\UseInfo())->getOneByWhere([['mobile', $mobile]]);
            $Redis->setex($key, 86400, json_encode($User, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $User;
        }
    }

    public function getUserById(int $id)
    {
        $key = 'eshxs:user:getuserbyid3:' . $id;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $User = (new \App\Model\User\UseInfo())->getOneByWhere([['id', $id]]);
            $Redis->setex($key, 86400, json_encode($User, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $User;
        }
    }
}