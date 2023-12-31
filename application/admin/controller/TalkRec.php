<?php
/**
 * 71CMS [ 创先云智慧党建系统 ]
 * =========================================================
 * Copyright (c) 2018-2023 南宁小橙科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.71cms.net
 * 这不是一个自由软件！未经许可不能去掉71CMS相关版权。
 * 禁止对系统程序代码以任何目的，任何形式的再发布。
 * =========================================================
 */
namespace app\admin\controller;

use app\common\controller\ModelHelper;
use app\common\model\TalkRec as model;

/**
 * 谈话记录
 */
class TalkRec extends AdminBase
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
     * @return \think\response\Json|\think\response\View
     * 列表
     */
    public function index()
    {
        $this->modelHelper
            ->setTitle('list test');

        if (input('page', 0) > 0) {
            $where = $this->modelHelper->getSearchWhere();
            $where['status'] = $where['status'] ?? 1;
            $count = $this->model::where($where)
                ->order('sort')
                ->count();
            $list = $this->model::where($where)
                ->order('sort desc, id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $this->modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('主题', 'theme')
            ->addField('形式', 'meeting_mode')
            ->addField('主持人', 'compere')
            ->addField('记录人', 'recorder')
            ->addField('摘要', 'abstract')
            ->addField('应到人数', 'people_num')
            ->addField('添加时间', 'create_time', 'datetime', ['templet' => ModelHelper::transformUnixTime("create_time"), 'width' => 160])
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 120
            ]);
        return $this->modelHelper->showList();
    }

    /**
     * @param string $id
     * @return \think\response\View
     * 编辑 添加
     */
    public function edit(string $id = '')
    {
        $model = new model();
        if ($this->request->isPost()) {
            $data = $this->request->post();
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
            ->setTitle($id ? '编辑' : '添加')
            ->addField('主题', 'theme')
            ->addField('形式', 'meeting_mode')
            ->addField('主持人', 'compere')
            ->addField('记录人', 'recorder')
            ->addField('摘要', 'abstract')
            ->addField('应到人数', 'people_num')
            ->addField('添加时间', 'create_time', 'datetime')
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $this->modelHelper->showForm();
    }


    /**
     * @param string $id
     * 删除
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