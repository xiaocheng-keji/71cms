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
use app\wap\model\Meeting;
use \app\common\model\Article as AritcleModel;
use app\common\model\Category as CategoryModel;

class Category extends Base
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function index()
    {
        $id = (int)input('id', 0);
        if ($id <= 0) {
            return json(['status' => -1, 'msg' => '缺少参数']);
        }
        $pid = input('pid', '');
        $p = input('page', 1);
        $size = (int)input('size', 10);
        $category = CategoryModel::find($id)->toArray();
        if ($pid) {
            $p_category = CategoryModel::find($pid)->toArray();
            $catList = CategoryModel::where('parent_id', $p_category['id'])->order('sort', 'asc')->select();
            $title = $p_category['cat_name'];
        } else {
            $title = $category['cat_name'];
            $catList = [];
        }
        $articleList = [];
        $meetingList = [];
        if ($category['type'] == 1) {
            //文章列表
            $articleList = AritcleModel::alias('a')
                ->field('a.*')
                ->where('a.cat_id', '=', $category['id'])
                ->order('a.recommend desc, a.id desc')
                ->group('a.id')
                ->page($p, $size)
                ->select();

            foreach ($articleList as $k => &$v) {
                $v['content'] = strip_tags(htmlspecialchars_decode($v['content']));
                $v['thumb'] = SITE_URL . $v['thumb'];
            }
        } elseif ($category['type'] == 2 || $category['type'] == 3 || $this->checkMeeting($category['id'])) {
            $cate_id = $this->getCateAll([$category['id']]);
            //会议列表
            $meetingList = Meeting::hasWhere('meetingUser', ['user_id' => $this->user_id])
                ->with(['compereUser', 'recordUser'])
                ->whereIn('cat_id', $cate_id)
                ->where('deleted', '=', 0)
                ->where(['meeting_type' => input('meeting')])
                ->order('id', 'desc')
                ->page($p, $size)
                ->select();
        }
        $result['title'] = $title;
        $result['catList'] = $catList;
        $result['category'] = $category;
        $result['articleList'] = $articleList;
        $result['meetingList'] = $meetingList;
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }


    public function getCateAll($id)
    {
        $childCate = \app\common\model\Category::whereIn('parent_id', $id)->where(['status' => 1])->column('id');
        if ($childCate) {
            return array_merge($id, $this->getCateAll($childCate));
        } else {
            return $id;
        }
    }

    public function checkMeeting($id)
    {
        $childCate = \app\common\model\Category::whereIn('parent_id', $id)->where(['status' => 1])->column('type');
        $flag = false;
        foreach ($childCate as $item) {
            if ($item == 3 || $item == 2) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }


    /**
     * 获取栏目列表
     *
     * @param int $pid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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

    /**
     * 获取栏目详情
     *
     * @param int $id
     * @throws \think\exception\DbException
     */
    public function getItem($id = 0, $type = '')
    {
        $where = [];
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        $list = CategoryModel::where('id', $id)
            ->where('status', 1)
            ->where($where)
            ->order('sort', 'asc')
            ->select();
        jsonReturn(1, '栏目列表', $list);
    }
}