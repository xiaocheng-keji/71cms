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

use think\Model;

class DicData extends Model
{


    // 定义全局的查询范围
    protected function base($query)
    {
    }


    public function DicType()
    {
        return $this->belongsTo('DicType', 'type_id', 'id');
    }

    /**
     * @param $code
     * @return array
     * 返回有效的排序的value=>text
     */
    public static function validValueTextColumn($code)
    {
        return self::where('type_code', $code)
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->cache(60)
            ->column('text', 'value');
    }

    /**
     * @param $code
     * @param $value
     * @return mixed
     * 根据值转换为内容
     */
    public static function value2text($code, $value)
    {
        return self::where('type_code', $code)
            ->where('value', $value)
            ->cache(60)
            ->value('text', '');
    }

    /**
     * @param $code
     * @param $text
     * @return mixed
     * 根据内容转换为值
     */
    public static function text2value($code, $text)
    {
        return self::where('type_code', $code)
            ->where('text', $text)
            ->cache(60)
            ->value('value', '');
    }
}