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

use think\Model;

class Menu extends Model
{
    // 无限级分类，仅供教学使用
    public static $treeListEasy = array();
    public static function treeEasy($data, $parentid = 0){
        foreach ($data as $key => $value) {
            if($value['parentid']==$parentid){
                self::$treeListEasy[] = $value;
                self::treeEasy($data, $value['id']);
            }
        }
        return self::$treeListEasy;
    }

   //  {
   //  //当前模型对应的数据表名
   //  protected $table = 'zhandao_menu';
   // }

    // 无限级分类，仅供教学使用
    public static $treeListTest = array();
    public static $pid = 0;
    public static $sid = 0;
    public static function treeTest( $data, $parentid = 0,$level = 1 ){
        foreach ($data as $key => $value) {
            if($value['parentid']==$parentid){
                if($level == 1){
                    self::$pid = $value['id'];
                    self::$treeListTest[self::$pid]=$value;
                }else if($level == 2){
                    self::$sid = $value['id'];
                    self::$treeListTest[self::$pid]['son'][self::$sid]=$value;
                }else if($level == 3){
                    self::$treeListTest[self::$pid]['son'][self::$sid]['son'][]=$value;
                }
                if($value['child']){
                    self::treeTest($data,$value['id'],$level+1);
                }
            }
        }
        return self::$treeListTest;
    }

    // 无限级分类，开发使用
    public static function tree($data){
        $tree = [];

        //第1步，将所有菜单项的ID作为数组下标
        foreach ($data as $value) {
            $tree[$value['id']] = $value;
        }

        //利用引用的方式，将每个子菜单项添加到父类son的数组中，只需遍历一次
        foreach ($tree as $key => $value)
            $tree[$value['parentid']]['son'][] = &$tree[$key];
        $tree = isset($tree[0]['son']) ? $tree[0]['son'] :array();

        //原第3步，清除重复数据，已经不需要使用
        // foreach ($tree as $key => $value) {
        //     if($value['parentid'] !=0 ){
        //         unset($tree[$key]);
        //     }
        // }

        return $tree;
    }

    

}
