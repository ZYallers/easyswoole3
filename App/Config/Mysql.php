<?php
/**
 * Mysql 配置文件
 */

return [
    'enjoythin' => [
        'host' => '',
        'port' => 3306,
        'user' => '',
        'timeout' => 3,
        'charset' => 'utf8',
        'password' => '',
        'database' => '',
        'pool' => [
            'maxnum' => 8, // 最大连接数
            'minnum' => 1, // 最小连接数
            'timeout' => 0.1, // 获取对象超时时间，单位秒
            'idletime' => 30, // 连接池对象存活时间，单位秒
            'checktime' => 60000, // 多久执行一次回收检测，单位毫秒
        ],
    ]
];