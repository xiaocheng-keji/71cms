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
namespace app\api\validate;

use think\Validate;

class MeetingStudyRec extends Validate
{
	protected $rule = [
		'meeting_id' => 'require',
        'study_rec' => 'require|min:2',
	];

	protected $message = [
		'meeting_id.require' => '会议id不能为空',
		'study_rec.require' => '请输入心得',
        'study_rec.min' => '心得至少2个字符',
	];
}