<?php
namespace app\wap\model;

use think\Model;

class MeetingFile extends Model
{
	public function meeting()
    {
        return $this->belongsTo('Meeting', 'meeting_id', 'id');
    }
}