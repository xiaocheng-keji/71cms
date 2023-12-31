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


class DevelopUserTasklist extends Base
{
    public  static  $SESSION_SAVE_DEVELOP_ID = "develop_user_id";
    public  static  $SEESION_SAVE_TASKLIST_ID = 'task_list_id';

    /**
     * 增加map
     */
    function  addDevelopNTasklistMap(){
        $develop_id = session(self::$SESSION_SAVE_DEVELOP_ID);
        if(null != $develop_id && ''!=$develop_id){
            $data = array();
            $data['develop_id'] = $develop_id;
            $data['tasklist_id'] = session(self::$SEESION_SAVE_TASKLIST_ID);
            $data['update_time'] = time();
           $resDep = self::where('develop_id',"=",$data['develop_id'])->find();
            if(empty($resDep)){
                self::insert($data);
                session(self::$SESSION_SAVE_DEVELOP_ID,'');
                session(self::$SEESION_SAVE_TASKLIST_ID,'');
            }else{
                //执行更新
                unset($data['develop_id']);
                self::save($data,['develop_id'=>$data['develop_id']]);
            }
        }
   }


   static  function  getDepTaskListMap($develop_id){
       return  self::where('develop_id',"=",$develop_id)->find();
   }

}