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
use app\common\model\Forum as ForumModel;
use app\common\model\ForumPost;
use think\Db;

/**
 * 论坛管理
 * Class Form
 * @package app\admin\controller
 */
class Forum extends AdminBase
{
    protected $forum_model;

    protected function initialize()
    {
        parent::initialize();
        $this->forum_model = new ForumModel();
    }


    public function index($keyword = '', $page = 1)
    {

        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('帖子标题', 'title', 'text', ['exp' => 'LIKE']);

        if (input('page', 0) > 0) {
            $where = $modelHelper->getSearchWhere();
//            $where[] = ['f.deleted', '=', 0];
            $count = Db::name('forum_post')
                ->alias('f')
                ->join('forum_forum c', 'c.forum_id=f.forum_id')
                ->order('f.post_id desc')
                ->field('f.*,c.forum_name')
                ->where($where)
                ->count();
            $list = Db::name('forum_post')
                ->alias('f')
                ->join('forum_forum c', 'c.forum_id=f.forum_id')
                ->order('f.post_id desc')
                ->field('f.*, f.post_id as id, c.forum_name')
                ->where($where)
                ->select();
            foreach ($list as $k => &$v) {
                $v['add_time'] = date('Y年m月d日 H时i分', $v['add_time']);
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('帖子标题', 'title', 'text')
            // ->addField('帖子内容', 'content', 'text')
            ->addField('所属栏目', 'forum_name', 'text')
            ->addField('添加时间', 'add_time', 'text')
            ->addRowBtn('详情', Url('details'), 'barDemo', null, 'btn-warm', 'btn')
            ->addRowBtn('删除', Url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'width' => 120,
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);

        return $modelHelper->showList();
    }

    /**
     * 帖子详情
     * @return [type] [description]
     */
    public function details()
    {
        $id = (int)input('id', 0);
        if (empty($id)) {
            $this->error('缺少参数');
        }
        $post = Db::name('forum_post')
            ->alias('f')
            ->join('forum_forum c', 'c.forum_id=f.forum_id')
            ->field('f.*, f.post_id as id, c.forum_name')
            ->where('f.post_id', $id)
            ->order('f.post_id desc')
            ->find();
        $this->assign('post', $post);
        return view();
    }

    public function alldelete()
    {
        $params = input('post.');
        $ids = implode(',', $params['ids']);

        $result = $this->forum_model->destroy($ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    /**
     * 推荐热门
     */
    public function recommend($id, $status, $name)
    {
        if ($this->request->isGet()) {
            if ($status == 1) {
                $status = time();
            }
            if (Db::name('forum_post')->where('post_id', $id)->update([$name => $status]) !== false) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 删除帖子
     */
    public function delete($id)
    {
        $where = ['post_id' => $id];
        $post = Db::name('forum_post')->where($where)->find();
        if ($post) {
            $res = Db::name('forum_post')->where($where)->delete();
            Db::name('forum_comment')->where($where)->delete();
            if ($res) {
                return json(array('code' => 1, 'msg' => '删除成功'));
            } else {
                return json(array('code' => 0, 'msg' => '删除失败'));
            }
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function add($forum_id = '')
    {
        $category = Db::name('forum_forum');
        $tptc = $category->where(array('is_show' => 1))->select();
        foreach ($tptc as $k => $v) {
            $options[$v['forum_id']] = $v['forum_name'];
        }
        $article = new ForumPost();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($forum_id) {
                $r = $article->allowField(true)->save($data, ['forum_id' => $forum_id]);
            } else {
                $r = $article->allowField(true)->save($data);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $item = [];
        if ($forum_id) {
            $item = $article->get($forum_id);
            if (empty($item)) {
                $this->error('item error');
            }
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle($forum_id ? '编辑' : '添加')
            ->addField('标题', 'title', 'text', [])
            ->addField('版块', 'forum_id', 'select', ['options' => $options])
            ->addField('内容', 'content', 'editor')
            ->addField('评论', 'comment_open', 'radio', ['options' => ['0' => '禁止评论', '1' => '正常评论', '2' => '管理员才能看评论']])
            ->addField('ID', 'forum_id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * @param $id
     * @param $status
     * @param string $name
     */
    public function updateStatus($id, $status, $name = 'status')
    {
        if ($this->request->isGet()) {
            if ($this->model->where('id', $id)->update([$name => $status]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }
}