<?php


namespace app\notify\meeting;

use SplSubject;
use think\Db;
use think\facade\Log;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $data = $subject->message->getData();
        $users = $subject->message->getUserlist();
        $id = $data['id'];
        $push = new \app\common\model\Push();

        if ($users) {
            $type = $data['meeting_type'] == 2 ? 3 : 5;

            /*$compere = Db::name('user')
                ->where('id', $data['compere'])
                ->value('nickname');*/

            $userList = Db::name('user')
                ->where('id', 'in', $users)
                ->field('openid')
                ->select();
            if (!$userList) return;
            $url = h5_url('pagesMain/shyk/shyk-meeting', ['id' => $id]);

            //准备要发送的消息
            //开会通知
            $msg = [
                'keyword1' => "《{$data['theme']}》",
                'keyword2' => date('Y-m-d H:i', $data['start_time']) . " - \n" . date('Y-m-d H:i', $data['end_time']),
                'keyword3' => $data['place'],
            ];
            if (isset($data['msg_type']) && $data['msg_type'] == 'cancel_meeting') {
                // 取消会议
                $msg['keyword1'] = "取消会议《{$data['theme']}》";
            }

            foreach ($userList as $user) {
                if (!$user['openid']) continue;
                $res = $push->send($type, $msg, $user['openid'], $url);
                Log::write($res);
            }
        }
    }
}
