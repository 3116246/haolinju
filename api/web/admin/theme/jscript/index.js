var Index={};
Index.openid = "";
Index.host = window.location.protocol=="file:"?"http://112.126.77.162:8000":window.location.protocol+"//"+window.location.host;
Index.path = Index.host+"/api/http/exec_dataaccess?openid=";
Index.server = "";
Index.appid = "35dc24ba28ee06ce8741259c19fbc8e8";
Index.noDataHtml = '<div style="text-align: center; line-height: 50px; color: rgb(153, 153, 153);">未查询到数据!</div>';
Index.loadDataHtml = '<div style="text-align: center; line-height: 50px; color: rgb(153, 153, 153);">正在努力加载数据...</div>';
Index.init=function(){
	Index.openid=$.trim($.cookie("openid"));
	if(Index.openid=="")
	{
		window.location.href="login.html";
		return;
	}
	$.post(LOGIN_HOST+"/api/http/proxytoken?jsoncallback=?",{"grant_type":"proxy","appid":Index.appid,"code":$.cookie("__auth2_code"),"openid":Index.openid,"state":"justsy"},function(data){
		if(data.returncode!="0000")
		{
			$("#alert").show().children('span').html("登录失败：权限不足，请联系管理员！");
			return;
		}
		$.cookie("eim__auth2_token",data.data.access_token);	
		FaFaPresence.Connection(Index.appid,Index.openid,data.data.access_token);	
	},'json');
	

	$("#username").on('keypress',function(evt){if(evt.which==13){$("#password").focus();}});
	$("#password").on('keypress',function(evt){if(evt.which==13){Login.login()}});

	$("body").children('div').show();
	$("#nickname").html($.cookie("nickname"));
	$("#photo").attr("src",$.trim($.cookie("photo")));
	Index.server = Index.path+Index.openid;	

	bootbox.setLocale("zh_CN");
	Index.container = $(".page-content");
	Index.container.html("").show();
	$(".page-sidebar-menu li:first>a").trigger('click');
	$("#in_global_search").css("width","250px");
	$("#in_global_search").on('keypress',function(evt){
		var key = evt.which;
		if(key==13)
		{
			$("#btn_global_search").trigger('click');
		}
	})
};
Index.LoadMenu=function(ent,url){
	$this=$(ent);
	$(".page-sidebar-menu li.active").removeClass("active").find("span[class='selected']").remove();
	$this.append('<span class="selected"></span>').parent().addClass('active');
	Loading.page(Index.container,'努力加载中...');
	Index.container.load(url);
}

Index.search={};
//opts:{text:"",callback:func}
Index.search.config=function(opts)
{
	if(opts==null)
	{
		$(".search-form").hide();
		return;
	}
	$(".search-form").show();
	$("#in_global_search").attr("placeholder",opts.text);
	$("#btn_global_search").off('click').on("click",function(){
		if(opts.callback!=null)
		{
			$("#searchstatus").show();
			opts.callback($("#in_global_search").val());
			setTimeout(function(){Index.search.init()},3000);			
		}
	});
}

Index.search.init=function()
{
	$("#in_global_search").val("");
	$("#searchstatus").hide();
}

var Loading={};
Loading.page=function($container,text){
	$container.html('<div id="pub_loading" class="p_page_loading">'+text+'</div><div class="clearfix"></div>');
}