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

class DicData extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
	    'value'=>'require',
        'text'=>'require|checkRepeat',
        'sort'=>'require'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'value.require'=>'字典值不能为空',
        'text.require'=>'字典内容不能为空',
        'text.checkRepeat'=>'字典内容重复了',
        'sort.require'=>'排序不能为空'
    ];

    protected function checkRepeat($value,$rule,$data=[],$name,$desc){
        $exist = \app\common\model\DicData::where(['type_id'=>$data['type_id'],$name=>$value])->where('id','<>',$data['id'])->find();
        return $exist?$this->message[$name.'.checkRepeat']:true;
    }
}
