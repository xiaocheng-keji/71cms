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

use app\admin\controller\AdminBase;
use app\common\controller\ModelHelper;
use app\common\model\Push as PushModel;
use think\Db;

/**
 * 推送配置管理
 * Class PushConfig
 * @package app\admin\controller
 */
class PushConfig extends AdminBase
{
    public function index()
    {
        return view();
    }

    public function config($channel_type = '')
    {
        // 判断 $channel_type 必须是本类中的方法名
        if (!method_exists($this, $channel_type)) {
            $this->error('参数错误');
        }
        return call_user_func([$this, $channel_type]);
    }

    /**
     * 格式化数据
     * @param $data
     * @return array
     */
    protected function format2list($data, $channel_type)
    {
        $rows = [];
        foreach ($data as $item) {
            if (isset($item['status'])) {
                $rows[] = [
                    'channel_type' => $channel_type,
                    'type' => $item['type'],
                    'status' => $item['status'],
                    'value' => $item['value'],
                ];
            }
        }
        return $rows;
    }

    /**
     * 格式化数据
     * @param $rows
     * @return array
     */
    protected function formt2listByType($rows, $channel_type)
    {
        // 将数组转换成键值对
        $configList = [];
        foreach ($rows as $row) {
            $configList[$row['type']] = [
                'channel_type' => $channel_type,
                'type' => $row['type'],
                'status' => $row['status'],
                'value' => $row['value'],
            ];
        }
        return $configList;
    }

    /**
     * 微信推送
     */
    public function wechat()
    {
        $channel_type = 'wechat';
        if (request()->isPost()) {
            Db::name('push_config')->where('channel_type', $channel_type)->delete();
            $data = input('post.');
            $rows = $this->format2list($data, $channel_type);
            Db::name('push_config')->insertAll($rows);
            $this->success('保存成功');
        }
        $rows = Db::name('push_config')->where('channel_type', $channel_type)->select();
        $configList = $this->formt2listByType($rows, $channel_type);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
            ->addFieldset('通知公告')
            ->addField('类型', 'notice[type]', 'hidden', ['value' => 'notice'])
            ->addField('状态', 'notice[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'notice[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('会议通知')
            ->addField('类型', 'meeting[type]', 'hidden', ['value' => 'meeting'])
            ->addField('状态', 'meeting[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'meeting[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('活动通知')
            ->addField('类型', 'activity[type]', 'hidden', ['value' => 'activity'])
            ->addField('状态', 'activity[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'activity[value]', 'text', ['tip' => '公众号后台添加获取'])
//            ->addFieldset('考试通知')
//            ->addField('类型', 'exam[type]', 'hidden', ['value' => 'exam'])
//            ->addField('状态', 'exam[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addField('模板ID', 'exam[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('书记信箱通知')
            ->addField('类型', 'sec_mail[type]', 'hidden', ['value' => 'sec_mail'])
            ->addField('状态', 'sec_mail[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'sec_mail[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('任务清单通知')
            ->addField('类型', 'task[type]', 'hidden', ['value' => 'task'])
            ->addField('状态', 'task[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'task[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }

    /**
     * 个推推送
     */
    public function getui()
    {
        $channel_type = 'getui';
        if (request()->isPost()) {
            $data = input('post.');
            Db::name('push_config')->where('channel_type', $channel_type)->delete();
            $rows = $this->format2list($data, $channel_type);
            Db::name('push_config')->insertAll($rows);
            $this->success('保存成功');
        }
        $rows = Db::name('push_config')->where('channel_type', $channel_type)->select();
        $configList = $this->formt2listByType($rows, $channel_type);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
//            ->addFieldset('通知公告')
//            ->addField('类型', 'notice[type]', 'hidden', ['value' => 'notice'])
//            ->addField('状态', 'notice[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('会议通知')
            ->addField('类型', 'meeting[type]', 'hidden', ['value' => 'meeting'])
            ->addField('状态', 'meeting[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('活动通知')
//            ->addField('类型', 'activity[type]', 'hidden', ['value' => 'activity'])
//            ->addField('状态', 'activity[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('考试通知')
//            ->addField('类型', 'exam[type]', 'hidden', ['value' => 'exam'])
//            ->addField('状态', 'exam[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('书记信箱通知')
//            ->addField('类型', 'sec_mail[type]', 'hidden', ['value' => 'sec_mail'])
//            ->addField('状态', 'sec_mail[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('任务清单通知')
//            ->addField('类型', 'task[type]', 'hidden', ['value' => 'task'])
//            ->addField('状态', 'task[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }

    /**
     * 企业微信推送
     */
    public function work_weixin()
    {
        $channel_type = 'work_weixin';
        if (request()->isPost()) {
            $data = input('post.');
            Db::name('push_config')->where('channel_type', $channel_type)->delete();
            $rows = $this->format2list($data, $channel_type);
            Db::name('push_config')->insertAll($rows);
            $this->success('保存成功');
        }
        $rows = Db::name('push_config')->where('channel_type', $channel_type)->select();
        $configList = $this->formt2listByType($rows, $channel_type);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
            ->addFieldset('通知公告')
            ->addField('类型', 'notice[type]', 'hidden', ['value' => 'notice'])
            ->addField('状态', 'notice[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('会议通知')
            ->addField('类型', 'meeting[type]', 'hidden', ['value' => 'meeting'])
            ->addField('状态', 'meeting[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('活动通知')
            ->addField('类型', 'activity[type]', 'hidden', ['value' => 'activity'])
            ->addField('状态', 'activity[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('考试通知')
//            ->addField('类型', 'exam[type]', 'hidden', ['value' => 'exam'])
//            ->addField('状态', 'exam[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addField('模板ID', 'exam[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('书记信箱通知')
            ->addField('类型', 'sec_mail[type]', 'hidden', ['value' => 'sec_mail'])
            ->addField('状态', 'sec_mail[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('任务清单通知')
            ->addField('类型', 'task[type]', 'hidden', ['value' => 'task'])
            ->addField('状态', 'task[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }

    /**
     * 钉钉推送
     */
    public function dingtalk()
    {
        $channel_type = 'dingtalk';
        if (request()->isPost()) {
            $data = input('post.');
            Db::name('push_config')->where('channel_type', $channel_type)->delete();
            $rows = $this->format2list($data, $channel_type);
            Db::name('push_config')->insertAll($rows);
            $this->success('保存成功');
        }
        $rows = Db::name('push_config')->where('channel_type', $channel_type)->select();
        $configList = $this->formt2listByType($rows, $channel_type);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
            ->addFieldset('通知公告')
            ->addField('类型', 'notice[type]', 'hidden', ['value' => 'notice'])
            ->addField('状态', 'notice[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('会议通知')
            ->addField('类型', 'meeting[type]', 'hidden', ['value' => 'meeting'])
            ->addField('状态', 'meeting[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('活动通知')
            ->addField('类型', 'activity[type]', 'hidden', ['value' => 'activity'])
            ->addField('状态', 'activity[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addFieldset('考试通知')
//            ->addField('类型', 'exam[type]', 'hidden', ['value' => 'exam'])
//            ->addField('状态', 'exam[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addField('模板ID', 'exam[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('书记信箱通知')
            ->addField('类型', 'sec_mail[type]', 'hidden', ['value' => 'sec_mail'])
            ->addField('状态', 'sec_mail[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addFieldset('任务清单通知')
            ->addField('类型', 'task[type]', 'hidden', ['value' => 'task'])
            ->addField('状态', 'task[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }

    /**
     * 短信推送
     */
    public function sms()
    {
        $channel_type = 'sms';
        if (request()->isPost()) {
            $data = input('post.');
            Db::name('push_config')->where('channel_type', $channel_type)->delete();
            $rows = $this->format2list($data, $channel_type);
            Db::name('push_config')->insertAll($rows);
            $this->success('保存成功');
        }
        $rows = Db::name('push_config')->where('channel_type', $channel_type)->select();
        $configList = $this->formt2listByType($rows, $channel_type);
        //有新的配置 在下面加字段，保存的时候自动添加到数据库
        $modelHelper = new ModelHelper();
        $modelHelper
            ->addFieldset('通知公告')
            ->addField('类型', 'notice[type]', 'hidden', ['value' => 'notice'])
            ->addField('状态', 'notice[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'notice[value]', 'text', ['tip' => '短信平台添加获取'])
            ->addFieldset('会议通知')
            ->addField('类型', 'meeting[type]', 'hidden', ['value' => 'meeting'])
            ->addField('状态', 'meeting[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'meeting[value]', 'text', ['tip' => '短信平台添加获取'])
            ->addFieldset('活动通知')
            ->addField('类型', 'activity[type]', 'hidden', ['value' => 'activity'])
            ->addField('状态', 'activity[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'activity[value]', 'text', ['tip' => '短信平台添加获取'])
//            ->addFieldset('考试通知')
//            ->addField('类型', 'exam[type]', 'hidden', ['value' => 'exam'])
//            ->addField('状态', 'exam[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
//            ->addField('模板ID', 'exam[value]', 'text', ['tip' => '公众号后台添加获取'])
            ->addFieldset('书记信箱通知')
            ->addField('类型', 'sec_mail[type]', 'hidden', ['value' => 'sec_mail'])
            ->addField('状态', 'sec_mail[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'sec_mail[value]', 'text', ['tip' => '短信平台添加获取'])
            ->addFieldset('任务清单通知')
            ->addField('类型', 'task[type]', 'hidden', ['value' => 'task'])
            ->addField('状态', 'task[status]', 'radio', ['require' => '*', 'options' => ['1' => '启用', '0' => '禁用']])
            ->addField('模板ID', 'task[value]', 'text', ['tip' => '短信平台添加获取'])
            ->setData($configList);
        return $modelHelper->showForm(false);
    }
}
