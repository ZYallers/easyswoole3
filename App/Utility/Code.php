<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/1
 * Time: 上午10:24
 */

namespace App\Utility;


class Code
{
    const SIGN_INVAL = 0000;
    const TOKEN_TIMEOUT = 0001;
    const OK = 200;
    const BAD_REQUEST = 400;
    const NOT_LOGIN = 401;
    const NOT_FOUND = 404;
    const PARAM_INVAL = 501;

    private static $phrases = [
        0000 => 'sign非法',
        0001 => 'token超时',
        200 => 'OK',
        400 => 'Bad Request',
        401 => '请登录后再操作',
        404 => 'Not Found',
        501 => '参数缺少或非法',
    ];

    static function getReasonPhrase($statusCode): string
    {
        return isset(self::$phrases[$statusCode]) ? self::$phrases[$statusCode] : '';
    }
}