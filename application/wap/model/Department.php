<?php

namespace app\wap\model;

use think\Model;

class Department extends Model
{

    public function userDep(){
        return $this->belongsToMany('User','UserDep','user_id','dep_id');
    }
}
