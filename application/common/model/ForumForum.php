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

use think\Db;
use think\Model;

class ForumForum extends Model
{

    protected $insert = ['add_time'];

    // 定义全局的查询范围
    protected function base($query)
    {
    }

	function add($data)
    {
    	$result = $this->isUpdate(false)->allowField(true)->save($data);
    	if ($result) {
    		return true;
    	} else {
    		return false;
    	}
    }

    function edit($data)
    {
    	$result = $this->isUpdate(true)->allowField(true)->save($data);
    	if ($result) {
    		return true;
    	} else {
    		return false;
    	}
    }
	
	public function catetree()
    {
    	$tptc = $this->order('forum_id ASC')->select();
    	
    	return $this->sort($tptc);
    }
    
    public function sort($data, $tid = 0, $level = 1)
    {
    	static $arr = array();
    
    	foreach ($data as $v) {
    		
    		if ($v['parent_id'] == $tid) {
    		
    		
    			$v['level'] = $level;
    			$arr[] = $v;
    			$this->sort($data, $v['forum_id'], $level + 1);
    		}
    	}
    	
    	return $arr;
    }

    /**
     * 自动生成时间
     * @return bool|string
     */
    protected function setAddTimeAttr()
    {
        return time();
    }
}