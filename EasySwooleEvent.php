<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;

use App\Throwable\Handler;
use App\Utility\Code;
use App\Utility\Pool\Mysql\Enjoythin;
use App\Utility\Pool\Redis\Cache;
use App\Utility\Pool\Redis\Session;
use App\Utility\Pub;
use EasySwoole\Component\Di;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Utility\File;

class EasySwooleEvent implements Event
{
    private static function setErrorReporting()
    {
        if (Pub::isDev()) {
            ini_set('display_errors', 'On');
            error_reporting(-1);
        } else {
            ini_set('display_errors', 'Off');
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
    }

    private static function loadAppConfigFile(): void
    {
        $scan = File::scanDirectory(EASYSWOOLE_ROOT . '/App/Config');
        if (is_array($scan) && isset($scan['files'])) {
            foreach ($scan['files'] as $file) {
                Config::getInstance()->loadFile($file);
            }
        }
    }

    private static function registerPool(): void
    {
        $Pool = PoolManager::getInstance();
        // mysql.enjoythin
        $poolConf = Config::getInstance()->getConf('mysql.enjoythin.pool');
        $Pool->register(Enjoythin::class, $poolConf['maxnum'])
            ->setMaxIdleTime($poolConf['idletime'])
            ->setMinObjectNum($poolConf['minnum'])
            ->setGetObjectTimeout($poolConf['timeout'])
            ->setIntervalCheckTime($poolConf['checktime']);
        // redis.cache
        $poolConf = Config::getInstance()->getConf('redis.cache.pool');
        $Pool->register(Cache::class, $poolConf['maxnum'])
            ->setMaxIdleTime($poolConf['idletime'])
            ->setMinObjectNum($poolConf['minnum'])
            ->setGetObjectTimeout($poolConf['timeout'])
            ->setIntervalCheckTime($poolConf['checktime']);
        // redis.session
        $poolConf = Config::getInstance()->getConf('redis.session.pool');
        $Pool->register(Session::class, $poolConf['maxnum'])
            ->setMaxIdleTime($poolConf['idletime'])
            ->setMinObjectNum($poolConf['minnum'])
            ->setGetObjectTimeout($poolConf['timeout'])
            ->setIntervalCheckTime($poolConf['checktime']);
    }

    private static function preLoadPool()
    {
        $Pool = PoolManager::getInstance();
        $Pool->getPool(Cache::class)->preLoad(Config::getInstance()->getConf('redis.cache.pool.minnum'));
        $Pool->getPool(Session::class)->preLoad(Config::getInstance()->getConf('redis.session.pool.minnum'));
    }

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        // 设置错误显示级别
        self::setErrorReporting();
        // 设置时区
        date_default_timezone_set('Asia/Shanghai');
        // 载入Config文件夹中的配置文件
        self::loadAppConfigFile();
        // 配置错误处理回调
        Di::getInstance()->set(SysConst::ERROR_HANDLER, [Handler::class, 'errorHandler']);
        // 配置脚本结束回调
        Di::getInstance()->set(SysConst::SHUTDOWN_FUNCTION, [Handler::class, 'shutDownHandler']);
        // 配置http控制器异常回调
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, [Handler::class, 'httpExceptionHandler']);
        // 配置控制器命名空间
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_NAMESPACE, 'App\\Controller\\Http\\');
        // 配置http控制器最大解析层级，默认为5层
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_MAX_DEPTH, 5);
        // 配置http控制器对象池最大数量，默认为15个
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_POOL_MAX_NUM, 15);
        // 配置http控制器对象池等待时间，默认为5秒
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_POOL_WAIT_TIME, 5);
        // 配置自定人日志处理类
        Di::getInstance()->set(SysConst::LOGGER_HANDLER, new \App\Utility\Logger());
        // 注册连接池
        self::registerPool();
    }

    private static function registerCrontabTask(): void
    {
        $Crontab = Crontab::getInstance();
        $nowRunMode = Pub::getRunMode();
        foreach (Config::getInstance()->getConf('crontab') as $task) {
            if (!isset($task['class']) || empty($task['class']) || !class_exists($task['class'])) {
                continue;
            }
            if (isset($task['runmode']) && $task['runmode'] != $nowRunMode) {
                continue;
            }
            if (isset($task['version']) && !Pub::versionCompare($task['version'])) {
                continue;
            }
            $Crontab->addTask($task['class']);
        }
    }

    private static function registerProcess(): void
    {
        $ServerManager = ServerManager::getInstance();
        $nowRunMode = Pub::getRunMode();
        foreach (Config::getInstance()->getConf('process') as $process) {
            if (!isset($process['class']) || empty($process['name']) || !class_exists($process['class'])) {
                continue;
            }
            if (isset($process['runmode']) && $process['runmode'] != $nowRunMode) {
                continue;
            }
            if (isset($process['version']) && !Pub::versionCompare($process['version'])) {
                continue;
            }
            $processName = Pub::getProcessFullName($process['name']);
            $args = isset($process['args']) ? $process['args'] : null;
            $ServerManager->getSwooleServer()->addProcess((new $process['class']($processName, $args))->getProcess());
        }
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.

        // 配置 Swoole 日志文件名
        $Config = Config::getInstance();
        $logFile = $Config->getConf('LOG_DIR') . '/' . $Config->getConf('SERVER_NAME') . '.swoole.log';
        $Config->setConf('MAIN_SERVER.SETTING.log_file', $logFile);
        ServerManager::getInstance()->getSwooleServer()->set(['log_file' => $logFile]);

        // 注册定时任务
        self::registerCrontabTask();

        // 注册自定义进程
        self::registerProcess();

        if (Pub::isDev()) {
            $register->add($register::onConnect, function (\swoole_server $server, int $workerId) {
                echo "[" . Pub::udate() . "]  NOTICE  Server {$workerId} connect.\n";
            });

            $register->add($register::onClose, function (\swoole_server $server, int $workerId) {
                echo "[" . Pub::udate() . "]  NOTICE  Server {$workerId} close.\n";
            });
        }

        $register->add($register::onWorkerStart, function (\swoole_server $server, int $workerId) {
            if (Pub::isDev()) {
                $workerFlag = $server->taskworker ? 'TaskWorker' : 'Worker';
                echo "[" . Pub::udate() . "]  NOTICE  {$workerFlag} {$workerId} start.\n";
            }

            // 预创建连接池对象，避免在启动时突然大量请求,造成连接来不及创建从而失败的问题
            if ($server->taskworker == false) {
                self::preLoadPool();
            }
        });
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        // ============ 接口版本校验 ============
        $msg = Pub::parseUriPath($request);
        if (is_null($msg)) {
            $request->withAttribute('request_time', microtime(true));
            $request->withAttribute('remote_ip', Pub::clientIp($request));
        } else {
            $data = ['code' => Code::NOT_FOUND, 'data' => null, 'msg' => $msg];
            $response->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');
            $response->withStatus(Code::NOT_FOUND);
            $response->end();
        }
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
        if ($response->getStatusCode() == Code::OK) {
            // ========= Session更新处理 =========
            $sid = $request->getRequestParam('sess_token');
            if (!empty($sid)) {
                go(function () use ($sid) {
                    (new \App\Cache\Session())->refreshExpireTime($sid);
                });
            }
            // ========= 超过N秒记录到slow日志文件 =========
            if (Config::getInstance()->getConf('app.slow_log.enable')) {
                go(function () use ($request) {
                    Pub::saveSlowLog($request);
                });
            }
        }
    }
}