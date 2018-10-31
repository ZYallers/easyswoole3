<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/30
 * Time: 下午8:25
 */

namespace App\Cache\Note;

use App\Cache\Base;

class Notes extends Base
{
    public function getOneById(int $id)
    {
        $key = 'eshxs:note:get0nebyid3:' . $id;
        $Redis = $this->getCache();
        if ($Redis->exists($key)) {
            return json_decode($Redis->get($key), true);
        } else {
            $User = (new \App\Model\Note\Notes())->getOneByWhere([['id', $id]]);
            $Redis->setex($key, 86400, json_encode($User, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $User;
        }
    }
}