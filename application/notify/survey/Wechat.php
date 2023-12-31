<?php


namespace app\notify\survey;


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
                $res = $push->send(9, [
                    'keyword1' => $item['name'],
                    'keyword2' => $item['start_time'],
                    'keyword3' => $item['end_time'],
                ], $user['openid'], h5_url('pages/survey/details', ['id' => $item['survey_id']]));
                var_dump($res);
            }
        }
    }
}