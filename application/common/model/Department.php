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

use app\common\model\DepTeam as DepTeamModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\facade\Validate;
use think\model\concern\SoftDelete;
use util\Tree;

class Department extends ModelBasic
{
    //软删除
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    // 定义全局的查询范围
    protected function base($query)
    {
    }

    protected function setCreatetimeAttr($value)
    {
        return strtotime($value) ? strtotime($value) : null;
    }

    protected $type = [
        'attachment' => 'array',
    ];

    public static $type_default = 0;
    //党委
    public static $type_party_committee = 611;
    //党组
    public static $type_party_committee_group = 612;
    //党总支部
    public static $type_general_branch = 621;
    //党支部
    public static $type_branch = 631;
    //联合党支部
    public static $type_unite_branch = 632;
    //党小组
    public static $type_squad = 641;
    //临时党委
    public static $type_tmp_party_committee = 911;
    //临时党总支部
    public static $type_tmp_general_branch = 921;
    //临时党支部
    public static $type_tmp_branch = 931;
    //临时联合党支部
    public static $type_tmp_unite_branch = 932;

    /**
     * @var array
     * 组织类别
     */
    public static $type_options = [
        611 => '党委',
        612 => '党组',
        621 => '党总支部',
        631 => '党支部',
        632 => '联合党支部',
        641 => '党小组',
        911 => '临时党委',
        921 => '临时党总支部',
        931 => '临时党支部',
        932 => '临时联合党支部',
    ];

    /**
     * 初始化数据 创建根节点
     *
     * @param $name
     * @param $tenant_id
     */
    public static function createTenantInitData($name, $tenant_id)
    {
        //新增根组织
        $dep = [
            'name' => $name,
            'parent_id' => 0,
            'show' => 1,
            'layer' => 1,
            'type' => 611,//根节点 党委
            'tenant_id' => $tenant_id,
        ];
        Db::name('department')->insert($dep);
    }

    /**
     * 获得指定组织下的子组织的数组
     *
     * @access  public
     * @param int $id 组织的ID
     * @param int $selected 当前选中组织的ID
     * @param boolean $re_type 返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param int $level 限定返回的级数。为0时返回所有级数
     * @param string $show
     * @return array|string
     */
    public function departmentList($id = 0, $selected = 0, $re_type = true, $level = 0, $show = '')
    {
        static $res = NULL;

        if ($res === NULL) {
            $data = false;
            if ($data === false) {
                $where = " where c.tenant_id=" . TENANT_ID . ' ';
                if ($show) {
                    $where .= ' and c.show=' . $show . ' ';
                }
                $sql = "SELECT c.*, COUNT(s.id) AS has_children FROM __PREFIX__department AS c" .
                    " LEFT JOIN __PREFIX__department AS s ON s.parent_id=c.id $where " .
                    " GROUP BY c.id  ORDER BY parent_id, sort";
                $sql = str_replace('__PREFIX__', config('database.prefix'), $sql);
                $res = Db::name('department')->query($sql);
            } else {
                $res = $data;
            }
        }
        if (empty($res) == true) {
            return $re_type ? '' : array();
        }

        $options = $this->departmentOptions($id, $res); // 获得指定分类下的子分类的数组

        /* 截取到指定的缩减级别 */
        if ($level > 0) {
            if ($id == 0) {
                $end_level = $level;
            } else {
                $first_item = reset($options); // 获取第一个元素
                $end_level = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options as $key => $val) {
                if ($val['level'] >= $end_level) {
                    unset($options[$key]);
                }
            }
        }

        $pre_key = 0;
        foreach ($options as $key => $value) {
            $options[$key]['has_children'] = 1;
            if ($pre_key > 0) {
                if ($options[$pre_key]['id'] == $options[$key]['parent_id']) {
                    $options[$pre_key]['has_children'] = 1;
                }
            }
            $pre_key = $key;
        }

        if ($re_type == true) {
            $select = '';
            foreach ($options as $var) {
                $select .= '<option value="' . $var['id'] . '" ';
                $select .= ($selected == $var['id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['name'])) . '</option>';
            }
            return $select;
        } else {
            return $options;
        }
    }


    /**
     * 过滤和排序所有组织，返回一个带有缩进级别的数组
     *
     * @access  private
     * @param int $id 上级分类ID
     * @param array $arr 含有所有分类的数组
     * @param int $level 级别
     * @return  void
     */
    public function departmentOptions($spec_id, $arr)
    {
        static $cat_options = array();

        if (isset($cat_options[$spec_id])) {
            return $cat_options[$spec_id];
        }

        if (!isset($cat_options[0])) {
            $level = $last_id = 0;
            $options = $id_array = $level_array = array();
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $id = $value['id'];
                    if ($level == 0 && $last_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$id] = $value;
                        $options[$id]['level'] = $level;
                        $options[$id]['id'] = $id;
                        $options[$id]['name'] = $value['name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_id = $id;
                        $id_array = array($id);
                        $level_array[$last_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_id) {
                        $options[$id] = $value;
                        $options[$id]['level'] = $level;
                        $options[$id]['id'] = $id;
                        $options[$id]['name'] = $value['name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($id_array) != $last_id) {
                                $id_array[] = $last_id;
                            }
                            $last_id = $id;
                            $id_array[] = $id;
                            $level_array[$last_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_id) {
                        break;
                    }
                }

                $count = count($id_array);
                if ($count > 1) {
                    $last_id = array_pop($id_array);
                } elseif ($count == 1) {
                    if ($last_id != end($id_array)) {
                        $last_id = end($id_array);
                    } else {
                        $level = 0;
                        $last_id = 0;
                        $id_array = array();
                        continue;
                    }
                }

                if ($last_id && isset($level_array[$last_id])) {
                    $level = $level_array[$last_id];
                } else {
                    $level = 0;
                    break;
                }
            }
            $cat_options[0] = $options;
        } else {
            $options = $cat_options[0];
        }

        if (!$spec_id) {
            return $options;
        } else {
            if (empty($options[$spec_id])) {
                return array();
            }

            $spec_id_level = $options[$spec_id]['level'];

            foreach ($options as $key => $value) {
                if ($key != $spec_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }

            $spec_id_array = array();
            foreach ($options as $key => $value) {
                if (($spec_id_level == $value['level'] && $value['id'] != $spec_id) ||
                    ($spec_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_id] = $spec_id_array;

            return $spec_id_array;
        }
    }

    public function optionsArr()
    {
        $department_list = $this->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
        $department_options = [];
        $Trees = new Tree();
        $Trees->tree($department_list, 'id', 'parent_id', 'name');
        $department_list = $Trees->getArray();
        $department_list[0] = ['name' => '顶级', 'value' => 0, 'id' => 0];
//        unset($department_list[0]);
        foreach ($department_list as $k => $v) {
            $department_options[$v['id']] = $v['name'];
        }
        return $department_options;
    }


    /**
     * 标题带有树形缩进的数组
     *
     * @return array
     */
    public static function titleTreeArray()
    {
        try {
            $department_list = self::order('sort', 'asc')
                ->order('id', 'asc')
                ->fieldRaw('*,IF(short_name="",name,short_name) as name')
                ->select()
                ->toArray();
        } catch (Exception $exception) {
            $department_list = [];
        }
        $department_options = [];
        $Trees = new Tree();
        $Trees->tree($department_list, 'id', 'parent_id', 'name');
        $department_list = $Trees->getArray();
        unset($department_list[0]);
        foreach ($department_list as $k => $v) {
            $department_options[$v['id']] = $v['name'];
        }
        return $department_options;
    }

    /**
     * 标题带有树形缩进，带不可选中属性的二维数组
     *
     * @param array $auth_dep_arr
     * @return array
     */
    public static function titleTreeOption($auth_dep_arr = [])
    {
        try {
            $department_list = self::order('sort', 'asc')
                ->order('id', 'asc')
                ->fieldRaw('*,IF(short_name="",name,short_name) as name')
                ->select()
                ->toArray();
        } catch (Exception $exception) {
            $department_list = [];
        }
        $department_options = [];
        $Trees = new Tree();
        $Trees->tree($department_list, 'id', 'parent_id', 'name');
        $department_list = $Trees->getArray();
        unset($department_list[0]);
        foreach ($department_list as $k => $v) {
            $department_options[$v['id']] = [
                'title' => $v['name'],
                'disabled' => (!in_array($v['id'], $auth_dep_arr))
            ];
        }
        return $department_options;
    }


    /**
     * 获得指定组织下的子组织的数组
     *
     * @access  public
     * @param int $id 父级组织的ID
     * @param array $subsetId 子集组织的ID 20191121改为可选的id
     * @param array $checked 选中的id
     * @param int $level 限定返回的级数。为0时返回所有级数
     * @param int $show
     * @param boolean $disToSub true 将自己添加到子集里并在名称后面添加(单选/全选), false不添加到里面
     * @param string $titleField
     * @return array
     */
    public function dtreeList($id = 0, $subsetId = array(), $checked = array(), $level = 0, $show = 1, $disToSub = false, $titleField = '')
    {
        $where[] = ['parent_id', '=', $id];
        $where[] = ['show', '=', $show];
        $model = $this->where($where);
        if ($titleField == 'short_name') {
            $model->fieldRaw('short_name as title');
        } elseif ($titleField == 'name') {
            $model->fieldRaw('name as title');
        } else {
            $model->fieldRaw('IF(short_name="",name,short_name) as title');
        }
        $res = $model
            ->field('id,parent_id,type')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select();
        if (count($res) <= 0) {
            return null;
        }
        $level--;
        $result = [];
        foreach ($res as $k => $v) {
            if ($level < 0 || $level > 0) {
                $subset = $this->dtreeList($v['id'], $subsetId, $checked, $level, $show, $disToSub);
            }
            if ($disToSub && $subset != NULL) {
                $v['disabled'] = !in_array($v['id'], $subsetId);
                $Tosub = $v;
                $Tosub['last'] = true;
                $Tosub['checkArr'] = ['type' => 0, 'checked' => 0];
                if ($subset == NULL) {
                    $subset = [];
                }
                array_unshift($subset, $Tosub->toArray());
                $v['title'] = $v['title'] . '(全选)';
            }
            if (count($checked) > 0) {
                if (array_key_exists($v['id'], $checked)) {
                    $v['checkArr'] = ['type' => 0, 'checked' => $checked[$v['id']]];
                } else {
                    $v['checkArr'] = ['type' => 0, 'checked' => 0];
                }
            } else {
                $v['checkArr'] = ['type' => 0, 'checked' => 0];
            }
            if ($subset != NULL) {
                $v['last'] = false;
                $v['children'] = $subset;
            } else {
                $v['last'] = true;
            }
            $v['disabled'] = !in_array($v['id'], $subsetId);
            $v['id'] = (string)$v['id'];
//            if (in_array($v['id'], $show)) {
//                $result[] = $v;
//            }
            $result[] = $v;
        }
        return $result;
    }

    /**
     * @param $data
     * @param int $p_id
     * @param array $user_where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取有层级的数组
     */
    function getDepTree($data, $p_id = 0, $user_where = [])
    {
        $tree = [];
        foreach ($data as $row) {
            if ($row['parent_id'] == $p_id) {
                if ($user_where != false) {
                    $dep_team_id = DepTeamModel::where('dep_id', $row['id'])
                        ->order('session', 'desc')
                        ->order('id', 'desc')
                        ->value('id');
                    $row['user_list'] = DepTeamUser::useGlobalScope(false)
                        ->alias('a')
                        ->join('user b', 'a.user_id=b.id')
                        ->join('dic_data c', 'c.type_code="user_level" and a.level_id=c.value')
                        ->where('a.dep_team_id', $dep_team_id)
                        ->where('a.dismissal_time', null)
                        ->order('a.sort asc,a.id asc')
                        ->field('b.id,b.nickname,b.head_pic,a.level_id,c.text as name')
                        ->select();
                }
                $tmp = $this->getDepTree($data, $row['id'], $user_where);
                if ($tmp) {
                    $row['children'] = $tmp;
                } else {
                    $row['leaf'] = true;
                }
                $tree[] = $row;
            }
        }
        return $tree;
    }

    /**
     * @param array|int $id
     * @return array
     * 获取某些id的所有下级的id 返回值包含自己
     */
    public static function getSubDep($id): array
    {
        if ($id === []) return [];
        if (is_numeric($id)) $id = [$id];
        if (!is_array($id)) {
            return [];
        }
        $ids = Db::name('department')
            ->where('parent_id', 'in', $id)
            ->where('delete_time', 'null')
            ->column('id');
        if ($ids) {
            $ids = self::getSubDep($ids);
        } else {
            $ids = [];
        }
        return array_merge($id, $ids);
    }

    /**
     * @param $type
     * @return array
     * 返回允许升级的类型id
     */
    public static function allowableChangeType($type)
    {
        //党委：不可升级；
        //党组：可升级为党委；
        //党总支部：可升级为党委；
        //党支部：可升级为党总支部；
        //联合党支部：可升级为党总支部；
        //党小组：不可升级；
        $allowable = [];
        if ($type == 612 || $type == 621) {
            $allowable = [611];
        } elseif ($type == 631 || $type == 632) {
            $allowable = [621];
        }
        return $allowable;
    }

    /**
     * @param $type
     * @return array
     * 返回允许迁移的类型id
     */
    public static function allowableTransfer($type)
    {
//      党委：      可迁移至党委；
//      党组：      可迁移至党委、党总支部、联合党支部；
//      党总支部：   可迁移至党委；
//      联合党支部： 可迁移至党委、党总支部；
//      党支部：    可迁移至党委、党总支部；
//      党小组：    可迁移至党总支部、联合党支部、党支部、
//      611 => '党委',
//      612 => '党组',
//      621 => '党总支部',
//      631 => '党支部',
//      632 => '联合党支部',
//      641 => '党小组',
        $allowable = [];
        if ($type == 611) {
            $allowable = [611];
        } elseif ($type == 612) {
            $allowable = [611, 621, 632];
        } elseif ($type == 621) {
            $allowable = [611];
        } elseif ($type == 631) {
            $allowable = [611, 621];
        } elseif ($type == 632) {
            $allowable = [611, 621];
        } elseif ($type == 641) {
            $allowable = [621, 632, 641];
        }
        return $allowable;
    }

    /**
     * @param $id
     * @return bool
     * 检查当前登录用户对某个组织是否有权限
     */
    public static function checkDepAuth($id)
    {
        $auth_dep_arr = TmpSession::getDepAuth();
        if (in_array($id, $auth_dep_arr)) return true;
        return self::setErrorInfo('您没有权限操作该组织');
    }

    /**
     * @param $id
     * @param $type
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 修改类型
     */
    public static function changeType($id, $type)
    {
        if (!self::checkDepAuth($id)) return false;
        $auth_dep_arr = TmpSession::getDepAuth();
        if (!in_array($id, $auth_dep_arr)) {
            return self::setErrorInfo('您没有权限升级该组织');
        }
        $item = self::where('id', $id)->find();
        $allowable_change_type = self::allowableChangeType($item['type']);
        if (!in_array($type, $allowable_change_type)) {
            return self::setErrorInfo('升级类型错误');
        }
        //升级
        $data = [
            'type' => $type
        ];
        $res = self::where('id', $id)->update($data);
        //记录变动
        $res2 = DataLog::log('department', $id, '升级', ['type' => $item['type']], $data);
        return ($res && $res2) || self::setErrorInfo('升级失败');
    }

    /**
     * @param $id
     * @param $target_id
     * @return bool
     * 合并组织
     */
    public static function mergeDep($id, $target_id)
    {
        if (!self::checkDepAuth($id)) return false;
        if (!self::checkDepAuth($target_id)) return self::setErrorInfo('您没有权限合并到目标组织');
        //合并
        //只能合并两个相同组织类别的组织，不同党组织类别的组织不可进行合并。
        //当前党组织合并至目标党组织内，其下级党组织及党员自动调整至目标组织
        //下。
        if ($id == $target_id) {
            return self::setErrorInfo('目标组织不能是自己');
        }
        $target_dep = self::get($target_id);
        if (!$target_dep) {
            return self::setErrorInfo('目标组织不存在');
        }
        if (in_array($target_id, self::getSubDep($id))) {
            return self::setErrorInfo('目标组织不能是自己的下级');
        }
        $item = self::get($id);
        if ($item['type'] != $target_dep['type']) {
            return self::setErrorInfo('只能合并两个相同组织类别的组织');
        }
        //把目前组织的人都挂到目标组织
        try {
            UserDep::where('dep_id', $id)->update(['dep_id' => $target_id]);
        } catch (Exception $exception) {
            return self::setErrorInfo('合并党员失败');
        }
        //把目前组织的下级都挂到目标组织
        try {
            self::where('parent_id', $id)->update(['parent_id' => $target_id]);
            self::destroy($id);
        } catch (Exception $exception) {
            return self::setErrorInfo('合并组织失败' . $exception->getMessage());
        }
        return true;
    }

    /**
     * 迁移组织
     * @param $id
     * @param $target_id
     * @return bool
     */
    public static function transferDep($id, $target_id)
    {
        if (!self::checkDepAuth($id)) return false;
        if (!self::checkDepAuth($target_id)) return self::setErrorInfo('您没有权限迁移到目标组织');
        //迁移
        if ($id == $target_id) {
            return self::setErrorInfo('目标组织不能是自己');
        }
        $target_dep = Department::get($target_id);
        if (!$target_dep) {
            return self::setErrorInfo('目标组织不存在');
        }
        $item = self::get($id);
        $allowableTransfer = Department::allowableTransfer($item['type']);
        if (!in_array($target_dep['type'], $allowableTransfer)) {
            return self::setErrorInfo('迁移目标组织类别不允许');
        }
        //把当前的上级改为目标
        try {
            self::where('id', $id)->update(['parent_id' => $target_id]);
        } catch (Exception $exception) {
            return self::setErrorInfo('迁移失败' . $exception->getMessage());
        }
        return true;
    }

    /**
     * 删除组织
     * @param $id
     * @return bool
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function delDep($id)
    {
        if (!self::checkDepAuth($id)) return false;
        $department = self::where('parent_id', $id)->find();
        if (!empty($department)) {
            return self::setErrorInfo('此组织下存在子组织，不可删除');
        }
        $user_count = UserDep::where('dep_id', $id)->count();
        if ($user_count > 0) {
            return self::setErrorInfo('目前组织下的党员数量 ' . $user_count . '，请迁移党员后
进行删除操作');
        }
        if (!self::destroy($id)) {
            UserDep::where('dep_id', $id)->delete();
            return self::setErrorInfo('删除失败');
        }
        return true;
    }

    public static function allowableAddSubType($type)
    {
//      党小组不可添加下级组织；
//      611 => '党委',
//      612 => '党组',
//      621 => '党总支部',
//      631 => '党支部',
//      632 => '联合党支部',
//      641 => '党小组',
        $allowable = [];
        if ($type == 611) {
            //党委的下级组织类型限定为：党委、党总支部、党支部、联合党支部、党组；
            $allowable = [611, 621, 631, 632, 612];
        } elseif ($type == 621) {
            //党总支的下级组织类型限定为：党支部、联合党支部、党组；
            $allowable = [631, 632, 612];
        } elseif ($type == 631 || $type == 632) {
            //党支部、联合党支部的下级组织类型限定为：党小组、党组；
            $allowable = [641, 612];
        } elseif ($type == 612) {
            //党组的下级组织类型限定为：党委、党总支部、党支部、联合党支部、党组、党小组；
            $allowable = [611, 621, 631, 632, 612, 641];
        } elseif ($type == 0) {
            //根节点可以添加全部
            $allowable = array_keys(self::$type_options);
        }
        return $allowable;
    }


    /**
     * 获得指定组织下的子组织的数组
     * @param int $id
     * @param array $allowId
     * @param array $checked
     * @param int $level
     * @param int $show
     * @return array|null
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function depDtree($id = 0, $allowId = array(), $checked = array(), $level = 0, array $show = [])
    {
        $where[] = ['parent_id', '=', $id];
        $where[] = ['show', '=', 1];
        $res = $this->where($where)
            ->field('id, parent_id')
            ->fieldRaw('IF(short_name="",name,short_name) as title')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select();
        if (count($res) <= 0) {
            return null;
        }
        $level--;
        $result = [];
        foreach ($res as $k => $v) {
            if ($level < 0 || $level > 0) {
                $subset = $this->depDtree($v['id'], $allowId, $checked, $level, $show);
            }
            if (in_array($v['id'], $checked)) {
                $v['checkArr'] = ['type' => 0, 'checked' => 1];
            } else {
                $v['checkArr'] = ['type' => 0, 'checked' => 0];
            }
            if (!empty($subset)) {
                $v['last'] = false;
                $v['children'] = $subset;
            } else {
                $v['last'] = true;
            }
            $v['disabled'] = !in_array($v['id'], $allowId);
            $v['iconClass'] = 'dtree-icon-fenzhijigou';
            $v['id'] = (string)$v['id'];
            if (in_array($v['id'], $show)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * 获得指定组织下的子组织和用户的数组
     * @param int $id
     * @param array $allowId
     * @param array $checked
     * @param int $level
     * @param int $show
     * @return array|null
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function depUserDtree($id = 0, $allowId = array(), $checked = array(), $level = 0, array $show = [])
    {
        $where[] = ['parent_id', '=', $id];
        $where[] = ['show', '=', 1];
        $res = $this->where($where)
            ->fieldRaw('id, IF(short_name="",name,short_name) as title, parent_id')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select();
        if (count($res) <= 0) {
//            return null;
            $res = [];
        }
        $level--;
        $result = [];
        foreach ($res as $k => $v) {
            if ($level < 0 || $level > 0) {
                $subset = $this->depUserDtree($v['id'], $allowId, $checked, $level, $show);
            }
//            if (in_array($v['id'], $checked)) {
//                $v['checkArr'] = ['type' => 0, 'checked' => 1];
//            } else {
//                $v['checkArr'] = ['type' => 0, 'checked' => 0];
//            }
            if (!empty($subset)) {
                $v['last'] = false;
                $v['children'] = $subset;
            } else {
//                continue;//这里没有下级就跳过，不显示在树上了
                $sub_count = $this->where('parent_id', $v['id'])->count();
                $v['last'] = $sub_count > 0 ? false : true;
            }
            $v['disabled'] = !in_array($v['id'], $allowId);
            $v['iconClass'] = 'dtree-icon-fenzhijigou';
            if (in_array($v['id'], $show)) {
                $result[] = $v;
            }
        }
        //挂载用户
        if (in_array($id, $allowId)) {
            $user_list = Db::name('user_dep')
                ->alias('a')
                ->join('user b', 'a.user_id=b.id')
                ->where('a.dep_id', $id)
                ->where(['b.people_type' => [1, 2], 'b.delete_time' => null])
                ->where(['b.transfer_time' => 0])
                ->order('b.sort', 'DESC')
                ->order('b.create_time', 'DESC')
                ->fieldRaw('b.id,b.nickname')
                ->select();
            foreach ($user_list as &$u) {
                $u['last'] = true;
                $u['disabled'] = false;
                $u['title'] = $u['nickname'];
                $u['checkArr'] = ['type' => 0, 'checked' => in_array($u['id'], $checked) ? 1 : 0];
            }
            $result = array_merge($result, $user_list);
        }
        return $result;
    }

    /**
     * 获取某些id的所有父级的id 返回值包含自己
     * @param array $id
     * @return array
     */
    public static function getAllParentIds($id): array
    {
        if (empty($id)) return [];
        if (is_numeric($id)) $id = [$id];
        $ids = self::where('id', 'in', $id)->cache(60)->column('parent_id');
        if ($ids) {
            $ids = self::getAllParentIds($ids);
        } else {
            $ids = [];
        }
        return array_merge($id, $ids);
    }


    /**
     * 传入id获取当前和以下所的所有组织
     * @param int $id
     * @return array
     */
    public static function getAllChildIds($id = 0): array
    {
        $id_array = [];
        if ($id > 0) {
            $dep_id = self::where(['parent_id' => $id])->column('id');
        } else {
            $first = self::where(['parent_id' => $id])->column('id');
            foreach ($first as $item) {
                if (in_array($item, TmpSession::getDepAuth())) {
                    $dep_id[] = $item;
                    continue;
                }
            }
        }

        if ($dep_id) {
            foreach ($dep_id as $value) {
                $dep = self::where(['parent_id' => $value])->column('id');
                if ($dep) {
                    foreach ($dep as $item) {
                        if ($item) {
                            $dep_id2 = Department::getAllChildIds($item);
                            $id_array = array_unique(array_merge($dep_id2, [$item], $id_array));
                        }
                    }
                }
            }
        }
        if ($id > 0) {
            $id_array = array_unique(array_merge($dep_id, $id_array, [$id]));
        } else {
            $id_array = array_unique(array_merge($dep_id, $id_array));
        }
        return $id_array;
    }

    /**
     * 批量导入表格的组织数组
     * @param $arr
     * @return array|bool
     * @throws DbException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     */
    public static function import($arr)
    {
        $jump = [];
        $data_count = 0;
        $insert_succ = 0;
        $update_succ = 0;
        $dep_auth = TmpSession::getDepAuth();
        $err_array = [];
        foreach ($arr as $k => $v) {
            if ($k < 6) {
                continue;
            }
            $data_count++;
            if (empty(trim($v[1]))) {
                $jump[$k] = $v[0];
                continue;
            }
            $is_root_dep = false;

            $dep_name = trim($v[1]);//党组织名称
            $short_name = trim($v[2]);//党组织简称
            $parent_dep_name = trim($v[3]);//上级组织
            $sn = trim($v[4]);//党组织代码

            $type = trim($v[5]);//组织类别
            $type_arr = explode(' ', $type);
            if (count($type_arr) == 2 && isset($type_arr[1])) {
                $type = $type_arr[1];
            }
            $createtime = trim($v[6]);//党组织成立时间

            $belong_work_unit_type = trim($v[7]);//党组织所在单位情况
            $contact_person = trim($v[8]);//党组织联系人
            $contact_number = trim($v[9]);//联系电话

            $work_unit_name = trim($v[10]);//单位名称
            $work_unit_type = trim($v[11]);//单位类别
            $work_unit_type_arr = explode(' ', $work_unit_type);
            if (count($work_unit_type_arr) == 2 && isset($work_unit_type_arr[1])) {
                $work_unit_type = $work_unit_type_arr[1];
            }
            $work_unit_build_type = trim($v[12]);//单位建立党组织情况
            $work_unit_build_type_arr = explode(' ', $work_unit_build_type);
            if (count($work_unit_build_type_arr) == 2 && isset($work_unit_build_type_arr[1])) {
                $work_unit_build_type = $work_unit_build_type_arr[1];
            }
            $work_unit_sn = trim($v[13]);//党组织所在单位代码

            if (empty($dep_name)) {
                $err_array[$k][] = '党组织名称不能为空';
            } else {
                //判断重复
                foreach ($arr as $k2 => $v2) {
                    if ($k2 != $k && trim($v2[1]) == $dep_name) {
                        $err_array[$k][] = '党组织名称和第' . $k2 . '行重复';
                    }
                }
            }

            if (empty($short_name)) {
                $err_array[$k][] = '党组织简称不能为空';
            }

            if (empty($parent_dep_name)) {
                //如果是根节点，上级组织名称可以为空
                $root_dep = Department::where('parent_id', 0)->find();
                if ($dep_name != $root_dep['name']) {
                    $err_array[$k][] = '上级党组织名称不能为空';
                } else {
                    $is_root_dep = true;
                }
            }

            if ($dep_name == $parent_dep_name) {
                $err_array[$k][] = '党组织名称和上级组织一样';
            }

            if ($is_root_dep) {
                $parent_dep = [
                    'id' => 0,
                    'layer' => 0,
                ];
            } else {
                $parent_dep = Department::where('name|short_name', $parent_dep_name)->cache(60)->find();
            }
            if (!$parent_dep) {
                //上级组织在系统中不存在 查表格中是否存在
                $table_exist = false;
                foreach ($arr as $k2 => $v2) {
                    if ($k2 != $k && (trim($v2[1]) == $parent_dep_name) || (trim($v2[2]) == $parent_dep_name)) {
                        //在表格中存在
                        $table_exist = true;
                    }
                }
                if ($table_exist) {
                    //表格中存在 写入一条 数据先写这些，后面会根据表格进行更新
                    $dep = Department::create([
                        'name' => $parent_dep_name,
                        'show' => 1,
                        'status' => 1,
                        'layer' => 1,
                        'sort' => 0,
                        'type' => 611,
                    ]);
                    $dep_auth[] = $dep['id'];
                } else {
                    $err_array[$k][] = '上级党组织名称系统和表格中均不存在';
                }
            } else {
                if ($is_root_dep) {
                    //判断是否有根节点的管理权限
                    if (!in_array($root_dep['id'], $dep_auth)) {
                        $err_array[$k][] = '没有根节点的管理权限';
                    }
                } else {
                    //上级组织自己没有管理权限，跳过
                    if (!in_array($parent_dep['id'], $dep_auth)) {
                        $err_array[$k][] = '上级党组织没有管理权限不能管理之下组织';
                    }
                }
            }

            if (empty($type)) {
                $err_array[$k][] = '组织类别不能为空';
            } else {
                $type = array_search($type, Department::$type_options);//组织类别对应的编码
                if (!$type) {
                    $err_array[$k][] = '党组织类别选择错误';
                }
            }

            if (empty($belong_work_unit_type)) {
                $err_array[$k][] = '党组织所在单位情况不能为空';
            } else {
                $belong_work_unit_type = DicData::text2value('belong_work_unit_type', $belong_work_unit_type);
                if (!$belong_work_unit_type) {
                    $err_array[$k][] = '党组织所在单位情况在系统数据字典不存在';
                }
            }

            if ($createtime) {
                $createtime = gmdate('Y-m-d H:i', Date::excelToTimestamp($createtime));
                $validate = Validate::isDate($createtime);
                if ($validate !== true) {
                    $err_array[$k][] = '党组织成立时间格式不正确' . $createtime;
                }
            } else {
                $createtime = null;
            }

            if ($work_unit_type) {
                $work_unit_type = DicData::text2value('work_unit_type', $work_unit_type);
                if (!$work_unit_type) {
                    $err_array[$k][] = '单位类别在系统数据字典不存在';
                }
            }
            if ($work_unit_build_type) {
                $work_unit_build_type = DicData::text2value('work_unit_build_type', $work_unit_build_type);
                if (!$work_unit_build_type) {
                    $err_array[$k][] = '单位建立党组织情况在系统数据字典不存在';
                }
            }


            if (empty($err_array)) {
                $parent_id = $parent_dep['id'];
                $layer = $parent_dep['layer'] + 1;
                //组织基础信息
                $dep_data = [
                    'name' => filter_xss($dep_name),
                    'short_name' => filter_xss($short_name),
                    'parent_id' => $parent_id,
                    'sn' => filter_xss($sn),
                    'createtime' => $createtime,
                    'contact_person' => filter_xss($contact_person),
                    'contact_number' => filter_xss($contact_number),
                    'show' => 1,
                    'status' => 1,
                    'layer' => $layer,
                    'type' => $type,
                    'belong_work_unit_type' => $belong_work_unit_type,
                ];
                $has_dep = Department::where('name', $dep_name)->value('id');
                if ($has_dep) {
                    //系统中已存在 更新
                    $dep_data['id'] = $has_dep;
                    $dep = Department::update($dep_data, ['id' => $has_dep]);
                    if ($work_unit_name) {
                        //单位信息
                        $work_data = [
                            'dep_id' => $dep->id,
                            'name' => $work_unit_name,
                            'type' => $work_unit_type,
                            'build_type' => $work_unit_build_type,
                            'sn' => $work_unit_sn,
                        ];
                        $has_work_unit = WorkUnit::where('dep_id', $dep->id)
                            ->where('name', $work_unit_name)
                            ->find();
                        if ($has_work_unit) {
                            WorkUnit::update($work_data, ['dep_id' => $dep->id, 'name' => $work_unit_name]);
                        } else {
                            WorkUnit::create($work_data);
                        }
                    }
                    $update_succ++;
                } else {
                    $dep_data['sort'] = 1;
                    $dep = Department::create($dep_data);
                    if ($work_unit_name) {
                        //单位信息
                        $work_data = [
                            'dep_id' => $dep->id,
                            'name' => $work_unit_name,
                            'type' => DicData::text2value('work_unit_type', $work_unit_type),
                            'build_type' => DicData::text2value('work_unit_build_type', $work_unit_build_type),
                            'sn' => $work_unit_sn,
                        ];
                        WorkUnit::create($work_data);
                    }
                    $insert_succ++;
                }
                $dep_auth[] = $dep->id;
            }
        }
        if ($data_count == 0) {
            return self::setErrorInfo('读取到数据' . $data_count . '条，请检查表格第一个工作表中数据');
        }
        //全部正确返回 true 否则返回 错误信息的数组
        if (empty($err_array)) {
            if ($insert_succ == 0 && $update_succ == 0) {
                return self::setErrorInfo('导入/更新数据成功0条，请检查表格数据');
            }
        }
        if (empty($err_array)) {
            return self::setSuccessInfo(['insert_succ' => $insert_succ, 'update_succ' => $update_succ]);
        } else {
            return self::setErrorInfo($err_array);
        }
    }

    /**
     * 根据传进来用户名查找支部，如果是小组的，则根据父级查找支部
     * @param $user_id
     * @param int $dep_id
     * @return array|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getBranch($user_id, $dep_id = 0)
    {
        if ($dep_id == 0) {
            // 根据传进来用户名查找支部，如果是小组的，则根据父级查找支部
            $branch = self::useGlobalScope(false)
                ->alias('a')
                ->join('user_dep b', 'b.dep_id=a.id')
                ->where('b.user_id', '=', $user_id)
                ->where('a.type', 'in', [
                    Department::$type_branch,//党支部
                    Department::$type_tmp_branch,//临时党支部
                    Department::$type_party_committee_group,//党组
                    Department::$type_squad,//党小组
                ])
                ->field('a.*')
                ->find();
        } else {
            $branch = self::useGlobalScope(false)
                ->alias('a')
                ->where('a.id', '=', $dep_id)
                ->field('a.*')
                ->find();
        }
        if (empty($branch)) {
            return [];
        }
        if ($branch['type'] == Department::$type_branch) {
            return $branch;
        }

        if ($branch['parent_id'] > 0) {
            return Department::getBranch($user_id, $branch['parent_id']);
        }
        return [];
    }

    /**
     * 根据传进来用户id查找党委，如果不是，则递归往上查找
     * @param $user_id
     * @param int $dep_id
     * @return array|\PDOStatement|string|\think\Model
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getPartyCommittee($user_id, $dep_id = 0)
    {
        if ($dep_id == 0) {
            $branch = self::useGlobalScope(false)
                ->alias('a')
                ->join('user_dep b', 'b.dep_id=a.id')
                ->where('b.user_id', '=', $user_id)
                ->where('a.tenant_id', '=', TENANT_ID)
                ->field('a.*')
                ->find();
        } else {
            $branch = self::useGlobalScope(false)
                ->alias('a')
                ->where('a.id', '=', $dep_id)
                ->where('a.tenant_id', '=', TENANT_ID)
                ->field('a.*')
                ->find();
        }
        if (empty($branch)) {
            return [];
        }
        if ($branch['type'] == Department::$type_party_committee) {
            return $branch;
        }

        if ($branch['parent_id'] > 0) {
            return Department::getPartyCommittee($user_id, $branch['parent_id']);
        }
        return [];
    }
}