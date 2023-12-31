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

class User extends Validate
{
	protected $rule = [
		'nickname' => 'require',
		'sex' => 'require',
		'nation' => 'require',
		'native_place' => 'require',
		'education' => 'require',
		'birthday' => 'require',
		'department_id' => 'require',
//		'party_status' => 'require',
        'mobile' => 'require|mobile|unique:user',
        'password' => 'confirm:confirm_password|length:6,16',
        'confirm_password' => 'confirm:password',
	];

	protected $message = [
		'nickname.require' => '请输入用户名',
        'mobile.unique' => '手机号已存在',
        'mobile.mobile' => '手机格式错误',
        'password.confirm' => '两次输入密码不一致',
        'password.length' => '密码在6位到16位之间',
        'confirm_password.confirm' => '两次输入密码不一致',
	];

    protected $scene = [
        'editPassword' => ['password', 'confirm_password'],
    ];

    protected function sceneLogin () {
        return $this->only(['nickname', 'password'])
            ->remove('password', 'confirm');
    }
	
}