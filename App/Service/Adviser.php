<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service;


use App\Utility\Abst\Service;

class Adviser extends Service
{
    use \EasySwoole\Component\Singleton;

    public function getInfoByUserId(int $userId): ?array
    {
        $item = (new \App\Cache\Adviser())->getByUserId($userId);
        return $item;
    }
}