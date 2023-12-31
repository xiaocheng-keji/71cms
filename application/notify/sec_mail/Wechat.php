<?php


namespace app\notify\sec_mail;


use app\common\model\DicData;
use app\common\model\SecMailBox;
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
                $res = $push->send(11, [
                    'first' => $item['content'],
                    'keyword1' => $item['title'],
                    'keyword2' => SecMailBox::TYPE_TEXT[$item['type']],
                    'keyword3' => $item['nickname'],
                    'keyword4' => date('Y-m-d H:i', $item['create_time']),
                ], $user['openid'], h5_url('pages/mail/details', ['id' => $item['id']]));
            }
            var_dump($res);
        }
    }
}