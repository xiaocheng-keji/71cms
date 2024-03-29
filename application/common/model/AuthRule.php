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

use think\Db;
use think\Model;
use app\common\model\AuthGroup as AuthGroupModel;
use util\Tree;


class AuthRule extends Model
{
    public function getAllNode($id)
    {
        $auth_group_model = new AuthGroupModel();
        $auth_group_data = $auth_group_model->find($id)->toArray();
        $auth_rules = explode(',', $auth_group_data['rules']);

        $res = $this->field('id,pid,title')->select();

        $all_node = array();
        $sub_node = array();


        foreach ($res as $v) {
            if ($v['pid']==0){
//            if (in_array($v['pid'], $idarr)) {
                //为顶级菜单的子菜单
                $all_node[] = $v;
            } else {
                //次顶级菜单
                $sub_node[$v['pid']][] = $v;
            }
        }

        foreach ($all_node as $k => $v) {
            if (isset($sub_node[$v['id']])) {
                foreach ($sub_node[$v['id']] as $k1 => $v1) {
                    if (in_array($v1['id'], $auth_rules)) {
                        $sub_node[$v['id']][$k1]['checked'] = 1;
                    } else {
                        $sub_node[$v['id']][$k1]['checked'] = 0;
                    }
                }
                $all_node[$k]['subnode'] = $sub_node[$v['id']];
            } else {
                $all_node[$k]['subnode'] = 0;
            }
            if (in_array($v['id'], $auth_rules)) {
                $all_node[$k]['checked'] = 1;
            } else {
                $all_node[$k]['checked'] = 0;
            }
        }

        $res = $all_node;
        return $res;
    }

    public function getAllList($id)
    {
        $auth_group_model = new AuthGroupModel();
        $auth_group_data = $auth_group_model->find($id)->toArray();
        $auth_rules = explode(',', $auth_group_data['rules']);

        $auth_rule_list = $this->field('id,pid,title')->select();
        foreach ($auth_rule_list as $k => $v) {
            $v['checkArr'] = [
                "type"=> "0",
                "checked" => in_array($v['id'], $auth_rules)?1:0
            ];
            $auth_rule_list[$k] = $v;
        }

        return $auth_rule_list;
    }

    public function optionsArr()
    {
        $objList = $this->order(['sort' => 'ASC', 'id' => 'ASC'])->column('*', 'id');
        $Trees = new Tree();
        $Trees->tree($objList, 'id', 'pid', 'title');
        $res = $Trees->getArray();
        $res[0] = [
            'id' => 0,
            'title' => '顶级',
        ];
        return $res;
    }
}