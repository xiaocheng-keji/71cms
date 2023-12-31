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

use app\api\model\NavWap;
use \app\api\model\Article as ArticleModel;
use QL\QueryList;
use think\Db;
use app\common\model\Department as DepartmentModel;

class Index extends Base
{
    protected function initialize () {
        parent::initialize();
    }

    /**
     * 获取指定条数热门文章
     */
    public function getAdHot () {
    	$size  = input('size', 5);
    	$where = [['recommend', '>', '0'], ['thumb', '<>', ''],['recommend','<','11']];
    	$list = ArticleModel::where($where)->field('id, title, thumb')->order('recommend,id desc')->limit($size)->select();
    	return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$list]);
    }
	public function getPic($name){
		$homepic = Db::name('banner')
		    ->where('name', $name)
		    ->select();
			return json(['status'=>1, 'msg'=>'获取成功','homepic'=>$homepic]);
	}
    /**
     * 获取栏目分类
     */
    public function getCateList() {
    	$where[] = ['status', '=', 1];
//    	$where[] = ['app_link','<>',''];
//    	$where[] = ['pid', '=', (int)input('pid', 1)];
        $list = NavWap::where($where)->order('sort asc')->select();
//        $list = NavWap::where($where)->order('sort asc')->select();
    	foreach ($list as &$v) {
//    		$v['icon'] = SITE_URL . $v['icon'];
            //处理app_link里的&符号
            $v['app_link'] = str_replace('&amp;', '&', $v['app_link']);
        }

    	return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$list]);
    }

    /**
     * 获取热门文章列表
     */
    public function getHotArticleList () {
    	$p = input('page', 1);
    	$size = input('size', 10);
//    	$append = input('append', 5);
//    	$firstRow = ($p - 1) * $size + $append;
    	$where = [['recommend', '>', '10'], ['thumb', '<>', '']];
        $list = ArticleModel::where($where)->field('id, title, thumb, click, comment_count, author')
            ->fieldRaw('FROM_UNIXTIME(create_time, "%Y-%m-%d %H:%i") as create_time')// mysql格式化时间戳
            ->order('recommend,id desc')->page($p, $size)->select();
    	return json(['status'=>1, 'msg'=>'获取成功', 'result'=>$list]);
    }

    public function test1($year = 0)
    {
        $data = array();
        if($year == 0){
            $month = date('m');
            if($month >= 3){
                $data['year'] = date('Y');
            }else{
                $data['year'] = date('Y') - 1;
            }
        }else{
            $data['year'] = $year;
        }
        $score_list = Db::name('department_score')->where($data)->select();
        $score_all = array();
        if(!empty($score_list)){
            foreach ($score_list as $v){
                $score_all[$v['depart_id']] = $v;
            }
        }
        $data1['parent_id'] = 0;
        $data1['show'] = 1;

        $depart_list = Db::name('department')->where($data1)->order('sort')->select();
        $data_all = array();
        if(!empty($depart_list)){
            foreach ($depart_list as $v){
                $score_all[$v['id']]['name'] = $v['name'];
                $score_all[$v['id']]['show_name'] = $v['name'].$year.'年度党建工作KPI考核材料';
                //是否上传资料report_record
                $upload_time = Db::name('report')->alias('a')->join('__REPORT_RECORD__ b', 'a.report_id = b.report_id')->where('depart_id', $v['id'])->value('b.update_time');
                if($upload_time){
                    $score_all[$v['id']]['upload_time'] = date('Y-m-d H:i:s', $upload_time);
                }else{
                    $score_all[$v['id']]['upload_time'] = '';
                }
                if(isset($score_all[$v['id']]) && $score_all[$v['id']]['xianshang_score'] > 0){
                    $data_all[1][] = $score_all[$v['id']];
                }else{
                    $data_all[0][] = $score_all[$v['id']];
                }
                if(input('?depart_id') && input('depart_id') > 0 && input('depart_id') == $v['id']){
                    $data_all[3] = $score_all[$v['id']];
                }
            }
        }
        foreach ($data_all[0] as $key=>$val){
            $tmp[$key] = $val['upload_time'];
        }
        array_multisort($tmp, SORT_DESC, $data_all[0]);

        jsonReturn(1, '部门得分', $data_all);
    }

    public function test2(){
        if(input('?question_id') && input('question_id') > 0 && input('?ans_id') && input('ans_id') > 0 ){
            $question_id = input('question_id');
            $ans_id = input('ans_id');
            $right_id = Db::name('exam_ans')->where('question_id', $question_id)->where('rig', 1)->value('ans_id');
            $score = Db::name('exam_question')->where('question_id', $question_id)->value('score');
            $data['ans_id'] = $ans_id;
            if($right_id == $ans_id){
                $data['isok'] = 1;
                $data['score'] = $score;
                $data['msg'] = '正确';
            }else{
                $data['isok'] = 0;
                $data['score'] = 0;
                $data['msg'] = '错误';
            }
        }else{
            $data['isok'] = -1;
            $data['score'] = 0;
            $data['msg'] = '参数错误';
        }
        jsonReturn(1, '单选答案是否正确', $data);
    }

    public function test($year = 0)
    {
        $data = array();
        if($year == 0){
            $month = date('m');
            if($month >= 3){
                $data['year'] = date('Y');
            }else{
                $data['year'] = date('Y') - 1;
            }
        }else{
            $data['year'] = $year;
        }
        $score_list = Db::name('department_score')->where($data)->select();
        $score_all = array();
        if(!empty($score_list)){
            foreach ($score_list as $v){
                $score_all[$v['depart_id']] = $v;
            }
        }
        $data1['parent_id'] = 0;
        $data1['show'] = 1;

        $depart_list = Db::name('department')->where($data1)->order('sort')->select();
        $data_all = array();
        if(!empty($depart_list)){
            foreach ($depart_list as $v){
                $score_all[$v['id']]['name'] = $v['name'];
                $score_all[$v['id']]['show_name'] = $v['name'].$year.'年度党建工作KPI考核材料';
                $end_day = Db::name('report')->where('year', $year)->value('end_day');
                $score_all[$v['id']]['end_day'] = $end_day;
                //是否上传资料report_record
                $upload_time = Db::name('report')->alias('a')->join('__REPORT_RECORD__ b', 'a.report_id = b.report_id')->where('year', $year)->where('depart_id', $v['id'])->value('b.update_time');

                if($upload_time){
                    $score_all[$v['id']]['upload_time'] = date('Y-m-d H:i:s', $upload_time);
                }else{
                    $score_all[$v['id']]['upload_time'] = '';
                }
                if(isset($score_all[$v['id']]) && $score_all[$v['id']]['xianshang_score'] > 0){
                    $data_all[1][] = $score_all[$v['id']];
                }else{
                    $data_all[0][] = $score_all[$v['id']];
                }
                if(input('?depart_id') && input('depart_id') > 0 && input('depart_id') == $v['id']){
                    $data_all[3] = $score_all[$v['id']];
                }
            }
        }
        foreach ($data_all[0] as $key=>$val){
            $tmp[$key] = $val['upload_time'];
        }
        array_multisort($tmp, SORT_DESC, $data_all[0]);

        jsonReturn(1, '部门得分', $data_all);
    }

    /**
     * 获取系统小程序审核配置
     */
    public function get_mp_config(){
        //获取config表下varname列的is_mp_examine的值
        $data = Db::name('config')->where('varname', 'is_mp_examine')->value('value');
        jsonReturn(1, '获取系统小程序审核配置', $data == 1?true:false);
    }
}