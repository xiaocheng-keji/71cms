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

use app\common\model\Department as DepModel;
use app\common\controller\ModelHelper;
use app\common\model\DicData;
use app\common\model\User as UserModel;
use app\common\model\UserDep;
use app\common\model\UserLevel;
use think\Db;
use think\facade\Env;

/**
 * 用户管理
 * Class User
 * @package app\admin\controller
 */
class User extends AdminBase
{
    protected $user_model;

    protected function initialize()
    {
        parent::initialize();
        $this->user_model = new UserModel();
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $dep_id = '', $dep_name = '')
    {
        $map = [];
        if ($dep_id != '' && $dep_name) {
            session('dep_id', $dep_id);
            session('dep_name', $dep_name);
            if ($dep_id > 0) {
                $userDep = new UserDep();
                //查询组织下所有子组织的用户
                $dep_ids = DepModel::getSubDep($dep_id);
                $user_ids = $userDep->where('dep_id', 'in', $dep_ids)->column('user_id');
                $map[] = ['a.id', 'in', $user_ids];
                $map[] = ['b.dep_id', 'in', $dep_ids];
            }
        } else {
            $dd = '';
            if (session('dep_id') != '' && session('dep_name') != '' && $dd) {
                $dep_name = session('dep_name');
                $dep_id = session('dep_id');
                if ($dep_id > 0) {
                    $userDep = new UserDep();
                    $user_ids = $userDep->where('dep_id', '=', $dep_id)->column('user_id');

                    $map[] = ['a.id', 'in', array_unique($user_ids)];
                }
            } else {
                $dep_name = '全部';
                //如果是超级管理员可以显示没有在支部里的
                if ($this->admin_user['is_super'] != 1) {
                    $userDep = new UserDep();
                    $user_ids = $userDep->where('dep_id', 'in', array_keys(session('dep_auth')))->column('user_id');
                    $map[] = ['a.id', 'in', array_unique($user_ids)];
                }
                session('dep_id', null);
                session('dep_name', null);
            }
        }
        if ($keyword) {
            $dep_name .= ' 关键词“' . $keyword . '”';
            session('userkeyword', $keyword);
            $map[] = ['a.nickname|a.mobile|a.email', 'like', "%{$keyword}%", 'or'];
        } else {
            if (session('userkeyword') != '' && $page > 1) {
                $dep_name .= ' 关键词“' . session('userkeyword') . '”';
                $map[] = ['a.nickname|a.mobile|a.email', 'like', "%" . session('userkeyword') . "%", 'or'];
            } else {
                session('userkeyword', null);
            }
        }
        $count = Db::name('user')
            ->alias('a')
            ->where($map)
            ->leftJoin('user_dep b', 'a.id=b.user_id')
            ->group('b.user_id')
            ->count();
        $dep_name .= '（共' . $count . '人）';
        $order_by = input('order_by', 'a.id');
        $sort = input('sort', 'DESC');

        $user_list = Db::name('user')
            ->alias('a')
            ->where($map)
            ->leftJoin('user_dep b', 'a.id=b.user_id')
            ->leftJoin('user_level c', 'c.id=b.level_id')
            ->group('b.user_id')
            ->order($order_by, $sort)
            ->field('a.*,c.name as level_name')
            ->page(input('page', 1), input('limit', 10))
            ->select();
        if (Env::get('demo')) {
            // 演示站 把手机号中间隐藏
            foreach ($user_list as $k => $v) {
                $user_list[$k]['mobile'] = substr_replace($v['mobile'], '****', 3, 4);
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $user_list]);
        }
        return $this->fetch('index', ['user_list' => $user_list, 'keyword' => $keyword, 'dep_title' => $dep_name]);
    }

    /**
     * 添加用户
     * @param string $id
     * @return \think\response\Json|\think\response\View
     */
    public function edit(string $id = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 有星号的去掉数据
            $validate = new \app\admin\validate\User();
            if ($id && stripos($data['mobile'], '*') !== false) {
                $data['mobile'] = Db::name('user')->where('id', $id)->value('mobile');
            }
            if ($id && stripos($data['idcard'], '*') !== false) {
                $data['idcard'] = Db::name('user')->where('id', $id)->value('idcard');
            }
            if ($id) {
                if (!$data['password']) {
                    unset($data['password']);
                    unset($data['confirm_password']);
                }
                $validate_result = $validate->scene('edit')->check($data);
            } else {
                if (empty($data['head_pic'])) {
                    unset($data['head_pic']);
                }
                $validate_result = $validate->check($data);
            }

            if ($validate_result !== true) {
                $this->error($validate->getError());
            } else {
                if (empty($data['department'][1]['dep_id'])) {
                    return json(array('code' => 0, 'msg' => '请选择党组织'));
                }
                //判断时间 先申请，再转正，最后入党
                if (!empty($data['join_time']) && (strtotime($data['join_time']) <= strtotime($data['apply_time']))) {
                    $this->error('入党时间要大于申请入党时间');
                }
                if (!empty($data['become_time']) && strtotime($data['become_time']) <= strtotime($data['join_time'])) {
                    $this->error('转正时间要大于入党时间');
                }
                if (!empty($data['become_time']) && strtotime($data['become_time']) <= strtotime($data['apply_time'])) {
                    $this->error('转正时间要大于申请入党时间');
                }
                if ($id) {
                    $mobileUser = $this->user_model->where([['mobile', '=', $data['mobile']], ['id', '<>', $id]])->find();
                    if ($mobileUser) return json(array('code' => 0, 'msg' => '手机号已存在'));
                    unset($data['id']);
                    $r = $this->user_model->allowField(true)->save($data, ['id' => $id]);
                } else {
                    //过虑管理员不能管理的组织的id
                    foreach ($data['department'] as $k => $v) {
                        if ($v['dep_id'] > 0 && !in_array($v['dep_id'], array_keys(session('dep_auth')))) {
                            $dep_name = \app\common\model\Department::where('id', $v['dep_id'])->value('name');
                            $this->error('您没有权限往' . $dep_name . '添加数据');
                        }
                    }
                    $r = $this->user_model->allowField(true)->save($data);
                    $id = $this->user_model->id;
                }
                if ($r) {
                    //保存选择的支部和选择的职务
                    $userDep = new UserDep();
                    $userDep->where(['user_id' => $id])->delete();
                    $rows = [];
                    $filter = [];
                    foreach ($data['department'] as $k => $v) {
                        if (isset($filter[$v['dep_id'] . $v['level_id']])) {
                            continue;
                        }
                        $rows[] = [
                            'user_id' => $id,
                            'dep_id' => $v['dep_id'],
                            'level_id' => $v['level_id'],
                            'add_time' => time(),
                        ];
                        $filter[$v['dep_id'] . $v['level_id']] = true;
                    }
                    $userDep->saveAll($rows);
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
        $level_options = [0 => '无'] + UserLevel::column('name', 'id');
        $department_model = new \app\common\model\Department();
        //指定添加子级组织的时候不能选其他组织
        $auth_dep_arr = array_keys(session('dep_auth'));
        $department_options = $department_model->optionsArr();
        foreach ($department_options as $k => $v) {
            $department_options[$k] = [
                'title' => $v,
                'disabled' => $k > 0 && (!in_array($k, $auth_dep_arr))
            ];
        }
        $dep_arr = [];
        if ($id) {
            $user = $this->user_model->find($id);
            $userDep = new UserDep();
            $dep_arr = $userDep->where('user_id', '=', $user['id'])->select();
        }
        $this->assign('dep_arr', $dep_arr);
        $this->assign('level_options', $level_options);
        $this->assign('dep_options', $department_options);
        $this->assign('native_place', UserModel::$jiguan);
        $this->assign('sex_column', DicData::validValueTextColumn('sex'));
        $this->assign('nation_column', DicData::validValueTextColumn('nation'));
        $this->assign('jobs_column', DicData::validValueTextColumn('job'));
        $this->assign('people_status_column', DicData::validValueTextColumn('people_status'));
        $this->assign('people_type_column', DicData::validValueTextColumn('people_type'));
        $this->assign('education_column', DicData::validValueTextColumn('education'));
        if (Env::get('demo') && $user) {
            // 演示站 把手机号中间隐藏
            $user['mobile'] = substr_replace($user['mobile'], '****', 3, 4);
            $user['idcard'] = preg_replace("/(\d{3,4})\d{11}(\d{1,2})/", "\$1***********\$2", $user['idcard']);
        }
        $this->assign('user', $user);
        return view();
    }

    /**
     * 删除用户
     * @param $id
     */
    public function delete($id)
    {
        if ($this->user_model->where('id', $id)->delete()) {
            UserDep::where('user_id', $id)->delete();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 用户列表
     */
    public function userList($keyword = '', $page = 1)
    {
        return view();
    }

    /**
     * 获取部门树形数据
     */
    public function departmentDtree()
    {
        //选择部门或者是用户的时候使用的树形
        $checked = [];
        $disToSub = input('disToSub', false);
        $department_model = new \app\common\model\Department();
        $list = $department_model->dtreeList(0, array_keys(session('dep_auth')), $checked, 0, 1, $disToSub);
        return json(array('status' => array('code' => 200, 'message' => '操作成功'), 'data' => $list));
    }

    /**
     * 获取用户提示列表
     */
    public function getLikeUserList()
    {
        $keyword = input('keyword');
        if (empty($keyword)) {
            return json(array('code' => 1, 'data' => ''));
        }
        $map[] = ['nickname|mobile', 'like', "%{$keyword}%", 'or'];
        $map[] = ['b.dep_id', 'in', array_keys(session('dep_auth'))];
        $count = Db::name('user')
            ->alias('a')
            ->join('user_dep b', 'a.id = b.user_id')
            ->field('a.id,a.nickname,a.mobile, b.dep_id')
            ->where($map)
            ->order('a.id DESC')
            ->count();
        $user_list = Db::name('user')
            ->alias('a')
            ->join('user_dep b', 'a.id = b.user_id')
            ->leftJoin('department c', 'b.dep_id = c.id')
            ->leftJoin('user_level d', 'b.level_id = d.id')
            ->field('a.id,a.nickname,a.mobile, b.dep_id, c.name as dep_name,d.name as level_name')
            ->where($map)
            ->order('d.sort asc')
            ->order('a.id DESC')
            ->page(input('page', 1), input('size', 10))
            ->select();
        return json(array('code' => 0, 'data' => $user_list, 'count' => $count));
    }

    /**
     * 选择部门与用户
     */
    public function getDepUserList()
    {
        $scene = input('scene', 'all');
        $checkbar = input('checkbar', false);
        $disToSub = input('disToSub', false);
        $this->assign('scene', $scene);
        $this->assign('checkbar', $checkbar);
        $this->assign('disToSub', $disToSub);
        return view();
    }

    /**
     * 选择用户页
     */
    public function choiceUserList()
    {
        return view();
    }

    /**
     * 获取选择用户列表
     */
    public function choiceUser()
    {
        $dep_id = input('dep_id', 0);
        if (empty($dep_id)) {
            $this->error('缺少参数');
        }
        //判断可以查看的组织权限
        $dep_arr = array_keys(session('dep_auth'));
        if (!in_array($dep_id, $dep_arr)) {
            $this->error('您没有查看此组织数据的权限');
        }
        $userDep = new UserDep();
        $user_ids = $userDep->where('dep_id', '=', $dep_id)->column('user_id');
        $userList = $this->user_model->field('id,nickname,head_pic')->where('id', 'in', $user_ids)->order('id DESC')->select();
        $this->assign('userList', $userList);
        return view();
    }

    /**
     * @param $user_id
     * @return \think\response\View
     * 修改用户积分
     */
    public function editPoint($user_id)
    {
        $userInfo = UserModel::find($user_id);
        if (!$userInfo) {
            $this->error('无此用户');
        }
        if ($this->request->isPost()) {
            $desc = input('desc', '');
            if (!$desc) {
                $this->error('请输入修改说明');
            }
            $r = UserModel::savePoint($user_id, input('point', 0), $desc);
            if ($r) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $this->assign('userInfo', $userInfo);
        $modelHelper = new ModelHelper();
        $modelHelper->setTitle('修改' . $userInfo['nickname'] . '的积分，目前积分：' . $userInfo['point'])
            ->addField('加积分', 'point', 'number', ['tip' => '如果填入的是负数将会扣减用户积分'])
            ->addField('说明', 'desc', 'textarea');
        return $modelHelper->showForm();
    }

    /**
     * @return \think\response\Json|\think\response\View
     */
    public function excelList()
    {
        $modelHelper = new ModelHelper();
        if (input('page', 0) > 0) {
            $filesLists = [];
//            getDirFilesLists('sys', $filesLists, false, false, false);
            $list[] = [
                'id' => 1,
                'file_name' => 1,
            ];
            foreach ($filesLists as $k => $v) {
                $list[] = [
                    'id' => $v,
                    'file_name' => $v,
                ];
            }
            $count = count($list);
            return json(['code' => 1, 'msg' => '', 'count' => 1, 'data' => $list]);
        }

        $modelHelper
            ->addTips('1. 只会导入拥有管理权限的组织的党员<br>')
            ->addTips('2. 手机号已存在的不会导入<br>')
            ->addTips('3. 上传的 Excel 格式请务必和示例 Excel 里的格式一样')
            ->addTopBtn('从 Excel 导入', url('upExcel'))
            ->addTopBtn('示例 Excel 下载', '/sys/user.xlsx')
            ->addField('id', 'id', 'text', ['hide' => true])
            ->addField('文件名', 'file_name', 'text', ['hide' => true])
            ->addRowBtn('导入', url('importExcel'))
            ->addRowBtn('删除', url('deleteExcel'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 200,
                'hide' => true,
            ]);
        return $modelHelper->setPage(false)->showList();
    }

    /**
     * @return \think\response\View
     * 上传并导入用户
     */
    public function upExcel()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = $this->doImportExcel($this->app->getRootPath() . DIRECTORY_SEPARATOR . 'public' . $data['excel']);
            $msg = '导入';
            if ($r) {
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
                    $auth = \app\common\model\Department::getSubDep($dep_admin);
                    $dep_auth = [];
                    foreach ($auth as $k => $v) {
                        $dep_auth[$v] = 1;
                    }
                    session('dep_auth', $dep_auth);
                }
                $this->success($msg . '成功');
            } else {
                $this->error($msg . '失败');
            }
        }
        $modelHelper = new ModelHelper();
        return $modelHelper->addField('用户列表文件', 'excel', 'file', ['placeholder' => '请选择Excel文件'])->showForm();
    }

    private function doImportExcel($file)
    {
        $dep_auth = array_keys(session('dep_auth'));
        $arr = import_excel($file);
        if (!is_array($arr)) {
            $this->error('数据读取失败，请检查表格');
        }
        Db::startTrans();
        $jump = [];
        $succ = 0;
        foreach ($arr as $k => $v) {
            if (!is_numeric(trim($v[0])) || empty(trim($v[12]))) {
                $jump[$k] = $v[0];
                continue;
            }
            $nickname = trim($v[1]);//姓名
            $dep_name = trim($v[2]);//组织
            $idcard = trim($v[3]);//身份证
            $sex = trim($v[4]);//性别
            $nation = trim($v[5]);//民族
            $birthday = trim($v[6]);//出生日期
            if (is_numeric($birthday)) {
                $birthday = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($birthday));
            }
            $education = trim($v[7]);//学历
            $people_type = trim($v[8]);//人员类别
            $join_time = trim($v[9]);// 加入党组织日期
            if (is_numeric($join_time)) {
                $join_time = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($join_time));
            }
            $become_time = trim($v[10]);//转为正式党员日期
            if (is_numeric($become_time)) {
                $become_time = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($become_time));
            }
            $job = trim($v[11]);//工作岗位
            $mobile = trim($v[12]);//手机号码
            trim($v[13]);//固定电话
            $party_status = trim($v[14]);//党籍状态
            $lost_contact = trim($v[15]);//是否失联党员
            $lost_contact_time = trim($v[16]);//失联日期
            if (is_numeric($lost_contact_time)) {
                $lost_contact_time = gmdate('Y-m-d', \PHPExcel_Shared_Date::ExcelToPHP($lost_contact_time));
            }

            $has_user = UserModel::where('mobile', $mobile)->find();
            if (!empty($has_user)) {
                $jump[$k] = $v[0];
                continue;
            }

            //部门
            $dep_id = \app\common\model\Department::where('name', $dep_name)->cache(60)->value('id');
            if (!$dep_id) {
                Db::rollback();
                $this->error($dep_name . '不存在，请先在组织信息中添加');
            }
            if (!in_array($dep_id, $dep_auth)) {
                $jump[$k] = $v[0];
                continue;
            }

            $nation = explode(' ', $nation);
            $education = explode(' ', $education);
            $job = explode(' ', $job);
            $user = [
                'nickname' => $nickname,
                'idcard' => $idcard,
                'sex' => DicData::text2value('sex', $sex),
                'nation' => $nation[0],
                'birthday' => $birthday,
                'education' => $education[0],
                'people_type' => DicData::text2value('people_type', $people_type),
                'join_time' => $join_time,
                'become_time' => $become_time,
                'job' => $job[0],
                'mobile' => $mobile,
                'party_status' => $party_status == '正常' ? 1 : 0,
                'lost_contact' => $lost_contact == '是' ? 1 : 0,
                'lost_contact_time' => $lost_contact == '是' ? $lost_contact_time : null,
                'status' => 1,
                'password' => $mobile,
            ];
            $User = new UserModel();
            $User->save($user);

            $row = [
                'user_id' => $User->id,
                'dep_id' => $dep_id,
                'level_id' => 0,
                'add_time' => time(),
            ];
            UserDep::create($row);
            $succ++;
        }
        Db::commit();
        return true;
    }
}