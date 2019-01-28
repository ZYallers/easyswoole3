<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Utility\Abst;

use App\Utility\Pool\MysqlObject;
use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Component\Pool\Exception\PoolException;
use EasySwoole\Component\Pool\PoolManager;

abstract class Model
{
    private $db;
    private $className;
    protected $tableName;
    private $tryTimes = 3;

    protected function __construct(string $className)
    {
        if (class_exists($className)) {
            $this->className = $className;
            for ($i = 0; $i < $this->tryTimes; $i++) {
                $db = PoolManager::getInstance()->getPool($this->className)->getObj();
                if ($db instanceof MysqlObject) {
                    $this->db = $db;
                    break;
                }
            }
            if (is_null($db)) {
                throw new PoolEmpty("{$className} pool is empty");
            }
            if (!$db instanceof MysqlObject) {
                throw new PoolException("{$className} convert to pool error");
            }
        } else {
            throw new \Exception("{$className} class does not exist");
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
            /*if (Config::getInstance()->getConf('RUN_MODE') == AppConst::RM_DEV) {
                echo '[' . date('Y-m-d H:i:s') . '] Mysql pool recycle. LastQuery: [' . $this->db->getLastQuery() . '].' . PHP_EOL;
            }*/
        }
    }

    public function getOneByWhere(array $where): ?array
    {
        foreach ($where as $whereField => $whereProp) {
            if (is_array($whereProp)) {
                $this->db->where($whereField, ...$whereProp);
            } else {
                $this->db->where($whereField, $whereProp);
            }
        }
        return $this->db->getOne($this->tableName);
    }
}