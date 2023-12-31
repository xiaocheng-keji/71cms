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

use think\Controller;

/*接口统一集成类*/
class Base extends  Controller
{
	public $user = array();
	public $user_id = 0;
	public $token = '';
	public $tenant;

	private $not_check = [
        'api/user/login',
        'api/user/getdepartmentlist',
        'api/user/reginfo',
        'api/user/reg',
        'api/javauser/login',
        'api/javauser/reg',
        'api/meeting/getmeetingcate',
        'api/util/up_image',
        'api/index/getnewslist',
        'api/index/test',
        'api/index/get_mp_config',
        'api/user/getversion'
    ];

	private $head_tenant = [
	    'meeting/qrcode',
        'supervise/uploadui',
        'util/editor',
        'util/saveRich',
        'util/up_image'
    ];

    protected function initialize()
    {
        parent::initialize();
        return $this->checkAuth();
    }

    public function __construct () {
    	parent::__construct();
    }

    public function checkAuth () {
    	$this->token = input('token', '');

        $module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();
    	$url = strtolower($module . '/' . $controller . '/' . $action);
        define('TENANT_ID', $this->tenant['id']);
        define('TENANT_URL_ID', $this->tenant['tenant_id']);

        // 排除权限
    	if (!in_array(strtolower($module . '/' . $controller . '/' . $action), $this->not_check)) {

    		if (empty($this->token)) {
    		    echo json_encode(['status'=>-100, 'msg'=>'没有登录']);die;
            }
    		$this->user = \app\api\model\User::where('token', $this->token)->find();
    		if (empty($this->user)) {
    			echo json_encode(['status'=>-101, 'msg'=>'没有登录']);die;
    		}
    		// 冻结
            if ($this->user['is_lock']) {
    			echo json_encode(['status'=>-103, 'msg'=>'账号已被冻结']);die;
            }
            // 未审核
            if ($this->user['status'] == 0 || $this->user['status'] == 2) {
    			echo json_encode(['status'=>-103, 'msg'=>'账号未审核']);die;
            }
    		$this->user_id = $this->user['id'];
    	}
    }

}