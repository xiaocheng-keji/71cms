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

use think\Model;

class File extends Model
{
    /**
     * 根据附件地址 返回附件信息
     * @param $list
     * @return array
     */
    public static function getFileList($list, $field = '*', $site_url = '')
    {
        //读取附件信息
        $attachment_list = self::field($field)->whereIn('savepath', json_decode($list))->select()->toArray();
//        $attachmentList = $attachment_list ? $attachment_list->toArray() : [];
        //按上传的顺序排序
        $attachment_array = [];
        foreach ($attachment_list as $k => $v) {
            $key = array_search($v['savepath'], json_decode($list));
            if ($key !== false) {
//                $v['savepath'] = $site_url . $v['savepath'];
                $v['url'] = complete_url($v['savepath']);
                $attachment_array[$key] = $v;
            }
        }
        ksort($attachment_array);
        return $attachment_array;
    }
}