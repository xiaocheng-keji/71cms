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

class TaskListType extends Model
{

    public static $time_format = 'H:i:s';

    public static $task_type = [
        0 => '普松任务',
        1 => '党员发展',
    ];


    public  function  getTaskTypeAttr($value){
        return  TaskListType::$task_type[$value];
    }

    public  function  getAtTimeAttr($value){
        return  $this->getTimeByTimestamp($value);
    }

    private  function  getTimeByTimestamp($timestamp){
        if(!empty($timestamp)){
            return date(TaskListType::$time_format,$timestamp);
        }else{
            return '';
        }
    }

}
