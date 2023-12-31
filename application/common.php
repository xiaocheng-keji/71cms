<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;


function jsonReturn($status = 0, $msg = '', $data = '')
{
    $info['status'] = $status;
    $info['msg'] = $msg;
    $info['result'] = $data;
    header('Content-Type:application/json');
    exit(json_encode($info));
}

function remove_xss($val)
{
    $val = htmlspecialchars_decode($val);
    $val = strip_tags($val, '<img><attach><u><p><b><i><a><strike><pre><code><font><blockquote><span><ul><li><table><tbody><tr><td><ol><iframe><embed>');
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
        $val = preg_replace('/(�{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
    }

    $ra1 = array('embed', 'iframe', 'frame', 'ilayer', 'layer', 'javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'object', 'frameset', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);

    $found = true;
    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(�{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2);
            $val = preg_replace($pattern, $replacement, $val);
            if ($val_before == $val) {
                $found = false;
            }
        }
    }
    $val = htmlspecialchars($val);
    return $val;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{

    if (is_array($list)) {
        $refer = array();
        $resultSet = array();

        foreach ($list as $i => $data) {


            $refer[$i] = $data[$field];

        }


        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

function getbaseurl()
{
    $baseUrl = str_replace('\\', '', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = empty($baseUrl) ? '/' : '/' . trim($baseUrl, '/') . '/';
    return $baseUrl;
}

function getusernamebyid($uid)
{
    if ($uid == 0) {
        return '所有人';
    } else {
        $children = Db::name('user')->where(['id' => $uid])->find();
        if (empty($children)) {

            $children = Db::name('admin_user')->where(['id' => $uid])->find();
            return $children['username'];
        } else {
            return $children['username'];
        }

    }


}

function friendlyDate($sTime, $type = 'normal', $alt = 'false')
{
    if (!$sTime)
        return '';
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $dTime = $cTime - $sTime;
    $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
    //$dDay     =   intval($dTime/3600/24);
    $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if ($type == 'normal') {
        if ($dTime < 60) {
            if ($dTime < 10) {
                return '刚刚';    //by yangjs
            } else {
                return intval(floor($dTime / 10) * 10) . "秒前";
            }
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
            //今天的数据.年份相同.日期相同.
        } elseif ($dYear == 0 && $dDay == 0) {
            //return intval($dTime/3600)."小时前";
            return '今天' . date('H:i', $sTime);
        } elseif ($dYear == 0) {
            return date("m月d日 H:i", $sTime);
        } else {
            return date("Y-m-d", $sTime);
        }
    } elseif ($type == 'mohu') {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime / 3600) . "小时前";
        } elseif ($dDay > 0 && $dDay <= 7) {
            return intval($dDay) . "天前";
        } elseif ($dDay > 7 && $dDay <= 30) {
            return intval($dDay / 7) . '周前';
        } elseif ($dDay > 30) {
            return intval($dDay / 30) . '个月前';
        }
        //full: Y-m-d , H:i:s
    } elseif ($type == 'full') {
        return date("Y-m-d , H:i:s", $sTime);
    } elseif ($type == 'ymd') {
        return date("Y-m-d", $sTime);
    } else {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime / 3600) . "小时前";
        } elseif ($dYear == 0) {
            return date("Y-m-d H:i:s", $sTime);
        } else {
            return date("Y-m-d H:i:s", $sTime);
        }
    }
}

function dir_create($path, $mode = 0777)
{
    if (is_dir($path)) {
        return TRUE;
    }
    $ftp_enable = 0;
    $path = dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir)) {
            continue;
        }
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

function format_bytes($size, $delimiter = '')
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 6; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

//用于生成用户密码的随机字符
function generate_password($length = 8)
{
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        // 这里提供两种字符获取方式
        // 第一种是使用 substr 截取$chars中的任意一位字符；
        // 第二种是取字符数组 $chars 的任意元素
        // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 数组层级缩进转换
 * @param array $array 源数组
 * @param int $pid
 * @param int $level
 * @return array
 */
function array2level($array, $pid = 0, $level = 1)
{
    static $list = [];

    foreach ($array as $v) {


        if ($v['pid'] == $pid) {

            $v['level'] = $level;
            $list[] = $v;

            array2level($array, $v['id'], $level + 1);
        }
    }

    return $list;
}

/**
 * 构建层级（树状）数组
 * @param array $array 要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $pid_name 父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree(&$array, $pid_name = 'pid', $child_key_name = 'children')
{
    $counter = array_children_count($array, $pid_name);
    if (!isset($counter[0]) || $counter[0] == 0) {
        return $array;
    }
    $tree = [];
    while (isset($counter[0]) && $counter[0] > 0) {
        $temp = array_shift($array);
        if (isset($counter[$temp['id']]) && $counter[$temp['id']] > 0) {
            array_push($array, $temp);
        } else {
            if ($temp[$pid_name] == 0) {
                $tree[] = $temp;
            } else {
                $array = array_child_append($array, $temp[$pid_name], $temp, $child_key_name);
            }
        }
        $counter = array_children_count($array, $pid_name);
    }
    return bubbleSort($tree);
}


function bubbleSort($numbers)
{
    $cnt = count($numbers);
    for ($i = 0; $i < $cnt - 1; $i++) {
        for ($j = 0; $j < $cnt - $i - 1; $j++) {
            if (($numbers[$j]['sort'] > $numbers[$j + 1]['sort'])
                || (($numbers[$j]['sort'] == $numbers[$j + 1]['sort']) && ($numbers[$j]['id'] > $numbers[$j + 1]['id']))) {
                $temp = $numbers[$j];
                $numbers[$j] = $numbers[$j + 1];
                $numbers[$j + 1] = $temp;
            }
        }
    }
    return $numbers;
}


/**
 * 子元素计数器
 * @param array $array
 * @param int $pid
 * @return array
 */
function array_children_count($array, $pid)
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }

    return $counter;
}

/**
 * 把元素插入到对应的父元素$child_key_name字段
 * @param        $parent
 * @param        $pid
 * @param        $child
 * @param string $child_key_name 子元素键名
 * @return mixed
 */
function array_child_append($parent, $pid, $child, $child_key_name)
{
    foreach ($parent as &$item) {
        if ($item['id'] == $pid) {
            if (!isset($item[$child_key_name]))
                $item[$child_key_name] = [];
            $item[$child_key_name][] = $child;
        }
    }

    return $parent;
}

/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DIRECTORY_SEPARATOR . $item)) {
                        delete_dir_file($dir_name . DIRECTORY_SEPARATOR . $item);
                    } else {
                        unlink($dir_name . DIRECTORY_SEPARATOR . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}

function encrypt($str)
{
    return md5(config("auth_code") . $str);
}

function array_val_key($arr)
{
    $new_arr = [];
    foreach ($arr as $k => $v) {
        $new_arr[$v] = $v;
    }
    return $new_arr;
}

function cat_options($pre = true, $cat_id = 0, $selected = 0, $type = [])
{
    static $options;
    $key = md5($pre . $cat_id . $selected . serialize($type));
    if ($options[$key]) return $options[$key];
    $category = new \app\common\model\Category();
    return $options[$key] = $category->cat_options($pre, $cat_id, $selected, $type);
}

function cat_select(array $type = [])
{
    $category = new \app\common\model\Category();
    $cat_list = $category->article_cat_list(0, 0, false, 0, $type);
    $Trees = new \util\Tree();
    $Trees->tree($cat_list, 'id', 'parent_id', 'name');
    $cat_list = $Trees->getArray();
    unset($cat_list[0]);
    $cat_options[] = [
        'title' => '栏目',
        'disabled' => false,
    ];
    foreach ($cat_list as $k => $v) {
        $cat_options[$k]['title'] = $v['name'];
        $cat_options[$k]['disabled'] = $v['type'] == 0;
    }
//    foreach ($cat_list as $k => $v) {
//        $str = '';
//        if ($v['level'] >= 1) {
//            $str = '|';
//            for ($i = 0; $i < $v['level']; $i++) {
//                $str .= '--';
//            }
//        }
//        $cat_options[$k]['title'] = $str . $v['name'];
//        $cat_options[$k]['disabled'] = $v['type'] == 0;
//    }
    return $cat_options;
}

function dep_options()
{
    static $options;
    if ($options) return $options;
    $department_model = new \app\common\model\Department();
    return $options = $department_model->optionsArr();
}

function get_img_src_from_str($str)
{
    preg_match_all('/<img[^>]*src\s*=\s*([\'"]?)([^\'" >]*)\1/isu', $str, $src);
    return $src[2];
}

/*
 * 计算是时间
 */
function format_date($time)
{
    $t = time() - $time;
    $f = array(
        '31536000' => '年',
        '2592000' => '个月',
        '604800' => '星期',
        '86400' => '天',
        '3600' => '小时',
        '60' => '分钟',
        '1' => '秒'
    );
    foreach ($f as $k => $v) {
        if (0 != $c = floor($t / (int)$k)) {
            return $c . $v . '前';
        }
    }
    return '刚刚';
}

/**
 * @param $file
 * @param bool $return_obj
 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
 */
function import_excel($file, $return_obj = false)
{
    ini_set('max_execution_time', '0');
    $objReader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
    $objSpreadsheet = $objReader->load($file);
    if ($return_obj) {
        return $objSpreadsheet;
    }
    $sheet = $objSpreadsheet->getSheet(0);
    // 取得总行数
    $highestRow = $sheet->getHighestRow();
    // 取得总列数
    $highestColumn = $sheet->getHighestColumn();
    //循环读取excel文件,读取一条,插入一条
    $data = array();
    //从第一行开始读取数据
    for ($j = 1; $j <= $highestRow; $j++) {
        //从A列读取数据
        for ($k = 'A'; $k <= $highestColumn; $k++) {
            // 读取单元格
            $data[$j][] = $sheet->getCell("$k$j")->getValue();
        }
    }
    return $data;
}

/**
 * 获取系统配置中某字段信息
 * @param string $name
 * @return array
 */
function getConfig($name = '')
{
    $site_config = \app\admin\model\Config::cache(60)->column('varname,value');
    if (is_array($name)) {
        $res = [];
        foreach ($name as $key) {
            $res[$key] = $site_config[$key];
        }
        return $res;
    } else {
        if (empty($name)) {
            return $site_config;
        } else {
            return $site_config[$name];
        }
    }
}

/**
 * @param array $fields 字段, eg: ['字段1', '字段2'...]
 * @param array $data 数据
 * @param string|null $fileName 导出文件名
 * return bool
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
 */
function makeExcel(array $fields = [], array $data = [], string $fileName = '')
{
    ob_clean();
    if (!is_array($fields) || !is_array($data)) {
        return false;
    }
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    // sheet title
    $sheet->setTitle(date('Y-m-d'));
    $startRow = 1; // 数据起始行
    foreach ($data as $key => $value) {
        foreach ($fields as $k => $v) {
            $column = PHPExcel_Cell::stringFromColumnIndex($k);
            if ($key == 0) {
                $sheet->setCellValue($column . $startRow, $v['name']);
                $sheet->getColumnDimension($column)->setWidth(20);
            }
            $i = $key + 2; //表格是从2开始的
            $sheet->setCellValue($column . $i, $value[$v['key']]);
        }
    }
    // make file
    $write = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // 07Excel
//        header ( 'Content-Type:application/vnd.ms-excel' ); // Excel03
    header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"'); // filename
    header('Cache-Control: max-age=0'); // forbid cached
    header('Pragma: public');
    $write->save('php://output');
    exit;
}

/*
 * 获取指定目录下指定文件后缀的函数
 * @$path   文件路径
 * @$filename   使用时请提前赋值为空数组
 * @$recursive  是否递归查找，默认为false
 * @$ext    文件后缀名，默认为false为不指定，如果指定，请以数组方式传入
 * @$baseurl    是否包含路径，默认包含
 */
function getDirFilesLists($path, &$filename, $recursive = false, $ext = false, $baseurl = true)
{

    if (!$path) {
        die('请传入目录路径');
    }
    $resource = opendir($path);
    if (!$resource) {
        die('你传入的目录不正确');
    }
    //遍历目录
    while ($rows = readdir($resource)) {
        //如果指定为递归查询
        if ($recursive) {
            if (is_dir($path . '/' . $rows) && $rows != "." && $rows != "..") {
                getDirFilesLists($path . '/' . $rows, $filename, $resource, $ext, $baseurl);
            } elseif ($rows != "." && $rows != "..") {
                //如果指定后缀名
                if ($ext) {
                    //必须为数组
                    if (!is_array($ext)) {
                        die('后缀名请以数组方式传入');
                    }
                    //转换小写
                    foreach ($ext as &$v) {
                        $v = strtolower($v);
                    }
                    //匹配后缀
                    $file_ext = strtolower(pathinfo($rows)['extension']);
                    if (in_array($file_ext, $ext)) {
                        //是否包含路径
                        if ($baseurl) {
                            $filename[] = $path . '/' . $rows;
                        } else {
                            $filename[] = $rows;
                        }
                    }
                } else {
                    if ($baseurl) {
                        $filename[] = $path . '/' . $rows;
                    } else {
                        $filename[] = $rows;
                    }
                }
            }
        } else {
            //非递归查询
            if (is_file($path . '/' . $rows) && $rows != "." && $rows != "..") {
                if ($baseurl) {
                    $filename[] = $path . '/' . $rows;
                } else {
                    $filename[] = $rows;
                }
            }
        }
    }
}


if (!function_exists('sql_filter')) {
    /**
     * sql 参数过滤
     * @param string $str
     * @return mixed
     */
    function sql_filter($str)
    {
        if (!is_string($str)) return $str;
        $filter = ['select ', 'insert ', 'update ', 'delete ', 'drop', 'truncate ', 'declare', 'xp_cmdshell', '/add', ' or ', 'exec', 'create', 'chr', 'mid', ' and ', 'execute'];
        $toupper = array_map(function ($str) {
            return strtoupper($str);
        }, $filter);
        return str_replace(array_merge($filter, $toupper, ['%20']), '', $str);
    }
}

if (!function_exists('filter_xss')) {
    /**
     * 过滤xss
     *
     * @param $dirty_html
     * @return string
     */
    function filter_xss($dirty_html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $dirty_html = $purifier->purify($dirty_html);
        return $dirty_html;
    }
}



if (!function_exists('mime_content_type')) {
    /**
     * 获取文件的mime_content类型
     * @return string
     */
    function mime_content_type($filename)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }

        static $contentType = array(
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asc' => 'application/pgp', //changed by skwashd - was text/plain
            'asf' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'c' => 'text/plain', // or 'text/x-csrc', //added by skwashd
            'cc' => 'text/plain', // or 'text/x-c++src', //added by skwashd
            'cs' => 'text/plain', //added by skwashd - for C# src
            'cpp' => 'text/x-c++src', //added by skwashd
            'cxx' => 'text/x-c++src', //added by skwashd
            'cdf' => 'application/x-netcdf',
            'class' => 'application/octet-stream',//secure but application/java-class is correct
            'com' => 'application/octet-stream',//added by skwashd
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'csv' => 'text/comma-separated-values',//added by skwashd
            'dcr' => 'application/x-director',
            'diff' => 'text/diff',
            'dir' => 'application/x-director',
            'dll' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dot' => 'application/msword',//added by skwashd
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'h' => 'text/plain', // or 'text/x-chdr',//added by skwashd
            'h++' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hh' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hpp' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hxx' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference/x-cooltalk',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'ifb' => 'text/calendar',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jar' => 'application/x-jar', //added by skwashd - alternative mime type
            'java' => 'text/x-java-source', //added by skwashd
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'kar' => 'audio/midi',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'log' => 'text/plain',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'patch' => 'text/diff',
            'pbm' => 'image/x-portable-bitmap',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pgp' => 'application/pgp',//added by skwashd
            'php' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php3',
            'pl' => 'application/x-perl',
            'pm' => 'application/x-perl',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'po' => 'text/plain',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-realaudio',
            'rar' => 'application/octet-stream',
            'ram' => 'audio/x-pn-realaudio',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rm' => 'audio/x-pn-realaudio',
            'roff' => 'application/x-troff',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'shtml' => 'text/html',
            'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'stc' => 'application/vnd.sun.xml.calc.template',
            'std' => 'application/vnd.sun.xml.draw.template',
            'sti' => 'application/vnd.sun.xml.impress.template',
            'stw' => 'application/vnd.sun.xml.writer.template',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'swf' => 'application/x-shockwave-flash',
            'sxc' => 'application/vnd.sun.xml.calc',
            'sxd' => 'application/vnd.sun.xml.draw',
            'sxg' => 'application/vnd.sun.xml.writer.global',
            'sxi' => 'application/vnd.sun.xml.impress',
            'sxm' => 'application/vnd.sun.xml.math',
            'sxw' => 'application/vnd.sun.xml.writer',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tgz' => 'application/x-gtar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vbs' => 'text/plain', //added by skwashd - for obvious reasons
            'vcd' => 'application/x-cdlink',
            'vcf' => 'text/x-vcard',
            'vcs' => 'text/calendar',
            'vfb' => 'text/calendar',
            'vrml' => 'model/vrml',
            'vsd' => 'application/vnd.visio',
            'wav' => 'audio/x-wav',
            'wax' => 'audio/x-ms-wax',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wm' => 'video/x-ms-wm',
            'wma' => 'audio/x-ms-wma',
            'wmd' => 'application/x-ms-wmd',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wmz' => 'application/x-ms-wmz',
            'wrl' => 'model/vrml',
            'wvx' => 'video/x-ms-wvx',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'text/xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'z' => 'application/x-compress',
            'zip' => 'application/zip',
        );
        $type = strtolower(substr(strrchr($filename, '.'), 1));
        if (isset($contentType[$type])) {
            $mime = $contentType[$type];
        } else {
            $mime = 'application/octet-stream';
        }
        return $mime;
    }
}

function http($url, $params = array(), $method = 'GET', $header = array(), $multi = false)
{
    $opts = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );

    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            if ($params) {
                // 先判断 url 是否已经有参数，有则追加到 url 后面
                if (stripos($url, '?') !== false) {
                    $url = $url . '&' . http_build_query($params);
                } else {
                    $url = $url . '?' . http_build_query($params);
                }
            }
            $opts[CURLOPT_URL] = $url;
            break;
        case 'POST':
            //判断是否传输文件
            if ($params) {
                $params = $multi ? $params : http_build_query($params);
            }
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new \Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) throw new \Exception('请求发生错误：' . $error);
    return $data;
}

/**
 * 生成 H5 的页面的地址
 *
 * @param string $url
 * @param string $vars
 * @param string $domain
 * @return string
 */
function h5_url(string $url = '', $vars = '', $domain = SITE_URL)
{
    if (is_array($vars) || is_object($vars)) {
        $arg = http_build_query($vars);
    } else {
        $arg = '';
    }
    if ($arg) {
        $arg = '?' . $arg;
    }
    return $domain . '/h5/' . $url . $arg;
}

/**
 * 补全地址 为了APP的时候能正常显示
 * @param $url
 * @return mixed|string
 */
function complete_url($url)
{
    if ($url && is_string($url) && strtolower(substr($url, 0, 4)) != 'http') {
        return SITE_URL . $url;
    }
    return $url;
}

/**
 * 下载文件的token生成
 * @param $savename
 * @return string
 */
function file_token_url($savename)
{
    $token = md5(\app\common\model\TmpSession::getLoginId() . time() . $savename);
    //有效期一个小时
    \think\facade\Cache::set($token, $savename, 3600);
    return $token;
}


/**
 * 读取附件信息
 *
 * @param $list
 * @return array
 */
function getFileList($list, $field = '*', $site_url = SITE_URL)
{
    if ($list) {
        return \app\common\model\File::getFileList($list, $field, $site_url);
    }
    return [];
}