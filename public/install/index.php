<?php

if (file_exists('./install.lock')) {
    echo '
		<html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        </head>
        <body>
        	你已经安装过该系统，如果想重新安装，请先删除站点install目录下的 install.lock 文件，然后再安装。
        </body>
        </html>';
    exit;
}
@set_time_limit(1000);

if ('7.0.0' > phpversion()) {
    header("Content-type:text/html;charset=utf-8");
    exit('您的php版本过低，不能安装本软件，请升级到7.0.0或更高版本再安装，谢谢！');
}
date_default_timezone_set('PRC');
error_reporting(E_ALL & ~E_NOTICE);
header('Content-Type: text/html; charset=UTF-8');
define('SITEDIR', _dir_path(substr(dirname(__FILE__), 0, -8)));//入口文件目录
define('CMSDIR', _dir_path(substr(dirname(__FILE__), 0, -15)));//项目目录
define('SYS_NAME', '71CMS创先云智慧党建系统');
$current_version = file_get_contents(CMSDIR . '/application/version.php');
$envFile = '.env';
//检查根目录是否有.env文件，没有则复制.example.env一份
if (!file_exists(CMSDIR . $envFile)) {
    copy(CMSDIR . '.example.env', CMSDIR . $envFile);
}
if (is_dir(CMSDIR . 'application/tenant')) {
    echo '独立部署模式需要删除Tenant模块!';
    exit;
}

$step = isset($_GET['step']) ? $_GET['step'] : 1;

//地址
$scriptName = !empty($_SERVER["REQUEST_URI"]) ? $scriptName = $_SERVER["REQUEST_URI"] : $scriptName = $_SERVER["PHP_SELF"];
$rootpath = @preg_replace("/\/(I|i)nstall\/index\.php(.*)$/", "", $scriptName);
$domain = empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
if ((int)$_SERVER['SERVER_PORT'] != 80) {
    $domain .= ":" . $_SERVER['SERVER_PORT'];
}
$domain = $domain . $rootpath;
switch ($step) {
    case '1':
        include_once("./templates/step1.php");
        exit();
    case '2':
        $phpv = @ phpversion();
        $phpv = '<span class="span-ok">√ ' . $phpv . '</span> ';
        $os = PHP_OS;
        //$os = php_uname();
        $tmp = function_exists('gd_info') ? gd_info() : array();
        $server = $_SERVER["SERVER_SOFTWARE"];
        $host = (empty($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_HOST"] : $_SERVER["SERVER_ADDR"]);
        $name = $_SERVER["SERVER_NAME"];
        $max_execution_time = ini_get('max_execution_time');
        $allow_reference = (ini_get('allow_call_time_pass_reference') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
        $allow_url_fopen = (ini_get('allow_url_fopen') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
        $safe_mode = (ini_get('safe_mode') ? '<font color=red>[×]On</font>' : '<font color=green>[√]Off</font>');

        $err = 0;
        if (empty($tmp['GD Version'])) {
            $gd = '<span class="span-no">×</span>';
            $err++;
        } else {
            $gd = '<span class="span-ok">√</span>';
        }
        if (function_exists('mysqli_connect')) {
            $mysql = '<span class="span-ok">√</span>';
        } else {
            $mysql = '<span class="span-no">× 请安装mysqli扩展</span>';
            $err++;
        }
        if (ini_get('file_uploads')) {
            $uploadSize = '<span class="span-ok">√</span> ';
        } else {
            $uploadSize = '<span class="span-no">×</span>';
        }
        if (function_exists('session_start')) {
            $session = '<span class="span-ok">√ </span>';
        } else {
            $session = '<span class="span-no">× </span> ';
            $err++;
        }
        if (function_exists('curl_init')) {
            $curl = '<span class="span-ok">√</span>';
        } else {
            $curl = '<span class="span-no">×</span>';
            $err++;
        }
        if (function_exists('file_put_contents')) {
            $file_put_contents = '<span class="span-ok">√</span>';
        } else {
            $file_put_contents = '<span class="span-no">×</span>';
            $err++;
        }
        if (function_exists('bcadd')) {
            $BC = '<span class="span-ok">√</span>';
        } else {
            $BC = '<span class="span-no">×</span>';
            $err++;
        }
        if (function_exists('openssl_encrypt')) {
            $openssl = '<span class="span-ok">√</span>';
        } else {
            $openssl = '<span class="span-no">×</span>';
            $err++;
        }

        $folder = array(
            'public/install',
            'public/uploads',
            'runtime',
            'runtime/cache',
            'runtime/temp',
            'runtime/log',
            '.env',
        );
        include_once("./templates/step2.php");
        exit();
    case '3':
        $dbname = strtolower(trim($_POST['dbname']));
        $_POST['dbport'] = $_POST['dbport'] ? $_POST['dbport'] : '3306';
        if ($_POST['test']) {
            $dbHost = $_POST['dbhost'];
            $conn = @mysqli_connect($dbHost, $_POST['dbuser'], $_POST['dbpw'], NULL, $_POST['dbport']);
            if (!$conn) {
//                returnJson(0, '数据库连接失败！<br>'.mysqli_connect_errno().':'.mysqli_connect_error());
                returnJson(0, '数据库连接失败！错误代码：' . mysqli_connect_errno());
            } else {
                $result = mysqli_query($conn, "SELECT @@global.sql_mode");
                $result = $result->fetch_array();
                $version = mysqli_get_server_info($conn);
                if ($version >= 5.7) {
                    if (strstr($result[0], 'STRICT_TRANS_TABLES') || strstr($result[0], 'STRICT_ALL_TABLES') || strstr($result[0], 'TRADITIONAL') || strstr($result[0], 'ANSI'))
                        returnJson(-1, '请修改数据库配置 sql_mode=NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION');
                }
                $result = mysqli_query($conn, "select count(table_name) as c from information_schema.`TABLES` where table_schema='$dbname'");
                $result = $result->fetch_array();
                if ($result['c'] > 0) {
                    returnJson(-2, '数据库已存在');
                }
                returnJson(1, '', $result);
            }
        }
        include_once("./templates/step3.php");
        exit();
    case '4':
        if (intval($_GET['install'])) {
            $n = intval($_GET['n']);
            $arr = array();

            $dbHost = trim($_POST['dbhost']);
            $_POST['dbport'] = $_POST['dbport'] ? $_POST['dbport'] : '3306';
            $dbname = strtolower(trim($_POST['dbname']));
            $dbuser = trim($_POST['dbuser']);
            $dbpw = trim($_POST['dbpw']);
            $dbPrefix = empty($_POST['dbprefix']) ? 'xc_' : trim($_POST['dbprefix']);

            $username = trim($_POST['manager']);
            $password = trim($_POST['manager_pwd']);
            $email = trim($_POST['manager_email']);

            if (!function_exists('mysqli_connect')) {
                $arr['msg'] = "请安装 mysqli 扩展!";
                echo json_encode($arr);
                exit;
            }
            $conn = @mysqli_connect($dbHost, $dbuser, $dbpw, NULL, $_POST['dbport']);
            if (!$conn) {
                $arr['msg'] = "连接数据库失败!" . mysqli_connect_error($conn);
                echo json_encode($arr);
                exit;
            }
            mysqli_set_charset($conn, "utf8"); //,character_set_client=binary,sql_mode='';
            $version = mysqli_get_server_info($conn);
            if ($version < 5.1) {
                $arr['msg'] = '数据库版本太低! 必须5.1以上';
                echo json_encode($arr);
                exit;
            }

            if (!mysqli_select_db($conn, $dbname)) {
                //创建数据时同时设置编码
                if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4;")) {
                    $arr['msg'] = '数据库 ' . $dbname . ' 不存在，也没权限创建新的数据库！';
                    echo json_encode($arr);
                    exit;
                }
                mysqli_select_db($conn, $dbname);
            }

            if ($n == -1) {
                // 下载数据库文件保存到 install 下
                $sql = file_get_contents('http://server.71cms.net/install/data.sql');
                file_put_contents(SITEDIR . 'install/data.sql', $sql);
                $sql = file_get_contents('http://server.71cms.net/install/data_demo.sql');
                file_put_contents(SITEDIR . 'install/data_demo.sql', $sql);
                $arr['n'] = 0;
                $arr['msg'] = "成功创建数据库:{$dbname}<br>";
                echo json_encode($arr);
                exit;
            }

            //读取数据文件
            $sqlFile = $_POST['type'] == 'demo' ? 'data_demo.sql' : 'data.sql';
            $sqldata = file_get_contents(SITEDIR . 'install/' . $sqlFile);
            $sqldata = file_get_contents($sqlFile);
            $sqlFormat = sql_split($sqldata, $dbPrefix);
            //创建写入sql数据库文件到库中 结束

            $counts = count($sqlFormat);
            for ($i = $n; $i < $counts; $i++) {
//                usleep(10000);
                $sql = trim($sqlFormat[$i]);
                if (strstr($sql, 'CREATE TABLE')) {
                    preg_match('/CREATE TABLE `xc_([^ ]*)`/is', $sql, $matches);
//                    mysqli_query($conn, "DROP TABLE IF EXISTS `{$dbPrefix}{$matches[1]}`");
                    $sql = str_replace('`xc_', '`' . $dbPrefix, $sql);//替换表前缀
                    $ret = mysqli_query($conn, $sql);
                    if ($ret) {
                        $message = '<li><span class="span-ok">√</span> 创建数据表[' . $dbPrefix . $matches[1] . ']完成!<span style="float: right;">' . date('Y-m-d H:i:s') . '</span></li> ';
                    } else {
                        @session_start();
                        $_SESSION['success'] = false;
//                        $message = '<li><span class="span-no">×</span> 创建数据表[' . $dbPrefix . $matches[1] . ']失败!'.mysqli_error($conn).'<span style="float: right;">' . date('Y-m-d H:i:s') . '</span></li>';
                        $message = '<li><span class="span-no">×</span> 创建数据表[' . $dbPrefix . $matches[1] . ']失败!<span style="float: right;">' . date('Y-m-d H:i:s') . '</span></li>';
                    }
                    $i++;
                    $arr = array('n' => $i, 'msg' => $message, 'suc' => $ret);
                    echo json_encode($arr);
                    exit;
                } else {
                    if (trim($sql) == '')
                        continue;
                    $sql = str_replace('`xc_', '`' . $dbPrefix, $sql);//替换表前缀
                    $ret = mysqli_query($conn, $sql);
                    $message = '';
                    $arr = array('n' => $i, 'msg' => $message);
//                    echo json_encode($arr); exit;
                }
            }

            //读取配置文件，并替换真实配置数据1
            $envStr = file_get_contents(CMSDIR . '/' . $envFile);
            $envStr = str_replace('#DB_HOST#', $dbHost, $envStr);
            $envStr = str_replace('#DB_NAME#', $dbname, $envStr);
            $envStr = str_replace('#DB_USER#', $dbuser, $envStr);
            $envStr = str_replace('#DB_PWD#', $dbpw, $envStr);
            $envStr = str_replace('#DB_PORT#', $_POST['dbport'], $envStr);
            $envStr = str_replace('#DB_PREFIX#', $dbPrefix, $envStr);
            $envStr = str_replace('#DB_CHARSET#', 'utf8mb4', $envStr);
            @chmod(CMSDIR . '/.env', 0777); //数据库配置文件的地址
            @file_put_contents(CMSDIR . '/.env', $envStr); //数据库配置文件的地址

            $time = time();
            $salt = sp_random_string(4);
            $password = md5(trim($_POST['manager_pwd']) . $salt);//注意加密方式
            $addadminsql = "INSERT INTO `{$dbPrefix}admin_user` (`id`, `username`, `password`, `status`, `create_time`, `salt`, `tenant_id`) VALUES
(1, '" . $username . "', '" . $password . "', '1', '" . $time . "', '" . $salt . "', '1')";
            $res = mysqli_query($conn, $addadminsql);
            @session_start();
            if ($res) {
                $message = '<li><span class="span-ok">√</span> 成功添加管理员<br />成功写入配置文件<br>安装完成．';
            } else {
                $_SESSION['success'] = false;
                $message = '<li><span class="span-no">×</span> 添加管理员失败<br />成功写入配置文件<br>安装完成．';
            }
            $arr = array('n' => 999999, 'msg' => $message, 'install' => $_SESSION['success']);
            echo json_encode($arr);
            exit;
        }
        @session_start();
        $_SESSION['success'] = true;
        include_once("./templates/step4.php");
        exit();
    case '5':
        $ip = get_client_ip();
        $host = $_SERVER['HTTP_HOST'];
        $time = time();
        $mt_rand_str = $current_version . sp_random_string(6);
        $str_constant = "<?php" . PHP_EOL . "define('INSTALL_DATE'," . $time . ");" . PHP_EOL . "define('SERIALNUMBER','" . $mt_rand_str . "');";
        file_put_contents(CMSDIR . '/application/constant.php', $str_constant);
        include_once("./templates/step5.php");
        @touch('./install.lock');
        exit();
}

function testwrite($d)
{
    if (is_file($d)) {
        if (is_writeable($d)) {
            return true;
        }
        return false;

    } else {
        $tfile = "_test.txt";
        $fp = @fopen($d . "/" . $tfile, "w");
        if (!$fp) {
            return false;
        }
        fclose($fp);
        $rs = @unlink($d . "/" . $tfile);
        if ($rs) {
            return true;
        }
        return false;
    }

}


function sql_split($sql)
{
    $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8mb4", $sql);
    $sql = str_replace("\r", "\n", $sql);
    $ret = array();
    $num = 0;
    $queriesarray = explode(";\n", trim($sql));
    unset($sql);
    foreach ($queriesarray as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        $queries = array_filter($queries);
        foreach ($queries as $query) {
            $str1 = substr($query, 0, 1);
            if ($str1 != '#' && $str1 != '-')
                $ret[$num] .= $query;
        }
        $num++;
    }
    return $ret;
}

function _dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

// 获取客户端IP地址
function get_client_ip()
{
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
            unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
    return $ip;
}

function dir_create($path, $mode = 0777)
{
    if (is_dir($path))
        return TRUE;
    $ftp_enable = 0;
    $path = dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir))
            continue;
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

function dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

function sp_password($pw, $pre)
{
    $decor = md5($pre);
    $mi = md5($pw);
    return substr($decor, 0, 12) . $mi . substr($decor, -4, 4);
}

function sp_random_string($len = 8)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

function returnJson($status, $msg = '', $data = [])
{
    header('Content-type: application/json');
    echo json_encode([
        'status' => $status,
        'msg' => $msg,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    die;
}
