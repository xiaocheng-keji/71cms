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
use app\common\model\AdminUser as AdminUserModel;
use app\common\model\AuthGroup as AuthGroupModel;
use app\common\model\AuthGroupAccess as AuthGroupAccessModel;
use think\Db;

/**
 * 管理员管理
 * Class AdminUser
 * @package app\admin\controller
 */
class AdminUser extends AdminBase
{
    protected $admin_user_model;
    protected $auth_group_model;
    protected $auth_group_access_model;

    protected function initialize()
    {
        parent::initialize();
        $this->admin_user_model = new AdminUserModel();
        $this->auth_group_model = new AuthGroupModel();
        $this->auth_group_access_model = new AuthGroupAccessModel();
    }

    /**
     * 管理员管理
     * @return mixed
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test');

        if (\think\facade\Request::isAjax()) {
            $count = $this->admin_user_model->count();
            $list = $this->admin_user_model->order('create_time desc')->page(input('page', 1), input('limit', 10))->select();
            foreach ($list as $k => &$v) {
                if (empty($v['create_time'])) {
                    $v['add_time'] = '';
                } else {
                    $v['add_time'] = date('Y-m-d H:i', $v['create_time']);
                }
                if (empty($v['last_login_time'])) {
                    $v['last_login_time'] = '';
                } else {
                    $v['last_login_time'] = date('Y-m-d H:i', $v['last_login_time']);
                }
                $auth_group = \app\common\model\AuthGroup::where('id', 'in', function ($query) use ($v) {
                    $query->name('auth_group_access')->where('uid', $v['id'])->field('group_id');
                })
                    ->group('id')
                    ->column('title');
                $v['auth_group'] = implode(',', $auth_group);
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('添加管理员', url('add'))
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('用户名', 'username', 'text')
            ->addField('权限组', 'auth_group', 'text')
            ->addRowBtnEx('启用|禁用', url('updatestatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('状态', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo1',
                'width' => 100
            ])
            ->addField('创建时间', 'add_time', 'text')
            ->addField('最后登录时间', 'last_login_time', 'text')
            ->addField('最后登录IP', 'last_login_ip', 'text')
            ->addRowBtn('编辑', url('edit'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 140
            ]);
        return $modelHelper->showList();
    }

    /**
     * 添加管理员
     * @return mixed
     */
    public function add($id = '')
    {
        $auth_group_list = $this->auth_group_model->where('status', 1)->select();
        return $this->fetch('add', ['auth_group_list' => $auth_group_list]);
    }

    /**
     * 保存管理员
     * @param $group_id
     */
    public function save($group_id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'AdminUser');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                $data['salt'] = generate_password(18);
                $data['password'] = md5($data['password'] . $data['salt']);
                if ($this->admin_user_model->allowField(true)->save($data)) {
                    $auth_group_access['uid'] = $this->admin_user_model->id;
                    $auth_group_access['group_id'] = $group_id;
                    $this->auth_group_access_model->save($auth_group_access);

                    return json(array('code' => 200, 'msg' => '管理员添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '管理员添加失败'));
                }
            }
        }
    }

    /**
     * 编辑管理员
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $admin_user = $this->admin_user_model->find($id);
        $auth_group_list = $this->auth_group_model->where('status', 1)->select();
        $auth_group_access = $this->auth_group_access_model->where('uid', $id)->find();
        $admin_user['group_id'] = $auth_group_access['group_id'];

        return $this->fetch('edit', ['admin_user' => $admin_user, 'auth_group_list' => $auth_group_list]);
    }

    /**
     * 更新管理员
     * @param $id
     * @param $group_id
     */
    public function update($id, $group_id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'AdminUser');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                $admin_user = $this->admin_user_model->find($id);
                if ($admin_user['is_super'] == 1 && $data['status'] == 0) {
                    return json(array('code' => 0, 'msg' => '默认管理员不能禁用'));
                } else {
                    $admin_user = $this->admin_user_model->find($id);

                    $admin_user->id = $id;
                    $admin_user->username = $data['username'];
                    $admin_user->status = $data['status'];
                    $admin_user->group_id = $data['group_id'];

                    if (!empty($data['password']) && !empty($data['confirm_password'])) {
                        $admin_user->password = md5($data['password'] . $admin_user['salt']);
                    }
                    if ($admin_user->save() !== false) {
                        $auth_group_access['uid'] = $id;
                        $auth_group_access['group_id'] = $group_id;
                        $this->auth_group_access_model->where('uid', $id)->update($auth_group_access);
                        return json(array('code' => 200, 'msg' => '更新成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '更新失败'));
                    }
                }
            }
        }
    }

    /**
     * 删除管理员
     *
     * @param $id
     */
    public function delete($id)
    {
        if ($id == 1) {
            return json(array('code' => 0, 'msg' => '默认管理员不可删除'));
        }
        if ($this->admin_user_model->where('id', $id)->delete()) {
            $this->auth_group_access_model->where('uid', $id)->delete();
            Db::name('admin_user_dep_auth')->where('admin_id', $id)->delete();
            return json(array('code' => 1, 'msg' => '删除成功'));

        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    /**
     * 编辑用户部门权限
     */
    public function editDepAuth()
    {
        if ($this->request->isPost()) {
            $user_id = input('post.user_id');
            $admin_user = \app\common\model\AdminUser::get($user_id);
            if (!$admin_user) {
                $this->error('参数错误');
            }
            $auth = input('post.auth');
            $add_data = $no_all_selected = $selected = $del_data = [];
            $depAuth = Db::name('admin_user_dep_auth');
            $user_auth = $depAuth->where('admin_id', $user_id)->column('dep_id,status');
            foreach ($auth as $k => $v) {
                if (array_key_exists($v['nodeId'], $user_auth)) {
                    if ($v['checked'] == 0) {
                        $del_data[] = $v['nodeId'];
                    } else if ($v['checked'] == 1 && $user_auth[$v['nodeId']] != 1) {
                        $selected[] = $v['nodeId'];
                    } else if ($v['checked'] == 2 && $user_auth[$v['nodeId']] != 2) {
                        $no_all_selected[] = $v['nodeId'];
                    }
                } else if ($v['checked'] > 0) {
                    $add_data[] = ['dep_id' => $v['nodeId'], 'admin_id' => $user_id, 'status' => $v['checked']];
                }
            }
            try {
                Db::startTrans();
                $res = $res2 = $res3 = $res4 = true;
                if ($add_data) {
                    $res = $depAuth->insertAll($add_data);
                }
                if ($del_data) {
                    $res2 = $depAuth->where([['dep_id', 'in', $del_data], ['admin_id', '=', $user_id]])->delete();
                }
                if ($no_all_selected) {
                    $res3 = Db::name('admin_user_dep_auth')->where([['dep_id', 'in', $no_all_selected], ['admin_id', '=', $user_id]])->update(['status' => 2]);
                }
                if ($selected) {
                    $res4 = Db::name('admin_user_dep_auth')->where([['dep_id', 'in', $selected], ['admin_id', '=', $user_id]])->update(['status' => 1]);
                }
                if ($res && $res2 && $res3 && $res4) {
                    Db::commit();
                    return json(array('code' => 200, 'msg' => '保存成功'));
                } else {
                    Db::rollback();
                    return json(array('code' => 0, 'msg' => '保存失败'));
                }
            } catch (Exception $e) {
                Db::rollback();
                return json(array('code' => 0, 'msg' => $e->getMessage()));
            }
        } else {
            $id = input('id', 0);
            if (empty($id)) {
                $this->error('缺少参数');
                die;
            }
            $this->assign('user_id', $id);
            return view();
        }
    }

    public function updatestatus($id, $status, $name)
    {
        if ($this->request->isGet()) {
            if ($this->admin_user_model->where('id', $id)->update([$name => $status]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }
}