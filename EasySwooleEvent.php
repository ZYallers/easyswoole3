<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;


use App\Exception\ExceptionHandler;
use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use EasySwoole\Component\Di;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use App\Utility\Status;
use App\Utility\SysConst;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Utility\File;

class EasySwooleEvent implements Event
{
    public static function loadPath(): void
    {
        $files = File::scanDirectory(EASYSWOOLE_ROOT . '/App/Conf');
        if (is_array($files)) {
            foreach ($files['files'] as $file) {
                $fileNameArr = explode('.', $file);
                $fileSuffix = end($fileNameArr);
                if ($fileSuffix == 'php') {
                    Config::getInstance()->loadFile($file);
                } elseif ($fileSuffix == 'env') {
                    Config::getInstance()->loadEnv($file);
                }
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
                    $version = Config::getInstance()->getConf('APP_VERSION');
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

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
        // 载入项目 Conf 文件夹中的所有的配置文件
        self::loadPath(EASYSWOOLE_ROOT . '/Conf');
        // 设置POST/文件上传最大尺寸限制
        //Config::getInstance()->setConf('MAIN_SERVER.SETTING.package_max_length', 2 * 1024 * 1024);
        // 允许 URL 最大解析至5层
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_MAX_DEPTH, 5);
        // 异常捕获处理
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, [ExceptionHandler::class, 'handle']);
        // 注入 Mysql 连接池
        PoolManager::getInstance()->register(MysqlPool::class);
        // 注入 Redis 连接池
        PoolManager::getInstance()->register(RedisPool::class);
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        // 天天都在问的服务热重启 单独启动一个进程处理
        /*if (Config::getInstance()->getConf('DEBUG')) {
            ServerManager::getInstance()->getSwooleServer()->addProcess((new \App\Process\Inotify('inotify_process'))->getProcess());
        }*/
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        $request->withAttribute('request_time', microtime(true));
        // 接口版本校验
        self::versionCheck($request, $response);
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
        // 超过 3 秒记录到 slow 日志文件
        $debugInfo = $request->getAttribute('debug_info');
        if (is_array($debugInfo) && isset($debugInfo['runtime']) && $debugInfo['runtime'] >= 3) {
            Logger::getInstance()->log(join('|', $debugInfo), 'slow');
        }
    }
}