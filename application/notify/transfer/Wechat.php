<?php


namespace app\notify\transfer;


use app\common\model\User;
use SplSubject;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $user = User::where('id', 'in', $subject->message->getUserList())->field('openid')->find();
        if ($user['openid']) {
            $res = $push->send(10, [
                'keyword1' => $item['text'],
            ], $user['openid'], h5_url('pages/transfer/transferInfo', ['id' => $item['id']]));
            var_dump($res);
        }
    }
}