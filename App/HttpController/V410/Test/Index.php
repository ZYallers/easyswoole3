<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午7:25
 */

namespace App\HttpController\V410\Test;

use App\HttpController\Base;
use App\Utility\Status;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Swoole\Time\Timer;

class Index extends Base
{
    public function banben()
    {
        Timer::delay(20000, function () {
            Logger::getInstance()->log('deeess11', 'debug');
        });
        $this->writeJson(Status::CODE_OK, ['uri' => $this->request()->getUri()->getPath()]);
    }
}