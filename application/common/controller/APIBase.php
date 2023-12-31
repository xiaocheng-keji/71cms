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
namespace app\common\controller;

use think\Controller;

/**
 * 接口统一集成类
 */
class APIBase extends  Controller
{
	public $user = array();
	public $user_id = 0;
	public $token = '';

    protected function initialize()
    {
        parent::initialize();
    }

    public function __construct () {
    	parent::__construct();
        $this->checkAuth();
    }

    public function checkAuth () {
    	$this->token = input('token', '');
    	
    	$module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();

        // 排除权限
    	$not_check = ['api/user/login', 'api/user/getDepartmentList', 'api/user/regInfo', 'api/user/reg'];
    	if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {

    		if (empty($this->token)) {
                // exit(json(['status'=>-100, 'msg'=>'没有登录']));
    		    return json(['status'=>-100, 'msg'=>'没有登录']);
    		    die;
            }
    		$this->user = db('user')->where('token', $this->token)->find();
    		if (empty($this->user)) {
    			return json(['status'=>-101, 'msg'=>'没有登录']);
    		}
    		// 冻结
            if ($this->user['is_lock']) {
    			return json(['status'=>-103, 'msg'=>'账号已被冻结']);
            }
            // 未审核
            if ($this->user['status'] == 0 || $this->user['status'] == 2) {
    			return json(['status'=>-103, 'msg'=>'账号未审核']);
            }
    		$this->user_id = $this->user['id'];
            if (time() - $this->user['last_login_time'] > 7200000) {
            	$update['token'] = md5(time() . mt_rand(10000, 99999) . $this->user_id);
                header('changeToken:'.$update['token']);
            }
            $update['last_login_time'] = time();
            db('user')->where('id', $this->user_id)->update($update);
    	}
    }

}