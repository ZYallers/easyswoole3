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
    'test/index/is-login' => ['version' => '4.1.0+', 'method' => 'get', 'forward' => 'test/index/isLogin'],
    'account/user/session-data' => ['version' => '4.1.0+', 'method' => 'get', 'forward' => 'account/user/getSessionData'],
    'account/user/info' => ['version' => '4.1.0+', 'method' => 'get'],
    'account/user/infos' => ['version' => '4.1.0+', 'method' => 'get'],
];
