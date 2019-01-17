<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Utility\Abst;

use App\Utility\Pool\RedisObject;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;

abstract class Cache
{
    private $cache;
    private $className;
    private $tryTimes = 3;

    public function __construct(string $className)
    {
        $this->className = $className;
        for ($i = 0; $i < $this->tryTimes; $i++) {
            $cache = PoolManager::getInstance()->getPool($this->className)->getObj();
            if ($cache instanceof RedisObject) {
                $this->cache = $cache;
                break;
            }
        }
        if (!$cache instanceof RedisObject) {
            throw new \Exception('Cache pool is empty');
        }
    }

    /**
     * getCache
     * @return RedisObject
     */
    public function getCache()
    {
        return $this->cache;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->cache instanceof RedisObject) {
            PoolManager::getInstance()->getPool($this->className)->recycleObj($this->cache);
            /*if (Config::getInstance()->getConf('RUN_MODE') == AppConst::RM_DEV) {
                echo '[' . date('Y-m-d H:i:s') . '] Redis pool recycle.' . "\n";
            }*/
        }
    }

    protected function getRandomTtl(int $maxDay = 5): int
    {
        mt_srand();
        return mt_rand(($maxDay - 1) * 86400, $maxDay * 86400);
    }

    protected function getNullTtl(): int
    {
        return 60;
    }
}