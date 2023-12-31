<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/15
 * Time: 14:56
 */

namespace app\wap\model;


class Message extends \app\common\model\Message
{

    public function fromUser()
    {
        return $this->belongsTo('User', 'touid', 'id');
    }

    public function getTimeAttr($value)
    {
        return date('Y-m-d H:i', $value);
    }
}