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

use app\admin\model\Payment;
use app\common\model\Excel;
use think\Db;
use think\Exception;
/**
 * 微信管理
 * Class Wx
 * @package app\admin\controller
 */
class Wx extends AdminBase
{
    /**
     * 微信的配置界面
     * @return \think\response\View
     */
    public function config()
    {
        $res = Db::name('wxconfig')->find();
        $this->assign('res', $res);
        return view();
    }

    /**
     * 配置修改操作
     * @return \think\response\Json
     */
    public function config_update()
    {
        $data = input('post.');
        if (!empty($data['sub_mch_id'])) {
            $data['is_sub'] = 1;
        } else {
            $data['is_sub'] = 0;
        }
        $res = Db::name('wxconfig')->find();
        if (!$res) {
            $res = Db::name('wxconfig')->insert($data);
        } else {
            $res = Db::name('wxconfig')->Cache('wxconfig')->where('id', $res['id'])->update($data,['id'=>$res['id']]);
        }

        if ($res) {
            return json(['code' => 200, 'msg' => '设置成功']);
        } else {
            return json(['code' => 0, 'msg' => '设置失败']);
        }
    }

    /**
     * 人员缴费列表
     * @return \think\response\View
     */
    public function user_list()
    {
        return view();
    }

    /**
     * 获取列表数据
     * @param int $page
     * @param int $limit
     * @param int $id
     * @return \think\response\Json
     */
    public function get_list($page = 1, $limit = 10, $keyword = '')
    {
        $map1 = [];
        $map2 = [];
        if (empty($keyword)) {
            if (input('time_range') || (input('is_pay') !== null && input('is_pay') != 2) || input('is_pay') == 1) {
                if (input('time_range')) {
                    $time_arr = explode(' - ', input('time_range'));
                    $map1[] = ['pay_time', '>=', $time_arr[0]];
                    $map1[] = ['pay_time', '<=', $time_arr[1]];
                    $map2[] = ['pay_time', '>=', $time_arr[0]];
                    $map2[] = ['pay_time', '<=', $time_arr[1]];
                }
                if (input('is_pay') == 0 || input('is_pay') == 1) {
                    $map1[] = ['is_pay', '=', input('is_pay')];
                    $map2[] = ['is_pay', '=', input('is_pay')];
                }
                $list = Payment::withJoin(['user' => ['nickname']], 'left')->order('id', 'desc')->whereOr([$map1, $map2])->page($page, $limit)->select();
                $count = Db::name('payment')->alias('payment')->whereOr([$map1, $map2])->count();
            } else {
                $list = Payment::withJoin(['user' => ['nickname']], 'left')->order('id', 'desc')->page($page, $limit)->select();
                $count = Db::name('payment')->count();
            }

        } else {
            $map1[] = ['nickname', 'like', "%$keyword%"];
            $map2[] = ['title', 'like', "%$keyword%"];
            if (input('time_range')) {
                $time_arr = explode(' - ', input('time_range'));
                $map1[] = ['pay_time', '>=', $time_arr[0]];
                $map1[] = ['pay_time', '<=', $time_arr[1]];
                $map2[] = ['pay_time', '>=', $time_arr[0]];
                $map2[] = ['pay_time', '<=', $time_arr[1]];
            }

            if (input('is_pay') == 0 || input('is_pay') == 1) {
                $map1[] = ['is_pay', '=', input('is_pay')];
                $map2[] = ['is_pay', '=', input('is_pay')];
            }

            $list = Payment::withJoin(['user' => ['nickname']], 'left')->whereOr([$map1, $map2])->page($page, $limit)->select();
//            dump($list);die;
            $count = Payment::withJoin(['user' => ['nickname']], 'left')->whereOr([$map1, $map2])->count();
        }


        return json(['code' => 0, 'msg' => '成功获取', 'count' => $count, 'data' => $list]);
    }

    /**
     * 信息修改
     * @return \think\response\View
     */
    public function payment_edit()
    {
        if ($this->request->isAjax()) {
            $post = input('post.');
            $res = Db::name('payment')->update($post);
            if ($res !== false) {
                jsonReturn(1, '保存成功');
            } else {
                jsonReturn(0, '保存失败');
            }
        } else {
            $res = Db::name('payment')->alias('a')
                ->field('a.id,a.title,a.year,a.end_time,b.nickname,a.payment')
                ->join('user b', 'a.uid=b.id')
                ->where(['a.id' => input('id')])->find();
            $this->assign('res', $res);
            return view();
        }
    }


    /**
     * 导入excel表的操作
     */
    public function upload_excel()
    {
        //获取表单上传文件
        $file = request()->file('file');

        if ($file) {
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->move('./uploads/excel');
            if ($info) {
                $file_path = trim($info->getPathName(), '.');
                $file_path = str_replace("\\", "/", $file_path);

            } else {
                jsonReturn('0', $file->getError());
                die;
            }

            $inputFileName = '.' . $file_path;
            date_default_timezone_set('PRC');
            // 读取excel文件
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                jsonReturn(0, $e->getMessage());
            }

//        确定要读取的sheet，什么是sheet，看excel的右下角，真的不懂去百度吧
            $sheet = $objPHPExcel->getSheet(0);

            $get_list = $sheet->toArray();

            $head = [];
            $list = [];
            foreach ($get_list as $k => $v) {
                if ($k == 0) {
                    $head = $v;
                    continue;
                }
                $index = $k - 1;
                foreach ($v as $kk => $vv) {
                    if (empty($vv)) {
                        unset($list[$index]);
                        break;
                    }
                    if ($head[$kk] == 'end_time') {
                        $list[$index][$head[$kk]] = str_replace('/', '-', $vv);
                    } else {
                        $list[$index][$head[$kk]] = $vv;
                    }
                }
            }
            Db::startTrans();
            $res = Db::name('payment')->insertAll($list, true);
            if ($res) {
                Db::commit();
                jsonReturn(1, '文件导入成功');
            } else {
                Db::rollback();
                jsonReturn(0, '文件导入失败');
            }
        } else {
            jsonReturn(0, '无效图片');
        }
    }


    /**
     * 删除
     * @param $id
     */
    public function payment_del($id)
    {
        if (Db::name('payment')->where(['id' => $id])->value('is_pay') == 1) {
            jsonReturn(0, '已支付的项目不能删除');
        }
        $res = Db::name('payment')->where(['is_pay' => 0])->delete($id);
        if ($res) {
            jsonReturn(1, '删除成功');
        } else {
            jsonReturn(0, '删除失败');
        }
    }


    public function dz_list($page = 1, $limit = 10, $start = '', $end = '')
    {
        if ($this->request->isAjax()) {
            if (!empty($start) && !empty($end)) {
                $list = Db::name('bill')->whereBetweenTime('pay_time', $start, "$end 23:59:59")->page($page, $limit)->select();
                $count = Db::name('bill')->whereBetweenTime('pay_time', $start, $end)->count();
            } else {
                $list = Db::name('bill')->page($page, $limit)->select();
                $count = Db::name('bill')->count();
            }

            return json(['code' => 0, 'msg' => '成功获取', 'count' => $count, 'data' => $list]);
        } else {
            return view();
        }

    }

    public function export(){
        $keyword = input('keyword');
        $time_range = input('time_range');
        $map1[] = ['name', 'like', "%$keyword%"];
        $map2[] = ['title', 'like', "%$keyword%"];
        if ($time_range) {
            $time_arr = explode(' - ', $time_range);
            $map1[] = ['pay_time', '>=', $time_arr[0]];
            $map1[] = ['pay_time', '<=', $time_arr[1]];
            $map2[] = ['pay_time', '>=', $time_arr[0]];
            $map2[] = ['pay_time', '<=', $time_arr[1]];
        }

        if (input('is_pay') == 0 || input('is_pay') == 1) {
            $map1[] = ['is_pay', '=', input('is_pay')];
            $map2[] = ['is_pay', '=', input('is_pay')];
        }

        $excel = new Excel();
        $head = [
            'id'=>'列表ID',
            'title'=>'缴费标题',
            'uid'=>'用户id',
            'name'=>'用户名',
            'payment'=>'应缴金额',
            'year'=>'归属年份',
            'end_time'=>'截止时间',
            'is_pay'=>'缴费状态',
            'pay_time'=>'缴费时间'
        ];
        $data = Payment::field("id,title,uid,name,payment,year,end_time,IF(is_pay=0,'未支付','已支付') as is_pay,pay_time")
            ->where(function($query) use ($map1,$map2){
                $query->whereOr([$map1,$map2]);
            })
            ->select();
        $excel->export($head,$data,'缴费列表');
    }
}
