<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>安装中-<?php echo SYS_NAME; ?></title>
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
                <div class="layui-card-header">安装中</div>
                <div id="loginner" class="layui-card-body" style="height: 400px;overflow-y: scroll;">
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
<script src="layui/layui.js" charset="utf-8"></script>
<script>
    layui.use(['layer', 'jquery'], function () {
        var layer = layui.layer
            , $ = layui.jquery;

        var n = -1;
        var data = <?php echo json_encode($_POST);?>;
        $.ajaxSetup({cache: false});

        function reloads(n) {
            var url = "<?php echo $_SERVER['PHP_SELF']; ?>?step=4&install=1&n=" + n;
            $.ajax({
                type: "POST",
                url: url,
                data: data,
                dataType: 'json',
                beforeSend: function () {
                },
                success: function (msg) {
                    if (msg.n >= 0) {
                        $('#loginner').append(msg.msg);
                        var ele = document.getElementById('loginner');
                        ele.scrollTop = ele.scrollHeight;
                        if (msg.n == '999999') {
                            $('#dosubmit').attr("disabled", false);
                            $('#dosubmit').removeAttr("disabled");
                            $('#dosubmit').removeClass("nonext");
                            if (msg.install == true) {
                                setTimeout(function () {
                                    window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?step=5';
                                }, 1000);
                            } else {
                                //询问框
                                layer.confirm('安装错误，请清空数据库后检查配置重新安装', {
                                    btn: ['确定'] //按钮
                                }, function(){
                                    history.back();
                                });
                            }
                            return false;
                        } else {
                            reloads(msg.n);
                        }
                    } else {
                        alert(msg.msg);
                    }
                }
            });
        }

        $(document).ready(function () {
            reloads(n);
        })
    });
</script>
</body>
</html>