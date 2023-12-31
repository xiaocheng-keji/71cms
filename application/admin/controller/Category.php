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
use app\common\model\Category as CategoryModel;
use GuzzleHttp\Client;
use QL\Ext\PhantomJs;
use QL\QueryList;
use think\facade\Cache;
use util\Tree;
use app\common\model\Article as ArticleModel;
use app\common\model\Meeting as MeetingModel;

/**
 * 栏目管理
 * Class Category
 * @package app\admin\controller
 */
class Category extends AdminBase
{
    protected $category_model;

    protected function initialize()
    {
        parent::initialize();
        $this->category_model = new CategoryModel();
    }

    /**
     * 栏目管理
     * @return mixed
     */
    public function index()
    {
        $modelHelper = new ModelHelper();
        if (\think\facade\Request::isAjax()) {
            $admin_menu_list = $this->category_model->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
            $list = [];
            foreach ($admin_menu_list as $k => $v) {
                $v['type'] = CategoryModel::TYPE_ARRAY[$v['type']];
                $list[$v['id']] = $v;
            }
            $Trees = new Tree();
            $Trees->tree($list, 'id', 'parent_id', 'cat_name');
            $admin_menu_level_list = $Trees->getArray();
            unset($admin_menu_level_list[0]);
            $list = array_values($admin_menu_level_list);
            return json(['code' => 1, 'msg' => '', 'count' => count($list), 'data' => $list]);
        }
        $modelHelper
            ->addTopBtn('添加栏目', url('add'))
            ->addTips('点击排序的序号可编辑排序，数字越小排序越前')
            ->addField('ID', 'id', 'text', ['width' => 70, 'align' => 'center', 'sort' => false])
            ->addField('名称', 'cat_name', 'text', ['width' => 250])
            ->addField('类型', 'type', 'text', ['width' => 250])
            ->addField('排序', 'sort', 'text', ['edit' => 'text', 'width' => 100])
            ->addRowBtnEx('显示|隐藏', url('updateStatus'), ['htmlID' => 'barDemo1', 'type' => 'checkbox', 'id' => 'id', 'name' => 'status', 'opt' => ['field' => 'status', 'operator' => '==', 'value' => 1]])
            ->addField('是否显示', 'toolbar', 'toolbar', [
                'width' => 120,
                'fixed' => 'right',
                'toolbar' => '#barDemo1'
            ])
            ->addRowBtn('添加子栏目', url('add'), 'barDemo', ['field' => 'type', 'operator' => '==', 'value' => "'父级栏目'"], 'btn-warm', 'btn')
//            ->addRowBtn('数据抓取', url('spider_list'))
            ->addRowBtn('编辑', url('edit'))
            ->addRowBtn('删除', url('delete'))
            ->addField('操作', 'toolbar', 'toolbar', [
                'fixed' => 'right',
                'toolbar' => '#barDemo'
            ]);
        return $modelHelper->setPage(false)->showList();
    }

    /**
     * 添加栏目
     *
     * @param string $pid
     * @return mixed
     */
    public function add($id = '', $pid = '')
    {
        //保存或增加
        if (request()->isAjax()) {
            $data = input('post.');
            $validate = $this->validate($data, 'category');
            if ($validate !== true) return json(['code' => -1, 'msg' => $validate]);
            $res = $this->category_model->allowField(['cat_name', 'parent_id', 'image', 'type', 'tenant_id', 'status'])->save($data);
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        $item['parent_id'] = $id;
        $options = cat_options(true, 0, $id);
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('添加栏目')
            ->addField('栏目名称', 'cat_name', 'text', ['require' => '*'])
            ->addField('父级栏目', 'parent_id', 'select', ['require' => '*', 'options' => $options])
            ->addField('栏目类型', 'type', 'radio', ['require' => '*', 'options' => CategoryModel::TYPE_ARRAY])
            ->addField('栏目图片', 'image', 'image')
            ->addField('是否显示', 'status', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 编辑栏目
     *
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        //保存或增加
        if (request()->isAjax()) {
            $data = input('post.');
            $validate = $this->validate($data, 'category');
            if ($validate !== true) return json(['code' => -1, 'msg' => $validate]);
            //如果修改了栏目类型 判断栏目下是否有内容
            $item = CategoryModel::where('id', $id)->find();
            if ($item['type'] != $data['type']) {
                if ($item['type'] == CategoryModel::TYPE_ARTICLE) {
                    //文章
                    $exist = ArticleModel::where('cat_id', $id)->find();
                    if ($exist) {
                        $this->error('栏目下还存在文章，不能修改栏目类型');
                    }
                } elseif ($item['type'] == CategoryModel::TYPE_MEETING) {
                    //会议
                    $exist = MeetingModel::where('cat_id', $id)->where('deleted', 0)->find();
                    if ($exist) {
                        $this->error('栏目下还存在会议，不能修改栏目类型');
                    }
                } elseif ($item['type'] == CategoryModel::TYPE_ACTIVITY) {
                    //活动
                    $exist = MeetingModel::where('cat_id', $id)->where('deleted', 0)->find();
                    if ($exist) {
                        $this->error('栏目下还存在活动，不能修改栏目类型');
                    }
                }
            }
            $res = $this->category_model->allowField(['cat_name', 'parent_id', 'image', 'type', 'spider_url', 'status'])->save($data, ['id' => $id]);
            if ($res) {
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }
        $item = $this->category_model->find($id);
        $options = cat_options(true, 0, $pid);
        $modelHelper = new ModelHelper();
        $modelHelper
            ->setTitle('编辑栏目')
            ->addField('栏目名称', 'cat_name', 'text', ['require' => '*'])
            ->addField('父级栏目', 'parent_id', 'select', ['require' => '*', 'options' => $options])
            ->addField('栏目类型', 'type', 'radio', ['require' => '*', 'options' => CategoryModel::TYPE_ARRAY])
            ->addField('栏目图片', 'image', 'image')
            ->addField('数据地址', 'spider_url', 'text')
            ->addField('是否显示', 'status', 'radio', ['require' => '*', 'options' => [1 => '是', 0 => '否']])
            ->addField('ID', 'id', 'hidden')
            ->setData($item);
        return $modelHelper->showForm();
    }

    /**
     * 更新栏目
     *
     * @param $id
     */
    public function update($id)
    {
        if (!$this->request->isPost()) {
            return false;
        }
        $data = $this->request->param();
        $validate_result = $this->validate($data, 'Category');
        if ($validate_result !== true) {
            $this->error($validate_result);
        } else {
            $children = $this->category_model->where(['path' => ['like', "%,{$id},%"]])->column('id');
            if (in_array($data['parent_id'], $children)) {
                $this->error('不能移动到自己的子分类');
            } else {
                if ($this->category_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除栏目
     * @param $id
     */
    public function delete($id)
    {
        $category = $this->category_model->where(['parent_id' => $id])->find();
        if (!empty($category)) {
            $this->error('此分类下存在子分类，不可删除');
        }
        $article = ArticleModel::where(['cat_id' => $id])->find();
        if (!empty($article)) {
            $this->error('此分类下存在文章，不可删除');
        }
        if ($this->category_model->where('id', $id)->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @param $id
     * @param $status
     * @return \think\response\Json
     */
    public function updatestatus($id, $status)
    {
        if ($this->request->isGet()) {
            if ($this->category_model->where('id', $id)->update(['status' => $status]) !== false) {
                return json(array('code' => 1, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * @param $id
     * @param $sid
     * @return \think\response\Json
     */
    public function updatesid($id, $sid)
    {
        if ($this->request->isGet()) {
            if ($this->category_model->where('id', $id)->update(['sid' => $sid]) !== false) {
                return json(array('code' => 1, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    public function updateField()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                $r = $this->category_model->allowField('sort')->save([$data['field'] => $data['value']], ['id' => $data['id']]);
            }
            if ($r) {
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 爬取列表
     * @param $id
     * @param int $num
     */
    public function spider_list($id, $num = 0)
    {
        $category = CategoryModel::where('id', $id)->find();
        if (!$category['spider_url']) {
            $this->error('没有数据地址');
        }
        $urls = Cache::get($category['spider_url']);
        if (!$urls) {
            $client = new Client();
            $res = $client->request('GET', $category['spider_url']);
            $urls = (string)$res->getBody();
            $urls = json_decode($urls, true);
            Cache::set($category['spider_url'], $urls, 3600);
        }

        $ql = QueryList::getInstance();
        // 安装时需要设置PhantomJS二进制文件路径
        //$ql->use(PhantomJs::class,'/usr/local/bin/phantomjs');
        ////or Custom function name
        //$ql->use(PhantomJs::class,'/usr/local/bin/phantomjs','browser');

        // Windows下示例
        // 注意：路径里面不能有空格中文之类的
        $ql->use(PhantomJs::class, 'E:/software/phantomjs-2.1.1-windows/bin/phantomjs.exe');

        $url_num = count($urls);
        if ($url_num == $num) {
            $this->success('全部获取完成', url('index'));
        }
        for ($i = $num; $i < $url_num; $i++) {
            $url = $urls[$i]['url'];
            $art = \app\common\model\Article::where('from_url', $url)->find();
            if ($art) {
                continue;
            }
            $ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($url) {
                $r->setMethod('GET');
                $r->setUrl($url);
                $r->setTimeout(10000); // 10 seconds
                $r->setDelay(0); // 3 seconds
                return $r;
            });
            $rt = [];
            $rt['title'] = $ql->find('.render-detail-title')->text();
            if ($rt['title']) {
                $rt['publish_time'] = $ql->find('.render-detail-time')->text();
                $rt['resource'] = $ql->find('.render-detail-resource')->text();
                $rt['author'] = $ql->find('.render-detail-creator-name')->text();
                $rt['content'] = $ql->find('.render-detail-content')->html();
                $rt['editors_name'] = $ql->find('.render-detail-editors-name')->text();

                $rt['publish_time'] = strtotime($rt['publish_time']);
                $rt['author'] = str_replace('作者：', '', $rt['author']);
                $rt['resource'] = str_replace('来源：', '', $rt['resource']);
                $rt['editors_name'] = str_replace('责任编辑：', '', $rt['editors_name']);
                $rt['from_url'] = $url;
                $rt['cat_id'] = $category['id'];
                \app\common\model\Article::create($rt);
            }
            $this->success('(' . $num . '/' . $url_num . ')' . $rt['title'] . '获取成功，开始获取下一条', url('spider_list', ['id' => $id, 'num' => $i]), '', 0);
        }
    }
}