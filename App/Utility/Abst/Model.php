<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Utility\Abst;

use App\Utility\Pool\MysqlObject;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;

abstract class Model
{
    private $db;
    private $className;
    protected $tableName;

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
            PoolManager::getInstance()->getPool($this->className)->recycleObj($this->db);
            if (Config::getInstance()->getConf('RUN_MODE') == 'develop') {
                echo '[' . date('Y-m-d H:i:s') . '] Mysql pool recycle. LastQuery: [' . $this->db->getLastQuery() . '].' . PHP_EOL;
            }
        }
    }

    public function getOneByWhere(array $where): ?array
    {
        foreach ($where as $item) {
            call_user_func_array([$this->db, 'where'], $item);
        }
        return $this->db->getOne($this->tableName);
    }
}