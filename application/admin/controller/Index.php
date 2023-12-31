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

use app\admin\model\CourseComplete;
use app\common\model\Department as DepartmentModel;
use app\common\model\DicData;
use think\Db;


/**
 * 后台首页
 * Class Index
 * @package app\admin\controller
 */
class Index extends AdminBase
{
    /**
     * 首页
     *
     * @return mixed
     */
    public function index()
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) . '/';
        $root = 'http://' . $_SERVER['HTTP_HOST'] . $baseUrl;
        $version = Db::query('SELECT VERSION() AS ver');
        $config = [
            'url' => $_SERVER['HTTP_HOST'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'server_os' => PHP_OS,
            'server_port' => $_SERVER['SERVER_PORT'],
            'server_soft' => $_SERVER['SERVER_SOFTWARE'],
            'php_version' => PHP_VERSION,
            'mysql_version' => $version[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize'),
        ];
        $this->assign('root', $root);
        $this->getMenu();
        return $this->fetch('index', ['config' => $config]);
    }

    public function home()
    {
        return $this->fetch();
    }

    public function update()
    {
        $host = $_SERVER['HTTP_HOST'];
        $version = file_get_contents(app()->getAppPath() . 'version.php');

        $data = [
            'version' => $version,
            'host' => $host,
        ];

        $url = 'http://server.71cms.net/api/update/check';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);
        if ($result['status'] == 1 && $result['result']['version'] > $version) {
            return json(array('code' => 200, 'msg' => '有新版本' . $result['result']['version'], 'need_update' => 1, 'data' => $result['result']));
        } elseif ($result['status'] == 1) {
            return json(array('code' => 200, 'msg' => '当前已经是最新版本', 'need_update' => 0));
        }
    }

    public function echart($full = '')
    {
        //党员人数
        $party_count = Db::name('user')->count();
        $department = DepartmentModel::count();
        $this->assign('party_count', $party_count);
        $this->assign('department', $department);
        //那女比例
        $male = Db::name('user')->where(['sex' => 1])->count();
        $female = Db::name('user')->where(['sex' => 2])->count();
        $this->assign('male_percent', sprintf("%.2f", $male == 0 ? 0 : $male / ($male + $female) * 100));
        $this->assign('female_percent', sprintf("%.2f", $female == 0 ? 0 : $female / ($male + $female) * 100));
        $this->assign('full', $full);
        return view();
    }


    public function meetingList($date = '', $dep_id = 0, $is_ajax = 0)
    {
        if (empty($date)) {
            $current_month_start = date('Y-m-01');
            $start_time = strtotime("{$current_month_start} -11 month");
            $end_time = strtotime("{$current_month_start} +1 month -1 day");
            $date = date('Y-m', $start_time) . ' - ' . date('Y-m', $end_time);
        } else {
            $date_arr = explode(' - ', $date);
            $start_time = strtotime($date_arr[0]);
            $end_time = strtotime($date_arr[1] . " +1 month -1 day");
        }

        $department_model = new DepartmentModel();
        $department_level_list = $department_model->select()->toArray();

        $meeting_where = [
            ['end_time', 'BETWEEN', [$start_time, $end_time]]
        ];
        $count_where = [];
        if ($dep_id > 0) {
            $department = DepartmentModel::find($dep_id);
            if (!$department) {
                $this->error('id 错误');
            }
            $userList = Db::name('user_dep')->where('dep_id', $dep_id)->column("user_id");
            $count_where[] = ['user_id', 'in', $userList];
            $dep = '%' . $dep_id . '-' . $department_level_list[$dep_id]['name'] . '%';
            $meeting = Db::name('meeting')->where($meeting_where)->where(function ($query) use ($dep) {
                $query->where('choice_group', 'like', $dep)->whereOr('attendee', 0);
            })->column('id');
        } else {
            $meeting = Db::name('meeting')->where($meeting_where)->column('id');
        }
        $meetingId = Db::name('meeting')->where([['id', 'in', $meeting], ['sign_status', '=', 1]])->column('id');

        $count_where[] = ['meeting_id', 'in', $meetingId];
        $total['meeting'] = count($meeting);
        $total['reach'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 1)->count();
        $total['absent'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 0)->count();
        $total['leave'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 2)->count();

        $month = [];
        $n = 0;
        for ($i = $start_time; $i < $end_time; $i = strtotime(date('Y-m-d', $i) . " +1 month")) {
            $n++;
            $end_i_time = strtotime(date('Y-m-d', $i) . " +1 month -1 day");
            $current_where_meeting = [
                ['start_time', 'BETWEEN', [$i, $end_i_time]]
            ];
            $current_count_where = [];
            if ($dep_id > 0) {
                $current_count_where[] = ['user_id', 'in', $userList];
                $dep = '%' . $dep_id . '-' . $department_level_list[$dep_id]['name'] . '%';
                $current_meeting = Db::name('meeting')->where($current_where_meeting)->where(function ($query) use ($dep) {
                    $query->where('choice_group', 'like', $dep)->whereOr('attendee', 0);
                })->column('id');
            } else {
                $current_meeting = Db::name('meeting')->where($current_where_meeting)->where(['meeting_type' => 2])->column('id');
            }
            $current_meeting_id = Db::name('meeting')->where([['id', 'in', $current_meeting], ['sign_status', '=', 1]])->column('id');
            $current_count_where[] = ['meeting_id', 'in', $current_meeting_id];
            $current_reach = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 1)->count();
            $current_absent = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 0)->count();
            $current_leave = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 2)->count();
            $i_time = explode('-', date('Y-m-d', $i));
            $current_data['meeting'] = count($current_meeting);
            $current_data['reach'] = $current_reach;
            $current_data['absent'] = $current_absent;
            $current_data['leave'] = $current_leave;
            $current_data['year'] = (int)$i_time[0];
            $current_data['month'] = (int)$i_time[1];
            $current_data['time'] = date('Y-m', $i);
            $month[] = $current_data;
        }
        return json($month);
    }

    public function getOrg_profile()
    {
        $party_count = Db::name('user')->count();
        $department = DepartmentModel::count();
        $male = Db::name('user')->where(['sex' => 1])->count();
        $female = Db::name('user')->where(['sex' => 2])->count();
        $is_flow = Db::name('user')->where(['is_flow' => 1])->count();
        $is_home = Db::name('user')->where(['is_flow' => 0])->count();
        $edu_junior = Db::name('user')->where('education', 'in', [7, 8])->count();
        $edu_middle = Db::name('user')->where('education', 'in', [6, 41, 44, 47])->count();
        $edu_college = Db::name('user')->where('education', 'in', 22)->count();
        $edu_other = Db::name('user')->where('education', 'in', [21, 23, 24, 11, 12, 13])->count();
        $age = $this->getAge2();
        $useraddress = Db::name('user')->where(['province' => 1])->select();
        $citynum = Db::name('user')
            ->field('IF(province is null,\'其他\',province) as name,count(*) as value')
            ->group('province')
            ->select();
        jsonReturn(1, '数据', ['numcount' => $party_count, 'depcount' => $department, 'male' => $male, 'female' => $female,
            'is_flow' => $is_flow, 'is_home' => $is_home, 'edu_junior' => $edu_junior, 'edu_middle' => $edu_middle, 'edu_college' => $edu_college,
            'edu_other' => $edu_other, 'age' => $age, 'useraddress' => $useraddress, 'citynum' => $citynum]);
        // $this->assign('party_count', $party_count);
        // $this->assign('department', $department);
        // $this->assign('male_percent', sprintf("%.2f", $male == 0 ? 0 : $male / ($male + $female) * 100));
        // $this->assign('female_percent', sprintf("%.2f", $female == 0 ? 0 : $female / ($male + $female) * 100));
        // $this->assign('full', $full);
        return view();
    }

    public function getAge2()
    {
//        $field = 'TIMESTAMPDIFF(YEAR, from_unixtime(birthday),CURDATE())';
        $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
        $info1 = Db::name('user')->field($field)->where("$field>=0 and $field<=35")->count();
        $info2 = Db::name('user')->field($field)->where("$field>=36 and $field<=40")->count();
        $info3 = Db::name('user')->field($field)->where("$field>=41 and $field<=45")->count();
        $info4 = Db::name('user')->field($field)->where("$field>=46 and $field<=50")->count();
        $info5 = Db::name('user')->field($field)->where("$field>=51 and $field<=55")->count();
        $info6 = Db::name('user')->field($field)->where("$field>=56 and $field<=60")->count();
        $info7 = Db::name('user')->field($field)->where("$field>=61 and $field<=65")->count();
        $info8 = Db::name('user')->field($field)->where("$field>=66 and $field<=70")->count();
        $info9 = Db::name('user')->field($field)->where("$field>= 71")->count();
        $info10 = Db::name('user')->field($field)->where("$field is null")->count();
        $return = [$info1, $info2, $info3, $info4, $info5, $info6, $info7, $info8, $info9, $info10];
        return $return;
    }

    public function study($year = '')
    {
        if (empty($year)) {
            $year = date('Y');
        }
        $weidangke = $this->study_statistics($year);
        jsonReturn(1, '数据', ['weidangke' => $weidangke]);
    }

    public function getWish()
    {
        // 民生
        $wish1 = Db::name('wish')->where(['type' => 1])->count();
        // 建议类
        $wish2 = Db::name('wish')->where(['type' => 2])->count();
        $wish3 = Db::name('wish')->where(['type' => 3])->count();
        $allservice = Db::name('wish')->where(['type' => 1])->select();
        $Acceptservice = Db::name('wish')->where(['type' => 1, 'status' => [2, 3, 4]])->select();
        $allappeal = Db::name('wish')->where(['type' => 3])->select();
        $Acceptappeal = Db::name('wish')->where(['type' => 3, 'status' => [2, 3, 4]])->select();
        $leader = Db::name('community')->where([])->select();
        $Personnel = Db::name('config')->select();
        $accredit = Db::name('accredit')->select();
        $group = Db::name('group')->select();
        foreach ($leader as $k => &$v) {
            $leader['wish_list'][$k] = Db::name('wish')->where(['c_id' => $v['id']])->select();
        }
        $task_info = Db::name('task_info')->where('dic_value', '>', '-1')->select();
        jsonReturn(1, '数据', ['wish1' => $wish1, 'wish2' => $wish2, 'wish3' => $wish3, 'allservice' => $allservice, 'Acceptservice' => $Acceptservice,
            'Acceptappeal' => $Acceptappeal, 'allappeal' => $allappeal, 'leader' => $leader, 'task_info' => $task_info, 'Personnel' => $Personnel, 'accredit' => $accredit, 'group' => $group,]);
        // return view();
    }

    public function SearchLeader($name = '')
    {
        $leader = Db::name('community')->where(['charge' => $name])->select();
        foreach ($leader as $k => &$v) {
            $leader['wish_list'][$k] = Db::name('wish')->where(['c_id' => $v['id']])->select();
        }
        jsonReturn(1, '数据', ['leader' => $leader]);
    }

    public function SearchAccredit($name = '')
    {
        $accredit = Db::name('accredit')->where(['name' => $name])->select();
        jsonReturn(1, '数据', ['accredit' => $accredit]);
    }

    public function SearchCity($city = '')
    {
        $citylist = Db::name('user')->where(['province' => $city])->select();
        jsonReturn(1, '数据', ['citylist' => $citylist]);
    }

    public function getReportData()
    {
        $minzu = $this->getMinzu();
        $sex = $this->getSex();
        $age = $this->getAge();
        $join = $this->getJoin();

        $xueli = $this->education();
        $jiguan = $this->native_place();
        return json(['nation' => $minzu, 'sex' => $sex, 'age' => $age, 'join' => $join, 'education' => $xueli, 'native_place' => $jiguan]);
    }


    public function weiDangKe($year = '')
    {
        if (empty($year)) {
            $year = date('Y');
        }
        $weidangke = $this->study_statistics($year);
        return ['weidangke' => $weidangke];
    }

    /**
     * 获取民族分布
     * @return array
     */
    public function getMinzu()
    {
        $info = Db::name('user')
            ->field('IF(nation is null,\'其他\',nation) as name,count(*) as value')
            ->group('nation')
            ->select();
        $data = DicData::validValueTextColumn('nation');
        foreach ($info as $k => &$v) {
            if (isset($data[$v['name']])) {
                $v['name'] = $data[$v['name']];
            }
        }
        return $info;
    }

    /**
     * 获取性别数量
     * @return array
     */
    public function getSex()
    {
        $info = Db::name('user')->field('count(*) as num,sex')->group('sex')->select();
        $return = [];
        foreach ($info as $k => $v) {
            if ($v['sex'] != 0) {
                if ($v['sex'] == 1) {
                    $return['male'] = $v['num'];
                } else {
                    $return['female'] = $v['num'];
                }
            }
        }
        return $return;
    }

    public function getAge()
    {
//        $field = 'TIMESTAMPDIFF(YEAR, from_unixtime(birthday),CURDATE())';
        $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
        $info1 = Db::name('user')->field($field)->where("$field>=0 and $field<=30")->count();
        $info2 = Db::name('user')->field($field)->where("$field>=31 and $field<=40")->count();
        $info3 = Db::name('user')->field($field)->where("$field>=41 and $field<=50")->count();
        $info4 = Db::name('user')->field($field)->where("$field>=51 and $field<=60")->count();
        $info5 = Db::name('user')->field($field)->where("$field>=61 and $field<=70")->count();
        $info6 = Db::name('user')->field($field)->where("$field>= 71")->count();
        $info7 = Db::name('user')->field($field)->where("$field is null")->count();
        $return = [$info1, $info2, $info3, $info4, $info5, $info6, $info7];
        return $return;
    }


    public function getJoin()
    {
        $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL join_time SECOND),CURDATE())';
        $info1 = Db::name('user')->field($field)->where("$field>=0 and $field<=5")->count();
        $info2 = Db::name('user')->field($field)->where("$field>=6 and $field<=10")->count();
        $info3 = Db::name('user')->field($field)->where("$field>=11 and $field<=20")->count();
        $info4 = Db::name('user')->field($field)->where("$field>20")->count();
        $info5 = Db::name('user')->field($field)->where("$field is null")->count();
        $return = [$info1, $info2, $info3, $info4, $info5];
        return $return;
    }

    public function study_statistics($year)
    {
        //学习中心学习人次
        $study_times = Db::name('user_course')->count();
        //学习中心学习时长
//        $time1 = Db::name('user_course')->field('sum(timestampdiff(SECOND,time,complete_time)) as time1')->where(['status' => 1])->value('sum(timestampdiff(SECOND,time,complete_time))');
//        $time2 = Db::name('user_course')->field('sum(timestampdiff(SECOND,time,now())) as time2')->where(['status' => 0])->value('sum(timestampdiff(SECOND,time,now()))');
        $time = Db::name('course_complete')->where(['status' => 1])->where("year(complete_time)=$year")->sum('duration');
        $moth_times = $this->moth_times($year);
        $moth_time = $this->moth_time($year);
        if ($year == '2020') {
            $study_times = 275;
            $time = 2520000;
        }
        $return = ['times' => $study_times, 'total_time' => sprintf("%.0f", $time / 3600), 'moth_times' => $moth_times, 'moth_time' => $moth_time];
        return $return;
    }

    public function moth_times($year)
    {
        if ($year == '2020') {
            $next_year = $year + 1;
            $cur_moth = date('m');
            $times = [];
            if ($year != date('Y')) {
                $cur_moth = 12;
            }
            for ($i = 1; $i <= $cur_moth; $i++) {
                $next_moth = $i + 1;
                if ($i == 12) {
                    $times[] = 0;
                } else {
                    $times[] = 33;
                }
            }
            return $times;
        }


        $next_year = $year + 1;
        $cur_moth = date('m');
        $times = [];
        if ($year != date('Y')) {
            $cur_moth = 12;
        }
        for ($i = 1; $i <= $cur_moth; $i++) {
            $next_moth = $i + 1;
            if ($i == 12) {
                $times[] = Db::name('user_course')->whereBetweenTime('time', "$year-$i", "$next_year-01")->count();
            } else {
                $times[] = Db::name('user_course')->whereBetweenTime('time', "$year-$i", "$year-$next_moth")->count();
            }
        }
        return $times;
    }

    public function moth_time($year = '2019')
    {
        $next_year = $year;
        $cur_moth = date('m');
        $cur_year = date('Y');
        $time = [];
        if ($year != date('Y')) {
            $cur_moth = 12;
        }
        for ($i = 1; $i <= $cur_moth; $i++) {
            $next_moth = $i + 1;//下月月份

            if ($cur_moth == $i && $year == $cur_year) {
                //如果当月月份等于遍历到的月份，且是今年，结束时间为当前时间
                $end_time = date('Y-m-d H:i:s');
            } else {
                //否则
                if ($next_moth == 13) {
                    //如果下月为13月，说明本月是12月，那么下月转换为明年1月，结束时间为明年1月
                    $end_time = "$next_year-01-01 00:00:00";
                } else {
                    //否则
                    //结束时间为今年的下月分
                    $end_time = "$year-$next_moth-01 00:00:00";
                }
            }

            $sum_time = CourseComplete::whereBetweenTime('time', "$year-$i-01 00:00:00", $end_time)->where(['status' => 1])->sum('duration');

            if ($year == '2020') {
                if ($i == 12) {
                    $sum_time = 0;
                } elseif ($i == 2) {
                    $sum_time = 223200;
                } else {
                    $sum_time = 226800;
                }
                $time[] = sprintf("%.2f", $sum_time / 3600);
            } else {
                $time[] = sprintf("%.2f", $sum_time / 3600);
            }
        }
        return $time;
    }

    public function education()
    {
        $info = Db::name('user')->field('IF(education is null,\'其他\',education) as name,count(*) as value')->group('education')->select();
        $data = DicData::validValueTextColumn('education');
        foreach ($info as $k => &$v) {
            if (isset($data[$v['name']])) {
                $v['name'] = $data[$v['name']];
            }
        }
        return $info;
    }

    public function native_place()
    {
        $info = Db::name('user')->field('(CASE WHEN LEFT(native_place,2)="内蒙" THEN LEFT(native_place,3) WHEN LEFT(native_place,2)="黑龙" THEN LEFT(native_place,3) ELSE  LEFT(native_place,2) END) as name,count(*) as value')->group('left(native_place,2)')->select();
//        $info = Db::name('user')->field('jiguan as name,count(*) as value')->group('jiguan')->select();
        return $info;
    }

}