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

use app\common\controller\ModelHelper;
use app\common\model\Department as DepModel;
use app\common\model\DicData;
use app\common\model\UserDep;
use app\common\model\WorkUnit;
use think\Db;
use util\Tree;

/**
 * 组织管理
 * Class Department
 * @package app\admin\controller
 */
class Department extends AdminBase
{

    protected $department_model;

    protected function initialize()
    {
        parent::initialize();
        $this->department_model = new DepModel();
    }

    /**
     * 栏目管理
     *
     * @return mixed
     */
    public function index()
    {
        $type_options = DepModel::$type_options;
        $type_options[''] = '全部';
        $modelHelper = new ModelHelper();
        $modelHelper->setTitle('list test')
            ->addSearchField('名称', 'name', 'text', ['exp' => 'LIKE'])
            ->addSearchField('组织类别', 'type', 'select', ['options' => $type_options]);

        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $department_level_list = $this->department_model
                ->where($where)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->field('id, name, short_name, parent_id, type, show, sort')
                ->select()
                ->toArray();
            if (empty($where)) {
                $Trees = new Tree();
                $Trees->tree($department_level_list, 'id', 'parent_id', 'short_name');
                $department_level_list = $Trees->getArray();
                unset($department_level_list[0]);
            }
            $list = array_values($department_level_list);
            foreach ($list as $k => &$v) {
                $sub_dep = DepModel::getSubDep([$v['id']]);
                //党组织，不包括党小组
                $v['dep_count'] = DepModel::where('type', '<>', DepModel::$type_squad)->count();
                //党员
                $v['user_count'] = (string)Db::name('user')
                    ->alias('a')
                    ->where('b.dep_id', 'in', $sub_dep)
                    ->leftJoin('user_dep b', 'a.id=b.user_id')
                    ->group('a.id')
                    ->count();
                //班子成员
                $v['lead_count'] = (string)Db::name('UserDep')
                    ->where('dep_id', $v['id'])
                    ->alias('a')
                    ->join('__USER_LEVEL__ b', 'a.level_id=b.id')
                    ->where('b.type', '>', 0)
                    ->cache(300)
                    ->count();
                //组织类型
                $v['type_text'] = DepModel::$type_options[$v['type']];
                //组织管理权限的可以显示按钮
                $v['manager'] = in_array($v['id'], array_keys(session('dep_auth'))) ? 1 : 0;
                $v['allow_add_sub'] = $v['manager'] && !empty(DepModel::allowableAddSubType($v['type']));
            }
            return json(['code' => 1, 'msg' => '', 'count' => count($department_level_list), 'data' => $list]);
        }

        $modelHelper
            ->addTopBtn('添加组织', url('add'))
            ->addTips('点击排序的序号可编辑排序，数字越小排序越前')
            ->addField('简称', 'short_name', 'text', ['minWidth' => 200])
            ->addField('全称', 'name', 'text', ['minWidth' => 300])
            ->addField('组织概况', 'info', 'text', ['width' => 350, 'templet' => '<div>
                                <div class="layui-row">
                                    <div class="layui-col-xs4" title="统计当前党组织下组织数量
（包含当前组织节点）">
                                        党组织：{{= d.dep_count}}
                                    </div>
                                    <div class="layui-col-xs4" title="统计当前党组织及下辖节点
的党员数量">
                                        党员：{{= d.user_count}}
                                    </div>
                                    <div class="layui-col-xs4" title="统计当前党组织下班子成员">
                                        班子成员：{{= d.lead_count}}
                                    </div>
                                </div>
                                </div>'])
            ->addField('组织类别', 'type_text', 'text', ['width' => 100])
            ->addField('排序', 'sort', 'text', ['edit' => 'text', 'width' => 60])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'show', 'opt' => ['field' => 'show', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 100,
                'toolbar' => '#barDemo1'
            ])
            ->addRowBtn('新增下级', url('add'), 'barDemo', ['field' => 'allow_add_sub', 'operator' => '==', 'value' => 1], 'btn-warm', 'btn')
            ->addRowBtn('管理员', url('dep_admin/index'), 'barDemo', ['field' => 'manager', 'operator' => '==', 'value' => 1])
            ->addRowBtn('编辑', url('edit'), 'barDemo', ['field' => 'manager', 'operator' => '==', 'value' => 1])
            ->addRowBtn('变动', url('change'), 'barDemo', ['field' => 'manager', 'operator' => '==', 'value' => 1], '', 'btn', ['openType' => 'layer'])
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 250,
            ]);
        return $modelHelper->setPage(false)->showList();
    }

    /**
     * 添加组织
     * @param string $pid
     * @return mixed
     */
    public function add($id = '')
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        //保存或增加
        if (request()->isAjax()) {
            $data = input('post.');
            if ($data['parent_id'] != 0 && !in_array($data['parent_id'], $auth_dep_arr)) {
                $this->error('您没有权限在该组织下新增组织');
            }
            if ($data['parent_id'] > 0) {
                $parent_dep = DepModel::get($data['parent_id']);
                if ($parent_dep['type'] == 0) {
                    $this->error('请先设置上级组织的党组织类别');
                }
                $allowableAddSubType = DepModel::allowableAddSubType($parent_dep['type']);
                if (!in_array($data['type'], $allowableAddSubType)) {
                    $this->error('上级组织下不允许添加此类别的的组织');
                }
            }
            foreach ($data['attachment'] as $k => $attachment) {
                if (empty($attachment)) unset($data['attachment'][$k]);
            }
            $validate = new \app\admin\validate\Department();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            //获取他的父级 知道层级 layer
            if ($data['parent_id'] == 0) {
                $data['layer'] = 1;
            } else {
                $parent = $this->department_model->where('id', $data['parent_id'])->find();
                $data['layer'] = $parent['layer'] + 1;
            }
            $res = $this->department_model->allowField([
                'parent_id',
                'name',
                'short_name',
                'image',
                'layer',
                'show',
                'sort',
                'introduce',
                'createtime',
                'type',
                'address',
                'belong_work_unit_type',
                'attachment'
            ])->save($data);
            if ($res) {
                $id = $this->department_model->id;
                //保存单位信息
                $rows = [];
                foreach ($data['work_unit'] as $k => $v) {
                    $rows[] = [
                        'dep_id' => $id,
                        'name' => $v['name'],
                        'type' => $v['type'],
                        'build_type' => $v['build_type']
                    ];
                }
                $workUnit = new WorkUnit();
                $workUnit->saveAll($rows);
                if (session('login_type') == 'admin_user') {
                    //后台管理员可以查看所有
                    $auth_list = Db::name('department')->column('id');
                    //$auth = [0 => 1];
                    $auth = [];
                    foreach ($auth_list as $k => $v) {
                        $auth[$v] = 1;
                    }
                    session('dep_auth', $auth);
                } elseif (session('login_type') == 'user') {
                    $dep_admin = \app\common\model\AuthGroupAccessDep::where('uid', session('user_id'))->
                    column('dep_id');
                    //获取这个组织的所有下级组织
                    $auth = DepModel::getSubDep($dep_admin);
                    $dep_auth = [];
                    foreach ($auth as $k => $v) {
                        $dep_auth[$v] = 1;
                    }
                    session('dep_auth', $dep_auth);
                }
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        $item = [
            'show' => 1,
            'sort' => 1,
        ];
        if ($id) {
            if (!in_array($id, $auth_dep_arr)) {
                $this->error('您没有权限在该组织下新增组织');
            }
            $item['parent_id'] = $id;
            $type_options = DepModel::$type_options;
            $parent_dep = DepModel::get($item['parent_id']);
            $allowableAddSubType = DepModel::allowableAddSubType($parent_dep['type']);
            foreach ($type_options as $k => $v) {
                $type_options[$k] = [
                    'title' => $v,
                    'disabled' => !in_array($k, $allowableAddSubType)
                ];
            }
        } else {
            $type_options = DepModel::$type_options;
        }
        //指定添加子级组织的时候不能选其他组织
        $options = $this->department_model->optionsArr();
        foreach ($options as $k => $v) {
            $options[$k] = [
                'title' => $v,
                'disabled' => ($k > 0 && (!in_array($k, $auth_dep_arr)) || ($id > 0 && $id != $k))
            ];
        }
        $this->assign('dep_options', $options);
        $this->assign('type_options', $type_options);

        $belong_work_unit_type_column = DicData::validValueTextColumn('belong_work_unit_type');
        $work_unit_type_column = DicData::validValueTextColumn('work_unit_type');
        $work_unit_build_type_column = DicData::validValueTextColumn('work_unit_build_type');

        $this->assign('belong_work_unit_type_column', $belong_work_unit_type_column);
        $this->assign('work_unit_type_column', $work_unit_type_column);
        $this->assign('work_unit_build_type_column', $work_unit_build_type_column);

        //默认有一个单位信息
        $work_unit_arr[] = [
            'name' => '',
            'type' => '',
            'build_type' => '',
        ];
        $this->assign('work_unit_arr', $work_unit_arr);
        $this->assign('data', $item);
        return view('edit');
    }

    /**
     * 编辑组织
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($id, $auth_dep_arr)) {
            $this->error('您没有权限编辑该组织信息!');
        }
        //保存或增加
        if (request()->isAjax()) {
            $data = input('post.');
            foreach ($data['attachment'] as $k => $attachment) {
                if (empty($attachment)) unset($data['attachment'][$k]);
            }
            $validate = new \app\admin\validate\Department();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $children = $this->department_model->where('parent_id', $id)->column('id');
            if (in_array($data['parent_id'], $children)) {
                //TODO 目前只检测了第一层子集
                $this->error('不能移动到自己的下级组织');
            }
            //获取他的父级 知道层级 layer
            if ($data['parent_id'] == 0) {
                $data['layer'] = 1;
            } else {
                $parent = $this->department_model->where('id', $data['parent_id'])->find();
                $data['layer'] = $parent['layer'] + 1;
            }
            unset($data['id']);
            $res = $this->department_model->allowField([
                'parent_id',
                'name',
                'short_name',
                'image',
                'layer',
                'show',
                'sort',
                'introduce',
                'createtime',
                'type',
                'address',
                'belong_work_unit_type',
                'attachment'
            ])->save($data, ['id' => $id]);
            if ($res) {
                //保存单位信息
                $workUnit = new WorkUnit();
                //这里先使用这种方式，删除完再重新添加 TODO 优化，使用按id的方式编辑
                $workUnit->where(['dep_id' => $id])->delete();
                $rows = [];
                foreach ($data['work_unit'] as $k => $v) {
                    if (empty(trim($v['name']))) continue;
                    $rows[] = [
                        'dep_id' => $id,
                        'name' => trim($v['name']),
                        'type' => $v['type'],
                        'build_type' => $v['build_type']
                    ];
                }
                $workUnit->saveAll($rows);
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }
        $department = $this->department_model->find($id);
        $options = $this->department_model->optionsArr();
        $type_options = DepModel::$type_options;

        $this->assign('dep_options', $options);
        $this->assign('type_options', $type_options);

        $belong_work_unit_type_column = DicData::validValueTextColumn('belong_work_unit_type');
        $work_unit_type_column = DicData::validValueTextColumn('work_unit_type');
        $work_unit_build_type_column = DicData::validValueTextColumn('work_unit_build_type');

        $this->assign('belong_work_unit_type_column', $belong_work_unit_type_column);
        $this->assign('work_unit_type_column', $work_unit_type_column);
        $this->assign('work_unit_build_type_column', $work_unit_build_type_column);

        //默认有一个单位信息
        $workUnit = new WorkUnit();
        $work_unit_arr = $workUnit->where('dep_id', $department['id'])->select();
        $work_unit_arr = is_object($work_unit_arr) ? $work_unit_arr->toArray() : [];
        if (empty($work_unit_arr)) {
            //默认有一个单位信息
            $work_unit_arr[] = [
                'name' => '',
                'type' => '',
                'build_type' => '',
            ];
        }
        $this->assign('work_unit_arr', $work_unit_arr);
        $this->assign('data', $department);
        return view('edit');
    }


    /**
     * 删除组织组织
     * @param $id
     */
    public function delete($id)
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($id, $auth_dep_arr)) {
            $this->error('您没有权限删除该组织信息');
        }
        $department = $this->department_model->where(['parent_id' => $id])->find();
        if (!empty($department)) {
            $this->error('此组织下存在子组织，不可删除');
        }
        if ($this->department_model->destroy($id)) {
            UserDep::where('dep_id', $id)->delete();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @param $id
     * @param $name
     * @param $status
     * @return \think\response\Json
     */
    public function updateStatus($id, $name, $status)
    {
        $auth_dep_arr = array_keys(session('dep_auth'));
        if (!in_array($id, $auth_dep_arr)) {
            $this->error('您没有权限更新该组织信息');
        }
        if ($this->department_model->where('id', $id)->update([$name => $status]) !== false) {
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    public function updateField()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $auth_dep_arr = array_keys(session('dep_auth'));
            if (!in_array($data['id'], $auth_dep_arr)) {
                $this->error('您没有权限编辑该组织信息');
            }
            if ($data['id']) {
                $r = $this->department_model->allowField('sort')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 变动 升级 合并 迁移
     *
     * @param $id
     * @return \think\response\View
     */
    public function change($id)
    {
        if (request()->isAjax()) {
            $post = input('post.');
            if ($post['change_type'] == 1) {
                //升级
                $res = DepModel::changeType($id, $post['type']);
                if ($res) {
                    $this->success('升级成功');
                } else {
                    $this->error(DepModel::getErrorInfo());
                }
            } elseif ($post['change_type'] == 2) {
                $res = DepModel::mergeDep($id, $post['merger_target_id']);
                if ($res) {
                    $this->success('合并成功');
                } else {
                    $this->error(DepModel::getErrorInfo());
                }
            } elseif ($post['change_type'] == 3) {
                $res = DepModel::transferDep($id, $post['transfer_target_id']);
                if ($res) {
                    $this->success('迁移成功');
                } else {
                    $this->error(DepModel::getErrorInfo());
                }
            } elseif ($post['change_type'] == 4) {
                $res = DepModel::delDep($id);
                if ($res) {
                    $this->success('删除成功');
                } else {
                    $this->error(DepModel::getErrorInfo());
                }
            }
        }

        $item = DepModel::where('id', $id)->find();
        $this->assign('data', $item);

        //自己的下级
        $sub_dep = DepModel::getSubDep([$item['id']]);

        $auth_dep_arr = array_keys(session('dep_auth'));
        $dep_options = $this->department_model->optionsArr();
        //可以合并的组织
        $dep_options_merge = [];
        foreach ($dep_options as $k => $v) {
            //disabled 判断是否有权限 是否下级 类型是否允许
            $dep_options_merge[$k] = [
                'title' => $v,
                'disabled' => !in_array($k, $auth_dep_arr) || $k == $id || in_array($k, $sub_dep) || $item['type'] != $this->department_model->where('id', $k)->value('type')
            ];
        }
        $this->assign('dep_options_merge', $dep_options_merge);

        //可以迁移的组织
        $dep_options_transfer = [];
        $allowableTransfer = DepModel::allowableTransfer($item['type']);
        foreach ($dep_options as $k => $v) {
            //disabled 判断是否有权限 是否是目前的上级 是否下级 类型是否允许
            $dep_options_transfer[$k] = [
                'title' => $v,
                'disabled' => !in_array($k, $auth_dep_arr) || ($item['parent_id'] == $k) || $k == $id || in_array($k, $sub_dep) || !in_array($this->department_model->where('id', $k)->value('type'), $allowableTransfer)
            ];
        }
        $this->assign('dep_options_transfer', $dep_options_transfer);

        $type_options = DepModel::$type_options;
        $allowable_change_type = DepModel::allowableChangeType($item['type']);
        foreach ($type_options as $k => &$v) {
            $v = [
                'title' => $v,
                'disabled' => !in_array($k, $allowable_change_type) && $k != $item['type'],
            ];
        }
        $this->assign('type_options', $type_options);
        return view();
    }
}