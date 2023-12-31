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

class Config extends Model
{
    public function saveConfig($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            if (empty($key)) {
                continue; //结束本次循环
            }
            if (Config::where('varname', $key)->find() == null) {
                Config::insert(['varname' => $key, 'value' => trim($value)]);
            }
            Config::where('varname', $key)->update(['value' => trim($value)]);//更新
        }
        return true;
    }
}
