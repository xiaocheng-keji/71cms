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

namespace app\common\taglib;

use think\Db;
use think\template\TagLib;

/**
 * 自定义标签
 */
class Xc extends TagLib
{
    protected $tags = [
        'sql' => ['attr' => 'sql,key,id,cache,result'],//执行sql query
        'catlist' => ['attr' => 'parent_id,key,id,cache,type,result,length,order'],//栏目列表
        'artlist' => ['attr' => 'cat_id,p_cat_id,key,id,result,cache,offset,length,order,thumb,recommend'],//文章列表
        'config' => ['attr' => 'name,cache'],//读取配置
        'banner' => ['attr' => 'title,name,cache,group,width,height,id', 'close' => 0],//banner获取
    ];

    /**
     * 执行sql
     * @param $tag
     * @param $content
     * @return string|void
     */
    public function tagSql($tag, $content)
    {
        $sql = $tag['sql'];
        $id = $tag['id'];
        $sql = str_replace('__PREFIX__', config('database.prefix'), $sql);
        $key = !empty($tag['key']) ? $tag['key'] : 'i';
        $cache = !empty($tag['cache']) ? $tag['cache'] : config('cache.expire');// 缓存时间
        $name = !empty($tag['result']) ? $tag['result'] : 'sql_result_' . $id;
        $parseStr = '<?php
                        $md5_key = md5("' . $sql . '");
                        $' . $name . ' = cache("sql_".$md5_key);
                        if(empty($' . $name . '))
                        {
                            $' . $name . ' = think\Db::query("' . $sql . '");
                            cache("sql_".$md5_key, $' . $name . ', ' . $cache . ');
                        }
                     ';
        $parseStr .= ' foreach($' . $name . ' as $' . $key . '=>$' . $id . '): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; ?>';

        return $parseStr;
    }

    /**
     * 获取栏目
     * @param $tag
     * @param $content
     * @return string|void
     */
    public function tagCatlist($tag, $content)
    {
        $key = !empty($tag['key']) ? $tag['key'] : 'i';
        $id = !empty($tag['id']) ? $tag['id'] : 'id';// 返回的变量id
        $cache = !empty($tag['cache']) ? $tag['cache'] : config('cache.expire');// 缓存时间
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : '9999';
        $result = !empty($tag['result']) ? $tag['result'] : 'sql_result_' . $id;
        $parseStr = '<?php 
                        $where = [];
                        ' . (isset($tag['cat_id']) && !is_null($tag['cat_id']) ? '$where[]=["id", "in", "' . $tag["cat_id"] . '"];' : '') . '
                        ' . (isset($tag['parent_id']) && !is_null($tag['parent_id']) ? '$where[]=["parent_id", "=", ' . $tag["parent_id"] . '];' : '') . '
                        ' . (isset($tag['type']) ? '$where[]=["type", "in", "' . $tag["type"] . '"];' : '') . '
                         $where[]=["status", "=", "1"];
                        ' . (isset($tag['order']) && !is_null($tag['order']) ? '$order="' . $tag["order"] . '";' : '$order="sort asc,id asc";') . '
                        $' . $result . ' = think\Db::name("category")->where($where)->order($order)->limit("' . $offset . '","' . $length . '")->cache(' . $cache . ')->select(); 
                     ';
        $parseStr .= ' foreach($' . $result . ' as $' . $key . '=>$' . $id . '): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; ?>';

        return $parseStr;
    }

    /**
     * 文章列表
     * @param $tag
     * @param $content
     * @return string|void
     * {artlist p_cat_id='父栏目' cat_id='栏目' order='id desc' id="v" offset="0" length='2'}
     */
    public function tagArtlist($tag, $content)
    {
        $key = !empty($tag['key']) ? $tag['key'] : 'i';
        $id = !empty($tag['id']) ? $tag['id'] : 'id';// 返回的变量id
        $thumb = !empty($tag['thumb']) ? $tag['thumb'] : 1;// 返回的变量id
        $cache = !empty($tag['cache']) ? $tag['cache'] : config('cache.expire');// 缓存时间
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : '5';
        $result = !empty($tag['result']) ? $tag['result'] : 'result';// 返回的变量
        $parseStr = '';
        if (isset($tag['p_cat_id']) && is_string($tag['p_cat_id'])) {
            if (substr($tag['p_cat_id'], 0, 1) == '$') {
                $parseStr .= '<?php 
                    $p_cat_id = think\Db::name("category")->where("parent_id", ' . $tag['p_cat_id'] . ')->cache(' . $cache . ')->column("id");
                    $p_cat_id = implode(",", $p_cat_id);
                ?> ';
                $p_cat_id = '$p_cat_id';
            } else {
                $p_cat_id = Db::name('category')
                    ->where('parent_id', $tag['p_cat_id'])
                    ->cache($cache)
                    ->column('id');
                $p_cat_id = implode(',', $p_cat_id);
            }
        }
        $parseStr .= '<?php 
                        $where = [];
                        $where[] = ["status", "=", 1];
                        ' . (isset($tag['cat_id']) && !is_null($tag['cat_id']) ? '$where[]=["article.cat_id","=", ' . $tag["cat_id"] . '];' : '') . '
                        ' . (isset($tag['p_cat_id']) && !is_null($tag['p_cat_id']) ? '$where[]=["article.cat_id","IN",' . $p_cat_id . '];' : '') . '
                        ' . (isset($tag['thumb']) && !is_null($tag['thumb']) ? '$where[]=["article.thumb","!=",""];' : '') . '
                        ' . (isset($tag['recommend']) && !is_null($tag['recommend']) ? '$where[]=["article.recommend",">",0];' : '') . '
                        $order = "";
                        ' . (isset($tag['order']) && !is_null($tag['order']) ? '$order="article.' . $tag["order"] . '";' : '$order="article.id desc";') . '
                        ' . (isset($tag['length']) && !is_null($tag['length']) ? '$length=' . $tag["length"] . ';' : '$length="' . $length . '";') . '
                     ';
        if (isset($tag['offset']) && !is_null($tag['offset'])) {
            $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
            $parseStr .= '$' . $result . ' = think\Db::name("article")
                                                            ->alias("article")
                                                            ->where($where)
                                                            ->limit("' . $offset . '",$length)
                                                            ->cache(' . $cache . ')
                                                            ->order($order)
                                                            ->field("article.*")
                                                            ->select();';
        } else {
            $parseStr .= '$' . $result . ' = think\Db::name("article")
                                                            ->alias("article")
                                                            ->where($where)
                                                            ->cache(' . $cache . ')
                                                            ->order($order)
                                                            ->field("article.*")
                                                            ->paginate($length);';
        }

        $parseStr .= ' foreach($' . $result . ' as $' . $key . '=>$' . $id . '): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; ?>';

        return $parseStr;
    }

    public function tagConfig($tag)
    {
        $name = $tag['name'];
        $cache = !empty($tag['cache']) ? $tag['cache'] : config('cache.expire');// 缓存时间
        $parseStr = '<?php echo think\Db::where("config")->where("name","' . $name . '")->cache("' . $cache . '")->value("value") ?>';

        return $parseStr;
    }

    /**
     * 获取banner
     * {banner title="" name="" group="" width="" height="" cache=""}
     *
     * @param $tag
     * @param $content
     * @return string|void
     */
    public function tagBanner($tag, $content)
    {
        $name = !empty($tag['name']) ? $tag['name'] : '';
        if ($name == '') return '';

        $group = !empty($tag['group']) ? $tag['group'] : '默认分组';
        $title = !empty($tag['title']) ? $tag['title'] : $group . uniqid();

        $id = !empty($tag['id']) ? $tag['id'] : 'id';// 返回的变量id
        $result = !empty($tag['result']) ? $tag['result'] : 'sql_result_' . $id;
        $cache = !empty($tag['cache']) ? $tag['cache'] : config('cache.expire');// 缓存时间

        //如果banner不存在 插入一条
        $exist = Db::name('banner')->where('name', $name)->find();
        if (!$exist) {
            Db::name('banner')->data([
                'name' => $name,
                'title' => $title,
                'group' => $group,
                'create_time' => time(),
                'img' => '',
                'link' => '',
                'status' => 0
            ])->insert();
        }

        $parseStr = '<?php 
                        $where = [];
                        $where[]=["name", "=", "' . $name . '"];
                        $where[]=["status", "=", "1"];
                        $' . $result . ' = $' . $id . ' = think\Db::name("banner")->where($where)->cache(' . $cache . ')->find(); 
                     ?>';

        return $parseStr;
    }
}
 