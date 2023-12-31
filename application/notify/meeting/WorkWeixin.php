<?php


namespace app\notify\meeting;

use app\notify\Message;
use SplSubject;
use think\Db;

class WorkWeixin implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $tenant_id = $subject->message->getTenantId();
        $data = $subject->message->getData();
        $users = $subject->message->getUserlist();
        $push = new \app\common\model\Push();
        if ($users) {
            $compere = Db::name('user')
                ->where('id', $data['compere'])
                ->where('tenant_id', $tenant_id)
                ->value('nickname');

            $userList = Db::name('work_weixin_user')
                ->alias('a')
                ->leftJoin('work_weixin b', 'a.corp_id=b.corp_id')
                ->where('b.tenant_id', $tenant_id)
                ->where('b.status', 1)
                ->where('a.user_id', 'in', $users)
                ->where('a.tenant_id', $tenant_id)
                ->column('a.userid');

            //卡片消息
            $time = date('Y-m-d H:i', $data['start_time']) . ' - ' . date('Y-m-d H:i', $data['end_time']);
            $html = <<<EOT
{$data['theme']}  
时间：{$time}  
地点：{$data['place']}  
主持人：{$compere}
EOT;

            if ($data['meeting_type'] == 2) {
                $title = '会议提醒';
                $type = Message::TYPE_MEETING;
            } else {
                $title = '活动提醒';
                $type = Message::TYPE_ACTIVITY;
            }
            if (isset($data['msg_type']) && $data['msg_type'] == 'cancel_meeting') {
                $title = '取消' . $title;
            }
            $url = h5_url('pages/meeting/details', ['id' => $data['id']]);

            $agentId = \app\work_weixin\model\WorkWeixin::getAgentId($tenant_id);
            while (!empty($userList)) {
                //接口限制 每次最多发给1000个人
                $_sub = array_splice($userList, 0, 1000);
                $send = implode('|', $_sub);
                try {
                    $res = $push->workWeixinTextCard($type, $send, $title, $html, $url, $tenant_id, $agentId);
                } catch (\exception $exception) {
                    var_dump($exception->getMessage());
                }
            }
        }
    }
}