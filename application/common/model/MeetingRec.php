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

namespace app\common\model;


use think\Model;

class MeetingRec extends Model
{

    protected $autoWriteTimestamp = true;

    public function category()
    {
        return $this->hasOne('Category', 'id', 'cat_id');
    }

    public function department()
    {
        return $this->hasOne('Department', 'id', 'department_id');
    }

    public function setDatetimeAttr($value)
    {
        return strtotime($value) ?? $value;
    }

    public function getDatetimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}