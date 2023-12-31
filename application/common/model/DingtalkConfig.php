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


class DingtalkConfig extends ModelBasic
{

    protected $insert = ['tenant_id' => TENANT_ID];

    public $autoWriteTimestamp = true;

    protected $type = [
        'config' => 'array',
    ];

    public static function getConf()
    {
        $config = DingtalkConfig::where('tenant_id', TENANT_ID)->find();
        if (!$config) {
            $config = DingtalkConfig::create([]);
        }
        return $config;
    }

    public static function getConfVal()
    {
        $config = self::getConf();
        return $config['config'];
    }
}