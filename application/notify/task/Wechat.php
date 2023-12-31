<?php


namespace app\notify\task;

use app\common\model\User;
use SplSubject;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $users = User::where('id', 'in', $subject->message->getUserList())->field('openid')->select();
        dump($users);
        if ($item['msg_type'] == 'start_msg') {
            $text = '收到一条任务，任务名称“' . $item['title'] . '”，任务类型：' . $item['cycle_text'] . '任务，请登陆pc管理端-任务管理查看并操作';
        } elseif ($item['msg_type'] == 'end_msg') {
            $text = '您的任务名称“' . $item['title'] . '”，任务类型：' . $item['cycle_text'] . '任务，今天是截止时间哦，请登陆pc管理端-任务管理查看并操作';
        }
        dump($text);
        foreach ($users as $k => $user) {
            if ($user['openid']) {
                $ress = $push->send(12, [
                    'keyword1' => $text,
                ], $user['openid']);
                var_dump($ress);
            }
        }
    }
}