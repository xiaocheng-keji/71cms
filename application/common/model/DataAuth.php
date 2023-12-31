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

class DataAuth extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * 数据授权到部门
     *
     * @param $table_name
     * @param int $table_id
     * @param $dep_id
     * @return bool|\think\Collection
     * @throws \Exception
     */
    public static function dataAuth($table_name, int $table_id, $dep_id)
    {
        if (is_numeric($dep_id)) {
            $dep_id = [$dep_id];
        }
        foreach ($dep_id as $k => $v) {
            $data = [
                'table_name' => $table_name,
                'table_id' => $table_id,
                'dep_id' => $v,
            ];
            self::create($data);
        }
    }


    /**
     * @param $table_name
     * @param int $table_id
     * @param array $dep_id
     * @return bool
     */
    public static function hasDataAuth($table_name, int $table_id, $dep_id = []): bool
    {
        $auth_dep_id = self::where('table_name', $table_name)
            ->where('table_id', $table_id)
            ->column('dep_id');
        if (!$auth_dep_id) {
            return true;
        }
        $has_auth = array_intersect($auth_dep_id, $dep_id);
        if ($has_auth) return true;
        return false;
    }
}