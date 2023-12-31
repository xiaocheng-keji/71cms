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

use app\common\model\Forum as ForumModel;
use app\common\model\Forumcate as ForumcateModel;

/**
 * 论坛栏目管理
 * Class Category
 * @package app\admin\controller
 */
class Forumcate extends AdminBase
{

    protected $category_model;
    protected $article_model;

    protected function initialize()
    {
        parent::initialize();
        $this->category_model = new ForumcateModel();
        $this->article_model = new ForumModel();

        $category_level_list = $this->category_model->catetree();
        $this->assign('category_level_list', $category_level_list);
    }

    /**
     * 栏目管理
     *
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 添加栏目
     *
     * @param string $pid
     * @return mixed
     */
    public function add($pid = '')
    {
        return $this->fetch('add');
    }

    /**
     * 保存栏目
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Forumcate');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
                //$this->error($validate_result);
            } else {
                if ($this->category_model->allowField(true)->save($data)) {
                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }
    }

    /**
     * 编辑栏目
     *
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $category = $this->category_model->find($id);
        return $this->fetch('edit', ['tptc' => $category]);
    }

    /**
     * 更新栏目
     *
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Forumcate');

            if ($validate_result !== true) {
                return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                $children = $this->category_model->getchilrenid($id);
                if (!empty($children) && in_array($data['pid'], $children)) {
                    // $this->error('不能移动到自己的子分类');
                    return json(array('code' => 0, 'msg' => '不能移动到自己的子分类'));
                } else {
                    if ($this->category_model->allowField(true)->save($data, $id) !== false) {
                        return json(array('code' => 200, 'msg' => '更新成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '更新失败'));
                    }
                }
            }
        }
    }

    public function updatestatus($id, $status, $name)
    {
        if ($this->request->isGet()) {


            if ($this->category_model->where('id', $id)->update([$name => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }


    /**
     * 删除栏目
     *
     * @param $id
     */
    public function delete($id)
    {
        $category = $this->category_model->where(['tid' => $id])->find();
        $article = $this->article_model->where(['tid' => $id])->find();

        if (!empty($category)) {
            return json(array('code' => 0, 'msg' => '此分类下存在子分类，不可删除'));
        }
        if (!empty($article)) {
            return json(array('code' => 0, 'msg' => '此分类下存在文章或帖子，不可删除'));
        }
        if ($this->category_model->destroy($id)) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
}