<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 删除demo数据
 */
class ClearData extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('ClearData');
    }

    protected function execute(Input $input, Output $output)
    {
        // 调试模式才能执行
        if (!env('app_debug')) {
            $output->writeln('请在调试模式下执行');
            return;
        }

        // 删除操作日志
        Db::name('log')->delete(true);
        // 删除文章数据
        Db::name('article')->delete(true);
        // 删除用户表数据
        Db::name('user')->delete(true);
        // 删除用户和组织关联数据
        Db::name('user_dep')->delete(true);
        // 删除组织表数据
        Db::name('department')->delete(true);
        // 删除党党员发展表数据
        Db::name('develop_user')->delete(true);
        Db::name('develop_user_tasklist')->delete(true);
        // 删除会议数据
        Db::name('meeting')->delete(true);
        Db::name('meeting_user')->delete(true);
        Db::name('meeting_read')->delete(true);
        // 删除学习数据
        Db::name('video')->delete(true);
        Db::name('course_complete')->delete(true);
        // 删除组织管理员
        Db::name('auth_group_access_dep')->delete(true);
        // 删除论坛数据
        Db::name('forum_post')->delete(true);
        Db::name('forum_comment')->delete(true);
        // 删除消息数据
        Db::name('message')->delete(true);

    	// 指令输出
    	$output->writeln('清除成功');
    }
}
