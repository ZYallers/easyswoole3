<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/27
 * Time: 下午12:38
 */

namespace App\Model\User;

use App\Utility\Abst\Model;
use App\Utility\Pool\Mysql\Enjoythin;

class UserPassport extends Model
{
    public $tableName = 'et_user_passport';

    public function __construct(string $className = null)
    {
        parent::__construct(Enjoythin::class);
    }
}