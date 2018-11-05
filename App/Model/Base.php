<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Model;

use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\MysqlPoolObject;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Config;

abstract class Base
{
    use Singleton;

    private $db;

    public function __construct()
    {
        $db = PoolManager::getInstance()->getPool(MysqlPool::class)->getObj();
        if ($db instanceof MysqlPoolObject) {
            $this->db = $db;
        } else {
            throw new \Exception('Db pool is empty');
        }
    }

    /**
     * getDb
     * @return MysqlPoolObject|mixed|null
     */
    protected function getDb()
    {
        return $this->db;
    }

    public function freeDb()
    {
        if (self::getInstance()->getDb() instanceof MysqlPoolObject) {
            if (Config::getInstance()->getConf('app.debug')) {
                echo 'At ' . date('Y-m-d H:i:s') . ', LastQuery: [' . self::getInstance()->getDb()->getLastQuery() . '], Mysql pool free.' . "\n";
            }
            PoolManager::getInstance()->getPool(MysqlPool::class)->recycleObj(self::getInstance()->getDb());
        }
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->db instanceof MysqlPoolObject) {
            if (Config::getInstance()->getConf('app.debug')) {
                echo 'At ' . date('Y-m-d H:i:s') . ', LastQuery: [' . $this->db->getLastQuery() . '], Mysql pool recycle.' . "\n";
            }
            PoolManager::getInstance()->getPool(MysqlPool::class)->recycleObj($this->db);
        }
    }
}