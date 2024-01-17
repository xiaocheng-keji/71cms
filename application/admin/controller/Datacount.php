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
use app\common\model\Department as DepartmentModel;
use app\common\model\DicData;
use think\Db;
use app\common\model\TmpSession;

/**
 * 会议统计
 * @package app\admin\controller
 */
class Datacount extends AdminBase
{
    /**
     * 会议统计面板
     *
     * @param string $date
     * @param int $dep_id
     * @param int $is_ajax
     */
    public function meetingList($date = '', $dep_id = 0, $is_ajax = 0)
    {
        //指定添加子级组织的时候不能选其他组织
        $auth_dep_arr = TmpSession::getDepAuth();
        $department_options = DepartmentModel::titleTreeArray();
        $department_options = [0 => '全部（权限下的）'] + $department_options;
        foreach ($department_options as $k => $v) {
            $department_options[$k] = [
                'title' => $v,
                'disabled' => $k > 0 && (!in_array($k, $auth_dep_arr))
            ];
        }
        $default_dep_id = 0;
        $dep_id = $dep_id > 0 ? $dep_id : $default_dep_id;
        $this->assign('dep_id', $dep_id);

        if (empty($date)) {
            $current_month_start = date('Y-m-01');
            $start_time = strtotime(date('Y-01-01'));
            $end_time = strtotime("{$current_month_start} +1 month") - 1;
            $date = date('Y-m', $start_time) . ' - ' . date('Y-m', $end_time);
        } else {
            $date_arr = explode(' - ', $date);
            $start_time = strtotime($date_arr[0]);
            $end_time = strtotime($date_arr[1] . " +1 month") - 1;
        }

        //获取会议栏目
        $category_model = new \app\common\model\Category();
        $cat_list = $category_model->where('type', 'in', [2])->select();

        $month = [];
        for ($i = $start_time; $i < $end_time; $i = strtotime(date('Y-m-d', $i) . " +1 month")) {
            $end_i_time = strtotime(date('Y-m-d', $i) . " +1 month") - 1;
            $current_where_meeting = [];
            $current_where_meeting[] = ['start_time', 'BETWEEN', [$i, $end_i_time]];
            $current_where_meeting[] = ['meeting_type', '=', 2];
            $current_where_meeting[] = ['deleted', '=', 0];
            $current_where_meeting[] = ['publish', 'in', TmpSession::getDepAuth()];
            $current_count_where = [];
            if ($dep_id > 0) {
                $current_where_meeting[] = ['publish', '=', $dep_id];
            }
            $current_meeting = Db::name('meeting')->where($current_where_meeting)->column('cat_id', 'id');
            $current_count_where[] = ['meeting_id', 'in', array_keys($current_meeting)];
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
            //计算每个会议栏目下的会议数量
            foreach ($cat_list as $k => $v) {
                $current_data['cats'][$v['id']]['name'] = $v['cat_name'];
                $current_data['cats'][$v['id']]['num'] = 0;
            }
            foreach ($current_meeting as $k => $cat) {
                $cat_array = explode(',', $cat);
                foreach ($cat_array as $v) {
                    isset($current_data['cats'][$v]['num']) && $current_data['cats'][$v]['num']++;
                }
            }
            $current_data['meeting'] = array_sum(array_column($current_data['cats'], 'num'));
            $month[] = $current_data;
        }
        $month = array_reverse($month);
        if ($is_ajax == 1) {
            return json($month);
        }

        $total['meeting'] = array_sum(array_column($month, 'meeting'));
        $total['reach'] = array_sum(array_column($month, 'reach'));
        $total['absent'] = array_sum(array_column($month, 'absent'));
        $total['leave'] = array_sum(array_column($month, 'leave'));

        $this->assign('dep_options', $department_options);
        $this->assign('date', $date);
        $this->assign('total', $total);
        $this->assign('month', $month);

        return view('datacount/meeting_list');
    }

    /**
     * @param int $time
     * @param int $dep_id
     * @param int $meeting_type
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查看组织的会议列表
     */
    public function meeting_month($time = 0, $dep_id = 0, $meeting_type = 2)
    {
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addPageWhere(['time' => $time, 'dep_id' => $dep_id, 'meeting_type' => $meeting_type]);

        if (\think\facade\Request::isAjax()) {
            if (empty($time)) {
                $time = strtotime(date('Y-m-01'));
            }
            $start_time = strtotime($time);
            $end_time = strtotime("{$time} +1 month") - 1;
            $where[] = ['a.start_time', 'BETWEEN', [$start_time, $end_time]];
            $where[] = ['a.meeting_type', '=', $meeting_type];
            $where[] = ['a.deleted', '=', 0];
            $where[] = ['a.publish', 'in', TmpSession::getDepAuth()];
            if ($dep_id > 0) {
                $where[] = ['a.publish', '=', $dep_id];
            }
            $count = Db::name('meeting')
                ->alias('a')
                ->where($where)
                ->count();
            $list = Db::name('meeting')
                ->alias('a')
                ->field('a.id, a.theme, a.place, a.start_time, a.end_time, a.add_time, a.sign_status, a.cat_id')
                ->where($where)
                ->order('a.id desc')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => &$item) {
                $item['cat_name'] = Db::name('category')->where('id', 'in', explode(',', $item['cat_id']))->column('cat_name');
                $item['meeting_time'] = date('Y-m-d H:i', $item['start_time']) . ' - ' . date('Y-m-d H:i', $item['end_time']);
                if ($item['start_time'] > time()) {
                    $status = '未开始';
                } else if ($item['start_time'] < time() && $item['end_time'] > time()) {
                    $status = '进行中';
                } else {
                    $status = '已结束';
                }
                $item['status'] = $status;
                $wheres = [];
                $wheres[] = ['meeting_id', '=', $item['id']];
                if ($item['sign_status'] == 1) {
                    $item['absent'] = Db::name('meeting_user')->where($wheres)->where('sign_status', 0)->count();
                    $item['reach'] = Db::name('meeting_user')->where($wheres)->where('sign_status', 1)->count();
                    $item['leave'] = Db::name('meeting_user')->where($wheres)->where('sign_status', 2)->count();
                } else {
                    $item['absent'] = 0;
                    $item['reach'] = 0;
                    $item['leave'] = 0;
                }
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('返回', 'javascript:window.history.back()')
            ->addField('栏目', 'cat_name', 'text')
            ->addField('主题', 'theme', 'text')
            ->addField('会议时间', 'meeting_time', 'text')
            ->addField('地点', 'place', 'text')
            ->addField('状态', 'status', 'text', ['width' => 80])
            ->addField('开始时间', 'start_time', 'text', ['hide' => true])
            ->addRowBtn('详情', url('meeting/detail'), 'barDemo', ['field' => 'start_time', 'operator' => '\<', 'value' => time()], 'btn-warm', '', ['open_type' => 'layer'])
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 80
            ]);
        return $modelHelper->showList();
    }

    public function meetingExport2()
    {
        $objExcel = new \PHPExcel();
        $objExcel->getProperties();
        $objExcel->getActiveSheet()->setCellValue('A1', '全部组织');
        $objExcel->getActiveSheet()->setCellValue('B1', '会议总数');
        $objExcel->getActiveSheet()->setCellValue('C1', '实到人数');
        $objExcel->getActiveSheet()->setCellValue('D1', '缺席人数');
        $objExcel->getActiveSheet()->setCellValue('E1', '请假人数');
        // dump($objExcel);die;
        // $callStartTime = microtime(true);
        $PHPWriter = \PHPExcel_IOFactory::createWriter($objExcel, "Excel2007");
        ob_end_clean(); // Added by me
        ob_start(); // Added by me
        header('Content-Disposition: attachment;filename="表单数据.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }

    /**
     * 导出Excel
     *
     * @param string $date
     * @param int $dep_id
     */
    public function meetingExport($date = '', $dep_id = 0)
    {
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        ini_set("memory_limit", "1024M"); // 设置php可使用内存
        if (empty($date)) {
            $current_month_start = date('Y-m-01');
            $start_time = strtotime("{$current_month_start} -11 month");
            $end_time = strtotime("{$current_month_start} +1 month") - 1;
        } else {
            $date_arr = explode(' - ', $date);
            $start_time = strtotime($date_arr[0]);
            $end_time = strtotime($date_arr[1] . " +1 month") - 1;
        }
        $meeting_where = [
            ['start_time', 'BETWEEN', [$start_time, $end_time]],
            ['meeting_type', '=', 2],
            ['tenant_id', '=', TENANT_ID],
            ['deleted', '=', 0],
            ['publish', 'in', TmpSession::getDepAuth()],
        ];
        $count_where = [];
        if ($dep_id > 0) {
            $dep_name = Db::name('department')->where('id', $dep_id)->value('name');
            $meeting_where[] = ['publish', '=', $dep_id];
        }
        $meeting = Db::name('meeting')->where($meeting_where)->column('id');
        $meeting_id = Db::name('meeting')->where([['id', 'in', $meeting]])->column('id');
        $count_where[] = ['meeting_id', 'in', $meeting_id];
        $total['meeting'] = count($meeting);
        $total['reach'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 1)->count();
        $total['absent'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 0)->count();
        $total['leave'] = Db::name('meeting_user')->where($count_where)->where('sign_status', 2)->count();

        $objExcel = new \PHPExcel();

        $objExcel->getProperties();
        $objExcel->getActiveSheet()->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getActiveSheet()->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1', $dep_id > 0 ? $dep_name : '全部组织');
        $objExcel->getActiveSheet()->setCellValue('B1', '会议总数');
        $objExcel->getActiveSheet()->setCellValue('C1', '实到人数');
        $objExcel->getActiveSheet()->setCellValue('D1', '缺席人数');
        $objExcel->getActiveSheet()->setCellValue('E1', '请假人数');
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $j = 1;
        for ($i = $start_time; $i < $end_time; $i = strtotime(date('Y-m-d', $i) . " +1 month")) {
            $objExcel->setActiveSheetIndex(0);
            $j++;
            $end_i_time = strtotime(date('Y-m-d', $i) . " +1 month") - 1;
            $current_where_meeting = [
                ['start_time', 'BETWEEN', [$i, $end_i_time]],
                ['meeting_type', '=', 2],
                ['deleted', '=', 0],
                ['publish', 'in', TmpSession::getDepAuth()]
            ];
            $current_count_where = [];
            if ($dep_id > 0) {
                $current_where_meeting[] = ['publish', 'in', $dep_id];
            }
            $current_meeting = Db::name('meeting')->where($current_where_meeting)->column('id');
            $current_meeting_id = Db::name('meeting')->where([['id', 'in', $current_meeting]])->column('id');
            $current_count_where[] = ['meeting_id', 'in', $current_meeting_id];
            $current_reach = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 1)->count();
            $current_absent = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 0)->count();
            $current_leave = Db::name('meeting_user')->where($current_count_where)->where('sign_status', 2)->count();

            $objExcel->getActiveSheet()->setCellValue('A' . $j, date('Y年m月', $i))
                ->setCellValue('B' . $j, count($current_meeting))
                ->setCellValue('C' . $j, $current_reach)
                ->setCellValue('D' . $j, $current_absent)
                ->setCellValue('E' . $j, $current_leave);

            $objExcel->createSheet();
            $objExcel->setActiveSheetIndex(($j - 1));
            $objExcel->getActiveSheet()->setTitle(date('Y年m月', $i));

            $objExcel->getActiveSheet()->setCellValue('A1', '会议主题')
                ->setCellValue('B1', '栏目')
                ->setCellValue('C1', '会议地点')
                ->setCellValue('D1', '会议状态')
                ->setCellValue('E1', '会议时间')
                ->setCellValue('F1', '添加时间')
                ->setCellValue('G1', '实到人数')
                ->setCellValue('H1', '缺少人数')
                ->setCellValue('I1', '请假人数');
            $key = 1;
            foreach ($current_meeting as $k => $v) {
                $current_meeting_where = [];
                $key++;
                $meeting_info = Db::name('meeting')->alias('a')->join('category c', 'a.cat_id = c.id')->field('a.id, a.theme, a.place, a.start_time, a.end_time, a.add_time, a.sign_status, c.cat_name')->where('a.id', $v)->find();
                $current_meeting_where[] = ['meeting_id', '=', $v];
                if ($meeting_info['sign_status'] == 1) {
                    $meeting_info['absent'] = Db::name('meeting_user')->where($current_meeting_where)->where('sign_status', 0)->count();
                    $meeting_info['reach'] = Db::name('meeting_user')->where($current_meeting_where)->where('sign_status', 1)->count();
                    $meeting_info['leave'] = Db::name('meeting_user')->where($current_meeting_where)->where('sign_status', 2)->count();
                } else {
                    $meeting_info['absent'] = 0;
                    $meeting_info['reach'] = 0;
                    $meeting_info['leave'] = 0;
                }
                if ($meeting_info['start_time'] > time()) {
                    $meeting_info['meeting_status'] = '未开始';
                } else if ($meeting_info['start_time'] < time() && $meeting_info['end_time'] > time()) {
                    $meeting_info['meeting_status'] = '进行中';
                } else {
                    $meeting_info['meeting_status'] = '已结束';
                }
                $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                $objExcel->getActiveSheet()->setCellValue('A' . $key, $meeting_info['theme'])
                    ->setCellValue('B' . $key, $meeting_info['cat_name'])
                    ->setCellValue('C' . $key, $meeting_info['place'])
                    ->setCellValue('D' . $key, $meeting_info['meeting_status'])
                    ->setCellValue('E' . $key, date('Y-m-d H:i:s', $meeting_info['start_time']) . ' - ' . date('Y-m-d H:i:s', $meeting_info['end_time']))
                    ->setCellValue('F' . $key, date('Y-m-d H:i:s', $meeting_info['add_time']))
                    ->setCellValue('G' . $key, $meeting_info['reach'])
                    ->setCellValue('H' . $key, $meeting_info['absent'])
                    ->setCellValue('I' . $key, $meeting_info['leave']);
            }
        }
        $objExcel->setActiveSheetIndex(0);
        $j++;
        $objExcel->getActiveSheet()->setCellValue('A' . $j, '总数')
            ->setCellValue('B' . $j, $total['meeting'])
            ->setCellValue('C' . $j, $total['reach'])
            ->setCellValue('D' . $j, $total['absent'])
            ->setCellValue('E' . $j, $total['leave']);

        $objExcel->getActiveSheet()->setTitle('会议统计');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, "Excel2007");
        $filename = '会议统计' . date('Y-m', $start_time) . '-' . date('Y-m', $end_time) . '.xlsx';
        $filename = urlencode($filename);
        ob_end_clean();
        ob_start();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // 07Excel
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"'); // filename
        header('Cache-Control: max-age=0'); // forbid cached
        header('Pragma: public');
        try {
            $objWriter->save('php://output');
        } catch (Exception $e) {
            dump($e);
        }

    }


    /**
     * 组织概况
     * @return \think\response\View
     */
    public function org_profile()
    {
        $party_count = Db::name('user')->count();
        $department = DepartmentModel::count();
        $this->assign('party_count', $party_count);
        $this->assign('department', $department);
        return view();
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


    /**
     * 获取民族分布
     * @return array
     */
    public function getMinzu()
    {
        $info = Db::name('user')->field('IF(nation is null,\'其他\',nation) as name,count(*) as value')->group('nation')->select();
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
//        $field = 'TIMESTAMPDIFF(YEAR, from_unixtime(join_time),CURDATE())';
        $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL join_time SECOND),CURDATE())';
        $info1 = Db::name('user')->field($field)->where("$field>=0 and $field<=5")->count();
        $info2 = Db::name('user')->field($field)->where("$field>=6 and $field<=10")->count();
        $info3 = Db::name('user')->field($field)->where("$field>=10 and $field<=20")->count();
        $info4 = Db::name('user')->field($field)->where("$field>20")->count();
        $info5 = Db::name('user')->field($field)->where("$field is null")->count();
        $return = [$info1, $info2, $info3, $info4, $info5];
        return $return;
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