<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午5:45
 */

namespace App\Utility\Pool\Redis;

use App\Utility\Pool\RedisObject;
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;

class Session extends AbstractPool
{
    protected function createObject()
    {
        // TODO: Implement createObject() method.
        $return = null;
        try {
            $redis = new RedisObject();
            $conf = Config::getInstance()->getConf('redis.session');
            if ($redis->connect($conf['host'], $conf['port'])) {
                if (!empty($conf['auth'])) {
                    $redis->auth($conf['auth']);
                }
                $return = $redis;
            }
        } catch (\Throwable $throwable) {
            // to do something...
            throw $throwable;
        } finally {
            return $return;
        }
    }
}