<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Cache;

use App\Utility\Pool\RedisPool;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Config;
use Swoole\Coroutine\Redis;

abstract class Base
{
    use Singleton;

    private $cache;

    public function __construct()
    {
        $cache = PoolManager::getInstance()->getPool(RedisPool::class)->getObj();
        if ($cache instanceof Redis) {
            $this->cache = $cache;
        } else {
            throw new \Exception('Cache pool is empty');
        }
    }

    /**
     * @return Redis|mixed|null
     */
    protected function getCache()
    {
        return $this->cache;
    }

    public function freeCache()
    {
        if (self::getInstance()->getCache() instanceof Redis) {
            if (Config::getInstance()->getConf('app.debug')) {
                echo 'At ' . date('Y-m-d H:i:s') . ', Cache pool free.' . "\n";
            }
            PoolManager::getInstance()->getPool(RedisPool::class)->recycleObj(self::getInstance()->getCache());
        }
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->cache instanceof Redis) {
            if (Config::getInstance()->getConf('app.debug')) {
                echo 'At ' . date('Y-m-d H:i:s') . ', Cache pool recycle.' . "\n";
            }
            PoolManager::getInstance()->getPool(RedisPool::class)->recycleObj($this->cache);
        }
    }
}