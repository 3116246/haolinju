//----------加载组件-----------------
var LoadComponent=function (Adivid, Aurl, data) 
{
  $("#"+Adivid).append("<div class='urlloading'><div /></div>");
  $("#"+Adivid).load(Aurl, $.extend({t: new Date().getTime()}, data),
    function () 
    {
      $("#"+Adivid+" urlloading").remove();
    });
};

//jquery easyui loading css效果
var ajaxLoading=function (){
  $('<div class="datagrid-mask" style="z-index:999998;"></div>').css({display:"block",width:"100%",height:$(window).height()}).appendTo("body");
  $('<div class="datagrid-mask-msg" style="font-size:12px;z-index:999999;"></div>')
  .css({display:"block",left:($(document.body).outerWidth(true) - 190) / 2,top:($(window).height() - 45) / 2})
  .html("正在处理，请稍候。。。").appendTo("body");
};
var ajaxLoadEnd=function (){
  $(".datagrid-mask").remove();
  $(".datagrid-mask-msg").remove();
};

var getQueryString=function(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
}

//指定区域内的图片延迟加载 option{id:'区域控件ID',src:'默认图片地址'}
var delayload = function (option) {
    //读取参数
    //图片未加载时显示的图片
    var src = option.src ? option.src : '';
    //指定那些id下的img元素使用延迟显示
    var id = option.id ? option.id : [];
    //图片列表
    var imgs = [];
    //获得所有的图片元素
    for (var i = 0; i < id.length; i++) {
        var idbox = document.getElementById(id[i]), _imgs;
        if (idbox && (_imgs = idbox.getElementsByTagName('img'))) {
            for (var t = 0; t < _imgs.length; t++) {
                imgs.push(_imgs[t]);
            }
        }
    }
    //将所有的图片设置为指定的loading图片
    for (var i = 0; i < imgs.length; i++) {
        //图片本来的图片路径放入_src中
        imgs[i].setAttribute('_src', imgs[i].src);
        imgs[i].src = src;
    }
    //取元素的页面绝对 X位置
    var getLeft = function (el) {
        var left = 0;
        do {
            left += el.offsetLeft;
        } while ((el = el.offsetParent).nodeName != 'BODY');
        return left;
    };
    //取元素的页面绝对 Y位置
    var getTop = function (el) {
        var top = 0;
        do {
            top += el.offsetTop;
        } while ((el = el.offsetParent).nodeName != 'BODY');
        return top;
    };
    //是否为ie，并读出ie版本
    var isIe = !!navigator.userAgent.match(/MSIE\b\s*([0-9]\.[0-9]);/img);
    isIe && (isIe = RegExp.$1);
    //是否为chrome
    var isGoo = !!navigator.userAgent.match(/AppleWebKit\b/img);
    //获得可以触发scroll事件的对象
    var box = isIe ? document.documentElement : document;
    //body元素的scroll事件
    var onscroll = box.onscroll = function () {
        //读取滚动条的位置和浏览器窗口的显示大小
        var top = isGoo ? document.body.scrollTop : document.documentElement.scrollTop,
            left = isGoo ? document.body.scrollLeft : document.documentElement.scrollLeft,
            width = document.documentElement.clientWidth,
            height = document.documentElement.clientHeight;
        //对所有图片进行批量判断是否在浏览器显示区域内
        for (var j = 0; j < imgs.length; j++) {
            var _top = getTop(imgs[j]), _left = getLeft(imgs[j]);
            //判断图片是否在显示区域内
            if (_top >= top &&
                _left >= left &&
                _top <= top + height &&
                _left <= left + width) {
                var _src = imgs[j].getAttribute('_src');
                //如果图片已经显示，则取消赋值
                if (imgs[j].src !== _src) {
                    imgs[j].src = _src;
                }
            }
        }
    };
    var load = new Image();
    load.src = src;
    load.onload = function () {
        onscroll();
    };
};
//out1,out2 是HTML控件ID值
//src 默认显示图片(在网络状况不好的情况下)
//delayload({ id: ['out1', 'out2'], src: globalPath + '/Content/images/no_photo.png' });



/*****************
*全局通用模块
******************/

var globalPath = "";
//获取当前访问地址 htttp://www.baidu.com?search=s  返回www.baidu.com
var getHost = function (url) {
    var host = "null";
    if (typeof url == "undefined" || null == url) url = window.location.href;
    var regex = /.*\:\/\/([^\/]*).*/;
    var match = url.match(regex);
    if (typeof match != "undefined" && null != match) host = match[1];
    return host;
};
//true 为空 false 不为空
var IsNullOrEmpty = function (val) {
    if (val == null || $.trim(val) == "" || $.trim(val) == "undefined" || val == []) return true;
    return false;
};
//检测是否是邮箱
var checkEmail = function (val) {
    var regex=/^(.+)@(.+)$/;
    if(!(regex.test(val))) return false;
    return true;
}
//检测是否是手机号码
var checkMobile = function(val){
    var regex1=/^1\d{10}$/;
    if(!(regex1.test(val))) return false;
    return true;
}
var checkPhone = function(val){
    var regex=/(^[0-9]{3,4}\-[0-9]{7,8}$)|(^[0-9]{7,8}$)|(^\([0-9]{3,4}\)[0-9]{3,8}$)|(^0{0,1}13[0-9]{9}$)/;
    if(!(regex.test(val))) return false;
    return true;
}
//清空字符串前后空格
var trim = function (val) {
    return val.replace(/(^[\s\xA0]+|[\s\xA0]+$)/g, '');
};
//图片类型
var imageType = {
    jpg: "jpg",
    jpeg: "jpeg",
    gif: 'gif',
    png: "png"
};

//isfull true 包含年 2013-01-01   false 不包含年 01-01
var getDateTime = function (isfull) {
    var today = new Date();
    var year = today.getFullYear();
    var month = today.getMonth() + 1;
    var day = today.getDate();
    var hours = today.getHours();
    var minutes = today.getMinutes();
    var timeValue1 = ((hours < 10) ? "0" : "") + hours + "";
    timeValue1 += ((minutes < 10) ? ":0" : ":") + minutes + "";
    var timeValue2 = "";
    if(isfull) timeValue2=year + "-";
    timeValue2 += ((month < 10) ? "0" : "") + month + "";
    timeValue2 += ((day < 10) ? "-0" : "-") + day + "";
    return timeValue2 + " " + timeValue1;
};

var $doc = function (id) {
    return document.getElementById(id);
};
var $doc_html = function(id) {
    return document.getElementById(id).innerHTML;
};
/**************
*Cookie公用模块
***************/

//此 cookie 将被保存 30 天
var days = 30;

//两个参数，一个是cookie的名子，一个是值
var setCookie = function (name, value) {
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + value + "; expires=" + exp.toGMTString();
    return true;
};
//两个参数，一个是cookies的Keys，一个是Vals
var setCookies = function (keys, vals) {
    var cookie = "";
    for (var i = 0; i < keys.length; i++) {
        if (cookie != "")
            cookie += ";";
        cookie += keys[i] + "=" + vals[i];
    }
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = cookie + "; expires=" + exp.toGMTString();
    return true;
};
//取cookies函数
var getCookie = function (name) {
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    if (arr != null) return unescape(arr[2]);
    return null;
};
//删除cookie
var delCookie = function (name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if (cval != null) document.cookie = name + "=" + cval + "; expires=" + exp.toGMTString();
};
