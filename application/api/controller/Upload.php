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
use app\common\model\Upload as UploadModel;
use think\Controller;
use think\Exception;

/**
 * 71CMS创先云智慧党建
 * https://www.71cms.net/
 * @package app\admin\controller
 */
class Upload extends Base
{
    function initialize()
    {
        parent::initialize();
        $this->model =new UploadModel();
    }
    public function upimage()
    {
   // return 	$this->model->upfile('images');
        try {
            return json($this->model->upfile('images'));
        }catch (Exception $e){
            return json(['msg'=>$e->getMessage()]);
        }

    }
    public function upfile()
    {
        return json($this->model->upfile('files'));
    }
    public function upchunk(){
        try{
            return json($this->model->chunkUpload('files'));
        }catch (Exception $e){
//             throw new Exception('dinsdfladskfjads kl');
            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }

    }
    public function upExcel()
    {
        return json($this->model->upfile('excel'));
    }
    public function layedit_upimage()
    {
        $result = $this->model->upfile('layedit', 'file', true);
        if ($result['code'] == 200) {
            $data = array('code' => 0, 'msg' => '上传成功', 'data' => array('id'=>$result['id'],'src' => $result['path'], 'title' => $result['info']['name']));
        } else {
            $data = array('code' => 1, 'msg' => $result['msg']);
        }
        return json($data);
    }
    public function umeditor_upimage()
    {
        $result = $this->model->upfile('umeditor', 'upfile', true);
        if ($result['code'] == 200) {
            $data = array("originalName" => $result['info']['name'], "name" => $result['savename'], "url" => $result['path'], "size" => $result['info']['size'], "type" => $result['info']['type'], "state" => "SUCCESS");
        } else {
            $data = array("originalName" => $result['info']['name'], "name" => $result['savename'], "url" => $result['path'], "size" => $result['info']['size'], "type" => $result['info']['type'], "state" => $result['msg']);
        }
        echo json_encode($data);
        exit;
    }
}