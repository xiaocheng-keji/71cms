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

use app\common\model\TmpSession;
use think\Db;

/**
 * 修改密码
 * Class ChangePassword
 * @package app\admin\controller
 */
class ChangePassword extends AdminBase
{
    /**
     * 修改密码
     * @return mixed
     */
    public function index()
    {
        return $this->fetch('system/change_password');
    }

    public function diachange_password()
    {
        return $this->fetch('system/diachange_password');
    }

    /**
     * 更新密码
     */
    public function updatePassword()
    {
        if ($this->request->isPost()) {
            if (TmpSession::getLoginType() == 'admin_user') {
                $admin_id = \session('admin_id');
                $data = $this->request->param();
                $result = Db::name('admin_user')->find($admin_id);

                if ($result['password'] == md5($data['old_password'] . $result['salt'])) {
                    if ($data['password'] == $data['confirm_password']) {
                        $new_password = md5($data['password'] . $result['salt']);
                        $res = Db::name('admin_user')->where(['id' => $admin_id])->setField('password', $new_password);

                        if ($res !== false) {
                            return json(array('code' => 200, 'msg' => '修改成功'));
                        } else {
                            return json(array('code' => 0, 'msg' => '修改失败'));
                        }
                    } else {
                        return json(array('code' => 0, 'msg' => '两次密码输入不一致'));
                    }
                } else {
                    return json(array('code' => 0, 'msg' => '原密码不正确'));
                }
            } elseif (TmpSession::getLoginType() == 'user') {
                //组织管理员修改密码
                $post = $this->request->post();

                //验证修改密码数据
                $validate = new \app\admin\validate\User();
                $validate_result = $validate->scene('ChangePassword')->check($post);
                if ($validate_result !== true) {
                    $this->error($validate->getError());
                }
                $user_id = TmpSession::getLoginId();
                $user = Db::name('user')->find($user_id);
                if ($user['password'] !== encrypt($post['old_password'])) {
                    $this->error('原密码错误！');
                }

                $UserModel = new \app\common\model\User();
                $res = $UserModel->allowField(['password'])
                    ->save(['password' => $post['password']], ['id' => $user_id]);

                if ($res !== false) {
                    TmpSession::clearMustChangePassword();
                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
        }
    }
}