# EasySwoole3
依托于EasySwoole框架，专为APP接口开发而搭建的一个高并发，多进程，可异步，高可用，多版本控制的API架构。

## EasySwoole
```
  ______                          _____                              _        
 |  ____|                        / ____|                            | |       
 | |__      __ _   ___   _   _  | (___   __      __   ___     ___   | |   ___ 
 |  __|    / _` | / __| | | | |  \___ \  \ \ /\ / /  / _ \   / _ \  | |  / _ \
 | |____  | (_| | \__ \ | |_| |  ____) |  \ V  V /  | (_) | | (_) | | | |  __/
 |______|  \__,_| |___/  \__, | |_____/    \_/\_/    \___/   \___/  |_|  \___|
                          __/ |                                               
                         |___/                                                
```
> https://www.easyswoole.com

EasySwoole 是一款基于Swoole Server 开发的常驻内存型的分布式PHP框架，专为API而生，摆脱传统PHP运行模式在进程唤起和文件加载上带来的性能损失。EasySwoole 高度封装了 Swoole Server 而依旧维持 Swoole Server 原有特性，支持同时混合监听HTTP、自定义TCP、UDP协议，让开发者以最低的学习成本和精力编写出多进程，可异步，高可用的应用服务

### 特性

- 强大的 TCP/UDP Server 框架，多线程，EventLoop，事件驱动，异步，Worker进程组，Task异步任务，毫秒定时器，SSL/TLS隧道加密
- EventLoop API，让用户可以直接操作底层的事件循环，将socket，stream，管道等Linux文件加入到事件循环中
- 定时器、协程对象池、HTTP\SOCK控制器、分布式微服务、RPC支持

### 入门成本

相比传统的FPM框架来说，EasySwoole是有一点的入门成本的，许多设计理念及和环境均与传统的FPM不同，
对于长时间使用LAMP（LANP）技术的开发人员来说会有一段时间的适应期，而在众多的Swoole框架中，EasySwoole上手还是比较容易，根据简单的例子和文档几乎立即就能开启EasySwoole的探索之旅。

### 优势

- 简单易用开发效率高
- 并发百万TCP连接
- TCP/UDP/UnixSock
- 支持异步/同步/协程
- 支持多进程/多线程
- CPU亲和性/守护进程

## 入门教程
高手直接看EasySwoole的官方文档：https://www.easyswoole.com/Manual/3.x/Cn/_book/

详细教程？不存在的～

## 部署流程
1. 升级PHP到7.1版本以上；
2. 安装PHP扩展`Swoole`版本4.2.12以上，建议通过`pecl`方式安装，简单明了；
3. 安装PHP扩展`Redis`，同上建议通过`pecl`安装；
4. 安装`composer`；
5. 克隆下来该项目;
6. 进入项目根目录下执行`composer install`，来拉取需要的依赖库；
7. 执行`composer dump-autoload -a`，优化classmap加载;
8. 配置日志文件目录`Log`和临时文件目录`Temp`；
9. 执行`php easyswoole start d`，开始你的探索之旅！

过程中有什么不懂，请自行百度脑补一波，还是不懂再联系！