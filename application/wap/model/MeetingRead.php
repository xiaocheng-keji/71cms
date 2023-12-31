<?php


namespace app\wap\model;


use think\facade\Config;
use think\Model;

class MeetingRead extends Model
{
    // 定义全局的查询范围
    protected function base($query)
    {
    }

    protected function getCreateTimeAttr($value)
    {
        return $value ? date(Config::get('database.datetime_format'), $value) : '';
    }
}