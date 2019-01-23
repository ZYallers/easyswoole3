<?php
namespace App\Utility;

use EasySwoole\Component\Singleton;
use EasySwoole\Curl\Field;
use EasySwoole\Curl\Request;
use EasySwoole\Curl\Response;

class Curl
{
    use Singleton;

    /**
     * post
     * @param string $uri
     * @param array|null $params
     * @return Response
     */
    public function post(string $uri, array $params = null): Response
    {
        $request = new Request($uri);
        if (isset($params['headers']) && is_array($params['headers'])) {
            $headers = [];
            foreach ($params['headers'] as $key => $value) {
                $headers[] = "{$key}:$value";
            }
            $request->setUserOpt([CURLOPT_HTTPHEADER => $headers]);
        }
        if (isset($params['form']) && is_array($params['form'])) {
            foreach ($params['form'] as $key => $value) {
                $request->addPost(new Field($key, $value));
            }
        } elseif (isset($params['body'])) {
            if (!isset($params['header']['Content-Type'])) {
                $params['header']['Content-Type'] = 'application/json; charset=utf-8';
            }
            $request->setUserOpt([CURLOPT_POSTFIELDS => $params['body']]);
        }
        if (isset($params['options']) && is_array($params['options'])) {
            $request->setUserOpt($params['options']);
        }
        return $request->exec();
    }

    /**
     * get
     * @param string $uri
     * @param array|null $params
     * @return Response
     */
    public function get(string $uri, array $params = null): Response
    {
        $request = new Request($uri);
        if (isset($params['headers']) && is_array($params['headers'])) {
            $headers = [];
            foreach ($params['headers'] as $key => $value) {
                $headers[] = "{$key}:$value";
            }
            $request->setUserOpt([CURLOPT_HTTPHEADER => $headers]);
        }
        if (isset($params['query']) && is_array($params['query'])) {
            foreach ($params['query'] as $key => $value) {
                $request->addGet(new Field($key, $value));
            }
        }
        if (isset($params['options']) && is_array($params['options'])) {
            $request->setUserOpt($params['options']);
        }
        return $request->exec();
    }
}