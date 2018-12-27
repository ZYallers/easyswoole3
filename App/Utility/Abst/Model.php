<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:42
 */

namespace App\Utility\Abst;

use App\Utility\AppConst;
use App\Utility\Pool\MysqlObject;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;

abstract class Model
{
    private $db;
    private $className;
    private $tryTimes = 3;
    protected $tableName;

    protected function __construct(string $className)
    {
        $this->className = $className;
        for ($i = 0; $i < $this->tryTimes; $i++) {
            $db = PoolManager::getInstance()->getPool($this->className)->getObj();
            if ($db instanceof MysqlObject) {
                $this->db = $db;
                break;
            }
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