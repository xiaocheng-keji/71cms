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
namespace app\admin\controller;

use app\common\controller\ModelHelper;
use app\common\model\Category as CategoryModel;
use app\common\model\File as FileModel;
use app\common\model\Meeting as MeetingModel;
use app\common\model\TmpSession;
use app\wap\model\Meeting as WapMeetingModel;
use app\common\model\UserDep;
use app\wap\model\MeetingRead;
use Endroid\QrCode\QrCode;
use think\Db;
use think\Queue;

/**
 * 会议管理
 * Class Department
 * @package app\admin\controller
 */
class  Meeting extends AdminBase
{
    protected function initialize()
    {
        parent::initialize();
        $this->category_model = new CategoryModel();
        $this->meeting_model = new MeetingModel();
    }

    public function index($cat_id = 0, $page = 1)
    {
        $meeting_type = input('meeting_type', 2);
        $modelHelper = new ModelHelper();
        $modelHelper
            // ->addSearchField('主题', 'theme', 'text', ['exp' => 'LIKE'])
            ->addSearchField('是否主页推荐', 'recommend', 'select', ['options' => ['是否推荐', '推荐'], 'exp' => '>='])
            ->addSearchField('类型', 'meeting_type', 'hidden', ['value' => $meeting_type])
            ->addSearchField('栏目', 'cat_id', 'select', ['options' => cat_select([$meeting_type])]);

        if (input('page', 0) > 0) {
            $meeting_type = input('meeting_type', 2);
            $recommend = input('recommend', '');
            $where[] = ['meeting_type', '=', $meeting_type];
            if ($recommend > 0) {
                $where[] = ['a.recommend', '>=', $recommend];
            }
            $where[] = ['deleted', '=', 0];
            if ($cat_id) {
                session('cat_id', $cat_id);
                $where[] = ['cat_id', '=', $cat_id];
            } else {
                if ((int)session('cat_id') > 0 && $page > 1) {
                    $cat_id = session('cat_id');
                    $where[] = ['cat_id', '=', $cat_id];
                } else {
                    session('cat_id', null);
                }
            }
            $dep_auth = session('dep_auth');
            $where[] = ['a.publish', 'in', array_keys($dep_auth)];
            $count = Db::name('meeting')
                ->alias('a')
                ->join('category c', 'a.cat_id = c.id')
                ->group('a.id')
                ->field('a.id, a.theme, a.place, a.start_time, a.end_time, a.add_time, c.cat_name')
                ->where($where)
                ->order('a.id DESC')
                ->count();
            $list = Db::name('meeting')
                ->alias('a')
                ->join('category c', 'a.cat_id = c.id')
                ->leftJoin('department e', "a.publish=e.id")
                ->group('a.id')
                ->field('a.id, a.theme, a.place, a.start_time, a.end_time, a.add_time, c.cat_name,a.status,a.sign_status,a.read_num,a.recommend')
                ->fieldRaw('IF(e.short_name="",e.name,e.short_name) as dep_name')
                ->where($where)
                ->order('id DESC')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => &$v) {
                $v['meeting_time'] = date('Y-m-d H:i', $v['start_time']) . ' - ' . date('Y-m-d H:i', $v['end_time']);
                $v['canCancel'] = 1;
                if ($v['status'] == 1) {
                    if ($v['start_time'] > time()) {
                        $status = '未开始';
                    } else if ($v['start_time'] < time() && $v['end_time'] > time()) {
                        $status = '进行中';
                    } else {
                        $status = '已结束';
                    }
                    if ($v['start_time'] < time()) {
                        $v['canCancel'] = 0;
                    }
                } else {
                    $v['canCancel'] = 0;
                    $status = '已取消';
                }
                $v['status'] = $status;
                $v['read_num'] = (string)$v['read_num'] > 0 ? $v['read_num'] : '0';
                $v['xinde_num'] = (string)\app\common\model\MeetingUser::where('meeting_id', $v['id'])->where('study_rec', '<>', '')->count('id');
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTips('列表[主页推荐]双击或者单击进入编辑模式,失去焦点可进行自动保存，1-10推荐到轮播图，大于10推荐到热点关注')
            ->addTopBtn('添加会议', url('meeting/add', array('meeting_type' => $meeting_type)))
            ->addField('栏目', 'cat_name', 'text')
            ->addField('主题', 'theme', 'text')
            ->addField('会议时间', 'meeting_time', 'text')
            ->addField('地点', 'place', 'text')
            ->addField('所属组织', 'dep_name', 'text')
            ->addField('阅读数', 'read_num', 'text', ['templet' => '<div>
                                <a class="layui-btn layui-btn-info layui-btn-xs" data-ext="?meeting_id={{= d.id }}" lay-event="' . url('readLIst') . '?meeting_id={{= d.id }}">{{= d.read_num}}</a>
                                </div>', 'width' => 80])
            ->addField('心得数', 'xinde_num', 'text', ['templet' => '<div>
                                <a class="layui-btn layui-btn-info layui-btn-xs" data-ext="?id={{= d.id }}" lay-event="' . url('detail') . '?id={{= d.id }}">{{= d.xinde_num}}</a>
                                </div>', 'width' => 80])
            ->addField('主页推荐', 'recommend', 'text', ['edit' => 'text', 'width' => 90])
            ->addField('状态', 'status', 'text', ['width' => 80])
            ->addField('会议开始时间', 'start_time', 'text', ['hide' => true])
            ->addRowBtn('编辑', url('add'), 'barDemo', ['field' => 'start_time', 'operator' => '\>', 'value' => time()])
            ->addRowBtn('详情', url('meeting/detail'), 'barDemo', ['field' => 'start_time', 'operator' => '\<', 'value' => time()], 'btn-warm', 'btn')
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#dropdown',
                'width' => 220
            ]);
        $modelHelper->list_tpl = $this->request->action();
        return $modelHelper->showList();

    }

    /**
     * 添加会议
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');

            if (empty($data['publish']) || $data['publish'] <= 0) {
                return json(['code' => 0, 'msg' => '请选择发起组织']);
            }
            $dep_auth = array_keys(session('dep_auth'));

            $type = $data['id'] ? 1 : 0;//1编辑，0添加

            if ($type == 1) {
                $meetingInfo = Db::name('meeting')
                    ->where('id', $data['id'])
                    ->find();
                if (!in_array($meetingInfo['publish'], $dep_auth)) {
                    $this->error('没有权限编辑');
                }
            }

            if (!in_array($data['publish'], $dep_auth)) {
                $this->error('没有权限选择此所属组织');
            }

            $time = explode(" - ", $data['messingtime']);
            $meetingData['cat_id'] = $data['cat_id'];
            $meetingData['place'] = $data['place'];
            $meetingData['theme'] = $data['theme'];
            $meetingData['content'] = $data['content'];
            $meetingData['sign_status'] = $data['sign_status'];
            $meetingData['publish'] = $data['publish'];
            $meetingData['compere'] = $data['compere'];
            $meetingData['recorder'] = $data['recorder'];
            $meetingData['require'] = $data['require'];
            $meetingData['attendee'] = $data['attendee'];
            $meetingData['start_time'] = strtotime($time[0]) == false ? '' : strtotime($time[0]);
            $meetingData['end_time'] = strtotime($time[1]) == false ? '' : strtotime($time[1]);
            $meetingData['meeting_type'] = $data['meeting_type'];
            $meetingData['activity_type'] = $data['activity_type'];
            if ($data['attachment']) {
                foreach ($data['attachment'] as $k => $attachment) {
                    if (empty($attachment)) unset($data['attachment'][$k]);
                }
            } else {
                $data['attachment'] = [];
            }
            $meetingData['attachment'] = json_encode($data['attachment']);
            $meetingData['sign_time'] = $data['sign_time'] ? strtotime($data['sign_time']) : 0;
            //地址坐标
            $latitude = getConfig('latitude');
            $longitude = getConfig('longitude');

            $meetingData['latitude'] = $data['latitude'] ? $data['latitude'] : ($latitude ? $latitude : '22.78176');
            $meetingData['longitude'] = $data['longitude'] ? $data['longitude'] : ($longitude ? $longitude : '108.320113');

            //活动风采
            $meetingData['fengcai'] = trim($data['fengcai'], ',');

            if ((int)$data['sign_status'] == 1) {
                if (empty($data['sign_responsibility'])) {
                    return json(array('code' => 0, 'msg' => '请选择签到负责人'));
                }
                $meetingData['sign_responsibility'] = $data['sign_responsibility'];
            } else {
                $meetingData['sign_responsibility'] = 0;
            }
            $validate_result = $this->validate($meetingData, 'Meeting');
            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            }
            if ($data['attendee'] == 1 && (empty($data['choice_group']) && empty($data['choice_user']))) {
                return json(array('code' => 0, 'msg' => '指定党员不能为空'));
            }
            if ($type == 0 && $meetingData['start_time'] < (time() + 3600)) {
//                return json(array('code' => 0, 'msg' => '会议开始最少大于当前时间1个小时'));
            }
            $choice_user_arr = [];
            $choice_group_arr = [];
            if ($data['attendee'] == 1) {
                if ($data['choice_user']) {
                    $meetingData['choice_user'] = implode(',', $data['choice_user']);
                    foreach ($data['choice_user'] as $k => $v) {
                        $choice_user = explode('-', $v);
                        $choice_user_arr[] = $choice_user[0];
                    }
                }
                if ($data['choice_group']) {
                    $meetingData['choice_group'] = implode(',', $data['choice_group']);
                    foreach ($data['choice_group'] as $k => $v) {
                        $choice_group = explode('-', $v);
                        $choice_group_arr[] = $choice_group[0];
                    }
                }
                $meetingData['tags'] = implode(',', $data['tags']);
                if ($choice_group_arr) {
                    $userDep = new UserDep();
                    $userIdList = $userDep->where('dep_id', 'in', $choice_group_arr)->column('user_id');
                }
                if (!empty($data['choice_user']) && !empty($data['choice_group'])) {
                    $userIdList = array_unique(array_merge($userIdList, $choice_user_arr));
                } else if (!empty($choice_user_arr)) {
                    $userIdList = $choice_user_arr;
                }
            } else {
                $meetingData['choice_user'] = '';
                $meetingData['choice_group'] = '';
                $meetingData['tags'] = '';
                // 权限下的所有用户
                $userIdList = Db::name('user')
                    ->alias('a')
                    ->leftJoin('user_dep b', 'a.id = b.user_id')
                    ->where('b.dep_id', 'in', TmpSession::getDepAuth())
                    ->column('a.id');
            }
            if (!in_array($meetingData['compere'], $userIdList)) {
                $userIdList[] = $meetingData['compere'];
            }
            if (!in_array($meetingData['recorder'], $userIdList)) {
                $userIdList[] = $meetingData['recorder'];
            }
            if (!in_array($meetingData['sign_responsibility'], $userIdList) && $meetingData['sign_status'] == 1) {
                $userIdList[] = $meetingData['sign_responsibility'];
            }
            Db::startTrans();
            $res = $res1 = $res2 = $res3 = true;
            $templateUserList = [];
            if ($type == 1) {
                $info = Db::name('meeting')->where('id', $data['id'])->find();
                if (empty($info)) {
                    return json(array('code' => 0, 'msg' => '非法操作'));
                }
                if ($info['start_time'] < time()) {
                    return json(array('code' => 0, 'msg' => '开始时间不能少于当前时间'));
                }
                $meeting_user_list = Db::name('meeting_user')->where('meeting_id', $data['id'])->column('user_id');
                $add_diff = array_diff($userIdList, $meeting_user_list);
                $del_diff = array_diff($meeting_user_list, $userIdList);
                $res = Db::name('meeting')->where('id', $data['id'])->update($meetingData);
                $res = $data['id'];
                if ($add_diff) {
                    $templateUserList = $add_diff;
                    $userDataArr = [];
                    $messageArr = [];
                    foreach ($add_diff as $k => $v) {
                        $message['uid'] = $userData['user_id'] = $v;
                        $userData['meeting_id'] = $data['id'];
                        $message['time'] = $userData['add_time'] = time();
                        $userDataArr[] = $userData;
                        $message['type'] = 3;
                        $message['content'] = $data['theme'];
                        $message['other_id'] = $data['id'];
                        $messageArr[] = $message;
                    }
                    $res1 = Db::name('meeting_user')->insertAll($userDataArr);
                    $res3 = Db::name('message')->insertAll($messageArr);
                }
                if ($del_diff) {
                    $res2 = Db::name('meeting_user')->where([['meeting_id', '=', $data['id']], ['user_id', 'in', $del_diff]])->delete();
                }
            } else {
                $templateUserList = $userIdList;
                $meetingData['add_time'] = time();
                $meetingData['release'] = session('admin_id') ? session('admin_id') : session('user_id');
                $res = Db::name('meeting')->insertGetId($meetingData);
                $userDataArr = [];
                $messageArr = [];
                foreach ($userIdList as $k => $v) {
                    $message['uid'] = $userData['user_id'] = $v;
                    $userData['meeting_id'] = $res;
                    $message['time'] = $userData['add_time'] = time();
                    if ($meetingData['meeting_type'] == 2) {
                        $message['type'] = 3;
                    } else {
                        $message['type'] = 5;
                    }
                    $userDataArr[] = $userData;
                    $message['content'] = $data['theme'];
                    $message['other_id'] = $res;
                    $messageArr[] = $message;
                }
                $res1 = Db::name('meeting_user')->insertAll($userDataArr);
                $res3 = Db::name('message')->insertAll($messageArr);
            }
            if ($res && $res1 && $res2 && $res3) {
                Db::commit();
                if ($templateUserList) {
                    //TODO 异步 队列发送
                    $message = new \app\notify\Message(\app\notify\Message::TYPE_MEETING, $meetingData, $templateUserList);
                    $isPushed = Queue::push(\app\job\Message::class, $message->toArray(), \app\job\Message::QUEUE_NAME);
                    if ($isPushed === false) {
                        //加入队列失败
                        \think\facade\Log::write($data, 'queue_push_fail');
                    }
                }
                $msg = $type == 1 ? '编辑' : '发布';
                return json(array('code' => 200, 'msg' => '会议' . $msg . '成功'));
            } else {
                // 回滚事务
                Db::rollback();
                return json(array('code' => 0, 'msg' => '操作失败', 'data' => [$res, $res1, $res2, $res3]));
            }
        } else {
            //下面是显示页面
            $meeting_type = input('meeting_type', 2);
            $cat_select = cat_select([$meeting_type]);
            unset($cat_select[0]);
            $id = input('id', 0);
            if ($id) {
                $meetingInfo = Db::name('meeting')->where('id', $id)->find();
                if (empty($meetingInfo)) {
                    return json(array('code' => 0, 'msg' => '非法操作'));
                }
                //读取附件信息
                $meetingInfo['attachment'] = FileModel::getFileList($meetingInfo['attachment']);
                $meetingInfo['messingtime'] = date('Y-m-d H:i:s', $meetingInfo['start_time']) . ' - ' . date('Y-m-d H:i:s', $meetingInfo['end_time']);
                $meetingInfo['compereName'] = Db::name('user')->where('id', $meetingInfo['compere'])->value('nickname');
                $meetingInfo['recorderName'] = Db::name('user')->where('id', $meetingInfo['recorder'])->value('nickname');
                if ($meetingInfo['sign_status'] == 1) {
                    $meetingInfo['signresponsibilityName'] = Db::name('user')->where('id', $meetingInfo['sign_responsibility'])->value('nickname');
                }
                if ($meetingInfo['sign_time'] > 0) {
                    $meetingInfo['sign_time'] = date('Y-m-d H:i:s', $meetingInfo['sign_time']);
                } else {
                    $meetingInfo['sign_time'] = '';
                }
                if ($meetingInfo['choice_user']) {
                    $choice_user = explode(',', $meetingInfo['choice_user']);
                    $choice_user_arr = [];
                    foreach ($choice_user as $k => $v) {
                        $explode_arr = explode('-', $v);
                        // $choice_user_arr[$explode_arr[0]] = $explode_arr[1];
						$choice_user_arr[] = [
						    'id' => $explode_arr[0],
						    'name' => $explode_arr[1],
						    'type' => 'user'
						];
                    }
                    $meetingInfo['choice_user'] = $choice_user_arr;
                }
                if ($meetingInfo['choice_group']) {
                    $choice_group = explode(',', $meetingInfo['choice_group']);
                    $choice_group_arr = [];
                    foreach ($choice_group as $k => $v) {
                        $explode_arr = explode('-', $v);
                        // $choice_group_arr[$explode_arr[0]] = $explode_arr[1];
						$choice_group_arr[]=[
						    'id' => $explode_arr[0],
						    'name' => $explode_arr[1],
						    'type' => 'group'
						];
                    }
                    $meetingInfo['choice_group'] = $choice_group_arr;
                }
                if ($meetingInfo['tags']) {
                    $meetingInfo['tags'] = explode(',', $meetingInfo['tags']);
                }
                $meetingInfo['fengcai_pic'] = Db::name('file')->whereIn("id", $meetingInfo['fengcai'])->select();
                $meetingInfo['content'] = htmlspecialchars_decode($meetingInfo['content']);
                $this->assign('meetingInfo', $meetingInfo);
            } else {
                $meetingInfo['messingtime'] = '';
            }

            $this->assign('cat_options', $cat_select);
            $this->assign('meeting_type', $meeting_type);

            $auth_dep_arr = array_keys(session('dep_auth'));
            $department_model = new \app\common\model\Department();
            $department_options = $department_model->optionsArr();
            unset($department_options[0]);
            foreach ($department_options as $k => $v) {
                $department_options[$k] = [
                    'title' => $v,
                    'disabled' => $k > 0 && (!in_array($k, $auth_dep_arr))
                ];
            }
            $dep_auth = session('dep_auth');
            $dep_auth = array_keys($dep_auth);
            $this->assign('dep_options', $department_options);
            $this->assign('currentDep', $dep_auth);

            return $this->fetch('add');
        }
    }

    /**
     * 删除
     * [deleted description]
     * @return [type] [description]
     */
    public function delete()
    {
        $id = input('id', 0);
        if (empty($id)) {
            return json(array('code' => 0, 'msg' => '缺少参数'));
        }
        Db::name('meeting')->where('id', $id)->update(['deleted' => 1]);
        //减少红绿灯
        $list = \app\common\model\MeetingUser::where('meeting_id', $id)->field('user_id,light')->select();
        foreach ($list as $k => $v) {
            switch ($v['light']) {
                case 1:
                    \app\admin\model\User::where('id', $v['user_id'])->setDec('red_light', 1, 1);
                    break;
                case 2:
                    \app\admin\model\User::where('id', $v['user_id'])->setDec('yellow_light', 1, 1);
                    break;
                case 3:
                    \app\admin\model\User::where('id', $v['user_id'])->setDec('green_light', 1, 1);
                    break;
            }
        }
        return json(array('code' => 1, 'msg' => '删除成功'));
    }

    /**
     * 详情
     * [detail description]
     * @param integer $id [description]
     * @param integer $dep_id [description]
     * @return [type]          [description]
     */
    public function detail($id = 0, $dep_id = 0)
    {
        if (empty($id)) {
            $this->error('缺少参数');
        }

        $meeting = Db::name('meeting')->where('id', $id)->find();
        if (!$meeting) {
            $this->error('参数错误');
        }
        $meeting['compere'] = Db::name('user')->where('id', $meeting['compere'])->value('nickname');
        $meeting['recorder'] = Db::name('user')->where('id', $meeting['recorder'])->value('nickname');

        $meeting_rec = Db::name('meeting_rec')->where('meeting_id', $id)->find();
        if ($meeting_rec && !empty($meeting_rec['imgs'])) {
            $meeting_rec['imgs'] = json_decode($meeting_rec['imgs']);
        }

        if ($meeting['sign_status'] == 1) {
            $qrCode = WapMeetingModel::signQrCode($id);
            $qrCode->setSize(300);
            $meeting['code_url'] = $qrCode->writeDataUri();
        }
        $where[] = ['a.meeting_id', '=', $id];
        if ($dep_id > 0) {
            $where[] = ['b.department_id', '=', $dep_id];
        }
        $userList = Db::name('meeting_user')
            ->alias('a')
            ->join('user b', 'a.user_id=b.id')
            ->field('a.sign_status, a.sign_time,a.study_rec_time, a.study_rec, b.head_pic, b.nickname')
            ->where($where)
            ->select();
        $sign_status = ['缺席', '已签到', '请假'];
        $this->assign('sign_status', $sign_status);
        $this->assign('userList', $userList);

        $this->assign('meeting', $meeting);
        $this->assign('meeting_rec', $meeting_rec);
        return view();
    }

    /**
     * 下载二维码
     *
     * @param int $id
     * @param int $dep_id
     * @return \think\response\View
     */
    public function qrcodeDownload($id = 0, $dep_id = 0)
    {
        if (empty($id)) {
            $this->error('缺少参数');
        }

        $meeting = Db::name('meeting')->where('id', $id)->find();
        if (!$meeting) {
            $this->error('参数错误');
        }

        if ($meeting['sign_status'] == 1) {
            $code = base64_encode($meeting['id'] . ',' . time());
            $qrCode = new QrCode($code);
            $qrCode->setSize(200);
            header('Content-Type: image/png');
            header("Accept-Ranges: bytes");
            header('Content-Disposition: attachment; filename="' . $meeting['theme'] . '签到二维码".png"');
            echo $qrCode->writeString();
            die;
        }
        $this->error('不需要签到');
    }

    public function cancelMeeting($id = 0)
    {
        if (empty($id)) {
            $this->error('缺少参数');
        }

        $meeting = Db::name('meeting')->where('id', $id)->find();
        if (!$meeting) {
            $this->error('参数错误');
        }
        if ($meeting['start_time'] < time()) {
            $this->error('开始时间已过，不能取消');
        }
        $res = Db::name('meeting')->where('id', $id)->update(['status' => 2]);
        if ($res) {
            $templateUserList = Db::name('meeting_user')
                ->alias('a')
                ->join('user b', 'a.user_id=b.id')
                ->where(['meeting_id' => $meeting['id']])
                ->field('b.openid')
                ->select();
            $compere = Db::name('user')->where('id', $meeting['compere'])->value('nickname');
            $params['first'] = '会议取消';
            $params['title'] = $meeting['theme'];
            $params['time'] = date('Y-m-d H:i:s', $meeting['start_time']) . ' - ' . date('Y-m-d H:i:s', $meeting['end_time']);
            $params['place'] = $meeting['place'];
            $params['compere'] = $compere;
            $params['id'] = $meeting['id'];
            $params['remark'] = '会议取消了';
            foreach ($templateUserList as $k => $v) {
                if ($v['openid']) {
                    $params['openid'] = $v['openid'];
                    //TODO 发送取消通知
                }
            }
            $this->success('取消成功');
        } else {
            $this->error('取消失败');
        }
    }

    public function readList($meeting_id)
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('用户名', 'nickname', 'text', ['exp' => 'LIKE']);

        if (input('page', 0) > 0) {
            $where = $modelHelper->getSearchWhere();
            $where[] = ['a.meeting_id', '=', $meeting_id];
            $article = new MeetingRead();
            $count = $article::useGlobalScope(false)->alias('a')
                ->leftJoin('__USER__ b', 'a.user_id=b.id')
                ->where($where)
                ->order('a.id desc')
                ->count();
            $list = $article::useGlobalScope(false)->alias('a')
                ->leftJoin('__USER__ b', 'a.user_id=b.id')
                ->where($where)
                ->field('a.*,b.nickname')
                ->order('id desc')
                // ->order('recommend asc, id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
//            ->addTips('推荐排序规则：0是不推荐，小的在前面')
            ->addTopBtn('返回', 'javascript:history.back()')
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('用户', 'nickname', 'text')
            ->addField('添加时间', 'create_time', 'text');
        return $modelHelper->showList();
    }

    /**
     * 在线会议配置
     */
    public function setOnlineMeeting(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = Db::name('online_meeting_config')->find();
            if (!$res) {
                $res = Db::name('online_meeting_config')->insert($data);
            } else {
                $res = Db::name('online_meeting_config')->where('id', $res['id'])->update($data);
            }
            if ($res) {
                return json(['code' => 200, 'msg' => '设置成功']);
            } else {
                return json(['code' => 0, 'msg' => '设置失败或未改动']);
            }
        }
        $item = Db::name('online_meeting_config')->find();
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('在线会议配置信息')
            ->addField('SDKAPPID', 'sdk_app_id', 'text', ['require' => '*',"tip"=>"腾讯云实时音视频appid"])
            ->addField('签发UserSig的密钥', 'user_sig_key', 'text',['require' => '*',"tip"=>"前往 https://cloud.tencent.com/document/product/647/17275#UserSig 查看如何获取"])
            ->addField('secretId', 'secret_id', 'text', ['require' => '*','tip'=>'前往 https://console.cloud.tencent.com/cam/capi 获取'])
            ->addField('secretKey', 'secret_key', 'text', ['require' => '*','tip'=>'前往 https://console.cloud.tencent.com/cam/capi 获取'])
            ->addField('开启屏幕分享', 'is_share', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 读取在线会议配置信息
     */
    private function getOnlineMeetingConfig(){
        $config = Db::name('online_meeting_config')->find();
        if(empty($config['sdk_app_id']) || empty($config['secret_id']) || empty($config['secret_key']) || empty($config['user_sig_key'])){
            $this->error('未正确配置在线会议参数');
        }
        return $config;
    }

    /**
     * 更新字段
     */
    public function updateField()
    {
        $model = new MeetingModel();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $r = $model->allowField('recommend')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }
}