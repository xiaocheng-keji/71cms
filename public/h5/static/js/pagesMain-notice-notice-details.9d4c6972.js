(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pagesMain-notice-notice-details"],{"15b0":function(t,e,a){var i=a("2669");i.__esModule&&(i=i.default),"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("4f06").default;n("7764be08",i,!0,{sourceMap:!1,shadowMode:!1})},2669:function(t,e,a){var i=a("24fb");e=i(!1),e.push([t.i,"a[data-v-51851ae0]{text-decoration:none}.content[data-v-51851ae0]{display:flex;flex-direction:column;align-items:center;justify-content:center}.logo[data-v-51851ae0]{height:%?200?%;width:%?200?%;margin-top:%?200?%;margin-left:auto;margin-right:auto;margin-bottom:%?50?%}.text-area[data-v-51851ae0]{display:flex;justify-content:center}.title[data-v-51851ae0]{font-size:%?36?%;color:#8f8f94}.banner-img[data-v-51851ae0]{width:100%}.news[data-v-51851ae0]{width:100%}.news-detail[data-v-51851ae0]{overflow:hidden}.news-detail .news-detail-title[data-v-51851ae0]{overflow:hidden;padding:%?20?%}.news-detail .news-detail-title h3[data-v-51851ae0]{font-size:%?36?%;\n\t/* line-height: 100upx; */margin:%?30?% 0}.news-detail .news-detail-title p[data-v-51851ae0]{margin:0;font-size:#c9c9c9;float:left;font-size:%?28?%;margin-right:%?10?%}.news-detail .news-detail-title span[data-v-51851ae0]{margin:0;font-size:#c9c9c9;float:left;font-size:%?28?%;margin-right:%?10?%;line-height:%?40?%}.news-detail .news-detail-title p[data-v-51851ae0]{margin-right:%?40?%;line-height:%?40?%}.news-detail-container[data-v-51851ae0]{padding:%?20?%;border-bottom:1px solid #f5f5f5}.news-detail-container uni-image[data-v-51851ae0]{width:100%;margin-bottom:%?20?%}.news-detail-container p[data-v-51851ae0]{font-size:%?32?%;margin:0;margin-top:%?10?%;text-indent:2em}.news-detail .news-detail-title .star[data-v-51851ae0]{float:right}.star uni-image[data-v-51851ae0]{height:%?32?%;width:%?32?%;margin-right:%?10?%;-webkit-transform:translateY(%?5?%);transform:translateY(%?5?%)}.news-file[data-v-51851ae0]{padding:0 5%}.news-file h3[data-v-51851ae0]{font-size:%?36?%;line-height:%?70?%;margin-bottom:%?30?%}.news-file-box[data-v-51851ae0]{float:left;margin-right:%?30?%}.news-file-box uni-image[data-v-51851ae0]{height:%?100?%;width:%?100?%;margin-right:%?20?%;float:left}.news-file-box p[data-v-51851ae0]{float:left;font-size:%?30?%;left:%?40?%;margin-top:%?10?%}",""]),t.exports=e},"4e0a":function(t,e,a){"use strict";a.r(e);var i=a("6649"),n=a("7c4a");for(var o in n)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return n[t]}))}(o);a("e7ee");var s=a("f0c5"),l=Object(s["a"])(n["default"],i["b"],i["c"],!1,null,"51851ae0",null,!1,i["a"],void 0);e["default"]=l.exports},"60f8":function(t,e,a){"use strict";(function(t){a("7a82");var i=a("4ea4").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=i(a("9839")),o={components:{uniSearchBar:n.default},data:function(){return{list2:[],images:[{src:"../../static/djxw.png"}],background:["color1","color2","color3"],indicatorDots:!1,autoplay:!0,interval:2e3,duration:500,msg:""}},onLoad:function(e){var a=this,i=e.id;this.$server.postJSON("/notice/detail",{token:getApp().globalData.token,id:i},(function(e){a.list2=e.data.result,uni.stopPullDownRefresh(),a.msg=e.data.result.notice.content,t("log",a.list2.notice," at pagesMain/notice/notice-details.vue:67")}))},methods:{}};e.default=o}).call(this,a("0de9")["log"])},6649:function(t,e,a){"use strict";a.d(e,"b",(function(){return i})),a.d(e,"c",(function(){return n})),a.d(e,"a",(function(){}));var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",[a("v-uni-view",{staticClass:"uni-padding-wrap"},[a("v-uni-view",{staticClass:"news"},[a("v-uni-view",{staticClass:"news-detail"},[a("v-uni-view",{staticClass:"news-detail-title"},[a("h3",[t._v(t._s(t.list2.notice.title))]),a("p",{staticStyle:{color:"rgb(156,156,156)"}},[t._v(t._s(t.list2.notice.author))]),a("span",{staticStyle:{color:"rgb(156,156,156)"}},[t._v(t._s(t.list2.notice.time))]),a("br"),a("p",{staticStyle:{color:"rgb(0, 85, 255)"}},[t._v(t._s(t.list2.read)+"人已读，"+t._s(t.list2.unread)+"人未读")])]),a("v-uni-view",{staticClass:"news-detail-container"},[a("v-uni-view",{staticClass:"clear"}),a("p",{domProps:{innerHTML:t._s(t.msg)}})],1)],1)],1),a("div",{staticClass:"clear",staticStyle:{height:"200upx"}})],1)],1)},n=[]},"7c4a":function(t,e,a){"use strict";a.r(e);var i=a("60f8"),n=a.n(i);for(var o in i)["default"].indexOf(o)<0&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e["default"]=n.a},e7ee:function(t,e,a){"use strict";var i=a("15b0"),n=a.n(i);n.a}}]);