<?php
return [
    'SERVER_NAME' => 'easyswoole3',
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '127.0.0.1',
        'PORT' => 9500,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为:EASYSWOOLE_SERVER/EASYSWOOLE_WEB_SERVER/EASYSWOOLE_SOCKET_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8, //运行的worker进程数量
            'max_request' => 5000, //worker完成该数量的请求后将退出，防止内存溢出
            'task_worker_num' => 8, //运行的task_worker进程数量
            'task_max_request' => 1000, //执行该数量次数后重启task进程
            'log_level' => 5, //设置Server错误日志打印的等级，https://wiki.swoole.com/wiki/page/538.html
            'enable_coroutine' => true, //底层自动在onRequest回调中创建协程，开发者无需自行使用go函数创建协程，https://wiki.swoole.com/wiki/page/949.html
            'max_coroutine' => 3000, //默认值为3000，设置当前工作进程最大协程数量。https://wiki.swoole.com/wiki/page/950.html
            'task_async' => true, //设置为true, task进程会变为异步模式, 允许使用异步/协程API. https://wiki.swoole.com/wiki/page/1014.html
            'tcp_fastopen' => true, //开启TCP快速握手特性。此项特性，可以提升TCP短连接的响应速度，https://wiki.swoole.com/wiki/page/792.html
            'reload_async' => false, //设置异步重启开关。设置为true时，将启用异步安全重启特性，Worker进程会等待异步事件完成后再退出，https://wiki.swoole.com/wiki/page/791.html
            'enable_reuse_port' => true, //设置端口重用，此参数用于优化TCP连接的Accept性能，https://wiki.swoole.com/wiki/page/486.html
            'tcp_defer_accept' => 5, //设置为一个数值，表示当一个TCP连接有数据发送时才触发accept，https://wiki.swoole.com/wiki/page/317.html
            'open_tcp_nodelay' => true, //开启后TCP连接发送数据时会关闭Nagle合并算法，立即发往客户端连接，https://wiki.swoole.com/wiki/page/316.html
            'package_max_length' => 2097152, //默认2M，POST/文件上传最大尺寸限制，https://wiki.swoole.com/wiki/page/301.html
        ]
    ],
    'TEMP_DIR' => '/tmp/easyswoole3', //请以绝对路径，不然守护模式运行会有问题
    'LOG_DIR' => null, //请以绝对路径，不然守护模式运行会有问题
    'CONSOLE' => [
        'ENABLE' => false,
        'LISTEN_ADDRESS' => '127.0.0.1',
        'HOST' => '127.0.0.1',
        'PORT' => 9501,
        'EXPIRE' => '120',
        'AUTH' => null,
        'PUSH_LOG' => false,
    ],
    'RUN_MODE' => 'produce', //运行模式
    'FAST_CACHE' => [
        'PROCESS_NUM' => 1 //FastCache进程数
    ]
];