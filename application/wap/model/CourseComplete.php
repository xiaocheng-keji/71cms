<?php

namespace app\wap\model;

use think\Model;

class CourseComplete extends Model
{
    public function video(){
        return $this->belongsTo('Video','vid','id');
    }
}
