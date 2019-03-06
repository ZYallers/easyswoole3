<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午5:40
 */

namespace App\Utility\Pool\Mysql;

use App\Utility\Pool\MysqlObject;
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;

class Enjoythin extends AbstractPool
{

    protected function createObject()
    {
        // TODO: Implement createObject() method.
        return new MysqlObject(new \EasySwoole\Mysqli\Config(Config::getInstance()->getConf('mysql.enjoythin')));
    }
}