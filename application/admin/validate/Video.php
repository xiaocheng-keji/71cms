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

class Video extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
	    'vname' => 'require',
        'tid' => 'require|gt:0',
        'cid' => 'require|gt:0',
        'upload_name' =>'require',
        'vimg' => 'require',
//        'src' => 'require',
        'integral' => 'egt:0',
        'sort' => 'egt:0',
        'vcontent' => 'require'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'vname.require' => '请填写名称',
        'upload_name.require'=>'请填写作者',
        'sort.egt' => '排序号必须大于等于0',
        'tid.require' => '选择文件分类出错',
        'tid.gt' => '选择文件分类出错',
        'cid.require' => '选择课程分类出错',
        'cid.gt' => '选择课程分类出错',
        'vimg.require' => '必须上传课程展示图',
//        'src.require' => '没有上传文件',
        'vcontent.require' => '请输入内容',
        'integral.egt' => '积分不能未负数'
    ];
}
