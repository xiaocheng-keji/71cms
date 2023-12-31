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

class Auth extends AdminBase
{

    public function index()
    {
        if ($this->request->isAjax()) {
            $params = [
                'company' => input('company'),
                'phone' => input('phone'),
                'auth_code' => input('auth_code'),
                'host' => $_SERVER['HTTP_HOST'],
            ];
            $res = http('http://server.71cms.net/api/auth/auth', $params, 'POST');
            $res = json_decode($res, true);
            if ($res['status'] == 1) {
                $this->success('授权成功');
            }
            $this->error($res['msg']);
        }

        $host = $_SERVER['HTTP_HOST'];
        $version = file_get_contents(\think\facade\App::getAppPath() . 'version.php');
        $res = http('http://server.71cms.net/api/auth/check?host=' . $host . '&version=' . $version);
        $res = json_decode($res, true);
        if ($res['status'] == 1) {
            $modelHelper = new ModelHelper();

            $modelHelper->addTips('<b>已授权</b><br>');
            $modelHelper->addTips('授权码：' . $res['result']['auth_code']);
            $modelHelper->submit_btn_text = '';
            return $modelHelper->showForm(false);
        }
        $modelHelper = new ModelHelper();
        $modelHelper->addTips('未授权');
        $modelHelper->addField('公司/单位名称', 'company', 'text', ['require' => '*']);
        $modelHelper->addField('手机号/电话', 'phone', 'text', ['require' => '*']);
        $modelHelper->addField('授权码', 'auth_code', 'text', ['require' => '*']);
        return $modelHelper->showForm(false);
    }
}