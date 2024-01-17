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
use app\common\model\Message as MessageModel;

class Message extends AdminBase
{
    protected $message_model;

    protected function initialize()
    {
        parent::initialize();
        $this->message_model = new MessageModel();
    }


    public function index()
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test');

        if (\think\facade\Request::isAjax()) {
            $count = $this->message_model->order('time desc')->count();
            $list = $this->message_model->with('user')
                ->order('time desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => &$v) {
                $time = friendlyDate($v['time']);
                $v['times'] = $time;
                if ($v['type'] == 1) {
                    $v['type'] = '系统消息';
                } else if ($v['type'] == 3) {
                    $v['type'] = '会议通知';
                } else if ($v['type'] == 5) {
                    $v['type'] = '活动通知';
                } else if ($v['type'] == 6) {
                    $v['type'] = '督办通知';
                }
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }
        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('时间', 'times', 'text')
            ->addField('接收人', 'nickname', 'text')
            ->addField('类型', 'type', 'text')
            ->addField('内容', 'content', 'text')
            ->addRowBtn('编辑', url('add'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $modelHelper->showList();
    }

    /**
     * 添加
     * @return mixed
     */
    public function add($id = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['content'])) {
                return json(['code' => 0, 'msg' => '请输入消息内容']);
            }
            $admin_id = \session('admin_id');
            $data['uid'] = $admin_id;
            if ($id) {
                $r = $this->message_model->allowField(true)->save($data, ['id' => $id]);
                $msg = '编辑';
            } else {
                $r = $this->message_model->allowField(true)->save($data);
                $msg = '添加';
            }
            if ($r) {
                return json(['code' => 1, 'msg' => $msg . '成功']);
            } else {
                return json(['code' => 0, 'msg' => $msg . '失败']);
            }
        }
        $item = [];
        if ($id) {
            $item = $this->message_model->find($id);
            if (empty($item)) {
                $this->error('item error');
            }
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle($id ? '编辑系统消息' : '添加系统消息')
            ->addField('内容', 'content', 'textarea', ['require' => '*'])
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 保存
     */
    public function save()
    {
        $admin_id = \session('admin_id');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['uid'] = $admin_id;
            $id = $data['id'];
            unset($data['id']);
            if ($this->message_model->save($data, ['id' => $id])) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
    }

    /**
     * 编辑
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $slide_category = $this->message_model->find($id);

        return $this->fetch('edit', ['slide_category' => $slide_category]);
    }

    /**
     * 更新
     * @throws \think\Exception
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //   $data['uid']=$admin_id;
            $id = $data['id'];
            unset($data['id']);
            if ($this->message_model->where(['id' => $id])->update($data) !== false) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 删除
     * @param $id
     * @throws \think\Exception
     */
    public function delete($id)
    {
        if ($this->message_model->where(['id' => $id])->delete()) {
            return json(array('code' => 1, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
}