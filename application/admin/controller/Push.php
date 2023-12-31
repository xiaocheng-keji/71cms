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

use app\common\model\Push as PushModel;
/**
 * 推送管理
 * Class Push
 * @package app\admin\controller
 */
class Push extends AdminBase
{
    public function index(){
        if($this->request->isPost()){
            $data = $this->request->post();
            $pushmodel = new PushModel();
            $res = $pushmodel->setInfo($data);
            if($res){
                return json(['code'=>200,'msg'=>'操作成功']);
            }else{
                return json(['code'=>0,'msg'=>'操作失败']);
            }
        }else{
            $push = PushModel::find();
            $this->assign('res',$push);
            return view();
        }
    }
}
