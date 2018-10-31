<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/25
 * Time: 下午3:35
 */

namespace App\Exception;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class ExceptionHandler
{
    public static function handle(\Throwable $throwable, Request $request, Response $response)
    {
        $str = $throwable->__toString() . "\n";
        Logger::getInstance()->log($str, 'exception');
        if (Config::getInstance()->getConf('DEBUG')) {
            echo $str;
        }
    }
}