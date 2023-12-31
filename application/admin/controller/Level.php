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
use app\common\model\UserLevel as model;

/**
 * 职务管理
 * Class Level
 * @package app\admin\controller
 */
class Level extends AdminBase
{
    public $modelHelper;
    public $model;

    public function initialize()
    {
        parent::initialize();
        $this->modelHelper = new ModelHelper();
        $this->model = new model();
    }

    /**
     * 列表
     *
     * @return \think\response\Json|\think\response\View
     */
    public function index()
    {
        $this->modelHelper
            ->setTitle('list test')
            ->addSearchField('职务', 'name', 'text', ['exp' => 'LIKE']);

        if (\think\facade\Request::isAjax()) {
            $where = $this->modelHelper->getSearchWhere();
            $count = $this->model::where($where)
                ->order('sort')
                ->count();
            $list = $this->model::where($where)
                ->order('sort asc, id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => &$v) {
                $v['type'] = model::$type_options[$v['type']];
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $this->modelHelper
            ->addTopBtn('添加职务', Url('edit'))
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('职务名称', 'name', 'text')
            ->addField('职务描述', 'describe', 'textarea')
            ->addField('类型', 'type', 'select')
            ->addField('排序', 'sort', 'text', ['edit' => 'text'])
            ->addRowBtn('编辑', Url('edit'))
            ->addRowBtn('删除', Url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $this->modelHelper->setPage(true)->showList();
    }

    /**
     * 编辑 添加
     *
     * @param string $id
     * @return \think\response\View
     */
    public function edit(string $id = '')
    {
        $model = new model();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = $this->validate($data, 'level');
            if ($validate !== true) $this->error($validate);
            if ($id) {
                $r = $model->allowField(true)->save($data, ['id' => $id]);
            } else {
                $r = $model->allowField(true)->save($data);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $item = [];
        if ($id) {
            $item = $model::with([])->get($id);
            if (empty($item)) {
                $this->error('item error');
            }
        }

        $this->modelHelper = new ModelHelper();
        $this->modelHelper
            ->setTitle($id ? '编辑' : '添加职务')
            ->addField('类型', 'type', 'select', ['options' => $model::$type_options, 'tip' => '', 'require' => '*'])
            ->addField('职务名称', 'name', 'text', ['require' => '*'])
            ->addField('职务描述', 'describe', 'textarea')
            ->addField('排序', 'sort', 'text', ['require' => '*'])
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $this->modelHelper->showForm();
    }


    /**
     * 删除
     *
     * @param string $id
     */
    public function delete(string $id)
    {
        $item = model::get($id);
        if ($item && $item->delete()) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function updateField()
    {
        $model = new model();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $r = $model->allowField(true)->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }

}