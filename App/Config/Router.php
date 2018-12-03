<?php
/**
 * 路由配置文件
 */

return [
    'test/index/allconfig' => ['version' => '4.0.0+', 'method' => 'get'],
    'test/index/sleep' => ['version' => '4.0.0+', 'method' => 'get'],
    'test/index/bingfa' => ['version' => '4.0.0+', 'method' => 'get'],
    'test/index/banben' => ['version' => '4.0.0|4.1.0+', 'method' => 'get,post'],
    'test/index/curl' => ['version' => '4.0.0+', 'method' => 'get'],
    'test/index/asyncdemo' => ['version' => '4.1.0+', 'method' => 'get,post'],
    'test/index/gotest' => ['version' => '4.1.0+', 'method' => 'get'],
    'test/index/ptest' => ['version' => '4.1.0+', 'method' => 'get'],
    'test/index/routers' => ['version' => '4.1.0+', 'method' => 'get'],
    'test/index/session-data' => ['version' => '4.1.0+', 'method' => 'get', 'forward' => 'test/index/getSessionData'],
    'test/index/is-login' => ['version' => '4.1.0+', 'method' => 'get', 'forward' => 'test/index/isLogin'],
    'test/index/userinfo' => ['version' => '4.1.0+', 'method' => 'get'],
    'test/index/utest' => ['version' => '4.1.0+', 'method' => 'get'],
];
