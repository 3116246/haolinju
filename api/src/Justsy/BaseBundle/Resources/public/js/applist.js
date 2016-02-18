function ComponentSelectedFun(params)
{
  if(params.event!=null && params.event.target!=null){
  	var functionid = "";
  	var  control = $(params.event.target);
  	if ( params.code=="component_userprofile" ){
  		functionid = control.parent().attr("functionid");
  	}
  	else if (params.code=="component_userbasicinfo"){
  		functionid = params.attrs.functionid.text;
  	}
  	if(functionid!=null && functionid!="" && AppConfigManager)
	 	   AppConfigManager.interNodeSelected(functionid);
  }
}
var HTML5_APP_CODE="9902",MOBILE_APP_CODE="9903",WEB_APP_CODE="9904",makeDefaultPage=false; //是否是生成默认页面
function configpage_init() {
	makeDefaultPage=false;
	$.ajaxSetup({
		error: function(x, e) {
			ajaxerror = (x);
			if (x.responseText != "config file is not make."
				&&x.responseText != "No found APP info."
				&&x.responseText != "No found config file."
				)
				return;
			if($("#content_template").attr("appid").toUpperCase()=="PORTAL") {
				initAppByData();
				SaveApplicationConfig(oneApp);//PORTAL配置时，更改了模板后立即提交
				GetApplictionPage(oneApp);
				return;
			}
			initAppByData();
			SaveApplicationConfig(oneApp);//PORTAL配置时，更改了模板后立即提交
			GetApplictionPage(oneApp,(typeof(cFunc)=="undefined" || cFunc== null) ? null : cFunc.functionid);
			//生成默认首页
			//加载第一个界面模板
			makeDefaultPage=true;
			return false;
		}
	});
	//加载当前应用 的应用配置xml
	var appid = $("#content_template").attr("appid");
	if (appid != "") {
		$(".runtime_main_content").html('<div id="app_sys_error_hint">正在加载应用配置，请稍候...</div>');
		var url = appid=="PORTAL" ? getportalconfigurl : getappconfigurl.replace("appid", appid);
		$.get(url,
			function(data) {
				$("#app_sys_error_hint").remove();
				if (data == null) {
					initAppByData();
					SaveApplicationConfig(oneApp);//PORTAL配置时，更改了模板后立即提交
					if(appid=="PORTAL"){
						GetApplictionPage(oneApp);
					}
					else{
						GetApplictionPage(oneApp,cFunc!=null?cFunc.functionid:null);
					}
					return;
				}
				//解析界面
				oneApp.init({
					xmlString: data, //xml字符串
					device: 'Android', //值为'IOS'或'Android'
					appid: appid, //应用id
					p: $("#iosruntime .runtime_main_content")[0], //模拟器容器dom对象
					ComponentSelected: function(params) {
						ComponentSelectedFun(params);
					}, //当模拟器中控件被选中时触发
					InterfaceSelected: function(sender,functionid){
						Container_ComponentSelected(sender,functionid);
					},
					ErrorOccured: function(error) {} //当发生错误时触发 error 为当前错误对象					
				});				
				//设置当前页面				
				var cFunc =oneApp.getRootInterData();
				if(appid=="PORTAL")
					GetApplictionPage(oneApp);
				else
					GetApplictionPage(oneApp,cFunc!=null?cFunc.functionid:null);
			  if ( cFunc==null || cFunc.functionid=="")
			  {
			     $("#interface_name").attr("functionid",APP_PAGE_LIST[0].uuid);
			  }
			  else
			  	$("#interface_name").attr("functionid",cFunc.functionid);			     
//				$("#interface_name").off("click").on("click",function(){
//					var functionid = $(this).attr("functionid");
//					if(functionid=="") return;
//					$(".user_appconfig_list[uuid='"+functionid+"']").trigger("click");
//				});
			},
			"xml"
		);
	}
	

	$(".application_CreateNewPage").unbind("mouseover").bind("mouseover", function() {
		$(".application_CreateNewPage>img").attr("src", "/bundles/fafatimewebase/images/edit_add.png");
	});

	$(".application_CreateNewPage").unbind("mouseout").bind("mouseout", function() {
		$(".application_CreateNewPage>img").attr("src", "/bundles/fafatimewebase/images/icon_add.png");
	});
}

function initAppByData() {
	$("#app_sys_error_hint").remove();
	oneApp.initWithAppInfo({
		appinfo: {
			appid: $("#content_template").attr("appid"), //应用id
			appname:$(".runtimescreentop").eq(0).text(), //应用名称
			appversion: '', //版本
			appicon: '', //图标
			bindurl: '', //绑定地址
			rootfunctionid: "index"
		},
		device: 'Android', //值为'IOS'或'Android'
		appid: $("#content_template").attr("appid"), //应用id
		p: $("#iosruntime .runtime_main_content")[0], //模拟器容器dom对象
		InterfaceSelected: function(sender,functionid){
			 Container_ComponentSelected(sender,functionid);
		},
		ComponentSelected: function(params) {
			ComponentSelectedFun(params);
		}, //当模拟器中控件被选中时触发
		ErrorOccured: function(error) {
			console.info(error);
		} //当发生错误时触发 error 为当前错误对象
	});
	
	//生成默认页面
	if(ApplicationMgr.apptype=="99"){
		if(oneApp.appid!="PORTAL"){
			oneApp.addInterfaceWidthTempXml(
				"index",
				getfirstpagename(),
				"<template><title color='#FFFFFF'><text>"+getfirstpagename()+"</text></title></template>",null
			 );	
		}
		else{
			oneApp.addInterfaceWidthTempXml(
				"index",
				getfirstpagename(),
				"<template></template>",null
			 );	
		}	
	}
	else if(ApplicationMgr.apptype=="9902"){
			oneApp.addInterfaceWithHTML5Xml(
			"index",
			getfirstpagename(),
			"<html5></html5>"
		);
	}
	else if(ApplicationMgr.apptype=="9903"){
			oneApp.addInterfaceWithMobileXml(
			"index",
			getfirstpagename(),
			"<mobileapp></mobileapp>"
		);
	}
	else if(ApplicationMgr.apptype=="9904"){
			oneApp.addInterfaceWithWebXml(
			"index",
			getfirstpagename(),
			"<webapp></webapp>"
		);
	}	
	else{
		oneApp.addInterfaceWithNativeXml(
			"index",
			getfirstpagename(),
			"<native></native>"
		);
	}
}

function getfirstpagename(){
	if (oneApp.appid=="PORTAL")
	   return enterprisename+"-门户";
	else
		return  ApplicationMgr.appname+"-首页";
}

function loadTempalteXML(tempid) {
	if(tempid==null || tempid=="")
	{
		oneApp.loadInterObj( AppConfigMgr.currentpageid );		
		if(makeDefaultPage)
		{
			//创建默认页面
			oneApp.addInterfaceWidthTempXml(
				"index",
				"",
				"<template></template>"
			);
			oneApp.setRootInterface("index");
			GetApplictionPage(oneApp,'index');
			SaveApplicationConfig(oneApp);
			makeDefaultPage=false;
		}
		return;
	}
}


//显示消息
function showMeesageHint(state, hint) {
	var html = [];
	var font_style = "float:left;padding-left:5px;color:red;";
	html.push("<div style='height:16px;line-height:16px;margin-top:10px;margin-left:5px;'>");
	if (state == "add" || state == "update" || state == "delete") {
		html.push(" <img src='/bundles/fafatimewebase/images/zq.png' style='float:left;width:16px;height:16px;' />");
		html.push(" <span style='" + font_style + "'> " + hint + "</span>");
	} else if (state == "error" || state == "checkError") {
		html.push("<img src='/bundles/fafatimewebase/images/error.gif' style='float:left;width:16px;height:16px;' />");
		html.push("<span style='" + font_style + "'>" + hint + "</span>");
	} else if (state == "loading") {
		html = [];
		html.push("<div style='height:32px;line-height:32px;'>");
		html.push(" <img src='/bundles/fafatimewebase/images/loading.gif' style='float:left;width:32px;height:32px;' /><span style='float:left;'>" + hint + "</span>");
	}
	html.push("</div>");
	$("#confige_errorhint").html(html.join(''));
	if (state == "checkError") {
		setTimeout("$('#confige_errorhint').html('');", 2000);
	}
}

function FieldsSelected(e) {
	$(".AttributesFields_Action").attr("class", "AttributesFields");
	$(e).attr("class", "AttributesFields_Action");
	$(e).next().focus();
}

var APP_PAGE_LIST=[],APP_PAGE_MAP=new HashMap();//全局列表
//从配置中获取页面列表
function GetApplictionPage(appobj,vAutoOpenFunctionID)
{
	var $xmldom = $(oneApp.xmlBuilder.xmlDom);
	APP_PAGE_LIST=[];
	APP_PAGE_MAP=new HashMap();
	if ($xmldom != null && $xmldom.length>0)
	{
		var $functions = $xmldom.find("function");
		for (var i = 0; i < $functions.length; i++)
		{			
			var $func = $($functions[i]);
			var page={
				"uuid":$.trim($func.children("functionid").text()),
				"name":$.trim($func.children("functionname").text()),
				"pagetype":$.trim($func.children("functiontype").text()),
				"config" : $func.children("template,native"),
				"templateid":$func.children("template").attr("id")
			};
			if(page.uuid=="" || APP_PAGE_MAP.get(page.uuid)!=null) continue; //无效的或者已加载的页面配置
			APP_PAGE_LIST.push(page);
			APP_PAGE_MAP.put(page.uuid,page);
		}
		//切换到根页面
		var rootpageid = oneApp.getRootFunctionid();
		if(vAutoOpenFunctionID==null && oneApp.cFuncid!=rootpageid)
		{
			oneApp.loadInterObj(rootpageid);
		}
		//加载页面列表
		ApplicationMgr.PageListView($(".user_appconfig_area"));	
		if(vAutoOpenFunctionID!=null)
		{
			$(".user_appconfig_list[uuid='"+vAutoOpenFunctionID+"']").trigger('click');
		}		
		//如果没有页页，则创建默认页面
    if ( $xmldom.length>0 && oneApp.appid=="PORTAL" && $($xmldom[0].documentElement).find("html5").length==0 && $($xmldom[0].documentElement).find("native").length==0 && $($xmldom[0].documentElement).find("template").children().length==0) {
    	 CreateDefaultPage();
    }
	}
}

function SaveApplicationConfig(appObj) {
	//清理配置文件中的垃圾数据
	var $xmldom = $(oneApp.xmlBuilder.xmlDom),curuuid =oneApp.cFuncid,curappid=oneApp.appid;
	if ($xmldom != null) {
		var $functions = $xmldom.find("function");
		for (var i = 0; i < $functions.length; i++) {
			var $func = $($functions[i]);
			var $uuid = $.trim($func.children("functionid").text()),cacheObj = APP_PAGE_MAP.get($uuid),
				$ftype = $func.children("functiontype").text();
			if ($uuid == null || $uuid == "" || $uuid == "undefined") {
				oneApp.removeInterByFunctionid($uuid);
				continue;
			}			
			if ($ftype != "1" || curappid=="PORTAL") continue; //只处理模板类型的function
		}
	}
	componentDrop.init();
	$("#app_sys_error_hint").remove();
	$.post(appconfig_publish_url, {
			'appid': curappid,
			"xmldata": appObj.getXmlString()
		},
		function(data) {
			if (data.s == "1") {
				 if ( AppConfigManager.hint)
				  	$("#syshint").html("应用配置已更新成功!").show();
			} else {
				$("#syshint").html(data.msg).show();
			}
			setTimeout(function() {
				$("#syshint").html("").hide("400");
			}, 3000);
		}
	);
}


//加载xml文件
function loadXML(params) {
	ComponentAttr.edit("edit_xml", params);
}

//xml配置页面管理
var XMLConfigPage ={
	appid:$("#content_template").attr("appid"),
	url:"",
	apptype:"99",
	//我的应用初始化
	Init:function()
	{
		$("#application_submenu_ul li").show();
		$("#portals_info").parent().hide();
	},
	//企业移动门户初始化
	portalsInit:function(){
		this.apptype="99";
		this.appid = "PORTAL";
		$("#application_submenu_ul li").show();		
		$("#app_basic").parent().hide();
		$("#app_mobilepp").parent().hide();
		$("#app_webapp").parent().hide();
		$("#app_html5").parent().hide();
	},
	//跳转至xml配置页面	
	switchXMLConfig:function()	{		  
		  $(".component_black_firstpage").show();
	    $("#content_template").attr("appid",this.appid).attr("apptype",this.apptype);
	    $("#content_template").show();
	    $("#template_right").show();
	    ComponentAttr.currentCode = null;
	    //如果当前应用不是门户应用，默认进入添加组件
	   	if(this.appid!="PORTAL") {
	   		configpage_init();
	   		if(this.apptype==HTML5_APP_CODE)//html5应用 
	   		{
	   			$(".application_submenu_ul li").hide();
	   			$("#app_basic,#app_html5,#app_publish").parent().show();
	   			$("#app_html5").trigger('click');
	   		}
	   		else if (this.apptype == MOBILE_APP_CODE){
	   			$(".application_submenu_ul li").hide();
	   			$("#app_basic,#app_mobilepp,#app_publish").parent().show();
	   			$("#app_mobilepp").trigger('click');
	   		}
	   		else if (this.apptype==WEB_APP_CODE){
	   			$(".application_submenu_ul li").hide();
	   			$("#app_basic,#app_webapp,#app_publish").parent().show();
	   			$("#app_webapp").trigger('click');
	   		}
	   		else if(this.apptype=="99")
	   		{
	   			$(".application_submenu_ul li").show();
	   			$("#app_portals,#portals_info,#app_html5,#app_mobilepp,#app_webapp").parent().hide();
	   			InterfaceCustom();//触发界面定制
	   			$("#app_basic").removeAttr("current_selected");
	   			$("#app_basic>span:first").attr("class","application_sub_menu_basic");
	   			$("#app_basic>span:last").css("color","#555555");
	   			$("#interface_name").attr("current_selected","1");
	   			$("#interface_name>span:first").attr("class","application_sub_menu_interface_active");
	   			$("#interface_name>span:last").css("color","#059bc6");
	   		}  		
	   	}
	   	menu_status = true;
	},
	//跳转至组件编辑
	switchComponent:function(url){
		//LoadComponent($("#main_area"),compentediturl,{},function(){
			$("#main_area").html("");
			var title = "<span style='color:#cc3300;font-weight:bold;margin-left:10px;'>"+$(".user_appconfig_list_selected").text()+"</span>可选组件";			
			$("#main_area").append('<div style="background-color: #F3F3F3; width: 99%; margin-bottom: 20px;">'+title+'</div><div id="componentitem_list" style="margin-left: 20px; margin-bottom: 10px; float: left; width: 640px; margin-top: -10px;"></div><div class="clearfix"></div><div style="background-color: rgb(243, 243, 243); width: 100%; margin-bottom: 20px;">已选组件</div><div id="componentitem_list_select" style="margin-left: 20px; margin-bottom: 10px; float: left; width: 640px; margin-top: -10px;"></div>'); 
	  	 	var $list=$("#componentitem_list"),$selectlist = $("#componentitem_list_select");
	  	 	$list.html("");
	  	 	$selectlist.html("");
	  	 	//加载已有组件
			ComponentEdit.init(oneApp);
			ComponentEditView.show($selectlist,ComponentEdit.componentlist);
			//加载可选择组件列表
			if(ComponentEditView.isAdd())
			{
				  var component_code = "";
				  var component_obj = oneApp.getSourceComponent($("#interface_name").attr("functionid"));
				  if ( component_obj != null) component_code = component_obj.code;
				  				  
		  	 	$list.append(component_list.getHtmlEle2({"type":"1","dom_index":"0"}));
		  	 	//只有门户配置时应用列表才有用
		  	 	if(oneApp.appid=="PORTAL") {
		  	 		 $list.append(component_applist.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	}
		  	 	$list.append(component_title.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_menu.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_switch.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_search.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	//临时页面中不允许添加导航
		  	 //	var cacheObj = APP_PAGE_MAP.get(oneApp.cFuncid);
		  	// 	if( cacheObj==null || !cacheObj.temp){
		  	// 		$list.append(component_nav.getHtmlEle2({"type":"","dom_index":"0"}));
		  	// 	}
		  	 	if ( component_code!="component_nav")
		  	 	  $list.append(component_nav.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	if ( component_code != "component_tabs")
		  	 	 	$list.append(component_tabs.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_groupnews.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_circlenews.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_publicaccount.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_repository.getHtmlEle2({"type":"","dom_index":"0"}));
		  	 	$list.append(component_contacts.getHtmlEle2({"type":"","dom_index":"0"}));
		  	}
		  	else
		  	{
		  		$list.append("<span style='color:#ccc'>无！导航页面和原生功能页面都不允许添加其他组件！</span>");
		  	  $(".componentlist").css("border-right","none");
		  	}
			$selectlist.find(".application_component_icon").off("click");
			$("#componentitem_list_select .application_component_icon").each(function(index, val) {
				 var $this = $(this).children('span:first');
				 $this.attr("class",$this.attr("class")+"_active");
			});
			componentDrop.init();
			//事件绑定
			$("#componentitem_list_select .application_component_icon").off("mouseover,mouseout").on("mouseover",function(event){
				if(checkHover(event,this)){
					var $this=$(this);
					$(".component").removeClass("component_hover");
					$(".component[aindex='"+$this.attr("dom_index")+"']").addClass("component_hover");
					initComponentToolbar();
					$("#runtime_component_toolbar").appendTo($this).attr({"dom_index":$this.attr("dom_index"),"component":$this.attr("component")}).css("margin-top",(0-$this.height())+"px").show();
				}
			}).on("mouseout",function(event){
				if(checkHover(event,this)){
					var $this=$(this);
					$(".component").removeClass("component_hover");
					$("#runtime_component_toolbar").appendTo($(document.body)).hide();
				}
			});
			
			$("#componentitem_list .application_component_icon").off("click,mouseout,mouseover").on("mouseover",function(event){
				if(checkHover(event,this)){
					var $this=$(this);
					$this.find(".application_sub_menu_addcomponent_active").show();
				}
			}).on("mouseout",function(event){
				if(checkHover(event,this)){
					var $this=$(this);
					$this.find(".application_sub_menu_addcomponent_active").hide();
				}
			});
			$("#componentitem_list .application_sub_menu_addcomponent_active").off("click").on("click",function(){
				var $this = $(this).parent(),code=$this.attr("component");
				var componentObject=ComponentAttr.getComponentObject(code);
				//添加导航菜单时需要提示,如果页面上已有内容
				if( (code=="component_nav"|| componentObject.type=="native"))
				{
					 if (  $("#componentitem_list_select").children().length>0) {
						  wefafaWin2.weconfirm(null,"提示","添加该组件将清空页面内容，确定吗？",function($this){
							    var r=ComponentEdit.add(code,$this.attr("type"));
							    if(!r) return;
							    //添加成功
							    //直接进入组件编辑
							    $("#runtime_component_toolbar").appendTo($(document.body)).hide();
							    ComponentAttr.currentCode =code + r;//锁定当前组件，编辑页面加载完成后自动切换到该组件
							    $("#edit_component").trigger('click');
						    },
						  $this);
					}
					else{
						var r=ComponentEdit.add(code,$this.attr("type"));
						if(!r) return;
						var componentobj = oneApp.getInterComponent(oneApp.cFuncid,1);
						var rootfunctionid = oneApp.cFuncid;
						var funcid = oneApp.cFuncid + "-1-0";
						var funcname = componentobj.attrs.navitems[0].itemname;
            componentobj.attrs.navitems[0].functionid.text = funcid;
            componentobj.attrs.navitems[0].functionid.target = "self";
            oneApp.setInterComponent(rootfunctionid,1,componentobj);  //设置首页的functionid;            						
						OneApp.addInterfaceWidthTempXml(funcid,funcname,"<template></template>");
            ComponentEditView.RefreshPage();  
            
            SaveApplicationConfig(oneApp);            
  			    $(".user_appconfig_list[uuid='"+funcid+"']").trigger('click');
					}
					return ;
				}
				//判断界面上是否已有导航或原生组件，有则不允许添加其他组件
				if(!ComponentEditView.isAdd())
				{
					return;
				}
				//点击添加组件
				var r=ComponentEdit.add(code,$this.attr("type"));
				if(!r) return;
				//添加成功								
				//直接进入组件编辑
				$("#runtime_component_toolbar").appendTo($(document.body)).hide();
				ComponentAttr.currentCode =code + r;//锁定当前组件，编辑页面加载完成后自动切换到该组件
				$("#edit_component").trigger('click');
			});
		//});
	},	
	showAttributes:function(e){
	   $("#attr_desc").text($(e).attr("attrdesc"));
	   $(".AttributesFields_Action").attr("class", "AttributesFields");
	   $(e).prev().attr("class", "AttributesFields_Action");
	},
	portals_mgr:{
		show:function(url){
			LoadComponent("main_area",componentattrediturl.replace("componentname","configfilemgr"),null,function(){
				$("#app_portals_mgr").show();
				$("#upload_area").parent().show();
				$("#upload_area").prev().show();
				$("#upload_area").hide();			
				$.getJSON(url,function(data){
					menu_status = true;
					var android_fileid = data.android_fileid;
					var ios_fileid = data.ios_fileid;
					var html = Array();
					if ( android_fileid !="")
					  html.push("<a id='android_fileid' href='"+ (file_webserver_url + android_fileid)+"' style='margin-right:20px;'>Android配置文件</a>");
					if (ios_fileid!="")
					  html.push("<a id='ios_fileid' href='"+ (file_webserver_url + ios_fileid)+"'>IOS配置文件</a>");
					if ( html.length>0)
					  $("#config_area").html(html.join(""));
				});
			});
    },
    exportFile:function(ev){
    	var url = $(ev).attr("href");
    	if ( url != null && url !=""){
    		return;
    	}
    	url = $(ev).attr("url");
    	$("#fromportals").hide();
    	$("#export_area").show();
    	if (url!=null && url!=""){
	    	$.getJSON(url,function(data){
	    		 $("#export_area").hide();
	    		 $("#fromportals").show();
	    		 $("#down_file").attr("src",data);
	    		 $(ev).attr("href",data);
	    	});
      }
    },
	  uploadProtals:function(){  
       var filetype = "",filename = "";
       filename = $("#file_android").val();
       if ( filename !=""){
       	 filename = filename.toLowerCase();
       	 var temp =  filename.split(".");
       	 if ( temp[temp.length-1]!="xml"){
       	 	 $(".hint_message").text("请上传为xml格式的配置文件");
       	 	 setTimeout(function() { $(".hint_message").text("");},2000);
       	 	 return;
       	 }
         filetype = "file_android";
       }       
       filename = $("#file_ios").val();
       if ( filename !="") {
       	 filename = filename.toLowerCase();
       	 var temp =  filename.split(".");
       	 if ( temp[temp.length-1]!="xml"){
       	 	 $(".hint_message").text("请上传为xml格式的配置文件");
       	 	 setTimeout(function() { $(".hint_message").text("");},2000);
       	 	 return;
       	 }       	 
         filetype = filetype=="" ? "file_ios":filetype + ",file_ios";
       }
       if ( filetype=="") 
       {
       	  $(".hint_message").text("请选择上传的配置文件！");
       	  setTimeout(function() { $(".hint_message").text("");},2000);
       	  return;
       }       
       $("#filetype").val(filetype);
       var html = Array();
       html.push("<img style='float:left;width:30px;height:30px;margin-top: -1px; margin-left:-4px;' src='/bundles/fafatimewebase/images/loading.gif'> ");
       html.push("<span style='float: left; font-size: 12px;'>正在上传配置文件，请稍候</span>");
			 $(".hint_message").html(html.join(""));
	     $("#fromportals").ajaxSubmit({
				 dataType:'json',
				 success:function(data){
				 	 html = [];
				 	 if ( data.success){
				 	 	 $(".hint_message").html("文件上传成功！");
				 	 	 //更改
				 	 	 if ( data.android_fileid!="")
					     html.push("<a id='android_fileid' href='"+ (file_webserver_url + data.android_fileid)+"' style='margin-right:20px;'>Android配置文件</a>");
					   if (data.ios_fileid!="")
					     html.push("<a id='ios_fileid' href='"+ (file_webserver_url + data.ios_fileid)+"'>IOS配置文件</a>");
					   if ( html.length>0)
					     $("#config_area").html(html.join(""));
					   setTimeout(function() { XMLConfigPage.portals_mgr.cancelupload();},3000);
				 	 }
				 	 else{
				 	 	 $(".hint_message").html("文件上传失败！");				 	 					 	 					 	 	
				 	 }
				 }			 
			 });
	  },
		cancelupload:function(){
		  	$("#upload_area").hide();
		  	$("#upload_area").prev().show();
		},
		showupdload:function(){
		  	$("#upload_area").show();
		  	$("#upload_area").prev().hide();
		  	$("#file_android").val("");
		  	$("#file_ios").val("");
		  	$(".hint_message").text("");
		},
		removefile:function(evn)
		{
			$(evn).prev().val("");
		}
  }
};

//应用管理
ApplicationMgr = {
	  appid : "",
	  appname:"",
  	data:null,
  	createpageurl:null,
  	apptype:"99",
  	newcreate:false,
  	BindAppInfo:function(){
  	  var data = this.data;
  	  this.appid = data.appid;
  	  var html = [];
  	  html.push("<div appid='"+data.appid+"' id='basic_"+data.appid+"' style='margin:auto;'>"); 
  	 
  	  html.push("<div class='application_line' style='height:28px;'>");
  	  html.push("  <span class='application_line_label'>应用名称：</span><span id='read_appname' style='float:left;color:#333333;'>"+data.appname + "</span>");
  	  if ( data.logo_url!=null && data.logo_url!="")
  	     html.push("<img id='read_image' onerror=\"this.src='/bundles/fafatimewebase/images/normal.png'\" src='"+data.logo_url+"' style='float:right;width:64px;height:64px;border-radius:5px;' applogo='"+data.applogo+"'>");
  	  else
  	 	   html.push("<img id='read_image' src='/bundles/fafatimewebase/images/normal.png' title='未上传应用Logo' applogo='' style='float:right;width:48px;height:48px'>");
  	  html.push("</div>");
      html.push("<div class='application_line'><span  class='application_line_label'>用户认证接口：</span>");
      html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>"+((data.bindurl==null || data.bindurl=="" )?"[暂无]":data.bindurl)+"</span>"+((data.bindurl==null || data.bindurl=="" )?"":"<span>测试</span>")+"</div>");

      html.push("<div class='application_line'><span class='application_line_label'>开放APPID：</span>");
      html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>"+((data.appid==null || data.appid=="" )?"[未认证]":data.appid)+"</span></div>"); 

      html.push("<div class='application_line'><span class='application_line_label'>开放APPKEY：</span>");
      html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>"+((data.appkey==null || data.appkey=="" )?"":data.appkey)+"</span></div>"); 
      
      
      if(data.login_account==null ||data.login_account=="")
      {
      	html.push("<div class='application_line' ><span class='application_line_label'>业务代理：</span>");
    	html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'><span style='color:#ccc'>未开启</span>&nbsp;&nbsp;&nbsp;&nbsp;[<span style='color:#0088CC;cursor:pointer' id='startBizProxyBtn' onclick='ApplicationMgr.StartBizProxy(\""+data.appid+"\")'>开启业务代理</span>]</span>");
      	html.push("</div>");
      }
      else
      {
      	html.push("<div class='application_line' style='height: 120px;'><span class='application_line_label'>业务代理：</span>");
    	html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'><span style='color:#cc3300'>已开启</span>&nbsp;&nbsp;&nbsp;&nbsp;</span>");
    	html.push("<span style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>你可以拷贝以下配置内容到业务代理配置文件中。</span>");
    	html.push("<span style='display:none;color: rgb(51, 51, 51); line-height: 22px; margin-top: 3px; overflow: auto; height: 110px; padding-left: 20px; word-wrap: break-word; border: 1px solid rgb(204, 204, 204);' id='appBizProxyConfig'></span>");
      	html.push("</div>");
      }

      html.push("<div class='application_line'><span  class='application_line_label'>应用简介：</span>");
      html.push("<span style='color:#333333;display:block;width:380px;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'  id='read_appdesc''>"+data.appdesc + "</span></div>");
      
      html.push("<div class='application_line'><span class='application_line_label'>排列序号：</span>");
      html.push("<span id='read_sortid' style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>" + (data.sortid==null ? "0":data.sortid) + "</span></div>"); 

      html.push("<div class='application_line'><span  class='application_line_label'>配置脚本：</span>");
      html.push("<span id='configfilelook' style='color:#333333;display:block;padding-right:0px;word-wrap:break-word;line-height:22px;margin-top:3px;'>"+(data.configfileid==null||data.configfileid=="" ?"[未定义配置脚本]":"<a href='" + file_webserver_url + data.configfileid+"'>查看</a>")+"</span><div id='configfilediv' style='float: left; width: 100%; margin-left: 100px;'><span style='cursor:pointer;color:#08C;' onclick='$(\"#configfilediv\").hide();$(\"#updateconfigdiv\").show();'>上传配置脚本</span></div></div></div>");
	  html.push("<div id='updateconfigdiv' style='display:none;float: left; width: 100%; margin-left: 160px;'><form id='configfile_frm' method='post' enctype='multipart/form-data'><input type='hidden' value='\""+data.appid+"\"' id='currappid' name='currappid'/><input type='file'  id='configfilename' name='configfilename' style='float:left' ><span class='appconfig_button' style='color:black;' onclick='configfile_update(\""+data.appid+"\")' >上&nbsp;&nbsp;传</span><span class='appconfig_button' style='color:black;' onclick='$(\"#updateconfigdiv\").hide();$(\"#configfilediv\").show();'>取&nbsp;&nbsp;消</span></form></div>");
	  
	  html.push("<div id='configfilehint' style='display:none;float: left; width: 100%; margin-left: 160px;color:red'></div>");
      
      $(".application_view").html(html.join(''));
      if(data.login_account!=null &&data.login_account!="")
      {
      		ApplicationMgr.GetBizProxyCofnig(data.appid);
      }
  	},
  	PageListView:function($parent)
  	{
  		$parent.html("");
  		for(var i=0; i<APP_PAGE_LIST.length; i++)
  		{
  			var page = APP_PAGE_LIST[i];
  			if(page==null) continue;
  			var pageBtn = "<span uuid='"+page.uuid+"' class='user_appconfig_list'>" + page.name + "</span>";
  			$parent.append(pageBtn);
  		}
  		$(".user_appconfig_list").off("click").on("click",function(){
  			var _class = $(this).attr("class");
  			$(".user_appconfig_list_selected").attr("class","user_appconfig_list");
  			$(this).attr("class","user_appconfig_list_selected");
  			var uuid=$(this).attr("uuid");
  			oneApp.loadInterObj(uuid);
  			$("#interface_name").attr("functionid",uuid);
  			//$("#menu_component").trigger("click");
  		});
  		if($("#content_template").attr("apptype")==HTML5_APP_CODE)
  			$("#app_html5").trigger("click");
  		//else 
  		//	$("#menu_component").trigger("click");
  	},
  	StartBizProxy:function(appid){
  		//开启代理配置
  		$("#startBizProxyBtn").removeAttr("onclick").text("正在提交...");
  		$.getJSON(appBizProxyUrl,{"appid":appid,"action":"start"},function(d){
  			if(d.s==1)
  			{
  				var url = $("#viewApp").attr("viewurl");  	 	
      			$.getJSON(url,{"appid":appid},function(data){  	 
  	    			if(data.length>0){
  	    	 			ApplicationMgr.data = data[0];
			  			ApplicationMgr.BindAppInfo();
  	    			}
				});
  			}
  		});
  	},
  	GetBizProxyCofnig:function(appid){
  		//开启代理配置
  		$.getJSON(appBizProxyUrl,{"appid":appid,"action":"download"},function(d){
  			if(d.s==1)
  			{
  				var xml = '<WeBizProxy NeedBind="1" id="'+d.data.appid+'" BizName="'+d.data.appname+'" CreateDate="">'+
	                      '    <BizDesc>'+d.data.appdesc+'</BizDesc>'+
		                  '    <Welcome></Welcome>'+
						  '	   <Bind BindType="ByWefafa">'+
						  '	        <BindUrl>'+d.data.bindurl+'</BindUrl>'+
						  '	   </Bind>'+
						  '	   <LoginAccount>'+d.data.number+'</LoginAccount>'+
						  '	   <Password>'+d.data.appkey+'</Password>'+
						  '	   <ServerAddr>'+wefafaHost+'</ServerAddr>'+
						  '	</WeBizProxy>';
				$("#appBizProxyConfig").text(xml).show();
  			}
  		});
  	},  	
  	showHint:function(control, showtext, state){
  		var error_image = "/bundles/fafatimewebase/images/error.gif";
	    var success_image = "/bundles/fafatimewebase/images/exactness.png";
	    var html = "";
	    if (state == "error" || state == "success") {
		    html = "<img src='" + (state == "error" ? error_image : success_image) + "'><span style='padding-left:5px;color:red;'>" + showtext + "</span>";
		    $(".application_error_area").html(html);
		    if (control != null) control.focus();
		    setTimeout(function() { $(".application_error_area").children().remove();}, 2000);
	    }
	    else {
		    html = "<img src='/bundles/fafatimewebase/images/loading.gif' style='width:32px;height:32px;margin-top:4px;float:left;'><span style='float:left;padding-left:5px;color:red;'>" + showtext + "</span>";
		    $(".application_error_area").html(html);
	    }  		
  	},
  	saveApp:function() {
  		 var appid = ApplicationMgr.appid;
	     var appname = $.trim($(".appname:visible").val());
	     if (appname == "") {
		     ApplicationMgr.showHint($(".appname"),"请输入应用名称", "error");
		     return;
	     }
	     if(!(/^[0-9a-zA-Z\u4e00-\u9faf]{1,}$/).test(appname)){
	     		ApplicationMgr.showHint($(".appname"),"应用名称只能由汉字,数字,字母组成", "error");
		     return;
	     }
	     if ($(".img_applogo").attr("imageUrl") == "") {
		     ApplicationMgr.showHint(null, "请上传应用Logo", "error");
		     return;
	     }
	     var staff = $.trim($(".application_line .staff").val());
	     if ( staff==""){
	     	 ApplicationMgr.showHint($(".application_line .staff"),"请输入开发团队", "error");
		     return;
	     }
	     var sortid = $.trim($(".sortid").val());
			 sortid = (sortid == null || sortid =="") ? 0 : sortid;
			 if ( isNaN(sortid)){
			 	 ApplicationMgr.showHint(null, "排列序号应用为数字类型！", "error");
			 	 return;
			 }
		   var paras = {
			  	"appid": appid,
				  "appname": appname,
				  "applogo": $(".img_applogo").attr("imageUrl"),
				  "appdesc": $(".appdesc").val(),
				  "bindurl": $(".textbindurl").val(),
				  "sortid":  $(".sortid").val(),
				  "apptype": this.apptype,
				  "createstaff":staff
			 };
	     ApplicationMgr.showHint(null, "正在操作，请稍候……", "lodding");
			 $.post(edit_url, paras, function(data) {
					var ctl = null;
					if (data.s == "add") {
						ApplicationMgr.showHint(null, "添加应用成功！", "success");
						setTimeout(function() {
							$(".application_line>input").val("");
							$(".application_line>textarea").val("");
							$("#applist").hide();
							var html=new Array();
							html.push("<div appid='" + data.appid +"' apptype='" + paras.apptype + "' class='app_applist_list'>");
						  html.push(" <img class='app_applist_icon' src='" + file_webserver_url + paras.applogo +"'>");
						  html.push(" <div class='app_applist_info'>");
						  html.push("   <span class='app_applist_appname' onclick=\"ApplicationMgr.viewApp('" + data.appid +"')\"  title='查看应用'>" + paras.appname +"</span>");
						  html.push("   <span><span style='float:left;width:50px;color:#cccccc;'>V 0.1</span><span style='color:red;'>开发中</span></span>");
						  html.push("   <span style='color:#CCCCCC;'>创建者：" + paras.createstaff + "</span>");
						  html.push("  </div>");
						  html.push("  <div class='app_applist_right'>");
						  html.push("    <div> <span class='app_applist_followicon'></span><span style='float: left'>&nbsp;&nbsp;</span><span style='float:right;'>用户管理</span></div>");
						  html.push("    <div style='margin-top:8px;'><span class='app_applist_settingicon'></span><span class='app_applist_linkestyle' onclick='ApplicationMgr.selectApp(this)' style='float:right;'>进入开发模式</span></div>");
						  html.push("  </div>");
						  html.push("</div>");
							var  ctl = $("#appitem .app_applist_list");
							if ( ctl.length==0)
							   $("#appitem").html(html.join(''));
							else
								 ctl.first().before(html.join(''));
							ApplicationMgr.appid = data.appid;
							ApplicationMgr.appname = paras.appname;
							ApplicationMgr.apptype = paras.apptype;
  		        ApplicationMgr.importAppConfig();
						}, 2000);
				  }
				  if (data.s == "edit") {
						ApplicationMgr.showHint(null, "修改应用成功！", "success");
						//修改应用列表
						ctl = $(".app_applist_list[appid='" + paras.appid + "']");
						ctl.find(".app_applist_appname").text(paras.appname);
						ctl.find(".app_applist_icon").attr("src", $(".img_applogo").attr("src"));
						ctl.find(".app_applist_info>span:last").text("创建者："+ paras.createstaff);						
						//修改基本信息
						$("#read_appname").text(paras.appname);
						$("#read_image").attr("applogo",paras.applogo);
						$("#read_image").attr("src", $(".img_applogo").attr("src"));						
						$("#read_subscribe").text(paras.subscribe == "" ? "暂无" : paras.subscribe);
						$("#read_appdesc").text(paras.appdesc);
						$("#read_sortid").text(paras.sortid);
						$("#applist_update").hide();
						$("#applist_basicInfo").show();
						ApplicationMgr.canceledit();
				  } else if (data.s == "exists") {
					  ApplicationMgr.showHint($(".appname"), "已存在该应用名称！", "error");
				  } else if (data.s == "error") {
					  ApplicationMgr.showHint(null, "操作失败，请重试！", "error");
				  }
			 });
  	},
  	showedit:function(){
  	   $("#viewApp").hide();
  	   var row = this.data;
  	   $("#applist_update").show();
  	   if ($("#appdata_update>div").length==0) {
  	   	  var html = "<div class='application_error_area' style='height:35px;line-height:35px;margin:20px auto auto;width: 200px;'> "+
  	   	             "  <img src='/bundles/fafatimewebase/images/loading.gif' style='width:32px;height:32px;margin-top:4px;float:left;'> "+
  	   	             "  <span style='float: left; padding-left: 5px;'>正在加载，请稍候……</span></div>";
	  	 	  $("#appdata_update").html(html);
	  	 	  var url = $("#viewApp").attr("url");
	  	 	  $("#appdata_update").load(url,function(){
		  	   	$(".appname:visible").val(row.appname);
		  	   	if (row.applogo!=null && row.applogo!="") {
		  	 	      $(".img_applogo:visible").attr("imageurl",row.applogo);
		  	 	      $(".img_applogo:visible").attr("src",row.logo_url);
		  	   	}
		  	   	$(".staff:visible").val(row.login_account);
		  	   	$(".textbindurl:visible").val(row.bindurl);
		  	   	$(".appdesc:visible").val(row.appdesc);
		  	   	$(".sortid:visible").val(row.sortid==null?"0":row.sortid);
		  	   	$(".application_line>.staff:visible").val(row.createstaff);
	        });
  	   }
  	   else{
  	   		$(".appname:visible").val(row.appname);
		  	  if (row.applogo!=null && row.applogo!="") {
		  	 	  $(".img_applogo:visible").attr("imageurl",row.applogo);
		  	 	  $(".img_applogo:visible").attr("src",row.logo_url);
		  	  }
		  	  $(".staff:visible").val(row.login_account);
		  	  $(".textbindurl:visible").val(row.bindurl);
		  	  $(".appdesc:visible").val(row.appdesc);
		  	  $(".sortid:visible").val(row.sortid==null?"0":row.sortid);
		  	  $(".application_line>.staff:visible").val(row.createstaff);
  	   }
  	},
  	deleteedit:function(vThis){
  		wefafaWin2.weconfirm(null,"警告","删除后将不可能恢复，确定吗？",function(p){
  			$(p).html("删除中...").removeAttr('onclick');
  			$.getJSON(deletapp,{"appid":ApplicationMgr.appid},function(d){
  				if(d.s==1)
  				{
  					$("#syshint").html("应用成功删除!").show();
  					setTimeout(function(){
  						$("#myapps").trigger('click');
  					},1000); 
  				}
  				else
  				{
  					$("#syshint").html("删除应用失败："+d.msg).show();
  				}
  				setTimeout(function(){
  						$("#syshint").html("").hide();
  					},3000); 
  			});
  		},vThis)
  	},
  	canceledit:function(){
  		if ( ApplicationMgr.appid==""){
  		  $("#appmain_menu").show();
  		  $(".app_create_box").hide();
  		  $(".app_applist_box").show();
  		}
  		else{
  		  $("#applist_update").hide();
		    $("#viewApp").show();
  		}
  	},
  	createApplication:function(){
  		if ( $(".app_apptype_item_active").length==0){
  			 $(".app_apptype_error").children().show();
  			 setTimeout(function() { $(".app_apptype_error").children().hide();},2000);
  			 return;
  		}
  		$(".app_apptype_error").children().hide();
  		this.apptype = $(".app_apptype_item_active").parents(".app_apptype_box").attr("apptype");
  		$('#selectApptype').modal('hide');
  		this.appid="";
  		var url = $(".app_addapp").attr("url");
  		$(".app_applist_box").hide();
  		$(".app_create_box").show();
  		$("#appmain_menu").hide();  		
			$("#createApp").html("<div class='urlloading'><div style='float:left;margin:150px 0px 0px 150px;'></div><span style='float:left;margin-top:168px;padding-left:5px;'>正在加载，请稍候……</span></div>");
			$("#createApp").load( url+"?appid=",function(){
			});
  	
  	},
  	selectApp:function(ev){
  		var appEle = $(ev).parents(".app_applist_list");
  		ApplicationMgr.appid = appEle.attr("appid");
  		ApplicationMgr.appname = appEle.find(".app_applist_appname").text();
  		ApplicationMgr.apptype = appEle.attr("apptype");
  		ApplicationMgr.importAppConfig();
  	},
  	component_createApp:function(){
  		ApplicationMgr.newcreate = true;
  		$('#myapps').trigger('click');
  	},
  	getAppList:function(url){
  		$("#applist").show().html("");
  		$("#template_right").hide();
  		LoadComponent($("#applist"),componentattrediturl.replace("componentname","applist"),{},function()
  		{
  			var html=[];
  			if(applist.list==null || applist.list.length==0){
  				 $(".app_addapp").hide();
	       	 html.push("<div class='application_botton_newapp' onclick=\"$('#selectApptype').modal('show')\">")
	       	 html.push("<img src='/bundles/fafatimewebase/images/menu_icon_add_active.png'><span>创建应用</span></div>");
  			};  			
  			var data = applist.list[0];
		    html=[];
			  $("#aapcount").text(data.length);
			  if ( data.length==0 ) {
	        $(".app_addapp").hide();
	       	html.push("<div class='application_botton_newapp' onclick=\"$('#selectApptype').modal('show')\">")
	       	html.push("<img src='/bundles/fafatimewebase/images/menu_icon_add_active.png'><span>创建应用</span></div>");
			  }
			  else {
	         $("#app_addapp").show();
	     	   var datarow = null;
	     	   for(var i=0;i< data.length;i++){
	     	      var state = 2; //1:申请参与开发；2:进入开发模式；3:原生功能定制
	     	 	 	  datarow = data[i];
		          html.push("<div appid='"+ datarow.appid + "' apptype='"+datarow.apptype+"' class='app_applist_list'>");
		          if ( datarow.applogo == null || datarow.applogo=="")
		           	 html.push("<img class='app_applist_icon' src='/bundles/fafatimewebase/images/normal.png'>");
		          else
		         	html.push("<img class='app_applist_icon' onerror=\"this.src='/bundles/fafatimewebase/images/normal.png'\"  src='" + datarow.applogo + "'>");
		          html.push("<div class='app_applist_info'>");
		          
		          var app_name = datarow.appname;
		          if ( datarow.apptype==HTML5_APP_CODE)
		            app_name += "(HTML5应用)";
		          else if (datarow.apptype==MOBILE_APP_CODE)
		          	app_name += "(第三方移动应用)";
		          else if (datarow.apptype==WEB_APP_CODE)
		            app_name += "(Web应用)";
		          html.push("  <span class='app_applist_appname' onclick=\"ApplicationMgr.viewApp('" + datarow.appid+"')\" title='查看应用'>" + app_name + "</span>");			        
		          html.push("  <span><span class='appversion' style='float:left;width:50px;color:#cccccc;'>"+(datarow.version!="" ? "V "+(datarow.date==null || datarow.date=="" ? "0." :"1.") + datarow.version :"&nbsp;" ) + "</span>");
		          if ( datarow.date == null || datarow.date==""){
		        	  state = 1;
		            html.push("<span class='appstatus' style='color:red;'>开发中</span></span>");
		          }
		          else
		        	  html.push("<span class='appstatus' style='color:#008000;'>已发布</span></span>");					        	
		        	if ( state==1 )
		        	    html.push("<span class='applast' style='color:#CCCCCC;'>创建者：" + (datarow.createstaff == null ? "[未知]" : datarow.createstaff) +"</span>");
		        	else
		        	 	  html.push("<span class='applast' style='color:#CCCCCC;'>最后更新："+datarow.date+" by "+ datarow.staff + "</span>");
		        html.push("</div>");
		        
		        html.push("<div class='app_applist_right'>");
		        html.push("  <div>");
		        if ( datarow.role==2 || datarow.isowner==1)
		           html.push("    <span class='app_applist_followicon'></span><span style='float:left'>&nbsp;&nbsp;</span><span class='app_applist_linkestyle' style='width:60px;text-align:right;' onclick='AppRoleManager.selectRole(this);'>用户管理</span>");
		        else
		        	html.push("  <span class='app_applist_followicon'></span><span style='float:left'>&nbsp;&nbsp;</span><span style='float:right;'>用户管理</span>");
		        html.push("  </div>");
		        
		        if (datarow.role>0 || datarow.isowner==1)
		          html.push("  <div style='margin-top:8px;'><span class='app_applist_settingicon'></span><span class='app_applist_linkestyle' onclick='ApplicationMgr.selectApp(this)' style='float:right;'>进入开发模式</span></div>");
		        else
		        	html.push("  <div style='margin-top:8px;'><span class='app_applist_settingicon'></span><span class='app_applist_linkestyle' onclick='ApplicationMgr.selectApp(this)' style='float:right;'>申请参与开发</span></div>");
		        html.push("</div>");
		        html.push("</div></div>");html.push("</div>");
	       }
	       html.push("<div class='pagecontainer'></div>");
			  }   
			  $("#appitem").html(html.join(''));
			  //设置页
				if ( data.length>6){
					 pageControl.every = 6;
				   pageControl.maxIndex = 6;
				   pageControl.status = false;
				   pageControl.control = $(".app_applist_list");
				   pageControl.totalIndex = Math.ceil(data.length / 6);
				   pageControl.container = $(".pagecontainer");
				   pageControl.callback = null;
				   pageControl.setting();
				}
				if (ApplicationMgr.newcreate){
				   $("#selectApptype").modal("show");
				   ApplicationMgr.newcreate = false;
				}
	  	});  		
  	},
  	importAppConfig:function(){
  		$(".runtimescreentop").text(ApplicationMgr.appname);
	    $("#app_portals").parent().hide();
	    $("#App_Area").hide();
	    $("#adv_defined").hide();
	    $("#basic_defined").attr("class","application_main_menu_active");
	    XMLConfigPage.appid = ApplicationMgr.appid;
	    XMLConfigPage.apptype = ApplicationMgr.apptype;
	    XMLConfigPage.switchXMLConfig();
  	},
  	selectApptype:function(ev){
  		var curControl = $(ev).find("div");
  		var curClass = curControl.attr("class");
  		if ( curClass == "app_apptype_item_active"){
  			curControl.attr("class","app_apptype_item");
  		}
  		else{
  			$(".app_apptype_item_active").attr("class","app_apptype_item");
  			curControl.attr("class","app_apptype_item_active");
  		}
  	},
  	viewApp:function(appid){
  		$(".app_applist_box").hide();
  		$(".app_create_box").hide();
  		$("#viewApp").show();
  		this.appid=appid;
  		var html="<div style='height:32px;line-height:32px;width:240px;margin:50px auto auto;'>"+
  	 	           "  <img style='float:left;width:32px;height:32px;' src='/bundles/fafatimewebase/images/loading.gif'>"+
  	 	           "  <span style='float:left;'>正在加载应用基本信息，请稍候……</span></div>";
  	 	$(".application_view").html(html);
  	 	var url = $("#viewApp").attr("viewurl");  	 	
      	$.getJSON(url,{"appid":appid},function(data){
      		menu_status = true; 
  	    	if(data.length>0){
  	    	 	ApplicationMgr.data = data[0];
			  	  ApplicationMgr.BindAppInfo();
  	    	}
		    });
  		
  	}
};

//应用配置管理
var AppConfigMgr = {  
	currentpageid:"",
	configid : "",
	tempid : "",
	tempdata : new Array(),
	runtimeData : new Array(),
	curData:null,
	backpage:function(ev){
		if ( $(ev).attr("status")=="applist"){
		  $('#viewApp').hide();
		  $('.app_applist_box').show();
		}
	  else {
	  	 $("#template_right").show();
	  	 $("#applist,#App_Area,#appmain_menu").hide();
	  	 var apptype = ApplicationMgr.apptype;
	  	 if ( apptype =="9902" || apptype =="9903" || apptype =="9904" )
	  	   ApplicationMgr.importAppConfig();	  	 	
	  	 else
	  	 	 InterfaceCustom();
	  }
	},
	//初始化操作
	Init:function(){
		this.configid = "";
		this.tempid = "";
		//this.runtimeData = new Array();
		this.curData = null;
	},
	//创建新页面
	createConfig:function(){
		this.Init(); //初始化
		$("#app_sys_error_hint").remove();
    $("#applist_pages").show();
    $("#app_template").show();

	  $("#delete_appconfig").hide();	  
	  $(".application_template_list_active").attr("class", "application_template_list");
	  $(".application_template_list").eq(0).attr("class","application_template_list_active");//设置第一个为选中状态	  
	  $(".application_interface_active").attr("class", "application_interface_item");
	  $(".user_appconfig_area").hide();
	  //创建时取第一个页面
	  this.tempid = $("#app_template>div:first").attr("temp_id");
	  AppConfigMgr.showNewPageAttr(this.tempid);
	},
	isCommiting:0
};

var AppPublishMgr = {
	type:"",
	publish_url:"",
	history_url:"",
	ispublish:false,
	show:function(){
		 $("#main_area").html("");
	   var html=new Array();
	   var appid = "";
	 	 html.push("<div style='float:left;margin-left:35px;'><div style='float:left;height:28px;line-height:28px;width:100%;'>");
	 	 if (this.type=="PORTAL") {
	 	 	 appid = "PORTAL";
	 	 	 html.push("  <span onclick='AppPublishMgr.publish(this)' class='button_default'>立即发布</span>");
	 	 }
	 	 else {
	 	 	 appid = ApplicationMgr.appid;
		 	 html.push("  <span style='float:left;'>发布当前应用【</span>");
			 html.push("  <span style='float:left;color:#cc3300;'>" + ApplicationMgr.appname + "</span>");
			 html.push("  <span style='float:left'>】</span>");
			 html.push("  <span onclick='AppPublishMgr.publish(this)' class='button_default' style='margin-left:50px;'>立即发布</span>");
		 }
		 html.push("</div>");
		 html.push("<div style='float:left;height:16px;line-height:16px;margin-top:10px;' id='publish_error'>");
		 html.push("</div>");
		 //发布历史区域
		 html.push("<div class='publish_titlefileds'><span>发布日期</span><span>发布人</span><span>发布版本</span><span style='border-right:none;'>查看配置文件</span></div>");
		 html.push("<div class='publish_box'>")
		 html.push("  <div style='margin:auto;height:30px;line-height:30px;width:150px;'><img src='/bundles/fafatimewebase/images/loading.gif' style='float:left;width:30px;height:30px;'/><span style='float:left;padding-left;5px;;'>正在获得发布历史</span></div>");
		 html.push("</div>");
		 html.push("<div class='pagecontainer' style='margin-top: 10px; margin-left: 0px; width: 600px;'></div></div>");
		 $("#main_area").html(html.join('')); 
		 html = [];
		 $.getJSON(AppPublishMgr.history_url,{"appid":appid},function(data){
			 menu_status = true;
			 if ( data.length==0){
			 	 html.push("<div class='publish_box_row' style='border-bottom:none;'>");
			 	 html.push("<span style='width:100%;color:#cccccc;'>还没有发布历史记录</span>");
			 	 html.push("</div>"); 
			 }
			 else{
			 	 var rows = null;
				 for(var i=0;i<data.length;i++)
				 {
				 	  rows = data[i];
				    html.push("<div class='publish_box_row'" + ( ((i+1) % 15==0 || i+1==data.length) ? "style='border-bottom:none;'":"")+">");
				    html.push("  <span>"+rows.publishdate+"</span>");
				    html.push("  <span>"+rows.publishstaff+"</span>");
				    html.push("  <span>"+rows.publishversion+"</span>");
				    if ( appid=="PORTAL")
				    {
				    	html.push("<span style='border-right:none;'>");
				    	if ( rows.configfileid != "")
				    	  html.push("<a style='padding:0px 5px;' href='" + file_webserver_url + rows.configfileid + "'>Android</a>");
				      if ( rows.ios_configfileid != "")
				    	  html.push("<a style='padding:0px 5px;' href='" + file_webserver_url + rows.ios_configfileid + "'>IOS</a>");
				      html.push("</span>");
				    }
				    else
				    {
				      html.push("<span style='border-right:none;'><a href='" + file_webserver_url + rows.configfileid + "'>查&nbsp;看</a></span>");
				    }
				    html.push("</div>");
				 }
			 }	 
			 $(".publish_box").html(html.join(''));
			  //设置页
				 if ( data.length>15){
				 	 pageControl.every = 15;
					 pageControl.maxIndex = 10;
					 pageControl.status = false;
					 pageControl.control = $(".publish_box_row");
					 pageControl.totalIndex = Math.ceil(data.length / 15);
					 pageControl.container = $(".pagecontainer");
					 pageControl.callback = null;
					 pageControl.setting();
				}
		});
	},
	publish:function(v){	
		if(this.ispublish) return;
		$(v).text("发布中...");
		this.ispublish = true;
		var appid = ApplicationMgr.appid;
		if ( AppPublishMgr.type=="PORTAL")
		  appid = "PORTAL";
		  
		$.getJSON(AppPublishMgr.publish_url,{"appid":appid},function(data){
			$(v).text("立即发布");
			AppPublishMgr.ispublish=false;
			 var html=new Array();
			 if ( data.s) {
			 	  html.push("<span>"+data.date+"</span>");
			 	  html.push("<span>"+data.staff+"</span>");
			 	  html.push("<span>"+data.version+"</span>");
			 	  html.push("<span style='border-right:none;'><a href='" + file_webserver_url + data.fileid + "'>查&nbsp;看</a></span>");
			 	  var temp = "";
			 	  if ( $(".publish_box").children().length <= 1){
			 	   temp = "<div class='publish_box_row' style='border-bottom:none;'>"+ html.join('')+"</div>";
			 	  	$(".publish_box").html(temp);
			 	  }
			 	  else {
			 	    temp = "<div class='publish_box_row'>"+ html.join('')+"</div>";
			 	  	$(".publish_box>div:first").before(temp);
			 	  }
			 	  html = [];
			 	  html.push(" <img src='/bundles/fafatimewebase/images/zq.png' style='float:left;width:16px;height:16px;' />");
		     	html.push(" <span style='float:left;padding-left;5px;;'>应用发布成功！</span>");
		     	var applist = $(".app_applist_list[appid='"+oneApp.appid+"']");
		     	if ( applist.length>0){
		     		var ctl = applist.find(".appstatus");
		     		ctl.text("已发布");
		     		ctl.css("color","#008000");
		     		ctl = applist.find(".appversion");
		     		ctl.text("v1."+data.version);
		     		ctl = applist.find(".applast");
		     		ctl.text("最后更新："+data.date+" by "+data.staff);
		     	}
			 }
			 else {
			 	  html.push(" <img src='/bundles/fafatimewebase/images/error.gif' style='float:left;width:16px;height:16px;' />");
		     	html.push(" <span style='float:left;padding-left:5px;'>" + data.msg + "</span>");
			 }
			 $("#publish_error").html(html.join(''));			 
			 setTimeout(function(){
			 	 $("#publish_error").html(""); 
			 },2000);
		});
	}	
};

var pageControl = {
	control:null,
	container:null,
	totalIndex:8,
	maxIndex:5,
	curIndex:1,
	callback:null,
	para:new Object,
	status: false,
	every:6,
	pageprev:function(){
		this.pagechange(parseInt(this.curIndex) - 1);
	},
	pagenext:function(){
		this.pagechange(parseInt(this.curIndex) + 1);
	},
	setting:function(){
		this.curIndex = 1;
		var html = new Array();
		if ( this.control != null)  //隐藏第二页开始的内容
		  this.control.slice(this.every).hide();
    if ( this.maxIndex > this.totalIndex ) this.maxIndex = this.totalIndex;    
    //上一页
		if ( this.totalIndex > this.maxIndex){
		   html.push("<span id='pageprev' onclick='pageControl.pageprev()' style='display:none;' class='pagesprev'><span class='pagesprev_arrow'></span>");
		   html.push("<sapn UNSELECTABLE='on' style='float:left;margin-right:2px;'>上一页</span></span>");
		}
		html.push("<div style='float:left;' id='pagearea'>");
		for(var i=1;i <= this.totalIndex;i++){
			if ( i==1)
			  html.push("<span class='pagestyle_active' value='" + i + "' onclick=\"pageControl.pagechange('"+i+"')\">"+ i+"</span>");
			else if ( i<= this.maxIndex)
				html.push("<span class='pagestyle' value='" + i + "' onclick=\"pageControl.pagechange('" + i +"')\">"+ i+"</span>");
		  else
		  	html.push("<span class='pagestyle' style='display:none;' value='" + i + "' onclick=\"pageControl.pagechange('" + i +"')\">"+ i+"</span>");
		}
		html.push("</div>");		
		//下一页
		if ( this.totalIndex > this.maxIndex)
		  html.push("<span id='pagenext' onclick='pageControl.pagenext()' class='pagesprev'><span UNSELECTABLE='on' style='float:left;margin-left:2px;'>下一页</span><span class='pagesnext_arrow'></span></span>");
		this.container.html(html.join(''));
	},
	pagechange:function(pageno) {	
	  if ( this.status ) return;
		if ( this.curIndex == pageno) return;
		this.curIndex = pageno;
		$("#pagearea .pagestyle_active").attr("class","pagestyle");
		var currentControl = $("#pagearea .pagestyle[value='" + pageno +"']");
		if (!currentControl.is(":visible")){
			$("#pagearea .pagestyle").hide();
			if ( pageno % this.maxIndex == 1)
			  $("#pagearea .pagestyle").slice(pageno-1,pageno+this.maxIndex-1).show();
			else
				$("#pagearea .pagestyle").slice(pageno-this.maxIndex,pageno).show();
		}
		if ( this.maxIndex <= this.totalIndex ) {
				if ( pageno==1)
					$("#pageprev").hide();
			  else
			  	$("#pageprev").show();
				if (pageno == this.totalIndex)
					$("#pagenext").hide();
			  else
			  	$("#pagenext").show();

				currentControl = $("#pagearea .pagestyle[value='" + pageno +"']");
				currentControl.attr("class","pagestyle_active");
	  }
		if ( this.callback == null){
			currentControl.attr("class","pagestyle_active");
			this.control.hide();
  	  var start = (pageno-1) * this.every;
	    var end =   start + this.every;
		  this.control.slice(start,end).show();
		}
		else {
		   this.status = true;
			 this.callback(this.curIndex);//选中回调事件
		}
	}
}


function Container_ComponentSelected(sender,functionid){
	 if ( sender == null ) return;
	 //var componentobj = oneApp.getInterComponent(sender.functionid,sender.index);
	 if(AppConfigManager)
	 	AppConfigManager.interNodeSelected(functionid);
}

//创建默认页面
function CreateDefaultPage(){
	 //创建导航组件
   if ( oneApp.appid.toLowerCase()!="portal") return;
   ComponentEdit.add("component_nav",null);
   SaveApplicationConfig(oneApp);
   //创建页面及每个页面放置一个标题组件
   var appid = oneApp.appid;
   var functionid = "";
   //创建默认页面  
   functionid = appid + "-1-0";
   oneApp.addInterfaceWidthTempXml(functionid ,"首页","<template><title color='#FFFFFF'><text>首页</text></title></template>");
 	 functionid = appid + "-1-1";
 	 oneApp.addInterfaceWidthTempXml(functionid ,"通讯录","<template><title color='#FFFFFF'><text>通讯录</text></title></template>");
 	 functionid = appid + "-1-2";
 	 oneApp.addInterfaceWidthTempXml(functionid ,"消息中心","<template><title color='#FFFFFF'><text>消息中心</text></title></template>");
 	 functionid = appid + "-1-3";
 	 oneApp.addInterfaceWidthTempXml(functionid ,"企业微博","<template><title color='#FFFFFF'><text>企业微博</text></title></template>");
 	 functionid = appid + "-1-4";
 	 oneApp.addInterfaceWidthTempXml(functionid ,"设置","<template><title color='#FFFFFF'><text>设置</text></title></template>");
 	 SaveApplicationConfig(oneApp); 	 
 	 
 	 var componentobj = oneApp.getInterComponent(oneApp.cFuncid,1);
	 var attrs = componentobj.attrs.navitems;
	 for (var i=0;i<attrs.length;i++){
		 	 functionid = appid + "-1-" + i;
		   componentobj.attrs.navitems[i].functionid.text = functionid;
		   componentobj.attrs.navitems[i].functionid.target = "self";
	 }
	 oneApp.setInterComponent(oneApp.cFuncid,1,componentobj);
	 ComponentEdit.add("component_applist",null);
	 
	 SaveApplicationConfig(oneApp);	 
}

//界面定制
var AppConfigManager={
	treeObj:null,
	cFuncid:null,
	tid:null,
	hint:true, //编辑保存时是否有提示
	onSelectpage:null,
	initConfigPage:function(){ //界面定制页面加载过后，进行初始化
		var setting = {
			view: {
				selectedMulti: false
			},
			edit: {
				enable: false,
				editNameSelectAll: false
			},
			data: {
				simpleData: {
					enable: true
				}
			},
			callback:{
				onClick:function(event, treeId, treeNode){
					AppConfigManager.tid = treeNode.tId;
					if(treeNode.type=="interface"){
						AppConfigManager.interNodeSelected(treeNode.functionid);
					}
					else if(treeNode.type=="component"){
						AppConfigManager.componentNodeSelected(treeNode.functionid,treeNode.index);
					}
				}
			}
		};
		var zNodes =oneApp.getAppOrgData();
		$.fn.zTree.init($("#tree_component"), setting, zNodes);
		AppConfigManager.treeObj = $.fn.zTree.getZTreeObj("tree_component");		
		//
		ComponentEdit.init(oneApp);
		//默认选中第一个节点
		AppConfigManager.interNodeSelected(oneApp.getRootFunctionid());
	},
	reloadConfigPage:function(functionid){
		
	},
	getInterNodeId:function(functionid){
		return functionid;
	},
	getComNodeId:function(functionid,index){
		return functionid.toString()+"_"+index.toString();
	},
	selectInterNode:function(functionid){
		if ( AppConfigManager.treeObj == null) return;
		AppConfigManager.treeObj.selectNode(AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getInterNodeId(functionid)));
	},
	selectComNode:function(functionid,index){
		if ( AppConfigManager.treeObj == null) return;
		AppConfigManager.treeObj.selectNode(AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getComNodeId(functionid,index)));
	},
	interNodeSelected:function(functionid){  //界面节点被选择时
		AppConfigManager.cFuncid=functionid;
		//设置界面节点为选中状态
		AppConfigManager.selectInterNode(functionid);
		//模拟器中加载相应页面
		var theComponent=oneApp.getSourceComponent(functionid);
		if(theComponent!=null && (theComponent.code=="component_nav" || theComponent.code=="component_tabs")){
			oneApp.loadInterObj(functionid,{params:{sender:"sender"}});
		}
		else{
			oneApp.loadInterObj(functionid);
		}
		//加载界面的组件列表
		var functiontype=oneApp.getInterData(functionid).functiontype;
		if(functiontype=="1"){
			AppConfigManager.loadSelectedComponents(functionid,functiontype);
			if (AppConfigManager.treeObj != null && $(".interface_center:visible").children().length==0 ){			  
				if ( ComponentEditView.isAdd()){
			  	var html = new Array();
				  html.push("<div class='component_createApp_area'>");
				  html.push("  <div class='component_createicon'><span><span></div>");
				  html.push("  <div class='component_createtitle'><span>您还未在该区域添加任何组件</span></div>");
				  html.push("  <div class='component_createtitle' style='margin-top:-5px;'><span>请添加新组件</span></div>");
				  html.push("  <div style='width:100%;margin-top:35px;'><span class='component_createApp' href='#selectComponentDialog'	data-backdrop='static' data-toggle='modal' onclick='AppConfigManager.loadComponentList();'>添加组件</span></div>");
				  html.push("</div>");
					$(".interface_center:visible").html(html.join(''));
			 }
			}
		}
		else if(functiontype=="2"){//html5			
		}
		else if(functiontype=="3"){//native
			AppConfigManager.loadSelectedComponents(functionid,functiontype);
		}
		AppConfigManager.expandNode(AppConfigManager.cFuncid,null,true);
	},
	componentNodeSelected:function(functionid,index){
		AppConfigManager.cFuncid=functionid;
		//设置组件节点为选中状态
		AppConfigManager.selectComNode(functionid,index);
		//模拟器中加载相应页面
		var theComponent=oneApp.getSourceComponent(functionid);
		if(theComponent!=null && (theComponent.code=="component_nav" || theComponent.code=="component_tabs")){
			oneApp.loadInterObj(functionid,{params:{sender:"sender"}});
		}
		else{
			oneApp.loadInterObj(functionid);
		}
		//模拟器中控制组件状态
		$(".component").removeClass("component_hover");
		$(".component[aindex='"+index.toString()+"']").addClass("component_hover");
		//加载组件编辑界面
		ComponentEditView.attributeRight(functionid,index);
		AppConfigManager.expandNode(AppConfigManager.cFuncid,index,true);
	},
	loadSelectedComponents:function(functionid,functiontype){  //点击界面节点时加载组件列表
		$("#component_editarea").hide();
		$("#component_listarea").html(null).show();
		ComponentEditView.showRight($("#component_listarea"),ComponentEdit.getComponentList());
	},
	loadComponentList:function(){
		var $list=$("#selectComponentDialog .componentlist");
		var $desc=$("#selectComponentDialog .componentdesc");
		$list.html(null);
		$("#selected_notice").text("");
		//加载可选择组件列表
		if(ComponentEditView.isAdd()) {
			 $(".componentlist").css("border-right","1px solid #bbb");
			 var component_code = "";
			 var component_obj = oneApp.getSourceComponent(AppConfigManager.cFuncid);
			 if ( component_obj != null) component_code = component_obj.code;
			  
			 $inter=$(".interface[functionid='"+AppConfigManager.cFuncid+"']");
			 var pComponent=oneApp.getSourceComponent(AppConfigManager.cFuncid);
			 while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
			   $inter=$(".interface[functionid='"+pComponent.functionid+"']");
				 pComponent=oneApp.getSourceComponent(pComponent.functionid);
			 }
			 //列表控件
			 var count = $inter.find(".cp_list_static_GRID3,.cp_list_static_GRID4,.cp_list_static_normal").length;
			 if ( count==0 || (count >0 && count < parseInt(component_list.maxcount)) )
			   $list.append(component_list.getHtmlEle({"type":"1","dom_index":"0"}));
	  	 //只有门户配置时应用列表才有用
	  	 if(oneApp.appid=="PORTAL") { 		 
	  	   if($inter.find(".cp_applist").length==0)
	  	 	   $list.append(component_applist.getHtmlEle({"type":"","dom_index":"0"}));
	  	 }
	  	 if(component_code != "component_tabs" && $inter.find(".cp_title").length==0)
	  	   $list.append(component_title.getHtmlEle({"type":"","dom_index":"0"}));
	  	 if(component_code != "component_tabs" && $inter.find(".cp_menu_R,.cp_menu_L").length==0)
	  	   $list.append(component_menu.getHtmlEle({"type":"","dom_index":"0"}));
	  	 if($inter.find(".cp_search").length==0)
	  	   $list.append(component_search.getHtmlEle({"type":"","dom_index":"0"}));
       count = $inter.find(".cp_switch").length;
       if ( count==0 || (count >0 && count < parseInt(component_switch.maxcount)))
         $list.append(component_switch.getHtmlEle({"type":"","dom_index":"0"}));	  	 	
	  	 if (component_code != "component_tabs" && $inter.find(".cp_nav").length==0)
	  	   $list.append(component_nav.getHtmlEle({"type":"","dom_index":"0"}));	  	 	
	  	 if ( component_code != "component_tabs"){
	  	   count = $inter.find(".component_tabs").length;
			   if ( count==0 || (count >0 && count < parseInt(component_tabs.maxcount)))
	  	     $list.append(component_tabs.getHtmlEle({"type":"","dom_index":"0"}));
	  	 }
	  	 if ( component_code=="component_tabs"){
	  	   $area = $(".tabs_div:visible");
	  	 	 if ( $area.find(".cp_summary").length==0)
	  	 	   $list.append(component_userprofile.getHtmlEle({"type":"","dom_index":"0"}));
	  	 	 if ( $area.find(".cp_userbaseinfo").length==0 )
	  	 	   $list.append(component_userbasicinfo.getHtmlEle({"type":"","dom_index":"0"}));
	  	 }
	  	 else{
	  	   if ( $inter.find(".cp_summary").length==0)
	  	 	   $list.append(component_userprofile.getHtmlEle({"type":"","dom_index":"0"}));
	  	 	 if ( $inter.find(".cp_userbaseinfo").length==0 )
	  	 	   $list.append(component_userbasicinfo.getHtmlEle({"type":"","dom_index":"0"}));
	  	 }
	  	 count = $("#component_listarea>.application_component_icon[component='component_functionbar']").length
	  	 if ( count==0 || (count >0 && count < parseInt(component_functionbar.maxcount)))
	  	   $list.append(component_functionbar.getHtmlEle({"type":"","dom_index":"0"}));
	  	 	
	  	 $list.append(component_matchlist.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_matchdetail.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_goodsdetail.getHtmlEle({"type":"","dom_index":"0"}));	  	 		  	 		
	  	 $list.append(component_groupnews.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_circlenews.getHtmlEle({"type":"","dom_index":"0"}));	  	 	
	  	 $list.append(component_publicaccount.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_repository.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_contacts.getHtmlEle({"type":"","dom_index":"0"}));
	  	 $list.append(component_message.getHtmlEle({"type":"","dom_index":"0"}));
	  	 //$list.append(component_enoweibo.getHtmlEle({"type":"","dom_index":"0"}));//企业微博就是圈子动态
	  	 $list.append(component_setting.getHtmlEle({"type":"","dom_index":"0"}));	  	 	
	  }
	  else
	  {
	  	$list.append("<span style='color:#ccc'>无！导航页面和原生功能页面都不允许添加其他组件！</span>");
	  	$(".componentlist").css("border-right","none");
	  }
	  $desc.html(ComponentAttr.getComponentObject("component_list").desc);
	  $(".application_component_icon").unbind("click").bind("click",function(event){
	  	$("#selected_notice").text("");
	  	var code=$(this).attr("component");
	  	var componentObject=ComponentAttr.getComponentObject(code);
			//添加导航菜单时需要提示,如果页面上已有内容
			if( ( componentObject!=null && ( code=="component_nav"|| componentObject.type=="native"))) {
				if(code=="component_nav")
				 	$("#selected_notice").text("选择导航控件将覆盖页面上的所有内容。").show();	
				else
					$("#selected_notice").text("原生功能控件将清空页面上的其他内容。").show();
			}
	  	$(".application_component_icon").removeClass("application_component_icon_selected");
	  	$(this).addClass("application_component_icon_selected");
	  	$desc.html(ComponentAttr.getComponentObject($(this).attr("component")).desc);
	  });
	},
	addComponent:function(){
		var $list=$("#selectComponentDialog .componentlist");
		var $selected=$list.find(".application_component_icon_selected");
		if ( $selected.length==0){
			$("#selected_notice").text("请选择需要添加的组件！");
		}
		else {
			var code=$selected.attr("component");
			var componentobj = null,attrs = null;
		  var component_obj = ComponentAttr.getComponentObject(code);
			if( $("#component_listarea").children().length>1 && ( component_obj!=null && ( code=="component_nav"|| component_obj.type=="native"))) {
				var hint = "";
				if ( code=="component_nav")
					hint = "选择导航控件将覆盖页面上的所有内容,请问是否继续？";
			  else
			  	hint = "原生功能控件将清空页面上的其他内容,请问是否继续？"
			 	wefafaWin2.weconfirm(null,"提示",hint,function(a){
			 		//如果是导航控件
			 		var index1=ComponentEdit.add(code);
					if(code=="component_nav"){
			       if ( !index1) return;
						 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index1);
					   attrs = componentobj.attrs.navitems;
					   for (var i=0;i<attrs.length;i++){
					   	 var functionid = AppConfigManager.cFuncid + "-1-" + i;
					   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template><title color='#FFFFFF'><text>"+attrs[i].itemname+"</text></title></template>");
					     componentobj.attrs.navitems[i].functionid.text = functionid;
					     componentobj.attrs.navitems[i].functionid.target = "self";
					   }
					   oneApp.setInterComponent(AppConfigManager.cFuncid,index1,componentobj);
					   SaveApplicationConfig(oneApp);
					}
					$("#selectComponentDialog").modal("hide");
					AppConfigManager.refreshInterNode();
			    AppConfigManager.componentNodeSelected(AppConfigManager.cFuncid,index1);			
			    AppConfigManager.expandNode(AppConfigManager.cFuncid,index1,true);		 
				},{"index":"1"});
				return;
			}
			$("#selectComponentDialog").modal("hide");
			var index=ComponentEdit.add(code);
			if ( !index) return;
	 		//如果是导航控件
			if(code=="component_nav"){
				 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.navitems;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-1-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template><title color='#FFFFFF'><text>"+attrs[i].itemname+"</text></title></template>");
			     componentobj.attrs.navitems[i].functionid.text = functionid;
			     componentobj.attrs.navitems[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}			
			//如果是二级分类
			if(code=="component_tabs"){
				 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.tabitems;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-" +index.toString()+ "-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template></template>")
			     componentobj.attrs.tabitems[i].functionid.text = functionid;
			     componentobj.attrs.tabitems[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}
			if (code=="component_userprofile"){
         componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.items;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-" +index.toString()+ "-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template></template>")
			     componentobj.attrs.items[i].functionid.text = functionid;
			     componentobj.attrs.items[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);				
			}
			if (code == "component_userbasicinfo"){
				componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);			   
			  var functionid = AppConfigManager.cFuncid + "-" +index.toString()+ "-0";
			  oneApp.addInterfaceWidthTempXml(functionid ,"详细页面","<template></template>")
			  componentobj.attrs.functionid.text = functionid;
			  componentobj.attrs.functionid.target = "blank";
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}
			//新闻列表
			if(code=="component_list"){
				 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.listitems;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-" + index.toString() + "-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template><title color='#FFFFFF'><text>"+attrs[i].itemname+"</text></title></template>");
			     componentobj.attrs.listitems[i].functionid.text = functionid;
			     componentobj.attrs.listitems[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}			
			//快捷菜单
			if(code=="component_menu"){
				 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.menuitems;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-" + index.toString() + "-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].itemname,"<template><title color='#FFFFFF'><text>"+attrs[i].itemname+"</text></title></template>");
			     componentobj.attrs.menuitems[i].functionid.text = functionid;
			     componentobj.attrs.menuitems[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}
		  if(code =="component_search"){
				 componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
				 var functionid = AppConfigManager.cFuncid + "-" + index.toString() + "-0";
			   componentobj.attrs.functionid.text= functionid;
			   componentobj.attrs.functionid.target="blank";			   
			   oneApp.addInterfaceWidthTempXml(functionid ,"详细内容","<template><title color='#FFFFFF'><text>详细内容</text></title></template>");
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}
		  if ( code=="component_functionbar"){			   
			   componentobj = oneApp.getInterComponent(AppConfigManager.cFuncid,index);
			   attrs = componentobj.attrs.items;
			   for (var i=0;i<attrs.length;i++){
			   	 var functionid = AppConfigManager.cFuncid + "-" + index.toString() + "-" + i;
			   	 oneApp.addInterfaceWidthTempXml(functionid ,attrs[i].text,"<template><title color='#FFFFFF'><text>"+attrs[i].text+"</text></title></template>");
			     componentobj.attrs.items[i].functionid.text = functionid;
			     componentobj.attrs.items[i].functionid.target = "self";
			   }
			   oneApp.setInterComponent(AppConfigManager.cFuncid,index,componentobj);
			   SaveApplicationConfig(oneApp);
			}
			AppConfigManager.refreshInterNode();
			AppConfigManager.componentNodeSelected(AppConfigManager.cFuncid,index);			
			AppConfigManager.expandNode(AppConfigManager.cFuncid,index,true);			
		}
	},
	refreshInterNode:function(){
		if (AppConfigManager.treeObj==null) return;
		var pNode=AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getInterNodeId(AppConfigManager.cFuncid));
		//删除当前页面节点下的所有子节点
		AppConfigManager.treeObj.removeChildNodes(pNode);
		var appdata=oneApp.getAppData();
		var nodes=oneApp.getAppOrgData([],appdata,"",AppConfigManager.cFuncid);
		nodes.shift();
		AppConfigManager.treeObj.addNodes(pNode,nodes);
	},
	delComponent:function(index){
		AppConfigManager.refreshInterNode();
		//选中当前页面
		AppConfigManager.interNodeSelected(AppConfigManager.cFuncid);
	},
	addTreeNode:function(data,index){
		 if ( AppConfigManager.treeObj==null) return;
	   var zTree = AppConfigManager.treeObj;
		 var node = AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getComNodeId(data.pId,index));
	   zTree.addNodes(node, data);
	   if(data.type=="interface")
	   		AppConfigManager.interNodeSelected(data.functionid);
	   else
	   		AppConfigManager.componentNodeSelected(data.functionid,data.index);

	},
	addComTreeNode:function(data){
		var zTree = AppConfigManager.treeObj;
		if ( zTree == null ) return;
		var node =  zTree.getNodeByTId(AppConfigManager.tid);
	  zTree.addNodes(node, data);
	  AppConfigManager.componentNodeSelected(data.functionid,data.index);
	  AppConfigManager.treeObj.selectNode(node.children[node.children.length-1]);
	},
	removeTreeNode:function(vItemIndex,index){
		 if (AppConfigManager.treeObj == null) return;
		 var node =  AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getComNodeId(AppConfigManager.cFuncid,index));
		 if (node == null) return;
		 for( var i=0;i<node.children.length;i++){
		 	 if (node.children[i].functionid == vItemIndex){
		 	 	 AppConfigManager.treeObj.removeNode(node.children[i]);
		 	 	 break;
		 	 }
		 }
	},
	expandNode:function(pid,index,state){  //展开或折叠某个节点
		var node=null;
		if ( AppConfigManager.treeObj == null) return;
		if(index=='' || index==null){
			node=AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getInterNodeId(pid));
		}
		else{
			node = AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getComNodeId(pid,index));
		}
		if(node!=null)
	  	AppConfigManager.treeObj.expandNode(node,state, false, true,false);
	},
	selectedPage:function(){
		var status = $(".component_newpage_icon").attr("status");
		if ( status=="1")
		  result = "";
		else{
			 var ctl = $(".component_selectedpage_label input:checked");
			 if ( ctl.length==0)
			 	 $("#selectpagehint").html("请选择页面定制方式");
			 else
			 	  result = ctl.attr("value");
		}
		if ( this.onSelectpage!=null){
			$("#selectPageDialog").modal("hide");
			this.onSelectpage(result);//选中回调事件
		}
	}
}

//应用权限管理
var AppRoleManager = {
	appid:"",
	geturl:"",
	getaccount:"",
	saveurl:"",
	applyurl:"",
	removeurl:"",
	type:"dev",
	recordcountByRole:32,
	recordcountByAccount:27,
	roleicon:"/bundles/fafatimewebase/images/role.png",  //角色用户默认图片
	orgicon:"/bundles/fafatimewebase/images/default_circle_re.png",   //组织机构
	totalcount:{"dev":0,"user":0,"role":0,"org":0},
	curpageindex:1,
	selectRole:function(ev) {
		$(".app_role_body").show();
		$(".app_role_showaccount").hide();
		var app = $(ev).parents(".app_applist_list");
		var appname = app.find(".app_applist_appname").text();
		this.appid = app.attr("appid");		
		$(".app_role_title_area").html("<span class='app_role_title'>" + appname +"</span>应用权限设置");
		$(".app_role_window").modal("show");
		
		$(".app_role_menu_area>.app_role_selected_menu").attr("class","app_role_default_menu");
		$(".app_role_menu_area>span:first").attr("class","app_role_selected_menu");
		$(".app_role_area").children().hide();
		$(".app_role_area>div[state='dev']").show();
		AppRoleManager.getRole(1,"");
	},
	//选择账号面板
	show:function(){
		$(".app_role_hint_area").children().hide();
		$(".app_role_page_area").html("");
		$(".app_role_selectListArea").html("");
		$(".app_role_body").hide();
		$(".app_role_showaccount").show();
		var type = AppRoleManager.type;
		if (type=="dev"){
		  $(".app_role_add_hint").text("为应用指定开发者");
		  $(".searchaccount").attr("placeholder","请输入姓名或账号");		  
		}
		else if ( type=="user"){
			$(".app_role_add_hint").text("为应用指定普通用户");
			$(".searchaccount").attr("placeholder","请输入姓名或账号");
		}
	  else if ( type=="role"){
	  	$(".app_role_add_hint").text("为应用指定角色用户");
	  	$(".searchaccount").attr("placeholder","请输入角色名称");		  
	  }
	  else if (type="org"){
	  	$(".app_role_add_hint").text("为应用指定组织机构");
	  	$(".searchaccount").attr("placeholder","请输入组织机构名称");
	  }
	  $(".searchaccount").val("");
	  AppRoleManager.getAccount("",1);
	},
	getAccount:function(searchtext,pageindex){
		var html = new Array();
		html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/loading.gif'><span>正在获得数据信息，请稍候……</span></div>");
		$("#accountlist").html(html.join(''));
		var para = {"type":AppRoleManager.type,"appid":AppRoleManager.appid,"searchtext":searchtext,"pageindex":pageindex,"pagerecord":AppRoleManager.recordcountByAccount};
	  $.post(AppRoleManager.getaccount,para,function(data){
	  	 AppRoleManager.showAccount(data,pageindex);
	  });
	},
	//显示用户账号
	showAccount:function(data,pageindex){
		var type = AppRoleManager.type;
		var html = new Array();
		if ( data!=null ){
			var list = data.list;			
			var header = "";
			if (list.length>0){
				for(var i=0;i<list.length;i++){
					html.push("<span class='app_role_user_header app_role_viesaccount' keyid='"+list[i].id+"' onclick='AppRoleManager.selectedAccount(this)'>");
					if (type=="dev" || type=="user"){
					  header = list[i].header;
					  header = header =="" ? "/bundles/fafatimewebase/images/tx.jpg":header;
				  }
				  else if (type=="role")
				  	header = this.roleicon;
				  else if (type=="org")
				  	header = this.orgicon;
					html.push("   <img src='"+header+"' title='"+list[i].name+"'><span>"+list[i].name+"</span></span>");
				}
			}
			else{
				html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/ts.png'><span>没有可供选择的数据记录！</span></div>");
			}
			pageControl.status = false;
			if (pageindex==1){
				var record = parseInt(data.totalcount);
				if (record>0 && record>AppRoleManager.recordcountByAccount){
					 pageControl.every = AppRoleManager.recordcountByAccount;
					 pageControl.maxIndex = 10;
					 pageControl.status = false;
					 pageControl.control = $("#accountlist");
					 pageControl.totalIndex = Math.ceil(record /AppRoleManager.recordcountByAccount);
					 pageControl.container = $(".app_role_page_area");
					 pageControl.callback = function(index){
					 	 var searchtext = $.trim($(".searchaccount").val());					 	 
					 	 AppRoleManager.getAccount(searchtext,index);
					 };
					 pageControl.setting();
				}
				else{
					$(".app_role_page_area").html("");
				}
		  }	
		}
		else
			html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/ts.png'><span>没有可供选择的数据记录！</span></div>");
		$("#accountlist").html(html.join(''));
	},
	selectedAccount:function(ev){
	   var id = $(ev).attr("keyid");
	   var img = $(ev).find("img").attr("src");
	   var name = $.trim($(ev).text());
	   var html = new Array();
	   html.push("<span class='app_role_selecticon' selectedid='"+id+"' onclick='AppRoleManager.removeSelect(this)'>");
	   html.push("  <img src='"+img+"'/><span>"+name+"</span></span>");
	   if ( $(".app_role_selectListArea").children().length==0)
	     $(".app_role_selectListArea").append(html.join(''));
	   else
	   	 $(".app_role_selectListArea>span:visible:first").before(html.join(''));
	   $(ev).remove();
	   if ($(".app_role_selectListArea").children().length>10){
	   	 $(".app_role_pageLeft,.app_role_pageRight").show();
	   }
	   else{
	   	 $(".app_role_pageLeft,.app_role_pageRight").hide();
	   }	   
	},
	pageleft:function(){
	  var cur = this.curpageindex-1;
	  if ( cur >0 ){
	  	this.curpageindex -= 1;
	  	$(".app_role_selectListArea>span").hide();
	    var startindex = 10 * (cur - 1);
		  var endindex = 10 * cur;
		  $(".app_role_selectListArea>span").slice(startindex,endindex).show();
	  }		 
	},
	pageright:function(){
	  var total = Math.ceil($(".app_role_selectListArea>span").length/10);
	  var cur = this.curpageindex+1;
	  if ( cur <= total ){
	  	this.curpageindex += 1;
	  	$(".app_role_selectListArea>span").hide();
	    var startindex = 10 * (cur - 1);
		  var endindex = 10 * cur;
		  $(".app_role_selectListArea>span").slice(startindex,endindex).show();
	  }
	},
	//回车查询数据
	keysarch:function(){
		var keyword = $.trim($(".searchaccount").val());
		AppRoleManager.getAccount(keyword,1);
	},
	//获得用户权限
	getRole:function(pageindex,searchtext){
		var type = this.type;
		var html = new Array();
		if (type=="dev")
		  html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/loading.gif'><span>正在获得开发者账号信息，请稍候……</span></div>");
		else if (type=="user")
		  html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/loading.gif'><span>正在获得用户账号信息，请稍候……</span></div>");
		else if ( type=="role")
			html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/loading.gif'><span>正在获得用户角色信息，请稍候……</span></div>");
		else if (type=="org")
			html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/loading.gif'><span>正在获得组织机构信息，请稍候……</span></div>");
		if ( html.length>0){
		  $(".app_role_area div[state='"+type+"']").html(html.join(""));
		  var para = {"appid":this.appid,"type":type,"pageindex":pageindex,"searchtext":searchtext,
		  	 "recordcount":this.recordcountByRole };    
		  $.getJSON(this.geturl,para,function(data){
				AppRoleManager.loadRole(pageindex,type,data);
		  });
	  }	  
	},
	loadRole:function(pageindex,type,data){		
		var html = new Array();
		var existsid = "";
		var list = data.list;
		var totalcount = data.totalcount;
		var header = "";
		if ( list!=null && list.length>0){
			for(var i=0;i<list.length;i++){
				html.push("<span class='app_role_head_box' del=0>");
			  html.push("  <span class='app_role_user_header'>");
			  if (type=="dev" || type=="user"){
				  header = list[i].header;
			    header = header=="" ?"/bundles/fafatimewebase/images/tx.jpg" : header;
			  }
			  else if (type=="role")
			  	header=this.roleicon;
			  else if(type=="org")
			  	header=this.orgicon;
			  html.push("  <img src='"+header+"'>");
			  html.push("  <span class='name'>"+list[i].name+"</span></span>");
			  html.push("  <span title='删除用户权限' class='icon-remove app_role_remove' keyid='"+list[i].id+"' onclick='AppRoleManager.removeRole(this)'></span></span>");
			}
		}
		else{
			var msg = "";
			if (type=="dev")
			  msg = "应用目前没有开发者，请添加！";
			if (type=="user")
			  msg = "应用目前没有用户账号，请添加！";
			if (type=="role")
			  msg = "应用目前没有角色用户，请添加！";
			else if ( type=="org")
				msg = "应用目前没有组织机构权限，请添加！";
		  html.push("<div class='app_role_loading'><img src='/bundles/fafatimewebase/images/ts.png'><span>"+msg + "</span></div>");
		}	  
		if ( html.length>0)
		  $(".app_role_area>div[state='"+type+"']").html(html.join(""));
		pageControl.status = false;  
		//翻页处理
		var page = $(".pagebyrole[type='"+type+"']");
		var record = parseInt(data.recordcount);
		if ( pageindex==1){
			if (type=="dev")
			  this.totalcount.dev=record;
			else if(type=="user")
				this.totalcount.user=record;
		  else if(type=="role")
		  	this.totalcount.role=record;
		  else if (type=="org")
		  	this.totalcount.org=record;	
			$("#role_recordcount").html("( "+record+" )");
	  }		
		if (pageindex==1 && record>0 && record>AppRoleManager.recordcountByRole){
			 page.show();
			 pageControl.every = AppRoleManager.recordcountByAccount;
			 pageControl.maxIndex = 10;
			 pageControl.status = false;
			 pageControl.control = $(".app_role_area>div[state='"+type+"']")
			 pageControl.totalIndex = Math.ceil(record /AppRoleManager.recordcountByRole);
			 pageControl.container = page;
			 pageControl.callback = function(index){
			 	 var searchtext = $.trim($(".text_sarch").val());					 	 
			 	 AppRoleManager.getRole(index,searchtext);
			 };
			 pageControl.setting();	 
		}
				
		$(".app_role_head_box").live("mouseover",function(){
			 if ( $(this).attr("del")=="0")
			   $(this).find(".app_role_remove").show();
		});
		$(".app_role_head_box").live("mouseout",function(){
			$(this).find(".app_role_remove").hide();
		});
	},
	saveRole:function(){
		var selectedCtl = $(".app_role_selectListArea").children();
		var selectedid = new Array();
		if ( selectedCtl.length==0){
			$(".app_role_hint_area").children().hide();
			$(".app_role_hint_area .app_role_erroricon").show();
			$("#app_role_message").text("请至少选择一项！").show();
			return;			 
		}
		else{
			for(var i=0;i< selectedCtl.length;i++){
				selectedid.push(selectedCtl[i].getAttribute("selectedid"));
			}
		}
		$(".app_role_hint_area").children().hide();
		$(".app_role_hint_area>img").show();
		$("#app_role_message").text("正在保存用户权限，请稍候……").show();
		$.post(this.saveurl,{"appid":AppRoleManager.appid,"type":AppRoleManager.type,"selectedid":selectedid },function(data){
			 if ( data.success){
			 	 //处理选择的项
			 	 var html = new Array();
	 	     for(var i=0;i<selectedCtl.length;i++){
	 	       html.push("<span class='app_role_head_box' del=0>");
	 	 	     html.push("  <span class='app_role_user_header'>");
	 	 	     html.push("  <img src='"+selectedCtl.eq(i).find("img").attr("src")+"'>");
	 	 	     html.push("  <span class='name'>"+$.trim(selectedCtl.eq(i).text())+"</span></span>");
	 	 	     html.push("  <span onclick='AppRoleManager.removeRole(this)' keyid='"+selectedCtl.eq(i).attr("selectedid")+"' class='icon-remove app_role_remove' title='删除用户权限'></span></span>");
	 	     }
	 	     //被添加成员的控件
	 	     var ctl = $(".app_role_body .app_role_area>.app_role_userlist[state='"+AppRoleManager.type+"']");
	 	     if (ctl.find(".app_role_loading").length>0)
	 	       ctl.html(html.join(''));
	 	     else{
	 	     	 ctl.children().eq(0).before(html.join('')); //总是添加到最前面
	 	     }
			 	 $(".app_role_showaccount").hide();
			 	 $(".app_role_body").show();
			 	 showSuccessBox("保存成功");
			 }
			 else{
			 	 $(".app_role_hint_area").children().hide();
			 	 $("#app_role_message").text("保存数据记录失败，请重试！").show();
			 }
		});
	},
	removeRole:function(ev){
		var type = this.type;
		var id = $(ev).attr("keyid");
		var hint = "";
		var uername = $.trim($(ev).prev().text());
		if (type=="dev" )
			hint="确认要删除开发者<span class='app_role_delname'>"+uername+"</span>吗？"
		else if(type=="user")
			hint="确认要删除用户<span class='app_role_delname'>"+uername+"</span>吗？"
	  else if(type=="role")
	  	hint="确认要删除角色<span class='app_role_delname'>"+uername+"</span>吗？"
	  else if(type=="org")
	  	hint="确认要删除组织机构<span class='app_role_delname'>"+uername+"</span>吗？"
		var removeControl = $(ev).parents(".app_role_head_box");
		wefafaWin2.weconfirm(null,"提示",hint,function(a){
			var para={"type":type,"id":id};
			removeControl.find(".name").text("…删除中…");
			removeControl.attr("del",1);
			$.post(AppRoleManager.removeurl,para,function(data){
				if (data.success)
				  removeControl.remove();
				else{
					removeControl.attr("del",0);
					removeControl.find(".name").text(username);
				}
			});
			  
		},null);
	},
	//申请参与开发者
	apply_developer:function(en){
		var app = $(ev).parents(".app_applist_list");
		var appname = app.find(".app_applist_appname").text();
		this.appid = app.attr("appid");		
	},
	search:function(){
		var searchtext = $.trim($(".text_sarch").val());
		AppRoleManager.getRole(1,searchtext);
	},
	removeSelect:function(ev){
		var html = new Array();
		var _id= $(ev).attr("selectedid");
		var _text= $.trim($(ev).text());
		var _src = $(ev).find("img").attr("src");
		html.push("<span onclick='AppRoleManager.selectedAccount(this)' keyid='"+_id+"' class='app_role_user_header app_role_viesaccount'>");
		html.push("  <img src='"+_src+"'><span>"+_text+"</span></span>");
		if ( $("#accountlist").children().length==0)
		  $("#accountlist").html(html.join(''));
		else
			$("#accountlist>span:visible:first").before(html.join(''));
		$(ev).remove();
		if ($(".app_role_selectListArea").children().length<11)
		  $(".app_role_pageLeft,.app_role_pageRight").hide();
		
	}
}