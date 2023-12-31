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
namespace app\api\model;

use think\Model;

class ForumPost extends Model
{
    //一对一关联方法
	public function postUser () {
        return $this->hasMany('user', 'id', 'user_id');
	}

	public function postPlate () {
        return $this->hasMany('forum_forum', 'forum_id', 'forum_id');
	}
}