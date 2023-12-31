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

namespace app\common\model;


use app\common\model\AuthGroup as AuthGroupModel;
use app\common\model\AuthGroupAccess as AuthGroupAccessModel;

//这个是前台用户组织管理员登录后台的权限组关联
use app\common\model\AuthGroupAccessDep as AuthGroupAccessDepModel;
use app\common\model\Department as DepModel;
use think\Db;

/**
 * 登录后的会话管理器
 *
 * Class TmpSession
 * @package app\common\model
 */
class TmpSession
{
    /**
     * 设置登录用户的信息
     * @param string $login_type
     * @param array $login_user
     */
    public static function setLoginUser(string $login_type, array $login_user): void
    {
        session('is_login', true);
        session('login_type', $login_type);//记录登录类型，判断权限的时候需要
        session('login_user', $login_user);
        session('tenant_id', $login_user['tenant_id']);
        //判断是否超级管理员，是否有超级权限组
        $group = [];
        $is_super = false;
        if ($login_type == 'admin_user') {
            if (isset($login_user['is_super']) && $login_user['is_super'] == 1) {
                $is_super = true;
            } else {
                //查他的权限组是否有超级权限组
                $group = AuthGroupAccessModel::where('uid', self::getLoginId())->column('group_id');
            }
        } else {
            $group = AuthGroupAccessDep::where('uid', self::getLoginId())->column('group_id');
            $super_id = AuthGroup::where(['is_super' => 1, 'tenant_id' => TENANT_ID])->value('id');
            if (in_array($super_id, $group)) {
                $is_super = true;
            }
        }
        self::upDep();
        if ($is_super != true && !empty($group)) {
            $is_super = (bool)AuthGroupModel::where('id', 'in', $group)
                ->where('tenant_id', TENANT_ID)
                ->where('status', 1)
                ->where('is_super', 1)
                ->find();
        }
        session('is_super', $is_super);
        session('login_time', time());
        if ($login_type == 'admin_user') {
            session('login_name', $login_user['username']);
        } else {
            session('login_name', $login_user['nickname']);
        }
        //更新组织权限
        self::upDepAuth();
    }

    /**
     * 获取租户id
     * @return mixed
     */
    public static function getTenantId()
    {
        return session('tenant_id');
    }

    /**
     * 是否超级管理员
     * @return bool
     */
    public static function isSuper(): bool
    {
        return (bool)session('is_super');
    }

    /**
     * 是否已登录
     * @return bool
     */
    public static function isLogin(): bool
    {
        return (bool)session('is_login');
    }

    /**
     * 获取登录类型
     * @return string
     */
    public static function getLoginType(): string
    {
        return (string)session('login_type');
    }

    /**
     * 获取登录用户名
     * @return string
     */
    public static function getLoginName(): string
    {
        return (string)session('login_name');
    }

    /**
     * 获取允许操作组织
     * @return array
     */
    public static function getDepAuth(): array
    {
        return (array)session('_dep_auth');
    }

    /**
     * 设置允许操作组织
     * @param array $dep_auth
     */
    public static function setDepAuth(array $dep_auth): void
    {
        session('_dep_auth', $dep_auth);
    }

    /**
     * 重新获取组织权限
     */
    public static function upDepAuth()
    {
        if (self::getLoginType() == 'admin_user') {
            //后台管理员可以查看所有
            $auth_list = DepModel::column('id');
            self::setDepAuth($auth_list);
        } elseif (self::getLoginType() == 'user') {
            $dep_admin = AuthGroupAccessDepModel::where('uid', self::getLoginId())->column('dep_id');
            //获取这个组织的所有下级组织
            $auth = DepModel::getSubDep($dep_admin);
            self::setDepAuth($auth);
        }
    }

    /**
     * @return mixed
     * 获取后台组织管理员设置的组织（不包含下级）
     */
    public static function getCurrentDep()
    {
        if (self::getLoginType() == 'admin_user') {
            return DepModel::cache(60)->column('id');
        } elseif (self::getLoginType() == 'user') {
            return AuthGroupAccessDepModel::where('uid', self::getLoginId())->cache(60)->column('dep_id');
        }
    }

    /**
     * 设置组织
     */
    public static function upDep()
    {
        $dep = UserDep::where('user_id', self::getLoginId())->column('dep_id');
        session('dep', $dep);
    }

    /**
     * 获取组织
     * @return array
     */
    public static function getDep()
    {
        self::upDep();
        return (array)session('dep');
    }

    /**
     * 获取登录者id
     * @return int
     */
    public static function getLoginId(): int
    {
        $user = self::getLoginUser();
        return (int)$user['id'];
    }


    /**
     * 获取登录用户
     * @return array
     */
    public static function getLoginUser(): array
    {
        return (array)session('login_user');
    }

    /**
     * 获取可用权限组
     * @return array
     */
    public static function getAllowGroup(): array
    {
        if (self::isSuper()) {
            $group = AuthGroupModel::where('status', 1)
                ->where('tenant_id', self::getTenantId())
                ->column('id');
        } else {
            //根据登录类型的不同 不一样的可选权限组
            if (self::getLoginType() == 'admin_user') {
                $group = AuthGroupAccessModel::where('uid', self::getLoginId())->column('group_id');
            } else {
                $group = AuthGroupAccessDepModel::where('uid', self::getLoginId())->column('group_id');
            }
        }
        return $group;
    }

    /**
     * 获取登录时间
     * @return mixed
     */
    public static function getLoginTime()
    {
        return session('login_time');
    }

    /**
     * 退出登录
     */
    public static function logout()
    {
        session(null);
    }

    /**
     * 设置必须修改密码
     */
    public static function setMustChangePassword()
    {
        session('mustChangePassword', true);
    }

    /**
     * 清除必须修改密码
     */
    public static function clearMustChangePassword()
    {
        session('mustChangePassword', null);
    }

    /**
     * 判断是否需要修改密码
     * @return bool
     */
    public static function mustChangePassword(): bool
    {
        return boolval(session('mustChangePassword'));
    }

    /**
     * 保存权限数组id到会话
     * @param $auth_rules
     */
    public static function setAuthRules($auth_rules)
    {
        session(self::getLoginId() . 'auth_rules', $auth_rules);
    }

    /**
     * 获取权限id数组
     * @return array
     */
    public static function getAuthRules(): array
    {
        return (array)session(self::getLoginId() . 'auth_rules');
    }

    /**
     * 清除权限id数组
     */
    public static function clearAuthRules()
    {
        session(self::getLoginId() . 'auth_rules', null);
    }

    /**
     * 设置登录方式 weixin内打开 dingtalk内打开
     *
     * @param $platform
     */
    public static function setPlatform($platform)
    {
        session('platform', $platform);
    }

    /**
     * 获取登录方式
     *
     * @return mixed
     */
    public static function getPlatform()
    {
        return session('platform');
    }
}