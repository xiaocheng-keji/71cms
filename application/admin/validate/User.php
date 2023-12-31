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

class User extends Validate
{
    protected $rule = [
        'nickname'         => 'require|min:2',
        'mobile'           => 'require|number|mobile|unique:user|length:11',
        'sex'           => 'require',
        'nation'           => 'require',
        'birthday'        =>'require',
        'status'           => 'require',
        'people_status'     => 'require',
        'department'       => 'require',
        'people_type'     => 'require',
//        'jiguan'           => 'require',
//        'xueli'            => 'require',
//        'apply_time'       => 'require',
//        'work_status'      => 'require',
//        'party_status'     => 'require',



        'password'         => 'require|min:6|confirm:confirm_password',
        'confirm_password' => 'require|confirm:password',
    ];

    protected $message = [
        'nickname.require'         => '姓名不能为空',
        'nickname.min'             => '姓名至少两个字符',
        'nickname.unique'          => '姓名已存在',
        'mobile.require'           => '手机号不能为空',
        'mobile.mobile'            => '手机号格式错误',
        'mobile.number'            => '手机号格式错误',
        'mobile.length'            => '手机号长度错误',
        'mobile.unique'            => '手机号已存在',
        'password.require'         => '密码不能为空',
        'department.require'       => '请添加所属组织',
        'sex.require'              => '请选择性别',
        'nation.require'           => '请选择民族',
        'native_place.require'           => '请选择籍贯',
        'education.require'            => '请选择学历',
        'apply_time.require'       => '申请入党时间不能为空',
        'work_status.require'      => '党员状态不能为空',
        'party_status.require'     => '用户状态不能为空',
        'status.require'           => '请选择账号状态',
        'people_status.require'    => '请选择状态',
        'people_type.require'           => '请选择人员类别',
        'usermail.unique'          => '邮箱已存在',
        'password.confirm'         => '密码与确认密码不一致',
        'password.min'             => '密码不小于6位',
        'confirm_password.require' => '确认密码不能为空',
        'confirm_password.confirm' => '密码与确认密码不一致',
        'birthday'                  => '请填写生日',
    ];

    // edit 验证场景定义
    public function sceneEdit()
    {
        return $this->remove('mobile', 'unique')
            ->remove('password', 'require')
            ->remove('confirm_password', 'require');
    }
    
    /**
    * 修改密码的验证
    * @return User
    */
    public function sceneChangePassword()
    {
        return $this->only(['password', 'confirm_password']);
    }

}