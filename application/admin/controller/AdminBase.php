<?php
/**
 * 71CMS [ 创先云智慧党建系统 ]
 * =========================================================
 * Copyright (c) 2018-2023 南宁小橙科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.71cms.net
 * 这不是一个自由软件！未经许可不能去掉71CMS相关版权。
 * 禁止对系统程序代码以任何目的，任何形式的再发布。
 * =========================================================
 */

namespace app\admin\controller;

use app\common\model\AdminUser;
use org\Auth;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Session;
use think\Loader;

/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\admin\controller
 */
class AdminBase extends Controller
{
    public $tenant;
    public $site_config;
    public $admin_user;

    protected function initialize()
    {
        parent::initialize();
        $system_version = file_get_contents(app()->getAppPath() . 'version.php');
        $this->assign('system_version', $system_version);

        if (is_null(session('admin_name'))) {
            // 判断来路 如果没有来路就直接跳转到登录页
            if ($_SERVER["HTTP_REFERER"] == null) {
                $referer = parse_url($_SERVER["HTTP_REFERER"]);
                if ($referer['host'] != $_SERVER['SERVER_NAME']) {
                    $this->redirect(url('login/index'));
                }
            }
            $this->error('登录超时，请重新登录', url('login/index'), 1,1);
        }
        $this->checkAuth();
        $this->admin_user = session('admin_user');
        $root = 'http://' . $_SERVER['HTTP_HOST'] . getbaseurl();
        $this->assign('root', $root);
        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $this->assign('controller', Loader::parseName($this->request->controller()));
        $this->getSystem();
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();

        // 排除权限
        $not_check = ['admin/Index/index','admin/Index/home','admin/Index/update'];
        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $admin_id = Session::get('admin_id');
            $auth = new Auth();

            //user表的用户登录后台 切换授权验证表
            if (session('login_type') == 'user') {
                $admin_id = session('user_id');
                Config::set('app.auth.auth_group_access', 'auth_group_access_dep');
                $auth = new Auth();
            }
            if ($admin_id != 1 && !$auth->check($module . '/' . $controller . '/' . $action, $admin_id)) {
                $this->error('账号没有获得此权限');
            }
        }
        //操作记录
        if (!in_array($module . '/' . $controller . '/' . $action, ['admin/Log/index', 'admin/AdminNotice/noticecount'])) {
            $data['uid'] = session('admin_id');
            $data['add_time'] = time();
            $data['controller'] = $module . '/' . $controller . '/' . $action;
            $data['username'] = session('admin_name');
            if(empty($data['username'])){
                $data['username'] =  "未知";
            }
            $data['user_type'] = session('login_type') == 'user' ? 1 : 0;
            Db::name('log')->insert($data);
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu = [];
        //user表的用户登录后台
        if (session('login_type') == 'user') {
            $admin_id = session('user_id');
            $admin_user['is_super'] = 0;
            Config::set('app.auth.auth_group_access', 'auth_group_access_dep');
        } else {
            $admin_id = Session::get('admin_id');
            $admin_user = session('admin_user');
        }
        $auth = new Auth();
        //获取系统菜单和用户自己添加的菜单 并根据租户的配置显示不显示
        $auth_rule_list = Db::name('auth_rule')
            ->alias('a')
            ->leftJoin('auth_rule_conf b', 'a.id=b.a_id ')
            ->where('a.status', 1)
            ->where(function ($query) {
                $query->where('b.status', 1)->whereOr('b.status', 'null');
            })
            ->orderRaw('IF(b.sort is null,a.sort,b.sort) ASC')
            ->order(['b.sort' => 'ASC', 'a.sort' => 'ASC', 'a.id' => 'ASC'])
            ->field('a.*')
            ->select();
        foreach ($auth_rule_list as $value) {
            if ($admin_id == 1 || ($admin_user['is_super'] == 1) || (strtolower(substr($value['name'], 0, 4)) == 'http') || $auth->check($value['name'], $admin_id)) {
                if (strtolower(substr($value['name'], 0, 4)) != 'http') {
                    if ($value['pid'] != 0) {
                        $value['href'] = url($value['name']);
                    }
                } else {
                    $value['href'] = $value['name'];
                }
                $menu[] = $value;
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];
        foreach ($menu as $key => $value) {
            $menu[$key]['children'] = bubbleSort($value['children']);
        }
        $this->assign('menu', json_encode($menu, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        $this->site_config = getConfig();
        $this->assign('site_config', $this->site_config);
    }
}