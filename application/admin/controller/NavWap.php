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
use app\common\model\NavWap as NavWapModel;
use util\Tree;

/**
 * 移动端导航管理
 * Class NavWap
 * @package app\admin\controller
 */
class NavWap extends AdminBase
{

    protected $nav_model;

    protected function initialize()
    {
        parent::initialize();
        $this->nav_model = new NavWapModel();
    }

    /**
     * 导航管理
     * @return mixed
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test');
//            ->addSearchField('名称', 'forum_name', 'text', ['exp' => 'LIKE']);

        if (\think\facade\Request::isAjax()) {
            $count = $this->nav_model
                ->order(['sort' => 'ASC', 'id' => 'ASC'])
                ->count();
            $list = $this->nav_model->order(['sort' => 'ASC', 'id' => 'ASC'])->column('id,name,sort,status,pid,icon,link,app_link', 'id');
            $Trees = new Tree();
            $Trees->tree($list, 'id', 'pid', 'name');
            $list = $Trees->getArray();
            $list = array_values($list);
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('添加菜单', url('add'))
            ->addTips('点击排序的序号可编辑排序，数字越小排序越靠前')
            ->addField('ID', 'id', 'text', ['width' => 70, 'align' => 'center', 'sort' => false])
            ->addField('名称', 'name', 'text', ['width' => 200])
            ->addField('链接', 'link', 'text', ['width' => 300])
            ->addField('APP链接', 'app_link', 'text', ['width' => 300])
            ->addField('图标', 'icon', 'image', ['width' => 100])
            ->addField('排序', 'sort', 'text', ['edit' => 'text', 'width' => 100])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 100,
//                'fixed' => 'right',
                'toolbar' => '#barDemo1'
            ])->addField('操作', 'status', 'templet', [
                'width' => 120,
                'fixed' => 'right',
                'templet' => '<div>
                                <a class="layui-btn layui-btn-info layui-btn-xs" data-href="/admin/nav_wap/add?id={{= d.id }}" data-ext="?id={{= d.id }}" lay-event="' . url('add') . '?id={{= d.id }}">编辑</a>
                                {{#  if(d.tpl_id == null){ }}
                                        <a class="layui-btn layui-btn-warm layui-btn-xs" data-ext="?id={{= d.id }}" data-delete-href="' . url('delete') . '" lay-event="delete">删除</a>
                                {{#  } }}
                                </div>',
            ]);
        return $modelHelper->setPage(false)->showList();
    }

    /**
     * 添加导航
     * @param string $pid
     * @return mixed
     */
    public function add($id = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, null);
            if ($data['func']) {
                $data['link'] = $data['func'];
            } else if ($data['cat']) {
                $data['link'] = $data['cat'];
            }
            //处理applink里转义的&符号
            $data['app_link'] = str_replace('&amp;', '&', $data['app_link']);
            $validate_result = $this->validate($data, 'NavWap');
            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                if (empty($id)) {
                    $msg = '添加';
                    $res = $this->nav_model->save($data);
                } else {
                    $msg = '编辑';
                    unset($data['id']);
                    $res = $this->nav_model->save($data, ['id' => $id]);
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
                $nav = $this->nav_model->find($id);
                if (empty($nav)) {
                    $this->error('nav error');
                }
                //处理applink里转义的&符号
                $nav['app_link'] = str_replace('&amp;', '&', $nav['app_link']);
            }

            $func_options = [
                '0' => '选择功能',
                '/wap/func/payment_list' => '交党费',
                '/wap/video/study' => '学习中心',
                '/wap/exam/index' => '在线考试',
                '/wap/appraise/appraise_list' => '考评',
                '/wap/supervise/index' => '待办',
                '/wap/vote/index' => '投票',
            ];

            $cat_list = \app\common\model\Category::column('*', 'id');
            $Trees = new Tree();
            $Trees->tree($cat_list, 'id', 'parent_id', 'cat_name');
            $cat_list = $Trees->getArray();
            unset($cat_list[0]);
            $cat_options = [];
            $cat_options[0] = '选择栏目';
            foreach ($cat_list as $k => $v) {
                $link = url('/wap/category/index', ['pid' => $v['parent_id'], 'id' => $v['id']]);
                $cat_options[$link] = $v['cat_name'];
            }

            if (isset($func_options[$nav['link']])) {
                $nav['func'] = $nav['link'];
                unset($nav['link']);
            } else if (isset($cat_options[$nav['link']])) {
                $nav['cat'] = $nav['link'];
                unset($nav['link']);
            }
            $options = $this->nav_model->optionsArr();
            foreach ($options as &$v) {
                $v['title'] = $v['name'];
                $v['disabled'] = $nav['id'] > 0 && $v['id'] == $nav['id'];
            }
            $modelHelper = new ModelHelper();
            $modelHelper
                ->setTitle($id ? '编辑菜单' : '添加菜单')
                ->addField('导航名称', 'name', 'text', ['require' => '*'])
                ->addField('上级菜单', 'pid', 'select', ['require' => '*', 'options' => $options])
                ->addField('点击打开功能', 'func', 'select', ['options' => $func_options, 'tip' => '点击打开 功能/栏目/链接 三个中只能设置一个'])
                ->addField('点击打开栏目', 'cat', 'select', ['options' => $cat_options, 'tip' => '点击打开 功能/栏目/链接 三个中只能设置一个'])
                ->addField('点击打开链接', 'link', 'text', ['tip' => '点击打开 功能/栏目/链接 三个中只能设置一个', 'placeholder' => '可以为空'])
                ->addField('APP链接', 'app_link', 'text')
                ->addField('封面图', 'icon', 'image')
                ->addField('状态', 'status', 'radio', ['options' => ['隐藏', '显示']])
                ->addField('排序', 'sort', 'number')
                ->addField('ID', 'id', 'hidden')
                ->setData($nav);
            return $modelHelper->showForm();
        }


        // return $this->fetch('add');
    }

    public function updatestatus($id, $status)
    {
        if ($this->request->isGet()) {
            if ($this->nav_model->where('id', $id)->update(['status' => $status]) !== false) {
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
        if ($this->nav_model->where('id', $id)->delete()) {
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
                $r = $this->nav_model->allowField('sort')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }
}