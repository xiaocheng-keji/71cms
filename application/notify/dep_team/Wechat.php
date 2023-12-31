<?php


namespace app\notify\dep_team;

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
        dump($item['text']);
        foreach ($users as $k => $user) {
            if ($user['openid']) {
                $ress = $push->send(16, [
                    'first' => '换届提醒',
                    'keyword1' => $item['text'],
                ], $user['openid']);
                var_dump($ress);
            }
        }
    }
}