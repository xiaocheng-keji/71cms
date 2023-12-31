<?php


namespace app\notify\dep_team;

use SplSubject;

class Message implements \SplObserver
{
    public function update(SplSubject $subject)
    {
        $item = $subject->message->getData();
        $users = $subject->message->getUserList();
        foreach ($users as $k => $user) {
            \app\common\model\Message::create([
                'uid' => $user,
                'type' => 16,
                'content' => $item['text'],
                'time' => time(),
                'other_id' => $item['id']
            ]);
        }
    }
}