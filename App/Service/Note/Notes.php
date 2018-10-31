<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service\Note;


use App\Cache\User\UserInfo;
use App\Service\Base;

class Notes extends Base
{
    public function getNoteById(int $id): array
    {
        $ret = [];
        $note = (new \App\Cache\Note\Notes())->getOneById($id);
        if (!empty($note)) {
            $ret = $note;
            $userId = isset($note['user_id']) ? intval($note['user_id']) : 0;
            if ($userId > 0) {
                $user = (new UserInfo())->getUserById($userId);
                $ret['user_info'] = empty($user) ? null : $user;
            }
        }
        return $ret;
    }
}