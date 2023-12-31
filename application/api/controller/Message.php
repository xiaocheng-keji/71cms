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

use app\wap\model\Message as messageModel;
use think\Db;
use app\common\model\Category as CategoryModel;

class Message extends Base
{
	protected function initialize () {
		parent::initialize();
	}

	public function __construct () {
		parent::__construct();
	}

	/**
	 * 消息列表
	 */
	public function getMessageList () {
		$p = (int)input('page', 1) < 1 ? 1 : (int)input('page', 1);
		$size = input('size', 10);
		$where = [];
        $type = input('type', '');
        if ($type) {
            $where[] = ['type', '=', $type];
        }
		$result = \app\wap\model\Message::where('uid', $this->user_id)->where('status', 1)->where($where)->order('id', 'desc')->page($p, $size)->select();
		foreach ($result as $k => &$v) {
			$v['content'] = htmlspecialchars_decode($v['content']);
			$v['content'] = str_ireplace('src="/', 'src="' . SITE_URL . '/',  $v['content']);
			$v['content'] = preg_replace(
	            [
	                '/(<img [^<>]*?)width=.+?[\'|\"]/',
	                '/(<img [^<>]*?)/',
	                '/(<img.*?)((height)=[\'"]+[0-9|%]+[\'"]+)/',
	                '/(<img.*?)((style)=[\'"]+(.*?)+[\'"]+)/',
	            ],
	            [
	                '$1 width="100%" ',
	                '$1 width="100%" ',
	                '$1',
	                '$1'
	            ],
	            $v['content']
	        );
		}
		return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$result]);
	}

	public function getCount(){
	    $count = messageModel::where(['uid'=>$this->user_id,'read_time'=>'','status'=>1])->count();
	    jsonReturn(1,'获取成功',(string)$count);
    }

	/**
	 * 删除消息
	 */
	public function delMessage () {
		$id = (int)input('id', 0);
		if (empty($id)) return json(['status'=>0, 'msg'=>'缺少参数']);
		$info = messageModel::find($id);
		if ($info['uid'] != $this->user_id) {
			return json(['status'=>0, 'msg'=>'你无权删除']);
		}
		$update['status'] = 2;
		$res = $info->allowField(true)->save($update);
		if ($res) {
			return json(['status'=>1, 'msg'=>'删除成功']);
		} else {
			return json(['status'=>0, 'msg'=>'删除失败']);
		}
	}

    /**
     * 获取不同消息的未读数量和最后一条未读消息
     */
    public function index()
    {
        //每天首次登录 增加积分
        $today = strtotime(date('Y-m-d'));
        if ($this->user['last_login_time'] < $today) {
            //每日更新一次登陆时间
            \app\common\model\User::where('id', $this->user['id'])->update(['last_login_time'=>time()]);
            \app\common\model\User::savePoint($this->user['id'], 1, '每日登录',4);
        }

        $result = [];

        // 通知公告栏目未读的数量和最后一条内容
        $unread = Db::name('article')
            ->alias('a')
            ->leftJoin('article_read b', 'a.id=b.article_id and b.user_id=' . $this->user_id)
            ->where('a.cat_id', 68)
            ->where('b.id', null)
            ->count();;
        $content = '';
		$time = '';
		$name = '通知公告';
        if ($unread > 0) {
            $content = Db::name('article')
                ->alias('a')
                ->leftJoin('article_read b', 'a.id=b.article_id and b.user_id=' . $this->user_id)
                ->where('a.cat_id', 68)
                ->where('b.id', null)
                ->order('a.id', 'desc')
                ->value('title');
			$time = Db::name('article')
			    ->alias('a')
			    ->leftJoin('article_read b', 'a.id=b.article_id and b.user_id=' . $this->user_id)
			    ->where('a.cat_id', 68)
			    ->where('b.id', null)
			    ->order('a.id', 'desc')
			    ->value('a.create_time');
        }
        $result['notice'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 会议的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 3)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '会议通知';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 3)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 3)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['meeting'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 活动的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 5)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '活动通知';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 5)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 3)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['activity'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 待办的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 8)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '待办通知';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 8)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 3)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['todo'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 系统的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 1)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '系统通知';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 1)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 3)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['system'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 书记信箱的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 11)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '书记信箱';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 11)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 11)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['sec_mail'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        // 任务清单的消息
        $unread = Db::name('message')
            ->where('uid', $this->user_id)
            ->where('type', 12)
            ->where('read_time', 0)
            ->count();
        $content = '';
		$time = '';
		$name = '任务清单';
        if ($unread > 0) {
            $content = Db::name('message')
                ->where('uid', $this->user_id)
                ->where('type', 12)
                ->where('read_time', 0)
                ->order('id', 'desc')
                ->value('content');
			$time = Db::name('message')
			    ->where('uid', $this->user_id)
			    ->where('type', 12)
			    ->where('read_time', 0)
			    ->order('id', 'desc')
			    ->value('time');
        }
        $result['task_info'] = [
            'unread' => $unread,
            'content' => $content,
			'time' => $time,
			'name' => $name
        ];

        jsonReturn(1, '获取成功', $result);
    }
	public function getList($pid = 0, $id = 0, $type = '')
	{
	    $where = [];
	    if ($type !== '') {
	        $where[] = ['type', '=', $type];
	    }
	    if ($id) {
	        $list = CategoryModel::where('id', $id)
	            ->where('status', 1)
	            ->where($where)
	            ->order('sort', 'asc')
	            ->select();
	    } else {
	        $list = CategoryModel::where('parent_id', $pid)
	            ->where('status', 1)
	            ->where($where)
	            ->order('sort', 'asc')
	            ->select();
	    }
	    jsonReturn(1, '栏目列表', $list);
	}
	public function getMsgTypeList($id = 0, $type = '')
	{
		$content = '';
		$content = Db::name('message')
		    ->where('uid', $this->user_id)
		    ->where('type', $id)
		    ->where('read_time', 0)
		    ->select();
	    jsonReturn(1, '栏目列表', $content);
	}
	public function detail($id)
	    {
	        $item = Db::name('message')
	            ->where('id', $id)
	//            ->where('a.show', 1)
	            ->find();
	        if (!$item) {
	            jsonReturn(-1, '数据获取失败');
	        }
	
	        jsonReturn(1, '获取成功', $item);
	    }
	    
	        /**
     * 阅读消息
     */
        public function read($id) {
            $item = Db::name('message')
                ->where('id', $id)
                ->find();
            if (!$item) {
                jsonReturn(-1, '数据获取失败');
            }
            $data = [
                'read_time' => time()
            ];
            Db::name('message')
                ->where('id', $id)
                ->update($data);
            jsonReturn(1, '获取成功', $item);
        }
}