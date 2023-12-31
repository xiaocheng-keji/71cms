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


use think\facade\Cache;
use think\facade\Config;

class Dingtalk
{
    /**
     * 获取钉钉的access_token
     *
     * @return mixed|\SimpleXMLElement
     */
    public static function getAccessToken()
    {
        $cache_name = 'dingtalk_access_token' . TENANT_ID;
        $access_token = Cache::get($cache_name);
        if (!$access_token) {
            $config = DingtalkConfig::getConfVal();
            $c = new \DingTalkClient(\DingTalkConstant::$CALL_TYPE_OAPI, \DingTalkConstant::$METHOD_GET, \DingTalkConstant::$FORMAT_JSON);
            $req = new \OapiGettokenRequest;
            $req->setAppkey($config['appkey']);
            $req->setAppsecret($config['appsecret']);
            $resp = $c->execute($req, '', "https://oapi.dingtalk.com/gettoken");
            $access_token = $resp->access_token;
            Cache::set($cache_name, $access_token, 7000);
        }
        return $access_token;
    }

    /**
     * 从缓存删除钉钉的access_token
     * @return bool
     */
    public static function rmAccessToken()
    {
        $cache_name = 'dingtalk_access_token' . TENANT_ID;
        return Cache::rm($cache_name);
    }
}