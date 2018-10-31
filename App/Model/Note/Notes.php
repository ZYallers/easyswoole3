<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:38
 */

namespace App\Model\Note;

use App\Model\Base;

class Notes extends Base
{
    private $tableName = 'et_notes';

    public function getOneByWhere(array $where)
    {
        foreach ($where as $item) {
            call_user_func_array([$this->getDb(), 'where'], $item);
        }
        return $this->getDb()->getOne($this->tableName);
    }
}