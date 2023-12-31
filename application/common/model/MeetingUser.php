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

class MeetingUser extends Model
{
    public function getSignTimeAttr($value)
    {
        if ($value > 0) {
            return date('Y-m-d H:i', $value);
        } else {
            return '';
        }
    }

    public function getStudyRecTimeAttr($value)
    {
        if ($value > 0) {
            return date('Y-m-d H:i', $value);
        } else {
            return '';
        }
    }

    public function getSignStatusAttr($value)
    {
        switch ($value) {
            case 0:
                return '未签到';
                break;
            case 1:
                return '签到';
                break;
            case 2:
                return '请假';
                break;
            default:
                return $value;

        }
    }
    public function getStudyRecStatusAttr($value)
    {
        switch ($value) {
            case 0:
                return '待提交';
                break;
            case 1:
                return '正常提交';
                break;
            case 2:
                return '超时未提交';
                break;
            case 3:
                return '超时提交';
                break;
            default:
                return $value;

        }
    }
}