<?php


namespace app\notify\sec_mail2;


use app\common\model\DicData;
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
            $res = $push->send(13, [
                'first' => $item['reply_content'],
//                'keyword1' => $item['reply_user']['nickname'],
//                'keyword2' => $item['reply_user']['mobile'],
                'keyword3' => date('Y-m-d H:i', time()),
                'keyword4' => '已受理',
                'remark' => '如有疑问请联系我们，感谢您的支持',
            ], $user['openid'], h5_url('pages/mail/details', ['id' => $item['id']]));
            var_dump($res);
        }
    }
}