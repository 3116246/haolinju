
//  给Array设置map,indexOf,filter方法
if (!('map' in Array.prototype)) {
    Array.prototype.map = function (mapper, that /*opt*/) {
        var other = new Array(this.length);
        for (var i = 0, n = this.length; i < n; i++)
            if (i in this)
                other[i] = mapper.call(that, this[i], i, this);
        return other;
    };
}
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (elt /*, from*/) {
        var len = this.length >>> 0;

        var from = Number(arguments[1]) || 0;
        from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
        if (from < 0)
            from += len;

        for (; from < len; from++) {
            if (from in this &&
          this[from] === elt)
                return from;
        }
        return -1;
    };
}
if (!Array.prototype.filter)
{
  Array.prototype.filter = function(fun /*, thisArg */)
  {
    "use strict";

    if (this === void 0 || this === null)
      throw new TypeError();

    var t = Object(this);
    var len = t.length >>> 0;
    if (typeof fun !== "function")
      throw new TypeError();

    var res = [];
    var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
    for (var i = 0; i < len; i++)
    {
      if (i in t)
      {
        var val = t[i];

        // NOTE: Technically this should Object.defineProperty at
        //       the next index, as push can be affected by
        //       properties on Object.prototype and Array.prototype.
        //       But that method's new, and collisions should be
        //       rare, so use the more-compatible alternative.
        if (fun.call(thisArg, val, i, t))
          res.push(val);
      }
    }

    return res;
  };
}

function showLoading () {
   $(".loading").show();
}
function hideLoading () {
   $(".loading").hide();
}

$(document).ready(function () {
    $(document).ajaxStart(function () {
        showLoading();
    });
    $(document).ajaxComplete(function () {
        hideLoading();
    });
});

var showDialog = {
	 style:"width: 450px; top:65px;",
	 type:"ok",
	 title:"",
	 message:"",
	 callback:null,
	 //正确时的对话框
	 Success:function(title,message){
	 	title = title==""?"提示":title;
	 	this.type="ok";
	 	this.title = title,
	 	this.message = message;
    var html = this.getHtml();
	 	$("body").append(html);
	 },
	 Error:function(title,message){
	 	 title = title==""?"错误":title;
	 	 this.type="Error";
	 	 this.title = title,
	 	 this.message = message;
     var html = this.getHtml();
	 	 $("body").append(html);	 	 
	 },
	 Query:function(title,message){
	 	 title = title==""?"确认消息":title;
	 	 this.type="query";
	 	 this.title = title,
	 	 this.message = message;
	 	 var html = this.getHtml();
	 	 $("body").append(html);
	 },
	 getHtml:function(){
	 	 var html = new Array();
	 	 html.push("<div class='dialogBox' style='display:block;'>");
	 	 html.push("<div class='background'></div>");
	 	 html.push("  <div class='dialog' style='"+this.style+"'>");
	 	 html.push("    <div class='title'>"+this.title+"</div>");
	 	 html.push("    <span class='close-tip-icon' style='display:block;' onclick='showDialog.Closed(this);' title='关闭'></span>");
	 	 html.push("    <div class='content' style='width:auto;height:75px;'>"+this.message+"</div>");
	 	 html.push("    <div class='operation'>");
	 	 html.push("      <button class='btn btnGreen' onclick='showDialog.confirm(this);'>确定</button>");
	 	 if (this.type=="query")
	 	   html.push("     <button class='btn btnGreen' onclick='showDialog.Closed(this);'>关闭</button>");
     html.push("    </div></div></div>");
     return html.join("");
	 },
	 Closed:function(ev){
	 	 $(ev).parents(".dialogBox").remove();
	 	 if ( this.callback != null)
	 	   this.callback("No");
	 },
	 confirm:function(ev){
	 	 $(ev).parents(".dialogBox").remove();
	 	 if (this.callback != null)
	 	   this.callback("Yes");
	 }
};
