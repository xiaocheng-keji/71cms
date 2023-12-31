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

use app\api\model\UserDep;
use app\api\model\UserLevel;
use app\common\model\AuthGroupAccessDep as AuthGroupAccessDepModel;
use app\common\model\Department as DepartmentModel;
use app\common\model\DepTeamUser;
use app\common\model\DicData;
use app\common\model\TmpSession;
use app\wap\model\Meeting as MeetingModel;
use app\common\model\User as UserModel;
use app\common\controller\Upload;
use Exception;
use think\Request;
use think\Db;
use app\common\model\PointLog;

class User extends Base
{
    /**
     * 登录
     * @return [type] [description]
     */
    public function login()
    {
        $data['nickname'] = input('username', '');
        $data['password'] = input('password', '');
        $data['igexin_cid'] = input('igexin_cid', '');
        $validate_result = $this->validate($data, 'User.login');
        if ($validate_result !== true) {
            return json(['status' => 0, 'msg' => $validate_result]);
        }
        $user = \app\api\model\User::where('mobile', $data['nickname'])->find();
        if (!$user) {
            return json(['status' => -1, 'msg' => '账号不存在!']);
        } else if (encrypt($data['password']) != $user['password']) {
            return json(['status' => -1, 'msg' => '密码错误!']);
        } else if ($user['is_lock'] == 1) {
            return json(['status' => -1, 'msg' => '账号异常已被锁定！！！']);
        } else {
            \app\api\model\User::update(['client_id' => ''], ['client_id' => input('client_id', '')]);
            $user['level_name'] = UserLevel::where('id', $user['level_id'])->value('name');
            $user['department'] = DepartmentModel::where('id', $user['department_id'])->find();
            $user['token'] = md5(time() . mt_rand(10000, 99999) . $user['id']);
            $update['igexin_cid'] = $data['igexin_cid'];
            $update['token'] = $user['token'];
            $update['client_id'] = input('client_id', '');
            //更新登录时间
//            $update['last_login_time'] = time();
            \app\api\model\User::where('id', $user['id'])->update($update);
            if (strtolower(substr($user['head_pic'], 0, 4)) != 'http') {
                $user['head_pic'] = SITE_URL . $user['head_pic'];
            }
            // 查找用户所在的部门所在的党支部
            $user['dep'] = \app\common\model\Department::getBranch($user['id']);
            $user['dep_id'] = $user['dep']['id'];
            $user['dep'] = $user['dep'] ? $user['dep'] : '';
            $user['dep_admin'] = AuthGroupAccessDepModel::where('uid', $user['id'])->column('dep_id');
            return json(['status' => 1, 'msg' => '登陆成功', 'result' => $user]);
        }
    }

    public function changeClientId()
    {
        \app\api\model\User::update(['client_id' => ''], ['client_id' => input('client_id')]);
        $res = \app\api\model\User::update(['client_id' => input('client_id')], ['id' => $this->user_id]);
        if ($res) {
            jsonReturn(1, '成功更改');
        } else {
            jsonReturn('未更新client_id');
        }
    }

    /**
     * 获取我的页面数据
     */
    public function my()
    {
        $user = $this->user;
        $dep_level = UserDep::with(['level'])->where(['user_id' => $this->user_id])->select()->toArray();
        $user['department'] = '';
        $user['level_name'] = '';
        foreach ($dep_level as $item) {
            $user['department'] .= ',' . $item['department']['name'];
            $user['level_name'] .= ',' . $item['level']['name'];
        }

        $user['department'] = trim($user['department'], ',');
        $user['level_name'] = trim($user['level_name'], ',');

        if (strtolower(substr($user['head_pic'], 0, 4)) != 'http') {
            $user['head_pic'] = SITE_URL . $user['head_pic'];
        }
        $user['nation2'] = $user['nation'];
        $user['job2'] = $user['job'];
        $user['education2'] = $user['education'];
        $user['nation'] = DicData::value2text('nation', $user['nation']);
        $user['job'] = DicData::value2text('job', $user['job']);
        $user['education'] = DicData::value2text('education', $user['education']);
        $user['birthday'] = date('Y-m-d', $user['birthday']);
        $user['apply_time'] =date('Y-m-d', $user['apply_time']);
        $user['join_time'] = date('Y-m-d', $user['join_time']);
        $user['become_time'] =date('Y-m-d', $user['become_time']);
        $user['people_type'] = DicData::value2text('people_type', $user['people_type']);
        $user['dep_id'] = $dep_id = UserDep::where('user_id', $user['id'])->value('dep_id');
        $user['dep'] = DepartmentModel::where('id', $dep_id)->find();
        $user['dep'] = $user['dep'] ? $user['dep'] : '';
        $user['dep_admin'] = AuthGroupAccessDepModel::where('uid', $user['id'])->column('dep_id');

        // 今日获得积分 大于0
        $user['today_point'] = PointLog::where('user_id', $user['id'])->whereTime('create_time', 'today')->where('points', '>', 0)->sum('points');
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $user]);
    }
    public function getMy()
    {

    }
    /**
     * 退出登录
     */
    public function logout()
    {
        $update['igexin_cid'] = '';
        $update['client_id'] = '';
        $update['token'] = '';
        $r = db('user')->where('id', $this->user_id)->update($update);
        if ($r) {
            $result = ['status' => 1, 'msg' => '退出登录成功'];
        } else {
            $result = ['status' => -1, 'msg' => '退出登录失败'];
        }
        return json($result);
    }

    /**
     * 获取指定部门列表
     */
    public function getDepartmentList()
    {
        $parent_id = (int)input('parent_id', 0);
        $DepartmentModel = new DepartmentModel();
        $department = $DepartmentModel->where([['parent_id', '=', $parent_id], ['show', '=', 1]])->order('sort asc')->select();
        // $department = $DepartmentModel->departmentList($parent_id, 0, false, 1);
        // array_shift($department);
        // $departmentList =  array_values($department);

        return json(['status' => 1, 'msg' => '获取成功', 'result' => $department]);
    }

    /**
     * 获取注册所需信息
     */
    public function regInfo()
    {
        $result['xueli'] = DicData::validValueTextColumn('education');
        $result['jiguan'] = UserModel::$jiguan;
        $result['nationList'] = DicData::validValueTextColumn('nation');
        $result['partyStatus'] = DicData::validValueTextColumn('people_status');

        $result['sex'] = DicData::validValueTextColumn('sex');
        $result['jobs'] = DicData::validValueTextColumn('job');
        $result['people_type'] = DicData::validValueTextColumn('people_type');

        if (input('token')) {
            $result['userinfo'] = \app\api\model\User::where(['token' => input('token')])->find();
        }
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 注册用户
     */
    public function reg()
    {
        $data = $this->request->post();
        $validate = $validate = new \app\api\validate\User;
        $data['password'] = $data['new_password'];
        if ($validate->check($data) == false) {
            return json(['status' => -1, 'msg' => $validate->getError()]);
        }
        $data['status'] = 1;
        if ($data['party_status'] != 1) {
            $data['apply_time'] = strtotime($data['join_time']);
            unset($data['join_time']);
        } else {
            $data['join_time'] = strtotime($data['join_time']);
        }
        $data['token'] = md5(time() . mt_rand(10000, 99999));
        $data['last_login_time'] = time();
        $member = new UserModel();
        if ($member->allowField(true)->save($data)) {
            $depData['user_id'] = $member->id;
            $depData['dep_id'] = $data['department_id'];
            $depData['add_time'] = time();
            $depData['tenant_id'] = TENANT_ID;
            db('user_dep')->insert($depData);
            // $user = UserModel::find($member->getLastInsID());
            // $user['level_name'] = db('user_level')->where('id', $user['level_id'])->value('name');
            // $user['department'] = db('department')->where('id', $user['department_id'])->find();
            return json(array('status' => 1, 'msg' => '注册成功')); // , 'result'=>$user
        } else {
            return json(array('status' => 0, 'msg' => '注册失败'));
        }
    }

    /**
     * 积分记录
     */
    public function integral()
    {
        $p = (int)input('page', 1);
        $size = (int)input('size', 10);
        $list = \app\common\model\PointLog::where('user_id', $this->user_id)->order('id desc')->page($p, $size)->select();
        return json(array('status' => 1, 'msg' => '获取成功', 'result' => $list));
    }

    /**
     * 我的支部
     */
    public function myBranch()
    {
        $p = (int)input('page', 1);
        $size = (int)input('size', 10);
        $department3 = DepartmentModel::where([['show', '=', 1], ['tenant_id', '=', TENANT_ID]])->order('sort asc')->column('id');
        $departmentInfo = db('user_dep')->where([['user_id', '=', $this->user_id], ['dep_id', 'in', $department3], ['tenant_id', '=', TENANT_ID]])->find();
        $result = [];
        if ($departmentInfo) {
            $department = DepartmentModel::find($departmentInfo['dep_id']);
            $userIdList = db('user_dep')->where('dep_id', $departmentInfo['dep_id'])->column('user_id');
            $userList = UserModel::with(['level'])
                ->where('id', 'in', $userIdList)
                ->field('id, nickname, head_pic, level_id, mobile')
                ->order('id desc')
                ->page($p, $size)
                ->select();
            foreach ($userList as $k => &$v) {
                if (strtolower(substr($v['head_pic'], 0, 4)) != 'http') {
                    $v['head_pic'] = SITE_URL . $v['head_pic'];
                }
            }
            $result['department'] = $department;
            $result['userList'] = $userList;
        }
        return json(array('status' => 1, 'msg' => '获取成功', 'result' => $result));
    }

    /**
     * 编辑用户信息
     */
    public function editUserInfo()
    {
        $user_id = (int)input('user_id', 0);
        if (empty($user_id)) {
            $user_id = $this->user_id;
        }
        $info = UserModel::find($user_id);
        if (empty($info)) {
            return json(array('status' => 0, 'msg' => '用户不存在'));
        }
        $data = input();
        if ($data['birthday']) {
        }
        if ($data['apply_time']) {
        }
        if ($data['join_time']) {
        }
        if ($data['native_place']) {
            $data['native_place'] = trim($data['native_place']);
        }
        if ($data['education']) {
            $data['education'] = trim($data['education']);
        }
        if ($data['nation']) {
            $data['nation'] = trim($data['nation']);
        }
        $res = $info->allowField(true)->save($data);
        if ($res) {
            $userInfo = UserModel::find($user_id);
            $userInfo['level_name'] = db('user_level')->where('id', $userInfo['level_id'])->value('name');
            $userInfo['department'] = db('department')->where('id', $userInfo['department_id'])->find();
            if (strtolower(substr($userInfo['head_pic'], 0, 4)) != 'http') {
                $userInfo['head_pic'] = SITE_URL . $userInfo['head_pic'];
            }
            return json(array('status' => 1, 'msg' => '获取成功', 'result' => $userInfo));
        } else {
            return json(array('status' => 0, 'msg' => '获取失败', 'result' => ''));
        }
    }
    public function getNativeDic(){
        $list = Db::name('dic_data')
            ->where('type_id',2)
            ->select();
        jsonReturn(1, '列表信息', [
            'list' => $list,
        ]);
    }
    public function getEducationDic(){
        $list = Db::name('dic_data')
            ->where('type_id',3)
            ->select();
        jsonReturn(1, '列表信息', [
            'list' => $list,
        ]);
    }
    public function getJobDic(){
        $list = Db::name('dic_data')
            ->where('type_id',4)
            ->select();
        jsonReturn(1, '列表信息', [
            'list' => $list,
        ]);
    }
    /**
     * 用户上传头像
     */
    public function headPicUp()
    {
        $config = [
            'size' => 20000000,
            'ext' => 'jpg,jpeg,png,gif'
        ];
        try {
            $upload = new Upload();
            $info = $upload->uploadFiles('/Public/head_pic', $config);
            if ($info['status'] == 1) {
                $update['head_pic'] = $info['result']['path'];
                $res = db('user')->where('id', $this->user_id)->update($update);
                if ($res) {
                    return json(array('status' => 1, 'msg' => '获取成功', 'result' => SITE_URL . $info['result']['path']));
                } else {
                    return json(array('status' => 0, 'msg' => '修改头像失败', 'result' => ''));
                }
            } else {
                return json($info);
            }
        } catch (Exception $e) {
            return json(array('status' => 0, 'msg' => $e->getMessage(), 'result' => ''));
        }

    }

    /**
     * 积分排名
     */
    public function integralRanking()
    {
        $type = input('type', 1);
        /**
         * 1 按月 pay_points 字段
         * 2 按年 字段 points
         */
        $date_type = input('date_type', 1);
        /**
         * 1正式党员
         * 2预备党员
         * 3入党积极分子
         */
        $party_status = input('party_status', '');

        $p = input('page', 1);
        $size = input('size', 10);

        $where = [];
        if ($party_status != '') {
            $where['party_status'] = $party_status;
        }
        $field = $date_type == 1 ? 'mon_points' : 'year_points';
        // $where['department_id'] = $this->user['department_id'];

        $res = UserModel::where($where)
            ->order($field . ' DESC, id ASC')
            ->field('nickname,id,' . $field . ' as pay_points,head_pic,tenant_id')
            ->page($p, $size)
            ->select();
//		foreach ($res as $k => &$v) {
//			if (strtolower(substr($v['head_pic'], 0, 4)) != 'http') {
//				$v['head_pic'] = SITE_URL . $v['head_pic'];
//			}
//		}
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $res]);
    }

    /**
     * 我的成绩
     */
    public function myExam()
    {
        $p = (int)input('page', 1);
        $size = (int)input('size', 10);
        $user_id = (int)input('user_id', 0) != 0 ? input('user_id') : $this->user_id;
        $list = db('exam_stage')
            ->alias('a')
            ->leftJoin('__EXAM__ b', 'a.exam_id=b.exam_id')
            ->where(['a.user_id' => ['=', $user_id], 'status' => ['=', 2], 'a.tenant_id' => ['=', TENANT_ID]])
            ->field('a.stage_id,a.sum_score,a.end_time,b.name,b.image')
            ->order('a.stage_id desc')
            ->page($p, $size)
            ->select();
        foreach ($list as $k => &$v) {
            $v['image'] = SITE_URL . $v['image'];
        }
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
    }

    /**
     * 用户会议情况列表
     */
    public function meetingStatus()
    {
        $light = (int)input('light', 0);
        if (empty($light)) return json(['status' => 0, 'msg' => '缺少参数']);
        $p = (int)input('page', 1);
        $size = (int)input('size', 10);
        $meetingList = MeetingModel::hasWhere('meetingUser', ['user_id' => $this->user_id, 'light' => $light])
            ->order('id', 'desc')
            ->page($p, $size)
            ->select();
        if ($light === 1) {
            $title = '红灯情况';
            $color = 'red';
        } else if ($light === 2) {
            $title = '黄灯情况';
            $color = 'yellow';
        } else if ($light === 3) {
            $title = '绿灯情况';
            $color = 'green';
        }
        $result['list'] = $meetingList;
        $result['title'] = $title;
        $result['color'] = $color;
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 获取用户部门职务
     */
    public function getDepList()
    {
        $user_id = (int)input('input', 0);
        if (empty($user_id)) {
            $user_id = $this->user_id;
        }
        $list = db('user_dep')
            ->alias('a')
            ->leftJoin('department b', 'a.dep_id = b.id')
            ->leftJoin('user_level c', 'a.level_id = c.id')
            ->where('user_id', $user_id)
            ->where('show', 1)
            ->field('a.dep_id, a.level_id, a.user_id, b.name as department_name, c.name as level_name')
            ->select();
        return json(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
    }

    /**
     * 修改密码
     *
     * @return \think\response\View
     */
    public function editPassword()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($this->user['password'] !== encrypt($post['old_password'])) {
                jsonReturn(-1, '密码错误');
            }
            if (empty($post['password'])) {
                jsonReturn(-1, '新密码不能为空');
            }
            $validate_result = $this->validate($post, 'User.editPassword', [
                'password.require' => '新密码不能为空',
                'password.length' => '密码在6位到16位之间',
                'password.confirm' => '确认新密码不正确',
                'confirm_password.require' => '确认新密码不能为空',
                'confirm_password.confirm' => '确认新密码不正确',
            ]);
            if ($validate_result !== true) {
                jsonReturn(0,$validate_result);
            } else {
                $userModel = new UserModel();
                $res = $userModel->allowField(true)->save($post, ['id' => $this->user_id]);
                if ($res) {
                    jsonReturn(1, '修改成功');
                } else {
                    jsonReturn(-1, '修改失败');
                }
            }
        }
    }

    public function getVersion()
    {
        if(input('?version') && input('version') > 0){
            $version = input("version");
            //最新的版本
            $app_code = Db::name('config')->where('varname', 'app_code')->value('value');
            $app_num = Db::name('config')->where('varname', 'app_num')->value('value');
            $app_link = Db::name('config')->where('varname', 'app_link')->value('value');
            $app_desc = Db::name('config')->where('varname', 'app_desc')->value('value');
            if($version !== $app_code && version_compare($version, $app_code) == -1){
                $update_flag = 1;
                $data = array(
                    "update_flag"=> 1,
                    "update_url"=>$app_link,
                    "forceupdate"=>0,
                    "wgt_flag"=>0,
//                    "wgt_url"=>"http://192.168.10.7:88/Public/Uploads/app/backorder_1.0.4.wgt",
                    "update_tips"=>$app_desc,
                    "version"=>$app_code,
//                    "size"=>0
                );
                return json(array("code"=>100,"msg"=>"","data"=>$data));
            }else{
                return json(array("code"=>100,"msg"=>"暂无新版本","data"=>array()));
            }
        }else{
            return json(array("code"=>-100,"msg"=>"参数错误","data"=>array()));
        }
    }

    /**
     * 获取我的所有学习
     */
    public function get_course($page=1,$type='all'){
        //本月完成
        $user = $this -> user;
//        $user['course'] = CourseComplete::whereTime('complete_time','month') -> count();

        $uncomplete = '';
//        $complete = '';
//        if($type=='all'){
//            //未完成的
//            $uncomplete = $this -> get_uncomplete($user['id'],$page);
//            //已完成的
//            $complete = $this -> get_complete($user['id'],$page);
//        }elseif($type=='complete'){
//            $complete = $this -> get_complete($user['id'],$page);
//            $uncomplete = '';
//        }elseif($type=='uncomplete'){
//            $uncomplete = $this -> get_uncomplete($user['id'],$page);
//            $complete = '';
//        }
        //获取我的学习数据
        $type_array = array('1053','1054','1055','1056','1057','1058','1059','1060','1089','1090','1092','1093','1094','1095','1087','1088','109','1078');
        $total = array();
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $total['today'] = Db::name('article_read')->alias('a')->join('__ARTICLE__ b', 'a.article_id = b.id')
            ->where(['a.user_id'=>$user['id']])->where('a.create_time', '>', $beginToday)->where('a.create_time', '<', $endToday)
            ->where('cat_id', 'in', $type_array)->count();
        $my_type = Db::name('article_read')->alias('a')->join('__ARTICLE__ b', 'a.article_id = b.id')
            ->where(['a.user_id'=>$user['id']])->where('a.create_time', '>', $beginToday)->where('a.create_time', '<', $endToday)
            ->where('cat_id', 'in', $type_array)->column('cat_id');
        $total['type'] = count(array_unique($my_type));
        $total['all'] = Db::name('article_read')->alias('a')->join('__ARTICLE__ b', 'a.article_id = b.id')
            ->where(['a.user_id'=>$user['id']])
            ->where('cat_id', 'in', $type_array)->count();
        $complete['list'] = Db::name('article_read')->alias('a')->join('__ARTICLE__ b', 'a.article_id = b.id')
            ->where(['a.user_id'=>$user['id']])
            ->where('cat_id', 'in', $type_array)->field('b.id, b.title, a.create_time')->limit(15)->select();
        if(!empty($complete['list'])){
            foreach ($complete['list'] as $k=>$v){
                $complete['list'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        jsonReturn(1,'成功获取',['user'=>$user,'complete'=>$complete,'uncomplete'=>array(),'total'=>$total]);
    }
}