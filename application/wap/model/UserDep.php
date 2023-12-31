<?php

namespace app\wap\model;

use think\Model;
use think\model\Pivot;

class UserDep extends Pivot
{
    // 定义全局的查询范围
//    protected function base($query)
//    {
//        $query->where(['tenant_id'=> TENANT_ID]);
//    }

    public function department(){
        return $this->belongsTo('Department','dep_id','id');
    }
}
