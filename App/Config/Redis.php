<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/26
 * Time: 下午5:27
 */

return [
    'cache' => [
        'host' => '',
        'port' => 6379,
        'auth' => '',
        'POOL_MAX_NUM' => 16,
        'POOL_TIME_OUT' => 0.1,
    ],
    'session' => [
        'host' => '',
        'port' => 6380,
        'auth' => '',
        'POOL_MAX_NUM' => 8,
        'POOL_TIME_OUT' => 0.1,
        'mod_rate' => 300, // 5分钟；更新频率，单位秒
        'expire_time' => 2592000, //30天；有效时间，单位秒
    ]
];