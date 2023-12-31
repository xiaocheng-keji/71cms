<?php

namespace app\api\validate;

use think\Validate;

class MeetingRec extends Validate
{
    protected $rule = [
        'theme' => 'require|min:2',
        'meeting_id' => 'require',
        'content' => 'require',
    ];

    protected $message = [
        'theme.require' => '请输入主题',
        'theme.min' => '主题名称至少2个字符',
        'meeting_id.require' => 'meeting_id 错误',
        'content.require' => '内容不能为空',
    ];

}