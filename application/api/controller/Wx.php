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
namespace app\api\controller;

use EasyWeChat\Factory;
use think\Controller;
use think\Db;
use think\Request;

class Wx extends Controller
{
    /**
     * 微信授权
     */
    public function oath(){
        $wx = Db::name('wxconfig') -> Cache('wxconfig') -> find();
        $config = [
            'app_id' => $wx['app_id'],
            'secret' => $wx['app_secret'],
            'response_type' => 'array',
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/api/wx/redirect_url',
            ]
        ];
        $app = Factory::officialAccount($config);
        $oath = $app -> oauth;
        $oath -> redirect() -> send();
    }

    /**
     *授权的callback配置跳转地址，授权后跳转到该地址
     */
    public function redirect_url(){
        $wx = Db::name('wxconfig') -> Cache('wxconfig') -> find();
        $config = [
            'app_id' => $wx['app_id'],
            'secret' => $wx['app_secret'],
            'response_type' => 'array',
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/api/wx/callback',
            ]
        ];
        $app = Factory::officialAccount($config);
        $oath = $app -> oauth;
        $user = $oath -> user() -> getOriginal();
//        dump($user);die;
        $this -> checkOath($user);
    }

    /**
     * 获取用户信息并添加
     * @param array $userArray
     */
    public function checkOath($userArray=[]){
        if(!$userArray){
            if(empty(input('openid'))){
                $this -> oath();
                die;
            }
            $user = Db::name('users') -> where(['openid'=>input('openid')]) -> find();
            if(empty($user)){
                $this -> oath();
                die;
            }
        }else{
            $user = Db::name('users') -> where(['openid'=>$userArray['openid']]) -> find();
            if($user){
                echo '已存在';
            }else{
                $data = [
                    'nickname' => $userArray['nickname'],
                    'sex' => $userArray['sex'],
                    'reg_time' => time(),
                    'last_login' => time(),
                    'openid' => $userArray['openid'],
                    'head_pic' => $userArray['headimgurl'],
                    'qq' => '',
                    'token' => md5(time())
                ];
                Db::name('users') -> data($data) -> insert();
                echo '添加成功';
            }
        }
    }

    public function push(){
        $wx = Db::name('wxconfig') -> Cache('wxconfig') -> find();
        $config = [
            'app_id' => $wx['app_id'],
            'secret' => $wx['app_secret'],
            'response_type' => 'array',
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/api/wx/redirect_url',
            ]
        ];
        $app = Factory::officialAccount($config);

        $app->template_message->send([
            'touser' => 'otzIjwLIAxIrI35YZeD8cI0676Mk',
            'template_id' => 'AsEcJgUArBLXr3tJA3vJmh-HIpChPncnJdoNw4kiZHs',
            'url' => 'https://www.baidu.com',
            'data' => [
                'keyword1' => 'VALUE',
                'keyword2' => 'VALUE2',
                'keyword3' => 'VALUE2',
            ]
        ]);

//        $items = [
//            new NewsItem([
//                'title'=>'',
//                'description'=>'标题<br>截止时间：2019-05-01 00:00:00',
//                'url'=>'https://www.baidu.com',
//                'image'=> 'http://file.orangelite.cn/image/4222155419457303132941'
//            ])
//        ];
//        $news = new News($items);
//        $app->customer_service->message($news)->to('otzIjwLIAxIrI35YZeD8cI0676Mk')->send();
    }


    public function callback(){

    }


    /**
     * 支付
     * @param string $type
     * @return array|\think\response\View
     */
    public function pay($type='jsapi'){
        if($type!=='app'){
//            $type = 'jsapi';
            if($this->is_weixin()){
                $type = 'jsapi';
            }else{
                $type = 'h5';
            }
        }

        $wx = Db::name('wxconfig') -> Cache('wxconfig') -> find();

        $config = [
            'app_id' => $wx['app_id_app'],
            'mch_id' => $wx['mch_id_app'],
            'key'    => $wx['key_app'],   // API 密钥
        ];
        if($wx['sub_mch_id_app']){
            $config += ['sub_mch_id' => $wx['sub_mch_id_app']];
        }
        $app = Factory::payment($config);

//        $app->setSubMerchant('sub-merchant-id', $wx['sub_mch_id_app']);  // 子商户 AppID 为可选项
        $payment = Db::name('payment') -> where(['id'=>input('id')]) -> find();
        if($payment['end_time']<date('Y-m-d')){
            jsonReturn(0,'时间已截止');
        }

        $out_trade_no = date('YmdHis').rand(100000,999999);
        $unify = [
            'body' => "{$payment['name']}缴纳{$payment['title']}",
//            'attach' => json_encode(['uid'=>$payment['uid'],'name'=>$payment['name']]),
            'out_trade_no' => $out_trade_no,
            'total_fee' => $payment['payment']*100,
//            'notify_url' => $wx['notify_url'],
            'notify_url' => SITE_URL.'/api/wx/notify',
            'spbill_create_ip' => $this->request->ip(),
        ];
        switch ($type){
            case 'h5':
                $unify += ['trade_type'=>'MWEB'];
                break;
            case 'jsapi':
                $unify += ['trade_type'=>'JSAPI','openid'=>session('user.openid')];
                break;
            case 'app':
                $unify += ['trade_type'=>'APP'];
                break;
        }
        $result = $app -> order -> unify($unify);
        if($result['return_code'] === 'FAIL'){
//            dump($wx['sub_mch_id_app']);
//            dump($unify);
//            dump($config);
            jsonReturn(0,$result['return_msg']);
        }
        $jssdk = $app -> jssdk;

        $change_no = Db::name('payment') -> data(['out_trade_no'=>$out_trade_no]) -> where(['id'=>input('id')]) -> update();
        if(!$change_no){
            jsonReturn(0,'该单不存在');
        }
        if($type=='h5'){

            jsonReturn(1,'h5获取返回值',"{$result['mweb_url']}&redirect_url=".urlencode(SITE_URL.'/wap/func/payment_list'));
        }elseif($type=='jsapi'){
            $json = $jssdk -> bridgeConfig($result['prepay_id']);
            $json = json_decode($json);
            jsonReturn(1,'jsapi获取返回值',$json);
        }elseif($type=='app'){
            $json = $jssdk -> appConfig($result['prepay_id']);
            jsonReturn(1,'app获取返回值',$json);
        }
    }


    /**
     * 异步通知
     * @param Request $request
     */
    public function notify(Request $request)
    {
        //获取tenant_id
        $data = file_get_contents('php://input');
        $xml = simplexml_load_string($data,'SimpleXMLElement',LIBXML_NOCDATA);
        define('TENANT_ID',json_decode($xml->attach,true)['tenant_id']);

        //获取配置
        $wx = Db::name('wxconfig')->find();
        $config = [
            'app_id' => $wx['app_id_app'],
            'mch_id' => $wx['mch_id_app'],
            'key' => $wx['key_app'],   // API 密钥
        ];
        if ($wx['is_sub'] == 1) {
            $config += ['sub_mch_id' => $wx['sub_mch_id_app']];
        }

        $app = Factory::payment($config);

        $response = $app->handlePaidNotify(function ($message, $fail) {
            if ($message['return_code'] == 'SUCCESS' && $message['result_code'] == 'SUCCESS') {
                $data = [
                    'is_pay' => 1,
                    'pay_time' => date('Y-m-d H:i:s'),
                    'transaction_id' => $message['transaction_id']
                ];
                Db::name('payment')->data($data)->where(['out_trade_no' => $message['out_trade_no']])->update();
            } else {
                $fail('未支付成功');
            }
            file_put_contents('./data/time_' . time() . '.txt', json_encode($message));
            return true;
        });
        $response->send();
    }


    /**
     * 下载对账单并加入数据库
     */
    public function duizhang($pay_date){
        $is_dz = Db::name('bill_pay_date') -> where(['paydate'=>$pay_date]) -> find();
        if($is_dz){
            dump($pay_date);
            return;
        }
        $wx = Db::name('wxconfig') -> Cache('wxconfig') -> find();
        $config = [
            'app_id' => $wx['app_id'],
            'mch_id' => $wx['mch_id'],
            'key'    => $wx['key']
        ];
        $app = Factory::payment($config);
        $bill = $app->bill->get($pay_date); // type: SUCCESS
        // 调用正确，`$bill` 为 csv 格式的内容，保存为文件：
        $file_name = $bill->saveAs('duizhang', "file-$pay_date.txt");
        $data = file_get_contents('duizhang/'.$file_name);
        $response = str_replace(","," ",$data);
        $response = explode(PHP_EOL, $response);

        $result = [];
        foreach ($response as $key=>$val){
            if(strpos($val, '`') !== false){
                $data = explode('`', $val);
                array_shift($data); // 删除第一个元素并下标从0开始

                $date = date('Y-m-d H:i:s');
                if(count($data) == 27){ // 处理账单数据
                    $result['bill'][] = array(
                        'pay_time'             => $data[0], // 交易时间
                        'app_id'               => $data[1], // 公众账号ID
                        'mch_id'               => $data[2], // 商户号
                        'smn'                  => $data[3],//特约商户号
                        'imei'                 => $data[4], // 设备号
                        'order_sn_wx'          => $data[5], // 微信订单号
                        'order_sn_sh'          => $data[6], // 商户订单号
                        'user_tag'             => $data[7], // 用户标识
                        'pay_type'             => $data[8], // 交易类型
                        'pay_status'           => $data[9], // 交易状态
                        'bank'                 => $data[10], // 付款银行
                        'money_type'           => $data[11], // 货币种类
                        'total_amount'         => $data[12], // 应结订单金额
                        'coupon_amount'        => $data[13], // 代金券金额
                        'refund_number_wx'     => $data[14], // 微信退款单号
                        'refund_number_sh'     => $data[15], // 商户退款单号
                        'refund_amount'        => $data[16], // 退款金额
                        'coupon_refund_amount' => $data[17], // 代金券或立减优惠退款金额
                        'refund_type'          => $data[18], // 退款类型
                        'refund_status'        => $data[19], // 退款状态
                        'goods_name'           => $data[20], // 商品名称
                        'mch_body'             => $data[21], // 商户数据包
                        'service_charge'       => $data[22], // 手续费
                        'rate'                 => $data[23], // 费率
                        'order_money'          => $data[24],//订单金额,
                        'refund_money'         => $data[25],//申请退款金额,
                        'mark'                 => $data[26],//费率备注
                        'time'                 => $date
                    );
                }


                if(count($data) == 7){ // 统计数据
                    $result['summary'] = array(
                        'order_num'       => $data[0],    // 总交易单数
                        'turnover'        => $data[1],    // 应结订单总金额
                        'refund_turnover' => $data[2],    // 退款总金额
                        'coupon_turnover' => $data[3],    // 充值券退款总金额
                        'rate_turnover'   => $data[4],    // 手续费总金额
                        'order_total'     => $data[5],    //订单总金额,
                        'refund_total'    => $data[6],    //申请退款总金额
                        'time'            => $date
                    );
                }
            }
        }
//        dump($result['summary']);die;
        Db::name('bill') -> insertAll($result['bill'],true);
        Db::name('summary') -> insert($result['summary'],true);
        Db::name('bill_pay_date') -> data(['paydate'=>$pay_date]) -> insert();
    }


    function is_weixin() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        } return false;
    }

}
