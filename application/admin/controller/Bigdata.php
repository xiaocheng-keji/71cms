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
use app\admin\controller\AdminBase;
use app\common\model\Department as DepartmentModel;
use app\common\model\DicData;
use think\Db;
use think\response\Json;


/**
 * 后台首页
 * Class bigData
 * @package app\admin\controller
 */

class Bigdata
{
    
	public function getBaseCount(){
		// $depCount = Db::name('department')->count();
		$depCount = Db::name('department')->where(['type'=>631])->count();
		$userCount = Db::name('user')->count();
		$male = Db::name('user')->where(['sex'=> '1'])->count();
		$female = Db::name('user')->where(['sex'=> '2'])->count();
		jsonReturn(1, '数据', ['depCount'=>$depCount,'userCount'=>$userCount,'male'=>$male,'female'=>$female]);
	}
	public function getNation()
	    {
	        // $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
	        $han = Db::name('user')->where(['nation' => '01'])->count();
			$minority = Db::name('user')->where('nation', '<>', '01')->count();
	        jsonReturn(1, '数据', ['han'=>$han,'minority'=>$minority]);
	    }
	public function getBigSource()
		 {
		        // $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
		    $han = Db::name('add_source')->where(['status' => '1'])->select();
		    jsonReturn(1, '数据', ['data'=>$han]);
		}
		
	public function getNews()
	    {
	        // $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
	        $han = Db::name('article')->where(['cat_id' => '1068'])->order('id', 'desc')->select();
	        jsonReturn(1, '数据', ['news'=>$han]);
	    }
	public function getAges()
	    {
	        $field = 'TIMESTAMPDIFF(YEAR, DATE_ADD(FROM_UNIXTIME(0), INTERVAL birthday SECOND),CURDATE())';
	        $info1 = Db::name('user')->field($field)->where("$field>=20 and $field<25")->count();
	        $info2 = Db::name('user')->field($field)->where("$field>=25 and $field<30")->count();
	        $info3 = Db::name('user')->field($field)->where("$field>=30 and $field<35")->count();
	        $info4 = Db::name('user')->field($field)->where("$field>=35 and $field<40")->count();
			$info5 = Db::name('user')->field($field)->where("$field>=40 and $field<45")->count();
	        $info6 = Db::name('user')->field($field)->where("$field>= 45")->count();
	        $info10 = Db::name('user')->field($field)->where("$field is null")->count();
	        $return = [$info1, $info2, $info3, $info4, $info5,$info6];
	        jsonReturn(1, '数据',  $return);
	    }
		public function getPartyAge()
		{
		    $join = $this->getJoin();
		    jsonReturn(1, '数据',$join);
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
		public function getNativeCount()
		    {
		         $jiguan = $this->native_place();
		        jsonReturn(1, '数据',  $jiguan);
		    }
		public function native_place()
		    {
		        $info = Db::name('user')->field('(CASE WHEN LEFT(native_place,2)="内蒙" THEN LEFT(native_place,3) WHEN LEFT(native_place,2)="黑龙" THEN LEFT(native_place,3) ELSE  LEFT(native_place,2) END) as name,count(*) as value')->group('left(native_place,2)')->select();
		//        $info = Db::name('user')->field('jiguan as name,count(*) as value')->group('jiguan')->select();
		        return $info;
		    }
		public function getJob()
		    {
				$info = Db::name('user')->field('IF(job is null,\'其他\',job) as name,count(*) as value')->group('job')->select();
				$data = DicData::validValueTextColumn('job');
				foreach ($info as $k => &$v) {
				    if (isset($data[$v['name']])) {
				        $v['name'] = $data[$v['name']];
				    }
				}
				jsonReturn(1, '数据',  $info);
		        // $num = Db::name('user')
		        // ->field('IF(job is null,\'其他\',job) as name,count(*) as value')
		        // ->group('job')
		        // ->select();
		        // jsonReturn(1, '数据',  $num);
		    }	
		
		public function getEducation()
		{
		    $info = Db::name('user')->field('IF(education is null,\'其他\',education) as name,count(*) as value')->group('education')->select();
		    $data = DicData::validValueTextColumn('education');
		    foreach ($info as $k => &$v) {
		        if (isset($data[$v['name']])) {
		            $v['name'] = $data[$v['name']];
		        }
		    }
		    jsonReturn(1, '数据',  $info);
		}
			// public function statisticsStudy($year = '')
			// {
			//     if (empty($year)) {
			//         $year = date('Y');
			//     }
			//     $weidangke = $this->study_statistics($year);
			// 	jsonReturn(1, '数据',['weidangke' => $weidangke]);
			// }
		public function getWeather($url)
		{
            // 定义白名单URL
            $allowedUrls = [
                'https://wis.qq.com/weather/common',
                // 可以添加其他允许的URL
            ];

            // 检查URL是否在白名单中
            if (!in_array($url, $allowedUrls)) {
                jsonReturn(-1, '不允许的 URL');
            }
			$curl = curl_init(); // 启动一个CURL会话
			curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
			curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
			//curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
			curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
			curl_setopt($curl, CURLOPT_POSTFIELDS, 'source=pc&weather_type=observe&province=江苏省&city=连云港市'); // Post提交的数据包x
			curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制 防止死循环
			curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
			
			$tmpInfo = curl_exec($curl); // 执行操作
			if(curl_errno($curl))
			{
			echo 'Errno'.curl_error($curl);//捕抓异常
			}
			curl_close($curl); // 关闭CURL会话
			return $tmpInfo; // 返回数据
		}
		public function statisticsStudy($year)
		    {
		        //学习中心学习人次
		        $study_times = Db::name('article_read')->where('point', '>',0)->count();
                //总人数
                $total = Db::name('user')->where('status',1)->count();
		        //学习中心学习时长
		//        $time1 = Db::name('user_course')->field('sum(timestampdiff(SECOND,time,complete_time)) as time1')->where(['status' => 1])->value('sum(timestampdiff(SECOND,time,complete_time))');
		//        $time2 = Db::name('user_course')->field('sum(timestampdiff(SECOND,time,now())) as time2')->where(['status' => 0])->value('sum(timestampdiff(SECOND,time,now()))');
		        $time = Db::name('course_complete')->where(['status' => 1])->where("year(complete_time)=$year")->sum('duration');
		        $moth_times = $this->moth_times($year);
		        // $moth_time = $this->moth_time($year);
		        if ($year == '2020') {
		            $study_times = 275;
		            $time = 2520000;
		        }
				jsonReturn(1, '数据',['peopleTotal' => $total,'peopleNum' => $study_times, 'studyTime' => sprintf("%.0f", $time / 3600), 'month' => $moth_times, 'moth_time' => $moth_time]);
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
			            $times[] = Db::name('article_read')->whereBetweenTime('create_time', "$year-$i", "$next_year-01")->where('point', '>',0)->count();
			        } else {
			            $times[] = Db::name('article_read')->whereBetweenTime('create_time', "$year-$i", "$year-$next_moth")->where('point', '>',0)->count();
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
		            } elseif($i==2){
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

    /**
     * @return Json
     * @throws \think\exception\DbException
     * 获取图书借阅情况,借阅总数，已还数量和占比,未还数量和占比
     */
    public function getBorrwoInfo(){
        $data = [];
		$ans_num = 0;
        $data['total'] = Db::name('books')->where(['status'=>1])->sum('inventory');
		// foreach ($data['total'] as $k => $v) {
		//    $ans_num = $ans_num + $data['total'][$k]["inventory"];
		// }
		// $data['total'] = $ans_num;
        // $data['return'] = Db::name('borrow')->where(['status'=>1])->count();
        // $data['return_rate'] = sprintf("%.2f", $data['return']/$data['total']*100);
        $data['not_return'] = Db::name('borrow')->where(['status'=>1])->count();
        // $data['not_return_rate'] = sprintf("%.2f", $data['not_return']/$data['total']*100);
        return jsonReturn(1,'success',$data);
    }

    /**
     * @return Json
     * @throws \think\exception\DbException
     * 根据季度获取各支部的三会一课情况占比，默认今年
     */
    public function getMeetingByBranch(){
        $year = input('year',date('Y'));
        //季度,默认当前季度
        $quarter = input('quarter',ceil(date('m')/3));
        //获取季度的开始时间和结束时间
        $time = $this->getQuarterTime($quarter,$year);
        //获取支部ID列表，type为631
        $branch = Db::name('department')->where(['type'=>631])->column('id');
        $where[] = ['start_time','between time',[$time['start_time'],$time['end_time']]];
        $where[] = ['meeting_type','=',2];
        foreach ($branch as $k=>$v){
            //获取支部下级部门ID列表
            $branch_children = Db::name('department')->where([['parent_id','in',$v]])->column('id');
            //包含自己
            $branch_children[] = $v;
            $where[2] = ['publish','in',$branch_children];
            $data['list'][$k]['name'] = Db::name('department')->where(['id'=>$v])->value('short_name');
            //支部党员大会
            $data['list'][$k]['party_meeting'] = Db::name('meeting')->where($where)->where(['cat_id'=>97])->count();
            //支部委员会
            $data['list'][$k]['committee'] = Db::name('meeting')->where($where)->where(['cat_id'=>98])->count();
            //党小组会
            $data['list'][$k]['group_meeting'] = Db::name('meeting')->where($where)->where(['cat_id'=>99])->count();
            //党课
            $data['list'][$k]['party_class'] = Db::name('meeting')->where($where)->where(['cat_id'=>85])->count();
            //总数
//            $data['list'][$k]['total'] = $data['list'][$k]['party_meeting']+$data['list'][$k]['committee']+$data['list'][$k]['group_meeting']+$data['list'][$k]['party_class'];
        }
        $total_where[] = ['start_time','between time',[$time['start_time'],$time['end_time']]];
        $total_where[] = ['meeting_type','=',2];
        $data['party_meeting_total'] = Db::name('meeting')->where($total_where)->where(['cat_id'=>97])->count();
        $data['committee_total'] = Db::name('meeting')->where($total_where)->where(['cat_id'=>98])->count();
        $data['group_meeting_total'] = Db::name('meeting')->where($total_where)->where(['cat_id'=>99])->count();
        $data['party_class_total'] = Db::name('meeting')->where($total_where)->where(['cat_id'=>85])->count();
        return jsonReturn(1,'success',$data);
    }

    /**
     * @return Json
     * @throws \think\exception\DbException
     * 获取各支部各季度的活动开展次数,默认今年
     */
    public function getActivityByBranch(){
        $year = input('year',date('Y'));
        //获取支部ID列表，type为631
        $branch = Db::name('department')->where(['type'=>631])->column('id');
        //活动的类型
        $where[] = ['meeting_type','=',3];
        //季度
        $quarter_where[0] = ['start_time','between time',[$this->getQuarterTime(1,$year)['start_time'],$this->getQuarterTime(1,$year)['end_time']]];
        $quarter_where[1] = ['start_time','between time',[$this->getQuarterTime(2,$year)['start_time'],$this->getQuarterTime(2,$year)['end_time']]];
        $quarter_where[2] = ['start_time','between time',[$this->getQuarterTime(3,$year)['start_time'],$this->getQuarterTime(3,$year)['end_time']]];
        $quarter_where[3] = ['start_time','between time',[$this->getQuarterTime(4,$year)['start_time'],$this->getQuarterTime(4,$year)['end_time']]];
        foreach ($branch as $k=>$v){
            //获取支部下级部门ID列表
            $branch_children = Db::name('department')->where([['parent_id','in',$v]])->column('id');
            //包含自己
            $branch_children[] = $v;
            $where[5] = ['publish','in',$branch_children];
            $data['list'][$k]['name'] = Db::name('department')->where(['id'=>$v])->value('short_name');
            //第一季度
            $data['list'][$k]['first'] = Db::name('meeting')->where($where)->where([$quarter_where[0]])->count();
            //第二季度
            $data['list'][$k]['second'] = Db::name('meeting')->where($where)->where([$quarter_where[1]])->count();
            //第三季度
            $data['list'][$k]['third'] = Db::name('meeting')->where($where)->where([$quarter_where[2]])->count();
            //第四季度
            $data['list'][$k]['fourth'] = Db::name('meeting')->where($where)->where([$quarter_where[3]])->count();
        }
        return jsonReturn(1,'success',$data);
    }

    /**
     * @return array
     * @throws \think\exception\DbException
     * 获取季度的开始时间和结束时间
     */
    function getQuarterTime($quarter,$year){
        $time = [];
        switch ($quarter){
            case 1:
                $time['start_time'] = $year.'-01-01 00:00:00';
                $time['end_time'] = $year.'-03-31 23:59:59';
                break;
            case 2:
                $time['start_time'] = $year.'-04-01 00:00:00';
                $time['end_time'] = $year.'-06-30 23:59:59';
                break;
            case 3:
                $time['start_time'] = $year.'-07-01 00:00:00';
                $time['end_time'] = $year.'-09-30 23:59:59';
                break;
            case 4:
                $time['start_time'] = $year.'-10-01 00:00:00';
                $time['end_time'] = $year.'-12-31 23:59:59';
                break;
        }
        return $time;
    }
}