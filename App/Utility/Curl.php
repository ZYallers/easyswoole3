<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-10-16
 * Time: 下午2:16
 */

namespace App\Utility;

use EasySwoole\Component\Singleton;
use EasySwoole\Curl\Field;
use EasySwoole\Curl\Request;
use EasySwoole\Curl\Response;

class Curl
{
    use Singleton;

    /**
     * @param string $method
     * @param string $uri
     * @param array|null $params
     * @return Response
     */
    public function request(string $method, string $uri, array $params = null): Response
    {
        $request = new Request($uri);
        switch (strtoupper($method)) {
            case 'GET' :
                if ($params && isset($params['query'])) {
                    foreach ($params['query'] as $key => $value) {
                        $request->addGet(new Field($key, $value));
                    }
                }
                break;
            case 'POST' :
                if ($params && isset($params['form_params'])) {
                    foreach ($params['form_params'] as $key => $value) {
                        $request->addPost(new Field($key, $value));
                    }
                } elseif ($params && isset($params['body'])) {
                    if (!isset($params['header']['Content-Type'])) {
                        $params['header']['Content-Type'] = 'application/json; charset=utf-8';
                    }
                    $request->setUserOpt([CURLOPT_POSTFIELDS => $params['body']]);
                }
                break;
            default:
                throw new \InvalidArgumentException("Method error");
                break;
        }

        if (isset($params['header']) && !empty($params['header']) && is_array($params['header'])) {
            foreach ($params['header'] as $key => $value) {
                $string = "{$key}:$value";
                $header[] = $string;
            }
            $request->setUserOpt([CURLOPT_HTTPHEADER => $header]);
        }

        if (isset($params['opt']) && !empty($params['opt']) && is_array($params['opt'])) {
            $request->setUserOpt($params['opt']);
        }

        return $request->exec();
    }
}