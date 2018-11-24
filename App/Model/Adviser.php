<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:38
 */

namespace App\Model;

use App\Utility\Abst\Model;
use App\Utility\Pool\Mysql\Enjoythin;

class Adviser extends Model
{
    public $tableName = 'et_adviser';

    public function __construct(string $className = null)
    {
        parent::__construct(Enjoythin::class);
    }
}