<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>环境检查-<?php echo SYS_NAME; ?></title>
    <link rel="stylesheet" href="layui/css/layui.css" media="all">
    <link rel="stylesheet" href="css/style.css" media="all">
    <style>

    </style>
</head>
<body>
<div class="install-title">
    <img src="http://server.71cms.net/image/logo.png">
    <h1>创先云智慧党建系统安装向导</h1>
</div>
<div class="layui-container">
    <div class="layui-row">
        <?php include "step.php"; ?>
        <div class="layui-col-md8">
            <div class="layui-card">
                <div class="layui-card-body">
                    <table class="layui-table" lay-size="sm">
                        <colgroup>
                            <col width="25%">
                            <col width="25%">
                            <col width="50%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>PHP 设置</th>
                            <th>要求环境设置</th>
                            <th>当前环境设置</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>PHP版本</td>
                            <td>>7.2.0</td>
                            <td><?php echo $phpv; ?></td>
                        </tr>
                        <tr>
                            <td>附件上传</td>
                            <td>>2M</td>
                            <td><?php echo $uploadSize; ?></td>
                        </tr>
                        <tr>
                            <td>GD库</td>
                            <td>开启</td>
                            <td><?php echo $gd; ?></td>
                        </tr>
                        <tr>
                            <td>mysqli</td>
                            <td>开启</td>
                            <td><?php echo $mysql; ?></td>
                        </tr>
                        <tr>
                            <td>session</td>
                            <td>支持</td>
                            <td><?php echo $session; ?></td>
                        </tr>
                        </tbody>
                    </table>

                    <table class="layui-table" lay-size="sm">
                        <colgroup>
                            <col width="25%">
                            <col width="25%">
                            <col width="25%">
                            <col width="25%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>目录、文件权限检查</th>
                            <th>推荐配置</th>
                            <th>写入</th>
                            <th>读取</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($folder as $dir) {
                            $Testdir = CMSDIR . $dir;
                            if (!is_file($Testdir)) {
                                if (!is_dir($Testdir)) {
                                    dir_create($Testdir);
                                }
                            }

                            if (testwrite($Testdir)) {
                                $w = '<span class="span-ok">&radic;</span> ';
                            } else {
                                $w = '<span class="span-no">&radic;</span> ';
                                $err++;
                            }


                            if (is_readable($Testdir)) {
                                $r = '<span class="span-ok">&radic;</span>';
                            } else {
                                $r = '<span class="span-no">&radic;</span>';
                                $err++;
                            }
                            ?>
                            <tr>
                                <td><?php echo $dir; ?></td>
                                <td>读写</td>
                                <td><?php echo $w; ?></td>
                                <td><?php echo $r; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <table class="layui-table" lay-size="sm">
                        <colgroup>
                            <col width="25%">
                            <col width="25%">
                            <col width="50%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>函数检测</th>
                            <th>推荐配置</th>
                            <th>当前状态</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>curl_init</td>
                            <td>必须扩展</td>
                            <td><?php echo $curl; ?></td>
                        </tr>
                        <tr>
                            <td>Openssl</td>
                            <td>必须扩展</td>
                            <td><?php echo $openssl; ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="btn-div">
                <a href="?step=2" class="layui-btn"> 重新检测 </a>
                <a href="?step=3" class="layui-btn layui-btn-warm"> 下一步 </a>
            </div>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>