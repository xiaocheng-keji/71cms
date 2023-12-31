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
use think\Db;
use app\common\model\Log as LogModel;

/**
 * 日志管理
 */
class Log extends AdminBase
{
    protected $logmodel;

    protected function initialize()
    {
        parent::initialize();
        $this->logmodel = new LogModel();
    }

    public function index($keyword = '', $page = 1)
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('用户名', 'username', 'text', ['exp' => 'LIKE']);

        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $count = $this->logmodel->useGlobalScope(false)
                ->alias('l')
                ->join('auth_rule ar', 'ar.name=l.controller')
                ->where($where)
                ->count();
            $list = $this->logmodel->useGlobalScope(false)
                ->alias('l')
                ->join('auth_rule ar', 'ar.name=l.controller')
                ->where($where)
                ->field('l.*,ar.title,ar.pid')
                ->order('l.id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['add_time'] = date('Y-m-d H:i', $v['add_time']);
                if ($v['pid'] == 0) {
                    $list[$k]['pcontroller'] = '顶级';
                } else {
                    $list[$k]['pcontroller'] = Db::name('auth_rule')->where('id', $v['pid'])->cache(60)->value('title');
                }
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('操作', 'pcontroller', 'text')
            ->addField('子操作', 'title', 'text')
            ->addField('用户名', 'username', 'text')
            ->addField('操作时间', 'add_time', 'text');
        return $modelHelper->showList();
    }
}
