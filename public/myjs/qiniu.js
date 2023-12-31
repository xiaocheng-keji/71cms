/**
 * Created by Administrator on 2019/2/27.
 */
function Qiniu(key,token,putExtra,config){
    this.observable='';//初始化七牛云上传对象
    this.subscription='';//上传中的对象
    this.key=key;
    this.token=token;
    this.putExtra=putExtra;
    this.config=config;

    this.getObservable = function(file){
        if(!this.putExtra){
            putExtra = {
                fname: "",
                params: {},
                mimeType: [] || null
            };
        }

        if(!this.config){
            config = {
                useCdnDomain: true
            };
        }
        return qiniu.upload(file, this.key, this.token, this.putExtra, this.config);
    };
    this.upload = function(file,fJson){
        var observable = this.getObservable(file);
        this.subscription = observable.subscribe(fJson) // 上传开始
    };
    this.cancel = function(callback){
        this.subscription.unsubscribe(); // 上传取消
        callback();
    }
}