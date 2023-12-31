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
namespace app\admin\controller;

use app\common\controller\ModelHelper;
use app\common\model\AuthGroupAccessDep;

/**
 * 组织管理员管理
 * Class DepAdmin
 * @package app\admin\controller
 */
class DepAdmin extends AdminBase
{
    /**
     * 列表
     * @return mixed
     */
    public function index($id)
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($id, $auth_dep_arr)) {
            $this->error('您没有权限查看该组织管理员列表');
        }
        $modelHelper = new ModelHelper();
        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $where[] = ['a.dep_id', '=', $id];
            $count = AuthGroupAccessDep::alias('a')
                ->where($where)
                ->count();
            $list = AuthGroupAccessDep::alias('a')
                ->leftJoin('user b', 'a.uid=b.id')
                ->leftJoin('auth_group c', 'a.group_id=c.id')
                ->where($where)
                ->order('a.id desc')
                ->field('a.id,a.create_time,b.nickname,b.mobile,c.title as group_name')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }
        $dep = \app\common\model\Department::where('id', $id)->find();
        $modelHelper
            ->addTips($dep['name'] . ' 管理员，并管理下级组织')
            ->addTopBtn('返回', 'javascript:history.back();')
            ->addTopBtn('添加组织管理员', url('add', ['dep_id' => $id]))
            ->addField('用户', 'nickname', 'text')
            ->addField('手机号', 'mobile', 'select')
            ->addField('权限组', 'group_name', 'select')
            ->addField('添加时间', 'create_time', 'text')
            ->addRowBtn('删除', url('delete', ['dep_id' => $id]))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);

        return $modelHelper->showList();
    }

    /**
     * 添加
     * @param string $dep_id
     * @return mixed
     */
    public function add($dep_id = 0)
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($dep_id, $auth_dep_arr)) {
            $this->error('您没有权限给该组织添加管理员');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $exist = AuthGroupAccessDep::where('uid', $post['user_id'])
                ->where('dep_id', $dep_id)
                ->find();
            if ($exist) {
                $this->error('已存在，请勿重复添加！');
            }
            $auth_group_access['uid'] = $post['user_id'];
            $auth_group_access['group_id'] = $post['group_id'];
            $auth_group_access['dep_id'] = $dep_id;
            AuthGroupAccessDep::where(['uid' => $post['user_id']])->delete();
            $authGroupAccessDep = new AuthGroupAccessDep();
            $r = $authGroupAccessDep->save($auth_group_access);
            if ($r) {
                $this->success(' 保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $group_options = \app\admin\model\AuthGroup::column('title', 'id');
        $this->assign('group_options', $group_options);
        return view();
    }

    /**
     * @param $id
     * 删除
     */
    public function delete($id)
    {
        $auth_group_access_dep = AuthGroupAccessDep::get($id);
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($auth_group_access_dep['dep_id'], $auth_dep_arr)) {
            $this->error('您没有权限删除该组织的管理员');
        }
        if (AuthGroupAccessDep::destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}