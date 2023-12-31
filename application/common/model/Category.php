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

use think\Db;
use think\Model;
use util\Tree;


class Category extends Model
{
    const TYPE_PARENT = 0;
    const TYPE_ARTICLE = 1;
    const TYPE_MEETING = 2;
    const TYPE_ACTIVITY = 3;

    const TYPE_ARRAY = [
        self::TYPE_PARENT => '父级栏目',
        self::TYPE_ARTICLE => '文章',
        self::TYPE_MEETING => '会议',
        self::TYPE_ACTIVITY => '活动',
    ];

    /**
     * 获得指定分类下的子分类的数组
     *
     * @access  public
     * @param int $cat_id 分类的ID
     * @param int $selected 当前选中分类的ID
     * @param boolean $re_html 返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param int $level 限定返回的级数。为0时返回所有级数
     * @param int $type 分类的类型 0返回全部类型
     * @return  mix
     */
    public function article_cat_list($cat_id = 0, $selected = 0, $re_html = true, $level = 0, $type = [])
    {
        static $res = NULL;

        if ($res === NULL) {
            $data = false;//read_static_cache('art_cat_pid_releate');
            if ($data === false) {
                $where = "";
                /*if ($type == 0) {
                    $where = " where 1=1 ";
                } else {
                    $where = " where c.type=" . $type;
                }*/
                $prefix = Db::getConfig('prefix');
                $sql = "SELECT c.*, COUNT(s.id) AS has_children FROM {$prefix}category AS c" .
                    " LEFT JOIN {$prefix}category AS s ON s.parent_id=c.id $where " .
                    " GROUP BY c.id  ORDER BY parent_id, sort";
                $res = Db::name('category')->query($sql);
                //write_static_cache('art_cat_pid_releate', $res);
            } else {
                $res = $data;
            }
        }

        if (empty($res) == true) {
            return $re_html ? '' : array();
        }

        $options = $this->article_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

        /* 截取到指定的缩减级别 */
        if ($level > 0) {
            if ($cat_id == 0) {
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

        if (!empty($type) && is_array($type)) {
            foreach ($options as $key => $var) {
                if ($var['type'] != 0 && !in_array($var['type'], $type)) {
                    unset($options[$key]);
                }
            }
            foreach ($options as $key => $value) {
                if ($value['type'] == 0 && $this->has_children($value['id'], $options) == false) {
                    unset($options[$key]);
                }
            }
            foreach ($options as $key => $value) {
                if ($value['type'] == 0 && $this->has_children($value['id'], $options) == false) {
                    unset($options[$key]);
                }
            }
        }
        if ($re_html == true) {
            $select = '';
            foreach ($options as $var) {
                $select .= '<option value="' . $var['id'] . '" ';
                $select .= ' cat_type="' . $var['cat_type'] . '" ';
                $select .= ($selected == $var['id']) ? "selected='ture'" : '';
                $select .= '>';
                if ($var['level'] > 0) {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
            }

            return $select;
        } else {
            return $options;
        }
    }

    public function has_children($cat_id, $cats)
    {
        foreach ($cats as $k => $v) {
            if ($cat_id == $v['parent_id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
     *
     * @access  private
     * @param int $cat_id 上级分类ID
     * @param array $arr 含有所有分类的数组
     * @param int $level 级别
     * @return  void
     */
    public function article_cat_options($spec_cat_id, $arr)
    {
        static $cat_options = array();

        if (isset($cat_options[$spec_cat_id])) {
            return $cat_options[$spec_cat_id];
        }

        if (!isset($cat_options[0])) {
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            while (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    $cat_id = $value['id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                    break;
                }
            }
            $cat_options[0] = $options;
        } else {
            $options = $cat_options[0];
        }

        if (!$spec_cat_id) {
            return $options;
        } else {
            if (empty($options[$spec_cat_id])) {
                return array();
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options as $key => $value) {
                if ($key != $spec_cat_id) {
                    unset($options[$key]);
                } else {
                    break;
                }
            }

            $spec_cat_id_array = array();
            foreach ($options as $key => $value) {
                if (($spec_cat_id_level == $value['level'] && $value['id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])) {
                    break;
                } else {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }

    /**
     * @param bool $pre 前缀
     * @param int $cat_id
     * @param int $selected
     * @return array
     */
    public function cat_options($pre = true, $cat_id = 0, $selected = 0, $type = [])
    {
        $cat_list = $this->article_cat_list($cat_id, $selected, false, 0, $type);
        $Trees = new Tree();
        $Trees->tree($cat_list, 'id', 'parent_id', 'name');
        $cat_list = $Trees->getArray();
        $cat_list[0] = [
            'id' => 0,
            'name' => '顶级'
        ];
        foreach ($cat_list as $k => $v) {
            $options[$k] = $v['name'];
        }
        return $options;
    }
}