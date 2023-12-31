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

use think\Controller;
use think\Db;
use app\common\model\TmpSession;


/**
 * 后台登录
 * Class Login
 * 71CMS
 * @package app\admin\controller
 */
class Login extends Controller
{

    // 是否开启验证码验证
    protected $captcha = true;

    public function captcha()
    {
        $m = new \think\captcha\Captcha([
            // 验证码字体大小
            'fontSize' => 36,
            // 验证码位数
            'length' => 4,
            // 关闭验证码杂点
            'useNoise' => true,
            'useCurve' => false,
            // 验证码图片高度
            'imageH' => 70,
            // 验证码图片宽度
            'imageW' => 0,
            'codeSet' => '1234567890',
        ]);
        $img = $m->entry();
        return $img;
    }

    /**
     * 后台登录
     *
     * @return mixed
     */
    public function index()
    {
        $this->assign('yzm', $this->captcha);
        return $this->fetch();
    }

    /**
     * 登录验证
     *
     * @return string
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['username', 'password', 'verify']);
            if ($this->captcha) {
                if (!captcha_check($data['verify'])) {
                    return json(array('code' => 0, 'msg' => '验证码错误'));
                }
            }
            $where['username'] = $data['username'];
            $admin_user = Db::name('admin_user')
                ->field('id,username,password,status,salt,is_super')
                ->where($where)
                ->find();
            if (!empty($admin_user)) {
                $password = md5($data['password'] . $admin_user['salt']);
                if ($password == $admin_user['password']) {
                    if ($admin_user['status'] != 1) {
                        $this->error('当前用户已禁用');
                    } else {
                        //超级管理员可以查看所有
                        $auth = [];
                        $auth_list = Db::name('department')->column('id');
                        foreach ($auth_list as $k => $v) {
                            $auth[$v] = 1;
                        }
                        session('dep_auth', $auth);
                        session('admin_id', $admin_user['id']);
                        session('is_super', $admin_user['is_super']);
                        session('admin_tenant_id', 1);
                        session('admin_name', $admin_user['username']);
                        session('admin_user', $admin_user);
                        session('login_type', 'admin_user');//记录登录类型，判断权限的时候需要
                        TmpSession::setLoginUser('admin_user', $admin_user);
                        Db::name('admin_user')->update(
                            [
                                'last_login_time' => time(),
                                'last_login_ip' => $this->request->ip(),
                                'id' => $admin_user['id']
                            ]
                        );
                        $this->success('登录成功');
                    }
                } else {
                    $this->error('密码错误');
                }
            } else {
                //尝试使用前台的账号登录
                $user = Db::name('user')->where('mobile', $data['username'])->find();
                if (!$user) {
                    $this->error('该账号不存在');
                }
                //判断密码
                if ($user['password'] == encrypt($data['password'])) {
                    if ($user['status'] <= 0) {
                        $this->error('账号已失效，请联系管理员');
                    }
                    $dep_admin = \app\common\model\AuthGroupAccessDep::where('uid', $user['id'])->column('dep_id');
                    if (!$dep_admin) {
                        $this->error('不是管理员无法登录，请先设置为支部管理员');
                    }
                    session('login_type', 'user');//记录登录类型，判断权限的时候需要
                    session('user', $user);
                    session('user_id', $user['id']);
                    //获取这个组织的所有下级组织
                    $auth = \app\common\model\Department::getSubDep($dep_admin);
                    $dep_auth = [];
                    foreach ($auth as $k => $v) {
                        $dep_auth[$v] = 1;
                    }
                    session('dep_auth', $dep_auth);
                    session('admin_tenant_id', 1);
                    session('admin_name', $user['nickname']);
                    TmpSession::setLoginUser('user', $user);

                    $this->success('登录成功');
                } else {
                    $this->error('密码错误');
                }
            }
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        TmpSession::logout();
        return json(array('code' => 200, 'msg' => '退出成功'));
    }
}
