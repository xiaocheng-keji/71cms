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

use app\common\model\DicData;
use app\common\model\DicType;
use app\admin\controller\AdminBase;
use think\Db;
use think\Request;

/**
 * 数据字典管理
 * Class Dic
 * @package app\admin\controller
 */
class Dic extends AdminBase
{
    /**
     * 首页、列表页
     *
     * @return \think\response\Json|\think\response\View
     */
    public function index(Request $request)
    {
        if ($this->request->isAjax()) {
            $map = [];
            if (input('type_id')) {
                $map['type_id'] = input('type_id');
            }

            $data = DicData::with('DicType')
                ->where($map)->order('sort desc,id desc')
                ->page(input('page'), input('limit'))
                ->select();
            $count = DicData::where($map)->count();

            return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $data]);
        } else {
            $dic_type = DicType::select();
            $this->assign('dic_type', $dic_type);

            $id = $request->param('id');
            $this->assign('id', $id);

            $tips = $request->param('tips');
            $this->assign('tips', $tips);

            return view();
        }
    }


    /**
     * 添加
     *
     * @return \think\response\View
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $data = input();

            $validata = new \app\admin\validate\DicData();
            if (!$validata->check($data)) {
                $this->error($validata->getError());
            }

            Db::startTrans();
            $res = DicData::create($data);

            if ($res) {
                Db::commit();
                $this->success('添加成功');
            } else {
                Db::rollback();
                $this->error('添加失败');
            }
        } else {
            $dic_type = DicType::where(['id' => input('type_id')])->find();
            $this->assign('dic_type', $dic_type);
            return view();
        }
    }

    /**
     * 修改
     *
     * @param $id
     * @return \think\response\View
     */
    public function edit($id = '')
    {
        if ($this->request->isAjax()) {
            $data = input();

            $validata = new \app\admin\validate\DicData();
            if (!$validata->check($data)) {
                $this->error($validata->getError());
            }

            $res = DicData::update($data, ['id' => $id]);
            if ($res) {
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        } else {
            $data = DicData::get($id);
            $dic_type = DicType::where(['id' => $data->type_id])->find();
            $this->assign('dic_type', $dic_type);
            $this->assign('data', $data);
            return view();
        }
    }

    public function delete($id)
    {
        $res = DicData::destroy(function ($query) use ($id) {
            $query->where(['id' => $id]);
        });
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}
