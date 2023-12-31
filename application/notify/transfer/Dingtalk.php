<?php


namespace app\notify\transfer;

use SplSubject;
use think\Db;

class Dingtalk implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $userList = Db::name('dingtalk_user')
            ->where('user_id', 'in', $subject->message->getUserList())
            ->where('tenant_id', TENANT_ID)
            ->column('userid');

        $title = '组织关系转接通知';
        //卡片消息
        $markdown = <<<EOT
# {$title}  \n  
**{$item['text']}**  \n  
EOT;
        $url = h5_url('pages/transfer/transferInfo', ['id' => $item['id']]);
        while (!empty($userList)) {
            //接口限制 每次最多发给100个人
            $_sub = array_splice($userList, 0, 100);
            $send = implode(',', $_sub);
            try {
                $res = $push->dingTalkActionCardMsg('transfer', $send, $title, $markdown, $url);
            } catch (\exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}