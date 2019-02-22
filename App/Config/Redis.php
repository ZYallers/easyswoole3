<?php
/**
 * Redis 配置文件
 */

return [
    'cache' => [
        'host' => getenv('redis_host'),
        'port' => getenv('redis_port'),
        'auth' => getenv('redis_password'),
        'pool' => [
            'maxnum' => 16, // 最大连接数
            'minnum' => 2, // 最小连接数
            'timeout' => 0.5, // 获取对象超时时间，单位秒
            'idletime' => 30, // 连接池对象存活时间，单位秒
            'checktime' => 60000, // 多久执行一次回收检测，单位毫秒
        ],
    ],
    'session' => [
        'host' => getenv('redis_session_host'),
        'port' => getenv('redis_session_port'),
        'auth' => getenv('redis_session_password'),
        'mod_rate' => 300, // 5分钟；更新频率，单位秒
        'expire_time' => 2592000, //30天；有效时间，单位秒
        'pool' => [
            'maxnum' => 8, // 最大连接数
            'minnum' => 2, // 最小连接数
            'timeout' => 0.5, // 获取对象超时时间，单位秒
            'idletime' => 30, // 连接池对象存活时间，单位秒
            'checktime' => 60000, // 多久执行一次回收检测，单位毫秒
        ],
    ]
];