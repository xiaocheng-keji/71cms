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

use think\Model;

class TaskList extends Model
{
    public  static  $date_format= 'Y-m-d';
    public static $time_format = 'H:i';
    public  static  $TASK_TYPE_DEVELOP = 1;  //党员发展类型
    public  static  $STATUS_FINISH = 4;



    protected $_validate = array(
    );

    //提醒频次
    public static $frequency = [
        0 => '指定一次',
        1 => '每天一次',
        2 => '每周一次',
        3 => '每月一次',
        4 => '每季度一次',
        5 => '每半年一次',
        6 => '每年一次',
    ];



    /**
     * 编写定时任务脚本，每年初需要重新启动
     */
    public function writeShellScript(){
        $scriptText = "";
        $list = self::useGlobalScope(false)->field('id,at_time,at_date,frequency')->where(['status'=>1])->select();

        foreach($list as $item){
            $apiUrl = SITE_URL.url('cli/task_info/timeTrigger',['id'=>$item['id']]);
            $month = date('n',$item->getData('at_date'));
            $day = date('j',$item->getData('at_date'));
            $hour = date('G',$item->getData('at_time'));
            $min = date('i',$item->getData('at_time'));
            switch ($item['frequency']){
                case '指定一次'://默认一次
                    $scriptText .= "$min $hour $day $month * curl $apiUrl\n";
                    break;
                case '每天一次'://每天
                    $scriptText .= "$min $hour * * * curl $apiUrl\n";
                    break;
                case '每周一次'://每周
                    $n = date('N',$item->getData('at_date'));
                    $scriptText .= "$min $hour * * $n curl $apiUrl\n";
                    break;
                case '每月一次'://每月，需要判断月末
                    $scriptText .= $this->calculatePerMonth($month,$day,$hour,$min,$apiUrl);
                    break;
                case '每季度一次'://每季度，需要判断月末
                    $scriptText .= $this->calculatePerQuarter($month,$day,$hour,$min,$apiUrl);
//                    $scriptText .= "0 0 1 1,4,7,10 * curl $apiUrl\n";
                    break;
                case '每半年一次'://每半年，需要判断月末
                    $scriptText .= $this->calculateHalfYear($month,$day,$hour,$min,$apiUrl);
//                    $scriptText .= "$min $hour $day */6 * curl $apiUrl\n";
                    break;
                case '每年一次'://每年，需要判断2月月末
                    $scriptText .= "$min $hour $day $month * curl $apiUrl\n";
                    break;
            }
        }
        file_put_contents('crontab.sh',$scriptText);
        exec("chmod 744 crontab.sh");
        exec('crontab -r');
        exec('crontab crontab.sh');
    }

    /**
     * 计算每月
     * @param $month
     * @param $day
     * @param $hour
     * @param $min
     * @param $apiUrl
     * @return string
     */
    public function calculatePerMonth($month,$day,$hour,$min,$apiUrl){
        $scriptText = '';
        if($day>28){
            //计算今年2月有多少天
            $t = date('t',strtotime(date('Y').'-02'));
            //2月末
            $scriptText .= "$min $hour $t 2 * curl $apiUrl\n";
            if($day>30){
                if(in_array($month,[1,3,5,7,8,'01','03','05','07','08',10,12])){
                    $scriptText .= "$min $hour 31 1,3,5,7,8,10,12 * curl $apiUrl\n";
                    $scriptText .= "$min $hour 30 4,6,9,11 * curl $apiUrl\n";
                }else{
                    $scriptText .= "$min $hour $day * * curl $apiUrl\n";
                }
            }else{
                $monthArray30 = [];
                $monthArray31 = [];
                for ($i=0;$i<4;$i++){
                    $next = $month+$i*3;
                    if($next>12){
                        $next = $next-12;
                    }
                    if(in_array($next,[1,3,5,7,8,10,12,'01','03','05','07','08'])){
                        array_push($monthArray31,$next);
                    }elseif(in_array($next,[4,6,9,11,'04','06','09'])){
                        array_push($monthArray30,$next);
                    }
                }
                sort($monthArray30);
                sort($monthArray31);
                $monthText30 = implode(',',$monthArray30);
                $monthText31 = implode(',',$monthArray31);
                $scriptText .= "$min $hour 30 $monthText30 * curl $apiUrl\n";
                $scriptText .= "$min $hour $day $monthText31 * curl $apiUrl\n";
            }
        }else{
            $scriptText .= "$min $hour $day * * curl $apiUrl\n";
        }
        return $scriptText;
    }

    /**
     * 计算每季度
     * @param $month
     * @param $day
     * @param $hour
     * @param $min
     * @param $apiUrl
     * @return string
     */
    public function calculatePerQuarter($month,$day,$hour,$min,$apiUrl){
        $scriptText = '';
        if($day>28){
            if(in_array($month,[11,8,5,2,'08','05','02'])){
                //计算今年2月有多少天
                $t = date('t',strtotime(date('Y').'-02'));
                //2月末
                $scriptText .= "$min $hour $t 2 * curl $apiUrl\n";
            }
            if($day>30){
                $monthArray30 = [];
                $monthArray31 = [];
                for ($i=0;$i<4;$i++){
                    $next = $month+$i*3;
                    if($next>12){
                        $next = $next-12;
                    }
                    if(in_array($next,[1,3,5,7,8,10,12,'01','03','05','07','08'])){
                        array_push($monthArray31,$next);
                    }elseif(in_array($next,[4,6,9,11,'04','06','09'])){
                        array_push($monthArray30,$next);
                    }
                }
                sort($monthArray30);
                sort($monthArray31);
                $monthText30 = implode(',',$monthArray30);
                $monthText31 = implode(',',$monthArray31);
                $scriptText .= "$min $hour 30 $monthText30 * curl $apiUrl\n";
                $scriptText .= "$min $hour $day $monthText31 * curl $apiUrl\n";
            }else{
                $monthArray = [];
                for ($i=0;$i<4;$i++){
                    $next = $month+$i*3;
                    if($next>12){
                        $next = $next-12;
                    }
                    if($next != 2){
                        array_push($monthArray,$next);
                    }
                }
                sort($monthArray);
                $monthText = implode(',',$monthArray);
                $scriptText .= "$min $hour $day $monthText * curl $apiUrl\n";
            }
        }else{
            $monthArray = [];
            for ($i=0;$i<4;$i++){
                $next = $month+$i*3;
                if($next>12){
                    $next = $next-12;
                }
                array_push($monthArray,$next);
            }
            sort($monthArray);
            $monthText = implode(',',$monthArray);
            $scriptText .= "$min $hour $day $monthText * curl $apiUrl\n";
        }
        return $scriptText;
    }

    /**
     * 计算每半年
     * @param $month
     * @param $day
     * @param $hour
     * @param $min
     * @param $apiUrl
     * @return string
     */
    public function calculateHalfYear($month,$day,$hour,$min,$apiUrl){
        $scriptText = '';
        if($day>28){
            if(in_array($month,[2,8,'02','08'])){
                //计算今年2月有多少天
                $t = date('t',strtotime(date('Y').'-02'));
                //2月末
                $scriptText .= "$min $hour $t 2 * curl $apiUrl\n";
                $scriptText .= "$min $hour $day 8 * curl $apiUrl\n";
            }else{
                if($day>30){
                    $monthArray30 = [];
                    $monthArray31 = [];
                    for ($i=0;$i<2;$i++){
                        $next = $month+$i*6;
                        if($next>12){
                            $next = $next-12;
                        }
                        if(in_array($next,[1,3,5,7,8,10,12,'01','03','05','07','08'])){
                            array_push($monthArray31,$next);
                        }elseif(in_array($next,[4,6,9,11,'04','06','09'])){
                            array_push($monthArray30,$next);
                        }
                    }
                    sort($monthArray30);
                    sort($monthArray31);
                    $monthText30 = implode(',',$monthArray30);
                    $monthText31 = implode(',',$monthArray31);
                    $scriptText .= "$min $hour 30 $monthText30 * curl $apiUrl\n";
                    $scriptText .= "$min $hour 31 $monthText31 * curl $apiUrl\n";
                }else{
                    $monthArray = [];
                    for ($i=0;$i<2;$i++){
                        $next = $month+$i*6;
                        if($next>12){
                            $next = $next-12;
                        }
                        array_push($monthArray,$next);
                    }
                    sort($monthArray);
                    $monthText = implode(',',$monthArray);
                    $scriptText .= "$min $hour $day $monthText * curl $apiUrl\n";
                }
            }
        }else{
            $monthArray = [];
            for ($i=0;$i<2;$i++){
                $next = $month+$i*6;
                if($next>12){
                    $next = $next-12;
                }
                array_push($monthArray,$next);
            }
            sort($monthArray);
            $monthText = implode(',',$monthArray);
            $scriptText .= "$min $hour $day $monthText * curl $apiUrl\n";
        }
        return $scriptText;
    }


    //状态
    public static $status = [
        0 => '关闭',
        1 => '开启',
        2 => '暂停',
        3 => '过期',
        4 => '完成',
    ];

    public  static  $task_type = [
        0=>'日常任务',
        1=>'党员发展'
    ];

    public static  $feedback_type = [
        0=>'无需反馈',
        1=>'确认反馈',
//        2=>'文件反馈'
    ];


    public  function getStatusAttr($value){
        if(null===$value){
            return '未知';
        }
        return TaskList::$status[$value];
    }

    public function  getFrequencyAttr($value){
        if(null===$value){
            return '未知';
        }
        return TaskList::$frequency[$value];
    }

    public function getTaskTypeAttr($value){
        if(null===$value) return '未知';
        return TaskList::$task_type[$value];
    }

    public function getFeedbackTypeAttr($value){
        if(null===$value) return '未知';
        return TaskList::$feedback_type[$value];
    }


    public function getCreateTimeAttr($value){
        return  $this->getDateByTimeStramp($value);
    }

    public  function  getEndTimeAttr($value){
        return $this-> getDateByTimeStramp($value);
    }

    public  function  getAtTimeAttr($value){
        return  $this->getTimeByTimestamp($value);
    }


    public  function  getAtDateAttr($value){
        return  $this->getDateByTimeStramp($value);
    }

    private  function  getDateByTimeStramp($timestamp){
        if(!empty($timestamp)){
            return date(TaskList::$date_format,$timestamp);
        }else{
            return '';
        }
    }

    private  function  getTimeByTimestamp($timestamp){
        if(!empty($timestamp)){
            return date(TaskList::$time_format,$timestamp);
        }else{
            return '';
        }
    }



    /**
     *  获取所有的信息
     */
    public function  getFullInfo(){

    }

    public function taskListResult(){
        return $this->hasMany('TaskListResult','tid','id');
    }


}
