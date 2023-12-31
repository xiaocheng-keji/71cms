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
namespace app\api\controller;

use GatewayClient\Gateway;
use think\Controller;

class Chat extends Controller
{
    public function bind_id(){
        Gateway::$registerAddress = config('web.WS_REGISTER');
        $user = db('users') -> where(['token'=>input('post.token')]) -> find();
        Gateway::bindUid(input('client_id'),$user['user_id']);
    }

    public function getList(){
        $my = db('users') -> where(['token'=>input('post.token')]) -> find();
        $to = db('users') -> where(['user_id'=>input('post.toid')]) -> find();
        $uid = $my['user_id'];
        $toid = input('post.toid');
        $res = db('chat') -> whereOr([[['uid','=',$uid],['toid','=',$toid]],[['uid','=',$toid],['toid','=',$uid]]]) -> order('time') -> select();
        foreach($res as $k=>$v){
            if($my['user_id']==$v['uid']){
                $res[$k]['is_mine'] = true;
                $res[$k]['head_pic'] = config('web.SITE').$my['head_pic'];
            }else{
                $res[$k]['is_mine'] = false;
                $res[$k]['head_pic'] = config('web.SITE').$to['head_pic'];
            }

        }
        jsonReturn(1,'',['data'=>$res,'myhead'=>config('web.SITE').$my['head_pic'],'tohead'=>config('web.SITE').$to['head_pic']]);
    }

    public function send(){
        Gateway::$registerAddress = config('web.WS_REGISTER');
        $data = [];
        $data['toid'] = input('post.id');
        $my = db('users') -> where(['token'=>input('post.token')]) -> find();
        $data['uid'] = $my['user_id'];
        $data['content'] = input('post.content');
        $data['time'] = date('Y-m-d H:i:s');
        $res = db('chat') -> data($data) -> insert();
//        Gateway::sendToAll(input('post.content'));
        Gateway::sendToUid($my['user_id'],json_encode(['content'=>input('post.content'),'uid'=>$data['uid'],'toid'=>$data['toid']]));
//        Gateway::sendToUid($data['toid'],input('post.content'));
        if($res){
            jsonReturn(1,'发送成功');
        }else{
            jsonReturn(0,'发送失败');
        }
    }
}
