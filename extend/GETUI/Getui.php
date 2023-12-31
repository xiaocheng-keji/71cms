<?php
error_reporting(0);
header("Content-Type: text/html; charset=utf-8");

require_once(dirname(__FILE__) . '/' . 'IGt.Push.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.AppMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.TagMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.APNPayload.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.BaseTemplate.php');
require_once(dirname(__FILE__) . '/' . 'IGt.Batch.php');
require_once(dirname(__FILE__) . '/' . 'igetui/utils/AppConditions.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/notify/IGt.Notify.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.MultiMedia.php');
require_once(dirname(__FILE__) . '/' . 'payload/VOIPPayload.php');

class Getui
{
    private $APPKEY = '';
    private $APPID = '';
    private $MASTERSECRET = '';
    private $HOST = 'http://sdk.open.api.igexin.com/apiex.htm';
    private $CID = '';
    private $DT = '';
    private $CID1 = '';
    private $DT1 = '';
    private $groupName = '';

    private $PN = '';
    private $Badge = '+1';
    private $TASKID = "OSA-0731_RGyUZj0gYEAC51o1EgbTz8";
    private $ALIAS = "ALIAS";

    public function __construct($APPID, $APPKEY, $MASTERSECRET)
    {
        $this->APPID = $APPID;
        $this->APPKEY = $APPKEY;
        $this->MASTERSECRET = $MASTERSECRET;
    }

    //服务端推送接口，支持三个接口推送
    //1.PushMessageToSingle接口：支持对单个用户进行推送
    //2.PushMessageToList接口：支持对多个用户进行推送，建议为50个用户
    //3.pushMessageToApp接口：对单个应用下的所有用户进行推送，可根据省份，标签，机型过滤推送
    //
    //单推接口案例
    function pushMessageToSingle($template)
    {
        $igt = new IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);

        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板

        //    	$template = $this->IGtNotyPopLoadTemplateDemo();
        //    	$template = $this->IGtLinkTemplate();
        //    	$template = $this->IGtNotificationTemplateDemo();
        //$template = $this->IGtTransmissionTemplateDemo();
        //个推信息体
        $message = new IGtSingleMessage();

        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        //	$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //接收方
        $target = new IGtTarget();
        $target->set_appId($this->APPID);
        $target->set_clientId($this->CID);
        //    $target->set_alias(ALIAS);
        try {
            $rep = $igt->pushMessageToSingle($message, $target);
            //var_dump($rep);
            //echo("<br><br>");

        } catch (RequestException $e) {
            $requstId = $e->getRequestId();
            $rep = $igt->pushMessageToSingle($message, $target, $requstId);
            //var_dump($rep);
            //echo("<br><br>");
        }
        return $rep;

    }

    function SmsDemo()
    {
        $template = new IGtTransmissionTemplate();
        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent("测试离线ddd");//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        $smsMessage = new SmsMessage();
        $smsContent = array();
        $smsContent["code1"] = "1234";
        $smsContent["time"] = "5";
        $smsMessage->setSmsContent($smsContent);
        $smsMessage->setSmsTemplateId("1a0ad952756f4c679ca67f008bf37b5e");
        $smsMessage->setOfflineSendtime(1000);
        $template->setSmsInfo($smsMessage);


        return $template;
    }

    function pushMessageToSingleBatch()
    {
        putenv("gexin_pushSingleBatch_needAsync=false");

        $igt = new IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
        $batch = new IGtBatch($this->APPKEY, $igt);
        $batch->setApiUrl($this->HOST);
        //$igt->connect();
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板

        //$template = IGtNotyPopLoadTemplateDemo();
        $templateLink = $this->IGtLinkTemplate();
        $templateNoti = $this->IGtNotificationTemplateDemo();
        //$template = IGtTransmissionTemplateDemo();

        //个推信息体
        $messageLink = new IGtSingleMessage();
        $messageLink->set_isOffline(true);//是否离线
        $messageLink->set_offlineExpireTime(12 * 1000 * 3600);//离线时间
        $messageLink->set_data($templateLink);//设置推送消息类型
        //$messageLink->set_PushNetWorkType(1);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送

        $targetLink = new IGtTarget();
        $targetLink->set_appId($this->APPID);
        $targetLink->set_clientId($this->CID);
        $batch->add($messageLink, $targetLink);

        //个推信息体
        $messageNoti = new IGtSingleMessage();
        $messageNoti->set_isOffline(true);//是否离线
        $messageNoti->set_offlineExpireTime(12 * 1000 * 3600);//离线时间
        $messageNoti->set_data($templateNoti);//设置推送消息类型
        //$messageNoti->set_PushNetWorkType(1);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送

        $targetNoti = new IGtTarget();
        $targetNoti->set_appId($this->APPID);
        $targetNoti->set_clientId($this->CID2);
        $batch->add($messageNoti, $targetNoti);
        try {
            $rep = $batch->submit();
            //var_dump($rep);
            //echo("<br><br>");
        } catch (Exception $e) {
            $rep = $batch->retry();
            //var_dump($rep);
            //echo("<br><br>");
        }
    }

    //多推接口案例
    function pushMessageToList()
    {
        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");

        $igt = new IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板


        //$template = IGtNotyPopLoadTemplateDemo();
        //$template = IGtLinkTemplate();
        //$template = IGtNotificationTemplateDemo();
        $template = $this->IGtTransmissionTemplateDemo();
        //个推信息体
        $message = new IGtListMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        //    $message->set_PushNetWorkType(1);	//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //    $contentId = $igt->getContentId($message);
        $contentId = $igt->getContentId($message, "toList任务别名功能");    //根据TaskId设置组名，支持下划线，中文，英文，数字

        //接收方1
        $target1 = new IGtTarget();
        $target1->set_appId($this->APPID);
        $target1->set_clientId($this->CID);
        //    $target1->set_alias(Alias);

        $targetList[] = $target1;

        $rep = $igt->pushMessageToList($contentId, $targetList);

        //var_dump($rep);

        //echo("<br><br>");

    }

    //群推接口案例
    function pushMessageToApp($template)
    {
        $igt = new IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板

        //个推信息体
        //基于应用消息体
        $message = new IGtAppMessage();
        $message->set_isOffline(true);
        $message->set_offlineExpireTime(10 * 60 * 1000);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_data($template);
        //    $message->setPushTime("201808011537");
        $appIdList = array($this->APPID);
        /*$phoneTypeList = array('ANDROID');
        $provinceList = array('浙江');
        $tagList = array('中文');
        $age = array("0000", "0010");


        $cdt = new AppConditions();
        $cdt->addCondition(AppConditions::PHONE_TYPE, $phoneTypeList);
        $cdt->addCondition(AppConditions::REGION, $provinceList);
        $cdt->addCondition(AppConditions::TAG, $tagList);
        $cdt->addCondition("age", $age);*/

        $message->set_appIdList($appIdList);
        //$message->set_conditions($cdt);

        $rep = $igt->pushMessageToApp($message);
        return $rep;
        //var_dump($rep);
        //echo("<br><br>");
    }

    //所有推送接口均支持四个消息模板，依次为通知弹框下载模板，通知链接模板，通知透传模板，透传模板
    //注：IOS离线推送需通过APN进行转发，需填写pushInfo字段，目前仅不支持通知弹框下载功能

    function IGtNotyPopLoadTemplate()
    {
        $template = new IGtNotyPopLoadTemplate();

        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        //通知栏
        $template->set_notyTitle("个推");//通知栏标题
        $template->set_notyContent("个推最新版点击下载");//通知栏内容
        $template->set_notyIcon("");//通知栏logo
        $template->set_isBelled(true);//是否响铃
        $template->set_isVibrationed(true);//是否震动
        $template->set_isCleared(true);//通知栏是否可清除
        //弹框
        $template->set_popTitle("弹框标题");//弹框标题
        $template->set_popContent("弹框内容");//弹框内容
        $template->set_popImage("");//弹框图片
        $template->set_popButton1("下载");//左键
        $template->set_popButton2("取消");//右键
        //下载
        $template->set_loadIcon("");//弹框图片
        $template->set_loadTitle("地震速报下载");
        $template->set_loadUrl("http://dizhensubao.igexin.com/dl/com.ceic.apk");
        $template->set_isAutoInstall(false);
        $template->set_isActived(true);
        //$template->set_notifyStyle(0);
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        return $template;
    }

    function IGtLinkTemplate($title, $text, $logo = '', $url = '')
    {
        $template = new IGtLinkTemplate();
        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        $template->set_title($title);//通知栏标题
        $template->set_text($text);//通知栏内容
        $template->set_logo($logo);//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        $template->set_url($url);//打开连接地址
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    function IGtNotificationTemplate($title, $text,$payload=[], $logo = 'http://wwww.igetui.com/logo.png')
    {
        $template = new IGtNotificationTemplate();
        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent(json_encode($payload));//透传内容
        $template->set_title($title);//通知栏标题
        $template->set_text($text);//通知栏内容
        $template->set_logo($logo);//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(false);//通知栏是否可清除
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    function IGtTransmissionTemplate($title, $content, $payload = array())
    {
        $template = new IGtTransmissionTemplate();
        $template->set_appId($this->APPID);//应用appid
        $template->set_appkey($this->APPKEY);//应用appkey
        $template->set_transmissionType(2);//透传消息类型
        //$template->set_transmissionContent("测试离线ddd");//透传内容
        // $payload = [
        //     'msg_type' => $msg_type
        // ];
        if($title&&$title){
            $str = json_encode([
                'title' => $title,
                'content' => $content,
                'payload' => $payload
            ]);
        }else{
            $str = json_encode($payload);
        }

        //'{title:"'.$title.'",content:"'.$content.'",payload:"payload"}'
        $template->set_transmissionContent($str);//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        //APN简单推送
        /*$apn = new IGtAPNPayload();
        $alertmsg = new SimpleAlertMsg();
        $alertmsg->alertMsg = $title;
        $apn->alertMsg = $alertmsg;
        $apn->badge = 0;
        $apn->sound = "";
        $apn->add_customMsg("payload", "payload");
        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
        $template->set_apnInfo($apn);*/

        //VOIP推送
//    $voip = new VOIPPayload();
//    $voip->setVoIPPayload("新浪");
//    $template->set_apnInfo($voip);


        //第三方厂商推送透传消息带通知处理
//    $notify = new IGtNotify();
////    $notify -> set_payload("透传测试内容");
//    $notify -> set_title("透传通知标题");
//    $notify -> set_content("透传通知内容");
//    $notify->set_url("https://www.baidu.com");
//    $notify->set_type(NotifyInfo_Type::_url);
//    $template -> set3rdNotifyInfo($notify);

        //APN高级推送
        $apn = new IGtAPNPayload();
        $alertmsg = new DictionaryAlertMsg();
        $alertmsg->body = $content;
        $alertmsg->actionLocKey = "ActionLockey";
        $alertmsg->locKey = "LocKey";
        $alertmsg->locArgs = array("locargs");
        $alertmsg->launchImage = "launchimage";
//        IOS8.2 支持
        $alertmsg->title = $title;
        $alertmsg->titleLocKey = "TitleLocKey";
        $alertmsg->titleLocArgs = array("TitleLocArg");

        $apn->alertMsg = $alertmsg;
        $apn->badge = 0;
        $apn->sound = "";
        $apn->add_customMsg("payload", json_encode($payload));
        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
//
////    IOS多媒体消息处理
        /*$media = new IGtMultiMedia();
        $media->set_url("http://docs.getui.com/start/img/pushapp_android.png");
        $media->set_onlywifi(false);
        $media->set_type(MediaType::pic);
        $medias = array();
        $medias[] = $media;
        $apn->set_multiMedias($medias);*/
        $template->set_apnInfo($apn);
        return $template;
    }

    function IGtTransmissionTemplateDemo(){
        $template =  new IGtTransmissionTemplate();
        //应用appid
        $template->set_appId($this->APPID);
        //应用appkey
        $template->set_appkey($this->APPKEY);
        //透传消息类型
        $template->set_transmissionType(2);
        //透传内容
        //$template->set_transmissionContent('{title:"通知标题",content:"通知内容",payload:"通知去干嘛这里可以自定义"}');
        $template->set_transmissionContent('内容呵呵');
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        //这是老方法，新方法参见iOS模板说明(PHP)*/
        //$template->set_pushInfo("actionLocKey","badge","message",
        //"sound","payload","locKey","locArgs","launchImage");
        return $template;
    }

//多标签推送接口案例
    function pushMessageByTag()
    {
        $igt = new IGeTui($this->HOST, $this->APPKEY, $this->MASTERSECRET);
        $template = $this->IGtLinkTemplate();
        //个推信息体
        //基于应用消息体
        $message = new IGtTagMessage();
        $message->set_isOffline(true);
        $message->set_offlineExpireTime(10 * 60 * 1000);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_data($template);

        $appIdList = array($this->APPID);

        $message->set_tag("中文");
        $message->set_appIdList($appIdList);

        $rep = $igt->pushTagMessage($message);

        //var_dump($rep);
        //echo("<br><br>");
    }

    function IGtTransmissionTemplateFunction()
    {
        $template = new IGtTransmissionTemplate();
        $template->set_appId('qmLGUim5KR76RY5us9og16');//应用appid
        $template->set_appkey('3qxvCArI7iAGFf4ZEyzqu8');//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent('12345677');//透传内容

        return $template;
    }

    public function setCid($cid)
    {
        $this->CID = $cid;
    }
}