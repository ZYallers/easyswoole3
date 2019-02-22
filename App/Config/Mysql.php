<?php
/**
 * Mysql 配置文件
 */

return [
    'enjoythin' => [
        'host' => getenv('mysql_host'),
        'port' => 3306,
        'user' => getenv('mysql_username'),
        'timeout' => 3,
        'charset' => 'utf8',
        'password' => getenv('mysql_password'),
        'database' => getenv('mysql_database'),
        'pool' => [
            'maxnum' => 8, // 最大连接数
            'minnum' => 2, // 最小连接数
            'timeout' => 0.5, // 获取对象超时时间，单位秒
            'idletime' => 30, // 连接池对象存活时间，单位秒
            'checktime' => 60000, // 多久执行一次回收检测，单位毫秒
        ],
    ]
];