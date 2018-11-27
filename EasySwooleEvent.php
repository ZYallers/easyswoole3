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
use EasySwoole\Component\Di;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Utility\File;

class EasySwooleEvent implements Event
{

    private static function loadAppConf(): void
    {
        $files = File::scanDirectory(EASYSWOOLE_ROOT . '/App/Config');
        if (is_array($files)) {
            foreach ($files['files'] as $file) {
                Config::getInstance()->loadFile($file);
            }
        }
    }

    private static function parseUriPath(Request $request, Response $response): void
    {
        $msg = null;
        $path = substr($request->getUri()->getPath(), 1);
        $routers = Config::getInstance()->getConf('router');
        if (isset($routers[$path])) {
            $router = $routers[$path];
            if (in_array(strtolower($request->getMethod()), explode(',', $router['method']))) {
                $version = $request->getRequestParam('version');
                // 如果没传，默认获取当前最新版本
                if (empty($version)) {
                    $version = Config::getInstance()->getConf('app.version');
                }

                $flag = false;
                // 先获取router支持的版本，然后遍历
                foreach (explode('|', $router['version']) as $item) {
                    // 判断是否包含'+'支持以上版本
                    if (strpos($item, '+') !== false) {
                        // 判断 $version 是否大于等于要求的 $vs 版本
                        $vs = substr($item, 0, -1);
                        if (version_compare($version, $vs, '>=')) {
                            $version = $vs;
                            $flag = true;
                            break;
                        }
                    } else {
                        // 判断 $version 是否等于要求的 $vs 版本
                        if ($version == $item) {
                            $flag = true;
                            break;
                        }
                    }
                }

                if ($flag) {
                    $module = join('', explode('.', $version));
                    // 配置了forward的用forward的，否则延用Path
                    if (isset($router['forward']) && !empty($router['forward'])) {
                        $path = $router['forward'];
                    }
                    $forward = "/v{$module}/{$path}";
                    $request->getUri()->withPath($forward);
                } else {
                    $msg = 'Version does not exist';
                }
            } else {
                $msg = 'Method not allowed';
            }
        } else {
            $msg = 'Uri not found';
        }

        if (!is_null($msg)) {
            $data = ['code' => Code::NOT_FOUND, 'data' => null, 'msg' => $msg];
            $response->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');
            $response->withStatus(Code::NOT_FOUND);
            $response->end();
        }
    }

    private static function saveSlowLog(Request $request): void
    {
        if (Config::getInstance()->getConf('app.slow_log.enable')) {
            $nowTime = microtime(true);
            $reqTime = $request->getAttribute('request_time');
            $second = Config::getInstance()->getConf('app.slow_log.second');
            if (($nowTime - $reqTime) > $second) {
                // 计算一下运行时间
                $runTime = round($nowTime - $reqTime, 6) . 's';
                // 获取用户IP地址
                $ip = $request->getAttribute('remote_ip');
                // 拼接日志内容
                $data = ['ip' => $ip, 'time' => date('Y-m-d H:i:s', $reqTime), 'runtime' => $runTime, 'uri' => $request->getUri()->__toString()];
                $userAgent = $request->getHeader('user-agent');
                if (is_array($userAgent) && count($userAgent) > 0) {
                    $data['user_agent'] = $userAgent[0];
                }
                Logger::getInstance()->log(var_export($data, true), 'slow');
            }
        }
    }

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
        self::loadAppConf(); //载入Conf文件夹中的所有的配置文件
        Di::getInstance()->set(SysConst::ERROR_HANDLER, [Handler::class, 'errorHandler']); //配置错误处理回调
        Di::getInstance()->set(SysConst::SHUTDOWN_FUNCTION, [Handler::class, 'shutDownHandler']); //配置脚本结束回调
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, [Handler::class, 'httpExceptionhandler']); //配置http控制器异常回调
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_NAMESPACE, 'App\\Controller\\Http\\'); //配置控制器命名空间
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_MAX_DEPTH, 5); //配置http控制器最大解析层级，默认为5层
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_POOL_MAX_NUM, 15); //http控制器对象池最大数量，默认为15个

        // 注入日志处理类
        Logger::getInstance()->setLoggerWriter(new \App\Utility\Logger());

        // 注入连接池
        PoolManager::getInstance()->register(Enjoythin::class, Config::getInstance()->getConf('mysql.enjoythin.POOL_MAX_NUM'));
        PoolManager::getInstance()->register(Cache::class, Config::getInstance()->getConf('redis.cache.POOL_MAX_NUM'));
        PoolManager::getInstance()->register(Session::class, Config::getInstance()->getConf('redis.session.POOL_MAX_NUM'));
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        // 天天都在问的服务热重启 单独启动一个进程处理
        /*if (Config::getInstance()->getConf('RUN_MODE') == 'develop') {
            ServerManager::getInstance()->getSwooleServer()->addProcess((new \App\Process\Inotify('inotify_process'))->getProcess());
        }*/

        $register->add($register::onWorkerStart, function (\swoole_server $server, int $workerId) {
            echo '--------------- worker ' . $workerId . ' start ---------------' . PHP_EOL;
        });

        // 注册自定义进程
        //ServerManager::getInstance()->getSwooleServer()->addProcess((new \App\Process\ProcessTest('test_process'))->getProcess());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        // ============ 接口版本校验 ============
        self::parseUriPath($request, $response);

        if (!$response->isEndResponse()) {
            $request->withAttribute('request_time', microtime(true));
            $ip = ServerManager::getInstance()->getSwooleServer()->connection_info($request->getSwooleRequest()->fd);
            $request->withAttribute('remote_ip', isset($ip['remote_ip']) ? $ip['remote_ip'] : 'Unknown');
        }

        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
        // ========= Session更新处理 =========
        \go(function () use ($request) {
            $sid = $request->getRequestParam('sess_token');
            if (!empty($sid)) {
                (new \App\Cache\Session())->refreshExpireTime($sid);
            }
        });

        // ========= 超过N秒记录到slow日志文件 =========
        \go(function () use ($request) {
            self::saveSlowLog($request);
        });
    }

    public static function onReceive(\swoole_server $server, int $fd, int $reactor_id, string $data): void
    {

    }

}