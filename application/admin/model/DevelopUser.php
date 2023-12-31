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


class DevelopUser extends Base
{
    protected $autoWriteTimestamp = true;


    public static $status = [
        1 => '申请入党',
        2 => '入党积极分子的确定和培养教育',
        3 => '发展对象的确定和考察',
        4 => '预备党员的接收',
        5 => '预备党员的教育考察和转正',
        6 => '发展完成',
    ];

    protected function setApplyTimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected function getApplyTimeAttr($value)
    {
        return !is_null($value) ? date('Y-m-d', $value) : '';
    }

    //成为预备时间
    protected function setYubeiTimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected function getYubeiTimeAttr($value)
    {
        return !is_null($value) ? date('Y-m-d', $value) : '';
    }

    //转正时间
    protected function setZhuanzhengTimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected function getZhuanzhengTimeAttr($value)
    {
        return !is_null($value) ? date('Y-m-d', $value) : '';
    }

    protected function setBirthdayAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected function getBirthdayAttr($value)
    {
        return !is_null($value) ? date('Y-m-d', $value) : '';
    }

    protected function getStatusAttr($value)
    {
        return self::$status[$value];
    }
}