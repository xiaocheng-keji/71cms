<?php

namespace app\wap\model;

use think\facade\Cache;
use think\Model;

class Video extends Model
{

    public function videoType(){
        return $this->belongsTo('VideoType','cid','id');
    }

    public function getVimgAttr($value){
        if(stripos($value,'http')!==false){
            return $value;
        }else{
            if(stripos($value,'uploads/')!==false){
                return SITE_URL.$value;
            }else{
                return Cache::get('site_config')['qiniu_url'].$value;
            }
        }

    }

    public function getSrcAttr($value){
        if(stripos($value,'http')!==false){
            return $value;
        }else{
            if(stripos($value,'uploads/')!==false){
                return SITE_URL.$value;
            }else{
                return Cache::get('site_config')['qiniu_url'].$value;
            }
        }
    }

    public function getHot($uid,$limit=10,$diff=false){
        $type_to_user = TypeToUser::group('cid')->column('cid');
        $type_to_user = implode(',', $type_to_user);
        $map1 = [['video.cid', 'not in', $type_to_user], ['videoType.status', '=', '1']];

        $type_to_user2 = TypeToUser::where(['uid'=>$uid])->group('cid')->column('cid');
        $type_to_user2 = implode(',', $type_to_user2);
        $map2 = [['video.cid', 'in', $type_to_user2], ['videoType.status', '=', '1']];

        $gids = UserDep::where(['user_id'=>$uid])->column('dep_id');
        $gids = implode(',',$gids);
        $type_to_user3 = TypeToUser::whereIn('gid',$gids)->group('cid')->column('cid');
        $type_to_user3 = implode(',', $type_to_user3);
        $map3 = [['video.cid', 'in', $type_to_user3], ['videoType.status', '=', '1']];

        if($diff){
            $map4 = ['video.ext'=>'.mp4'];
        }else{
            $map4 = [];
        }

        $hot = Video::useGlobalScope(false)
            ->field('*,count(video.cid) as count,sum(video.study_count) as study_count')
            ->withJoin(['videoType'=>function($query){
                $query->where([]);
            }])->where(function($query) use($map1,$map2,$map3){
                $query->whereOr([$map1, $map2, $map3]);
            })
            ->where($map4)
            ->group('cid')
            ->order('study_count desc')
            ->limit($limit)
            ->select()->toArray();

        foreach($hot as $kk=>$vv){
            $min = Video::where(['cid' => $vv['cid']]) -> order('sort')->find()->toArray();
            $playing = UserCourse::where(['cid' => $vv['cid'], 'status' => 0])->value('count(cid)');

            if ($vv['id'] != $min['id']) {
                $min['count'] = $vv['count'];
                $min['playing'] = $playing;
                $hot[$kk] = $min;
            } else {
                $hot[$kk]['playing'] = $playing;
            }
            $hot[$kk]['cname'] = VideoType::where(['id' => $vv['cid']])->value('typename');
        }

        return $hot;
    }
}
