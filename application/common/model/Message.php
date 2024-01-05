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

class Message extends Model
{
    protected $insert = ['time'];

    // 定义全局的查询范围
    protected function base($query)
    {
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id')
            ->field(['id', 'nickname', 'head_pic'])
            ->bind(['nickname', 'head_pic']);
    }

	/**
	 * 创建时间
	 * @return bool|string
	*/
	protected function setTimeAttr()
	{
		return time();
	}

    /**
     * 发送消息
     * @param $uid
     * @param $touid
     * @param $content
     * @param $type
     * @param $other_id
     * @return bool
     */
    public static function sendMessage($uid = 0,$touid,$content,$type,$other_id){
        // type 11书记信息
        // type 12任务清单
        $data = [
//            'uid'=>$uid,
//            'touid'=>$touid,
            'uid'=>$touid,
            'touid'=>$uid,
            'content'=>$content,
            'type'=>$type,
            'other_id'=>$other_id,
            'time'=>time(),
            'status'=>1
        ];
        return self::insert($data);
    }
}