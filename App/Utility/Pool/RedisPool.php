<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午5:45
 */

namespace App\Utility\Pool;

use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;
use Swoole\Coroutine\Redis;

class RedisPool extends AbstractPool
{
    protected function createObject()
    {
        // TODO: Implement createObject() method.
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('Not support redis');
        }
        $conf = Config::getInstance()->getConf('redis');
        $Redis = new Redis;
        if (isset($conf['persistent']) && $conf['persistent'] === true) {
            $Redis->pconnect($conf['host'], $conf['port'], $conf['timeout'], 'persistent_id_' . $conf['select']);
        } else {
            $Redis->connect($conf['host'], $conf['port'], $conf['timeout']);
        }
        if (isset($conf['password']) && !empty($conf['password'])) {
            $Redis->auth($conf['password']);
        }
        if (isset($conf['select']) && $conf['select'] !== 0) {
            $Redis->select($conf['select']);
        }
        return $Redis;
    }
}