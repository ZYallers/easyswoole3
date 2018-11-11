<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午8:25
 */

namespace App\Cache\User;


use App\Cache\Base;
use App\Model\User\UseInfo;
use App\Utility\Pool\Redis\Cache;

class UserInfo extends Base
{
    public function __construct(string $className = null)
    {
        parent::__construct(Cache::class);
    }

    public function getUserInfo(string $mobile)
    {
        $key = 'eshxs:user:getuserinfo4:' . $mobile;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $User = (new UseInfo())->getOneByWhere([['mobile', $mobile]]);
            $Redis->setex($key, 5, json_encode($User, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $User;
        }
    }

    public function getUserById(int $id)
    {
        $key = 'eshxs:user:getuserbyid4:' . $id;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $User = (new UseInfo())->getOneByWhere([['id', $id]]);
            $Redis->setex($key, 5, json_encode($User, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $User;
        }
    }
}