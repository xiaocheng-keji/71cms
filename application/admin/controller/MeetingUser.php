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
use app\common\model\MeetingUser as model;

/**
 * 会议用户管理
 * Class Department
 * @package app\admin\controller
 */
class MeetingUser extends AdminBase
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
        $user_id = input('user_id', '');
        $light = input('light', '');
        $this->modelHelper
            ->setTitle('list test')
            ->addSearchField('名称', 'name', 'text', ['exp' => 'LIKE']);

        if (input('page', 0) > 0) {
            $where = $this->modelHelper->getSearchWhere();
            $where['a.user_id'] = $user_id;
            $where['a.light'] = $light;
            $count = $this->model::alias('a')
                ->where($where)
                ->order('id')
                ->count();
            $list = $this->model::alias('a')
                ->where($where)
                ->alias('a')
                ->leftJoin('__MEETING__ b', 'a.meeting_id=b.id')
                ->order('a.id desc, a.id desc')
                ->field('a.*,b.theme')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $this->modelHelper
            ->addTopBtn('返回', 'javascript:history.go(-1);')
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('会议', 'theme', 'text')
            ->addField('到会状态', 'sign_status', 'text')
            ->addField('签到/请假时间', 'sign_time', 'text')
            ->addField('心得', 'study_rec', 'text')
            ->addField('心得提交状态', 'study_rec_status', 'text')
            ->addField('心得提交时间', 'study_rec_time', 'text');
            //->addField('会议', 'light', 'text');
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
            ->addField('名称', 'name', 'text')
            ->addField('描述', 'describe', 'textarea')
            ->addField('排序', 'sort', 'text')
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