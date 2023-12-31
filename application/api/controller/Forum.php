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
namespace app\api\controller;

use app\common\controller\Upload;
use think\Db;
use app\api\model\ForumPost as ForumPostModel;

class Forum extends Base
{
	protected function initialize () {
		// $this->user_id = 2482;
        parent::initialize();
        if (time() < strtotime(getConfig('bbs_open_time')) || time() > strtotime(getConfig('bbs_close_time'))) {
            jsonReturn(104,'未到论坛开放时间');
        }
    }

    // 获取版块列表
    public function getPlate () {
    	$plateList = Db::name('forum_forum')->where('is_show', 1)->order('sort_order asc')->select();
    	return json(['status'=>1, '获取成功', 'result'=>$plateList]);
    }

    // 获取帖子列表
    public function getPost () {
    	$key = input('key', '');
        $forum_id = (int)input('forum_id', 0);
    	// $p = (int)input('page', 1) <= 0 ? 1 : (int)input('page', 1);
        $p = (input('p', 0) + 1);
    	$size = (int)input('size', 10);
    	$where[] = ['deleted', '=', 0];
    	if ($forum_id > 0) {
    		$where[] = ['forum_id', '=', $forum_id];
    	}
    	if (!empty($key)) {
    		$where[] = ['title', 'like', '%' . $key . '%'];
    	}
    	$postList = ForumPostModel::with(['postPlate', 'postUser'])->where($where)->page($p, $size)->order('add_time desc')->select();
    	foreach ($postList as $k => &$v) {
            $image = Db::name('forum_image')->where('post_id', $v['post_id'])->column('path');
            foreach ($image as $key => &$val) {
                // $val = SITE_URL . str_replace('forum_post', 'forum_post/thumb', $val);
                $val = SITE_URL . $val;
            }
    		$v['content'] = htmlspecialchars_decode($v['content']);
            $content_image_url = get_img_src_from_str($v['content']);
            $v['content_image_url'] = array_merge($content_image_url, $image);
    		$v['add_time'] = date('Y/m/d', $v['add_time']);
            if (strtolower(substr($v['post_user'][0]['head_pic'], 0, 4)) !== 'http') {
        		$v['post_user'][0]['head_pic'] = SITE_URL . $v['post_user'][0]['head_pic'];
            }
    	}
        return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$postList]);
    }

    // 获取帖子详情
    public function postDetails () {
        $id = (int)input('post_id', 0);

        if ($id <= 0) return json(['status'=>0, 'msg'=>'缺少参数']);
        $forumPost = Db::name('forum_post');
        $forumPost->where([['deleted', '=', 0], ['post_id', '=', $id]])->setInc('click', 1);
        $postInfo = $forumPost
            ->alias('a')
            ->where([['a.deleted', '=', 0], ['post_id', '=', $id]])
            ->find();
        $isZan = Db::name('forum_zan')->where(['user_id'=>$this->user_id, 'post_id'=>$id])->count();
        $image =  Db::name('forum_image')->where('post_id', $id)->select();
        foreach ($image as $k => &$v) {
            if (strtolower(substr($$v['path'], 0, 4)) != 'http') {
                $v['path'] = SITE_URL . $v['path'];
            }
        }
        $postInfo['content'] = htmlspecialchars_decode(str_ireplace('src=&quot;/', 'src=&quot;' . SITE_URL . '/', $postInfo['content']));
        $postInfo['add_time'] = date('Y/m/d', $postInfo['add_time']);
		$user = Db::name('user')
		    ->alias('a')
		    ->where([['id', '=', $postInfo['user_id']]])
		    ->find();
		$postInfo['head_pic'] = complete_url($user['head_pic']);
		$postInfo['nickname'] = $user['nickname'];
        if ($isZan) {
            $result['zan'] = true;
        } else {
            $result['zan'] = false;
        }
        $result['post_image'] = $image;
        $result['postInfo'] = $postInfo;
        return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$result]);
    }

    /**
     * 帖子点赞
     */
    public function postZan () {
        $uid = $this->user_id;
        $data = $this->request->param();
        $where['post_id'] = $data['post_id'];
        $where['user_id'] = $uid;
        $res = Db::name('forum_zan')->where($where)->find();
        Db::startTrans();
        if (empty($res)) {
            $data['add_time'] = time();
            $data['user_id'] = $uid;
            unset($data['token']);
            // 启动事务
            try {
                unset($where['user_id']);
                $res = Db::name('forum_zan')->insert($data);
                $res2 = Db::name('forum_post')->where($where)->setInc('zan');
                if ($res && $res2) {
                    Db::commit();
                   return json(array('status' => 1, 'msg' => '点赞成功'));
                } else {
                    Db::rollback();
                    return json(array('status' => 0, 'msg' => '点赞失败'));
                }
            } catch (Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'msg' => '点赞失败'));
            }
        } else {
            // return json(array('status' => 0, 'msg' => '你已经赞过该评论'));
            try {
                $res = Db::name('forum_zan')->where($where)->delete();
                unset($where['user_id']);
                $res2 = Db::name('forum_post')->where($where)->setDec('zan');
                if ($res && $res2) {
                    Db::commit();
                   return json(array('status' => 1, 'msg' => '取消点赞成功'));
                } else {
                    Db::rollback();
                    return json(array('status' => 0, 'msg' => '取消点赞失败'));
                }
            } catch (Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'msg' => '取消点赞失败'));
            }
        }
    }

    // 获取帖子评论列表
    public function commentList () {
        $post_id = input('post_id');
        if ($post_id <= 0) return json(['status'=>0, 'msg'=>'缺少参数']);
        $parent_id = input('parent_id', 0);
        $page = input('page');
        $size = input('size', 10);
        $user_id = $this->user_id;
        $map = array(
            'a.post_id' => $post_id,
            'a.is_show' => 1,
            'a.parent_id' => $parent_id,
            'a.deleted' => 0,
        );
        $list = Db::name('forum_comment')
            ->alias('a')
            ->join('user b', 'a.user_id = b.id')
            ->where($map)
            ->order('a.comment_id asc')
            ->field('a.*,b.id as user_id,b.head_pic,b.nickname')
            ->page($page, $size)
            ->select();
        foreach ($list as $k => $v) {
            // 判断当前用户是否对该评论点过赞
            $where['comment_id'] = $v['comment_id'];
            $where['user_id'] = $user_id;
            $zan = Db::name("forum_comment_zan")->where($where)->count();
            if ($zan) {
                $list[$k]['commentZan'] = true;
            } else {
                $list[$k]['commentZan'] = false;
            }
            if (strtolower(substr($v['head_pic'], 0, 4)) !== 'http') {
                $list[$k]['head_pic'] = SITE_URL . $v['head_pic'];
            }
            //时间格式化
            $list[$k]['add_time'] = format_date($v['add_time']);
            //评论回复
            if($parent_id == 0 && $v['reply_num'] > 0){
                $list[$k]['reply'] = Db::name('forum_comment')
                    ->alias('a')//评论的回复
                    ->join('user b', 'a.user_id = b.id')//评论的回复人
                    ->join('forum_comment c ', 'a.reply_id = c.comment_id')//回复
                    ->join('user d ', ' c.user_id = d.id')//回复(会员)
                    ->where([['a.parent_id', '=', $v['comment_id']], ['a.deleted', '=', 0]])
                    ->limit(2)
                    ->order('a.comment_id asc')
                    ->field('a.comment_id,a.content,a.reply_id,b.nickname,d.nickname as nick_name2')
                    ->select();
            }

        }
        return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$list]);
    }

    /**
     * 添加评论
     */
    public function comment () {
        $id = (int)input('post_id', 0);
        $uid = $this->user_id;
        $data = input();
        if (empty($data['content'])) return json(array('status' => 0, 'msg' => '内容不能为空'));
        $data['add_time'] = time();
        // $data['post_id'] = $id;
        $data['user_id'] = $this->user_id;
        $data['content']= remove_xss($data['content']);
        $data['ip_address'] = request()->ip();
        unset($data['token']);
        // 启动事务
        Db::startTrans();
        try {
            $res = $res2 = $res3 = true;
            $res = Db::name('forum_comment')->insertGetId($data);;
            $res2 = Db::name('forum_post')->where(['post_id'=>$id])->setInc('comment_count');
            if($data['parent_id']>0){
                $res3 = Db::name('forum_comment')->where(['comment_id'=>$data['parent_id']])->setInc('reply_num');
            }
            if ($res && $res2 && $res3) {
                Db::commit();
                $result = Db::name('forum_comment')->where(['comment_id'=> $res])->find();
                $user = Db::name('user')->where('id', $result['user_id'])->field('head_pic, nickname')->find();
                $result['add_time'] = format_date($result['add_time']);
//                if (strtolower(substr($user['head_pic'], 0, 4)) !== 'http') {
                    $user['head_pic'] = SITE_URL . $user['head_pic'];
//                }
                $result = array_merge($result, $user);
                return json(array('status' => 1, 'msg' => '评论成功', 'result'=>$result));
            } else {
                Db::rollback();
                return json(array('status' => 0, 'msg' => '评论失败'));
            }
        } catch (Exception $e) {
            // dump($e->getMessage());
            Db::rollback();
            return json(array('status' => 0, 'msg' => '评论失败', 'error'=>$e->getMessage()));
        }
    }

    /**
     * 点赞评论
     */
    public function commentZan () {
        $data = $this->request->param();
        $uid = $this->user_id;
        unset($data['token']);
        if (empty($data['comment_id'])) return json(array('status' => 0, 'msg' => '缺少参数'));
        $where['comment_id'] = $data['comment_id'];
        $where['user_id'] = $uid;
        $res = Db::name('forum_comment_zan')->where($where)->find();
        Db::startTrans();
        if (empty($res)) {
            $data['add_time'] = time();
            $data['user_id'] = $uid;
            // 启动事务
            try {
                unset($where['user_id']);
                $res = Db::name('forum_comment_zan')->insert($data);
                $res2 = Db::name('forum_comment')->where($where)->setInc('zan_num');
                if ($res && $res2) {
                    Db::commit();
                    return json(array('status' => 1, 'msg' => '点赞成功'));
                } else {
                    Db::rollback();
                    return json(array('status' => 0, 'msg' => '点赞失败'));
                }
            } catch (Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'msg' => '点赞失败'));
            }
        } else {
            // return json(array('code' => 0, 'msg' => '你已经赞过该评论'));
            try {
                $res = Db::name('forum_comment_zan')->where($where)->delete();
                unset($where['user_id']);
                $res2 = Db::name('forum_comment')->where($where)->setDec('zan_num');
                if ($res && $res2) {
                    Db::commit();
                    return json(array('status' => 1, 'msg' => '取消点赞成功'));
                } else {
                    Db::rollback();
                    return json(array('status' => 0, 'msg' => '取消点赞失败'));
                }
            } catch (Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'msg' => '取消点赞失败'));
            }
        }
    }


    /**
     * 发布帖子
     */
    public function release () {
        $data['user_id'] = $this->user_id;
        $data['title'] = htmlspecialchars_decode(input('title',''));
        $data['content'] = htmlspecialchars_decode(input('content',''));
        $data['forum_id'] = (int)input('plate_id',0);
		if (empty($data['forum_id'])) return json(array('status' => 0, 'msg' => '请选择分类'));
        if (empty($data['title'])) return json(array('status' => 0, 'msg' => '请选择标题'));
        if (empty($data['content'])) return json(array('status' => 0, 'msg' => '请填写内容'));
        $data['add_time'] = time();
        $data['update_time'] = time();
        $id = Db::name('forum_post')->insertGetId($data);
        if ($id) {
            return json(array('status' => 1, 'msg' => '发布成功', 'post_id'=>$id));
        } else {
            return json(array('status' => 0, 'msg' => '发布失败'));
        }
    }

    /**
     * 上传图片
     */
    public function imageUp () {
        $post_id = (int)input('post_id', 0);
        if (empty($post_id)) return json(array('status' => 0, 'msg' => '缺少参数'));
        $config = [
            'size'=> 20000000,
            'ext'=>'jpg,jpeg,png,gif'
        ];
        $upload = new Upload();
        $info = $upload->uploadFiles('/Public/forum_post/'.$post_id, $config);
        if ($info['status'] == 1) {
            $data['post_id'] = $post_id;
            $data['path'] = $info['result']['path'];
            $data['add_time'] = time();
            $res = Db::name('forum_image')->insert($data);
            if ($res) {
                $result = array('status'=>1 , 'msg'=>'上传成功', 'result'=>$info['result']);
            } else {
                $result = array('status'=>0 , 'msg'=>'上传失败', 'result'=>'');
            }
            return json($info);
        } else {
            return json($info);
        }
    }
}