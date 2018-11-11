<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Model;

use App\Utility\Pool\MysqlObject;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;

abstract class Base
{
    private $db;
    private $className;

    protected function __construct(string $className)
    {
        $this->className = $className;
        $timeout = Config::getInstance()->getConf('mysql.' . strtolower(basename(str_replace('\\', '/', $this->className))) . '.POOL_TIME_OUT');
        $db = PoolManager::getInstance()->getPool($this->className)->getObj($timeout);
        if ($db instanceof MysqlObject) {
            $this->db = $db;
        } else {
            throw new \Exception('Mysql pool is empty');
        }
    }

    /**
     * getDb
     * @return MysqlObject
     */
    protected function getDb()
    {
        return $this->db;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->db instanceof MysqlObject) {
            if (Config::getInstance()->getConf('app.debug')) {
                echo 'At ' . date('Y-m-d H:i:s') . ', LastQuery: [' . $this->db->getLastQuery() . '], Mysql pool recycle.' . "\n";
            }
            PoolManager::getInstance()->getPool($this->className)->recycleObj($this->db);
        }
    }
}