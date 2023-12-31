<?php
/**
 * 71CMS [ 创先云智慧党建系统 ]
 * =========================================================
 * Copyright (c) 2018-2023 南宁小橙科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.71cms.net
 * 这不是一个自由软件！未经许可不能去掉71CMS相关版权。
 * 禁止对系统程序代码以任何目的，任何形式的再发布。
 * =========================================================
 */
namespace app\admin\controller;


use app\admin\model\CourseComplete;
use app\admin\model\TypeToUser;
use app\admin\model\UserCourse;
use app\admin\model\Video;
use app\admin\model\VideoType;
use app\admin\controller\AdminBase;
use app\common\controller\ModelHelper;
use app\common\model\UserDep;
use think\Db;
use think\Exception;
use think\facade\Cache;
class Paid extends AdminBase
{
    /**
     * 列表
     * @return \think\response\View
     */
    public function video_type($keyword = '', $page = '')
    {
        $modelHelper = new ModelHelper();
        $modelHelper->addSearchField('名称', 'typename', 'text', ['exp' => 'LIKE'])
            ->addTopBtn('添加课程', url('video_type_add'));


        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $count = VideoType::where($where)->order('group,pid,sort')->count();
            $list = VideoType::where($where)->order('group,pid,sort')->page(input('page', 1), input('limit', 10))->select();

            foreach($list as $k=>$v){
                if($v['level']==2){
                    $list[$k]['typename'] = "&nbsp;&nbsp;&nbsp;└─ {$v['typename']}";//&nbsp;&nbsp;&nbsp;└─
                }
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper->addField('ID', 'id', '', ['width' => 80])
            ->addField('名称', 'typename')
            ->addRowBtnEx('开启|关闭', url('type_change_update'), ['htmlID' => 'status_tool', 'id' => 'id', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => '1'], 'type' => 'checkbox'])
            ->addField('状态', 'toolbar', 'toolbar', ['toolbar' => '#status_tool'])
            ->addRowBtn('添加子类', url('video_type_add'), 'barDemo', ['field' => 'level', 'operator' => '==', 'value' => '1', 'ext_param' => ['pid' => 'id', 'id' => 'id']])
            ->addRowBtn('添加用户', url('target_user'), 'barDemo', ['field' => 'level', 'operator' => '==', 'value' => '2', 'ext_param' => ['cid' => 'id']], 'layui-btn-warm')
            ->addRowBtn('编辑', url('video_type_edit'), 'barDemo', ['field' => 'id', 'operator' => '>=', 'value' => '1'])
            ->addRowBtn('删除', url('video_type_delete'), 'barDemo', ['field' => 'id', 'operator' => '>=', 'value' => '1'], 'layui-btn-danger')
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $modelHelper->showList();
    }


    public function type_change_update($status)
    {
        $res = VideoType::where(['id'=>input('id')])->update(['status'=>$status]);
        if ($res !== false) {
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    /**
     * 添加页面
     * @return \think\response\View
     */
    public function video_type_add($pid = 0)
    {
        $type = VideoType::where([['status','>','0']])->order('group,pid,sort')->select();
        $this->assign('type', $type);
        $this->assign('pid', $pid);
        return view();
    }

    /**
     * 添加操作
     * @return \think\response\Json
     */
    public function type_save()
    {
        $post = input('post.');
        $validate = $this->validate($post, 'VideoType');

        if ($validate !== true) {
            return json(['code' => 0, 'msg' => $validate]);
        }
        Db::startTrans();
        $res = VideoType::create($post);
        if ($post['pid'] == 0) {
            $res2 = VideoType::where(['id'=>$res->id])->update(['group'=>$res->id,'level'=>1]);
        } else {
            $res2 = VideoType::where(['id'=>$res->id])->update(['group'=>$post['pid'],'level'=>2]);
        }
        if ($res && $res2) {
            Db::commit();
            return json(['code' => 200, 'msg' => '添加成功']);
        } else {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败']);
        }
    }


    /**
     * 分类修改页面
     * @param $id
     * @return \think\response\View
     */
    public function video_type_edit($id)
    {
        $res = VideoType::get($id);
        $type = VideoType::where([['status','>','0']])->order('group,pid,sort')->select();
        $this->assign('type', $type);
        $this->assign('res', $res);
        return view();
    }


    /**
     * 分类修改操作
     * @return \think\response\Json
     */
    public function type_update()
    {
        $post = input('post.');
        $validate = $this->validate($post, 'VideoType');
        if ($validate !== true) {
            return json(['code' => 0, 'msg' => $validate]);
        }
        Db::startTrans();
        $res = VideoType::update($post);


        if ($post['pid'] == 0) {
            $res2 = VideoType::update(['group' => $post['id'], 'level' => 1],['id'=>$post['id']]);

        } else {
            $res2 = VideoType::update(['group' => $post['pid'], 'level' => 2],['id'=>$post['id']]);
        }
        if ($res&&$res2) {
            Db::commit();
            return json(['code' => 200, 'msg' => '更新成功']);
        } else {
            Db::rollback();
            return json(['code' => 0, 'msg' => '更新失败']);
        }
    }

    /**
     * 单个删除
     * @param $id
     * @return \think\response\Json
     */
    public function video_type_delete($id)
    {
        $video = Video::where(['tid|cid'=>$id])->find();
        $type = VideoType::where(['pid'=>$id])->find();
        if ($video || $type) {
            return json(['code' => 0, 'msg' => '该分类下有内容，不能删除', 'wait' => 2]);
        }
        $res = VideoType::destroy($id);
        if ($res) {
            return json(['code' => 1, 'msg' => '删除成功', 'wait' => 2]);
        } else {
            return json(['code' => 0, 'msg' => '删除失败', 'wait' => 2]);
        }
    }


    /**
     * 批量删除
     * @param $delid
     * @return \think\response\Json
     */
//    public function video_type_alldelete($delid)
//    {
//        $map1 = [
//            ['tid', 'in', $delid]
//        ];
//        $map2 = [
//            ['cid', 'in', $delid]
//        ];
//        $video = Db::name('video')->whereOr([$map1, $map2])->select();
//
//        $type = Db::name('video_type')->whereIn('pid', $delid) ->select();
//
//        if ($video || $type) {
//            return json(['code' => 0, 'msg' => '有分类下有内容,不能删除']);
//        }
////        dump($video);
////        dump($video);die;
//
//        $res = Db::name('video_type')->delete($delid);
//        if ($res) {
//            return json(['code' => 200, 'msg' => '删除成功']);
//        } else {
//            return json(['code' => 0, 'msg' => '删除失败,该分类可能已删除']);
//        }
//    }


    /**
     * 视频列表
     * @param string $keyword
     * @param string $page
     * @return \think\response\View
     */
    public function video_list($keyword = '', $page = '')
    {
        $modelHelper = new ModelHelper();

        $modelHelper->addTopBtn('添加课程', url('video_add'))
            ->addSearchField('标题', 'vname', 'text', ['exp' => 'LIKE']);
        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            $count = Video::useGlobalScope(false)->withJoin(['videoTypeCid'=>function($query){
                $query->where([]);
            }])->order('tid,cid,video.sort')
                ->where($where)
                ->count();

            $list = Video::useGlobalScope(false)->withJoin(['videoTypeCid'=>function($query){
                $query->where([]);
            }])->order('tid,cid,sort')
                ->where($where)->page(input('page', 1), input('limit', 10))
                ->select();

            foreach ($list as $k => $v) {
                $type_1 = VideoType::where(['id'=>$v->video_type_cid->pid])->value('typename');
                $list[$k]['typename'] = $type_1 . '--' . $v->video_type_cid->typename;
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper->addField('ID', 'id', '', ['width' => 80])
            ->addField('课程', 'typename')
            ->addField('标题', 'vname')
            ->addField('时长(秒)','duration')
            ->addField('积分', 'integral')
            ->addRowBtn('编辑', url('video_edit'), 'action')
            ->addRowBtn('删除', url('video_delete'), 'action')
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#action'
            ]);
        return $modelHelper->setPage(true)->showList();
    }


    /**
     * 视频添加页面
     * @return \think\response\View
     */
    public function video_add()
    {
        $config = getConfig();
        if($config['qiniu']){
            $auth = new \Qiniu\Auth($config['access_key'], $config['secret_key']);
            $token = $auth->uploadToken($config['bucket']);
            $this->assign('url',$config['qiniu_url']);
        }

        $type = VideoType::where(['status' => 1, 'pid' => 0])->select()->toArray();
        if(empty($type)){
            $type2 = [];
        }else{
            $type2 = VideoType::where([['status', '=', 1], ['pid', '=', $type[0]['id']]])->select();
        }
        $this->assign('type2', $type2);
        $this->assign('type', $type);
        $this->assign('token', $token);
        return view();
    }

    /**
     * 视频添加
     * @return \think\response\Json
     */
    public function video_save()
    {
        $data = input('post.');
        $data['uid'] = session('admin_id');
        $validate = $this->validate($data, 'Video');
        if ($validate !== true) {
            return json(['code' => 0, 'msg' => $validate]);
        }
        $data['time'] = date('Y-m-d H:i:s');
        $json = json_decode($this->getDuration($data['src']),true);
        if(isset($json['streams'])){
            $data['duration'] = $json['streams'][0]['duration'];
        }
        Db::startTrans();
        $res = Video::create($data);
        $res2 = $this->checkComplete($res);

        if($res&&$res2) {
            Db::commit();
            return json(['code' => 200, 'msg' => '添加成功']);
        } else {
            Db::rollback();
            return json(['code' => 0, 'msg' => '添加失败']);
        }
    }



    /**
     * 视频修改页面
     * @param $id
     * @return \think\response\View
     */
    public function video_edit($id)
    {
        $res = Video::get($id);
        $config = getConfig();
        if($config['qiniu']){
            $auth = new \Qiniu\Auth($config['access_key'], $config['secret_key']);
            $token = $auth->uploadToken($config['bucket']);
            $this->assign('url',$config['qiniu_url']);
        }

        $type = VideoType::where(['status'=>1,'pid'=>0])->select();
        $type2 = VideoType::where([['status', '=', 1], ['pid', '=', $res['tid']]])->select();
        $this->assign('type', $type);
        $this->assign('type2', $type2);
        $this->assign('token', $token);
        //富文本内容处理
        $res['vcontent'] = htmlspecialchars_decode($res['vcontent']);
        $this->assign('res', $res);
        return view();
    }


    /**
     * 视频修改
     * @return \think\response\Json
     */
    public function video_update()
    {
        $data = input('post.');
        $validate = $this->validate($data, 'Video');
        if ($validate !== true) {
            return json(['code' => 0, 'msg' => $validate]);

        }
        $data['time'] = date('Y-m-d H:i:s');

        $this->checkComplete($data,'edit');

        $res = Video::update($data);

        if ($res) {
            return json(['code' => 200, 'msg' => '编辑成功']);
        } else {
            return json(['code' => 0, 'msg' => '编辑失败']);
        }
    }



    public function checkComplete($data,$type='add'){
        try{
            if($type=='add'){
                $courses = CourseComplete::where(['cid'=>$data->cid])->group('uid')->column('uid');
                if(count($courses)>0){
                    $add = [];
                    foreach($courses as $k=>$v){
                        $add[] = ['uid'=>$v,'vid'=>$data->id,'cid'=>$data->cid,'time'=>$data->time,'duration'=>$data['duration']];
                    }
                    (new CourseComplete)->saveAll($add);
                }
                $uids = UserCourse::where(['cid'=>$data->cid,'status'=>1]) -> select();
                if(count($uids)>0){
                    foreach($uids as $k=>$v){
                        UserCourse::where(['uid'=>$v['uid'],'cid'=>$v['cid']])->update(['complete_time'=>null,'status'=>0]);
                    }
                }
            }else{
                CourseComplete::where(['status'=>0,'vid'=>$data['id']])->update(['duration'=>$data['duration']]);
            }
            return true;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * 获取视频时长
     * @param $path
     */
    public function getDuration($path){
//       return file_get_contents(config('web.QINIU').$path.'?avinfo');
        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, getConfig()['qiniu_url'].$path.'?avinfo');
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $output contains the output string
        $output = curl_exec($ch);
        //echo output
        curl_close($ch);
        return $output;
    }

    /**
     * 单个视频删除
     * @param $id
     * @return \think\response\Json
     */
    public function video_delete($id)
    {
//        $video = Video::get($id);
//        $qninfo = Qn::where(['status' => 1])->find();
//        $auth = new \Qiniu\Auth($qninfo['access_key'], $qninfo['secret_key']);
//        $config = new \Qiniu\Config();
//        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
//        $bucketManager->delete($qninfo['bucket'], $video['vimg']);
//        $bucketManager->delete($qninfo['bucket'], $video['src']);

        $res = Video::destroy($id);

        $res2 = $this->deleteComplete($id);
        if ($res&&$res2) {
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }

    /**
     * 多个删除视频
     * @param $delid
     * @return \think\response\Json
     */
//    public function video_alldelete($delid)
//    {
//        $qninfo = Db::name('qn')->where(['status' => 1])->find();
//        $auth = new \Qiniu\Auth($qninfo['access_key'], $qninfo['secret_key']);
//        $config = new \Qiniu\Config();
//        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
//
//        foreach ($delid as $k => $v) {
//            $video = Db::name('video')->find($v);
//            $bucketManager->delete($qninfo['bucket'], $video['vimg']);
//            $bucketManager->delete($qninfo['bucket'], $video['src']);
//        }
//        $res = Db::name('video')->delete($delid);
//        $this->deleteAllComplete($delid);
//        if ($res) {
//            return json(['code' => 200, 'msg' => '删除成功']);
//        } else {
//            return json(['code' => 0, 'msg' => '删除失败']);
//        }
//    }


    public function deleteComplete($id){
        try{
            $cid = CourseComplete::where(['vid'=>$id])->group('cid')->value('cid');
            if($cid){
                CourseComplete::destroy([
                    'vid'=>$id
                ]);
                $user_complete = UserCourse::where(['cid'=>$cid,'status'=>0])->select();
                if($user_complete){
                    foreach($user_complete as $k=>$v){
                        $count = CourseComplete::where(['uid'=>$v['uid'],'cid'=>$v['vid'],'status'=>0]) -> count();
                        if($count<=0){
                            UserCourse::update(['status'=>1,'complete_time'=>date('Y-m-d H:i:s')],['cid'=>$v['cid'],'uid'=>$v['uid']]);
                        }
                    }
                }
            }
            return true;
        }catch (Exception $e){
            return false;
        }

    }


    public function deleteAllComplete($ids){
        $course_complete = CourseComplete::whereIn('vid',$ids)->group('vid')->column('vid');


        if($course_complete){
            CourseComplete::whereIn('vid',$ids)->delete();

            foreach($course_complete as $ck=>$cv){
                $user_complete = UserCourse::where(['cid'=>$cv,'status'=>0])->select();
                if($user_complete){
                    foreach($user_complete as $k=>$v){
                        $count = CourseComplete::where(['uid'=>$v['uid'],'cid'=>$v['vid'],'status'=>0])->count();
                        if($count<=0){
                            UserCourse::where(['cid'=>$v['cid'],'uid'=>$v['uid']])->update(['status'=>1,'complete_time'=>date('Y-m-d H:i:s')]);
                        }
                    }
                }
            }

        }
    }



    public function add_course($tid){
        $res = VideoType::where(['pid'=>$tid,'level'=>2])->select();
        if ($res) {
            return json(['code' => 200, 'msg' => '添加成功', 'data' => $res]);
        } else {
            return json(['code' => '添加失败']);
        }
    }

    /**
     * 目标用户弹窗界面
     * @param $cid
     * @return \think\response\View
     */
    public function target_user($cid)
    {
        $this->assign('cid', $cid);

        $user = TypeToUser::useGlobalScope(false)->withJoin(['user'=>function($query){
            $query->where([]);
        }])->where(['cid'=>$cid])->column('user.nickname','user.id');


        $group = TypeToUser::useGlobalScope(false)->withJoin(['department'=>function($query){
            $query->where([]);
        }])->where(['cid'=>$cid])->column('department.name nickname','department.id');

        $tag = array_merge($user, $group);
        $this->assign('tag', $tag);
        $this->assign('user', $user);
        $this->assign('group', $group);
        return view();
    }

    /**
     * 部门人员树形界面，现在不用
     * @return \think\response\Json
     */
    public function get_tree()
    {
        return json(['status' => ['code' => 200, 'message' => '成功获取'], 'data' => $this->get_tree_data()]);
    }


    /**
     * 获取树形界面的数据，现在不用
     * @param int $level
     * @param int $parent_id
     * @param int $max
     * @param int $cid
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function get_tree_data($level = 1, $parent_id = 0, $max = 0, $cid = 0)
    {
        if ($cid == 0) {
            $cid = input('cid');
        }
        if ($max == 0) {
            $max = Db::name('department')->max('layer');
        }
        $list = Db::name('department')->field('id,name,parent_id')->where(['layer' => $level, 'parent_id' => $parent_id])->select();
        $userDep = new UserDep();
        if ($level <= $max) {
            foreach ($list as $k => $v) {
                $deparment = $this->get_tree_data($level + 1, $v['id'], $max, $cid);

                $user_ids = $userDep->where('dep_id', '=', $v['id'])->column('user_id');

                $user = Db::name('user_dep')
                    ->alias('a')
                    ->field('b.id,b.nickname as name,c.id as parent_id')
                    ->join('user b', 'a.user_id=b.id')
                    ->join('department c', 'c.id=a.dep_id')
                    ->where('b.id', 'in', $user_ids)
                    ->select();


                foreach ($user as $kk => $vv) {
                    $check = Db::name('type_to_user')->where(['uid' => $vv['id'], 'cid' => $cid])->find();
                    if ($check) {
                        $user[$kk]['checkArr'] = ['type' => 0, 'checked' => 1];
                    } else {
                        $user[$kk]['checkArr'] = ['type' => 0, 'checked' => 0];
                    }

                }
                $list[$k]['children'] = array_merge($deparment, $user);
            }
        }
        return $list;
    }


    /**
     * 设置目标用户
     * @param $id
     * @param $checked
     * @param $cid
     */
    public function change_target()
    {
        Db::startTrans();
        $res1 = TypeToUser::where(['cid'=>input('cid')])->delete();
        $data = [];
        $data2 = [];

        if (input('uid')) {
            foreach (input('uid') as $k => $v) {
                $data[] = ['uid' => $v, 'cid' => input('cid')];
            }
            $flag1 = (new TypeToUser)->saveAll($data);
            if ($flag1 === false) {
                Db::rollback();
                jsonReturn(0, '加入用户ID时失败');
            }
        }

        if (input('gid')) {
            foreach (input('gid') as $k => $v) {
                $data2[] = ['cid' => input('cid'), 'gid' => $v];
            }
            $flag2 = (new TypeToUser)->saveAll($data2);
            if ($flag2 === false) {
                Db::rollback();
                jsonReturn(0, '加入部门信息时失败');
            }
        }

        if ($res1 !== false) {
            Db::commit();
            jsonReturn(1, '成功');
        } else {
            Db::rollback();
            jsonReturn(0, '操作失败');
        }
    }
}