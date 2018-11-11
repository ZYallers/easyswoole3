<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午3:13
 */

namespace App\Controller\Http;

class Index extends Base
{
    public function index()
    {
        $this->actionNotFound();
    }
}