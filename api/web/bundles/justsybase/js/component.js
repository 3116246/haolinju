//----------加载组件-----------------
function LoadComponent(Adivid, Aurl, data,func) 
{
	var t=null;
	if(typeof(Adivid)=="string") t=$("#"+Adivid);
	else t=Adivid;
  	t.append("<div class='urlloading'><div /></div>");
  	t.load(Aurl, 
  		$.extend({t: new Date().getTime()}, data),
    	function () 
    	{
      		$("#"+Adivid+" urlloading").remove();
      		if(func!=null) func();
    	}
    );
}
//----------显示新信息数量-----------------
//data格式：[{circle_id, num}]
function SetNewNumDisplay_conv_pc(data)
{
	if($(".Smescommend").length>0 && data.length>0){
		$(".Smescommend").show();
	}
	SetNewNumDisplay(data,"");
}
function SetNewNumDisplay_conv(data)
{
  SetNewNumDisplay(data, "red"); 
} 
function SetNewNumDisplay_atme(data)
{
  SetNewNumDisplay(data, "#7BBD3B"); 
} 
function SetNewNumDisplay_reply(data)
{
  SetNewNumDisplay(data, "#7BBD3B"); 
} 
function SetNewNumDisplay(data, bgcolor) 
{
  var allnum = $.trim($("#allcircle_new_num").text());
  allnum = allnum==""? 0:allnum*1;
  for (var i = 0; i<data.length; i++)
  {
  	var $tmp = $(".tip_new_num[circle_id='"+data[i].circle_id+"']");
    $tmp.text(data[i].num).show(); 
    var $tmpP=$tmp.parent(),$ind = $tmpP.index();
    if($ind>1)
      $tmp.parent().insertAfter($("#mycircle div:eq(0)")).show();
    if($ind>4){ //将有提示的圈子提到第一页中        
        $("#mycircle div:eq(5)").hide();
    }
    allnum += new Number(data[i].num);
  }
  if (allnum > 0) $("#allcircle_new_num").text(allnum).show();
}

//----------搜索框相关函数-----------------
var SearchEdit_items = null;
function SearchEdit_source(query, process) 
{
  if (SearchEdit_items) return SearchEdit_items;
  
  $.getJSON(SearchEdit_source_url, {t: new Date().getTime()}, function (data) 
  {
    SearchEdit_items = data;
    for (var i=0; i<SearchEdit_items.length; i++)
    {
//      SearchEdit_items[i].toString = function(){return JSON2.stringify(this);};
      SearchEdit_items[i].index = i; 
      SearchEdit_items[i].toString = function(){return this.index;};
    }
    process(SearchEdit_items);
  })
  
  return null;
}

function SearchEdit_highlighter(item) 
{
  var re = item;
  var errorimg = g_resource_context+'bundles/fafatimewebase/images/no_photo.png';
  
  if (item.datatype == 1)
  {    
    re = "<span><img src='"+item.photo_url+"' style='width:48px; height:48px; margin-right:10px' onerror='this.src=\""+errorimg+"\"'/>"+item.nick_name+"</span>";
  }
  else if (item.datatype == 2)
  {
    re = "<span><img src='"+item.group_photo_url+"' style='width:48px; height:48px; margin-right:10px' onerror='this.src=\""+errorimg+"\"'/>"+item.group_name+"</span>";
  }
  else if (item.datatype == 3)
  {
    re = "<span><img src='"+item.logo_url+"' style='width:48px; height:48px; margin-right:10px' onerror='this.src=\""+errorimg+"\"'/>"+item.circle_name+"</span>";
  }
  
  return re;
}

function SearchEdit_matcher(item) 
{
  if (item.datatype == 1)
  {    
    return ~item.login_account.split('@')[0].toLowerCase().indexOf(this.query.toLowerCase()) 
        || ~item.nick_name.toLowerCase().indexOf(this.query.toLowerCase());
  }
  else if (item.datatype == 2)
  {
    return ~item.group_name.toLowerCase().indexOf(this.query.toLowerCase());
  }
  else if (item.datatype == 3)
  {
    return ~item.circle_name.toLowerCase().indexOf(this.query.toLowerCase());
  }
  
  return false;
}

function SearchEdit_sorter(items) 
{
  return items;
}

function SearchEdit_updater(Aitem) 
{  
  var item = this.source[Aitem];//JSON2.parse(Aitem);
  var re = item;
  
  if (item.datatype == 1)
  {    
    re = item.nick_name;
    window.location.href = SearchEdit_user_url_pre + "/" + item.login_account;
  }
  else if (item.datatype == 2)
  {
    re = item.group_name;
    window.location.href = SearchEdit_group_url_pre + "/" + item.group_id;
  }
  else if (item.datatype == 3)
  {
    re = item.circle_name;
    window.location.href = SearchEdit_circle_url_pre + item.network_domain;
  }
  
  return re;
}

/*
 * 焦点移至下一个input控件
 * @ipts 焦点移动的input集合
 * @n 当前焦点所在的input
 */
function focusMoveToNext(ipts, n)
{
  var nxt = ipts.index(n)+1;
  var nxtNode = ipts.eq(nxt);
  if (nxtNode == null) return;
  if (nxtNode.attr("disabled"))
  {
    focusMoveToNext(ipts, nxtNode);
  }
  else
  {
    ipts.eq(nxt).focus();
  }
}
//显示错误信息
function setErrInfo(pn, info)
{
  pn.addClass("non-validated");
  pn.find(".alert_content").text(info);
  pn.find(".alert").slideDown(0,function()
  {
    var n = $(this);
    setTimeout(function()
    {
      n.css("display","none").fadeOut();
    },2000);
  });
  pn.find(".alert-ico").attr("src",g_resource_context+"bundles/fafatimewebase/images/error.png").show();
}
//清除错误信息
function clearErrInfo(pn)
{
  pn.removeClass("non-validated");
  pn.find(".alert_content").text("");
  pn.find(".alert").css("display","none");
  pn.find(".alert-ico").attr("src",g_resource_context+"bundles/fafatimewebase/images/ok.png").show();
}
//验证是否是合法邮箱地址
function validEmail(mail)
{
  var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
  return reg.test(mail);
}
//全角转半角
function DBC2SBC(str, flag)
{
  var result = '';
  str = str.replace(/。/g,"．");
  for(var i=0;i<str.length;i++)
  {
    code = str.charCodeAt(i);
    if(flag)
    {
      if(code >= 65281 && code <= 65373) result += String.fromCharCode(str.charCodeAt(i) - 65248);
      else if(code == 12288) result += String.fromCharCode(str.charCodeAt(i) - 12288 + 32);
      else result += str.charAt(i);
    }
    else
    {
      if(code >= 33 && code <= 126) result += String.fromCharCode(str.charCodeAt(i) + 65248);
      else if(code == 32) result += String.fromCharCode(str.charCodeAt(i) - 32 + 12288);
      else result += str.charAt(i);
    }
  }
  return result;
}