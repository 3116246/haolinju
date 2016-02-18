//企业主页中，当发布一条信息时会被调用，以取得最新的信息，并显示
function EnterpriseHome_OnPublished(Aconv_id,Aconv_type) 
{
  var el_ids = ["two1", "two2", "two3","two4","two5","two6"];
  
  for(var i=0; i<el_ids.length; i++)
  {
  	var tab_type=$("#con_"+el_ids[i]).attr('type');
  	if(typeof(tab_type)=='undefined' || tab_type==Aconv_type || tab_type=="conv"){
    	EnterpriseHome_OnPublished_GetNewConv(el_ids[i], Aconv_id);
    }
  }
}

function EnterpriseHome_OnPublished_GetNewConv(el_id, Aconv_id)
{
  var $unreadconvcountdiv = $(".unreadconvcount div"); 
  
  if ($unreadconvcountdiv.length > 0) // 若有未读的取出
  {
    $unreadconvcountdiv.click();
    return;  
  }
  
  var $el = $("#"+el_id+"[isloaded='1']");
  if ($el.length > 0)
  {
    var $conv_box = $("#con_"+el_id+" ul.conv_box[type='normalbox']");
    var $twotab=$("#"+el_id);
    //$conv_box.prepend("<div class='urlloading'><div /></div>");
    $.get($el.attr("onpublishurl"), {conv_id: Aconv_id, t: new Date().getTime()}, function (data) 
    {
      //$(".urlloading", $conv_box).remove();
      $li = $(data).children().children("li").css("display", "none");
      $li.children("input:hidden.endid").remove();
      var $div=$li.find("div.convbox");
      if($div.attr('top')!=$div.attr('conv_id') || $("ul.conv_box[type='topbox']").length ==0){
	      $conv_box.prepend($li);
	      $li.fadeIn("slow");
	    }
	    else{getTopConv($twotab);}
      //$li.slideDown("slow");
      if($(data).find(".fafa-map").length>0)
          fafaMap.AutoShow();
      overLengthAct();
      setListAct();
      registermoreoper();
    });    
  }
}
//筛选
function ConvFilter_OnClick(sender) 
{
  var $sender = $(sender);
  setDisplayTab($("#"+$sender.attr("targetid"))[0]);
  $sender.parent().parent().children().removeClass("topic_in");
  $sender.parent().addClass("topic_in");
}
function EnterpriseHome_Document_OnScroll() 
{
  var $document = $(document); //document
  if ($document.scrollTop() + $(window).height() >= $document.height()  - 100)
  {
    var $twotab = $("#two1.hover,#two2.hover,#two3.hover,#two4.hover,#two5.hover,#two6.hover");

    if ($twotab.attr("isscrollloading")) return;

    var scrollloadnum = $twotab.attr("scrollloadnum");
    if (!scrollloadnum) scrollloadnum = 1;
    if (scrollloadnum>= 3) return;
    
    var $twoid = $twotab.attr("id");
    var $two_hover = $("#con_"+$twoid);
    var hasmore = $twotab.attr("hasmore");
    var pageindex = $twotab.attr("pageindex");
    if (!pageindex) pageindex = 1;
    
    if ($two_hover.children("div.urlloading").length > 0) return;
    
    if (hasmore && hasmore == "0") return;
    
    $twotab.attr("isscrollloading", "1");
    
    var endid = GetConvMinID($twotab,$two_hover);
    
    $two_hover.append("<div class='urlloading'><div /></div>");
    $.get($twotab.attr("loadurl"), {network_domain: g_curr_network_domain, endid: endid, pageindex: pageindex, t: new Date().getTime()}, function (data) 
    {
      $two_hover.find("div.urlloading").remove();
      var $lis = $(data).children("ul").children("li");
      $twotab.attr("endid", $lis.find("input:hidden.endid").val());
      $twotab.attr("hasmore", $lis.length);
      $two_hover.children("div").children("ul.conv_box[type='normalbox']").append($lis);
      $twotab.attr("scrollloadnum", ++scrollloadnum);
      $twotab.removeAttr("isscrollloading");
      
      //超长动态显示控制
      overLengthAct();
      setListAct();
      registermoreoper();
      //增加分页
      if ((pageindex > 1 && $lis.length < 15) || scrollloadnum >= 3)
      {
        GenPageHtml(pageindex, $two_hover.children("div"), $twotab.attr("loadurl"));
      }
    });
  }
}

//生成分页
function GenPageHtml(pageindex, $parentdiv, Aurl) 
{
  if ($parentdiv.find("#pagination450").length > 0) return;
  
  //取得前450条的记录数，生成分页代码
  $.get(Aurl, {network_domain: g_curr_network_domain, pre450num: "1", t: new Date().getTime()}, function (data)
  {
    var pagenum = parseInt((data.pre450num - 1) / 45) + 1;
    var pagehtml1 = '<div id="pagination450" class="pagination pagination-right"><ul>';
    var pagehtml2 = '</ul></div>';
    var pagehtmlli = '';
    
    for (var i=1; i<=pagenum; i++)
    {
      pagehtmlli += '<li '+(pageindex == i ? 'class="active"' : '')+'><a href="javascript:void(0);" onclick="EnterpriseHome_OnPage(this)">'+i+'</a></li>';
    }
    $parentdiv.append(pagehtml1 + pagehtmlli + pagehtml2);
  });
}
function ShowPageHtml($parentdiv) 
{
  $parentdiv.children("div.pagination").show();
}

function EnterpriseHome_OnPage(sender)
{
  var $sender = $(sender);
  var pageindex = $sender.text();
  
  var $twotab = $("#two1.hover, #two2.hover, #two3.hover,#two4.hover,#two5.hover,#two6.hover");
  var $two_hover = $("#con_"+$twotab.attr("id"));
  var endid = GetConvMinID($twotab,$two_hover);
  if(pageindex=='1' && $twotab.attr('gettopurl')!='undefined' && $twotab.attr('gettopurl')!=''){
  	$twotab.attr('isloadtop','0');
  }
    
  $twotab.attr("isscrollloading", "1");
  $twotab.attr("scrollloadnum", "1");
  $twotab.removeAttr("hasmore");
  $twotab.removeAttr("endid");
  $twotab.attr("pageindex", pageindex);
  $twotab.attr("maxid", GetConvMaxID($twotab, $two_hover));
  $two_hover.children().remove();
  
  $two_hover.append("<div class='urlloading'><div /></div>");
  $two_hover.load($twotab.attr("loadurl"), {network_domain: g_curr_network_domain, pageindex: pageindex}, function () 
  {
    $two_hover.find("div.urlloading").remove();
    $twotab.removeAttr("isscrollloading");
  });
}
//信息提示
var _InfoHint= '<div id="infohint" style="border: 0px none; min-height: 0px; padding: 0px; text-align: center; cursor: pointer;"><div style="background-color: #FEFFE2; border: 1px solid #F8F09E; color: #CF9D2F;" >$info$</div></div>';
var _InfoHintTimer=null;
function ShowInfoHint(txt,longtime)
{
	    var $twotab = $("#two1.hover, #two2.hover, #two3.hover,#two4.hover,#two5.hover,#two6.hover");
      var $conv_box = $("#con_"+$twotab.attr("id"));
      if($conv_box.find("#infohint").length==0)
         $conv_box.prepend(_InfoHint.replace("$info$", txt));
      else
      	 $conv_box.find("#infohint").html(_InfoHint.replace("$info$", txt));
      if(_InfoHintTimer!=null) window.clearTimeout(_InfoHintTimer);
      _InfoHintTimer=window.setTimeout(function(){$("#infohint").remove()},longtime);
}


//取得当前页面最新未读数
var _UnreadTemplate = '<div class="unreadconvcount" style="border: 0px none; min-height: 0px; padding: 0px; text-align: center; cursor: pointer;"><div style="background-color: #FEFFE2; border: 1px solid #F8F09E; color: #CF9D2F;" onclick="UnreadConvCount_OnClick(this)">你有 <span>$COUNT$</span> 条新信息，点击查看</div></div>';
var _LastReadSplitTemplate = '<li class="lastreadsplit" style="text-align: center; min-height: 0px; padding: 10px 0px;"><div><span class="hr_here" style="display: inline-block; height: 0px; border: 1px solid rgb(204, 204, 204);margin: 0px 10px;"></span><span>你上次看到这里了哟～</span><span class="hr_here" style="display: inline-block; height: 0px; border: 1px solid rgb(204, 204, 204);margin: 0px 10px;"></span></div></li>';
function EnterpriseHome_GetConvUnreadCount_Timeout() 
{
  var $twotab = $("#two1.hover, #two2.hover, #two3.hover,#two4.hover,#two5.hover,#two6.hover");
  if($twotab.length==0)
  {
  	window.setTimeout(EnterpriseHome_GetConvUnreadCount_Timeout, 1000*60*3);
  	return;
  }
  var $two_hover = $("#con_"+$twotab.attr("id"));
  var maxid = GetConvMaxID($twotab, $two_hover);
  if(typeof(maxid)=="undefined" || maxid==null || maxid=="" || maxid < 0){
  	window.setTimeout(EnterpriseHome_GetConvUnreadCount_Timeout, 1000*60*3);
  }
  var Aurl = $twotab.attr("loadunreadurl");
  
  $.getJSON(Aurl, {network_domain: g_curr_network_domain, maxid: maxid, onlycount: "1", t: new Date().getTime()}, function (data) 
  {
    var $conv_box = $two_hover;
    if($conv_box.attr('class').indexOf('hover')>-1){
	    $conv_box.children("div.unreadconvcount").remove();
	    if (data.unreadcount > 0)
	      $conv_box.prepend(_UnreadTemplate.replace("$COUNT$", data.unreadcount));
	    
	    window.setTimeout(EnterpriseHome_GetConvUnreadCount_Timeout, 1000*60*3);    //每3分钟取一次未读数
    }
  });
}
function GetConvMaxID($twotab, $two_hover) 
{
  return Math.max($twotab.attr("maxid")||-10, Math.max.apply(null, $two_hover.find("div.convbox").map(function () { return $(this).attr('conv_id'); }).toArray()));
}
function GetConvMinID($twotab,$two_hover){
	return Math.max(-10,$twotab.attr("endid")||Math.min.apply(null, $two_hover.find("ul.conv_box[type='normalbox']").find("div.convbox").map(function () { return $(this).attr('conv_id'); }).toArray()));
}
function UnreadConvCount_OnClick(sender) 
{
  var $twotab = $("#two1.hover, #two2.hover, #two3.hover,#two4.hover,#two5.hover,#two6.hover");
  var $two_hover = $("#con_"+$twotab.attr("id"));
  var Aurl = $twotab.attr("loadunreadurl");
  var $two = $two_hover;
  
  $two.children(".unreadconvcount").html('<div style="background-color: #FEFFE2; border: 1px solid #F8F09E; color: #CF9D2F;" onclick="UnreadConvCount_OnClick(this)">正在努力为您加载数据，请稍候...</div>');
  //$conv_box.children(".lastreadsplit").remove();
  //$conv_box.prepend("<div class='urlloading'><div /></div>");
  $.get(Aurl, {'trend':manager_trend,network_domain: g_curr_network_domain, maxid: GetConvMaxID($twotab, $two_hover), t: new Date().getTime()}, function (data) 
  {
  	$two.children(".unreadconvcount").remove();
  	$li = $(data).children().children("li").css("display", "none");
    $li.children("input:hidden.endid").remove();
    var $conv_box=$two.find("ul.conv_box[type='normalbox']");
    $conv_box.children(".lastreadsplit").remove();
    //$conv_box.children(".urlloading").remove();
    $conv_box.prepend(_LastReadSplitTemplate);
		var html=[],num=0;
		for(var i=0;i<$li.length;i++){
			var $div=$($li[i]).find('div.convbox');
			if($div.attr('top')!=$div.attr('conv_id')){
				html.push("<li class='clearfix'>"+$($li[i]).html()+"</li>");
			}
			else{num++;}
		}
		var $html=$(html.join(''));
    $conv_box.prepend($html);
    if(num>0)getTopConv($twotab);
    $html.fadeIn("slow");
    overLengthAct();
      setListAct();
      registermoreoper();
  });
}


////显示评论
//function showReply(url)
//{
//  $('#Viewreply').show();
//  $('#load_img').show();
//  $('#together_content').html("");
//  $('#replay_content').html("");
//  $('#pagecontrol').html("");
//  $.post(url,function(result)
//  {
//    $('#load_img').hide();
//    $('#together_content').html(result.together);
//    $('#replay_content').html(result.replay);
//    $('#pagecontrol').html(result.page);
//  });
//}
//
////评论翻页
//function replyPage(url)
//{
//   $('#replay_content').html('');
//   $('#load_img').show();
//   $.post(url,function(result)
//   {
//     $('#replay_content').html(result);
//     $('#load_img').hide();
//   });
//}

//显示活动详细
function ViewTogetherDetails(url)
{
  $('#loadimg').show();
  $('#togetherdetails').show();
  $('#viewtogether').html("<div>正在加载活动</div>");
  $.post(url,function(result)
  {
    $('#loadimg').hide();
    $('#viewtogether').html(result);
  });
}

//显示投票详细
function ViewVoteDetails(_url,id)
{
	var id_eles = $(".voteenter input:hidden:[value="+id+"]");
	if(id_eles.length >0)  //页面上已加载该投票数据时，直接定位
	{
		  /*var y = id_eles.parent().offset().top;
		  y = y-(id_eles.parent().parent().height())-200;
		  y=y<0?0:y;
		  document.body.scrollTop =y;
		  document.documentElement.scrollTop =y;
	    return;	*/  //统一采用弹出div方式。暂停自动定位方式
	}
  $('#votedetails #loadimg').show();
  $('#votedetails').show();
  $('#viewvote').html("<div>正在加载投票数据</div>");
  $.post(_url,function(result)
  {
    $('#votedetails #loadimg').hide();
    $('#viewvote').html(result);
  });
}

//进入应用
function inapp(appid)
{
   //{{ path('JustsyBaseBundle_appcenter_index', {'network_domain' : curr_network_domain,'appid':'00440'}) }}?callbackurl=http://www.baidu.com
 	 window.location = appcenter_index.replace("AAA",appid);
}

var updateLastLogintimeUrl = "";
//显示第一次进入提示向导
function LoadFirstWizard(_URL)
{
	  updateLastLogintimeUrl = _URL;
	  var offset = $("#content").offset();
	  var imgPath = "/bundles/fafatimewebase/images/firstWizard/";
	  var node = [{"text":imgPath+"0font.png","img":imgPath+"0.png","img_pos":[(offset.top+70)+"px",(offset.left+170)+"px"],"text_pos":[(offset.top+70+175)+"px",(offset.left+170+166)+"px"]},
	              {"text":imgPath+"1font.png","img":imgPath+"1.png","img_pos":[(offset.top-35)+"px",(offset.left-31)+"px"],"text_pos":[(offset.top-35+283)+"px",(offset.left-31+269)+"px"]},
	              {"text":imgPath+"2font.png","img":imgPath+"2.png","img_pos":[(offset.top-35)+"px",(offset.left+741)+"px"],"text_pos":[(offset.top-35+248)+"px",(offset.left+741-525)+"px"]},
	              {"text":imgPath+"3font.png","img":imgPath+"3.png","img_pos":[0,0],"text_pos":[253,250]},
	              {"text":imgPath+"4font.png","img":imgPath+"4.png","img_pos":[(offset.top)+"px",(offset.left+200)+"px"],"text_pos":[(offset.top+184)+"px",(offset.left+200+150)+"px"]},
	             ];
	  var htmlbody = document.documentElement||document.body;
	  htmlbody.style.overflow = "hidden";
    var bg = document.createElement("DIV");
    bg.id="FirstWizard";
    document.body.appendChild(bg);
    var showImg = document.createElement("DIV");
    showImg.id="FirstWizard_showImg";
    document.body.appendChild(showImg);
    showImg=$(showImg);    
    bg=$(bg);
    var c = document.createElement("DIV");
    c.id="fafa_cur";
    showImg.append(c);
    $(c).css({"position": "absolute","top":"70%","left":"50%","height":"10px","line-height":"10px","width":"50px","text-align":"center"});
    var dian = [];
    for(var i=0;i<node.length; i++)
    {
        	dian.push("<a style='font-size:60px;color:#fff;display:none'>.</a>");
    }
    $(c).html(dian.join(""));
    var cImg = document.createElement("img");
    cImg.src = node[0].img;
    $(cImg).css({"position": "absolute","top":node[0].img_pos[0],"left":node[0].img_pos[1]});
    var cText = document.createElement("img");
    cText.src = node[0].text;   
    $(cText).css({"position": "absolute","top":node[0].text_pos[0],"left":node[0].text_pos[1]}); 
    showImg.append(cImg);   
    showImg.append(cText);  
    showImg.attr("page","0");
    bg.css({"position": "absolute","top":"0px","left":"0px","width":"100%","height":"100%","background-color":"#000000","z-index":10000,"filter":"alpha(opacity=80)","opacity":" 0.8","-moz-opacity":"0.8","-khtml-opacity":"0.8"});
    showImg.css({"position": "absolute","top":"0px","left":"0px","width":"100%","height":"100%","z-index":10001});
    showImg.bind("click",function(e){
    	  var page = $(this).attr("page");
    	  if($(this)[0].type=="input") return;
    	  if(page=="0") $("#fafa_cur a:gt(0)").css("display","");
    	  $("#fafa_cur a:eq("+page+")").css("color","#ffffff");  
    	  page = page*1+1;  	  
    	  $("#fafa_cur a:eq("+page+")").css("color","#F9FC00");    	  
        //判断是否已到最后
        var isLast=page==node.length;
        if(isLast)
        {
            	htmlbody.style.overflow = "auto";
            	bg.remove();
            	showImg.remove();
            	updateLastLogintime();
            	return;
        }
        showImg.attr("page",page);
        showImg.find("img").hide();
        var imgs = [showImg.find("img:eq(0)"),showImg.find("img:eq(1)")];
        if(node[page].img_pos[0]==0)
        {
          imgs[0].attr("src",node[page].img);
          imgs[0].css({"left": "","top":"","bottom":node[page].img_pos[0],"right":node[page].img_pos[1]});
          imgs[1].attr("src",node[page].text);
          imgs[1].css({"left": "","top":"","bottom":node[page].text_pos[0],"right":node[page].text_pos[1]});            	
        }
        else
        {
        imgs[0].attr("src",node[page].img);
        imgs[0].css({"top":node[page].img_pos[0],"left":node[page].img_pos[1]});
        imgs[1].attr("src",node[page].text);
        imgs[1].css({"top":node[page].text_pos[0],"left":node[page].text_pos[1]});
        }
        setTimeout('$("#FirstWizard_showImg").find("img").show();',200);
        if(page=="4")
        {
           setTimeout('FirstWizardByLast();',200);	
        }

    });
}

function FirstWizardByLast()
{
    	      var bg =$("#FirstWizard"),showImg=$("#FirstWizard_showImg");
        	  var offset2 = showImg.find("img:eq(0)").offset();
            var txtInput = document.createElement("textarea");
            txtInput.id = "txtInput2";
            $(txtInput).css({"border":"0px","height": "60px","left": (offset2.left+20)+"px","position": "absolute","top": (offset2.top+43)+"px","width": "485px"});
            showImg.append(txtInput);	
            $(txtInput).bind("click",function(e)
            {
                e.stopPropagation();	
            });
            $(txtInput).bind("mousedown",function(e)
            {
            	  $("#btnPublish2").attr("disabled",false);
                e.stopPropagation();
            });
            
            var btn='<span style="display: block;left: '+(offset2.left+450)+'px; position: absolute; top: '+(offset2.top+120)+'px;"><input id="btnPublish2" name="btnPublish2" type="button" class="libenter" value="发  布" onclick="publishInput()" disabled></span>';
            showImg.append(btn);
            $("#btnPublish2").bind("click",function(e)
            {
                e.stopPropagation();
                $("#Trend").val($("#txtInput2").val());
                publishInput();
                var htmlbody = document.documentElement||document.body;
                htmlbody.style.overflow = "auto";
            	  setTimeout("$('#FirstWizard').remove();$('#FirstWizard_showImg').remove()",1000);
            	  updateLastLogintime();
            	  return;
            });
            var txt='<span id="__txt" style="font-weight: bold;color: blue;cursor: pointer;display: block;left: '+(offset2.left+400)+'px; position: absolute; top: '+(offset2.top+10)+'px;">还没想好说些什么</span>';
            showImg.append(txt);
            $("#__txt").bind("click",function(e)
            {
                e.stopPropagation();
                var htmlbody = document.documentElement||document.body;
                htmlbody.style.overflow = "auto";
            	  bg.remove();
            	  showImg.remove();
            	  updateLastLogintime();
            	  return;
            });
}

function updateLastLogintime()
{
	  $.post(updateLastLogintimeUrl,"",function(d){});
}

function viewallcontent_OnClick(e,sender)
{
	if(checkHover(e,sender)){
	  var $sender = $(sender);  
	  
	  if ($sender.attr("isexpand") != "1")
	  {
	  	$sender.parent().siblings("p.news").removeClass("news_maxheight");
	    $sender.find("span").attr("class","arrow_down");
	    $sender.find("a").text("收起");
	    $sender.attr("isexpand", "1");
	  }
	  else
	  {
	  	$sender.parent().siblings("p.news").addClass("news_maxheight");
	    $sender.find("span").attr("class","arrow_up");
	    $sender.find("a").text("查看全部");
	    $sender.attr("isexpand", "0");
	    var t = $sender.siblings("p.news").offset().top - 50;
	    if ($(document).scrollTop() > t) $(document).scrollTop(t);
  	}
	}
}
//加载置顶列表
function loadtoplist(){
	var $twotab = $("#two1.hover[gettopurl], #two2.hover[gettopurl], #two3.hover[gettopurl],#two4.hover[gettopurl],#two5.hover[gettopurl],#two6.hover[gettopurl]");
	if($twotab.length>0){
		if($twotab.attr('isloadtop')!='1'){
			getTopConv($twotab);
		}
	}
}
//超长动态显示控制
function overLengthAct(){
	var news_maxheight_template ="<div class='viewall'><span style='float:left;font-weight:700;padding-bottom:2px;'>………………</span><span class='mit_view'><a href='javascript:void(0);' style='float:left;margin-left:4px;color:#0088CC'>显示全部</a><div class='arrow_t'><span class='arrow_down'></span></div></span>";
    var items =$(".news_maxheight");
    for(var i=0;i<items.length;i++){
    	$this=$(items[i]);
	    if ($this[0].scrollHeight > parseInt($this.css('max-height').replace("px","")) && $this.siblings(".viewall").length == 0)
	    {
	      $this.after(news_maxheight_template);
	      $this.siblings(".viewall").find(".mit_view").click(function(event){
	      			var $sender = $(this);
						  if ($sender.attr("isexpand") != "1")
						  {
						  	$sender.parent().siblings("p.news").removeClass("news_maxheight");
						    $sender.find("span").attr("class","arrow_up");
						    $sender.find("a").text("收起");
						    $sender.attr("isexpand", "1");
						  }
						  else
						  {
						  	$sender.parent().siblings("p.news").addClass("news_maxheight");
						    $sender.find("span").attr("class","arrow_down");
						    $sender.find("a").text("查看全部");
						    $sender.attr("isexpand", "0");
						    var t = $sender.parent().siblings("p.news").offset().top - 50;
						    if ($(document).scrollTop() > t) $(document).scrollTop(t);
					  	}
	      });
	    }
	  }
}
//动态过滤列表样式
$(document).ready(function(){
	var $lis=$("ul.topic_options li a");
	$lis.click(function(){
		$("ul.topic_options").find("span.topic_in,a.topic_in").removeClass("topic_in");
		$(this).siblings("span.squareico").addClass("topic_in");
		$(this).addClass("topic_in");
		$("div.topb span").text($(this).text());
	});
});
//js模拟动态锚定位功能
function anchorMode($e){
	var to=$e.offset().top+$e.height();
	var hei=$(window).height();
	$(document).scroll((to-hei)>0?hei:((to<0)?to:0));
}
//置顶导航
function createNavigation(e){
	
}

//圈子自动补位
//circle_id:要去除的圈子
//func:当没有可补位的圈子时，回调过程.
//该过程应该重新从服务器上获取推荐圈子，并判断是否获取到圈子，没有时则应终止处理，否则应该再次调用circlePadding进行补位
function circlePadding(circle_id,func)
{
    	var $circleid = $("#"+circle_id);
    	var $p = $circleid.parent();
    	var $hideCirle = $p.find("div:hidden:first");
    	if($hideCirle.length==0)
    	{
    		if(func!=null) func(circle_id);
    	}
    	else
    	{
    	    	$circleid.remove();
    	    	$hideCirle.css("display","block");
    	}
}
	function cancelCircleApply(circle_id)
	{
	    wefafaWin.weconfirm(null,"圈子申请","确定要取消加入该圈子的申请？","",function(circle_id){
			    $.getJSON(circle_cancel_apply_url+"/"+circle_id,{},function(d){
			       	if(d.success=="1")
				      {
				           $("li .circles_item[id='"+circle_id+"']").remove();
				      }
				  });
	    },circle_id);
  }  
  function right_applyAddCircle(sender)
    {
      var $sender = $(sender);
      var circle_id = $sender.siblings(".circle_id").val();
      var create_staff = $sender.siblings(".create_staff").val();
      var circleName = $sender.parents(".recomcircleitem").find(".cirlcename").text();      
      $sender.removeAttr("onclick");
      $sender.text("提交中...");
      ShowInfoHint("正在提交您加入圈子的申请，请稍等...",10000);
      $.post(circle_join_apply_url,{createStaff:create_staff,circleId:circle_id,circleName:circleName},
        function(data) 
        {
        	$sender.html("加入<span></span>");
          if(data=="0")
          {
              ShowInfoHint("申请失败：您已提交过申请，请等待圈子管理员的审批。",5000);
              CircleCard.hide();
              $sender.unbind("click").bind("click",function(){right_applyAddCircle(this)});
              return;
          }
          if(data=="99999")
          {
              ShowInfoHint("申请失败：您申请的圈子数已达到了最大数量限制。",5000);
              CircleCard.hide();
              $sender.unbind("click").bind("click",function(){right_applyAddCircle(this)});
              return;
          }
          if(data=="-2"){
          		ShowInfoHint("申请失败：您所加入的圈子过多，已达到了等级限制。",5000);
              CircleCard.hide();
              $sender.unbind("click").bind("click",function(){right_applyAddCircle(this)});
              return;
          }
          if(data=="-3"){
          		ShowInfoHint("申请失败：该圈子已满员。",5000);
              CircleCard.hide();
              $sender.unbind("click").bind("click",function(){right_applyAddCircle(this)});
              return;
          }                    
          ShowInfoHint("您加入圈子的申请已提交，请等待圈子管理员的审批。",5000);
          circlePadding(circle_id);
          CircleCard.hide();
          $sender.text("已经申请");
          $sender.parents(".recomcircleitem").fadeOut("slow", function(){
            $sender.parent().parent().remove();  
          });
        },"text"
      );
}

var ajaxHttpConnRef = null,authModalDlag=null,isAuthElementHover=false;
//显示认证说明
function show_auth_comment(authUrl)
{
	  var _cx = $("#view_auth_comment_dlag");
	  if(_cx.length==0)
	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="view_auth_comment_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="400px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="5px 5px 5px 5px";
	 	  	}
	 	  	_cx.innerHTML="<div id='view_auth_comment_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	authModalDlag = $("#view_auth_comment_dlag");
	 	  	if(authModalDlag.modal==null) return;
			 	authModalDlag.modal({show:false,backdrop:false});
			 	authModalDlag.on('shown', {Aurl: authUrl}, function(para){
			 	  	   $("#view_auth_comment_dlag_body").html("<div class='urlloading'>加载等级信息...<div /></div>");
			 	  	   ajaxHttpConnRef=$.get( para.data.Aurl, {},
			 	  	       function(d){
			 	  	       	  ajaxHttpConnRef=null;
//										 	var tmpDlg = 		  authModalDlag; 
//										  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
//											t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
//											l = l<150?l+20:l;
//											l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
//											tmpDlg.css({"top":t,"left":l});	  
			 	  	       	  $("#view_auth_comment_dlag_body").html(d);
			 	  	       }
			 	  	   );
			 	  	});		 	  	
	  }
	  /*
		jQuery('.view_auth_comment').each(function(index){
				jQuery(this).hover(
					function(e){
						if(isAuthElementHover) return;
						isAuthElementHover=true;						
						var _self = $(this);
						var ex = getEventCoord(e);
			 	  	
						if(authModalDlag.css("display")!="none"){
							  return;
						}			 	  	

			 	  	var l = ex.pageX-($(document).scrollLeft())+10,t = ex.pageY-$(document).scrollTop()+10;
	 	        authModalDlag.attr("x",l).attr("y",t);
			 	  	authModalDlag.css({"top":t,"left":l,"margin":0});
			 	  	authModalDlag.modal("show");
					},
					function(e){
						var _self = $(this); 
						isAuthElementHover=false;
						if(ajaxHttpConnRef!=null)
				 	  {
				 	  	  $("#view_auth_comment_dlag_body urlloading").remove();
				 	  	  ajaxHttpConnRef.abort();//立即终止请求
				 	  }
				 	  $("#view_auth_comment_dlag_body").html("");
						authModalDlag.modal("hide");						
					})
	 */
					
	//});	  
}

var wefafaWin={
	  confirm_sender:null,
	  confirm_ok_fun:null,
	  confirm_para:null,
   	weconfirm:function(sender,title,txt,ok_fun,para)
   	{
   		 var $wefafa_confirm = $("#wefafa_confirm");
   		 if($wefafa_confirm.length>0) return;
   	   var modal='';
   	   moda='<div class="wefafa_confirm modal"  id="wefafa_confirm" data-backdrop=false style="z-index:20000;display:none;height: 129px;margin-left: -110px;margin-top: -60px;width: 220px;" show=false>'+
				    '  <div class="doc_window_title">'+
				    '  	<span>&nbsp;&nbsp;{title}</span>'+
				    '  </div>'+
					  '  <div>'+
					  '    <div class="doc_rd_deleteconfirm_left"></div>'+
					  '    <div class="doc_rd_deleteconfirm_right">'+
					  '        <div class="doc_rd_deleteconfirm_text">{txt}</div>'+
					  '        <div style="margin-left: 0px;margin-top: 20px;float:left"><span id="wefafa_confirm_sureBtn" onclick="wefafaWin.confirm_ok()" class="doc_md_content_right_btn">确定</span><span id="wefafa_confirm_cancelBtn" onclick="wefafaWin.confirm_cancel()" class="doc_md_content_right_btn">取消</span></div>'+
					  '        <div class="wefafa_confirm_hint" style="margin-left: 70px;height:10px;width:130px;float:left"></div>'+
				    '   </div>'+
				    '  </div>'+
			      '</div>';
			 $(document.body).append(moda.replace("{title}",title).replace("{txt}",txt));
			 $(document.body).append("<div id='wefafa_confirm_cover' style='background-color: #111111;height: 100%;left: 0;opacity: 0.5;filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity:0.5; position: fixed; top: 0;width: 100%;z-index: 10000;'>");
			 $wefafa_confirm = $("#wefafa_confirm");
			 $wefafa_confirm.show();
			 this.confirm_ok_fun = ok_fun;
			 this.confirm_sender = sender;
			 this.confirm_para = para;
   	},
   	confirm_ok:function()
   	{
   		 $("#wefafa_confirm_cover").remove();
   		 this.confirm_ok_fun(this.confirm_para==null? this.confirm_sender:this.confirm_para); 
   		 $("#wefafa_confirm").remove();  		 
   	   return true;	
   	},
   	confirm_cancel:function()
   	{
   		 $("#wefafa_confirm_cover").remove();
   		 $("#wefafa_confirm").remove();
   	   return false;	
   	}
};