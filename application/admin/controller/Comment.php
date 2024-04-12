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
use app\common\model\Comment as CommentModel;
use think\Db;

/**
 * 评论
 *  Class Comment
 * @package app\admin\controller
 */
class Comment extends AdminBase
{
    protected $commentmodel;

    protected function initialize()
    {
        parent::initialize();
        $this->commentmodel = new CommentModel();
    }

    /**
     * 评论列表
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('所属帖子', 'title', 'text', ['exp' => 'LIKE']);

        if ($this->request->isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $where[] = ['c.deleted', '=', 0];
            $where[] = ['f.deleted', '=', 0];
            $count = db('forum_comment')
                ->alias('c')
                ->join('forum_post f', 'f.post_id=c.post_id')
                ->join('user m', 'm.id=c.user_id')
                ->where($where)
                ->order('c.comment_id desc')
                ->count();
            $list = db('forum_comment')
                ->alias('c')
                ->join('forum_post f', 'f.post_id=c.post_id')
                ->join('user m', 'm.id=c.user_id')
                ->where($where)
                ->field('c.comment_id as id, c.content, c.add_time, f.title, m.nickname')
                ->order('c.comment_id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => &$v) {
                $v['add_time'] = date('Y年m月d日 H时i分', $v['add_time']);
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('评论内容', 'content', 'text')
            ->addField('所属帖子', 'title', 'text')
            ->addField('评论人', 'nickname', 'text')
            ->addField('评论时间', 'add_time', 'text')
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'width' => 80,
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);

        return $modelHelper->showList();
    }

    /**
     * 删除评论
     */
    public function delete($id)
    {
        $where[] = ['comment_id', '=', $id];
        $info = db('forum_comment')->where($where)->find();
        if ($info && $info['deleted'] == 0) {
            $update['deleted'] = 1;
            $update['delete_time'] = time();
            Db::startTrans();
            try {
                Db::name('forum_comment')->where($where)->update($update);
                Db::name('forum_post')->where(['post_id' => $info['post_id']])->setDec('comment_count');
                Db::commit();
                return json(array('code' => 1, 'msg' => '删除成功'));
            } catch (Exception $e) {
                Db::rollback();
                return json(array('code' => 0, 'msg' => '删除失败'));
            }
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    /**
     * 删除选中的评论
     */
    public function alldelete()
    {
        $id_arr = input('post.ids');
        $list = db('forum_comment')->where('comment_id', 'in', $id_arr)->select();
        foreach ($list as $key => $value) {
            db('forum_post')->where('post_id', '=', $value['post_id'])->setDec('comment_count');
        }
        $update['deleted'] = 1;
        $update['delete_time'] = time();
        db('forum_comment')->where('comment_id', 'in', $id_arr)->update($update);
        return json(array('code' => 200, 'msg' => '删除成功'));
    }
}