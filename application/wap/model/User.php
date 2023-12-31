<?php

namespace app\wap\model;

class User extends \app\common\model\User
{
    //一对一关联方法
    public function meeting()
    {
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function getBirthdayAttr($value)
    {
        if ($value) return date('Y-m-d', $value);
        return '';
    }

    public function department(){
        return $this->belongsToMany('Department','UserDep','dep_id','user_id');
    }

    public function userDep(){
        return $this->hasMany('UserDep','user_id','id');
    }
}
