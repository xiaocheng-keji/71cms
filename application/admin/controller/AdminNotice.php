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
use think\facade\Request;

/**
 * 后台通知控制器
 * Class AdminUser
 * @package app\admin\controller
 */
class AdminNotice extends AdminBase
{
    public function noticeCount()
    {
        \think\facade\Log::close();
        $reg = \app\admin\model\AdminNotice::where('type', 'reg')->where('status', 1)->count();
        $develop = \app\admin\model\AdminNotice::where('type', 'develop')->where('status', 1)->count();
        return json(array('code' => 200, 'msg' => '读取成功', 'data' => ['reg' => $reg, 'develop' => $develop]));
    }

    public function lists()
    {
        $modelHelper = new ModelHelper();
        $model = new \app\admin\model\AdminNotice();

        $type = Request::param('type');
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('', 'content', 'text', ['exp' => 'LIKE']);

        if (input('page', 0) > 0) {
            $where = $modelHelper->getSearchWhere();
            $count = $model::where($where)
                ->order('id desc')
                ->count();
            if ($type) {
                $where[] = ['type', '=', $type];
            }
            $list = $model::where($where)
                ->order('status desc, id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('内容', 'content', 'text')
            ->addField('时间', 'create_time', 'text', ['width' => 170])
            ->addField('状态', 'status', 'text', ['width' => 70])
            ->addRowBtn('已读', url('read'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 70
            ]);
        return $modelHelper->showList();
    }

    public function show()
    {
        $id = Request::param('id');
        $data = \app\admin\model\AdminNotice::get($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function read()
    {
        $id = Request::param('id');
        \app\admin\model\AdminNotice::where('id', '=', $id)->update(['status' => 0, 'read_id' => session('admin_id')]);
        $this->success('操作成功');
    }
}