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
namespace app\common\model;

use think\Model;
use util\Tree;

class NavWap extends Model
{
    protected $insert = ['time'];

    // 定义全局的查询范围
    protected function base($query)
    {
    }

    public function optionsArr()
    {
        $objList = $this->order(['sort' => 'ASC', 'id' => 'ASC'])->column('*', 'id');
        $Trees = new Tree();
        $Trees->tree($objList, 'id', 'pid', 'name');
        $res = $Trees->getArray();
        $res[0] = [
            'id' => 0,
            'name' => '顶级',
        ];
        return $res;
    }
}