<?php

namespace app\wap\model;

use think\Model;

class MeetingRec extends Model
{
    protected $autoWriteTimestamp = true;

    protected $type = [
        'imgs' => 'array',
    ];

    public function setDateTimeAttr($v)
    {
        return strtotime($v);
    }
    //一对一关联方法
    public function user()
    {
        return $this->belongsTo('User', 'id', 'user_id');
    }

    public function meeting()
    {
        return $this->belongsTo('Meeting', 'id', 'meeting_id');
    }
}
