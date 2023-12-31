<?php

namespace app\wap\model;

use think\Model;

class MeetingUser extends Model
{
    //一对一关联方法
    public function user()
    {
        //return $this->belongsTo('User','modelid'); //第二个参数是外键字段名
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function meeting()
    {
        return $this->belongsTo('Meeting', 'meeting_id', 'id');
    }

    public function getSignTimeAttr($value)
    {
        return date('Y-m-d H:i', $value);
    }

    public function getStudyRecTimeAttr($value)
    {
        return date('Y-m-d H:i', $value);
    }
}
