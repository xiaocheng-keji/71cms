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

class DevelopUser extends Validate
{
    protected $rule = [
        'dep_id'         => 'require|number',
        'nickname'         => 'require|min:2',
        'sex'              => 'require',
        'phone'           => 'require|number|mobile|unique:develop_user|length:11',
        'nation'           => 'require',
        'native_place'           => 'require',
        'education'            => 'require',
        'idcard'           => 'idCard',
        'apply_time'       => 'require',
        'birthday'         => 'require',
        'apply_petition'   => 'require',
        'status'           => 'require',
    ];

    protected $message = [
        'dep_id.require'         => '请选择组织',
        'dep_id.number'         => '组织信息错误',
        'nickname.require'         => '姓名不能为空',
        'nickname.min'             => '姓名至少2位',
        'nickname.unique'          => '姓名已存在',
        'phone.require'           => '手机号不能为空',
        'phone.mobile'            => '手机号格式错误',
        'phone.number'            => '手机号格式错误',
        'phone.length'            => '手机号长度错误',
        'phone.unique'            => '手机号已存在',
        'password.require'         => '密码不能为空',
        'password.confirm'         => '密码与确认密码不一致',
        'password.min'             => '密码不小于6位',
        'confirm_password.require' => '确认密码不能为空',
        'confirm_password.confirm' => '密码与确认密码不一致',
        'department.require'       => '部门不能为空',
        'sex.require'              => '请选择性别',
        'nation.require'           => '请选择民族',
        'native_place.require'           => '请选择籍贯',
        'education.require'            => '请选择学历',
        'apply_time.require'       => '申请入党时间不能为空',
        'work_status.require'      => '党员状态不能为空',
        'party_status.require'     => '用户状态不能为空',
        'status.require'           => '请选择发展阶段',
        'usermail.unique'          => '邮箱已存在',
        'idcard.idCard'            => '请填写正确的身份证号码',
        'birthday.require'         => '出生日期不能为空',
        'apply_petition.require'         => '请上传入党申请书',
    ];

    // edit 验证场景定义
    public function sceneEdit()
    {
        return $this->remove('phone', 'unique');
    }

}