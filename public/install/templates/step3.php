<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>账号设置-<?php echo SYS_NAME; ?></title>
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
            <form class="layui-form" action="?step=4" method="post">
                <div class="layui-card">
                    <div class="layui-card-body">

                        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
                            <legend>数据库信息</legend>
                        </fieldset>

                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库地址</label>
                            <div class="layui-input-inline">
                                <input type="text" name="dbhost" value="localhost" lay-verify="required" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux">数据库服务器地址，一般为localhost</div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">端口</label>
                            <div class="layui-input-inline">
                                <input type="number" name="dbport" value="3306" lay-verify="required" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux">数据库服务器端口，一般为3306</div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">用户名</label>
                            <div class="layui-input-inline">
                                <input type="text" name="dbuser" value="" lay-verify="required" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux"></div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">密码</label>
                            <div class="layui-input-inline">
                                <input type="password" name="dbpw" value="" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux"></div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库</label>
                            <div class="layui-input-inline">
                                <input type="text" name="dbname" value="" lay-verify="required" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux"></div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">表前缀</label>
                            <div class="layui-input-inline">
                                <input type="text" name="dbprefix" value="xc_" required lay-verify="required" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux"></div>
                        </div>

                        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
                            <legend>管理员信息</legend>
                        </fieldset>
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员帐号</label>
                            <div class="layui-input-inline">
                                <input type="text" name="manager" value="admin" lay-verify="required" placeholder="" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux"></div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员密码</label>
                            <div class="layui-input-inline">
                                <input type="password" name="manager_pwd" value="" lay-verify="pass" placeholder=""
                                       autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-form-mid layui-word-aux">请填写6到12位密码</div>
                        </div>
                        <fieldset class="layui-elem-field layui-field-title" style="margin-top: 20px;">
                            <legend>数据选择</legend>
                        </fieldset>
                        <div class="layui-form-item">
                            <label class="layui-form-label">类型</label>
                            <div class="layui-input-block">
                                <input type="radio" name="type" value="default" title="默认数据" checked>
                                <input type="radio" name="type" value="demo" title="演示数据">
                            </div>
                            <span style="font-size: 12px">
                                注：演示数据包含组织架构、党员档案、三会一课等少量数据
                            </span>
                        </div>
                    </div>
                </div>
                <div class="btn-div">
                    <button class="layui-btn layui-btn-warm" lay-submit="" lay-filter="demo2"> 下一步</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
<script src="layui/layui.js" charset="utf-8"></script>
<script>
    layui.use(['form', 'layer', 'jquery'], function () {
        var form = layui.form
            , layer = layui.layer
            , jq = layui.jquery;

        jq('input[name="dbpw"]').blur(function () {
            console.log('blur')
            let dbhost = jq('input[name="dbhost"]').val();
            let dbport = jq('input[name="dbport"]').val();
            let dbuser = jq('input[name="dbuser"]').val();
            let dbpw = jq('input[name="dbpw"]').val();
            let dbname = jq('input[name="dbname"]').val();
            let test = true;
            jq.post('', {
                dbhost,
                dbport,
                dbuser,
                dbpw,
                dbname,
                test
            }, function (res) {
                console.log('post', res)
                if (res.status != 1) {
                    layer.alert(res.msg)
                }
            })
        });
    });
</script>
</body>
</html>