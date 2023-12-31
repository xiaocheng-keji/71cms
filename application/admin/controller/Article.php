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
use app\common\model\Article as model;
use think\Queue;

/**
 * 文章管理
 * Class Article
 * @package app\admin\controller
 */
class Article extends AdminBase
{
    /**
     * 列表
     *
     * @return \think\response\Json|\think\response\View
     */
    public function list()
    {
        $cat_select = cat_select([1]);
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('list test')
            ->addSearchField('是否主页推荐', 'recommend', 'select', ['options' => ['是否推荐', '推荐'], 'exp' => '>=','value'=>input('recommend',0)])
            ->addSearchField('文章状态', 'status', 'select', ['options' => ['全部','待发布', '已发布'], 'exp' => '=','value'=>input('status',0)])
            ->addSearchField('标题', 'title', 'text', ['exp' => 'LIKE','value' => input('title', '')])
            ->addSearchField('栏目', 'cat_id', 'select', ['options' => $cat_select,'value' => input('cat_id', 0)]);

        if (\think\facade\Request::isAjax()) {
            $where = $modelHelper->getSearchWhere();
            foreach ($where as $k => $v) {
                if ($v[0] == 'cat_id' && $v[2] == 0) {
                    unset($where[$k]);
                }
                if ($v[0] == 'status') {
                    if($v[2] == 0){
                        unset($where[$k]);
                    }else{
                        $where[$k][2] = $v[2]-1;
                    }
                }
            }
            $article = new model();
            $count = $article::with('category')
                ->alias('a')
                ->where($where)
                ->group('a.id')
                ->count();
            $list = $article::with(['category'])
                ->alias('a')
                ->where($where)
                ->order('a.sort asc, a.id desc')
                ->field('a.*')
                ->group('a.id')
                ->page(input('page', 1), input('limit', 10))
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['cat_id'] = $v['category']['cat_name'];
            }
            return json(['code' => 1, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        $modelHelper
            ->addTips('列表[主页推荐]单击进入编辑模式,失去焦点可进行自动保存，1-10推荐到轮播图，大于10推荐到首页新闻资讯')
            ->addTips('置顶排序规则：0是不推荐，小的在前面')
            ->addTopBtn('添加文章', url('edit'))
            ->addField('ID', 'id', 'text', ['width' => 80, 'align' => 'center', 'sort' => false])
            ->addField('标题', 'title', 'text')
            ->addField('栏目', 'cat_id', 'select', ['width' => 120])
            ->addField('主页推荐', 'recommend', 'text', ['edit' => 'text', 'width' => 100])
//            ->addField('置顶排序', 'sort', 'text', ['edit' => 'text'])
            ->addField('添加时间', 'create_time', 'text', ['width' => 160])
            ->addField('状态', 'status', 'text', [
                'width' => 120,
                'templet' => '<div>
                    {{#  if(d.status==1){ }}
                    <li style="color: green;">
                      已发布
                    </li>
                    {{#  } else { }}
                    <li style="color: red">
                      待发布
                    </li>
                    {{#  } }}
                </div>'])
            //status=0时显示的按钮
            ->addRowBtn('发布', url('updateField',['type'=>'publish']), 'barDemo', ['field' => 'status', 'operator' => '\<', 'value' => '1'])
            ->addRowBtn('编辑', url('edit'), 'barDemo', [], 'btn-warm')
            ->addRowBtn('删除', url('delete'), 'barDemo', [], 'btn-danger')
            //status=1时显示的按钮
            ->addRowBtn('下架', url('updateField',['type'=>'hidden']), 'barDemo', ['field' => 'status', 'operator' => '==', 'value' => '1'], 'btn-danger')
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo',
                'width' => 240
            ]);
        return $modelHelper->showList();
    }

    /**
     * 编辑 添加
     *
     * @param string $id
     * @return \think\response\View
     */
    public function edit(string $id = '')
    {
        $article = new model();
        $default_img = '';
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!empty($data['create_time'])) {
                $data['create_time'] = strtotime($data['create_time']);
            }
            $validate = $this->validate($data, 'article');
            if ($validate !== true) return json(['code' => -1, 'msg' => $validate]);
            if (empty($data['thumb'])) {
                $data['thumb'] = $default_img;
            }
            if ($id) {
                unset($data['id']);
                $r = $article->allowField(true)->save($data, ['id' => $id]);
            } else {
                $r = $article->allowField(true)->save($data);
                //通知公告栏目，推送消息
                if($data['cat_id'] == 68){
                    $data['id'] = $article->id;
                    $data['time'] = date('Y-m-d H:i:s');
                    //给所有党员发送消息
                    $userList = \app\common\model\User::where('status', 1)->column('id');
                    //异步 队列发送
                    $message = new \app\notify\Message(\app\notify\Message::TYPE_NOTICE, $data, $userList);
                    $isPushed = Queue::push(\app\job\Message::class, $message->toArray(), \app\job\Message::QUEUE_NAME);
                    if ($isPushed === false) {
                        //加入队列失败
                        \think\facade\Log::write($data, 'queue_push_fail');
                    }
                }
            }
            $msg = '保存';
            if ($r) {
                $this->success($msg . '成功');
            } else {
                $this->error($msg . '失败');
            }
        }
        $item = [];
        if ($id) {
            $item = $article::with(['category'])->get($id);
            if (empty($item)) {
                $this->error('item error');
            }
            $item['content'] = htmlspecialchars_decode($item['content']);
        } else {
            $item['thumb'] = $default_img;
            $item['create_time'] = date('Y-m-d H:i:s');
        }
        $cat_select = cat_select([1]);
        $cat_select[0]['disabled'] = true;
        $modelHelper = new ModelHelper();
        $modelHelper->style = <<<EOT
<style>
.layui-form-select dl {
    z-index: 1000;
}
</style>

EOT;
        $modelHelper
            ->setTitle($id ? '编辑文章' : '添加文章')
            ->addField('标题', 'title', 'text', ['require' => '*', 'attr' => 'style="width:370px"'])
            ->addField('作者', 'author', 'text')
            ->addField('栏目', 'cat_id', 'select', ['require' => '*', 'options' => $cat_select])
            ->addField('封面图', 'thumb', 'image', ['tip' => '推荐图片大小300*150，宽高比例2:1'])
            ->addField('描述', 'description', 'textarea')
//            ->addField('附件', 'attachment', 'file')
            ->addField('内容', 'content', 'ueditor', ['require' => '*'])
            ->addField('获得积分', 'point', 'number', ['tip' => '浏览大于2分钟获取此积分'])
            ->addField('主页推荐', 'recommend', 'number')
//            ->addField('置顶排序', 'sort', 'number', ['placeholder' => 50])
            ->addField('发布时间', 'create_time', 'text')
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 删除
     *
     * @param string $id
     */
    public function delete(string $id)
    {
        $item = model::get($id);
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
        $model = new model();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if($data['field']){
                if ($data['id']) {
                    $r = $model->allowField('recommend')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
                }
            }else{
                $r = $model->allowField('status')->save(['status' => $data['status']==1?0:1], ['id' => $data['id']]);
            }
            if ($r) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }
}