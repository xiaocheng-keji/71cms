/**
 * zepto插件：向左滑动删除动效
 * 使用方法：$('.itemWipe').touchWipe({itemDelete: '.item-delete'});
 * 参数：itemDelete  删除按钮的样式名
 */
;
(function($) {
    $.fn.touchWipe = function(option) {
        var defaults = {
            itemDelete: '.item-delete', //删除元素
        };
        var opts = $.extend({}, defaults, option); //配置选项
        var delWidth = $(opts.itemDelete).width();

        var initX; //触摸位置X
        var initY; //触摸位置Y
        var moveX; //滑动时的位置X
        var moveY; //滑动时的位置Y
        var X = 0; //移动距离X
        var Y = 0; //移动距离Y
        var flagX = 0; //是否是左右滑动 0为初始，1为左右，2为上下，在move中设置，在end中归零
        var objX = 0; //目标对象位置

        $(this).on('touchstart', function(event) {
            var obj = this;
            initX = event.targetTouches[0].pageX;
            initY = event.targetTouches[0].pageY;
            objX = (obj.style.WebkitTransform.replace(/translateX\(/g, "").replace(/px\)/g, "")) * 1;
            if (objX == 0) {
                $(this).on('touchmove', function(event) {

                    // 判断滑动方向，X轴阻止默认事件，Y轴跳出使用浏览器默认
                    if (flagX == 0) {
                        setScrollX(event);
                        return;
                    } else if (flagX == 1) {
                        event.preventDefault();
                    } else {
                        return;
                    }

                    var obj = this;
                    moveX = event.targetTouches[0].pageX;
                    X = moveX - initX;
                    if (X >= 0) {
                        obj.style.WebkitTransform = "translateX(" + 0 + "px)";
                    } else if (X < 0) {
                        var l = Math.abs(X);
                        obj.style.WebkitTransform = "translateX(" + -l + "px)";
                        if (l > delWidth) {
                            l = delWidth;
                            obj.style.WebkitTransform = "translateX(" + -l + "px)";
                        }
                    }
                });
            } else if (objX < 0) {
                $(this).on('touchmove', function(event) {

                    // 判断滑动方向，X轴阻止默认事件，Y轴跳出使用浏览器默认
                    if (flagX == 0) {
                        setScrollX(event);
                        return;
                    } else if (flagX == 1) {
                        event.preventDefault();
                    } else {
                        return;
                    }

                    var obj = this;
                    moveX = event.targetTouches[0].pageX;
                    X = moveX - initX;
                    if (X >= 0) {
                        var r = -delWidth + Math.abs(X);
                        obj.style.WebkitTransform = "translateX(" + r + "px)";
                        if (r > 0) {
                            r = 0;
                            obj.style.WebkitTransform = "translateX(" + r + "px)";
                        }
                    } else { //向左滑动
                        obj.style.WebkitTransform = "translateX(" + -delWidth + "px)";
                    }
                });
            }
        })

        //结束时判断，并自动滑动到底或返回
        $(this).on('touchend', function(event) {
            var obj = this;
            objX = (obj.style.WebkitTransform.replace(/translateX\(/g, "").replace(/px\)/g, "")) * 1;
            if (objX > -delWidth / 2) {
                obj.style.transition = "all 0.2s";
                obj.style.WebkitTransform = "translateX(" + 0 + "px)";
                obj.style.transition = "all 0";
                objX = 0;
            } else {
                obj.style.transition = "all 0.2s";
                obj.style.WebkitTransform = "translateX(" + -delWidth + "px)";
                obj.style.transition = "all 0";
                objX = -delWidth;
            }
            flagX = 0;
        });

        //设置滑动方向
        function setScrollX(event) {
            moveX = event.targetTouches[0].pageX;
            moveY = event.targetTouches[0].pageY;
            X = moveX - initX;
            Y = moveY - initY;

            if (Math.abs(X) > Math.abs(Y)) {
                flagX = 1;
            } else {
                flagX = 2;
            }
            return flagX;
        }

        //链式返回
        return this;
    };

})(Zepto);
