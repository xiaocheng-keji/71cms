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

use app\admin\controller\AdminBase;
use think\facade\App;

/**
 * 系统配置
 * Class System
 * 71CMS
 * @package app\admin\controller
 */
class System extends AdminBase
{
    /**
     * 清除缓存
     */
    function clear()
    {
        delete_dir_file(App::getRuntimePath() . 'cache');
        array_map('unlink', glob(App::getRuntimePath() . 'temp/*.php'));
        rmdir(App::getRuntimePath() . 'temp');
        return json(array('code' => 200, 'msg' => '更新缓存成功'));
    }
}
