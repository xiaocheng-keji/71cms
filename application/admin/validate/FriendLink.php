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

class FriendLink extends Validate
{
    protected $rule = [
		'name' => 'require',
//		'link' => 'require',
//		'icon' => 'require',
		'sort' => 'number',
	];

    protected $message = [
		'name.require' => '名称不能为空',
		'link.require' => '链接不能为空',
		'icon.require' => '封面图不能为空',
		'sort.number'  => '排序只能填写数字',
	];
}