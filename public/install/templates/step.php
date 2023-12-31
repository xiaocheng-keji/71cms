<div class="layui-col-md3 step-div">
    <ul class="layui-timeline">
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis <?php if($step>=1){ ?>active<?php } ?>"><?php if($step>1){ ?>&#xe605;<?php }else{ ?>&#xe63f;<?php } ?></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?php if($step==1){ ?><b>许可协议</b><?php }else{ ?>许可协议<?php } ?></h3>
                <p>请阅读许可协议 </p>
            </div>
        </li>
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis  <?php if($step>=2){ ?>active<?php } ?>""><?php if($step>2){ ?>&#xe605;<?php }else{ ?>&#xe63f;<?php } ?></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?php if($step==2){ ?><b>环境检查</b><?php }else{ ?>环境检查<?php } ?></h3>
                <p>请检查您的服务器配置是否符合要求</p>
            </div>
        </li>
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis  <?php if($step>=3){ ?>active<?php } ?>""><?php if($step>3){ ?>&#xe605;<?php }else{ ?>&#xe63f;<?php } ?></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?php if($step==3){ ?><b>账号设置</b><?php }else{ ?>账号设置<?php } ?></h3>
                <p>输入数据库和管理员的详细信息</p>
            </div>
        </li>
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis  <?php if($step>=4){ ?>active<?php } ?>""><?php if($step>4){ ?>&#xe605;<?php }else{ ?>&#xe63f;<?php } ?></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?php if($step==4){ ?><b>执行安装</b><?php }else{ ?>执行安装<?php } ?></h3>
                <p>请等待程序执行完成</p>
            </div>
        </li>
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis <?php if($step>=5){ ?>active<?php } ?>""><?php if($step>5){ ?>&#xe605;<?php }else{ ?>&#xe63f;<?php } ?></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?php if($step==5){ ?><b>安装完成</b><?php }else{ ?>安装完成<?php } ?></h3>
                <p>查看帮助文档、进入后台</p>
            </div>
        </li>
    </ul>
</div>