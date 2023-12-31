<?php
/**
 * 71CMS [ 创先云智慧党建系统 ]
 * =========================================================
 * Copyright (c) 2018-2023 南宁小橙科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.71cms.net
 * 这不是一个自由软件！未经许可不能去掉71CMS相关版权。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */
namespace app\api\model;

class User extends Base
{
    public function getHeadPicAttr($value){
        if(stripos($value,'http')!==false){
            return $value;
        }else{
            return SITE_URL.$value;
        }
    }

    public function department(){
        return $this->belongsToMany('Department','UserDep','dep_id','user_id');
    }

    public function userDep()
    {
        return $this->hasMany('UserDep', 'user_id', 'id');
    }

    public function appraiseUser(){
        return $this->hasMany('AppraiseUser','uid','id');
    }
}