$(function () {
    var navSwiper = new Swiper('.dj_swiper', {
        //freeMode: true,
        slidesPerView: 'auto',
        //freeModeSticky: true,
        slideToClickedSlide: true,
        speed: 500,
    });
    var tabsSwiper = new Swiper('#tabs-container', {
        speed: 500,
        on: {
            slideChangeTransitionStart: function () {
                $(".dj_swiper .active").removeClass('active');
                $('.dj_line').eq(this.activeIndex).addClass('active');
                tabsSwiper.slideTo(this.activeIndex);
            }
        }
    });

});
var dj_move_show_list = $('.dj-move-show-list');
var dj_mask_div = $('.dj_mask_div');
$('.dj-check-all').click(function () {
    dj_move_show_list.removeClass("am-hide");
    dj_move_show_list.addClass("am-block");
    dj_mask_div.removeClass("am-hide");
    dj_mask_div.addClass("am-block");
});
function showAllNav(){
    dj_move_show_list.removeClass("am-hide");
    dj_move_show_list.addClass("am-block");
    dj_mask_div.removeClass("am-hide");
    dj_mask_div.addClass("am-block");
}
$('.dj-move-hide-btn').click(function () {
    dj_move_show_list.removeClass("am-block");
    dj_move_show_list.addClass("am-hide");
    dj_mask_div.removeClass("am-block");
    dj_mask_div.addClass("am-hide");
});

$('.dj_mask_div').click(function () {
    dj_move_show_list.removeClass("am-block");
    dj_move_show_list.addClass("am-hide");
    dj_mask_div.removeClass("am-block");
    dj_mask_div.addClass("am-hide");
});

$(".dj_swiper .swiper-slide").on('click', function (e) {
    e.preventDefault();
    $(".dj_swiper .active").removeClass('active');
    $(this).find('.dj_line').addClass('active');
    let forum_id = $('.swiper-slide .active')[0].dataset.id;
    if (current_forum_id == forum_id) {
        return false;
    }
    current_forum_id = forum_id;
    page = 1;
    $('#contentList').html('');
    $('#load').removeClass('am-hide');
    get_forum_post_list(forum_id, page, '#contentList', '#load');
    /*$(this).addClass('active')
    tabsSwiper.slideTo($(this).index()); */
});

//bbs列表底层操作
$('.dj_bbs_list a').click(function () {
    var str = ['zhuanfa', 'pinglun', 'dianzan'], name = $(this).find('span')[0].className, index = str.indexOf(name);
    if (index != -1) {
        $(this).find('span').removeClass(name).addClass(name + '_active')
    } else {
        names = $.trim(name.replace('_active', ''));
        $(this).find('span').removeClass(name).addClass(names)
    }
});

$('.clickJump').click(function () {
    let url = $(this).data('url');
    location.href = url;
});

function click_jump(url) {
}

function get_forum_post_list(forum_id, page, label_id, load_id) {
    let data = {
        forum_id: forum_id,
        page: page,
    }
    $(load_id).prepend('<i class="am-icon-spinner am-icon-spin"></i>');
    $(load_id).attr('disabled', 'disabled');
    $.ajax({
        url: '/wap/forum/ajaxPostList',
        data: data,
        success: (res) => {
            $(label_id).append(res);
            $(load_id).find('i').remove();
            if (res == '') {
                // $(load_id).addClass('am-hide');
                $(load_id).html('已加载完');
                $(load_id).css('background-color', '#eeeeee');
                $(load_id).css('border', 'none');
            } else {
                $(load_id).css('background-color', '');
                $(load_id).css('border-color', '');
                $(load_id).html('加载更多');
                $(load_id).attr('disabled', false);
            }
        }
    });
}

//ImgD:要放图片的img元素，onload时传参可用this

//h:img元素的高度，像素

//w:img元素的宽度，像素
function autosize2(ImgD, h, w) {
    var image = new Image();
    image.src = ImgD.src;
    image.onload = function () {
        if (image.width < w && image.height < h) {
            ImgD.width = image.width;
            ImgD.height = image.height;
        } else {
            if (w / h <= image.width / image.height) {
                ImgD.width = w;
                ImgD.height = w * (image.height / image.width);
            } else {
                ImgD.width = h * (image.width / image.height);
                ImgD.height = h;
            }
        }

        ImgD.style.width = w + 'px';
        ImgD.style.margin = 'auto';

        //图片居中，IE8有效果，IE9,火狐无效果，请在页面用table居中
        ImgD.style.paddingLeft = (w - ImgD.width) / 2 + 'px';   //20是指padding-left和padding-right距离的和
        ImgD.style.paddingTop = (h - ImgD.height) / 2 + 'px';     //20是指padding-top和padding-bottom距离的和
    }
}