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
namespace app\common\model;

use PHPExcel;
use PHPExcel_Writer_Excel2007;
use think\Model;

class Excel extends Model
{
    public function export($header,$data,$fileName){
        $objExcel = new PHPExcel();
        $objWriter = new PHPExcel_Writer_Excel2007($objExcel);
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        //设置当前活动sheet的名称
        //$objActSheet->setTitle('测试Sheet');
        //*************************************
        //设置单元格内容
        $i=1;
        foreach ($header as $key => $value){
            $objActSheet->setCellValue($this->getCol($i).'1', $value);
            $i++;
        }
        $i=1;
        foreach ($data as $row){
            $i++;
            $j=1;
            foreach ($header as $key => $value){
                $objActSheet->setCellValue($this->getCol($j++).$i, $row[$key]);
            }
        }
        //*************************************
        //输出内容
        //到浏览器
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
//        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
//        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx');
        header('Cache-Control: max-age=1');
        $objWriter->save('php://output');
    }

    public function getCol($num){//递归方式实现根据列数返回列的字母标识
        $arr=array(0=>'Z',1=>'A',2=>'B',3=>'C',4=>'D',5=>'E',6=>'F',7=>'G',8=>'H',9=>'I',10=>'J',11=>'K',12=>'L',13=>'M',14=>'N',15=>'O',16=>'P',17=>'Q',18=>'R',19=>'S',20=>'T',21=>'U',22=>'V',23=>'W',24=>'X',25=>'Y',26=>'Z');
        if($num==0)
            return '';
        return $this->getCol((int)(($num-1)/26)).$arr[$num%26];
    }
}
