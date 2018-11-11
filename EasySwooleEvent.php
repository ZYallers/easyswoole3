<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;

use App\Throwable\Handler;
use App\Utility\Pool\Mysql\Enjoythin;
use App\Utility\Pool\Redis\Cache;
use App\Utility\Status;
use EasySwoole\Component\Di;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\Time\Timer;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Utility\File;

class EasySwooleEvent implements Event
{

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
        // 注入连接池
        PoolManager::getInstance()->register(Enjoythin::class, Config::getInstance()->getConf('mysql.enjoythin.POOL_MAX_NUM'));
        PoolManager::getInstance()->register(Cache::class, Config::getInstance()->getConf('redis.cache.POOL_MAX_NUM'));
    }

    private static function loadAppConf(): void
    {
        $files = File::scanDirectory(EASYSWOOLE_ROOT . '/App/Conf');
        if (is_array($files)) {
            foreach ($files['files'] as $file) {
                Config::getInstance()->loadFile($file);
            }
        }
    }

    private static function versionCheck(Request &$request, Response $response): void
    {
        $msg = null;
        $uriPath = substr($request->getUri()->getPath(), 1);
        $router = Config::getInstance()->getConf('router');
        if (isset($router[$uriPath])) {
            $uriRouter = $router[$uriPath];
            if (in_array(strtolower($request->getMethod()), explode(',', $uriRouter['method']))) {
                $version = $request->getRequestParam('version');
                // 如果没传，默认获取当前最新版本
                if (empty($version)) {
                    $version = Config::getInstance()->getConf('app.version');
                }
                $vsflag = false;
                // 先获取router支持的版本，然后遍历
                foreach (explode('|', $uriRouter['version']) as $item) {
                    // 判断是否包含'+'支持以上版本
                    if (strpos($item, '+') !== false) {
                        // 判断 $version 是否大于等于要求的 $vs 版本
                        $vs = substr($item, 0, -1);
                        if (version_compare($version, $vs, '>=')) {
                            $version = $vs;
                            $vsflag = true;
                            break;
                        }
                    } else {
                        // 判断 $version 是否等于要求的 $vs 版本
                        if ($version == $item) {
                            $vsflag = true;
                            break;
                        }
                    }
                }
                if ($vsflag) {
                    $module = join('', explode('.', $version));
                    $path = "/v{$module}/{$uriPath}";
                    $request->getUri()->withPath($path);
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
            $data = ['code' => Status::CODE_NOT_FOUND, 'data' => null, 'msg' => $msg];
            $response->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');
            $response->withStatus(Status::CODE_NOT_FOUND);
            $response->end();
        }
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        // 天天都在问的服务热重启 单独启动一个进程处理
        /*if (Config::getInstance()->getConf('app.debug')) {
            ServerManager::getInstance()->getSwooleServer()->addProcess((new \App\Process\Inotify('inotify_process'))->getProcess());
        }*/

        $register->add($register::onWorkerStart, function (\swoole_server $server, int $workerId) {
            echo '--------------- worker ' . $workerId . ' start ---------------' . PHP_EOL;
        });
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        $request->withAttribute('request_time', microtime(true));

        // ============ 接口版本校验 ============
        self::versionCheck($request, $response);
        return true;
    }

    private static function saveSlowLog(Request $request): void
    {
        $nowTime = microtime(true);
        $reqTime = $request->getAttribute('request_time', 0);
        if ($reqTime > 0 && ($nowTime - $reqTime) > 3) {
            Timer::delay(5000, function () use ($request, $reqTime, $nowTime) {
                // 计算一下运行时间
                $runTime = round($nowTime - $reqTime, 6) . 's';
                // 获取用户IP地址
                $ip = ServerManager::getInstance()->getSwooleServer()->connection_info($request->getSwooleRequest()->fd);
                $ip = isset($ip['remote_ip']) ? $ip['remote_ip'] : 'unknow';
                // 拼接日志内容
                $data = ['ip' => $ip, 'time' => date('Y-m-d H:i:s', $reqTime), 'runtime' => $runTime, 'uri' => $request->getUri()->__toString()];
                $userAgent = $request->getHeader('user-agent');
                if (is_array($userAgent) && count($userAgent) > 0) {
                    $data['user_agent'] = $userAgent[0];
                }
                Logger::getInstance()->log(var_export($data, true), 'slow');
            });
        }
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
        // ========= 超过3秒记录到slow日志文件 =========
        self::saveSlowLog($request);
    }

    public static function onReceive(\swoole_server $server, int $fd, int $reactor_id, string $data): void
    {
        // TODO: Implement afterAction() method.
    }
}