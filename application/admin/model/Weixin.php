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

namespace app\admin\model;

use EasyWeChat\Factory;

use think\Db;
use think\Model;

class Weixin extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 获取当前微信公众号实例
     *
     * @param array $weixin
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public static function app()
    {
        $wx = Db::name('wxconfig')->find();
        if(($wx['app_id'] && $wx['app_secret']) === false){
            return null;
        }
        $config = [
            'app_id' => $wx['app_id'],
            'secret' => $wx['app_secret'],
            'token' => 'token',
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/wechat.log',
            ],
            'oauth' => [
                'scopes' => ['snsapi_userinfo'],
                'callback' => '/wap/weixin/getUserSuccess',
            ],
        ];
        return Factory::officialAccount($config);
    }
}