<?php


namespace app\notify\survey;

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
        $userList = Db::name('work_weixin_user')
            ->alias('a')
            ->leftJoin('work_weixin b', 'a.corp_id=b.corp_id')
            ->where('b.tenant_id', $tenant_id)
            ->where('b.status', 1)
            ->where('a.user_id', 'in', $users)
            ->where('a.tenant_id', $tenant_id)
            ->column('a.userid');

        $title = '问卷调查通知';
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        //卡片消息
        $html = <<<EOT
问卷主题：{$data['name']}
发起时间：{$start_time}
结束时间：{$end_time}
EOT;
        $url = h5_url('pages/survey/details', ['id' => $data['survey_id']]);
        
        $agentId = \app\work_weixin\model\WorkWeixin::getAgentId($tenant_id);
        $push = new \app\common\model\Push();
        while (!empty($userList)) {
            //接口限制 每次最多发给1000个人
            $_sub = array_splice($userList, 0, 1000);
            $send = implode('|', $_sub);
            try {
                $res = $push->workWeixinTextCard(Message::TYPE_SURVEY, $send, $title, $html, $url, $tenant_id, $agentId);
            } catch (\exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}