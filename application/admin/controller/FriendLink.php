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
use app\common\model\FriendLink as FriendLinkModel;

/**
 * 友情链接管理
 * Class FriendLink
 * @package app\admin\controller
 */
class FriendLink extends AdminBase
{

    protected $model;

    protected function initialize()
    {
        parent::initialize();
        $this->model = new FriendLinkModel();
    }

    /**
     * 链接管理
     *
     * @return mixed
     */
    public function index()
    {
        if (\think\facade\Request::isAjax()) {
            $count = $this->model
                ->order(['sort' => 'ASC', 'id' => 'ASC'])
                ->count();
            $list = $this->model->order(['sort' => 'ASC', 'id' => 'ASC'])
                ->page(input('page', 1), input('limit', 10))
                ->column('id,name,sort,status,pid,icon,link', 'id');
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper = new ModelHelper();
        $modelHelper
            ->addTopBtn('添加链接', url('add'))
            ->addTips('点击排序的序号可编辑排序，数字越小排序越靠前')
            ->addField('ID', 'id', 'text', ['width' => 70, 'align' => 'center', 'sort' => false])
            ->addField('名称', 'name', 'text', ['width' => 200])
            ->addField('链接', 'link', 'text')
            ->addField('图标', 'icon', 'image', ['width' => 200])
            ->addField('排序', 'sort', 'text', ['edit' => 'text', 'width' => 100])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 100,
                'toolbar' => '#barDemo1'
            ])->addField('操作', 'status', 'templet', [
                'width' => 120,
                'templet' => '<div>
                                <a class="layui-btn layui-btn-info layui-btn-xs" data-href="/admin/friend_link/add?id={{= d.id }}" data-ext="?id={{= d.id }}" lay-event="' . url('add') . '?id={{= d.id }}">编辑</a>
                                {{#  if(d.tpl_id == null){ }}
                                        <a class="layui-btn layui-btn-warm layui-btn-xs" data-ext="?id={{= d.id }}" data-delete-href="' . url('delete') . '" lay-event="delete">删除</a>
                                {{#  } }}
                                </div>',
            ]);
        return $modelHelper->showList();
    }

    /**
     * 添加导航
     * @param string $pid
     * @return mixed
     */
    public function add($id = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = $this->validate($data, 'FriendLink');
            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                if (empty($id)) {
                    $msg = '添加';
                    $res = $this->model->save($data);
                } else {
                    $msg = '编辑';
                    unset($data['id']);
                    $res = $this->model->save($data, ['id' => $id]);
                }
                if ($res !== false) {
                    return json(array('code' => 1, 'msg' => $msg . '成功'));
                } else {
                    return json(array('code' => 0, 'msg' => $msg . '失败'));
                }
            }
        } else {
            $nav = [];
            if ($id) {
                $nav = $this->model->find($id);
                if (empty($nav)) {
                    $this->error('nav error');
                }
            }
            $options = $this->model->optionsArr();
            foreach ($options as &$v) {
                $v['title'] = $v['name'];
                $v['disabled'] = isset($nav['id']) && $nav['id'] > 0 && $v['id'] == $nav['id'];
            }
            $modelHelper = new ModelHelper();
            $modelHelper
                ->setTitle($id ? '编辑链接' : '添加链接')
                ->addField('导航名称', 'name', 'text', ['require' => '*'])
//                ->addField('上级', 'pid', 'select', ['require' => '*', 'options' => $options])
                ->addField('点击打开链接', 'link', 'text', ['tip' => '点击打开的链接', 'placeholder' => ''])
                ->addField('封面图', 'icon', 'image')
                ->addField('状态', 'status', 'radio', ['options' => ['隐藏', '显示']])
                ->addField('排序', 'sort', 'number')
                ->addField('ID', 'id', 'hidden')
                ->setData($nav);
            return $modelHelper->showForm();
        }
    }

    public function updatestatus($id, $status)
    {
        if ($this->request->isGet()) {
            if ($this->model->where('id', $id)->update(['status' => $status]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除导航
     * @param $id
     */
    public function delete($id)
    {
        if ($this->model->where('id', $id)->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function updateField()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $r = $this->model->allowField('sort')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }
}