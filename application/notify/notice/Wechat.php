<?php


namespace app\notify\notice;


use app\common\model\User;
use SplSubject;
use think\Db;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
            $push = new \app\common\model\Push();
            $users = User::where('id', 'in', $subject->message->getUserList())->field('openid')->select();
            foreach ($users as $k => $user) {
                if ($user['openid']) {
                    $aa = $push->send(7, [
                        'keyword1' => $item['title'],//标题
                        'keyword2' => $item['time'],//时间
                    ], $user['openid'], h5_url('pages/notice/details', ['id' => $item['id']]));
                    var_dump($aa);
                }
            }
    }
}