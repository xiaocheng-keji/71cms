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

class TaskListValidate extends Validate
{
	protected $rule = [
	    'info_id' =>'require',
        'name'	 	=> 'require',
		'uid' 	=> 'require',
		'end_time'	 	=> 'require',
		'frequency' 	 	=> 'require',
		'feedback_type' 	 	=> 'require',
		'at_date'	=> 'require',
		'at_time' 		=> 'require',
        'status' 	 	=> 'require',
	];

	protected $message = [
		'name.require' 		=> '任务清单名不能为空',
		'uid.require'	=>       '请选择责任人',
		'end_time.require' 		=> '结束时间不能为空',
		'frequency.require' 		=> '任务频次不能为空',
		'feedback_type.require' 		=> '请选择反馈类型提交',
		'at_date.require' 		=> '开始提醒日期不能为空',
		'at_time.require' 		=> '开始提醒时间不能为空',
        'status.require' 	 	=> '任务状态不能为空',
        'info_id'    =>         '任务信息表ID为空，请退出到任务清单列表再进入'
	];
}