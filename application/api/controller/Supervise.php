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

namespace app\api\controller;

use app\api\model\File;
use app\api\model\Supervise as SuperviseModel;
use app\api\model\SuperviseUser;
use app\common\model\Upload as UploadModel;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Env;

class Supervise extends Controller
{
    public function get_list($page = 1)
    {
        $meetingList = SuperviseUser::useGlobalScope(false)
            ->alias('a')
            ->leftJoin('supervise b', 'a.s_id=b.id')
            ->where('a.user_id', $this->user_id)
            ->order('a.id', 'desc')
            ->field('a.id,b.name,b.content,b.begin_time,b.end_time')
            ->page($page, 10)->select();
        foreach ($meetingList as &$item) {
            $item['time'] = date('Y年m月d日H:i', $item['begin_time']) . ' - ' . date('Y年m月d日H:i', $item['end_time']);
        }

        if (count($meetingList) >= 10) {
            $loading = 0;
        } else {
            $loading = 2;
        }
        jsonReturn(1, '获取成功', [
            'list' => $meetingList,
            'loading' => $loading
        ]);
    }

    public function detail($id)
    {
//        $article = SuperviseModel::get($id)->toArray();
        $supervise_user = SuperviseUser::where('id', $id)->find()->toArray();
        $article = SuperviseModel::get($supervise_user['s_id'])->toArray();

//        $supervise_user = SuperviseUser::where('s_id', $article['id'])->find();
        if ($article) {
            $article['content'] = htmlspecialchars_decode($article['content']);
            $article['content'] = preg_replace(
                [
                    '/(<img [^<>]*?)width=.+?[\'|\"]/',
                    '/(<img [^<>]*?)/',
                    '/(<img.*?)((height)=[\'"]+[0-9|%]+[\'"]+)/',
                    '/(<img.*?)((style)=[\'"]+(.*?)+[\'"]+)/',
                    '/\/uploads/',
                ],
                [
                    '$1 width="100%" ',
                    '$1 width="100%" ',
                    '$1',
                    '$1',
                    SITE_URL . '/uploads'
                ],
                $article['content']
            );
//            dump($article);die;
            if ($article['attachment']) {
                $attachmentList = File::where('savepath', 'in', $article['attachment'])->select();

            } else {
                $attachmentList = [];
            }
            if ($supervise_user['attachment']) {
                $userAttachmentList = Db::name('file')->where('savepath', 'in', $supervise_user['attachment'])->order('id', 'asc')->select();
                foreach ($userAttachmentList as &$item) {
                    $item['savepath2'] = SITE_URL . $item['savepath'];
                }
            } else {
                $userAttachmentList = [];
            }

            jsonReturn(1, '成功获取', [
                'attachmentList' => $attachmentList,
                'article' => $article,
                'supervise_user' => $supervise_user,
                'attachment' => $userAttachmentList
            ]);
        } else {
            jsonReturn(0, '无改信息');
        }
    }


    /**
     * @param $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function completion($id)
    {
        $supervise_user = SuperviseUser::where('id', $id)->find();
        if ($supervise_user['status'] != 1) {
            jsonReturn(0, '不是待办状态，无法确认');
        }
        $r = SuperviseUser::update(['status' => 2, 'completion_time' => time()], ['id' => $id, 'user_id' => $this->user_id]);
        if ($r) {
            jsonReturn(1, '确认成功');
        } else {
            jsonReturn(0, '确认失败');
        }
    }


    public function uploadui()
    {
        $this->assign('s_id', input('s_id'));
        $this->assign('token', $this->token);
        $this->assign('tenant_id', input('tenant_id'));
        $this->assign('site', SITE_URL);
        $this->assign('id', input('id'));
        $superviseUser = SuperviseUser::where(['id' => input('id')])->find();

        if ($superviseUser['attachment']) {
            $userAttachmentList = Db::name('file')->where('savepath', 'in', $superviseUser['attachment'])->order('id', 'asc')->select();
        } else {
            $userAttachmentList = [];
        }
        $this->assign('list', $userAttachmentList);
        return view();
    }

    public function upfile()
    {
        $upload = new UploadModel();
        return json($upload->upfile('files'));
    }


    /**
     * @param $id
     * @param $file
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 上传附件
     */
    public function upload($id, $file)
    {
        if (!$file) {
            $this->error('保存上传文件失败');
        }
        $supervise_user = SuperviseUser::where('id', $id)->find()->toArray();
        $supervise_user['attachment'][] = $file;
        $r = SuperviseUser::update(['attachment' => $supervise_user['attachment']], ['id' => $id]);
        if ($r) {
            $this->success('上传成功');
        } else {
            $this->error('上传失败');
        }
    }

    /**
     * @param $id
     * @param $file
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 删除上传的附件
     */
    public function del_attachment($id, $file)
    {
        if (!$file) {
            $this->error('参数错误');
        }
        $supervise_user = SuperviseUser::where('id', $id)->where('user_id', $this->user_id)->find()->toArray();
        $index = array_search($file, $supervise_user['attachment']);
        if ($index !== false) {
            unset($supervise_user['attachment'][$index]);
            $r = SuperviseUser::update(['attachment' => $supervise_user['attachment']], ['id' => $id]);
            if ($r) {
                jsonReturn(1, '移除成功');
            } else {
                jsonReturn(0, '移除失败');
            }
        }
        jsonReturn(0, '移除失败');
    }

    public function download1()
    {
        $config = Config::get('database.');
        $config['database'] = 'dangjian_group_new';
        $list = Db::connect($config)->name('article')
            ->where('cat_id', 1080)
            ->select();
        foreach($list as $article) {
            // 不在数据库里的，插入
            $exist = Db::name('article')->where('id', $article['id'])->find();
            if (!$exist) {
                Db::name('article')->insert($article);
            }
        }
        dd($list);
//        $res = Db::name('article')->select();
//        // 遍历，输出数据的大小，单位KB
//        foreach ($res as $val) {
//            $l = mb_strlen(json_encode($val));
//            if ($l > 20264) {
//                dump('id:' . $val['id'] . ' ' . $l);
//            }
//        }
////        dd($res);
//        die;
        // 获取栏目id，删除不在栏目id中的文章
        $cat_ids = Db::name('category')->column('id');
        $res = Db::name('article')->where('cat_id', 'not in', $cat_ids)->delete();
//        dd($res);
        // 删除未发布的文章
//        $res =Db::name('article')->where('content', 'like', '%习近平%')->delete();
//        dd($res);
        $res = Db::name('article')->where('content', 'like', '%中复神鹰%')->delete();
        $res = Db::name('article')->where('status', '=', 0)->delete();
        $res = Db::name('article')->where('title', 'like', '%新华社%')->delete();
        $res = Db::name('article')->where('content', 'like', '%新华社%')->delete();
        $res = Db::name('article')->where('title', '=', '1111')->delete();
        $res = Db::name('article')->where('title', 'like', '%智慧工会%')->delete();
        $res = Db::name('article')->where('title', 'like', '%文章标题%')->delete();
        $res = Db::name('article')->where('title', 'like', '%物产%')->delete();
        $res = Db::name('article')->where('title', 'like', '%西宁公司%')->delete();
        $res = Db::name('article')->where('cat_id', '=', '1088')->delete();
        $res = Db::name('article')->where('cat_id', '=', '1087')->delete();
        $res = Db::name('article')->where('cat_id', '=', '1090')->delete();
        $res = Db::name('article')->where('title', 'like', '%中国建材%')->delete();
        $res = Db::name('article')->where('title', 'like', '%新冠%')->delete();
        dd($res);
        // 读取books表的数据，判断image字段的图片是否存在，不存在则从https://dq.zfsycf.com.cn/下载图片到对应的目录
        $list = Db::name('article')->where('thumb', '<>', '')->field('thumb')->select();
//        dd($list);
        // 获取thinkphp的根目录
        $root_path = \think\facade\App::getRootPath();
        foreach ($list as $k => $v) {
            if (empty($v['thumb'])) {
                continue;
            }
            $image = $v['thumb'];
            // 判断图片是否存在本地
            if (!file_exists($root_path . 'public' . $image)) {
                // 不存在则下载
                $url = 'https://dq.zfsycf.com.cn' . $image;
                $url = 'http://dangjian.71cms.net' . $image;
                echo $url . "\n";
                // 使用curl获取图片，响应200则保存图片
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不输出内容
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);//超时时间
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书和hosts
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HEADER, 0);//不输出头部信息
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//跟踪重定向
                curl_setopt($ch, CURLOPT_MAXREDIRS, 5);//最大重定向次数
                curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//自动设置referer
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');//设置用户代理
                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode != 200) {
                    echo '下载失败' . "<br>";
                    continue;
                }
                curl_close($ch);
//                dd($content);
//                die;


                // 创建目录
                $dir = dirname($root_path . 'public' . $image);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                // 保存图片到本地
                file_put_contents($root_path . 'public' . $image, $content);
                echo '下载成功' . "<br>";
                usleep(100);
            }
        }
    }
}
