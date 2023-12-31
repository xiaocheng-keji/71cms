<?php


namespace app\notify\meeting;


use SplSubject;
use think\Db;

class Getui implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $data = $subject->message->getData();
        $users = $subject->message->getUserlist();
        $id = $data['id'];
        $push = new \app\common\model\Push();
        if ($users) {
            $type = $data['meeting_type'] == 2 ? 3 : 5;
            $title = $data['theme'];
            if (isset($data['msg_type']) && $data['msg_type'] == 'cancel_meeting') {
                // 取消会议
                $title = "取消会议《{$data['theme']}》";
            }
            $userList = Db::name('user')
                ->where('id', 'in', $users)
                ->field('client_id')
                ->select();
            foreach ($userList as $user) {
                $push->getui($type, '会议提醒', $title, [
                    'url' => '/pages/meeting/details?id=' . $id,
                    'redirect' => 0
                ], $user['client_id']);
            }
        }
    }
}