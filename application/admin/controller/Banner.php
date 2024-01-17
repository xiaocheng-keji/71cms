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
use app\common\model\Banner as bannerModel;

/**
 * 页面图片管理
 * Class Banner
 * @package app\admin\controller
 */
class Banner extends AdminBase
{

    protected $model;

    protected function initialize()
    {
        parent::initialize();
        $this->model = new bannerModel();
    }

    /**
     * 列表
     *
     * @return \think\response\Json|\think\response\View
     */
    public function list()
    {
        $group_list = bannerModel::where('id', '>', 0)->group('group')->column('group');
        $group_select = [0 => '全部'];
        foreach ($group_list as $k => $v) {
            $group_select[$v] = $v;
        }
        $modelHelper = new ModelHelper();
        $modelHelper
            ->addSearchField('标题', 'title', 'text', ['exp' => 'LIKE'])
            ->addSearchField('分组', 'group', 'select', ['options' => $group_select]);

        if (\think\facade\Request::isAjax()) {
            $where = [];
            $title = input('title', '');
            if ($title) {
                $where[] = ['title', 'like', '%' . $title . '%'];
            }
            $group = input('group', '');
            if ($group) {
                $where[] = ['group', '=', $group];
            }
            $article = new bannerModel();
            $count = $article::alias('a')
                ->where($where)
                ->group('a.id')
                ->count();
            $list = $article::alias('a')
                ->where($where)
                ->order('a.id asc')
                ->field('a.*')
                ->group('a.id')
                ->page(input('page', 1), input('limit', 10))
                ->select();

            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 70, 'align' => 'center', 'sort' => false])
            ->addField('分组', 'group', 'text')
            ->addField('标题', 'title', 'text')
//            ->addField('名称', 'name', 'text')
            ->addField('链接', 'link', 'text')
            ->addField('图片', 'img', 'image', ['width' => 200])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 100,
                'toolbar' => '#barDemo1'
            ])->addField('操作', 'status', 'templet', [
                'width' => 120,
                'templet' => '<div>
                                <a class="layui-btn layui-btn-info layui-btn-xs" data-href="/admin/banner/add?id={{= d.id }}" data-ext="?id={{= d.id }}" lay-event="' . url('add') . '?id={{= d.id }}">编辑</a>
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
            $data = $this->request->post('',[],null);
            $validate_result = $this->validate($data, \app\admin\validate\Banner::class);
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
                    $this->error('id error');
                }
            }
            $modelHelper = new ModelHelper();
            $modelHelper
                ->setTitle($id ? '编辑链接' : '添加链接')
                ->addField('分组', 'group', 'text', ['require' => '*'])
                ->addField('标题', 'title', 'text', ['require' => '*'])
//                ->addField('名称', 'name', 'text', ['require' => '*'])
                ->addField('点击打开链接', 'link', 'text', ['tip' => '点击打开的链接', 'placeholder' => ''])
                ->addField('图片', 'img', 'image')
                ->addField('状态', 'status', 'radio', ['options' => ['隐藏', '显示']])
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
}