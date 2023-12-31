/*
* @Author: layui-2
* @Date:   2018-08-31 11:40:42
* @Last Modified by:   layui-2
* @Last Modified time: 2018-09-04 14:44:38
*/
layui.define(['jquery', 'layer'], function (exports) {
    "use strict";
    var $ = layui.jquery, layer = layui.layer


        //外部接口
        , inputTags = {
            config: {}

            //设置全局项
            , set: function (options) {
                var that = this;
                that.config = $.extend({}, that.config, options);
                return that;
            }

            // 事件监听
            , on: function (events, callback) {
                return layui.onevent.call(this, MOD_NAME, events, callback)
            }
        }

        //操作当前实例
        , thisinputTags = function () {
            var that = this
                , options = that.config;
            return {
                config: options
                , addTag: function(val) {
                    that.addTag(val)
                }, removeTag: function(val) {
                    that.removeTag(val)
                },
            }
        }

        //字符常量
        , MOD_NAME = 'inputTags'


        // 构造器
        , Class = function (options) {
            var that = this;
            that.config = $.extend({}, that.config, inputTags.config, options);
            that.render();
        };

    //默认配置
    Class.prototype.config = {
        close: false  //默认:不开启关闭按钮
        , theme: ''   //背景:颜色
        , content: [] //默认标签
        , aldaBtn: false //默认配置
        , count: 5 //默认标签最多输入5个
        , showDeleted: true //是否显示删除按钮
    };

    // 初始化
    Class.prototype.init = function () {
        var that = this
            , spans = ''
            , options = that.config;
        var delBtn = '';

        $.each(options.content, function (index, item) {
            if (options.showDeleted == true) {
                delBtn = `<button type="button" class="close" data-id="${item.id}" data-name="${item.name}" data-type="${item.type}">×</button>`
            }
            spans += '<li><em>' + item.name + '</em>' + delBtn + '</li>';
        });

        options.elem.before('<ul id="tags-list">' + spans + '</ul>');

        //绑定事件
        options.elem.on('sugSuccess', function (event,value) {
            that.addTag(value)
        });
        that.events();
    };

    Class.prototype.render = function () {
        var that = this
            , options = that.config;
        options.elem = $(options.elem);
        that.enter();
    };

    Class.prototype.addTag = function ($val) {
        if (!$val) return false;
        var that = this
            , spans = ''
            , options = that.config;

        let index = options.content.findIndex(res=>{
            return res.id==$val.id && res.name==$val.name && res.type==$val.type;
        });


        if (index == -1) {
            options.content.push($val);
            that.render();
            var delBtn = '';
            var noShowStyle = '';
            if (options.showDeleted == true) {
                delBtn = `<button type="button" class="close" data-id="${$val.id}" data-name="${$val.name}" data-type="${$val.type}">×</button>`
            } else {
                noShowStyle = ' style="padding-right:8px;"';
            }
            spans += '<li' + noShowStyle + '><em>' + $val.name + '</em>' + delBtn + '</li>';
            var n = $('#tags-list li').length;//求出li标签的个数
            if (n < options.count) {
                $('#tags-list').append(spans);
            } else {
                layer.msg('标签最多不能够超过' + options.count + '个');
            }
        }
        options.done && typeof options.done === 'function' && options.done($val);
        options.elem.val('');
    };

    Class.prototype.removeTag = function ($val) {
        var that = this
            , options = that.config;
        let index;

        index = options.content.findIndex((res)=>{
            return res.id==$val.id && res.name==$val.name && res.type==$val.type;
        });
        if(index>-1){
            $('#tags-list').find('li').eq(index).remove();
            options.content.splice(index, 1);
            options.remove && typeof options.remove === 'function' && options.remove($val);
        }
    };

    // 空格生成标签
    Class.prototype.enter = function () {
        var that = this
            , spans = ''
            , options = that.config;
        options.elem.focus();
        options.elem.keypress(function (event) {
            var keynum = (event.keyCode ? event.keyCode : event.which);
            if (keynum == 32) {  //如加回车键也能够形成标签的话，将if条件改成keynum == 32||keynum==13
                return false;
                var $val = options.elem.val().trim();
                if (!$val) return false;
                if (options.content.indexOf($val) == -1) {
                    options.content.push($val);
                    that.render();
                    var delBtn = '';
                    if (options.showDeleted == true) {
                        delBtn = `<button type="button" class="close" data-id="${$val.id}" data-name="${$val.name}" data-type="${$val.type}">×</button>`
                    }
                    spans += '<li><em>' + $val + '</em>' + delBtn + '</li>';
                    var n = $('#tags-list li').length;//求出li标签的个数
                    if (n < options.count) {
                        $('#tags-list').append(spans);
                    } else {
                        layer.msg('标签最多不能够超过' + options.count + '个');
                    }
                }
                options.done && typeof options.done === 'function' && options.done($val);
                options.elem.val('');
            }
        });
    };

    //事件处理
    Class.prototype.events = function () {
        var that = this
            , options = that.config;
        options.elem.keypress(function (event) {
            var keynum = (event.keyCode ? event.keyCode : event.which);
            if (keynum == 8) {
                //如果options.elem中存在还未形成标签格式的字符串，则先删除掉这些字符串
                var $value = options.elem.val().trim();
                var $l = $value.length;
                if ($l > 0) {
                    $value.slice(0, $l - 1);
                }
                var $m = $('#tags-list li').length;//求出li标签的个数
                if ($m > 0 && $l == 0) {
                    var $v = $("#tags-list li").eq($m - 1).find("em").html();//找出li>em中的值
                    var $index = options.content.indexOf($v); //找出回格键删除元素的下标
                    if ($index > -1) {//如果存在options.content中
                        options.content.splice($index, 1);//则彻底删除数组中的值
                        $("#tags-list li").eq($m - 1).remove();//并移除了li标签
                    }
                }
            }
        });
        $('#tags').on('click', '.close', function () {
            var Thisremov = $(this).parent('li').remove()
                ,data = {
                id:$(this).data('id'),
                name:$(this).data('name'),
                type:$(this).data('type'),
            };
            options.content = options.content.filter(res=>{
                return res.id!=data.id || res.name!=data.name || res.type !=data.type;
            });
            // options.content.splice($.inArray(ThisText, options.content), 1);
            options.remove && typeof options.remove === 'function' && options.remove(data);
        });
    };

    //核心入口
    inputTags.render = function (options) {
        var inst = new Class(options);
        inst.init();
        return thisinputTags.call(inst);
    };
    exports('inputTags', inputTags);
}).link('/plugins/layui/inputTags/inputTags.css');