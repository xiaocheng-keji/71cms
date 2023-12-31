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

class Forumcate extends Model
{
    protected $insert = ['time'];

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
    	$tptc = $this->order('id ASC')->select();
    	
    	return $this->sort($tptc);
    }
    public function sort($data, $tid = 0, $level = 1)
    {
    	static $arr = array();
    
    	foreach ($data as $v) {
    		
    		if ($v['tid'] == $tid) {
    		
    		
    			$v['level'] = $level;
    			$arr[] = $v;
    			$this->sort($data, $v['id'], $level + 1);
    		}
    	}
    	
    	return $arr;
    }

    public function getchilrenid($cateid)
    {
    	$cates = $this->select();
    	return $this->_getchilrenid($cates, $cateid);
    }
    public function _getchilrenid($cates, $cateid)
    {
    	static $arr = array();
    	foreach ($cates as $k => $v) {
    		if ($v['tid'] == $cateid) {
    			$arr[] = $v['id'];
    			$this->_getchilrenid($cates, $v['id']);
    		}
    	}
    	return $arr;
    }


    /**
     * 自动生成时间
     * @return bool|string
     */
    protected function setTimeAttr()
    {
        return time();
    }

   
}