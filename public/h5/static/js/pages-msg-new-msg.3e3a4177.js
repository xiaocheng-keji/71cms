(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-msg-new-msg"],{"04d7":function(t,i,e){"use strict";var n=e("599d"),a=e.n(n);a.a},"1de5":function(t,i,e){"use strict";t.exports=function(t,i){return i||(i={}),t=t&&t.__esModule?t.default:t,"string"!==typeof t?t:(/^['"].*['"]$/.test(t)&&(t=t.slice(1,-1)),i.hash&&(t+=i.hash),/["'() \t\n]/.test(t)||i.needQuotes?'"'.concat(t.replace(/"/g,'\\"').replace(/\n/g,"\\n"),'"'):t)}},"1f02":function(t,i,e){"use strict";e.r(i);var n=e("f42c"),a=e("a9ad");for(var s in a)["default"].indexOf(s)<0&&function(t){e.d(i,t,(function(){return a[t]}))}(s);e("04d7");var c=e("f0c5"),o=Object(c["a"])(a["default"],n["b"],n["c"],!1,null,"5dcf4920",null,!1,n["a"],void 0);i["default"]=o.exports},"23c2":function(t,i,e){var n=e("24fb"),a=e("1de5"),s=e("f1ab");i=n(!1);var c=a(s);i.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */uni-page-body[data-v-5dcf4920]{background-color:#f3f3f3}body.?%PAGE?%[data-v-5dcf4920]{background-color:#f3f3f3}.list[data-v-5dcf4920]{width:%?750?%;min-height:%?400?%;background-color:#fff}.list .item[data-v-5dcf4920]{display:flex;align-items:center;padding:0 %?32?%;width:%?686?%;height:%?144?%;border-top:%?1?% solid #e6e6e6}.list .item uni-image[data-v-5dcf4920]{width:%?80?%;height:%?80?%;margin-right:%?16?%}.list .item .item-msgbox[data-v-5dcf4920]{display:flex;flex-direction:column;align-content:space-between;width:%?400?%;height:%?90?%}.list .item .item-msgbox .item-msgtitle[data-v-5dcf4920]{font-size:%?32?%;font-family:PingFangSC-Medium,PingFang SC;color:#000;letter-spacing:%?2?%}.list .item .item-msgbox .item-msgnav[data-v-5dcf4920]{width:%?400?%;height:%?32?%;margin-top:%?8?%;font-size:%?24?%;font-family:PingFangSC-Regular,PingFang SC;font-weight:400;color:#9b9b9b;letter-spacing:%?2?%;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.list .item .item-ortherbox[data-v-5dcf4920]{margin-left:%?76?%;width:%?96?%;height:%?80?%}.list .item .item-ortherbox .item-time[data-v-5dcf4920]{text-align:right;font-size:%?24?%;font-family:PingFangSC-Regular,PingFang SC;font-weight:400;color:#9b9b9b;letter-spacing:%?3?%}.list .item .item-ortherbox .item-num[data-v-5dcf4920]{margin:%?20?% 0 0 %?70?%;width:%?28?%;height:%?28?%;background-image:url('+c+");background-size:100% 100%;line-height:%?28?%;text-align:center;font-size:%?10?%;color:#fff;font-weight:300}.list .item[data-v-5dcf4920]:nth-child(1){border:none}",""]),t.exports=i},"527d":function(t,i,e){"use strict";(function(t){e("7a82"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0,e("14d9"),e("e25e");var n={data:function(){return{imges:[{src:e("57ed"),id:68},{src:e("c7d3"),id:3},{src:e("e0ff"),id:5},{src:e("f99b"),id:8},{src:e("8d48"),id:1},{src:e("75b6"),id:11},{src:e("f99b"),id:12}],list:[]}},onLoad:function(){},onShow:function(){this.getList()},methods:{toList:function(t,i){if(this.is_examine)return!1;68==t?uni.navigateTo({url:"/pagesMain/category/category?list_type=noread&id="+t+"&title=通知公告"}):uni.navigateTo({url:"/pagesMain/msg/list?type="+t+"&title="+i})},getList:function(){var i=this;uni.showLoading({mask:!0,title:"加载中"}),this.$server.postJSON("/Message/index",{token:getApp().globalData.token},(function(e){e=e.data.result;var n=[];for(var a in e)n.push(e[a]);i.list=n;for(var s=0,c=0;c<i.list.length;c++)i.list[c].id=i.imges[c].id,s+=i.list[c].unread,i.list[c].time=i.timeago(Date.parse(new Date)-i.list[c].time);s>0?(t("log","unread",s," at pages/msg/new-msg.vue:100"),uni.showTabBarRedDot({index:3})):uni.hideTabBarRedDot({index:3}),t("log",i.list," at pages/msg/new-msg.vue:109"),i.$forceUpdate(),uni.hideLoading(),uni.stopPullDownRefresh()}))},timeago:function(t){var i=864e5,e=(new Date).getTime(),n=e-t;if(!(n<0)){var a,s=n/6e4,c=n/36e5,o=n/i,r=n/6048e5,d=n/2592e6;return a=d>=1?parseInt(d)+"月前":r>=1?parseInt(r)+"周前":o>=1?parseInt(o)+"天前":c>=1?parseInt(c)+"小时前":s>=1?parseInt(s)+"分钟前":"刚刚",a}}}};i.default=n}).call(this,e("0de9")["log"])},"57ed":function(t,i,e){t.exports=e.p+"static/img/notic.72ea0c0a.png"},"599d":function(t,i,e){var n=e("23c2");n.__esModule&&(n=n.default),"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var a=e("4f06").default;a("475fe1e4",n,!0,{sourceMap:!1,shadowMode:!1})},"75b6":function(t,i,e){t.exports=e.p+"static/img/emailbox.7effc6d5.png"},"8d48":function(t,i,e){t.exports=e.p+"static/img/system.025f1c03.png"},a9ad:function(t,i,e){"use strict";e.r(i);var n=e("527d"),a=e.n(n);for(var s in n)["default"].indexOf(s)<0&&function(t){e.d(i,t,(function(){return n[t]}))}(s);i["default"]=a.a},c7d3:function(t,i,e){t.exports=e.p+"static/img/meeting.216f2671.png"},e0ff:function(t,i,e){t.exports=e.p+"static/img/action.21d34a64.png"},f1ab:function(t,i){t.exports="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAYAAAByDd+UAAAACXBIWXMAAAsTAAALEwEAmpwYAAAFFmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMxNDggNzkuMTY0MDM2LCAyMDE5LzA4LzEzLTAxOjA2OjU3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgMjEuMCAoV2luZG93cykiIHhtcDpDcmVhdGVEYXRlPSIyMDIyLTExLTE1VDEyOjAzOjE4KzA4OjAwIiB4bXA6TW9kaWZ5RGF0ZT0iMjAyMi0xMS0xNVQxMjoyOTozNiswODowMCIgeG1wOk1ldGFkYXRhRGF0ZT0iMjAyMi0xMS0xNVQxMjoyOTozNiswODowMCIgZGM6Zm9ybWF0PSJpbWFnZS9wbmciIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpmMTcyMjE4NC0yOTQzLWY2NDItYWY2ZS1kZTEyZjczNDE2N2UiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6ZjE3MjIxODQtMjk0My1mNjQyLWFmNmUtZGUxMmY3MzQxNjdlIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6ZjE3MjIxODQtMjk0My1mNjQyLWFmNmUtZGUxMmY3MzQxNjdlIj4gPHhtcE1NOkhpc3Rvcnk+IDxyZGY6U2VxPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0iY3JlYXRlZCIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDpmMTcyMjE4NC0yOTQzLWY2NDItYWY2ZS1kZTEyZjczNDE2N2UiIHN0RXZ0OndoZW49IjIwMjItMTEtMTVUMTI6MDM6MTgrMDg6MDAiIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFkb2JlIFBob3Rvc2hvcCAyMS4wIChXaW5kb3dzKSIvPiA8L3JkZjpTZXE+IDwveG1wTU06SGlzdG9yeT4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6j40x2AAABwklEQVRIib3WO4sTURjG8f/zJsOKja72wnohpYWOoriIl2bZ0ksj+EkUBGsLP4FNBAvBQkRQQXERxXR24gURS0VEdtFN9jwWkwmz0cBKMvNUc4aZ93few5zhaDXPmZAApH5/Tll20tIy9hFgAdgxfOYn8AXpDfYTp/SciFVgDfgNDABXi2oC2AJasi8gXTPsnzSrTcXgM/YNS3cq6AaQql2MvUMrpA7wwlJ3qxhFK3ss3QQeqajRHtYcpQQ1vM4CziZ7BTi8VegfOWj7ccBpitUaoVHB2gFnEtwHdk2BlZlPcC/gFJWVjBKT1EnQBbIZYGWyBLdDWmDYZQlmtm8xm87GszvZ3XIQFF/jOeBQDViZo7LPA4QGgzbS1RqxItL17b2eQu32omFf3Z6h8yvPF2NDWqobG6HScoQ9zX77T9HHAtjbGAgHAtjZIDgfhrkGwb9+3nXne9Pgu2ZB6WWjoOwHjYGCt9t6vZXmOrSvrOW5mwJfWboLzWyLryFdKgd1g+sBF5P9sQnwW8BSgmfVm3WBvZCOj2MzBwXvZV8GTiT7A8Wpe9PJuz1F8T7wA/iUpNct+6H7/afOsnWKk3YaxwD+AOIEjno4ouKbAAAAAElFTkSuQmCC"},f42c:function(t,i,e){"use strict";e.d(i,"b",(function(){return n})),e.d(i,"c",(function(){return a})),e.d(i,"a",(function(){}));var n=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"list"},t._l(t.list,(function(i,n){return e("v-uni-view",{key:n,staticClass:"item",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.toList(i.id,i.name)}}},[e("v-uni-image",{attrs:{src:t.imges[n].src,mode:""}}),e("v-uni-view",{staticClass:"item-msgbox"},[e("p",{staticClass:"item-msgtitle"},[t._v(t._s(i.name||""))]),e("p",{staticClass:"item-msgnav"},[t._v(t._s(i.content||"暂无最新消息"))])]),i.unread>0?e("v-uni-view",{staticClass:"item-ortherbox"},[e("p",{staticClass:"item-time"},[t._v(t._s(i.time))]),e("v-uni-view",{staticClass:"item-num"},[t._v(t._s(i.unread||""))])],1):t._e()],1)})),1)},a=[]},f99b:function(t,i,e){t.exports=e.p+"static/img/wait-do.13c0209a.png"}}]);