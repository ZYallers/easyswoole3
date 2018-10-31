<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\HttpController\V410\Test;

use App\HttpController\Base;

class Index extends Base
{
    public function banben()
    {
        $this->response()->write($this->request()->getUri()->getPath());
    }
}