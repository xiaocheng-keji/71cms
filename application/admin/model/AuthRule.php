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
namespace app\admin\model;

use app\common\model\AuthGroupAccess;
use think\Db;
use think\Model;
use app\admin\model\AuthGroup as AuthGroupModel;


class AuthRule extends Model
{
    public function getAllList($id)
    {
        //这里过滤掉当前操作者不拥有的权限，菜单是不会显示出来的
        $auth_group_model = new AuthGroupModel();
        $auth_group_id = AuthGroupAccess::where('uid', session('admin_id'))->value('group_id');
        $auth_group_data = $auth_group_model->find($auth_group_id)->toArray();
        $filter_auth_rules = explode(',', $auth_group_data['rules']);
        $admin_user = session('admin_user');
        if ($admin_user['is_super'] == 1) {
            //超级管理员吧自己租户的也显示出来
            $auth_rule_list = $this->alias('a')
                ->leftJoin('auth_rule_conf b', 'a.id=b.a_id')
                /*->whereOr(function ($query) use ($filter_auth_rules) {
                    $query->where('a.id', 'in', $filter_auth_rules);
                })*/
                ->orderRaw('IF(b.sort is null,a.sort,b.sort) ASC')
                ->order(['b.sort' => 'ASC', 'a.sort' => 'ASC', 'a.id' => 'ASC'])
                ->field('a.id,a.title,a.name,IF(b.sort is null,a.sort,b.sort) as sort,IF(b.status is null,a.status,b.status) as status,a.pid')
                ->select();
        } else {
//            $auth_rule_list = $this->field('id,pid,title')
//                ->where('id', 'in', $filter_auth_rules)
//                ->order(['sort' => 'ASC', 'id' => 'ASC'])
//                ->select();
            $auth_rule_list = $this->alias('a')
                ->leftJoin('auth_rule_conf b', 'a.id=b.a_id')
                ->where('a.id', 'in', $filter_auth_rules)
                ->orderRaw('IF(b.sort is null,a.sort,b.sort) ASC')
                ->order(['b.sort' => 'ASC', 'a.sort' => 'ASC', 'a.id' => 'ASC'])
                ->field('a.id,a.title,a.name,IF(b.sort is null,a.sort,b.sort) as sort,IF(b.status is null,a.status,b.status) as status,a.pid')
                ->select();
        }

        //当前id已拥有的
        $auth_group_model = new AuthGroupModel();
        $auth_group_data = $auth_group_model->find($id)->toArray();
        $auth_rules = explode(',', $auth_group_data['rules']);

        foreach ($auth_rule_list as $k => $v) {
            $v['checkArr'] = [
                "type" => "0",
                "checked" => in_array($v['id'], $auth_rules) ? 1 : 0
            ];
            $auth_rule_list[$k] = $v;
        }
        return $auth_rule_list;
    }
}