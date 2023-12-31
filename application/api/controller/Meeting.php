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

use app\common\model\File;
use app\common\model\PointLog;
use app\common\model\User;
use app\wap\model\Meeting as MeetingModel;
use app\wap\model\MeetingRead;
use app\wap\model\MeetingRec;
use app\wap\model\MeetingUser;
use think\Db;

class Meeting extends Base
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $cate_id int  栏目ID
     * @param $page int 当前页
     * @param $type int 2会议，3活动
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function meetingList($cate_id, $page, $type)
    {
        if (input('now') == 1) {
            $where[] = ['end_time', '>=', time()];
        }
        $limit = input('limit', 10);
        $user_id = $this->user_id;
        $meetingList = MeetingModel::with(['compereUser' => function ($query) {
            $query->field('id,nickname');
        }, 'recordUser' => function ($query) {
            $query->field('id,nickname');
        }])
            ->where($where)
            ->where(function ($query) use ($user_id) {
                // attendee=2是报名的，meetingUser还没有记录
                $query->where('attendee', 2)
                    ->whereOr('id', 'IN', function ($query) use ($user_id) {
                        $query->name('meetingUser')->where('user_id', $user_id)->field('meeting_id');
                    });
            })
            ->where('cat_id', $cate_id)
            ->where($where)
            ->where('deleted', '=', 0)
            ->order('id', 'desc')
            ->field('id,theme,compere,recorder,start_time,attendee')
            ->field('start_time as start_time2,end_time as end_time2')
            ->select();

        if (count($meetingList) >= $limit) {
            $status = 0;
        } else {
            $status = 2;
        }
        jsonReturn(1, '获取成功', [
            'list' => $meetingList,
            'loading' => $status
        ]);
    }

    public function addAttendee($id)
    {
        $data = MeetingModel::with(['recordUser', 'record'])->get($id);
        if ($data->attendee != 2) {
            jsonReturn(0, '不允许报名');
        }
        $meetingUser = MeetingUser::where('meeting_id', $id)
            ->where('user_id', $this->user_id)
            ->find();
        if ($meetingUser) {
            jsonReturn(0, '已报名');
        }
        $res = MeetingUser::create([
            'user_id' => $this->user_id,
            'meeting_id' => $id,
            'add_time' => time(),
        ]);
        if ($res->id > 0) {
            jsonReturn(0, '报名成功');
        } else {
            jsonReturn(0, '报名失败');
        }
    }

    /**
     * 获取会议列表，返回进行中的会议和已结束的会议两个列表
     * @param $cate_id int  栏目ID
     * @param $page int 当前页
     * @param $type int 2会议，3活动
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function endMeetingList($cate_id, $page, $type)
    {
        $cate_id = $this->getCateAll($cate_id);
        $limit = 10;
        //根据end_time结束时间返回已经结束的会议
        $meeting = MeetingModel::hasWhere('meetingUser', ['user_id' => $this->user_id], 'id,theme,compere,recorder,start_time')
            ->with(['compereUser' => function ($query) {
                $query->field('id,nickname');
            }, 'recordUser' => function ($query) {
                $query->field('id,nickname');
            }])
            ->where('cat_id', 'in', $cate_id)
            ->where('deleted', '=', 0)
            ->where(['meeting_type' => $type])
            ->where('end_time', '<', time())
            ->order('id', 'desc')
            ->field('start_time as start_time2,end_time as end_time2')
            ->page($page, $limit)
            ->select();
        jsonReturn(1, '获取成功', [
            'list' => $meeting,
        ]);
    }

    public function searchMeetingList($theme, $page, $type)
    {
        $limit = 10;
        $meetingList = MeetingModel::hasWhere('meetingUser', ['user_id' => $this->user_id], 'id,theme,compere,recorder,start_time')
            ->with(['compereUser' => function ($query) {
                $query->field('id,nickname');
            }, 'recordUser' => function ($query) {
                $query->field('id,nickname');
            }])
            ->where('deleted', '=', 0)
            ->where(['meeting_type' => $type])
            ->wherelike('theme', '%' . $theme . '%')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        if (count($meetingList) >= $limit) {
            $status = 0;
        } else {
            $status = 2;
        }
        jsonReturn(1, '获取成功', [
            'list' => $meetingList,
            'loading' => $status
        ]);
    }

    public function getCateAll($id)
    {
        $childCate = \app\common\model\Category::whereIn('parent_id', $id)->where(['status' => 1])->column('id');
        if ($childCate) {
            if (!is_array($id)) {
                $id = [$id];
            }
            return array_merge($id, $this->getCateAll($childCate));
        } else {
            return $id;
        }
    }

    // 获取会议详情页
    public function meetingDetails()
    {
        $id = (int)input('id', 0);

        //是否已阅读
        $read = MeetingRead::where('user_id', $this->user_id)->where('meeting_id', $id)->find();
        if (!$read) {
            MeetingModel::where('id', $id)->setInc('read_num', 1);
            MeetingRead::create([
                'meeting_id' => $id,
                'user_id' => $this->user_id,
                'create_time' => time(),
                'point' => 0,
                'read_time' => 0,
            ]);
        }
        $sql = \app\common\model\Message::where('uid', $this->user_id)->where(['other_id' => $id])->where(function ($query) {
            $query->whereOr([['type', '=', 3], ['type', '=', 5]]);
        })->update(['read_time' => time()]);


        $data = MeetingModel::with(['compereUser', 'recordUser', 'record'])->where(['deleted' => 0, 'id' => $id])->find();

        if (!$data) {
            jsonReturn(0, '该内容已不存在');
        }

        $start_time = db('meeting')->where(['deleted' => 0, 'id' => $id])->value('start_time');
        if (time() >= $start_time) {
            $data->available = true;
        } else {
            $data->available = false;
        }

        $this->inspectMeetingUser($data['id']);
        if ($data) {
            $data['content'] = htmlspecialchars_decode($data['content']);
        } else {
            return json(array('status' => 0, 'msg' => '不存在'));
        }
        if ($data['sign_status'] == 1) {
            $data['sign_responsibility'] = db('user')->where('id', $data['sign_responsibility'])->value('nickname');
        }
        $meetingUser = MeetingUser::where('meeting_id', $id)
            ->where('user_id', $this->user_id)
            ->find();
        $meetingUserList = MeetingUser::with(['user'])
            ->where('meeting_id', $id)
            ->order('id desc')
            ->select();


        $recordImg = [];
        if ($data['record']['imgs']) {
            foreach ($data['record']['imgs'] as $k => $v) {
                if (strtolower(substr($v, 0, 4)) != 'http') {
                    $recordImg[] = SITE_URL . $v;
                }
            }
        }
        $data['record']['imgs'] = $recordImg;
        if ($meetingUserList) {
            foreach ($meetingUserList as $k => &$v) {
                if (strtolower(substr($v['user']['head_pic'], 0, 4)) != 'http') {
                    $v['user']['head_pic'] = SITE_URL . $v['user']['head_pic'];
                }
            }
        }

        $data['content'] = str_replace('/uploads/', SITE_URL . "/uploads/", $data['content']);
        $data['content'] = htmlspecialchars_decode($data['content']);
        $data['content'] = preg_replace(
            [
                '/(<img [^<>]*?)width=.+?[\'|\"]/',
                '/(<img [^<>]*?)/',
                '/(<img.*?)((height)=[\'"]+[0-9|%]+[\'"]+)/',
                '/(<img.*?)((style)=[\'"]+(.*?)+[\'"]+)/',
            ],
            [
                '$1 width="100%" ',
                '$1 width="100%" ',
                '$1',
                '$1'
            ],
            $data['content']
        );

        $result['meetingInfo'] = $data;
        $result['meetingUser'] = $meetingUser;
        $result['meetingUserList'] = $meetingUserList;
        $result['attachment'] = File::getFileList($data['attachment']);
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * @param $meeting_id
     * 检查一个会议的红灯情况 没有签到 也没有提交心得
     */
    public function inspectMeetingUser($meeting_id)
    {
        $meeting = MeetingModel::find($meeting_id);
        if ($meeting['meeting_type'] == 2 && time() > ($meeting->getData('end_time') + (config('studyReclimitDay') * 86400))) {
            //开会结束5天了，看看还有谁没有提交心得 红灯+1
            $meetingUser = MeetingUser::where('meeting_id', $meeting_id)
                ->where('study_rec_status', 0)
                ->select();
            foreach ($meetingUser as $k => $v) {
                $r1 = MeetingUser::where('id', $v['id'])->update(['study_rec_status' => 2, 'light' => 1]);
                $r2 = User::where('id', $v['user_id'])->setInc('red_light');
            }
        }
    }

    // 添加/编辑学习心得
    public function addEditExperience()
    {
        $data = input();
        $validate_result = $this->validate($data, 'MeetingStudyRec');
        if ($validate_result !== true) {
            return json(['status' => 0, 'msg' => $validate_result]);
        }
        $meetingUser = MeetingUser::where([['meeting_id', '=', $data['meeting_id']], ['user_id', '=', $this->user_id]])->find();
        if (empty($meetingUser)) {
            return json(['status' => 0, 'msg' => '你不在会议内无法编辑心得']);
        }
        Db::startTrans();
        $r2 = true;
        if ($meetingUser['study_rec_status'] == 0 || $meetingUser['study_rec_status'] == 2) {
            $data['study_rec_time'] = time();
            $meeting = MeetingModel::find($meetingUser['meeting_id']);
            $studyReclimitDay = config('studyReclimitDay');
            $studyRecLimitTime = ($meeting->getData('end_time') + ($studyReclimitDay * 86400));
            if (time() < $studyRecLimitTime) {
                //规定时间内提交
                $data['study_rec_status'] = 1;
            } elseif (time() > $studyRecLimitTime) {
                //超时提交
                $data['study_rec_status'] = 3;
            }
            // 正常提交
            if ($data['study_rec_status'] == 1) {
                if ($meeting['sign_status'] == 0) {
                    //不需要签到
                    if ($meeting['meeting_type'] == 2) {
                        $r2 = User::where('id', $this->user_id)->setInc('green_light');
                    } elseif ($meeting['meeting_type'] == 3) {
                        //活动 得积分
                        $activity_type = ActivityType::find($meeting['activity_type']);
                        $r2 = true;
                    }
                } else {
                    //需要签到
                    if ($meetingUser['sign_status'] == 1) {
                        //正常签到
                        if ($meeting['meeting_type'] == 2) {
                            $data['light'] = 3;
                            $r2 = User::where('id', $this->user_id)->setInc('green_light');
                        } elseif ($meeting['meeting_type'] == 3) {
                            //活动 得积分
                            $activity_type = ActivityType::find($meeting['activity_type']);
                            $r2 = true;
                        }
                    } elseif ($meetingUser['sign_status'] == 0) {
                        //未签到
                        $data['light'] = 2;
                        $r2 = User::where('id', $this->user_id)->setInc('yellow_light');
                    } elseif ($meetingUser['sign_status'] == 2) {
                        //请假 的时候回已经增加了黄灯
                        $r2 = true;
                    }
                }
            } elseif ($data['study_rec_status'] == 3) {
                //超时提交 已经在inspectMeetingUser统一处理为红灯
                $r2 = true;
            }
        }
        $meeting = MeetingModel::find($meetingUser['meeting_id']);
        //判断心得字数是否达到50，达到则增加2积分,且该会议只增加一次
        $check = PointLog::where([['user_id', '=', $this->user_id], ['meeting_id', '=', $data['meeting_id']]])->find();
        if (mb_strlen($data['study_rec']) >= 50 && empty($check)) {
            $type = $meeting['meeting_type'] == 2 ? '在会议' : '在活动';
            User::savePoint($this->user_id, 2, $type . ' 【' . $meeting['theme'] . '】 填写心得获得积分2', 2, $meeting['id']);
        }
        $id = $meetingUser->allowField(true)->save($data);
        if ($id && $r2) {
            Db::commit();
            $result = ['status' => 1, 'msg' => '保存成功'];
        } else {
            Db::rollback();
            $result = ['status' => 0, 'msg' => '保存失败'];
        }
        return json($result);
    }

    // 获取用户学习心得
    public function getExperience()
    {
        $meeting_id = (int)input('meeting_id', 0);
        $meetingUser = MeetingUser::where([['meeting_id', '=', $meeting_id], ['user_id', '=', $this->user_id]])->find();
        if (empty($meetingUser)) {
            return json(['status' => 0, 'msg' => '你不在会议内无法编辑心得']);
        }
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $meetingUser['study_rec']]);
    }

    /**
     * 获取会议记录信息
     */
    public function recordInfo()
    {
        $meeting_id = (int)input('meeting_id', 0);
        if (empty($meeting_id)) return json(['status' => 0, 'msg' => '缺少参数']);
        $meetingRec = MeetingRec::where('meeting_id', $meeting_id)->find();
        $meeting = MeetingModel::with(['recordUser', 'compereUser'])->where('id', $meeting_id)->find();
        $meeting['people_num'] = MeetingUser::where('meeting_id', $meeting['id'])->count();
        //将图片转换为数组
        $result['meeting'] = $meeting;
        $result['meetingRec'] = $meetingRec;
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 保存会议记录
     */
    public function saveRec()
    {
        $data = input();
        $validate_result = $this->validate($data, 'app\api\validate\MeetingRec');
        if ($validate_result != true) {
            return json(['status' => 0, 'msg' => $validate_result]);
        }
        $meeting = MeetingModel::where('id', $data['meeting_id'])->find();
        if ($this->user_id != $meeting['recorder']) {
            return json(['status' => 0, 'msg' => '只有记录人能填写']);
        }
        $meetingRec = MeetingRec::where('meeting_id', $data['meeting_id'])->find();
        if (empty($meetingRec)) {
            $data['user_id'] = $this->user_id;
            $meetingRec = new MeetingRec;
        }
        if ($data['imgs']) {
            $data['imgs'] = explode(',', $data['imgs']);
        } else {
            $data['imgs'] = [];
        }
        $id = $meetingRec->allowField(true)->save($data);
        if ($id) {
            $result = ['status' => 1, 'msg' => '保存成功'];
        } else {
            $result = ['status' => 0, 'msg' => '保存失败'];
        }
        return json($result);
    }

    /**
     * 会议签到信息
     */
    public function signInfo()
    {
        $id = (int)input('id', 0);
        if (empty($id)) return json(array('status' => 0, 'msg' => '缺少参数'));
        $data = MeetingModel::with(['recordUser', 'record', 'signDirector'])->get($id);

        $start_time = db('meeting')->where(['id' => $id])->value('start_time');
        if (time() < $start_time - 600 || time() > $start_time) {
            $data->notSign = true;
        } else {
            $data->notSign = false;
        }
        if (time() > $start_time) {
            $data->notLeave = true;
        } else {
            $data->notLeave = false;
        }


        $unsigned = MeetingUser::withJoin(['user'])
            ->where('meeting_id', $data['id'])
            ->where('sign_status', 0)
            ->select();
        $signed = MeetingUser::withJoin(['user'])
            ->where('meeting_id', $data['id'])
            ->where('sign_status', 1)
            ->select();
        //请假
        $leave = MeetingUser::withJoin(['user'])->where('meeting_id', $data['id'])->where('sign_status', 2)->select();

        foreach ($unsigned as $k => &$v) {
            if (strtolower(substr($v['user']['head_pic'], 0, 4)) != 'http') {
                $v['user']['head_pic'] = SITE_URL . $v['user']['head_pic'];
            }
        }

        foreach ($signed as $k => &$v) {
            if (strtolower(substr($v['user']['head_pic'], 0, 4)) != 'http') {
                $v['user']['head_pic'] = SITE_URL . $v['user']['head_pic'];
            }
        }

        foreach ($leave as $k => &$v) {
            if (strtolower(substr($v['user']['head_pic'], 0, 4)) != 'http') {
                $v['user']['head_pic'] = SITE_URL . $v['user']['head_pic'];
            }
        }


        $meetingUser = MeetingUser::where('meeting_id', $id)
            ->where('user_id', $this->user_id)->find();
        $result['data'] = $data;
        $result['unsigned'] = $unsigned;
        $result['signed'] = $signed;
        $result['leave'] = $leave;
        $result['meetingUser'] = $meetingUser;
        // $result['wechat'] = $data;
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 获取二维码
     */
    public function qrCode()
    {
        header('Content-type:image/png');
        $meeting_id = (int)input('meeting_id', 0);
        if (empty($meeting_id)) return json(array('status' => 0, 'msg' => '缺少参数'));
        $meeting = MeetingModel::where('id', $meeting_id)->find();
        if ($meeting['sign_responsibility'] != $this->user_id) {
            return json(array('status' => 0, 'msg' => '没有权限获取二维码'));
        }
        $qrCode = MeetingModel::h5SignQrCode($meeting_id);
        $image = $qrCode->writeString();
        $image = base64_encode($image);
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $image]);
    }

    /**
     * 用户签到
     *
     */
    public function userSignPage()
    {
        $meeting_id = (int)input('meeting_id', 0);
        $code = MeetingModel::dataToToken($meeting_id, $this->user_id); //input('code', '', null);
        $meeting = MeetingModel::where('id', $meeting_id)->find();
        $meetingUser = MeetingUser::where('meeting_id', $meeting['id'])
            ->where('user_id', $this->user_id)
            ->find();
        if (empty($meetingUser)) {
            return json(array('status' => 0, 'msg' => '没有权限签到'));
        }
        if ($meeting['deleted'] != 0) {
            return json(array('status' => 0, 'msg' => '会议已删除'));
        }
        if ($meeting['status'] == 2) {
            return json(array('status' => 0, 'msg' => '会议已取消'));
        }
        Db::startTrans();
        $res = MeetingModel::tokenSign($code, $this->user_id);
        $type = $meeting['meeting_type'] == 2 ? '在会议' : '在活动';
        $res2 = User::savePoint($this->user_id, 2, $type . '【' . $meeting['theme'] . '】 中签到获得积分2', 2);
        if ($res && $res2) {
            Db::commit();
            return json(array('status' => 1, 'msg' => '签到成功', 'result' => ['item' => $meeting, 'status' => $res]));
        } else {
            Db::rollback();
            return json(array('status' => 1, 'msg' => MeetingModel::getErrorInfo(), 'result' => ['item' => $meeting, 'status' => $res]));
        }
    }


    /**
     * 请假
     */
    public function leave()
    {
        $meeting_id = (int)input('meeting_id', 0);
        if (empty($meeting_id)) return json(array('status' => 0, 'msg' => '缺少参数'));
        $meetingUser = MeetingUser::where('meeting_id', $meeting_id)
            ->where('user_id', $this->user_id)->find();
        if ($meetingUser['sign_status'] != 0) return json(array('status' => 0, 'msg' => '无法请假'));
        Db::startTrans();
        $r = MeetingUser::where('meeting_id', $meeting_id)
            ->where('user_id', $this->user_id)
            ->update(['sign_status' => 2, 'sign_time' => time(), 'reason' => input('reason', ''), 'light' => 2]);
        //增加一次黄灯
        $r2 = User::where('id', $this->user_id)->setInc('yellow_light');
        if ($r && $r2) {
            Db::commit();
            return json(array('status' => 1, 'msg' => '请假成功'));
        } else {
            Db::rollback();
            return json(array('status' => 0, 'msg' => '请假失败'));
        }
    }
}
