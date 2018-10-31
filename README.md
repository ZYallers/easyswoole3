# ES_HXS
依据好享瘦APP现有的接口业务，结合PHP的核心扩展Swoole4.2.4地EasySwoole3框架而搭建的ES_HXS架够。

## 特点
- 高效
- 简单
- 敲代码爽

## 入门教程

1、高手直接看EasySwoole3的文档：https://www.easyswoole.com/Manual/3.x/Cn/_book/

2、详细教程？不存在的～

## 大概流程
1. 升级PHP到7.1；
2. 安装Swoole4.2.4扩展，建议通过pecl方式安装，简单明了，记住要支持async_redis！！！；
3. 安装Redis扩展，同上建议通过pecl安装；
4. 安装composer；
5. git拉取该项目;
6. composer Install；
7. 配置日志和临时文件目录；
8. php easyswoole start；
9. 嗨起来～