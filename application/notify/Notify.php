<?php


namespace app\notify;

/**
 * 通知主体
 *
 * Class MeetingNotify
 * @package app\notify
 */
class Notify implements \SplSubject
{
    private $observers;
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->observers = new \SplObjectStorage();
    }

    public function attach(\SplObserver $observer) //加入观察者
    {
        $this->observers->attach($observer);
    }

    public function detach(\SplObserver $observer)
    {
        $this->observers->detach($observer);
    }

    public function notify() //通知所有观察者  也就是执行所有观察者
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
}