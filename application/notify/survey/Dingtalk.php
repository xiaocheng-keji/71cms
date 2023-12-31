<?php


namespace app\notify\survey;

use SplSubject;
use think\Db;

class Dingtalk implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $tenant_id = $subject->message->getTenantId();
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $userList = Db::name('dingtalk_user')
            ->where('user_id', 'in', $subject->message->getUserList())
            ->where('tenant_id', $tenant_id)
            ->column('userid');

        $title = '问卷调查通知';
        $start_time = $item['start_time'];
        $end_time = $item['end_time'];
        //卡片消息
        $markdown = <<<EOT
# {$title}  \n  
问卷主题：**{$item['name']}**  \n  
发起时间：**{$start_time}**  \n  
结束时间：**{$end_time}**
EOT;
        $url = h5_url('pages/survey/details', ['id' => $item['survey_id']]);
        while (!empty($userList)) {
            //接口限制 每次最多发给100个人
            $_sub = array_splice($userList, 0, 100);
            $send = implode(',', $_sub);
            try {
                $res = $push->dingTalkActionCardMsg('survey', $send, $title, $markdown, $url);
            } catch (\exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}