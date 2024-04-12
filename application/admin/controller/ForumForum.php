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

use app\common\model\ForumForum as ForumForumModel;
use app\common\controller\ModelHelper;
use think\Db;

/**
 * 论坛版块管理
 * Class Department
 * @package app\admin\controller
 */
class ForumForum extends AdminBase
{
    protected $forum_model;

    protected function initialize()
    {
        parent::initialize();
        $this->forum_model = new ForumForumModel();

        $category_level_list = $this->forum_model->catetree();
        $this->assign('category_level_list', $category_level_list);
    }

    /**
     * 版块列表
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('名称', 'forum_name', 'text', ['exp' => 'LIKE']);

        if ($this->request->isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $count = ForumForumModel::order('sort_order asc, forum_id desc')
                ->where($where)
                ->count();
            $list = ForumForumModel::order('sort_order asc, forum_id desc')
                ->where($where)
                ->field('forum_id as id, forum_name, is_show, sort_order, add_time')
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('添加版块', url('add'))
            ->addTips('排序规则：数字越小排序越前')
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('板块名称', 'forum_name', 'text')
            ->addRowBtnEx('显示|隐藏', url('updatestatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'is_show', 'opt' => ['field' => 'is_show', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo1'
            ])
            ->addField('排序', 'sort_order', 'text', ['edit' => 'text'])
            ->addRowBtn('编辑', url('edit'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $modelHelper->showList();
    }

    /**
     * 添加版块
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存版块
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'ForumForum');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
                //$this->error($validate_result);
            } else {
                if ($this->forum_model->allowField(true)->save($data)) {
                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }
    }

    /**
     * 编辑版块
     */
    public function edit($id)
    {
        $category = $this->forum_model->where(['forum_id' => $id])->find();
        return $this->fetch('edit', ['tptc' => $category]);
    }

    /**
     * 更新版块
     * @param $id
     */
    public function update($forum_id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'ForumForum');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                if (empty($data['is_show'])) {
                    $data['is_show'] = 0;
                }
                $res = $this->forum_model->allowField(true)->save($data, ['forum_id' => $forum_id]);
                if ($res !== false) {
                    return json(array('code' => 200, 'msg' => '更新成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '更新失败'));
                }
            }
        }
    }

    /**
     * 删除栏目
     * @param $id
     */
    public function delete($id)
    {
        $article = Db::name('forum_post')->where(['forum_id' => $id])->find();

        if (!empty($article)) {
            $this->error('此分类下存在文章，不可删除');
        }
        $res = Db::name('forum_forum')->where(['forum_id' => $id])->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function updatestatus($id, $status, $name)
    {
        if ($this->request->isGet()) {
            if ($this->forum_model->where('forum_id', $id)->update([$name => $status]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }
}