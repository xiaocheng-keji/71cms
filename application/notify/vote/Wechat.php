<?php


namespace app\notify\vote;


use app\common\model\User;
use SplSubject;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $users = User::where('id', 'in', $subject->message->getUserList())->field('openid')->select();
        foreach ($users as $k => $user) {
            if ($user['openid']) {
                $ress = $push->send(12, [
                    'keyword1' => $item['name'],
                    'keyword2' => date('Y-m-d H:i:s', $item['start_time']),
                    'keyword3' => date('Y-m-d H:i:s', $item['end_time']),
                ], $user['openid'], h5_url('pages/vote/details', ['id' => $item['vote_id']]));
                var_dump($ress);
            }
        }
    }
}