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

class Article extends Validate
{
    protected $rule = [
        'title|标题' => 'require|max:150',
        'author|作者' => 'max:20',
        'cat_id'   => 'require|gt:0',
        'content'   => 'require',
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'cat_id.require'   => '栏目不能为空',
        'cat_id.gt'   => '请选择栏目',
        'content.require'   => '内容不能为空',
    ];
}