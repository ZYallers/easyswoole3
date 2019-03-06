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
        $obj = new RedisObject();
        $conf = Config::getInstance()->getConf('redis.session');
        if (!$obj->connect($conf['host'], $conf['port'])) {
            return null;
        }
        if (!empty($conf['auth']) && !$obj->auth($conf['auth'])) {
            return null;
        }
        return $obj;
    }
}