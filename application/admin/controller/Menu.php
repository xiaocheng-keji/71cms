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
use app\common\model\AuthRule as AuthRuleModel;
use util\Tree;

/**
 * 后台菜单
 * Class Menu
 * @package app\admin\controller
 */
class Menu extends AdminBase
{

    protected $auth_rule_model;

    protected function initialize()
    {
        parent::initialize();
        $this->auth_rule_model = $this->model = new AuthRuleModel();
    }

    /**
     * 后台菜单
     * @return mixed
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        if (\think\facade\Request::isAjax()) {
            $this->auth_rule_model = new AuthRuleModel();
                $list = $this->auth_rule_model
                    ->alias('a')
                    ->leftJoin('auth_rule_conf b', 'a.id=b.a_id')
                    ->orderRaw('IF(b.sort is null,a.sort,b.sort) ASC')
                    ->order(['b.sort' => 'ASC', 'a.sort' => 'ASC', 'a.id' => 'ASC'])
                    ->column('a.id,a.title,a.name,IF(b.sort is null,a.sort,b.sort) as sort,IF(b.status is null,a.status,b.status) as status,a.pid', 'a.id');
            $Trees = new Tree();
            $Trees->tree($list, 'id', 'pid', 'title');
            $admin_menu_level_list = $Trees->getArray();
            unset($admin_menu_level_list[0]);
            $list = array_values($admin_menu_level_list);
            return json(['code' => 1, 'msg' => '', 'count' => count($list), 'data' => $list]);
        }
        $modelHelper
            ->addTopBtn('添加菜单', url('add'))
            ->addTips('点击排序的序号可编辑排序，数字越小排序越前')
            ->addField('ID', 'id', 'text', ['width' => 70, 'align' => 'center', 'sort' => false])
            ->addField('名称', 'title', 'text', ['width' => 300])
            ->addField('控制器方法', 'name', 'text', ['width' => 300])
            ->addField('排序', 'sort', 'text', ['edit' => 'text', 'width' => 80])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 120,
                'fixed' => 'right',
                'toolbar' => '#barDemo1'
            ])
            ->addRowBtn('添加子菜单', url('add'), 'barDemo', null, 'btn-warm', 'btn')
            ->addRowBtn('编辑', url('edit'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $modelHelper->setPage(false)->showList();
    }

    /**
     * 添加菜单
     * @param string $pid
     * @return mixed
     */
    public function add($id = '')
    {
        //保存或增加
        if (request()->isAjax()) {
            $data = input('post.');
            $data['name'] = trim($data['name']);
            $validate_result = $this->validate($data, 'Menu');
            if ($validate_result !== true) {
                $this->error($validate_result);
            }
            $res = $this->auth_rule_model->allowField(['title', 'name', 'pid', 'image', 'layer', 'icon', 'status', 'sort'])->save($data);
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        $options = $this->auth_rule_model->optionsArr();
        if ($id) {
            $modelHelper = new ModelHelper();
            $modelHelper
                ->setTitle('添加子菜单')
                ->addField('菜单名称', 'title', 'text', ['require' => '*', 'tip' => ''])
                ->addField('控制器方法', 'name', 'text', ['require' => '*', 'tip' => ''])
                ->addField('上级菜单', 'pid', 'select', ['require' => '*', 'options' => $options])
                ->addField('图标', 'icon', 'text', ['tip' => '支持的图标：layui'])
                ->addField('是否显示', 'status', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
                ->addField('排序', 'sort', 'number', ['require' => '*'])
                ->addField('ID', 'id', 'hidden')
                ->setData(['pid' => $id]);
            return $modelHelper->showForm();
        } else {
            $modelHelper = new ModelHelper();
            $modelHelper
                ->setTitle('添加菜单')
                ->addField('菜单名称', 'title', 'text', ['require' => '*', 'tip' => ''])
                ->addField('控制器方法', 'name', 'text', ['require' => '*', 'tip' => ''])
                ->addField('上级菜单', 'pid', 'select', ['require' => '*', 'options' => $options])
                ->addField('图标', 'icon', 'text', ['tip' => '支持的图标：layui'])
                ->addField('是否显示', 'status', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
                ->addField('排序', 'sort', 'number', ['require' => '*'])
                ->addField('ID', 'id', 'hidden');
            return $modelHelper->showForm();
        }
    }

    /**
     * 编辑菜单
     * @param $id
     * @return mixed
     */
    public function edit($id = 0)
    {
        //保存或增加
        $item = $this->auth_rule_model->find($id);
        if (request()->isAjax()) {
            $data = input('post.', null, null);
            $data['name'] = trim($data['name']);
            $validate_result = $this->validate($data, 'Menu');
            if ($validate_result !== true) {
                $this->error($validate_result);
            }
            $res = $this->auth_rule_model->allowField(['title', 'name', 'pid', 'image', 'layer', 'icon', 'status', 'sort'])->save($data, ['id' => $id]);
            if ($res) {
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }
        $options = $this->auth_rule_model->optionsArr();
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('编辑菜单')
            ->addField('菜单名称', 'title', 'text', ['require' => '*', 'tip' => ''])
            ->addField('控制器方法', 'name', 'text', ['require' => '*', 'tip' => ''])
            ->addField('上级菜单', 'pid', 'select', ['require' => '*', 'options' => $options])
            ->addField('图标', 'icon', 'text', ['tip' => '支持的图标：layui'])
            ->addField('是否显示', 'status', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
            ->addField('排序', 'sort', 'number', ['require' => '*'])
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 更新菜单
     * @param $id
     */
    public function update($id)
    {
        $item = $this->auth_rule_model->find($id);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = $this->validate($data, 'Menu');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->auth_rule_model->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除菜单
     * @param $id
     */
    public function delete($id)
    {
        $item = $this->auth_rule_model->find($id);
        $sub_menu = $this->auth_rule_model->where(['pid' => $id])->find();
        if (!empty($sub_menu)) {
            $this->error('此菜单下存在子菜单，不可删除');
        }
        if ($this->auth_rule_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 修改排序
     */
    public function updateField()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = $this->auth_rule_model->allowField('sort')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            if ($r) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * @param $id
     * @param $status
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 修改状态
     */
    public function updatestatus($id, $status)
    {
        if ($this->request->isGet()) {
            $r = $this->auth_rule_model->where('id', $id)->update(['status' => $status]);
            if ($r !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }
}