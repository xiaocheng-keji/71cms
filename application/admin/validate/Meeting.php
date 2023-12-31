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

class Meeting extends Validate
{
	protected $rule = [
		'cat_id'	 	=> 'require',
		'start_time' 	=> 'require',
		'end_time'	 	=> 'require',
		'place' 	 	=> 'require',
		'theme' 	 	=> 'require',
		'content' 	 	=> 'require',
		'sign_status'	=> 'require',
		'compere' 		=> 'require|gt:0',
		'recorder' 		=> 'require|gt:0',
	];

	protected $message = [
		'cat_id.require' 		=> '栏目不能为空',
		'start_time.require'	=> '开始时间不能为空',
		'end_time.require' 		=> '结束时间不能为空',
		'place.require' 		=> '会议地点不能为空',
		'theme.require' 		=> '会议主题不能为空',
		'content.require' 		=> '内容不能为空',
		'sign_status.require' 	=> '会议签到不能为空',
		'compere.require' 		=> '会议主持人不能为空',
		'compere.gt' 			=> '会议主持人不能为空',
		'recorder.require' 		=> '会议记录人不能为空',
		'recorder.gt' 			=> '会议记录人不能为空',
	];
}