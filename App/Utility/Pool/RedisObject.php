<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/9
 * Time: 下午7:31
 */

namespace App\Utility\Pool;


use EasySwoole\Component\Pool\PoolObjectInterface;
use Swoole\Coroutine\Redis;

class RedisObject extends Redis implements PoolObjectInterface
{
    public function gc()
    {
        // TODO: Implement gc() method.
        $this->close();
    }

    public function objectRestore()
    {
        // TODO: Implement objectRestore() method.
    }

    public function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }

}