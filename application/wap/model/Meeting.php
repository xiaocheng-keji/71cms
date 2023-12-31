<?php

namespace app\wap\model;

use app\common\model\MeetingUser;
use Endroid\QrCode\QrCode;
use Firebase\JWT\JWT;
use think\Db;
use think\Model;

class Meeting extends Model
{
    private static $errorMsg;

    protected const TOKEN_KEY = 'group1';

    const DEFAULT_ERROR_MSG = '操作失败,请稍候再试!';

    //一对一关联方法
    public function meetingUser()
    {
        //return $this->belongsTo('User','modelid'); //第二个参数是外键字段名
        return $this->hasMany('MeetingUser', 'meeting_id', 'id');
    }

//    public function getStartTimeAttr($value)
//    {
//        return self::formatTime($value);
//    }
//
//    public function getEndTimeAttr($value)
//    {
//        return self::formatTime($value);
//    }

    private function formatTime($value)
    {
        return str_replace(['am', 'pm'], ['上午', '下午'], date('Y年n月j日 a g:i', $value));
    }

    public function recordUser()
    {
        return $this->hasOne('User', 'id', 'recorder');
    }

    public function record()
    {
        return $this->hasOne('MeetingRec', 'meeting_id', 'id');
    }

    /**
     * 主持人
     * @return \think\model\relation\HasOne
     */
    public function compereUser()
    {
        return $this->hasOne('User', 'id', 'compere');
    }

    /**
     * 签到负责人
     * @return \think\model\relation\HasOne
     */
    public function signDirector()
    {
        return $this->hasOne('User', 'id', 'sign_responsibility');
    }

    public function meetingImg()
    {
        return $this->hasOne('MeetingFile', 'meeting_id', 'id');
    }

    /**
     * 设置错误信息
     * @param string $errorMsg
     * @return bool
     */
    protected static function setErrorInfo($errorMsg = self::DEFAULT_ERROR_MSG)
    {
        self::$errorMsg = $errorMsg;
        return false;
    }

    /**
     * 获取错误信息
     * @param string $defaultMsg
     * @return string
     */
    public static function getErrorInfo($defaultMsg = self::DEFAULT_ERROR_MSG)
    {
        return !empty(self::$errorMsg) ? self::$errorMsg : $defaultMsg;
    }

    /**
     * 签到链接
     * @param $id
     * @param string $user_id
     * @return string
     */
    public static function signUrl($id, $user_id = '')
    {
        $code = self::dataToToken($id, $user_id);
        return config('app.app_host') . url('/wap/meeting/signPage', ['code' => $code], '');
    }

    /**
     * 包含签到链接的二维码
     * @param $id
     * @param $user_id
     * @return QrCode
     */
    public static function signQrCode($id, $user_id = '')
    {
        $url = self::signUrl($id, $user_id);
        $qrCode = new QrCode($url);
        $qrCode->setMargin(0);
        $qrCode->setSize(300);
        return $qrCode;
    }

    /**
     * 包含签到H5链接的二维码
     * @param $id
     * @param $user_id
     * @return QrCode
     */
    public static function h5SignQrCode($id, $user_id = '')
    {
        $code = self::dataToToken($id, $user_id);
        $url = h5_url('pages/shyk/sign-page', ['code' => $code]);
        $qrCode = new QrCode($url);
        $qrCode->setMargin(0);
        $qrCode->setSize(300);
        return $qrCode;
    }

    /**
     * 数据加密为Token
     * @param $id
     * @param $user_id
     * @return string
     */
    public static function dataToToken($id, $user_id = '')
    {
        $key = self::TOKEN_KEY; //key
        $time = time(); //当前时间
        $token = [
//            'iss' => '', //签发者 可选
//            'aud' => '', //接收该JWT的一方，可选
            'iat' => $time, //签发时间
//            'nbf' => $time , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
//            'exp' => $time+7200, //过期时间,这里设置2个小时
            'data' => [ //自定义信息，不要定义敏感信息
                'id' => (int)$id,
                'user_id' => $user_id,
            ]
        ];
        return JWT::encode($token, $key); //输出Token
    }

    /**
     * Token解密回数据
     * @param $token
     * @return bool
     */
    public static function tokenToData($token)
    {
        $key = self::TOKEN_KEY; //key要和签发的时候一样
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            return $decoded->data;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            return self::setErrorInfo($e->getMessage());
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            return self::setErrorInfo($e->getMessage());
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            return self::setErrorInfo($e->getMessage());
        } catch (\Exception $e) {  //其他错误
            return self::setErrorInfo($e->getMessage());
        }
    }

    //使用token签到
    public static function tokenSign($token, $user_id)
    {
        $data = self::tokenToData($token);
        if ($data == false) {
            return self::setErrorInfo(self::getErrorInfo());
        }
        try {
            $meeting = Db::name('meeting')->where('id', $data->id)->find();
        } catch (\Exception $exception) {
            return self::setErrorInfo($exception->getMessage());
        }
        if ($meeting['tenant_id'] != TENANT_ID) {
//            return self::setErrorInfo('不在参会名单中等');
        }
        if ($meeting['sign_status'] != 1) {
            return self::setErrorInfo('未开启签到');
        }
        if (time() < $meeting['start_sign_time']) {
            return self::setErrorInfo('未到签到时间');
        }
        /*if (time() > $meeting['end_sign_time']) {
            return self::setErrorInfo('签到时间已过');
        }*/
        if (time() > ($meeting['start_time'] + 600)) {
//            return self::setErrorInfo('会议开始10分钟分不能签到');
        }
        try {
            $meetingUser = Db::name('meeting_user')
                ->where('meeting_id', $data->id)
                ->where('user_id', $user_id)
                ->find();
        } catch (\Exception $exception) {
            return self::setErrorInfo($exception->getMessage());
        }
        if (empty($meetingUser)) {
            return self::setErrorInfo('二维码错误！');
        }
        if ($meetingUser['sign_status'] != 0) {
            return self::setErrorInfo('已签到，请勿重复签到');
        }
        try {
            $r = MeetingUser::where('meeting_id', $data->id)
                ->where('user_id', $user_id)
                ->update(['sign_status' => 1, 'sign_time' => time()]);
            if ($r) {
                return true;
            } else {
                return self::setErrorInfo('签到失败');
            }
        } catch (\Exception $exception) {
            return self::setErrorInfo($exception->getMessage());
        }
    }
}
