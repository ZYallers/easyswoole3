<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午8:25
 */

namespace App\Cache;

use App\Utility\Pool\Redis\Cache;

class InnerVip extends \App\Utility\Abst\Cache
{
    public function __construct(string $className = null)
    {
        parent::__construct(Cache::class);
    }

    public function getByUserId(int $userId): ?array
    {
        $key = KeyConst::INNER_VIP_INFO_PREFIX . $userId;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $row = (new \App\Model\InnerVip())->getOneByWhere(['user_id' => $userId]);
            $ttl = is_null($row) ? $this->getNullTtl() : $this->getRandomTtl();
            $Redis->setex($key, $ttl, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $row;
        }
    }
}