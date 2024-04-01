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

use think\Exception;
use think\Model;
use think\File;
use think\Db;
use app\common\model\File as FileModel;

class Upload extends Model
{

    function initialize()
    {
        parent::initialize();
    }

    /**
     * @param $type $type是数组的话是指定后缀
     * @param string $filename
     * @param bool $is_water
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upfile($type, $filename = 'file', $is_water = false)
    {
        try {
            $file = request()->file($filename);
        } catch (Exception $exception) {
            return array('code' => 0, 'msg' => lang($exception->getMessage()));
        }
        if (is_array($type)) {
            $ext = $type;
        } else {
            $exts = [
                'images' => 'jpg,jpeg,png,gif',
                'excel' => 'xls,xlsx',
                'files' => 'jpg,jpeg,png,gif,xls,xlsx,doc,docx,pdf,psd,mp4',
                'layedit'=>"jpg,jpeg,png,gif,jpg,jpeg,png,gif,xls,xlsx,doc,docx,pdf,psd,mp4"
            ];
            $ext = $exts[$type];
            if (!$ext) {
                return array('code' => 0, 'msg' => '设置的格式错误');
            }
        }
        // 验证格式
        $info = $file->validate(['size' => 5000000000, 'ext' => $ext])->check();
        if (!$info) {
            return array('code' => 0, 'msg' => $file->getError());
        }

        $filemode = new FileModel();
        if (empty($n)) {
            $info = $file->move(env('root_path') . DIRECTORY_SEPARATOR . 'public/uploads');
            if ($info) {
                $path = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $info->getSaveName();
                $path = str_replace("\\", "/", $path);
                // $realpath=__DIR__.$path;
                $realpath = SITE_URL . $path;
                $data['sha1'] = $info->sha1();
                $data['md5'] = $info->md5();
                $data['create_time'] = time();//;
                $data['location'] = 1;
                $data['ext'] = $info->getExtension();
                $data['size'] = $info->getSize();
                $data['savepath'] = $path;
                $data['savename'] = $info->getFilename();
                $data['download'] = 0;
                $fileinfo = $info->getInfo();
                $data['name'] = $fileinfo['name'];
                $data['mime'] = $fileinfo['type'];
                if ($filemode->save($data)) {
                    return array(
                        'code' => 200,
                        'msg' => '上传成功',
                        'id' => $filemode->id,
                        'path' => $path,
                        'headpath' => $realpath,
                        'savename' => $info->getSaveName(),
                        'filename' => $info->getFilename(),
                        'info' => $info->getInfo(),
                        'ext'=>$data['ext'],
                        'token' => file_token_url($data['savename']),
                    );
                } else {
                    return array('code' => 0, 'msg' => '上传失败');
                }
            } else {
                return array('code' => 0, 'msg' => $file->getError());
            }
        } else {
            $path = $n['savepath'];

            if(!file_exists('.'.$n['savepath'])){
                $info = $file->validate(['size' => 5000000000, 'ext' => $ext])->move(env('root_path') . DIRECTORY_SEPARATOR . 'public/uploads');
                $path = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $info->getSaveName();
                $path = str_replace("\\", "/", $path);
                // $realpath=__DIR__.$path;
                $realpath = SITE_URL . $path;
                $data['sha1'] = $info->sha1();
                $data['md5'] = $info->md5();
                $data['create_time'] = time();//;
                $data['location'] = 1;
                $data['ext'] = $info->getExtension();
                $data['size'] = $info->getSize();
                $data['savepath'] = $path;
                $data['savename'] = $info->getFilename();
                $data['download'] = 0;
                $fileinfo = $info->getInfo();
                $data['name'] = $fileinfo['name'];
                $data['mime'] = $fileinfo['type'];
                if ($n->update($data,['id'=>$n->id])) {
                    return array(
                        'code' => 200,
                        'msg' => '上传成功',
                        'id' => $n->id,
                        'path' => $path,
                        'headpath' => $realpath,
                        'savename' => $data['savename'],
                        'filename' => $data['name'],
                        'info' => $info->getInfo(),
                        'ext'=>$data['ext'],
                        'token' => file_token_url($data['savename']),
                    );
                } else {
                    return array('code' => 0, 'msg' => '上传失败');
                }
            }

            // $realpath=__DIR__.$path;
            $realpath = SITE_URL . $path;
            return array(
                'code' => 200,
                'msg' => '上传成功',
                'id' => $n['id'],
                'path' => $path,
                'headpath' => $realpath,
                'savename' => $n['savename'],
                'filename' => $n['name'],
                'info' => $n,
                'ext'=> $n['ext'],
                'token' => file_token_url($n['savename']),
            );
        }
    }


    public function chunkUpload($type, $filename = 'file', $is_water = false){
        //获取文件
        $file = $_FILES['file'];
        //获取文件名
        $name = input('name');

        //获取拓展名
        $ext = strtolower(trim(strrchr($name, '.'),'.'));
        $ext_arr = ['jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'doc', 'docx', 'pdf', 'psd', 'mp4'];
        if (!in_array($ext, $ext_arr)) {
            return array('code' => 0, 'msg' => '文件类型错误');
        }

        //临时文件名
        $tmp_name = md5(input('token')).'.'.$ext;
        //上传临时文件路径
        if(!is_dir('./uploads/temp')){
            mkdir('./uploads/temp',0777);
        }
        $tmp_path = './uploads/temp/'.$tmp_name;



        //没有错误
        if($file['error']==0){
            //文件不存在
            if(input('init')=='true'){
                if(!move_uploaded_file($file['tmp_name'],$tmp_path)){
                    echo 'failed';
                }
                if(input('lastest')=='true'){
                    $md5 = md5_file($tmp_path);

                    $newfilename = md5(microtime()).'.'.$ext;
                    $new_path = './uploads/'.date('Ymd').'/'.md5(microtime()).'.'.$ext;

                    if(!is_dir('./uploads/'.date('Ymd'))){
                        mkdir('./uploads/'.date('Ymd'),0777);
                    }

                    $filemode = new FileModel();

                    $n = $filemode->where('md5',$md5)->find();

                    if($n){
                        $new_path = '.'.$n->savepath;
                        rename($tmp_path,$new_path);
                    }else{
                        rename($tmp_path,$new_path);
                    }





                    $path = $new_path;
                    $path = str_replace("\\", "/", $path);
                    $realpath = SITE_URL . trim($path,'.');
                    $data['md5'] = $md5;
                    $data['create_time'] = time();//;
                    $data['location'] = 1;
                    $data['ext'] = $ext;
                    $data['size'] = filesize($new_path);
                    $data['savepath'] = trim($path,'.');
                    $data['savename'] = $newfilename;
                    $data['download'] = 0;
                    $data['name'] = $name;
                    $data['mime'] = mime_content_type($new_path);

                    if($n){
//                        @unlink('.'.$n->savepath);
                        $filemode->where('md5',$md5)->update($data);
                        return array('code' => 200, 'msg' => '上传成功', 'id' => $n['id'], 'path' => trim($path,'.'),'headpath' => $realpath,'ext'=>$ext);
                    }else{
                        $res = $filemode->create($data);
                        return array('code' => 200, 'msg' => '上传成功', 'id' => $res['id'], 'path' => trim($path,'.'),'headpath' => $realpath,'ext'=>$ext);
                    }
                }
            }else{//存在则追加写入
                $content=file_get_contents($file['tmp_name']);
                if (!file_put_contents($tmp_path, $content,FILE_APPEND)) {
                    echo 'failed';
                }
                if(input('lastest')=='true'){
                    $md5 = md5_file($tmp_path);

                    $newfilename = md5(microtime()).'.'.$ext;
                    $new_path = './uploads/'.date('Ymd').'/'.md5(microtime()).'.'.$ext;

                    if(!is_dir('./uploads/'.date('Ymd'))){
                        mkdir('./uploads/'.date('Ymd'),0777);
                    }

                    $filemode = new FileModel();

                    $n = $filemode->where('md5',$md5)->find();

                    if($n){
                        $new_path = '.'.$n->savepath;
                        rename($tmp_path,$new_path);
                    }else{
                        rename($tmp_path,$new_path);
                    }




                    $path = $new_path;
                    $path = str_replace("\\", "/", $path);
                    $realpath = SITE_URL . trim($path,'.');
                    $data['md5'] = $md5;
                    $data['create_time'] = time();//;
                    $data['location'] = 1;
                    $data['ext'] = $ext;
                    $data['size'] = filesize($new_path);
                    $data['savepath'] = trim($path,'.');
                    $data['savename'] = $newfilename;
                    $data['download'] = 0;
                    $data['name'] = $name;
                    $data['mime'] = mime_content_type($new_path);
                    $filemode = new FileModel();

//                    $n = $filemode->where('md5',$md5)->find();
                    if($n){
//                        @unlink('.'.$n->savepath);
                        $filemode->where('md5',$md5)->update($data);
                        return array('code' => 200, 'msg' => '上传成功', 'id' => $n['id'], 'path' => trim($path,'.'),'headpath' => $realpath,'ext'=>$ext);
                    }else{
                        $res = $filemode->create($data);
                        return array('code' => 200, 'msg' => '上传成功', 'id' => $res['id'], 'path' => trim($path,'.'),'headpath' => $realpath,'ext'=>$ext);
                    }
                }
            }
        }else{
            echo 'failed';
        }
    }

}