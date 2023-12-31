<?php


namespace app\notify\exam;

use app\common\model\Department as DepartmentModel;
use SplSubject;
use think\Db;

class Dingtalk implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $dep = DepartmentModel::where('id', $item['dep_id'])->find();
        $userList = Db::name('dingtalk_user')
            ->where('user_id', 'in', $subject->message->getUserList())
            ->where('tenant_id', TENANT_ID)
            ->column('userid');

        $title = '您有考试需要参加';
        //卡片消息
        $markdown = <<<EOT
# {$title}  \n  
组织方：**{$dep['name']}**  \n  
考试内容：**{$item['name']}**
EOT;
        $url = h5_url('pages/exam/exam', ['id' => $item['exam_id']]);

        $push = new \app\common\model\Push();
        while (!empty($userList)) {
            //接口限制 每次最多发给100个人
            $_sub = array_splice($userList, 0, 100);
            $send = implode(',', $_sub);
            try {
                $res = $push->dingTalkActionCardMsg('exam', $send, $title, $markdown, $url);
            } catch (\exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}