<?php
/**
 * 71CMS [ 创先云智慧党建系统 ]
 * =========================================================
 * Copyright (c) 2018-2023 南宁小橙科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.71cms.net
 * 这不是一个自由软件！未经许可不能去掉71CMS相关版权。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace app\job;

use app\notify\Notify;
use think\facade\Log;
use think\queue\Job;
use think\Exception;

/**
 * 消息消费类
 *
 * Class Message
 * @package app\job
 */
class Message
{
    const QUEUE_NAME = 'message_queue';

    public function fire(Job $job, $data)
    {
        Log::write("开始消费消息队列" . date('Y-m-d H:i:s'), self::QUEUE_NAME);
        //....这里执行具体的任务
        if ($job->attempts() > 3) {
            //通过这个方法可以检查这个任务已经重试了几次了
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            Log::log("消息队列消费失败超过3次，任务将被删除", self::QUEUE_NAME);
            Log::write($data);
            $job->delete();
        } else {
            try {
                //这里发消息 获取数据
                $this->dispatch($data);
            } catch (Exception $e) {
                Log::write("消息队列消费失败" . date('Y-m-d H:i:s'), self::QUEUE_NAME);
                Log::write($e->getMessage());
                return;
            }
            Log::write("消息队列消费成功". date('Y-m-d H:i:s'), self::QUEUE_NAME);
            $job->delete();
        }
    }

    /**
     * 调度
     * @param $data
     */
    public function dispatch($data)
    {
        $message = new \app\notify\Message($data);
        $even = new Notify($message);

        //根据不同的消息类型添加观察者
        $type = $message->getType();

        $className = "\app\\notify\\" . $type . "\Wechat";
        $even->attach(new $className());

        /*$className = "\app\\notify\\" . $type . "\WorkWeixin";
        $even->attach(new $className());*/

        /*$className = "\app\\notify\\" . $type . "\Getui";
        $even->attach(new $className());*/

        /*$className = "\app\\notify\\" . $type . "\Dingtalk";
        $even->attach(new $className());*/

        $even->notify();
    }
}