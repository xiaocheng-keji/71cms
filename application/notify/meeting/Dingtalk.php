<?php


namespace app\notify\meeting;


use SplSubject;
use think\Db;

class Dingtalk implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $meetingData = $subject->message->getData();
        $templateUserList = $subject->message->getUserlist();
        $id = $meetingData['id'];
        $push = new \app\common\model\Push();
        if ($templateUserList) {
            $compere = Db::name('user')
                ->where('id', $meetingData['compere'])
                ->where('tenant_id', TENANT_ID)
                ->value('nickname');

            //发钉钉消息
            //钉钉用户
            $userList = Db::name('dingtalk_user')
                ->where('user_id', 'in', $templateUserList)
                ->where('tenant_id', TENANT_ID)
                ->column('userid');

            //卡片消息
            $time = date('Y-m-d H:i', $meetingData['start_time']) . ' - ' . date('Y-m-d H:i', $meetingData['end_time']);
            $markdown = <<<EOT
# {$meetingData['theme']}  \n  
时间：**{$time}**  \n  
地点：**{$meetingData['place']}**  \n  
主持人：**{$compere}**
EOT;
            $title = $meetingData['meeting_type'] == 2 ? '会议提醒' : '活动提醒';
            $url = h5_url('pages/meeting/details', ['id' => $id]);
            while (!empty($userList)) {
                //接口限制 每次最多发给100个人
                $_sub = array_splice($userList, 0, 100);
                $send = implode(',', $_sub);
                try {
                    $res = $push->dingTalkActionCardMsg($meetingData['meeting_type'] == 2 ? 'meeting' : 'activity', $send, $title, $markdown, $url);
                } catch (\exception $exception) {
                    var_dump($exception->getMessage());
                }
            }
        }
    }
}