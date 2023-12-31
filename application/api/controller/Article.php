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

use app\api\model\UserDep;
use app\api\model\Article as AritcleModel;
use app\common\model\User;
use think\Db;

class Article extends Base
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function articleList($page, string $cate_id = '')
    {
        $cateId = explode(',', $cate_id);
        $size = input('size', 12);
        $keyword = input('keyword', '');
        $where = [];
        if ($keyword) {
            $where[] = ['title|author', 'like', '%' . $keyword . '%'];
        }
        if ($cate_id) {
            $whereOr[] = ['a.cat_id', 'in', $cateId];
        }

        $articleList = AritcleModel::alias('a')
            ->field('a.id,a.thumb,a.title,a.create_time,a.author')
            ->where($whereOr)
            ->where($where)
            ->where('a.status', '=', 1)
            ->order('a.id desc')
            ->group('a.id')
            ->page($page, $size)
            ->select();
        if (count($articleList) >= $size) {
            $loading = 0;
        } else {
            $loading = 2;
        }
        foreach ($articleList as $k => $v) {
            $articleList[$k]['create_time'] = date('Y-m-d', $v['create_time']);
        }
        jsonReturn(1, '获取成功', [
            'list' => $articleList,
            'loading' => $loading
        ]);
    }

    public function details()
    {
        $id = (int)input('article_id', 0);
        if (empty($id)) {
            return json(['status' => -1, 'msg' => '缺少参数']);
        }
        $where[] = ['id', '=', $id];
        $where[] = ['status', '=', 1];
        $info = db('article')->where($where)->find();
        AritcleModel::where('id', $id)->setInc('click');
        if (empty($info)) {
            return json(['status' => -1, 'msg' => '文章已下架']);
        }
        $info['content'] = str_replace('/uploads/', SITE_URL . "/uploads/", $info['content']);
        $info['content'] = str_replace('/Public/upload/', SITE_URL . "/Public/upload/", $info['content']);
        $info['content'] = str_replace('/ueditor/php/upload/', SITE_URL . "/ueditor/php/upload/", $info['content']);
        $info['content'] = htmlspecialchars_decode($info['content']);

        $info['content'] = preg_replace("/(<a .*?href=\")((?!http).*?)(\".*?>)/is", "\${1}" . SITE_URL . "\${2}\${3}", $info['content']);
        $info['content'] = preg_replace("/(<img .*?src=\")((?!http).*?)(\".*?>)/is", "\${1}" . SITE_URL . "\${2}\${3}", $info['content']);
        //设置table最大宽度
        $info['content'] = preg_replace("/(<table .*?width=\")((?!http).*?)(\".*?>)/is", "\${1}100%\${3}", $info['content']);
        $info['create_time'] = date('Y年m月d日H:i', $info['create_time']);

        // 获取栏目名称
        $info['cat_name'] = Db::name('category')->where('id', $info['cat_id'])->value('cat_name');
        // 补全图片地址
        $info['thumb'] = complete_url($info['thumb']);

        $info['keep_read'] = false;
        // 记录点击的时间
        $articleRead = Db::name('article_read')
            ->where('article_id', $info['id'])
            ->where('user_id', $this->user_id)
            ->find();
        if (!$articleRead) {
            $readId = Db::name('article_read')->insertGetId([
                'article_id' => $info['id'],
                'user_id' => $this->user_id,
                'create_time' => time(),
                'point' => $info['point'],
                'read_time' => 0,
                'point_status' => 1,
            ]);
            $articleRead = Db::name('article_read')
                ->where('id', $readId)
                ->find();
        }
        $info['user_point_status'] = $articleRead['point_status'];
        if ($articleRead['point_status'] == 1) {
            // 记录开始阅读的文章 时间 需要阅读时长
            $readKey = 'read' . $this->user_id;
            $needReadTime = 1; // 需要阅读时长1分钟
            $readCache = [
                'id' => $info['id'],
                'title' => $info['title'],
                'time' => time(),
                'need_read_time' => $needReadTime,
                'point' => $info['point'],
            ];
            cache($readKey, $readCache, 60);
            $info['keep_read'] = true;
        }

        return json(['status' => 1, 'msg' => '获取成功', 'result' => $info]);
    }

    /**
     * 阅读加积分
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function keepRead()
    {
        $input = input();
        $readKey = 'read' . $this->user_id;
        $data = cache($readKey);
        if (!$data) {
            jsonReturn(1, $this->user_id . 'no cache');
        }
        if(empty($data['point'])){
            jsonReturn(1, '该文章没有积分');
        }
        cache($readKey, $data, 60);
        // 如果 当前时间大于 开始时间加上需要阅读的时间就加积分 小于就继续读
        if (time() < ($data['time'] + (60 * $data['need_read_time']))) {
            jsonReturn(1, 'keep on', ['keep' => true, 'ms' => 10000]);
            return json(['status' => 1, 'msg' => '继续', 'result' => ['done' => false, 'time' => $input['time']]]);
        }

        // 下面加积分
        $articleRead = Db::name('article_read')
            ->where('article_id', $data['id'])
            ->where('user_id', $this->user_id)
            ->find();
        if (!$articleRead) {
            // 一般不会走这里，升级的时候前端整合先读取了资讯内容才会走这里
            $readId = Db::name('article_read')->insertGetId([
                'article_id' => $data['id'],
                'user_id' => $this->user_id,
                'create_time' => time(),
                'point' => $data['point'],
                'read_time' => 0,
                'point_status' => 1,
            ]);
            $articleRead = Db::name('article_read')
                ->where('id', $readId)
                ->find();
        }
        // 积分已经发了
        if ($articleRead['point_status'] == 2) {
            return json(['status' => 1, 'msg' => '获得积分', 'result' => ['done' => true]]);
        }
        // 单日积分上限6分
        $todayPoint = Db::name('article_read')
            ->where('user_id', $this->user_id)
            ->where('point_status', 2)
            ->where('create_time', '>', strtotime(date('Y-m-d')))
            ->where('create_time', '<', strtotime(date('Y-m-d')) + 86400)
            ->sum('point');
        if ($todayPoint >= 6) {
            return json(['status' => 1, 'msg' => '今日获得积分已达到上限', 'result' => ['done' => true]]);
        }

        Db::name('article_read')
            ->where('id', $articleRead['id'])
            ->update([
                'article_id' => $data['id'],
                'user_id' => $this->user_id,
                'update_time' => time(),
                'point' => $data['point'],
                'read_time' => (time() - $data['time']),
                'point_status' => 2,
            ]);

        $desc = '阅读《' . $data['title'] . '》增加' . $data['point'] . '积分';
        if (User::savePoint($this->user_id, $data['point'], $desc, 1)) {
            return json(['status' => 1, 'msg' => '获得积分', 'result' => ['done' => true]]);
        } else {
            return json(['status' => 0, 'msg' => '']);
        }
    }

    /**
     * 获取党史上的今天
     */
    public function getTodayArticle()
    {
        // TODO 获取今天的日期，拼接成字符串，然后去查article表的title字段
        $date = date("n-j", strtotime("today"));
        $title = '【党史上的今天】' . str_replace('-', '月', $date) . '日';
        $article = DB::name('article')->where('title', $title)->find();
        jsonReturn(1, '', $article);
    }
}
