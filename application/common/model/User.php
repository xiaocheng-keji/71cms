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

class User extends ModelBasic
{
    protected $insert = ['password', 'head_pic', 'reg_ip'];

    // 定义全局的查询范围
    protected function base($query)
    {
    }

    protected $autoWriteTimestamp = true;

    public static $jiguan = [
        '北京',
        '天津',
        '上海',
        '重庆',
        '河北',
        '河南',
        '云南',
        '辽宁',
        '黑龙江',
        '湖南',
        '安徽',
        '山东',
        '新疆',
        '江苏',
        '浙江',
        '江西',
        '湖北',
        '广西',
        '甘肃',
        '山西',
        '内蒙古',
        '陕西',
        '吉林',
        '福建',
        '贵州',
        '广东',
        '青海',
        '西藏',
        '四川',
        '宁夏',
        '海南',
        '台湾',
        '香港',
        '澳门',
        '南海诸岛'
    ];

    public function level()
    {
        return $this->hasOne('UserLevel', 'id', 'level_id');
    }

    protected function setHeadpicAttr($vluae)
    {
        return $vluae ?? '/images/head_pic.png';
    }

    protected function setPasswordAttr($password)
    {
        return encrypt($password);
    }

    protected function setRegipAttr()
    {
        return $_SERVER["REMOTE_ADDR"];
    }

    protected function setJointimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected function setApplytimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    public static function savePoint($user_id, $points, $desc, $type = 0,$meeting_id = null,$all = true)
    {
        if($all){
            self::where('id', $user_id)->setInc('points', $points);
            self::where('id', $user_id)->setInc('mon_points', $points);
            self::where('id', $user_id)->setInc('year_points', $points);
        }
        self::where('id', $user_id)->setInc('point', $points);
        $balance = self::where('id', $user_id)->value('point');
        return PointLog::create([
            'user_id' => $user_id,
            'points' => $points,
            'balance' => $balance,
            'desc' => $desc,
            'type' => $type,
            'meeting_id' => $meeting_id
        ]);
    }

    public function setBirthdayAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    public function dep()
    {
        $userDep = new UserDep();
        return $dep_arr = $userDep->where(['user_id' => $this->id])->select();
    }

    /**
     * @param $value
     * @return false|int|null
     * 转正时间
     */
    public function setBecomeTimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    /**
     * @param $value
     * @return false|int|null
     * 失联时间
     */
    public function setLostContactTimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    /**
     * @param string $field
     * @param int $offset
     * @param null $length
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取排行列表
     */
    public static function pointRankList($field = 'mon_points', $offset = 0, $length = null)
    {
        $where = [];
        return self::where($where)
            ->order($field . ' DESC, id ASC')
            ->field($field . ',nickname,id,' . $field . ' as points,head_pic')
            ->limit($offset, $length)
            ->select();
    }

    /**
     * 获取用户的的排名
     * @param $user_id
     * @param string $field
     * @return bool|float|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function pointRank($user_id, string $field = 'mon_points')
    {
        $userInfo = self::where('id', $user_id)->find();
        if (!$userInfo) {
            return self::setErrorInfo('没有此用户');
        }
        $where = [];
        //比自己分数高的数量
        $gt_count = self::where($where)
            ->where($field, '>', $userInfo[$field])
            ->order($field . ' DESC, id ASC')
            ->count();
        //和自己分数一样，id比自己小的数量
        $eq_count = self::where($where)
            ->where($field . '=' . $userInfo[$field] . ' and id<' . $user_id)
            ->count();
        return $gt_count + $eq_count + 1;
    }
}