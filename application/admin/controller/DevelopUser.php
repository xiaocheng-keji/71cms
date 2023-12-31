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


use app\admin\model\DevelopUser as DevelopUserModel;
use app\admin\model\DevelopUserTasklist;
use app\admin\model\TaskList;
use app\common\controller\ModelHelper;
use app\common\model\DicData;
use app\common\model\TmpSession;
use think\facade\Request;

/**
 * 党员发展
 * Class Department
 * @package app\admin\controller
 */
class DevelopUser extends AdminBase
{
    /**
     * 列表
     *
     * @return \think\response\Json|\think\response\View
     */
    public function index()
    {
        $status = ['' => '请选择发展阶段'];
        $status = $status + DevelopUserModel::$status;
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('请选择发展阶段', 'status', 'select', ['options' => $status, 'exp' => '='])
            ->addSearchField('入党申请开始时间', 'apply_time', 'date', ['range' => '~', 'placeholder' => '入党申请开始时间~入党申请结束时间', 'exp' => 'between time', 'attr' => 'style="width:220px;height:30px;"'])
            ->addSearchField('姓名/手机号', 'nickname', 'text', ['exp' => 'like', 'attr' => 'style="width:220px;height:30px;"']);

        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            foreach ($where as $k => $v) {
                if ($v[0] == 'nickname') {
                    $subWhere = $v;
                    unset($where[$k]);
                    break;
                }
            }
            $model = new DevelopUserModel();
            if (!$this->admin_user['is_super']){
                $where[] = ['dep_id', 'in', TmpSession::getDepAuth()];
            }
            $model = $model->where($where);
            if ($subWhere) {
                $model->where('nickname|phone', 'like', $subWhere[2]);
            }
            $count = $model->count();
            $list = $model
                ->field('*')
                ->group('id')
                ->page(input('page', 1), input('limit', 10))
                ->order('id desc')
                ->select();
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $taskListInfo = TaskList::order('id asc')->find();

        $modelHelper
            ->addTopBtn('新建入党申请', Url('edit'))
            ->addTopBtn('导出全部数据', Url('export'))
            ->addField('xx', 'id', 'checkbox', ['fixed' => 'left'])
            ->setToolbarId('#toolbarExport')
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('姓名', 'nickname', 'text')
            ->addField('发展阶段', 'status', 'select')
            ->addField('入党申请时间', 'apply_time', 'date');
        /*if (!empty($taskListInfo)) {
            $modelHelper
                ->addRowBtn('发展任务', Url('TaskInfo/detail', ['task_type' => TaskList::$TASK_TYPE_DEVELOP]), 'barDemo', null, 'btn-warm');
        } else {
            $modelHelper
                ->addRowBtn('发展任务', Url('TaskInfo/detail', ['task_type' => TaskList::$TASK_TYPE_DEVELOP]), 'barDemo', null, 'btn-warm');
        }*/

        $modelHelper
            ->addRowBtn('编辑', Url('edit'))
            ->addRowBtn('删除', Url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 200
            ]);
        return $modelHelper->showList();
    }

    /**
     * @param string $id
     * @return \think\response\View
     * 编辑 添加
     */
    public function edit(string $id = '')
    {
        $model = new DevelopUserModel();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //新建
            if (!$id) {
                $data['status'] = 1;
                $count = $model->where('phone', $data['phone'])->count();
                if ($count) {
                    $this->error('手机号已存在');
                }
                $count = $model->where('idcard', $data['idcard'])->count();
                if ($count) {
                    $this->error('身份证号已存在');
                }
                $count = \app\common\model\User::where('mobile', $data['phone'])->count();
                if ($count) {
                    $this->error('手机号在党员档案中已存在');
                }
            } else {
                $count = $model->where('phone', $data['phone'])->where('id', '<>', $id)->count();
                if ($count) {
                    $this->error('手机号已存在');
                }
                $count = $model->where('idcard', $data['idcard'])->where('id', '<>', $id)->count();
                if ($count) {
                    $this->error('身份证号已存在');
                }
            }
            $validate = $this->validate($data, 'DevelopUser.edit');
            if ($validate !== true) {
                return json(['code' => -1, 'msg' => $validate]);
            }

            //判断时间 先申请，再预备，最后转正
            if (!empty($data['yubei_time']) && (strtotime($data['yubei_time']) <= strtotime($data['apply_time']))) {
                $this->error('成为预备党员时间要大于申请入党时间');
            }
            if (!empty($data['zhuanzheng_time']) && strtotime($data['zhuanzheng_time']) <= strtotime($data['yubei_time'])) {
                $this->error('转正时间要大于成为预备党员时间');
            }


            if (!empty($data['zhuanzheng_time']) && strtotime($data['zhuanzheng_time']) <= strtotime($data['apply_time'])) {
                $this->error('转正时间要大于申请入党时间');
            }

            if ($id) {
                $item = $model->get($id);
                $r = $model->allowField(true)->save($data, ['id' => $id]);
                //发通知
                if ($data['status'] != $item->getData('status')) {
                    if ($item['user_id'] > 0) {
                        //TODO 发送消息
                        /*$item = $model->get($id);
                        $user = \app\admin\model\User::get($item['user_id']);
                        $param['openid'] = $user['openid'];
                        $param['title'] = '入党申请状态改变';
                        $param['first'] = '变为' . $item['status'];
                        $param['time'] = date('Y-m-d');
                        $param['remark'] = '';*/
                    }
                }
                /*if ($data['is_task_remind']) {
                    $map = DevelopUserTasklist::getDepTaskListMap($id);
                    session(DevelopUserTasklist::$SESSION_SAVE_DEVELOP_ID, $id);
                    $info = TaskList::order('id asc')->find();
                    if (!empty($info)) {
                        if (empty($map)) {
                            $this->success('新增成功', url('TaskInfo/addNEditTask', ['info_id' => $info['id'], 'task_type' => TaskList::$TASK_TYPE_DEVELOP]));
                        } else {
                            $this->success('新增成功', url('TaskInfo/addNEditTask', ['info_id' => $info['id'], 'id' => $map['tasklist_id'], 'task_type' => TaskList::$TASK_TYPE_DEVELOP]));
                        }
                    }
                }*/
            } else {
                //新增
                $r = $model->allowField(true)->save($data);
                if ($data['is_task_remind']) {
                    //新建立提醒“任务清单”
                    if ($r) {
                        session(DevelopUserTasklist::$SESSION_SAVE_DEVELOP_ID, $model->id);
                        $this->success('新增成功', url('TaskInfo/addNEditTask', ['task_type' => TaskList::$TASK_TYPE_DEVELOP, 'info_id' => $model->id]));
                    }
                }
            }

            if ($r) {
                $msg = '';
                if ($data['is_task_remind']) {
                    $msg = '请初始化“任务清单”，自建党员发展提醒任务';
                    $msg = '';
                }
                $this->success('保存成功' . $msg);
            } else {
                $this->error('保存失败');
            }
        }
        $item = [];
        if ($id) {
            $item = $model->get($id);
            $item['train'] = \app\admin\model\User::where('id', $item['train_uid'])->value('nickname');
            if (empty($item)) {
                $this->error('item error');
            }
            if ($item['train_uid']) {
                $choice_user = explode(',', $item['train_uid']);
                $choice_user_arr = [];
                foreach ($choice_user as $k => $v) {
                    $explode_arr = explode('-', $v);
                    $choice_user_arr[] = [
                        'id' => $explode_arr[0],
                        'name' => $explode_arr[1],
                        'type' => 'user'
                    ];
                }
                $item['choice_user'] = $choice_user_arr;
            }
        }else{
            //默认session('dep_auth')的第一个key
            $item['dep_id'] = TmpSession::getDepAuth()[0];
        }
        $this->assign('sex_column', DicData::validValueTextColumn('sex'));
        $this->assign('nation_column', DicData::validValueTextColumn('nation'));
        $this->assign('education_column', DicData::validValueTextColumn('education'));


        $jiguan_options = \app\common\model\User::$jiguan;
        $status_options = DevelopUserModel::$status;
        $this->assign('native_options', array_val_key($jiguan_options));
        $this->assign('status_options', $status_options);
        $this->assign('data', $item);

        $auth_dep_arr = TmpSession::getDepAuth();
        $DepModel = new \app\common\model\Department();
        $dep_options = $DepModel->optionsArr();
        foreach ($dep_options as $k => $v) {
            //disabled 判断是否有权限
            $dep_options[$k] = [
                'title' => $v,
                'disabled' => (!in_array($k, $auth_dep_arr))
            ];
        }
        $this->assign('dep_options', $dep_options);

        return $this->fetch();
    }

    /**
     * 删除
     *
     * @param string $id
     */
    public function delete(string $id)
    {
        $item = DevelopUserModel::get($id);
        if ($item && $item->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 更新字段
     */
    public function updateField()
    {
        $model = new DevelopUserModel();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $r = $model->allowField('leixing')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }

    /**
     * 导出信息
     */
    public function export()
    {
        $post = Request::post();
        $where = [];
        if ($post['id']) {
            $where[] = ['id', 'in', $post['id']];
        }
        // 限制只能导出自己部门的数据
        $where[] = ['dep_id', 'in', TmpSession::getDepAuth()];
        $model = new DevelopUserModel();
        $list = $model::where($where)
            ->order('id', 'desc')
            ->select();
        $list = $list->toArray();
        foreach ($list as &$v) {
            $v['train'] = $v['train_uid'] ? \app\admin\model\User::where('id', $v['train_uid'])->value('nickname') : '';
            $v['dep_name'] = $v['dep_id'] ? \app\common\model\Department::where('id', $v['dep_id'])->value('name') : '';
        }
        if (empty($list)) {
            $this->error('允许导出的数据为空');
        }
        $fields = [
            ['key' => 'id', 'name' => '编号'],
            ['key' => 'dep_name', 'name' => '组织'],
            ['key' => 'nickname', 'name' => '姓名'],
            ['key' => 'sex', 'name' => '性别'],
            ['key' => 'nation', 'name' => '民族'],
            ['key' => 'native_place', 'name' => '籍贯'],
            ['key' => 'birthday', 'name' => '出生日期'],
            ['key' => 'education', 'name' => '学历'],
            ['key' => 'apply_time', 'name' => '申请入党时间'],
            ['key' => 'apply_petition', 'name' => '入党申请书'],
            ['key' => 'leixing', 'name' => '人员类型'],
            ['key' => 'train', 'name' => '培养联系人'],
        ];
        $dataList = [
            ['province' => '1省', 'city' => '1市', 'district' => 'xx县', 'street' => 'xx街道'],
            ['province' => '2省', 'city' => '2市', 'district' => 'xx县', 'street' => 'xx街道'],
            ['province' => '3省', 'city' => '3市', 'district' => 'xx县', 'street' => 'xx街道'],
            ['province' => '4省', 'city' => '4市', 'district' => 'xx县', 'street' => 'xx街道'],
        ];
        try {
            makeExcel($fields, $list, '导出数据_' . date('Y-m-d H:i'));
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}