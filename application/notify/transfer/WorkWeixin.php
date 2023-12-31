<?php


namespace app\notify\transfer;

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

        $title = '组织关系转接通知';
        //卡片消息
        $html = <<<EOT
{$data['text']}
EOT;
        $url = h5_url('pages/transfer/transferInfo', ['id' => $data['id']]);
        
        $agentId = \app\work_weixin\model\WorkWeixin::getAgentId($tenant_id);
        $push = new \app\common\model\Push();
        while (!empty($userList)) {
            //接口限制 每次最多发给1000个人
            $_sub = array_splice($userList, 0, 1000);
            $send = implode('|', $_sub);
            try {
                $res = $push->workWeixinTextCard(Message::TYPE_TRANSFER, $send, $title, $html, $url, $tenant_id, $agentId);
            } catch (\exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}