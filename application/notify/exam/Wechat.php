<?php


namespace app\notify\exam;


use app\common\model\Department as DepartmentModel;
use app\common\model\User;
use SplSubject;

class Wechat implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $push = new \app\common\model\Push();
        $users = User::where('id', 'in', $subject->message->getUserList())->field('openid')->select();
        $dep = DepartmentModel::where('id', $item['dep_id'])->find();
        foreach ($users as $k => $user) {
            if ($user['openid']) {
                $push_res = $push->send(8, [
                    'first' => '您有考试需要参加',
                    'keyword1' => $dep['name'],
                    'keyword2' => $item['name'],
                    'keyword3' => '在线考试',
                ], $user['openid'], h5_url('pages/exam/exam', ['id' => $item['exam_id']]));
                var_dump($push_res);
            }
        }
    }
}