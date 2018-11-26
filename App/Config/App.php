<?php
/**
 * Created by PhpStorm.
 * User: Neo
 * Date: 2018/11/24
 * Time: 下午6:56
 */

return [
    'name' => 'easyswoole3',
    'version' => '4.1.0',
    'token' => [
        'key' => '',
        'timeout' => 60
    ],
    'slow_log' => [
        'enable' => true,
        'second' => 3
    ],
    'dingtalk' => [
        'enable' => true,
        'uri' => 'https://oapi.dingtalk.com/robot/send?access_token=56b49320c790446f95a6e3e0a760e7beebbd32ca56a8ade9372023118336472b'
    ]
];