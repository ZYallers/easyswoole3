<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午5:40
 */

namespace App\Utility\Pool\Mysql;


use App\Utility\Pool\MysqlObject;
use App\Utility\Pool\PoolObject;
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;

class Enjoythin extends AbstractPool
{

    protected function createObject()
    {
        // TODO: Implement createObject() method.
        $return = null;
        try {
            $return = new MysqlObject(new \EasySwoole\Mysqli\Config(Config::getInstance()->getConf('mysql.enjoythin')));
        } catch (\Throwable $throwable) {
            // to do something...
            throw $throwable;
        } finally {
            return $return;
        }
    }
}