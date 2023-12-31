<?php


namespace app\admin\controller;

use think\Db;
use think\facade\Cache;

class File extends AdminBase
{
    public function download($file)
    {
        $file_name = Cache::get($file, '');
        if (!$file) {
            $this->error('没有此文件！');
        }
        $file = Db::name('file')
            ->where('savename', $file_name)
            ->find();
        if (!$file) {
            $this->error('没有此文件');
        }
        if ($file['location'] == 2) {
            $this->redirect($file['savepath']);
        } else {
            $filePath = $this->app->getRootPath() . 'public' . $file['savepath'];
        }

        $readBuffer = 8192;//分段下载 每次下载的字节数
        //设置头信息
        //声明浏览器输出的是字节流
        header('Content-Type: application/octet-stream');
        //声明浏览器返回大小是按字节进行计算
        header('Accept-Ranges:bytes');
        //告诉浏览器文件的总大小
//        $fileSize = filesize($filePath);//坑 filesize 如果超过2G 低版本php会返回负数
        header('Content-Length:' . $file['size']); //注意是'Content-Length:' 非Accept-Length
        //声明下载文件的名称
        header('Content-Disposition:attachment;filename=' . $file['name']);//声明作为附件处理和下载后文件的名称
        //获取文件内容
        $handle = fopen($filePath, 'rb');//二进制文件用‘rb’模式读取
        while (!feof($handle)) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
            echo fread($handle, $readBuffer);
        }
        fclose($handle);//关闭文件句柄
        Db::name('file')->where('id', $file['id'])->setInc('download');
        exit;
    }
}