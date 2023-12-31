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
namespace app\admin\controller;

use app\admin\model\Config as ConfigModel;
use app\common\controller\ModelHelper;

/**
 * @package app\admin\controller
 */
class Config extends AdminBase
{
    public function index()
    {
        if (request()->isPost()) {
            $config = new ConfigModel;
            $data = input('post.');
            $time = explode('~', $data['bbs_open_time']);
            $data['bbs_open_time'] = $time[0];
            $data['bbs_close_time'] = $time[1];
            $data['pc_bottom_text'] = htmlspecialchars_decode($data['pc_bottom_text']);
            if ($config->saveConfig($data)) {
                $this->success('配置更新成功!');
            } else {
                $this->error('配置更新失败!');
            }
        }

        $configList = ConfigModel::column('varname,value');
        $configList['bbs_open_time'] = $configList['bbs_open_time'] . ' ~ ' . $configList['bbs_close_time'];
        $this->assign('site', $configList);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
            ->showBackBtn(false)
            ->addFieldset('基础信息')
            ->addField('系统名称', 'sys_name', 'text', ['require' => '*'])
            ->addField('后台登录页LOGO', 'admin_login_logo_img', 'image', ['tip' => '建议图片大小：95px * 95px'])
            ->addField('后台左上角LOGO', 'admin_logo_img', 'image', ['tip' => '建议图片大小：200px * 60px'])
            ->addField('小程序审核', 'is_mp_examine', 'radio', ['options' => ['否', '是']])
//            ->addFieldset('PC端配置')
//            ->addField('SEO关键词', 'seo_keyword')
//            ->addField('SEO描述', 'seo_description', 'textarea')
//            ->addField('LOGO', 'pc_logo_img', 'image', ['tip' => '建议图片大小：高度95px'])
//            ->addField('端页面底部说明', 'pc_bottom_text', 'ueditor')
            ->addFieldset('手机端配置')
//            ->addField('登录页LOGO', 'wap_login_logo_img', 'image', ['tip' => '建议图片大小：90px * 90px'])
            ->addField('APK版本号', 'app_code', 'text', ['require' => '*'])
            ->addField('APK版数字', 'app_num', 'text', ['require' => '*'])
            ->addField('APK下载链接', 'app_link', 'text', ['require' => '*'])
            ->addField('APK更新说明', 'app_desc', 'text', ['require' => '*'])
//            ->addFieldset('活动配置')
//            ->addField('活动默认地址', 'activity_address', 'text', ['attr' => "style='width: 360px;'"])
//            ->addField('经度longitude', 'longitude', 'text', ['attr' => "style='width: 360px;'"])
//            ->addField('纬度latitude', 'latitude', 'text', ['attr' => "style='width: 360px;'"])
//            ->addFieldset('短信配置')
//            ->addField('access_key_id', 'sms_access_key_id', 'text', ['attr' => "style='width: 360px;'"])
//            ->addField('access_key_secret', 'sms_access_key_secret', 'text', ['attr' => "style='width: 360px;'"])
//            ->addField('签名', 'sms_sign_name')
//            ->addField('模板ID', 'sms_template_id')
           ->addFieldset('论坛配置')
           ->addField('开放时间', 'bbs_open_time', 'datetime', ['range' => '~', 'attr' => "style='width: 360px;'"])
           ->addField('评论查看', 'bbs_comment_open', 'radio', ['options' => ['管理员可见', '开放']])
//            ->addFieldset('七牛云配置')
//            ->addField('存储课程', 'qiniu', 'radio', ['options' => ['关闭', '开启']])
//            ->addField('地址', 'qiniu_url', 'text', ['attr' => "style='width: 360px;'", 'placeholder' => '请填写您的七牛云域名,如http://myqiniu.com/，"/"结尾'])
//            ->addField('access', 'access_key', 'text', ['attr' => "style='width: 360px;'"])
//            ->addField('secret', 'secret_key', 'text', ['attr' => "style='width: 360px;'"])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }
}
