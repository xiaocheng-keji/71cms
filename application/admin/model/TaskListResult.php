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
namespace app\admin\model;

use think\Model;

class TaskListResult extends Model
{
    public static $is_send = [
        -1=>'发送失败',
        0 => '未发送',
        1 => '已发送',
    ];

    public  static  $is_res = [
        -1=> '已过期',
        0 => '未完成',
        1 => '待完成',
        2=>  '已完成'
    ];

    public  static  $status = [
        0 => '正常',
        1 => '过期',
    ];

    public function user(){
        return $this->belongsTo('User','uid','id');
    }
}
