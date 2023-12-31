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
namespace app\admin\validate;

use think\Validate;

class Forumcate extends Validate
{
    protected $rule = [
        'tid'  => 'require',
        'name' => 'require',
        'sort' => 'require|number'
    ];

    protected $message = [
        'tid.require'  => '请选择上级版块',
        'name.require' => '请输入版块名称',
        'sort.require' => '请输入排序',
        'sort.number'  => '排序只能填写数字'
    ];
}