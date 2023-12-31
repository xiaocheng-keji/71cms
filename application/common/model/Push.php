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
namespace app\common\model;

use app\admin\model\Weixin;
use think\Model;

class Push extends Model
{
    public $title;
    public $subtitle;
    public $time;
    public $address;
    public $touser;
    public $content;
    public $url;
    public $msg_type;

    /**
     * @param int $code 类型:1系统消息；2帖子动态；3会议通知；4用户发送5；活动；6督办；7通知；8考试通知；9问卷通知；10组织关系转接；11书记信箱；12投票；13书记信箱；14党员发展提醒
     * @param array $content 微信模板推送发送的内容
     * @param string $touser 微信模板推送发送对象的openid
     * @param string $template_id 微信消息模板ID
     * @param string $url 微信模板消息跳转url
     */
    public function send($code = '', $content = [], $touser = '', $url = '')
    {
        try {
            switch ($code) {
                case 3:
                    $config = PushConfig::getPushConfig('meeting','wechat');
                    break;
                case 5:
                    $config = PushConfig::getPushConfig('activity','wechat');
                    break;
                case 6:
                    $config = PushConfig::getPushConfig('supervise','wechat');
                    break;
                case 7:
                    $config = PushConfig::getPushConfig('notice','wechat');
                    break;
                case 8:
                    $config = PushConfig::getPushConfig('exam','wechat');
                    break;
                case 9:
                    $config = PushConfig::getPushConfig('survey','wechat');
                    break;
                case 10:
                    $config = PushConfig::getPushConfig('transfer','wechat');
                    break;
                case 11:
                    $config = PushConfig::getPushConfig('sec_mail','wechat');
                    break;
                case 12:
                    $config = PushConfig::getPushConfig('vote','wechat');
                    break;
                case 13:
                    $config = PushConfig::getPushConfig('sec_mail','wechat');
                    break;
                case 14:
                    $config = PushConfig::getPushConfig('develop','wechat');
                    break;
                case 15:
                    $config = PushConfig::getPushConfig('task','wechat');
                    break;
                case 16:
                    $config = PushConfig::getPushConfig('dep_team','wechat');
                    break;
                default :
                    return ['errmsg' => '无该类型'];
                    break;
            }
            if (isset($config['value'])) {
                $template_id = $config['value'];
            }
            if (!$template_id) {
                return ['errmsg' => '未启用'];
            }
            $app = Weixin::app();
            return $app->template_message->send([
                'touser' => $touser,
                'template_id' => $template_id,
                'url' => $url,
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            return ['errmsg' => $e->getMessage()];
        }
    }
}
