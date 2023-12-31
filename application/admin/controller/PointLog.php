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
use app\common\model\PointLog as PointLogModel;
use app\common\model\User as UserModel;

/**
 * 积分管理
 * Class PointLog
 * @package app\admin\controller
 */
class PointLog extends AdminBase
{
    protected $model;
    protected $modelHelper;

    /**
     * 用户积分记录
     */
    public function index($user_id)
    {
        $this->model = new PointLogModel();
        $this->modelHelper = new ModelHelper();
        $this->modelHelper
            ->setTitle('list test')
            ->addSearchField('描述', 'desc', 'text', ['exp' => 'LIKE']);

        if (\think\facade\Request::isAjax()) {
            $where = $this->modelHelper->getSearchWhere();
            $where[] = ['user_id', '=', $user_id];
            $count = $this->model->where($where)
                ->order('id')
                ->count();
            $list = $this->model::where($where)
                ->order('id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        //$userInfo的排名
        $userInfo = UserModel::find($user_id);
        $this->modelHelper
            ->addTips('目前积分：' . $userInfo['point'] . '。 ')
            ->addTips('搜索框无关键词时，按回车键或单击搜索，即可显示积分列表')
            ->addTopBtn('返回', 'javascript:history.go(-1);')
            ->addTopBtn('修改 ' . $userInfo['nickname'] . ' 的积分', Url('User/editPoint', ['user_id' => $user_id]))
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('积分', 'points', 'number', ['width' => 80])
            ->addField('描述', 'desc', 'textarea')
            ->addField('时间', 'create_time', 'datetime');
        return $this->modelHelper->showList();
    }
}