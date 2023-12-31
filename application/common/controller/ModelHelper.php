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

namespace app\common\controller;


use function PHPSTORM_META\type;

class ModelHelper
{

    public $method = 'get';

    public $title = '';

    public $fields = [];

    public $search_fields = [];

    public $data = [];

    public $add_url = '';

    public $edit_url = '';

    public $del_url = '';

    public $list_url = '';

    public $form_tpl = 'common@model_helper/form';

    public $item_tpl = 'common@model_helper/list';

    public $list_tpl = 'common@model_helper/list';

    public $top_btn = [];

    public $row_btn = [];

    public $tips = [];

    public $page_where = [];

    public $limit = 10;

    public $page = true;

    public $toolbar_id = '';

    public $show_back_btn = false;

    public $style = '';

    public $submit_btn_text = '立即提交';

    public function setAddUrl($url)
    {
        $this->add_url = $url;
        return $this;
    }

    public function setEditUrl($url)
    {
        $this->edit_url = $url;
        return $this;
    }

    public function setListUrl($url)
    {
        $this->list_url = $url;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param $title
     * @return $this
     * 设置标题
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setToolbarId($value)
    {
        $this->toolbar_id = $value;
        return $this;
    }

    public function addTips($text)
    {
        $this->tips[] = $text;
        return $this;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function setPage($page)
    {
        if (is_array($page)) {
            $page = json_encode($page, JSON_UNESCAPED_UNICODE);
        } else {
            $page = $page ? json_encode(['curr' => $page], JSON_UNESCAPED_UNICODE) : 'false';
        }
        $this->page = $page;
        return $this;
    }

    public function addTopBtn(string $title, string $href, $target = '_self', $extra = [])
    {
        $btn = [
            'title' => $title,
            'href' => $href,
            'target' => $target,
        ];
        foreach ($extra as $k => $v) {
            $btn[$k] = $v;
        }
        $this->top_btn[] = $btn;
        return $this;
    }

    public function showBackBtn(bool $show)
    {
        $this->show_back_btn = $show;
        return $this;
    }

    /**
     * 添加分页参数
     * @param [array] $where 分页最加的参数数组
     */
    public function addPageWhere($where)
    {
        $this->page_where = $where;
        return $this;
    }


    /**
     * @param string $title
     * @param string $href
     * @param string $htmlID layui表格工具行toolbar 对应名字ID 即 <script id="$htmlID">....</script>
     * @param null $opt 按钮显示的条件(紧与当前行有关) 结构 field（列表字段）、operator（运算符）、value（值） 或 自己写一个字符串、ext_param（扩展url参数请求，默认当前行id，目前适用于btn类型，如['ext_param'=>pid],?id=60(当前行id)&pid=1(当前行的pid字段)）
     * @param string $class btn、btn-container、btn-danger、btn-fluid、btn-group、btn-warm、btn-normal、btn-primary、btn-warm
     * @return $this
     */
    public function addRowBtn(string $title, string $href, $htmlID = 'barDemo', $opt = null, $class = '', $btn = 'btn', array $extra = [])
    {
        return $this->addRowBtnEx($title, $href, array('htmlID' => $htmlID, 'opt' => $opt, 'class' => $class, 'type' => $btn), $extra);
    }

    /**
     * @param string $title
     * @param string $href
     * @param null $exProperty array(...) htmlID、opt、class、type、attr:自定义参数、value[0,1]其中0为未选中值，1为选中值
     * htmlID 即layui表格工具行toolbar 对应名字ID 即 <script id="$htmlID">....</script>
     * opt 按钮显示的条件(紧与当前行有关) 结构 field（列表字段）、operator（运算符）、value（值） 或 自己写一个字符串(当不写条件默认为满足条件显示，)
     * class  class里的 btn、btn-container、btn-danger、btn-fluid、btn-group、btn-warm、btn-normal、btn-primary、
     * type   支持btn 、checkbox
     * value  页面html里的value值
     * @return $this
     */
    public function addRowBtnEx(string $title, string $href, $exProperty = null, array $extra = [])
    {
        empty($exProperty) ? $exProperty = array() : true;
        empty($exProperty['htmlID']) ? $exProperty['htmlID'] = "barDemo" : true;
        empty($exProperty['opt']) ? $exProperty['opt'] = "" : true;
        empty($exProperty['class']) ? $exProperty['class'] = "" : true;
        empty($exProperty['type']) ? $exProperty['type'] = "btn" : true;
        empty($exProperty['value']) ? $exProperty['value'] = "" : true;
        empty($exProperty['name']) ? $exProperty['name'] = "" : true;
        empty($exProperty['id']) ? $exProperty['id'] = "" : true;
        empty($exProperty['ext_param']) ? $exProperty['ext_param'] = [] : true;

        if (!empty($exProperty['id'])) {
            $exProperty['id'] = "d['" . $exProperty['id'] . "']";
        }

        $optStr = '';
        //转化为layui显示形式
        if (!empty($exProperty['opt'])) {
            if (is_array($exProperty['opt'])) {
                if (!empty($exProperty['opt']['field'])) {
                    $optStr = "d['" . $exProperty['opt']['field'] . "']";
                    if (!empty($exProperty['opt']['operator'])) {
                        $optStr = $optStr . $exProperty['opt']['operator'];
                        if (isset($exProperty['opt']['value'])) {
                            $optStr = $optStr . $exProperty['opt']['value'];
                        }
                    }
                } else {
                    $optStr = '';
                }
            } else {
                $optStr = $exProperty['opt'];
            }
        }
        if ($exProperty['type'] == 'btn') {
            if (empty($exProperty['class'])) {
                $exProperty['class'] = 'layui-btn-normal';
            } else {
                if (strpos($exProperty['class'], 'btn') === 0) {
                    $exProperty['class'] = "layui-" . $exProperty['class'];
                }
            }
        }

        if (empty($optStr)) {
            $optStr = 'true';
        }

        //追加url参数
        $param = '';
        if (isset($exProperty['opt']['ext_param'])) {

            foreach ($exProperty['opt']['ext_param'] as $k => $v) {
                if (empty($param)) {
                    $param = $param . "?{$k}={{= d['{$v}']}}";
                } else {
                    $param = $param . "&{$k}={{= d['{$v} ']}}";
                }
            }
        }
        if (empty($param)) {
            $param = "?id={{= d['id']}}";
        }
        if ($exProperty['type'] == 'checkbox') {
            $param = '';
        }

        if (!is_array($exProperty['value'])) {
            $value = $exProperty['value'];
            $exProperty['value'] = [0 => $value, 1 => $value];
        }

        $btn = [
            'title' => $title,
            'href' => $href,
            'opt' => $optStr,
            'class' => $exProperty['class'],
            'type' => $exProperty['type'],
            'value' => $exProperty['value'],
            'name' => $exProperty['name'],
            'id' => $exProperty['id'],
            'attr' => $exProperty['attr'],
            'ext_param' => $param,
        ];
        foreach ($extra as $k => $v) {
            $btn[$k] = $v;
        }
        $this->row_btn[$exProperty['htmlID']][] = $btn;

        return $this;
    }

    /**
     * @param $title
     * @param $name
     * @param string $type
     * @param array $extra
     * @return $this
     * 增加字段
     */
    public function addField(string $title, $name, $type = 'text', array $extra = [])
    {
        $field = array(
            'title' => $title,
            'field' => $name,
            'type' => $type
        );
        foreach ($extra as $k => $v) {
            $field[$k] = $v;
        }
        if ($type == 'image' && !isset($field['templet'])) {
            //如果是单图 显示图片 TODO 鼠标悬停预览
            $field['templet'] = '<div><a href="{{d[\'' . $name . '\']}}" target="_blank"><img style="width:auto;height:28px;display:block;" src="{{d[\'' . $name . '\']}}"></a></div>';
        }
        $this->fields[] = $field;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function addFieldset(string $text = '')
    {
        $field = array(
            'type' => 'fieldset',
            'text' => $text,
        );
        $this->fields[] = $field;
        return $this;
    }

    /**
     * @param string $title
     * @param string $name
     * @param string $type
     * @param array $extra
     * @return $this
     * 增加搜索字段
     */
    public function addSearchField(string $title, string $name, string $type = 'text', array $extra = [])
    {
        $field = array(
            'title' => $title,
            'field' => $name,
            'type' => $type
        );
        foreach ($extra as $k => $v) {
            $field[$k] = $v;
        }
        $this->search_fields[] = $field;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     * 设置字段对应的数据
     */
    public function setData($data)
    {
        $this->data = $data;
        foreach ($this->fields as $key => $value) {
            if (isset($data[$value['field']]) && $value['type'] != 'password') {
                //整理字段值用于显示 废弃 设置格式请在模型中设置
                switch ($value['type']) {
//                    case 'datetime':
//                        if (ctype_digit($data[$value['field']]) && $data[$value['field']] <= 2147483647) {
//                            $this->fields[$key]['value'] = date('Y-m-d H:i:s', $data[$value['field']] ?? time());
//                        } else {
//                            $this->fields[$key]['value'] = $data[$value['field']];
//                        }
//                        break;
//                    case 'date':
//                        $this->fields[$key]['value'] = date('Y-m-d', $data[$value['field']] ?? time());
//                        break;
                    default:
                        $this->fields[$key]['value'] = $data[$value['field']];
                        break;
                }
            } elseif(isset($value['field']) && stripos($value['field'], '[') !== false) {
                // 支持字段名为数组的情况
                $fieldValue = self::getValueByField($value['field'], $data);
                if ($fieldValue === null && isset($value['value'])) {
                    $fieldValue = $value['value'];
                }
                $this->fields[$key]['value'] = $fieldValue;
            }
        }
        return $this;
    }

    /**
     * 展示表单
     */
    public function showForm($showBackBtn = true)
    {
        $this->showBackBtn($showBackBtn);
        return view($this->form_tpl, ['title' => $this->title, 'fields' => $this->fields, 'tips' => $this->tips, 'showBackBtn' => $this->show_back_btn, 'style' => $this->style, 'submit_btn_text' => $this->submit_btn_text]);
    }

    public function showItem()
    {
        return view($this->item_tpl, ['title' => $this->title, 'fields' => $this->fields]);
    }

    public function showList()
    {
        $cols = [];//{field: 'id', title: 'ID', width: 80, fixed: 'left', unresize: true, sort: true,templet: function(d){
        //return 'ID：'+ d.id +'，标题：<span style="color: #c00;">'+ d.title +'</span>'
        //}}
        foreach ($this->fields as $key => $value) {
            //整理字段值用于显示
            switch ($value['type']) {
                case 'select':
                    //$this->fields[$key]['templet'] = '';
                    break;
                default:
                    break;
            }
        }
        foreach ($this->fields as $k => $v) {
            $cols[] = $v;
        }
//        dump($cols);die;
//        dump($this->row_btn);die;
        $cols = json_encode([$this->fields], JSON_UNESCAPED_UNICODE);
        $this->page === true && $this->setPage(input('page', 1));
        return view($this->list_tpl, [
            'title' => $this->title,
            'search_fields' => $this->search_fields,
            'fields' => $this->fields,
            'cols' => $cols,
            'top_btn' => $this->top_btn,
            'row_btn' => $this->row_btn,
            'tips' => $this->tips,
            'page_where' => $this->page_where,
            'limit' => $this->limit,
            'page' => $this->page,
            'toolbar_id' => $this->toolbar_id,
            'style' => $this->style,
            'showBackBtn' => $this->show_back_btn,
            'method' => $this->method
        ]);
    }

    public function getSearchWhere()
    {
        $where = [];
        $vTypeArr = [];
        foreach ($this->search_fields as $key => $value) {
            $vTypeArr = explode("|", $value['type']);
            $var = explode("|", $value['field']);
            foreach ($var as $sKey => $sValue) {
                $v = input($sValue, 0);
                $isArr = sizeof($var) > 1 ? true : false;
                if ($v != '') {
                    if (strtolower($isArr ? $value[$sKey]['exp'] : $value['exp']) == 'like') {
                        $v = '%' . $v . '%';
                    } elseif (($isArr ? $value[$sKey]['exp'] : $value['exp']) == 'between time') {
                        $v = explode($value['range'], $v);
                        $v[1] = strtotime($v[1] . '+1 day') - 1;
                    }

//                var_dump(is_callable($value['func']));
                    if (is_callable($isArr ? $value[$sKey]['func'] : $value['func'])) {
                        $v = $isArr ? $value[$sKey]['func']($v) : $value['func']($v);
                    } else {
                        if ($value['type'] == 'date' && $value['range'] == 'false') {
                            $v = strtotime($v);
                        }
                    }
                    if (sizeof($vTypeArr) >= $sKey && $vTypeArr[$sKey] == "hidden") {
                        $where[] = [$isArr ? $sValue : $value['field'], $isArr ? $value[$sKey]['exp'] : $value['exp']];
                    } else {
                        $where[] = [$isArr ? $sValue : $value['field'], $isArr ? $value[$sKey]['exp'] : $value['exp'] ?? '=', $v];
                    }
                }
            }

            if (sizeof($vTypeArr) > 1) {
                $type = '';
                $mField = '';
                foreach ($vTypeArr as $tKey => $tValue) {
                    if ($tValue != 'hidden') {
                        $type = $type . $tValue . "|";
                        if (sizeof($var) >= $tKey) {
                            $mField = $mField . $var[$tKey] . '|';
                        }
                    }
                }
                $this->search_fields[$key]['type'] = substr($type, 0, strlen($type) - 1);
                $this->search_fields[$key]['field'] = substr($mField, 0, strlen($mField) - 1);
            }
        }
        return $where;
    }

    //废弃 请在模型中设置
    static function transformUnixTime($field)
    {
        //TODO 需要成加载模块的方式
//        return '<div>{{ layui.laytpl.toDateString(d.'.$field.') }}</div>';
        return '<div>{{formatTime(d[\'' . $field . '\']) }}</div>';
    }

    static function getValueByField($name, $array)
    {
        $keys = explode('[', str_replace(']', '', $name)); // 将名称拆分成键名数组
        $value = $array; // 初始值为整个数组
        foreach ($keys as $key) {
            if (isset($value[$key])) { // 如果键名存在，则继续获取下一级
                $value = $value[$key];
            } else { // 如果键名不存在，则返回空值
                return null;
            }
        }
        return $value;
    }

}