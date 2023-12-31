<?php


namespace app\notify;

use app\common\model\PushConfig;

/**
 * 发送通知使用的消息
 *
 * Class Message
 * @package app\notify
 */
class Message
{
    private $type;
    private $data;
    private $user_list; //要发给消息的用户的id数组
    private $tenant_id;

    /*消息的类型*/
    const TYPE_ACTIVITY = 'activity';
    const TYPE_MEETING = 'meeting';
    const TYPE_NOTICE = 'notice';
    const TYPE_VOTE = 'vote';
    const TYPE_SUPERVISE = 'supervise';
    const TYPE_SURVEY = 'survey';
    const TYPE_EXAM = 'exam';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_SEC_MAIL = 'sec_mail';
    const TYPE_SEC_MAIL2 = 'sec_mail2';
    const TYPE_DEVELOP = 'develop';
    const TYPE_DEP_TEAM = 'dep_team';
    const TYPE_TASK = 'task';

    public function __construct($type = '', $data = [], $user_list = [], int $tenant_id = 0)
    {
        if (is_array($type)) {
            $this->setData($type);
        } else {
            $this->type = $type;
            $this->data = $data;
            $this->user_list = $user_list;
            $this->tenant_id = 0;
        }
    }

    public function setData($data)
    {
        $this->type = $data['type'];
        $this->data = $data['data'];
        $this->user_list = $data['user_list'];
        $this->tenant_id = $data['tenant_id'] ?? 0;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getPushTypeStatus(string $type)
    {
        $config = PushConfig::getPushConfig($this->type,$type);
        if($config['status'] == 1){
            return true;
        }else{
            return false;
        }
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getUserList(): array
    {
        return $this->user_list;
    }

    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'user_list' => $this->user_list,
            'tenant_id' => $this->tenant_id ?? 0,
        ];
    }
}