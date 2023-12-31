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
namespace app\common\controller;

use think\Db;
use think\File;
use think\Request;
use think\Controller;


class Upload extends Controller
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 上传文件
     * @param  string $path      文件路径
     * @param  array  $config    验证规则数组
     * @param  string $paramName 参数名称
     * @param  int $isThumb 是否开启缩略图 1为开启 0为不开启
     * @param  int $width 缩略图宽度
     * @param  int $height 缩略图高度
     * @param  string $file_thumb_path 缩略图文件路径
     * @return [type]            [description]
     */
    public function uploadFiles ($path, $config = array(), $paramName = "file", $isThumb = 1, $width = 300 , $height = 300, $file_thumb_path = '') {
		if (empty($path)) return ['status'=>0, 'msg'=>'缺少参数'];
		$file = request()->file($paramName);
		$info = $file->validate($config)->move(env('root_path').'public'.$path);
		if ($info) {
			$result['path'] = $path.'/'.str_replace('\\', '/', $info->getSaveName());
			$result['type'] = $info->getExtension();
			$result['name'] = $info->getFilename();
			if (empty($file_thumb_path)) {
				$file_thumb_path = str_replace('forum_post', 'forum_post/thumb', $result['path']);
			} else {
				$file_thumb_path = $file_thumb_path.str_replace('\\', '/', $info->getSaveName());
			}
        	if ($isThumb == 1) {
	        	// TODO $file_thumb_path变量 路径有问题非包含forum_post的路径都报错 
	        	// $this->file_upload_thumb($result['path'], $file_thumb_path);
        	}
			return array('status'=>1,'msg'=>'上传成功', 'result'=>$result);
		} else {
			return array('status'=>0,'msg'=>$file->error());
		}
    }

    /**
     * 上传图片，信息添加到数据库，返回路径和对应ID
     * @param string $path     上传路径，/public/uploads/$path
     * @param array $config     验证规则
     */
    public function upload_pic($path,$config=[],$uid=""){
        $files = request()->file();
//        $path = [];
        if($files){
            foreach($files as $k=>$file){
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->validate($config)->move('./uploads'.$path);
                if($info){
                    $file_path = trim($info->getPathName(),'.');
                    $file_path=str_replace("\\","/",$file_path);
                    $ext = $info->getExtension();
                    $time = date('Y-m-d H:i:s');
                    $id = Db::name('upload_pic') -> insertGetId([
                            'uid'=>session('user.id')?session('user.id'):$uid,
                            'path'=>$file_path,
                            'ext'=>$ext,
                            'add_time'=>$time
                        ]);

                    jsonReturn(1,'上传成功',['file_id'=>$id,'path'=>$file_path,'path2'=>SITE_URL.$file_path]);
                }else{
                    jsonReturn('0',$file->getError());
                }
            }
        }else{
            jsonReturn(0,'无效图片');
        }
    }

    /**
     * 删除数据库和文件
     * @param int $pic      图片对应id
     * @param array $where    upload_pic数据库中的的条件，格式为关联数组,不能有'id'字段
     * $param $is_del   是否删除文件
     */
    public function delete_pic($pic,$where=[],$is_del=true){
        $map = ['id'=>$pic];
        if($where){
            $map += $where;
        }
        $path = Db::name('upload_pic') -> where($map) -> value('path');
        $res = Db::name('upload_pic') -> where($map) -> delete();
        if($res){
            if($is_del){
                unlink('.'.$path);
            }
            jsonReturn(1,'成功删除');
        }else{
            jsonReturn(0,'操作失败');
        }
    }

    /**
	 * 图片缩率图处理
	 * file_path：文件路径
	 * path：文件保存目录 : 需事先手动创建
	 * width :目标宽度
	 * height:目标高
	 */
	public function file_upload_thumb($file_path, $path, $width = 300, $height = 300){
		$fiel_urls = env('root_path')."/public".$file_path;
		$paths = rtrim(env('root_path'), '\\')."/public".$path;
		$path_arr = explode('/', $paths);
		array_pop($path_arr);
		$save_path = implode('/', $path_arr);
		if (!is_dir($save_path)) mkdir($save_path, 0777, true);
		$image = \think\Image::open($fiel_urls);
		$image->thumb($width, $height)->save($paths);
		if (!file_exists($paths)) {
			$this->file_upload_thumb($file_path, $path, $width, $height);
		}
	}
}