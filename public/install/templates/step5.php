<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>安装成功-<?php echo SYS_NAME; ?></title>
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
            <?php include "header.php"; ?>
            <div class="layui-card">
                <div class="layui-card-body" style="height: 300px;text-align: center">
                    <br>
                    <br>
                    <br>
                    <h1>安装成功</h1>
                    <br>
                    <p>现在您可以</p>
                    <br>
                    <a href="/admin/login/index.html" target="_blank" class="layui-btn layui-btn-sm">登录后台</a>
                    <a href="https://www.71cms.net/" target="_blank" class="layui-btn layui-btn-sm">查看帮助手册</a>
                    <br>
                    <br>
                    <p style="color: #999"><?php echo $current_version; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
</body>
</html>