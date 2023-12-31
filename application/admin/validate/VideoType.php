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

class VideoType extends Validate
{
    protected $rule = [
        'typename' => 'require|unique:video_type',
        'sort' => 'egt:0',
        'status' => '[0,1]'
    ];

    protected $message = [
        'typename.require' => '请填写名称',
        'typename.unique' => '名称已存在',
        'sort.egt' => '排序号必须大于等于0',
        'status.[0,1]' => '选择状态错误'
    ];
}