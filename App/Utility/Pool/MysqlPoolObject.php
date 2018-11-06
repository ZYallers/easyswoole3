<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午5:49
 */

namespace App\Utility\Pool;

use EasySwoole\Component\Pool\PoolObjectInterface;

class MysqlPoolObject extends \MysqliDb implements PoolObjectInterface
{
    public function gc()
    {
        // TODO: Implement gc() method.
        $this->rollback();
        $this->disconnect();
    }

    public function objectRestore()
    {
        // TODO: Implement objectRestore() method.
        $this->rollback();
        $this->reset();
    }

    public function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}