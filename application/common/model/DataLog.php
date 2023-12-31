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

class DataLog extends Model
{

    protected $autoWriteTimestamp = true;

    protected $type = [
        'change' => 'array'
    ];

    /**
     * @param $table_name
     * @param int $table_id
     * @param $remark
     * @param $old_val
     * @param $new_val
     * @return DataLog
     * 记录行的修改
     */
    public static function log($table_name, int $table_id, $remark, $old_val, $new_val)
    {
        $change = [];
        foreach ($new_val as $k => $v) {
            if (isset($old_val[$k])) {
                if ($v != $old_val[$k]) {
                    $change[$k] = [
                        'type' => 3,//1增2删3改
                        'old' => $old_val[$k],
                        'new' => $v,
                    ];
                }
                unset($old_val[$k]);
                continue;
            } else {
                $change[$k] = [
                    'type' => 1,
                    'new' => $v,
                ];
            }
        }
        //剩下的是被删除的值
        foreach ($old_val as $k => $v) {
            $change[$k] = [
                'type' => 2,
                'old' => $v
            ];
        }
        $data = [
            'table_name' => $table_name,
            'table_id' => $table_id,
            'remark' => $remark,
            'change' => $change,
        ];
        return self::create($data);
    }
}