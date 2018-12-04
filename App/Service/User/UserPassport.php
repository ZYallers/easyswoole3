<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service\User;

use App\Utility\Abst\Service;

class UserPassport extends Service
{
    use \EasySwoole\Component\Singleton;

    public function getByUserId(int $userId): ?array
    {
        $item = (new \App\Cache\User\UserPassport())->getByUserId($userId);
        return $item;
    }
}