<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service;

use App\Utility\Abst\Service;
use App\Utility\Pub;

class Community extends Service
{
    use \EasySwoole\Component\Singleton;

    public function getInfoByUserId(int $userId): ?array
    {
        $item = Pub::requestWithSign('get', 'https://community.hxsapp.com/user/userRelate/getBatchInfo', ['query' => ['user_ids' => $userId]]);
        return isset($item) ? $item['data'] : null;
    }
}