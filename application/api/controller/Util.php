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

use app\common\model\Upload;
use think\facade\Cache;

class Util extends Base
{
    public function editor(){
        $content = cache('content_'.$this->user_id);
        $this->assign('content',$content);
        $this->assign('token',$this->token);
        $this->assign('tenant_id',input('tenant_id'));
        return view();
    }

    public function up_image(){
        $upload = new Upload();
        $result = $upload->upfile('layedit', 'file', true);
        if ($result['code'] == 200) {
            $data = array('code' => 0, 'msg' => '上传成功', 'data' => array('id'=>$result['id'],'src' => $result['path'], 'title' => $result['info']['name']));
        } else {
            $data = array('code' => 1, 'msg' => $result['msg']);
        }
        return json($data);
    }

    public function saveRich(){
        Cache::set('content_'.$this->user_id,input('content'));
    }
}
