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

namespace app\api\controller;


use app\api\model\UserDep;
use app\common\model\AuthGroupAccessDep as AuthGroupAccessDepModel;
use app\common\model\Department as DepModel;
use app\common\model\DicData;
use app\common\model\UserLevel;
use think\Db;
use app\common\model\Department as DepartmentModel;
use app\common\model\UserDep as UserDepModel;
use app\common\model\User as UserModel;

class Department extends Base
{
    /**
     * 部门树结构
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function depTree()
    {
        $dep_id = input('dep_id', 0);
        if ($dep_id == 'top') $dep_id = 0;
        $department = new DepartmentModel();
        $res = $department->where('show', '=', 1)
            ->field('id, name,short_name, parent_id,"" as office ')
            ->fieldRaw('IF(short_name="",name,short_name) as title')
            ->order('sort')
            ->select()
            ->toArray();
        $dep_tree = $department->getDepTree($res, $dep_id, false);
        jsonReturn(1, '部门树', $dep_tree);
    }


    public function depTree1()
    {
        $dep_id = input('dep_id', 0);
        if ($dep_id == 'top') $dep_id = 0;
        $department = new DepartmentModel();
        $res = $department->where('show', '=', 1)
            ->field('id, name,short_name, parent_id,"" as office ')
            ->fieldRaw('IF(short_name="",name,short_name) as title')
            ->order('sort')
            ->select()
            ->toArray();
        $dep_tree = $department->getDepTree($res, $dep_id, false);
        array_unshift($dep_tree, array('id'=>0, 'name'=>'请选择支部'));
        jsonReturn(1, '部门树', $dep_tree);
    }

    public function department()
    {
        $department_list  = Db::name('department')->where('parent_id', 'eq', 0)->where('show', 'eq', 1)->where('status', 'eq', 0)->order('sort')->select();
        $userList = array();
        if(!empty($department_list)){
            foreach ($department_list as $v){
                $sub_list = array();
                $sub_list[] = $v['id'];
                $sub_list1 = Db::name('department')->where('parent_id', $v['id'])->column('id');
                if($sub_list1){
                    $sub_list[] = $sub_list1;
                }
                $userList[] = Db::name('user')
                    ->alias('a')
                    ->join('userDep b', 'a.id=b.user_id')
                    ->leftJoin('userLevel c', 'b.level_id=c.id')
                    ->where('b.dep_id', 'in', $sub_list1)
                    ->orderRaw('CONVERT( nickname USING gbk ) asc')
                    ->field('a.*,c.name level_name')
                    ->select();
            }
        }
        $this->assign('cur','department');
        $this->assign('department_list', $department_list);
        $this->assign('userList', $userList);
        jsonReturn(1, '部门树', $department_list,$userList);
    }

    /**
     * 部门树结构
     *
     * @param $dep_id
     * @param $range
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepList($dep_id = 'top', $range = 'all')
    {
        if ($dep_id == 'top') {
            //获取能看的最顶层的id
            $dep_ids = [45];
        } else {
            $dep_ids = [$dep_id];
        }
        $department = new DepartmentModel();
        if ($range == 'auth') {
            //获取管理的部门id以及下级部门的id
            $dep_admin = AuthGroupAccessDepModel::where('uid', $this->user_id)->column('dep_id');
            //获取这个部门的所有下级部门
            $auth = DepModel::getSubDep($dep_admin);
            $parent = DepartmentModel::getAllParentIds($dep_admin);
            $department = $department->where('id', 'in', $auth + $dep_admin + $parent);
            if ($dep_id == 'top') {
                //获取能看的最顶层的id
                if (count($dep_admin) == 1) {
                    //只有一个的话直接显示他下面的组织
                    $dep_ids = AuthGroupAccessDepModel::where('uid', $this->user_id)->column('dep_id');
                } else {
                    //有多个的话显示他们共同的上级
                    $parents = [];
                    foreach ($dep_admin as $k => $v) {
                        $parents[] = DepartmentModel::getAllParentIds($v);
                    }
                    $dep_ids = call_user_func_array('array_intersect', $parents);
                    $dep_id = DepartmentModel::where('id', 'in', $dep_ids)
                        ->order('layer', 'asc')
                        ->value('id');
                    $dep_ids = [$dep_id];
                }
            }
        }
        $res = $department->where('show', '=', 1)
            ->where('parent_id', 'in', $dep_ids)
            ->field('id, name,short_name, parent_id,"" as office ')
            ->fieldRaw('IF(short_name="",name,short_name) as title')
            ->order('sort')
            ->select()
            ->toArray();
        jsonReturn(1, '部门', $res);
    }

    /**
     * 根据部门id获取部门下的用户列表
     *
     * @param $dep_id
     * @param $range
     * @param $keyword
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserListByDepId($dep_id, $range = 'all', $keyword = '')
    {
        $UserModel = Db::name('user')
            ->alias('a')
            ->leftJoin('user_dep b', 'a.id=b.user_id');
        if ($range == 'auth') {
            //获取管理的部门id以及下级部门的id
            $dep_admin = AuthGroupAccessDepModel::where('uid', $this->user_id)->column('dep_id');
            if ($dep_id == 'top') {
                if (count($dep_admin) == 1) {
                    //只有一个的话直接显示他里面的人
                    $parent = DepartmentModel::getAllParentIds($dep_admin);
                    $parent = array_intersect($parent, $dep_admin);
                    $dep_id = DepartmentModel::where('id', 'in', $parent)
                        ->order('layer', 'asc')
                        ->value('id');
                } elseif (count($dep_admin) > 1) {
                    //有多个的话显示他们共同的上级
                    $parents = [];
                    foreach ($dep_admin as $k => $v) {
                        $parents[] = DepartmentModel::getAllParentIds($v);
                    }
                    $dep_ids = call_user_func_array('array_intersect', $parents);
                    $dep_id = DepartmentModel::where('id', 'in', $dep_ids)
                        ->where('id', 'in', $dep_admin)
                        ->order('layer', 'asc')
                        ->value('id');
                }
            } else {
                $dep_admin = DepartmentModel::getSubDep($dep_admin);
                $UserModel->where('b.dep_id', 'in', $dep_admin);
            }
        }

        if ($keyword) {
            $map = [];
            $map[] = ['nickname|mobile', 'like', "%{$keyword}%", 'or'];
            $UserModel->where($map);
        } else {
            if ($dep_id == 'top') {
                //获取能看的最顶层的id
                $dep_id = 45;
            }
           //获取这个部门的所有下级部门
            $dep_ids = DepModel::getSubDep($dep_id);
            $UserModel->where('b.dep_id', 'in', $dep_ids);
        }
        $user_list = $UserModel
            ->field('a.id,a.nickname as name,a.mobile,c.name as position,c.sort')
            ->join('xc_user_level c','b.level_id=c.id','left')
            // ->fieldRaw('CONCAT("' . SITE_URL . '",a.head_pic) avatar')
            ->group('a.id')
            ->orderRaw('if(isnull(c.sort),0,1) desc,c.sort')
            ->select();
        //获取部门名称
        $dep_name = DepartmentModel::where('id', '=', $dep_id)->value('name');
        jsonReturn(1, $dep_name, $user_list);
    }

    public function score($year = 0){
        $data = array();
        if($year == 0){
            $month = date('m');
            if($month >= 3){
                $data['year'] = date('Y');
            }else{
                $data['year'] = date('Y') - 1;
            }
        }else{
            $data['year'] = $year;
        }
        $score_list = Db::name('department_score')->where($data)->select();
        $score_all = array();
        if(!empty($score_list)){
            foreach ($score_list as $v){
                $score_all[$v['depart_id']] = $v;
            }
        }
        $data1['parent_id'] = 0;
        $data1['show'] = 1;

        $depart_list = Db::name('department')->where($data1)->order('sort')->select();
        $data_all = array();
        if(!empty($depart_list)){
            foreach ($depart_list as $v){
                $score_all[$v['id']]['name'] = $v['name'];
                $score_all[$v['id']]['show_name'] = $v['name'].$year.'年度党建工作KPI考核材料';
                $end_day = Db::name('report')->where('year', $year)->value('end_day');
                $score_all[$v['id']]['end_day'] = $end_day;
                //是否上传资料report_record
                $upload_time = Db::name('report')->alias('a')->join('__REPORT_RECORD__ b', 'a.report_id = b.report_id')->where('year', $year)->where('depart_id', $v['id'])->value('b.update_time');

                if($upload_time){
                    $score_all[$v['id']]['upload_time'] = date('Y-m-d', $upload_time);
                }else{
                    //$score_all[$v['id']]['upload_time'] = date('Y-m-d');
                    $score_all[$v['id']]['upload_time'] = '2022-12-05';
                }
                if(isset($score_all[$v['id']]) && $score_all[$v['id']]['xianshang_score'] > 0){
                    $data_all[1][] = $score_all[$v['id']];
                }else{
                    $data_all[0][] = $score_all[$v['id']];
                }
                if(input('?depart_id') && input('depart_id') > 0 && input('depart_id') == $v['id']){
                    $data_all[3] = $score_all[$v['id']];
                }
            }
        }
        foreach ($data_all[0] as $key=>$val){
            $tmp[$key] = $val['upload_time'];
        }
        array_multisort($tmp, SORT_DESC, $data_all[0]);

        jsonReturn(1, '部门得分', $data_all);
    }
}