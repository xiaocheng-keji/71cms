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

use think\facade\Cache;
use think\Model;

class Video extends Base
{
    protected $append = [
        'vimg_all','src_all'
    ];


    //
    public function getVimgAllAttr($value,$data){
        if(stripos($data['vimg'],'://')!==false){
            return $data['vimg'];
        }else{
            if(stripos($data['vimg'],'uploads/')!==false){
                return SITE_URL.$data['vimg'];
            }else{
                return getConfig()['qiniu_url'].$data['vimg'];
            }
        }
    }

    public function getSrcAllAttr($value,$data){
        if(stripos($data['src'],'://')!==false){
            return $data['src'];
        }else{
            if(stripos($data['src'],'uploads/')!==false){
                return SITE_URL.$data['src'];
            }else{
                return getConfig()['qiniu_url'].$data['src'];
            }
        }
    }

    public function videoTypeCid(){
        return $this->belongsTo('VideoType','cid','id');
    }
}
