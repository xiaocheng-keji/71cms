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
use app\common\model\MeetingRec as model;

/**
 * 会议记录管理
 * Class MeetingRec
 * @package app\admin\controller
 */
class MeetingRec extends AdminBase
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
            ->setTitle('list test')
            ->addSearchField('主题', 'theme', 'text', ['exp' => 'LIKE']);

        if (input('page', 0) > 0) {
            $where = $this->modelHelper->getSearchWhere();
            $count = $this->model::with(['department'])->where($where)
                ->order('id')
                ->count();
            $list = $this->model::with(['department'])->where($where)
                ->order('id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['department_name'] = $v['department']['name'];
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $this->modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('部门', 'department_name', 'text', ['width' => 200])
            ->addField('时间', 'date_time', 'datetime', ['width' => 160])
            ->addField('地点', 'address')
            ->addField('主题', 'theme')
            ->addField('主持人', 'compere')
            ->addField('记录人', 'recorder')
            ->addField('参会人数', 'people_num')
            ->addField('添加时间', 'create_time', 'datetime', ['width' => 160])
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
        //下面是展示表单
        $item = [];
        if ($id) {
            $item = $model::with(['department'])->get($id);
            if (empty($item)) {
                $this->error('item error');
            }
            $item['department_name'] = $item['department']['name'];
        }

        $this->modelHelper = new ModelHelper();
        $this->modelHelper
            ->setTitle($id ? '编辑' : '添加')
            ->addField('栏目', 'cat_id', 'select', ['options' => cat_options()])
            ->addField('时间', 'date_time', 'datetime')
            ->addField('地点', 'address')
            ->addField('主题', 'theme')
            ->addField('主持人', 'compere')
            ->addField('记录人', 'recorder')
            ->addField('部门', 'department_id', 'select', ['options' => dep_options()])
            ->addField('应到人数', 'need_people')
            ->addField('实到人数', 'people_num')
            ->addField('内容', 'content', 'editor')
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