<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:38
 */

namespace App\Model\User;

use App\Model\Base;
use App\Utility\Pool\Mysql\Enjoythin;

class UseInfo extends Base
{
    private $tableName = 'et_user_info';

    public function __construct(string $className = null)
    {
        parent::__construct(Enjoythin::class);
    }

    public function getOneByWhere(array $where)
    {
        foreach ($where as $item) {
            call_user_func_array([$this->getDb(), 'where'], $item);
        }
        return $this->getDb()->getOne($this->tableName);
    }
}