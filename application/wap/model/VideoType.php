<?php

namespace app\wap\model;

use think\Model;

class VideoType extends Model
{
    // 定义全局的查询范围
    protected function base($query)
    {
    }

    public function video(){
        return $this->hasMany('Video','cid','id');
    }

    public function target(){
        return $this->hasMany('TypeToUser','cid','id');
    }

    public function getAuthVideo($uid,$map=[]){
        $gids = UserDep::where(['user_id'=>$uid])->column('dep_id');
        $gids = implode(',',$gids);

        $info = db('video_type')
            ->alias('a')
            ->field('a.id,a.typename,a.status,a.sort,a.pid,a.group,a.level,a.tenant_id')
            ->join('type_to_user b',"a.id=b.cid",'left')
            ->where(['a.status'=>1,'a.level'=>2])
            ->where("b.uid=$uid or b.gid in($gids) or b.id is null")
            ->group('a.id');
        return $info;
    }

    public function getVideo($videos,$uid){
        foreach($videos as &$item){
            $video = Video::where(['cid'=>$item['id']])->order('sort')->select()->toArray();
            $uncomplete = CourseComplete::where(['cid'=>$item['id'],'status'=>0,'uid'=>$uid])->count();
            $complete = CourseComplete::where(['cid'=>$item['id'],'status'=>1,'uid'=>$uid])->count();
//            if ($video) {
//                $playing = UserCourse::where(['cid' => $item['id'], 'status' => 0])->count();
//                $video[0]['cname'] = $item['typename'];
//                $video[0]['count'] = count($video);
//                $video[0]['playing'] = $playing;
//            }
            $item['video'] = $video;
            $item['uncomplete'] = $uncomplete;
            $item['complete'] = $complete;
            $item['percent'] = sprintf('%.2f',$complete/($uncomplete+$complete)*100);
        }
        return $videos;
    }
}
