layui.define(['jquery','layer'],function(exports){ //提示：模块也可以依赖其它模块，如：layui.define('layer', callback);
    let $ = layui.jquery;
    let layer = layui.layer;

    let element,url,acceptMime,success,fail,progress;

    const LENGTH=3*1024*1024;//分段，每次上传10M
    let start = 0;//开始上传字节位置
    let end;//结束位置
    let blob;
    let pecent;//百分比
    let token;

    var obj = {
        render:(object)=>{
            //参数分配
            element = object.element||'';
            url = object.url||'';
            acceptMime = object.acceptMime||'';
            progress = object.progress||function(){};
            before = object.before||function(){};
            success = object.success||function(){};
            fail = object.fail||function(){};
            inputname = object.name||'file';
            acceptMime = acceptMime.split(',');
            let accept = '*';
            if(acceptMime){
                accept = acceptMime;
            }

            //添加元素
            $(element).after(`<input type="file" accept="${accept}" name="${inputname}" style="display:none;">`).click(function(){
                $(element).next('input').click();
            });

            //选择后上传
            $(element).next('input').on('change',function(){
                //上传前
                before();

                //第一段片段从0开始，到LENGTH结束
                start = 0;
                end = LENGTH+start;
                //初始化判断值（用于生成唯一临时文件名）
                token = (new Date()).getTime();
                obj.upload(this.files[0]);
                $(element).next('input').val('');
            });
        },
        upload:(file)=>{
            if(start<file.size){
                //根据start和end取此次上传分段
                blob=file.slice(start,end);

                //分配变量
                let data = new FormData;
                data.append('file',blob);
                data.append('name',file.name);
                data.append('token',token);

                if(end>=file.size){
                    data.append('lastest',true);
                }else{
                    data.append('lastest',false);
                }
                if(start==0){
                    data.append('init',true);
                }

                //ajax上传
                $.ajax({
                    url,
                    method:'post',
                    data,
                    contentType:false,
                    processData:false,
                    success:(res)=>{  //每段上传后成功的回调函数
                        //计算百分比
                        pecent = Math.floor(end/file.size*100);
                        pecent = pecent>100?100:pecent;
                        //触发progress函数
                        progress(pecent);
                        //更新下次片段位置
                        start = end;
                        end = LENGTH+start;
console.log(inputname);
                        //再次触发
                        if(pecent>=100){
                            success(res)
                        }else{
                            obj.upload(file);
                        }

                    },
                    //失败触发
                    error:function(err){
                        fail(err);
                    }
                });
            }
        }
    };



    //输出test接口
    exports('chunkUpload', obj);
});
