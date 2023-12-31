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

use app\wap\model\File as FileModel;
use app\common\model\NoticeType;
use app\api\model\Notice as NoticeModel;
use app\api\model\NoticeRead;

class Notice extends Base
{

    /**
     * 公告类型列表
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTypeList()
    {
        $notice[] = [
            'id' => 0,
            'name' => '全部',
        ];
        $notice2 = NoticeType::field('id,name')
            ->order('sort')
            ->select()
            ->toArray();
        $notice = array_merge($notice, $notice2);

        jsonReturn(1, '公告类型列表', $notice);
    }

    /**
     * 根据类型获取公告列表
     *
     * @param int $type
     * @param $page
     * @param int $size
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($type = 0, $page, $size = 10)
    {
        $where = [];
        if ($type != 0) {
            $where[] = ['type', '=', $type];
        }
        $noticeAll = $noticeAll = db('notice')
            ->alias('a')
            ->field('a.id,a.title,a.time,a.author,a.type,a.pic,a.top,c.status')
            ->join('xc_user_dep b', 'find_in_set(b.dep_id,a.group) or find_in_set(' . $this->user_id . ',a.user)')
            ->join('xc_notice_read c','c.nid=a.id')
            ->where(['a.tenant_id' => TENANT_ID, 'b.user_id' => $this->user_id])
            ->where($where)
            ->where(['c.uid'=>$this->user_id])
            ->group('a.id')
            ->order('a.top desc,a.time desc')
            ->page($page, $size)
            ->select();

        foreach ($noticeAll as &$item) {
            $item['notice_type'] = NoticeType::field('name,id')->where(['id' => $item['type']])->find();
            $file_pic = FileModel::field('savepath')->where(['id' => $item['pic']])->find();
            if ($file_pic['savepath'] && strtolower(substr($file_pic['savepath'], 0, 4)) != 'http') {
                $file_pic['savepath'] = SITE_URL . $file_pic['savepath'];
                $item['file_pic'] = $file_pic;
            }
        }
        jsonReturn(1, '获取成功', $noticeAll);
    }

    /**
     * 通知详情
     *
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $notice = NoticeModel::get($id);
        $attachmentList = FileModel::whereIn('id', $notice->attachment)->select();

        $noticeRead = new NoticeRead();
        $exist = $noticeRead::where(['nid' => $id, 'status' => 0, 'uid' => $this->user_id])->find();
        if ($exist) {
            $noticeRead::update(['status' => 1, 'read_time' => date('Y-m-d H:i:s')], ['id' => $exist->id]);

            NoticeModel::update(['read2' => $notice->read2 + 1], ['id' => $id]);

            $notice2 = NoticeModel::get($id);
            if ($notice2->read2 >= $notice2->read) {
                NoticeModel::update(['status' => 1], ['id' => $id]);
            }
        }

        $read = NoticeRead::where(['nid' => $id, 'status' => 1])->count();
        $unread = NoticeRead::where(['nid' => $id, 'status' => 0])->count();
        jsonReturn(1, '公告详情', [
            'unread' => $unread,
            'read' => $read,
            'attachmentList' => $attachmentList,
            'notice' => $notice,
        ]);
    }
}
