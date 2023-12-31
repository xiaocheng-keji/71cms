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

use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\AuthRule as AuthRuleModel;
use app\common\controller\ModelHelper;
use app\common\model\AuthGroupAccess;

/**
 * 权限组
 * Class AuthGroup
 * @package app\admin\controller
 */
class AuthGroup extends AdminBase
{
    protected $auth_group_model;
    protected $auth_rule_model;

    protected function initialize()
    {
        parent::initialize();
        $this->auth_group_model = new AuthGroupModel();
        $this->auth_rule_model = new AuthRuleModel();
    }

    /**
     * 权限组
     * @return mixed
     */
    public function index()
    {

        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test');

        if (\think\facade\Request::isAjax()) {
            $count = $this->auth_group_model->count();
            $list = $this->auth_group_model
                ->page(input('page', 1), input('limit', 10))
                ->order('id', 'asc')
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('添加权限组', Url('add'))
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('名称', 'title', 'text')
            ->addRowBtnEx('启用|禁用', url('updatestatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('状态', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo1',
            ])
            ->addRowBtn('权限配置', url('auth'), 'barDemo', null, 'btn-warm')
            ->addRowBtn('编辑', url('add'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
            ]);
        return $modelHelper->showList();
    }

    /**
     * 添加权限组
     *
     * @return mixed
     */
    public function add($id = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = $this->validate($data, 'AuthGroup');
            if ($validate_result !== true) {
                return json(['code' => 0, 'msg' => $validate_result]);
            }
            if ($id) {
                $r = $this->auth_group_model->allowField(true)->save($data, ['id' => $id]);
                $msg = '编辑';
            } else {
                $r = $this->auth_group_model->allowField(true)->save($data);
                $msg = '添加';
            }
            if ($r) {
                return json(['code' => 1, 'msg' => '权限组' . $msg . '成功']);
            } else {
                return json(['code' => 0, 'msg' => '权限组' . $msg . '失败']);
            }
        }

        $item = [];
        if ($id) {
            $item = $this->auth_group_model->find($id);
            if (empty($item)) {
                return json(['code' => 0, 'msg' => 'error']);
            }
        }
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle($id ? '编辑权限组' : '添加权限组')
            ->addField('名称', 'title', 'text', ['require' => '*', 'placeholder' => '请输入权限组名称'])
            ->addField('状态', 'status', 'radio', ['require' => '*', 'options' => ['禁用', '启用']])
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 更新权限组
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if ($id == 1 && $data['status'] != 1) {
                //  $this->error('超级管理组不可禁用');
                return json(array('code' => 0, 'msg' => '超级管理组不可禁用'));
            }
            if ($this->auth_group_model->save($data, $id) !== false) {
                //   $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                //  $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 删除权限组
     * @param $id
     */
    public function delete($id)
    {
        if ($id == 1) {
            //  $this->error('超级管理组不可删除');
            return json(array('code' => 0, 'msg' => '超级管理组不可删除'));
        }
        if ($this->auth_group_model->destroy($id)) {
            //   $this->success('删除成功');
            return json(array('code' => 1, 'msg' => '删除成功'));
        } else {
            //  $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    /**
     * 授权
     * @param $id
     * @return mixed
     */
    public function auth($id)
    {
        //确保$id是自己租户下的
        $auth_group = $this->auth_group_model->find($id);
        if (!$auth_group) {
            $this->error('参数错误');
        }
        return $this->fetch('auth', ['id' => $id]);
    }

    /**
     * AJAX获取规则数据
     * @param $id
     * @return mixed
     */
    public function getJson($id)
    {
        //确保$id是自己租户下的
        $auth_group = $this->auth_group_model->find($id);
        if (!$auth_group) {
            $this->error('参数错误');
        }
        $auth_rule_list = $this->auth_rule_model->getAllList($id);
        $this->success('json', '', $auth_rule_list);
    }

    /**
     * 更新权限组规则
     * @param $id
     * @param $auth_rule_ids
     */
    public function updateAuthGroupRule($id, $auth_rule_ids = [])
    {
        if ($this->request->isPost()) {
            //确保$id是自己租户下的
            $auth_group = $this->auth_group_model->find($id);
            if (!$auth_group) {
                $this->error('参数错误');
            }

            //当前操作者拥有的权限
            if (session('admin_id') == 1) {
                //平台超级账号拥有所有
                $filter_auth_rules = AuthRuleModel::column('id');
            } else {
                $auth_group_model = new AuthGroupModel();
                $auth_group_id = AuthGroupAccess::where('uid', session('admin_id'))->value('group_id');
                $auth_group_data = $auth_group_model->find($auth_group_id)->toArray();
                $filter_auth_rules = explode(',', $auth_group_data['rules']);
            }

            $group_data['id'] = $id;
            foreach ($auth_rule_ids as $k => $v) {
                //TODO 超过当前操作者权限的处理 目前是删除 后期可以判断租户权限做保留
                if (!in_array($v, $filter_auth_rules)) {
                    unset($auth_rule_ids[$k]);
                }
            }
            array_unique($auth_rule_ids);
            $group_data['rules'] = implode(',', $auth_rule_ids);

            if ($this->auth_group_model->save($group_data, $id) !== false) {
                $this->success('授权成功');
            } else {
                $this->error('授权失败');
            }
        }
    }

    public function updatestatus($id, $status, $name)
    {
        if ($this->request->isGet()) {
            $auth_group = $this->auth_group_model->where('id', $id)->find();
            if ($auth_group['is_super'] == 1) {
                $this->error('超级权限组不允许修改');
            }
            if ($this->auth_group_model->where('id', $id)->update([$name => $status]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }
}