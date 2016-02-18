/*组件编辑操作*/
var ComponentEdit={
	app:null,
	componentlist:[]
};
//事件定义
ComponentEdit.init=function(app){
	oneApp=app;
	this.componentlist=[];
	//获取当前页面的配置
	var pageXml = APP_PAGE_MAP.get(app.cFuncid);
	if(pageXml!=null)
	{
		pageXml = pageXml.config;
	}
	this.getList(pageXml);
};
//获取当前页面的组件列表
ComponentEdit.getList=function(pageconfigxml){
	if(pageconfigxml==null){
		this.componentlist=null;
	 	return this.componentlist;
	}
	//获取组件
	var eles = $(".interface[functionid='"+oneApp.cFuncid+"']").find(".component[functionid='"+oneApp.cFuncid+"']");
	for(var i=0; i<eles.length; i++)
	{
		if($(eles[i]).attr("aindex")=="0") continue;
		this.componentlist.push($(eles[i]));
	}
	return this.componentlist;
};
ComponentEdit.getComponentList=function(){
	 this.componentlist=[];
	 //获取组件
	 var eles=[];
	 var theComponent=oneApp.getSourceComponent(oneApp.cFuncid);
	 var $inter=$(".interface[functionid='"+oneApp.cFuncid+"']");
	 while(theComponent!=null && (theComponent.code=="component_nav" || theComponent.code=="component_tabs")){
	 		$inter=$(".interface[functionid='"+theComponent.functionid+"']");
	 	  theComponent=oneApp.getSourceComponent(theComponent.functionid);
	 }
	eles = $inter.find(".component[functionid='"+oneApp.cFuncid+"']");
	for(var i=0; i<eles.length; i++)
	{
		if($(eles[i]).attr("aindex")=="0") continue;
		this.componentlist.push($(eles[i]));
	}
	return this.componentlist;
}
//添加新组件到当前页面
ComponentEdit.add=function(componentcode,type)
{
	var componentobject = ComponentAttr.getComponentObject(componentcode);
	if(componentobject==null) return false;
	//判断是否允许添加多个
	if(false && componentobject.maxcount!="n")
	{
		var hint = "";
		var pageXml = APP_PAGE_MAP.get(oneApp.cFuncid).config;
		if(pageXml!=null && pageXml.length>0  && pageXml.find(componentobject.tagName).length>=componentobject.maxcount*1)
		{
			//不允许再添加
			hint =  "<span style='color:red;'> " + componentobject.name +"</span>最多允许添加<span style='color: red; font-weight: bold; font-family: arial; padding: 0px 5px;'>" + componentobject.maxcount + "</span>个。";
			showSuccessBox(hint);
			return false;
		}
		else{
		 var count = $("#componentitem_list_select>div[component='"+componentcode+"']").length;
		 if ( count >= componentobject.maxcount*1) {
		 	 //不允许再添加
			hint =  "<span style='color:red;'> " + componentobject.name +"</span>最多允许添加<span style='color: red; font-weight: bold; font-family: arial; padding: 0px 5px;'>" + componentobject.maxcount + "</span>个。";
			showSuccessBox(hint);
			 return false;
		 }
	  }
 }
 //获取最大的index值
 var theComponent=oneApp.getSourceComponent(oneApp.cFuncid);
 var inter=$(".interface[functionid='"+oneApp.cFuncid+"']");
 while(theComponent!=null && (theComponent.code=="component_nav" || theComponent.code=="component_tabs")){
 		inter=$(".interface[functionid='"+theComponent.functionid+"']");
 	  theComponent=oneApp.getSourceComponent(theComponent.functionid);
 }
 var hindex="1",cindex="1",findex="1";
 if(inter.find(".interface_head").children(".component[functionid='" + oneApp.cFuncid+"']").length>0){
	    hindex=inter.find(".interface_head").children(".component[functionid='" + oneApp.cFuncid+"']:last").attr("cindex");
 }
 if(inter.find(".interface_center").children(".component[functionid='" + oneApp.cFuncid+"']").length>0){
 			cindex=inter.find(".interface_center").children(".component[functionid='" + oneApp.cFuncid+"']:last").attr("cindex");
 }
 if(inter.find(".interface_foot").children(".component[functionid='" + oneApp.cFuncid+"']").length>0){
 			findex=inter.find(".interface_foot").children(".component[functionid='" + oneApp.cFuncid+"']:last").attr("cindex");
 }
	var dom_index=oneApp.addInterComponent(oneApp.cFuncid,Math.max(parseInt(hindex),parseInt(cindex),parseInt(findex)).toString(),"next",componentcode);
	if(componentobject.type=="native") dom_index=1;
	SaveApplicationConfig(oneApp);
	return dom_index;
};
ComponentEdit.getAttributeValue=function(componentxmldom,attrname)
{
	if(componentxmldom==null) return "";
	return componentxmldom.attr(attrname);
};

//功能开发。如果没有关联页面，则创建新页面
ComponentEdit.functionDev = function(functionid,functionname){
	if ( AppConfigManager.treeObj.getNodeByParam("id",AppConfigManager.getInterNodeId(functionid)) == null ){
		if ( ComponentAttr.parameter.code=="component_tabs")
		  oneApp.addInterfaceWidthTempXml(functionid,functionname,"<template></template>");
		else
			oneApp.addInterfaceWidthTempXml(functionid,functionname,"<template><title color='#FFFFFF'><text>"+  functionname +"</text></title></template>");
		SaveApplicationConfig(oneApp);
		var data = new Object;
		data.pId = ComponentAttr.parameter.functionid;
		data.id = functionid;
		data.functionid = functionid;
		data.type = "interface";
		data.name = functionname;	
		data.icon = "/bundles/fafatimewebase/images/inter.png";
		AppConfigManager.addTreeNode(data,ComponentAttr.parameter.index);		
		AppConfigManager.refreshInterNode();
	}
	else{
		AppConfigManager.refreshInterNode();
		AppConfigManager.interNodeSelected(functionid);
  }
};

var ComponentEditView={
	list:null
};

//刷新页面
ComponentEditView.RefreshPage = function(){
	var $xmldom = $(oneApp.xmlBuilder.xmlDom);
	APP_PAGE_LIST=[];
	APP_PAGE_MAP=new HashMap();
	if ($xmldom != null && $xmldom.length>0) {
		var $functions = $xmldom.find("function");
		for (var i = 0; i < $functions.length; i++) {			
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
		ApplicationMgr.PageListView($(".user_appconfig_area"));
  }
}

//判断页面上是否允许添加组件
//1、已有导航或原生组件，有则不允许添加其他组件
ComponentEditView.isAdd=function()
{
	//var eles = $(".interface[functionid='"+oneApp.cFuncid+"']").find(".cp_nav,.inter_native");
//	if(eles.length>0) return false;
//	return true;
	var nativeContainer = null;
	if ( oneApp.cFuncid.toLowerCase()==oneApp.appid.toLowerCase()) //表示根节表
	   nativeContainer = $(".interface_center:visible,.interface_foot:visible").find(".cp_nav,.inter_native");
	else //表示子节点
		 nativeContainer = $(".interface_center:visible").find(".cp_nav,.inter_native");
	return (nativeContainer.length>0 ? false:true);
};

ComponentEditView.show=function($parent,listItem){
	this.list = listItem;
	if(listItem==null) return;
	for(var i=0;i<listItem.length; i++)
	{
		//获取cindex
		var cindex = listItem[i].attr("aindex");
		var cfuncid=listItem[i].attr("functionid");
		var getInterComponent = oneApp.getInterComponent(cfuncid,cindex);
		if(getInterComponent==null || cindex.indexOf("-")>-1) continue;
		$parent.append(ComponentAttr.getHtmlEle(getInterComponent.code,{dom_index:cindex,type:getInterComponent.attrs.type}));
		if(ComponentAttr.currentCode!=null && ComponentAttr.currentCode==getInterComponent.code+cindex)
		{
			//自动进入编辑模式
			ComponentEditView.attribute($parent,$parent.find(".application_component_icon[component='"+getInterComponent.code+"'][dom_index='"+cindex+"']"));
		}
	}
	$parent.find(".application_component_icon").off("click").on("click",function(){
		var $this = $(this);
		$(".component").removeClass("component_hover");
		$(".component[aindex='"+$this.attr("dom_index")+"']").addClass("component_hover");
		ComponentEditView.attribute($parent,$this);
	});
}

ComponentEditView.showRight=function($parent,listItem){
	this.list = listItem;
	if(listItem==null) return;
	for(var i=0;i<listItem.length; i++)
	{
		//获取cindex
		var cindex = listItem[i].attr("aindex");
		var cfuncid=listItem[i].attr("functionid");
		var getInterComponent = oneApp.getInterComponent(cfuncid,cindex);
		if(getInterComponent==null || cindex.indexOf("-")>-1) continue;
		$parent.append(ComponentAttr.getHtmlEle(getInterComponent.code,{dom_index:cindex,type:getInterComponent.attrs.type}));
	}
	$parent.append("<div  data-toggle='modal' data-backdrop='static' href='#selectComponentDialog' onclick='AppConfigManager.loadComponentList()' class='add_component'><div class='i_tag4_active'></div><span>添加组件</span></div>");
	//事件绑定
			$parent.find(".application_component_icon").off("mouseover,mouseout").on("mouseover",function(event){
				if(checkHover(event,this)){
					var $this=$(this);
					$(".component").removeClass("component_hover");
					$(".component[functionid='"+oneApp.cFuncid+"'][aindex='"+$this.attr("dom_index")+"']").addClass("component_hover");
					$this.css("cursor","pointer");
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
			
			$("#runtime_component_toolbar span").off("click").on("click",function(){
				var $this= $("#runtime_component_toolbar"),code=$this.attr("component");
				var componentObject=ComponentAttr.getComponentObject(code);
				if($(this).attr("class").indexOf("remove")>-1){
					var hinttext = "确定要从界面上移除该组件吗？";
					if(code=="component_nav"|| componentObject.type=="native")
						hinttext = "移除该组件会导致页面内容<font color=red>全部重置</font><br>确定吗？";
					wefafaWin2.weconfirm(null,"提示",hinttext,function(a){
					  $("#runtime_component_toolbar").appendTo($(document.body)).hide();
					  if(componentObject.deleteFunctions!=null) componentObject.deleteFunctions(oneApp.cFuncid);//删除关联的临时页面
					  oneApp.removeInterComponent(oneApp.cFuncid,a.index);
						oneApp.getInterObj(oneApp.cFuncid).reload();
						SaveApplicationConfig(oneApp);
						//其他操作
						AppConfigManager.delComponent(a.index);
					},{"index":$this.attr("dom_index")});
				}
				else{
					AppConfigManager.componentNodeSelected(oneApp.cFuncid,$this.attr("dom_index"));
				}
			});
}


ComponentEditView.attribute=function($parent,$this){
	var itemFlag=$this.attr("dom_index"), interComponent = oneApp.getInterComponent(oneApp.cFuncid,itemFlag);
	if(interComponent==null) return;
	var $component_edit = $("#component_edit"),$thisposition=$this.offset();
	var left = ($thisposition.left+($this.width()/2)-5),t=$thisposition.top+$this.height()+5;
	if (ComponentAttr.currentCode != null && ComponentAttr.currentCode != interComponent.code + itemFlag){
		  wefafaWin2.weconfirm($this,"警告","当前编辑的组件还未保存，是否不保存?", function(e){
			$component_edit.css({"position":"absolute","left":"550px","top":t+"px"}).show();			
			$parent.children(".topmenu_app_triangle").css({"position":"absolute","left": left+ "px","top":(t-20)+"px"}).show();
			$parent.children(".topmenu_app_triangle:last").css({"left": (left)+ "px","top":(t-20+1)+"px"});
			interComponent.cindex = itemFlag;
			ComponentAttr.edit($("#component_edit .component_edit_content"),interComponent);
		},$this);
		return;
	}
	$component_edit.css({"position":"absolute","left":"550px","top":t+"px"}).show();
	$parent.children(".topmenu_app_triangle").css({"position":"absolute","left": left+ "px","top":(t-20)+"px"}).show();
	$parent.children(".topmenu_app_triangle:last").css({"left": (left)+ "px","top":(t-20+1)+"px"});
	
	interComponent.cindex = itemFlag;
	ComponentAttr.edit($("#component_edit .component_edit_content"),interComponent);
}
ComponentEditView.attributeRight=function(functionid,index){
	//点击组件进入组件编辑页面 
	var interComponent = oneApp.getInterComponent(functionid,index);
	interComponent.cindex = index;
	if ( interComponent==null ) return;
	$("#component_listarea").hide();
	$("#component_editarea").show();
	$(".component_edit_buttom").hide();
	$("#btn_basicedit").trigger('click');
	//加载公共属性及样式
	$("#bsiacattr_editdiv").html("");
	LoadComponent($("#bsiacattr_editdiv"),componentattrediturl.replace("componentname","publicstyle"),null,function(){
		$(".component_edit_buttom").show();
	});	
	//加载组件属性
	ComponentAttr.edit($("#component_editdiv"),interComponent);
	
}
var ComponentAttr = {
	parameter:null,
	currentCode:null,
	getComponentObject:function(componentcode)
	{
		if (componentcode == "component_list"|| componentcode=="list") {
			return component_list;
		} else if (componentcode == "component_applist" || componentcode=="applist") {
			return component_applist;
		}	else if (componentcode == "component_title" || componentcode=="title") {
			return component_title;
		} else if (componentcode== "component_switch" || componentcode=="switch") {
			return component_switch;
		} else if (componentcode == "component_menu" || componentcode=="menu") {
			return component_menu;
		} else if (componentcode == "component_nav" || componentcode=="nav") {
			return component_nav;
		} else if (componentcode == "component_tabs" || componentcode=="tabs") {
			return component_tabs;
		} else if (componentcode == "component_search" || componentcode=="search") {
			return component_search;
		}else if (componentcode == "component_groupnews" || componentcode=="groupnews") {
			return component_groupnews;
		}else if (componentcode == "component_circlenews" || componentcode=="circlenews") {
			return component_circlenews;
		}else if (componentcode == "component_publicaccount" || componentcode=="publicaccount") {
			return component_publicaccount;
		}else if (componentcode == "component_repository" || componentcode=="repository") {
			return component_repository;
		} else if (componentcode == "component_contacts" || componentcode=="contacts") {
			return component_contacts;
		}	else if (componentcode == "component_message" || componentcode=="message") {
			return component_message;
		}	else if (componentcode == "component_enoweibo" || componentcode=="enoweibo") {
			return component_enoweibo;
		}	else if (componentcode == "component_setting" || componentcode=="setting") {
			return component_setting;
		} else if (componentcode == "component_userprofile" || componentcode=="summary"){
			return component_userprofile;
		} else if ( componentcode == "component_userbasicinfo" || componentcode=="userbasicinfo"){
			return component_userbasicinfo;
		} else if ( componentcode == "component_matchlist" || componentcode=="matchlist"){
			return component_matchlist;
		}	else if ( componentcode == "component_functionbar" || componentcode=="functionbar"){
			return component_functionbar;
		} else if ( componentcode == "component_matchdetail" || componentcode=="matchdetail"){
			return component_matchdetail;
		} else if ( componentcode == "component_goodsdetail" || componentcode=="goodsdetail"){
			return component_goodsdetail;
		}		
		else
			return null;
	},
	currentEle: {
		"component": "",
		"htmlelement": ""
	}, //当前编辑的元素标识,由组件标识和元素标识组成。当编辑一个资源类型的控件(如图标)时，应该记录下该属性
	edit: function(outerEle, para) {		
		//para.outer = typeof(outerEle) == "string" ? $("#" + outerEle) : outerEle;
		para.outer = typeof(outerEle) == "string" ? $(outerEle) : outerEle;
		//para.outer.show(); //打开组件属性编辑面板
		this.parameter = para;
		ComponentAttr.edit_confirm();
	},
	edit_confirm:function(){
		ComponentAttr.currentCode = this.parameter.code + this.parameter.cindex;
		hideConfirmBox();
		var para = this.parameter,componentObject=this.getComponentObject(para.code);
		componentObject.edit(para);		
	},
	saveappresource: function() {
		var para = this.currentEle.htmlelement; //当前编辑的html元素标识或者自定义的标记符
		var componentObject=this.getComponentObject(this.currentEle.component);
		if(componentObject!=null)
			componentObject.setappresource(para);
		else{			
      if(PortaladvObj){
      	if ( PortaladvObj.icon){  //只作预览不做保存
      		var res_fileid = getappresource();
          PortaladvObj.selectappicon(res_fileid);
      	}
      	else{
				  PortaladvObj.saveappresource();
				}
			}
		}
	},
	save:function()
	{
		var componentObject=this.getComponentObject(this.parameter.code);
		componentObject.save();
		AppConfigManager.hint = true;
	},
	cancel:function(){
    AppConfigManager.refreshInterNode();
    AppConfigManager.interNodeSelected(this.parameter.functionid);
	},
	deleteComponent:function(){
		 var code = this.parameter.code;
		 var componentObject=ComponentAttr.getComponentObject(code);
		 var hinttext = "确定要从界面上移除该组件吗？";
		 if(code=="component_nav"|| componentObject.type=="native")
			 hinttext = "移除该组件会导致页面内容<font color=red>全部重置</font><br>确定吗？";
		 wefafaWin2.weconfirm(null,"提示",hinttext,function(a){
			  if(componentObject.deleteFunctions!=null) componentObject.deleteFunctions(oneApp.cFuncid);//删除关联的临时页面
			  oneApp.removeInterComponent(oneApp.cFuncid,a.index);
				oneApp.getInterObj(oneApp.cFuncid).reload();
				SaveApplicationConfig(oneApp);
				//其他操作
				AppConfigManager.delComponent(a.index);
				ComponentAttr.cancel();
			},{"index":this.parameter.index});
	},
	getHtmlEle: function(componentcode,para) { 
		var componentObject=this.getComponentObject(componentcode);		
		return componentObject.getHtmlEle(para);
	},	
	initEditorTitle: function(outer, title, toolsbar) {
		var html = [];
		html.push("<div class='component_editor_title'>");
		html.push("  <img src='/bundles/fafatimewebase/images/ico_edit.png' class='component_editor_title_icon'><span>" + title + "</span>");
		if (typeof(toolsbar) == "string")
			html.push(toolsbar);
		html.push("</div>");
		html.push("<div id='component_editor_hint' style='display: none; float: left; width: 100%; text-align: center; height: 25px; line-height: 25px; color: rgb(204, 51, 0);'></div>");
		outer.html(html.join(""));
		if (typeof(toolsbar) != "string") {
			outer.find('#component_list div:first').append(toolsbar);
		}
	},
	showMessage: function(msg) {
		$("#component_editor_hint").html(msg).show("200", function() {
			setTimeout(function() {
				$("#component_editor_hint").html("").hide("400");
			}, 5000);
		});
	},
	xmlDocumentToString:function(xmlData) {
		var xmlString;
    if (typeof window.XMLSerializer != "undefined") 
      xmlString = (new XMLSerializer()).serializeToString(xmlData);
    else
      xmlString = xmlData.xml;
    return xmlString.replace(/xmlns\=\"\"/g, "");
	},
	checkExists:function(parentControl,hint){  //判断两项是否相同
		var controls = $(parentControl);
		controls.css("border","1px solid #888888");
		for(var i=0;i<controls.length;i++){
			controls.eq(i).attr("tag",controls.eq(i).val());
		}
		var item = "",count=0;
		for(var j=0;j<controls.length;j++){
			item = $.trim(controls.eq(j).val());
			if ( item !=""){
				count = $(parentControl+"[tag='" + item + "']").length;
			  if (count>1){
			  	$(parentControl+"[tag='" + item + "']").eq(count-1).css("border","1px solid #cc3300");
			    ComponentAttr.showMessage(hint);
			  	return true;
			  }
			}
		}
		return false;
	},
	//获得选择页的html
	getSelectedPageHtml:function(){
		var appdata = oneApp.getAppData();
		var functions = appdata.functions;
		var html = new Array();
		if ( appdata != null && functions.length>0){
			for(var i=0;i<functions.length;i++){
				if ( functions[i].functionid != appdata.rootfunctionid){
					html.push("<label class='component_selectedpage_label'>" +
					         "  <input style='float:left;margin-right:5px;' type='radio' name='radio_selected' value='" + functions[i].functionid + "'>"+
					         "  <span>"+functions[i].functionname+"</span>"+
					         "</label>");
				}
			}
	  }
	  return html;
	}	
};


var component_list = {
	_para: null,
	itemcount:10, //允许添加的最大列表项数
	maxcount:"5", //同一页面允许添加的最大数量，n表示多个
	tagName:"list",
	desc:"", //组件描述
	name:"新闻列表组件",
	type:"user", //用户自定义组件
	currentRow:null,
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_list' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_list_icon'></span><span>新闻列表</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_list' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_list_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>新闻列表</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	selectIcon: function(e) {
		var ctlID = "";
		if ($(e).get(0).tagName.toLowerCase() == "img") //如果为img控件
			ctlID = $(e).attr("id");
		else if ($(e).get(0).tagName.toLowerCase() == "span") //如果为span
			ctlID = $(e).prev().attr("id");
		  ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement": ctlID
		};
	},	
	remove: function(e) {
	  if ( $(".component_newslist_body").children().length==1) {
      ComponentAttr.showMessage("至少应保留一个列表项！");		
			return;
		}
		this.currentRow = $(e).parents(".component_newslist_item");
		showConfirmBox("确定删除该本项吗？", "component_list.deleteItem()");
	},
	deleteItem: function() {
	  var itemindex = this.currentRow.attr("functionid");
		this.currentRow.remove();
		//重新排序
		var control = $(".component_newslist_body").children();
		for(var i=0;i < control.length;i++){
			control.eq(i).find(".component_newslist_no").text(i+1);
		}
		if ( itemindex!="") {
		  component_list.deleteFunctions(null,itemindex);
		  AppConfigManager.hint = false;
		  component_list.save();    
		 // AppConfigManager.componentNodeSelected(this._para.functionid,this._para.index);
		}
		else{
			this.currentRow = null;
		}		
		hideConfirmBox();
	},
	setappresource: function(imgObj) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if (  resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			 	if (!$("#" + imgObj).is(":visible")) {
				$("#" + imgObj).show();
				$("#" + imgObj).next().remove();
			}
			$("#" + imgObj).attr("src", url);
		}
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","list"),null,function(){
			 $(".component_edit_buttom").show();
		   BindListItem(params.attrs);		   
		});
	},
  customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_newslist_item").find("input");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入列表名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return;
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	  AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_newslist_item").attr("functionid",funid);
 	   	 component_list.customize(ev);
 	  }
	},	
	customize:function(ev){  //页面详细定制
		var inputControl = $(ev).parents(".component_newslist_item").find("input");
 	   var inputtext = $.trim(inputControl.val());
 	   if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输入列表名称！" );
 	  	 inputControl.css("border","1px solid #CC3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);	 	  	
 	   }
 	   else{
 	   	  var functionid = $(ev).parents(".component_newslist_item").attr("functionid");
 	   	  AppConfigManager.hint = (functionid==null || functionid=="") ? true:false;
 	   	  var index = $(ev).parents(".component_newslist_item").index();
 	   	  while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
				}
				$(ev).parents(".component_newslist_item").attr("functionid",functionid);
				component_list.save();
 	      AppConfigManager.interNodeSelected(functionid);
 	   }
	},
	dynamic_customize:function(){  //页面详细定制
	 	 if (component_list.save()) {
		 	 var functionid = this._para.attrs.functionid.text;
		 	 AppConfigManager.interNodeSelected(functionid);
	   }
  },
	save:function(){
		var attrs = new Object;
		var newstype = $("#newslistType input:checked").val();
		if ( newstype=="1"){
		  if (ComponentAttr.checkExists(".component_newslist_body .component_newslist_item>input","不能有相同的列表项！"))
		     return false;
		}
		//列表方式
    attrs.style = $("#listMode input:checked").val();
		attrs.type = newstype;
		if (newstype == 1) { //静态列表
			var controls = $(".component_newslist_body .component_newslist_item");
			var listitems = new Array(),itemicon = '',itemname = '',inputcontrol = null;
			for (var i = 0; i < controls.length; i++) {
				inputcontrol = controls.eq(i).find("input");
				itemname = $.trim(inputcontrol.val());
				if ( itemname=="" && this.currentRow == null){
					inputcontrol.focus();
					inputcontrol.css("border","1px solid #CC3300");
					ComponentAttr.showMessage("请输入列表名称");
					setTimeout(function(){inputcontrol.css("border","1px solid #AAAAAA");},2000);
					return;
				}
				if ( itemname=="" ) continue;
				itemicon = controls.eq(i).find("img").attr("src");
				if ( itemicon==null || itemicon=="")
				  itemicon = "/bundles/fafatimewebase/images/defaultimg.png";				  
				var functionid = new Object;
				functionid.text = controls.eq(i).attr("functionid");
				var j=i;
				while ( functionid.text==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid.text = theid;
				   	  break;
				   }
				   else{
				   	 j++;
				   }
				}
				if (this._para.attrs.type=="2"){
					 if ( typeof(this._para.attrs.functionid)=="string")
	 					 AppConfigManager.removeTreeNode(this._para.attrs.functionid,this._para.index);
	 				 else
	 				 	 AppConfigManager.removeTreeNode(this._para.attrs.functionid.text,this._para.index);
				}				
				functionid.target = controls.eq(i).attr("target");
				oneApp.updateInterInfo(functionid.text,"functionname",itemname);				
			  listitems.push({"functionid":functionid,"itemicon":itemicon,"itemname":itemname})
			}
			attrs.listitems = listitems;
		}
		else { //动态列表
			var url = $.trim($("#component_list_dynamic_dburl").val());
			if (url == "" || url == "http://") {
				ComponentAttr.showMessage("请输入获取数据URL地址！");
				return false;
			}
			if (url.indexOf("http") == -1) {
				ComponentAttr.showMessage("获取数据URL地址错误！");
				return false;
			}
			if (this._para.attrs.type=="1"){
				 for(var i=0;i<this._para.attrs.listitems.length;i++){
				 	 AppConfigManager.removeTreeNode(this._para.attrs.listitems[i].functionid.text,this._para.index);
				 }
			}			
			attrs.listurl = url;
			attrs.listurlpara = $.trim($("#component_list_dynamic_paraname").val());
		  var k = 0;
		  var funcid = "";
			while ( funcid ==""){
			   var theid = this._para.functionid + "-" + this._para.cindex + "-" + k;
			   if(oneApp.getInterData(theid) == null){
			   	  funcid = theid;
			   	  break;
			   }
			   else{
			   	 k++;
			   }
			}
			attrs.functionid = { "text": funcid,"target":"blank" }
		}
		var newpara = new Object;
		newpara = this._para;
		newpara.attrs = attrs;		
		oneApp.setInterComponent(this._para.functionid, newpara.cindex, newpara);
		SaveApplicationConfig(oneApp);
		this._para = newpara;
		if ( this.currentRow == null ){
			if ( newstype==1){
		    for(var i=0;i<attrs.listitems.length;i++){
   		    ComponentEdit.functionDev(listitems[i].functionid.text,attrs.listitems[i].itemname);
		    }
		  }
		  else{
		  	ComponentEdit.functionDev(attrs.functionid.text,"详细页面");
		  }
		  ComponentAttr.cancel();
		}
		else
			this.currentRow = null;
		return true;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex)
	{
		if(vItemIndex!=null)
		{
			$(".user_appconfig_area>.user_appconfig_list[uuid='"+vItemIndex+"']").remove();
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

var component_applist = {
	_para: null,	
	maxcount:"1", //同一页面允许添加的最大数量，n表示多个
	tagName:"applist",
	desc:"所属企业的应用列表。", //组件描述
	name:"应用列表组件",
	type:"user", //用户自定义组件
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_applist'  dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_list_icon'></span><span>应用列表</span></div>";
	},
	getHtmlEle2:function(para){
	   return "<div component='component_applist'  dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_list_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>应用列表</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
	 	LoadComponent(params.outer,componentattrediturl.replace("componentname","listapp"),null,function(){
	 		 $(".component_edit_buttom").show();
	 		 var type = params.attrs.style;
	 		 $("#listMode input").attr("checked","");
	 		 $("#listMode input[for='"+type+"']").attr("checked","checked");
	  });	
	},
	save: function() {
		var style = $("#listMode input:checked").attr("for");
		this._para.attrs.style = style;
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
	}
};
//编辑标题组件属性
var component_title = {
	_para: null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"标题组件", //组件描述
	tagName:"title",
	type:"user", //用户自定义组件
	name:"标题组件",
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_title' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_title_icon'></span><span>标题</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_title' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_title_icon'></span><span class='application_component_name' style='text-align:left'>标题</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit: function(params) {
		this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","title"),null,function(){
			$(".component_edit_buttom").show();
			if ( params.attrs.pic == ""){  //文字
				switchtitletype('text');
				var color = params.attrs.color;
				color = (color==null || color=="") ? "#000000" : color;
				$("#component_title_attribute_list .componnet_title_color").css("color",color);
				$("#component_title_attribute_list .componnet_title_color").text(color);
				$("#component_title_attribute_list input[value='text']").attr("checked","true");
				$("#title_text").val(params.attrs.text);
			}
			else {
				switchtitletype('pic');
				$("#component_title_attribute_list input[value='pic']").attr("checked","true");
				$("#title_ico").attr("src",params.attrs.pic);				
			}
		});		
	},
	//测试后删除该方法 edit_delete
	edit_delete: function(params) {
		this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","matchdetail"),null,function(){
			$(".component_edit_buttom").show();
			
		});		
	},	
	save: function() {
		var _text = "";
		var color = "";
		var title_icon="";
		var type = $("#component_title_attribute_list input:checked").val();
		if ( type=="text"){  //文本类型
			_text = $.trim($("#title_text").val());
			if ( _text=="")
			{
				ComponentAttr.showMessage("标题文字不能为空！");
				return;
			}
			color = $("#component_title_attribute_list .componnet_title_color").css("color");
			if (color.indexOf("rgb")>-1)
			   color = componentColor.formatRgb(color);
	  }
	  else{
	  	title_icon = $("#component_title_attribute_list #title_ico").attr("src");
	  	if ( title_icon == "") {
	  		ComponentAttr.showMessage("请上传标题图片");
				return;
	  	}	  	 
	  }
		if (_text != "" || title_icon!="") {
			var newpara = new Object;
			newpara.attrs = { "color":color,"text":_text,"pic":title_icon};
			newpara.cindex = this._para.cindex;
			newpara.functionid = this._para.functionid;
			newpara.code = this._para.code;
			newpara.name = this._para.name;			
			oneApp.setInterComponent(this._para.functionid, this._para.cindex, newpara);
			this._para = newpara;
			SaveApplicationConfig(oneApp);
			ComponentAttr.cancel();
		}
	}
};

/*
  快捷菜单项管理
*/
var component_menu = {
	_para: null,
	itemcount:10,
	maxcount:1, //同一页面允许添加的最大数量
	desc:"快捷操作菜单，可以是系统原生功能或者用户自定义功能，通常位于顶部右上角。", //组件描述
	name:"快捷菜单组件",
	tagName:"menu",
	type:"user", //用户自定义组件
	currentRow:null,
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){
		this.save();		
		$(".user_appconfig_list[uuid='"+vFunctinid+"']").trigger('click');
	},	
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_menu' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_menu_icon'></span><span>快捷菜单</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_menu' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_menu_icon'></span><span class='application_component_name' style='text-align:left'>快捷菜单</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},	
	selectIcon:function(e){
		var curid = e.id;
		ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement": curid
		};
	},
	remove:function(e){
		if ( $("#component_menu_area .component_menuitem").length==1) {
      ComponentAttr.showMessage("至少应保留一个菜单项！");			
			return;
		}
		this.currentRow = $(e).parents(".component_menuitem");
		showConfirmBox('确定删除该本项吗？', 'component_menu.deleteItem()');
	},
	deleteItem: function() {
		var itemindex = this.currentRow.attr("functionid");
		this.currentRow.remove();
		//重新排序
		var control = $("#component_menu_area .component_menuitem");
		for(var i=0;i<control.length;i++){
		  control.eq(i).find(".component_menuitem_no").text(i+1);
		}
		if ( itemindex!="") {
		  component_menu.deleteFunctions(null,itemindex);
		  component_menu.save();
		  //AppConfigManager.componentNodeSelected(this._para.functionid,this._para.index);
		}
		else { 
			this.currentRow = null 
		}	
		hideConfirmBox();
	},
	setappresource: function(imgObj) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if (  resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
     
			if (!$("#" + imgObj).is(":visible")) {
				$("#" + imgObj).show();
				$("#" + imgObj).next().remove();
			}
			$("#" + imgObj).attr("src", url);
   }
	},
	edit: function(params) {
	  this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","menu"),null,function(){	
			 $(".component_edit_buttom").show();
			 BindMenuItem(params.attrs);
		});
	},
  customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_menuitem").find("input");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入菜单名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return;
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	  AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_menuitem").attr("functionid",funid);
 	   	 component_menu.customize(ev);
 	  }
	},	
  customize:function(ev){  //页面详细定制
		var inputControl = $(ev).parents(".component_menuitem").find("input");
 	   var inputtext = $.trim(inputControl.val());
 	   if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输入菜单名称！" );
 	  	 inputControl.css("border","1px solid #cc3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },2000);	 	  	
 	   }
 	   else{
 	   	 var functionid = $(ev).parents(".component_menuitem").attr("functionid");
 	   	 AppConfigManager.hint = (functionid==null || functionid=="") ? true : false;
 	   	  var index = $(ev).parents(".component_menuitem").index();
 	   	  while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
				}
				$(ev).parents(".component_menuitem").attr("functionid",functionid);
				component_menu.save();
 	      AppConfigManager.interNodeSelected(functionid);
 	   }
	},		
	save: function() {
		if (ComponentAttr.checkExists("#component_menu_area .component_menuitem>input","不能有相同的菜单项！")) return;
		var menuitems = new Array();
		var controls = $("#component_menu_area .component_menuitem");
		var menuname = "",img = "";
		for(var i=0;i<controls.length;i++){
			var inputControl = controls.eq(i).find("input");
			menuname = $.trim(inputControl.val());
			if ( menuname == "" && this.currentRow == null ){
			  ComponentAttr.showMessage("请输入菜单名称！" );
 	  	  inputControl.css("border","1px solid #cc3300");
 	  	  inputControl.focus();
 	  	  setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },2000);	 	
				return;
			}
			if ( menuname =="" ) return;
			img = controls.eq(i).find("img").attr("src");
			if ( img==null || img == "")
			  img = "/bundles/fafatimewebase/images/defaultimg.png";
			var functionid = new Object;
			functionid.text = controls.eq(i).attr("functionid");
			var j = i;
			while ( functionid.text==""){
			  var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
				if(oneApp.getInterData(theid) == null){
				  functionid.text = theid;
				  break;
				}
				else{
				  j++;
				}
		  }
			functionid.target = controls.eq(i).attr("target");
			oneApp.updateInterInfo(functionid.text,"functionname",menuname);
			menuitems.push( { "functionid": functionid ,"itemicon":img,"itemname":menuname });
		}
		var position = $("#menu_left").attr("checked")==null ? "R":"L";
		//组件最新参数信息	
		var Attrs = new Object;
		Attrs.menuitems = menuitems;
		Attrs.position = position; 
		this._para.attrs = Attrs;
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);		
		if ( this.currentRow == null ){
		  for(var i=0;i<menuitems.length;i++){
   		  ComponentEdit.functionDev(menuitems[i].functionid.text ,menuitems[i].itemname);
		  }
		  ComponentAttr.cancel();
		}
		else
			this.currentRow = null;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			$(".user_appconfig_area>.user_appconfig_list[uuid='"+vItemIndex+"']").remove();
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
		//删除所有相关联的页面
		for(var i=0; i<APP_PAGE_LIST.length; i++) {
			var vUuid = APP_PAGE_LIST[i].uuid;
			if(APP_PAGE_LIST[i].temp && APP_PAGE_LIST[i].pagetype=="tabitem" && vUuid.indexOf(vMasterFunctionID)==0) {
				oneApp.removeInterByFunctionid(vUuid);
				$(".user_appconfig_area>.user_appconfig_list[uuid='"+vUuid+"']").remove();
				APP_PAGE_LIST[i]=null;
				APP_PAGE_MAP.get(vUuid)==null;
			}
		}
	}	
};

//图片轮换组件
var component_switch = {
	_para: null,
	itemcount:5,  //允许添加的最多图片张数
	maxcount:"5", //同一页面允许添加的最大数量
	desc:"", //组件描述
	tagName:"switch",
	name:"图片轮换组件",
	type:"user", //用户自定义组件
	loadStyleList: function() {},
	getHtmlEle:function(para){
		return "<div component='component_switch' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_switch_icon'></span><span>轮换图</span></div>";
	},
	getHtmlEle2:function(para){
		return "<div component='component_switch' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_switch_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 6px;'>轮换图</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -4px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";	
	},
	select_type:function(e){
		var type = $(e).find("input").val();
		if ( type=="1"){
		  $(".xml_tools_add").show();		  
		  $("#component_switch_dynamic").hide();
		  $("#component_switch_static").show();
		}
		else{
			$(".xml_tools_add").hide();
			$("#component_switch_static").hide();
			$("#component_switch_dynamic").show();
		}
	},
	remove:function(){
		if ( $("#pic_number>span").length==2){
 	  	ComponentAttr.showMessage("至少应保留一个导航项！");
 	  	return;
 	  }
 	  else{
 	    showConfirmBox("确认删除该项码？", "component_switch.deleteItem()");
 	  }
	},
	deleteItem:function(){
		$(".component_switch_number_active").remove();
 	  var controls = $("#pic_number>span");
 	  for(var i=0;i< controls.length;i++){
 	 	  if ( i==0){
 	 	 	  controls.eq(i).attr("class","component_switch_number_active");
 	 	 	  controls.eq(i).attr("active",1);
 	 	 	  $("#switch_picture").attr("src",controls.eq(i).attr("url"));
 	 	 	  $(".component_switch_title").text(controls.eq(i).attr("text"));
 	 	  }
 	 	  if (i+1 != controls.length){
 	 	 	  controls.eq(i).text(i+1);
 	 	  }
 	  }
 	  $("#component_tools").show();
 	  $("#component_tools").css("margin-top","0px");
 	  if ( controls.length - 1 < 4 ){
 	  	$(".component_tools_delete_active").hide();
 	  }
 	  
 	  if ( controls.length-1 < component_switch.itemcount)
 	     $("#pic_number>span:last").show();
 	  hideConfirmBox();
	},	
	edit: function(params) {
		this._para = params;
		this.loadStyleList();		
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","switch"),null,function(){
			 $(".component_edit_buttom").show();
			 params.outer.append('<div class="clearfix"></div>');
			 BindSwitch(params.attrs);
		});
	},
	add: function() {
		var imgsrc = $("#setting_image").attr("src");
		if ( typeof(imgsrc)=="undefined" || imgsrc=="") {
			$(".component_switch_error").children().show();
			$(".component_switch_error>span").text("请选择或上传轮换图片");
			setTimeout("$('.component_switch_error').children().hide();",2000);
		}
		else {
			var inputText  = $("#text_desc").val();
			$("#switch_picture").attr("src",imgsrc);
			$(".component_switch_title").text(inputText);
			//添加图片属性
			if ( $(".component_switch_number_active").text()=="+" ){
			  $(".component_switch_number_active").attr("class","component_switch_number");
			  $(".component_switch_number").attr("active",0);
			  var count = $("#pic_number>span:visible").length;		  
				var html ="<span class='component_switch_number_active' active=1 url='" + imgsrc + "' text='" + inputText +"'>" + count +"</span>";
				$("#pic_number>span:last").before(html);
				
				$("#component_tools").show();
	 	    $("#component_tools").css("margin-top",(count-1) * 21+"px");
				if ( count > 3)
				  $(".component_tools_delete_active").show();		
				else
					$(".component_tools_delete_active").hide();
			}
			else {
			  $(".component_switch_number_active").attr("url",imgsrc);
			 	$(".component_switch_number_active").attr("text",inputText);
			}
			$("#select_switchpic").modal("hide");
			if ( $("#pic_number").children().length == component_switch.itemcount + 1 )
			  $("#pic_number>span:last").hide();
		}
	},
	save: function() {
		var timer = 0;
		var playtype =  $("#switch_auto").attr("checked") == null ? "hand":"auto";
		if ( playtype=="auto") { //自动轮换
			 timer = $.trim($("#switch_timer").val());
		   if ( timer == "" || (timer * 1 < 1 || timer * 1 > 30)) {
			    ComponentAttr.showMessage("轮换间隔时间只能在1-30秒之间！");
			    return;
		   }
		}
		var attrs = new Object,pics = new Array();
		var resource = $("#switch_static").attr("checked") == null ? "dynamic":"static"; //数据来源
		if (resource == "static" ){
			 var controls = $("#pic_number>span");
			 if ( controls.length == 1){
			 	  ComponentAttr.showMessage("至少应有一项轮换内容");
			    return;
			 }
			 
			 for(var i=0; i < controls.length-1; i++){			  	
			 	 pics.push( { "url": controls.eq(i).attr("url"),"text":controls.eq(i).attr("text") });
			 }
			 attrs = { "timer": timer,"pics": pics };
		}
		else {
			var listurl = $.trim($("#text_address").val());
			if (listurl=="" || listurl=="http://" ){
				ComponentAttr.showMessage("请输入动态获得轮换图片URL地址！");
			  return;
			}
			else if ( listurl.substring(0,4)!="http") {
				ComponentAttr.showMessage("动态获取轮换图片URL地址不是有效地址！");
				return;
			}
			attrs = {"listurl":listurl,"timer": timer,"pics":pics};
		}
		var parameter = { "cindex":this._para.cindex, "code": this._para.code,"name": this._para.name,"attrs": attrs };
		oneApp.setInterComponent(this._para.functionid, parameter.cindex, parameter);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
	},
	selectIcon: function(controlid) {
		ComponentAttr.currentEle = {
			"component": "component_switch",
			"htmlelement": controlid
		};
	},
	setappresource: function(controlid) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if (  resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			 $("#" + controlid).attr("src", url);
    }
	}
};

var component_search={
	_para:null,
	itemcount:1,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"门户全局搜索组件，点击搜索区域后会进入全局搜索页", //组件描述
	tagName:"search",
	name:"搜索组件",
	type:"user", //用户自定义组件
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		
	},	
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_search' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_search_icon'></span><span>搜索</span></div>";
	},
	getHtmlEle2:function(para){
		return "<div component='component_search' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_search_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>搜索</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params) {
		this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","search"),null,function(){	
			 $(".component_edit_buttom").show();
			 var url = params.attrs.url;
			 if ( typeof(url)=="undefined" || url=="")  url = "http://";		
			 $("#component_search_url").val( url );
			 $("#component_search_parameter").val(params.attrs.text);
		});
	},
	save:function(){
		var url = $.trim($("#component_search_url").val());
		var key = $.trim($("#component_search_parameter").val());
		if (url == "") {
			ComponentAttr.showMessage("请输入获取数据URL");
			return;
		}
		if (url.substring(0,4) != "http") {
			ComponentAttr.showMessage("获取数据URL地址不是有效的！");
			return;
		}
		if ( key=="" ) key="请输入查询关键字";
		this._para.attrs.url = (url=="http://" ? "" : url);
	  this._para.attrs.text = key;
	  this._para.attrs.functionid = { "target":"blank","text": this._para.functionid+"-"+this._para.cindex+"-0" };
    oneApp.setInterComponent(this._para.functionid,this._para.cindex, this._para);
    SaveApplicationConfig(oneApp);
    ComponentEdit.functionDev(this._para.functionid + "-" + this._para.cindex + "-0","详细内容");
    ComponentAttr.cancel();		
	},
  customize:function(ev){  //页面详细定制
  	 AppConfigManager.hint = false;
	 	 component_search.save();
	 	 var functionid = this._para.functionid+"-"+this._para.cindex+"-0" ;
	 	 AppConfigManager.interNodeSelected(functionid);
  }
};

var component_nav={
	_para:null,
	itemcount:5,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"选择导航控件将覆盖页面上的所有内容", //组件描述
	tagName:"nav",
	name:"导航组件",
	type:"user", //用户自定义组件
	currentRow:null,
	loadStyleList: function() {},
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){
		this.save();		
		$(".user_appconfig_list[uuid='"+vFunctinid+"']").trigger('click');
	},	
	getHtmlEle:function(para){
		return "<div component='component_nav' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_nav_icon'></span><span>导航菜单</span></div>";
	},
	getHtmlEle2:function(para){
		return "<div component='component_nav' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_nav_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>导航菜单</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},	
	selectIcon: function(e) {
    var id = $(e).find("img").attr("id");
		ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement": id
		};
	},
	setappresource: function(imgId) {
		var resource = getappresource(); //获取当前选择的资源ID	
		if ( resource != null) {
			 var url = "";
			 if ( resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			$("#" + imgId).show();
			$("#" + imgId).attr("src", url);
		}
	},
	edit:function(params){
		this._para = params;
	 	this.loadStyleList();
	 	params.outer.html("");
	 	LoadComponent(params.outer,componentattrediturl.replace("componentname","nav"),null,function(){
			params.outer.append('<div class="clearfix"></div>');
			$(".component_edit_buttom").show();
			BindComponentNav(component_nav._para.attrs);
		});
	},
	remove:function(e){
		if ( $("#component_item_area .component_nav_item").length==1) {
      ComponentAttr.showMessage("至少应保留一个菜单项！");			
			return;
		}
		this.currentRow = $(e).parents(".component_nav_item");
		showConfirmBox("确定删除导航项吗？","component_nav.deleteItem()");
	},
	deleteItem: function() {
		if ( this.currentRow == null ) return false;
		var itemindex =  this.currentRow.attr("functionid");
		this.currentRow.remove();
		//删除项后重新排列序号
		var controls = $("#component_item_area .component_nva_no");
		for(var i=0;i<controls.length;i++){
			controls.eq(i).text(i+1);
		}
		//是否显示添加功能
		if ( controls.length < parseInt(component_nav.itemcount))
		  $(".component_nvar_bottom").show();
		else
			$(".component_nvar_bottom").hide();
		if ( controls.length < 6){
			$("#nva_bottom").removeAttr("disabled");
		}
		if ( itemindex!=null && itemindex!="") {
		  component_nav.deleteFunctions(null,itemindex);
		  component_nav.save();
		  //AppConfigManager.componentNodeSelected(this._para.functionid,this._para.index);
		}
		hideConfirmBox();
	},
	customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_nav_item").find("input");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入导航菜单名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return; 	
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	   AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_nav_item").attr("functionid",funid);
 	   	 component_nav.customize(ev);
 	  }
	},
	customize:function(ev){  //页面详细定制
		 var inputControl = $(ev).parents(".component_nav_item").find("input");
 	   var inputtext = $.trim(inputControl.val());
 	   if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输入导航菜单名称！" );
 	  	 inputControl.css("border","1px solid #cc3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);	 	  	
 	   }
 	   else{
 	     	var functionid = $(ev).parents(".component_nav_item").attr("functionid");
 	     	AppConfigManager.hint = (functionid==null || functionid=="") ? true : false;
 	   	  var index = $(ev).parents(".component_nav_item").index();
 	   	  while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
				}
				$(ev).parents(".component_nav_item").attr("functionid",functionid);
				AppConfigManager.hint = false;
				component_nav.save();
 	      AppConfigManager.interNodeSelected(functionid);
 	   }
	},
	save:function(){
		if (ComponentAttr.checkExists(".component_nav_input>input","不能有相同的导航菜单！")) return;
		var navitems = new Array();
	  var controls = $("#component_item_area .component_nav_item");
	  var itemname = "",itemicon="",itemicon_active="",defaultimg="",activeimg="";
		for(var i=0;i < controls.length;i++){
			var $item = controls.eq(i);
			itemname = $.trim($item.find("input").val());
			if (itemname=="" && this.currentRow == null){
				ComponentAttr.showMessage("请输入第" + $item.find(".component_nva_no").text() +"条导航菜单名称！");
				var inputCtl = $item.find("input");
				inputCtl.css("border","1px solid #CC3300");
				inputCtl.focus();
				setTimeout(function() { inputCtl.css("border","1px solid #AAAAAA");},2000);
				return;
			}
			if ( itemname=="" ) continue;
			defaultimg = $item.find("img").eq(0).attr("src");
		  if ( defaultimg==null || defaultimg =="")
			  defaultimg = "/bundles/fafatimewebase/images/defaultpic.png";
			activeimg =  $item.find("img").eq(1).attr("src");
			if ( activeimg == null || activeimg=="" )
			  activeimg = "/bundles/fafatimewebase/images/defaultimg.png";
			
		  var functionid = new Object;
		  functionid.text = controls.eq(i).attr("functionid");
			var j = i;
			while ( functionid.text==""){
			  var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
				if(oneApp.getInterData(theid) == null){
				  functionid.text = theid;
				  break;
				}
				else{
				  j++;
				}
		  }		  
			functionid.target = controls.eq(i).attr("target");
			oneApp.updateInterInfo(functionid.text,"functionname",itemname);
			navitems.push( {"itemname":itemname,"itemicon":defaultimg,"itemicon_active":activeimg,"functionid":functionid });
		}
		var attrs = new Object;
		attrs.navitems = navitems;
		attrs.navtype = $("#nva_bottom").attr("checked") == null ? "side":"bottom";
		attrs.bgcolor = componentColor.formatRgb($("#component_item_area .component_nav_backdefault:first").css("background-color"));
		attrs.bgcolor_active = componentColor.formatRgb($("#component_item_area .component_nav_backactive:first").css("background-color"));
		this._para.attrs = attrs;
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		if ( this.currentRow == null ){
		   for(var i=0;i<navitems.length;i++){
   		   ComponentEdit.functionDev(navitems[i].functionid.text,navitems[i].itemname);
		   }
		   ComponentAttr.cancel();
		 }
		else
			this.currentRow = null;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

var component_tabs = {
	_para:null,
	itemcount:7,
	maxcount:"5", //同一页面允许添加的最大数量
	desc:"当需要对内容区的内容进行分类过滤时，可使用该组件。该组件支持左右滑动切换分类。", //组件描述
	tagName:"tabs",
	type:"user", //用户自定义组件
	name:"二次分类组件",
	currentRow:null,
	loadStyleList: function() {
	},	
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){
		this.save();
		$(".user_appconfig_list[uuid='"+vFunctinid+"']").trigger('click');
	},
	getHtmlEle:function(para){
		return "<div component='component_tabs' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_tabs_icon'></span><span>二级分类</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_tabs' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_tabs_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>二级分类</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
  remove:function(e){
		if ( $(".component_tabs_area .component_tabs_item").length==1) {
      ComponentAttr.showMessage("至少应保留一个选项卡！");
			return;
		}
		this.currentRow = $(e).parents(".component_tabs_item");
		showConfirmBox("确定删除该本项吗？", "component_tabs.deleteItem()");
	},
	deleteItem: function() {
		var itemindex = this.currentRow.attr("functionid");
		this.currentRow.remove();
		//重新排序
		var control = $(".component_tabs_area").children();
		for(var i=0;i < control.length;i++){
			control.eq(i).find(".component_tabs_no").text(i+1);
		}
		if ( itemindex!="") {
		  component_tabs.deleteFunctions(null,itemindex);
		  component_tabs.save();
		}
		else { 
			this.currentRow = null ;
		}	
		hideConfirmBox();
	},	
	edit:function(params){
	  this._para = params;
		this.loadStyleList();		
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","tabs"),null,function(){
			 $(".component_edit_buttom").show();
			 BindTabs(params.attrs);
	  });
	},
  customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_tabs_item").find("input");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入选项卡名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return;
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	  AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_tabs_item").attr("functionid",funid);
 	   	 component_tabs.customize(ev);
 	  }
	},		
	//详细页面定制
	customize:function(ev){
     var inputControl = $(ev).parents(".component_tabs_item").find("input");
 	   var inputtext = $.trim(inputControl.val());
 	   if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输入选项卡名称！" );
 	  	 inputControl.css("border","1px solid #cc3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);	 	  	
 	   }
 	   else{
 	   	  var functionid = $(ev).parents(".component_tabs_item").attr("functionid");
 	   	  AppConfigManager.hint = (functionid==null || functionid=="") ? true : false;
 	   	  var index = $(ev).parents(".component_tabs_item").index();
 	   	  while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
				}
				$(ev).parents(".component_tabs_item").attr("functionid",functionid);
				AppConfigManager.hint = false;
				component_tabs.save();
 	      AppConfigManager.interNodeSelected(functionid);
 	   }
	},
	save:function(){		
		if (ComponentAttr.checkExists(".component_tabs_area .component_tabs_item>input","不能有相同的选项卡！")) return;
		 var tabitems = new Array();
		 var controls = $(".component_tabs_area").children();
		 for( var i=0;i< controls.length;i++){
		 	  var itemname = $.trim(controls.eq(i).find("input").val());
		 	  if ( itemname=="" && this.currentRow==null ){
		 	  	 ComponentAttr.showMessage("请输入第 " + (i+1) +" 条选项卡名称！");
				   controls.eq(i).find("input").css("border","1px solid #CC3300");
				   controls.eq(i).find("input").focus();
		 	  	 return;
		 	  }
 	  	  if ( itemname =="") continue;
				var functionid = new Object;
				functionid.text = controls.eq(i).attr("functionid");
				var j = i;
				while ( functionid.text==""){
				  var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
					if(oneApp.getInterData(theid) == null){
					  functionid.text = theid;
					  break;
					}
					else{
					  j++;
					}
			  }
				functionid.target = controls.eq(i).attr("target");
 	  	  oneApp.updateInterInfo(functionid.text,"functionname",itemname);
 	  	  tabitems.push( { "itemname": itemname, "itemicon": "","itemicon_active": "","functionid": functionid } );
		 }
		 //默认色和选中色
		 var bgcolor = $(".component_tabs_colorRow .component_tabs_defaultcolor").css("background-color");
		 bgcolor = componentColor.formatRgb(bgcolor);
		 var bgcolor_active = $(".component_tabs_colorRow .component_tabs_activecolor").css("background-color");
		 bgcolor_active = componentColor.formatRgb(bgcolor_active);
		 //组织属性
		 var Attrs = new Object;
		 Attrs.tabitems = tabitems;
     Attrs.bgcolor = bgcolor;
		 Attrs.bgcolor_active = bgcolor_active;
		 this._para.attrs = Attrs;
	 	 oneApp.setInterComponent(this._para.functionid, this._para.cindex,this._para);
 		 SaveApplicationConfig(oneApp);
 		 if ( this.currentRow == null ){
		   for(var i=0;i<tabitems.length;i++){
   		   ComponentEdit.functionDev(tabitems[i].functionid.text,tabitems[i].itemname);
		   }
		   ComponentAttr.cancel();
		 }
		else
			this.currentRow = null;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			$(".user_appconfig_area>.user_appconfig_list[uuid='"+vItemIndex+"']").remove();
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

//用户属性组件
var component_userprofile = {
	_para: null,
	itemcount:5, //允许添加的最大列表项数
	maxcount:1, //同一页面允许添加的最大数量，n表示多个
	tagName:"summary",
	desc:"用户属性组件", //组件描述
	name:"用户属性组件",
	type:"user", //用户自定义组件
	currentRow:null,
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_userprofile' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_list_icon'></span><span>用户属性</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_userprofile' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_list_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>用户属性</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	selectIcon: function(e) {
		var imgid = $(e).attr("id");
		ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement":imgid
		};
	},
	setappresource: function(imgid) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if ( resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			$("#"+imgid).attr("src", url);
		}
	},		
	remove: function(e) {
	  if ( $("#userprofile_area").children().length == 1) {
      ComponentAttr.showMessage("至少应保留一个列表项！");		
			return;
		}
		this.currentRow = $(e).parents(".component_newslist_item");
		showConfirmBox("确定删除该本项吗？", "component_userprofile.deleteItem()");
	},
	deleteItem: function() {
	  var functionid = this.currentRow.attr("functionid");
		this.currentRow.remove();
		//重新排序
		var control = $("#userprofile_area").children();
		if ( control.length< parseInt(component_userprofile.itemcount))
		  $("#addItem").show();
		for(var i=0;i < control.length;i++){
			control.eq(i).find(".component_newslist_no").text(i+1);
		}
		if ( functionid!=null && functionid!="") {
		  component_userprofile.deleteFunctions(null,functionid);
		  AppConfigManager.hint = false;
		  component_userprofile.save();
		}
		else{
			this.currentRow = null;
		}		
		hideConfirmBox();
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","userprofile"),null,function(){
			 $(".component_edit_buttom").show();
		   BundleAttr(params.attrs);
		});
	},
  customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_newslist_item").find("input");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入用户属性名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return; 	
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	   AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_newslist_item").attr("functionid",funid);
 	   	 component_userprofile.customize(ev);
 	  }
	},	
	customize:function(ev){  //页面详细定制
		var inputControl = $(ev).parents(".component_newslist_item").find(".itemname");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输用户属性名称！" );
 	  	 inputControl.css("border","1px solid #CC3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);	 	  	
 	  }
 	  else{
 	   	 var functionid = $(ev).parents(".component_newslist_item").attr("functionid");
 	   	 AppConfigManager.hint = (functionid==null || functionid=="") ? true:false;
 	   	 var index = $(ev).parents(".component_newslist_item").index();
 	   	 while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
			 }
			 $(ev).parents(".component_newslist_item").attr("functionid",functionid);
			 component_userprofile.save();
 	     AppConfigManager.interNodeSelected(functionid);
 	   }
	},
	save:function(){
	  if (ComponentAttr.checkExists(".component_newslist_item .itemname","不能有相同的项！"))
	     return false;
	  var controls = $("#userprofile_area").children();
		var items = new Array(),itemicon = "",itemname="",inputcontrol=null,dataurl="";
		for (var i = 0; i < controls.length; i++) {
			inputcontrol = controls.eq(i).find(".itemname");
			itemname = $.trim(inputcontrol.val());
			if ( itemname=="" && this.currentRow == null){
				inputcontrol.focus();
				inputcontrol.css("border","1px solid #CC3300");
				ComponentAttr.showMessage("请输用户属性名称！");
				setTimeout(function(){inputcontrol.css("border","1px solid #AAAAAA");},2000);
				return;
			}
			//数据接口
			inputcontrol = controls.eq(i).find(".dataurl");
			dataurl = $.trim(inputcontrol.val());
		　if ( dataurl!="" && dataurl.match(/^http:\/\/.+\..+/i)==null && this.currentRow==null){
				inputcontrol.focus();
				inputcontrol.css("border","1px solid #CC3300");
				ComponentAttr.showMessage("请输入正确的数据接口地址");
				setTimeout(function(){inputcontrol.css("border","1px solid #AAAAAA");},2000);
				return;
		  }
			itemicon = controls.eq(i).find("img").attr("src");
			itemicon = itemicon==null ? "":itemicon;
			var functionid = new Object;
			functionid.text = controls.eq(i).attr("functionid");
			var j=i;
			while ( functionid.text==""){
			   var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
			   if(oneApp.getInterData(theid) == null){
			   	  functionid.text = theid;
			   	  break;
			   }
			   else{
			   	 j++;
			   }
			}
			functionid.target = controls.eq(i).attr("target");
			oneApp.updateInterInfo(functionid.text,"functionname",itemname);				
		  items.push({"functionid":functionid,"itemicon":itemicon,"itemname":itemname,"dataurl":dataurl});
		}
	  this._para.attrs.items = items;
	  this._para.attrs.color =	componentColor.formatRgb($("#fontcolor").css("background-color"));
	  this._para.attrs.bgcolor =	componentColor.formatRgb($("#bgcolor").css("background-color"));
	  var imgurl = $("#userbgimg").attr("src");
	  imgurl = imgurl==null ? "":imgurl;
	  this._para.attrs.bgpic =imgurl;
	  imgurl = $("#userheader").attr("src");
	  imgurl = imgurl ==null ? "":imgurl;
	  this._para.attrs.header =	imgurl;
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		if ( this.currentRow == null ){
	    for(var i=0;i<items.length;i++){
 		    ComponentEdit.functionDev(items[i].functionid.text,items[i].itemname);
	    }
		  ComponentAttr.cancel();
		}
		else
			this.currentRow = null;
		return true;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

//用户信息组件
var component_userbasicinfo = {
	_para: null,
	maxcount:"1", //同一页面允许添加的最大数量，n表示多个
	tagName:"userbasicinfo",
	desc:"用户账号组件", //组件描述
	name:"用户账号组件",
	type:"user", //用户自定义组件
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_userbasicinfo' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_list_icon'></span><span>用户账号</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_userbasicinfo' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_list_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>用户账号</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},	
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","userbasicinfo"),null,function(){
			 $(".component_edit_buttom").show();
		   $("#backgroudcolor").css("background-color",params.attrs.bgcolor);
   	   $("#fontcolor").css("background-color",params.attrs.color);
		});
	},
	customize:function(){
  	 AppConfigManager.hint = false;
	 	 component_userbasicinfo.save();
	 	 var functionid = this._para.functionid+"-"+this._para.cindex+"-0" ;
	 	 AppConfigManager.interNodeSelected(functionid);
  },
	save:function(){
	  this._para.attrs.color = componentColor.formatRgb($("#fontcolor").css("background-color"));
	  this._para.attrs.bgcolor = componentColor.formatRgb($("#backgroudcolor").css("background-color"));
		this._para.attrs.functionid = { "target":"blank","text": this._para.functionid+"-"+this._para.cindex+"-0" };
		this._para.attrs.style="default";
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentEdit.functionDev(this._para.attrs.functionid.text ,"详细页面");		  
		ComponentAttr.cancel();
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex)
	{
		if(vItemIndex!=null)
		{
			$(".user_appconfig_area>.user_appconfig_list[uuid='"+vItemIndex+"']").remove();
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}	
};

//搭配列表组件
var component_matchlist ={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"搭配列表组件", //组件描述
	tagName:"matchlist",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_matchlist' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span>搭配列表</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_matchlist' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span class='application_component_name' style='text-align:left'>搭配列表</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	customize:function(){
		 AppConfigManager.hint = false;
	 	 component_search.save();
	 	 var functionid = this._para.functionid+"-"+this._para.cindex+"-0" ;
	 	 AppConfigManager.interNodeSelected(functionid);
	},
	edit:function(params){
		this._para = params;
		this.loadStyleList();		
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","matchlist"),null,function(){
			 $(".component_edit_buttom").show();
			 BundleAttr(params.attrs);
	  });
	},
	save:function(){
	  var	para_url = $.trim($("#matchlist_url").val());
	  if ( para_url == null || para_url==""){
	  	$("#matchlist_url").focus();
			$("#matchlist_url").css("border","1px solid #CC3300");
			ComponentAttr.showMessage("请输入\"数据获取接口的地址\"");
			setTimeout(function(){ $("#matchlist_url").css("border","1px solid #AAAAAA");},2000);
	  }
	  else if (para_url.match(/^http:\/\/.+\..+/i) == null){
		  $("#matchlist_url").focus();
			$("#matchlist_url").css("border","1px solid #CC3300");
			ComponentAttr.showMessage("请输入正确的\"数据获取接口地址\"");
			setTimeout(function(){ $("#matchlist_url").css("border","1px solid #AAAAAA");},2000);
	  }
	  else{
	  	this._para.attrs.title = $.trim($("#matchlist_title").val());
	  	this._para.attrs.url = (para_url=="http://" ? "" : para_url);
		  this._para.attrs.para_code = $.trim($("#matchlist_para_code").val());
		  var functionid = this._para.functionid+"-"+this._para.cindex+"-0";
		  this._para.attrs.functionid = { "target":"self","text":functionid};
	    oneApp.setInterComponent(this._para.functionid,this._para.cindex, this._para);
	    SaveApplicationConfig(oneApp);
	    ComponentEdit.functionDev(functionid,"详细内容");
	    ComponentAttr.cancel();
	  }
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex)
	{
		if(vItemIndex!=null)
		{
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
		}
	}	
};

//搭配详细组件
var component_matchdetail = {
	_para: null,
	itemcount:5, //允许添加的最大列表项数
	maxcount:1, //同一页面允许添加的最大数量，n表示多个
	tagName:"matchdetail",
	desc:"搭配详细", //组件描述
	name:"搭配详细组件",
	type:"native", //原生功能组件
	currentRow:null,
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_matchdetail' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span>搭配详细</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_matchdetail' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>搭配详细</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	selectIcon: function(e) {
		var imgid = $(e).attr("id");
		ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement":imgid
		};
	},
	setappresource: function(imgid) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if ( resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			$("#"+imgid).attr("src", url);
		}
	},		
	remove: function(e) {
	  if ( $("#matchdetail_area").children().length == 1) {
      ComponentAttr.showMessage("至少应保留一个列表项！");		
			return;
		}
		this.currentRow = $(e).parents(".component_matchdetail_rows");
		showConfirmBox("确定删除该本项吗？", "component_matchdetail.deleteItem()");
	},
	deleteItem: function() {
	  var functionid = this.currentRow.attr("functionid");
		this.currentRow.remove();
		//重新排序
		var control = $("#matchdetail_area").children();
		if ( control.length < parseInt(component_userprofile.itemcount))
		  $("#addItem").show();
		else
			$("#addItem").hide();
		for(var i=0;i < control.length;i++){
			control.eq(i).find(".component_newslist_no").text(i+1);
		}
		if ( functionid!=null && functionid!="") {
		  component_matchdetail.deleteFunctions(null,functionid);
		  AppConfigManager.hint = false;
		  component_matchdetail.save();
		}
		else{
			this.currentRow = null;
		}
		hideConfirmBox();
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","matchdetail"),null,function(){
			 $(".component_edit_buttom").show();
		   BundleAttr(params);
		});
	},
  customizebyempty:function(ev){
		var inputControl = $(ev).parents(".component_matchdetail_rows").find(".itemname");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	    ComponentAttr.showMessage("请输入操作名称！" );
 	  	inputControl.css("border","1px solid #cc3300");
 	  	inputControl.focus();
 	  	setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);
 	  	return; 	
 	  }
		var html = ComponentAttr.getSelectedPageHtml();
 	  $(".component_selected_page").html(html.join(''));
 	  $("#selectPageDialog").modal("show");
 	   AppConfigManager.onSelectpage = function(funid){
 	   	 $(ev).parents(".component_matchdetail_rows").attr("functionid",funid);
 	   	 component_matchdetail.customize(ev);
 	  }
	},	
	customize:function(ev){  //页面详细定制
		var ctlid = $(ev).attr("id");
		var special_funcid = "",functionid="";
		if (ctlid!=null && ctlid=="list_function"){
		   special_funcid = $(ev).attr("functionid");
		}
		else{
			var inputControl = $(ev).parents(".component_matchdetail_rows").find(".itemname");
		 	var inputtext = $.trim(inputControl.val());
		 	if ( inputtext =="" ){
		 		ComponentAttr.showMessage("请输入操作名称！" );
	  	  inputControl.css("border","1px solid #CC3300");
	  	  inputControl.focus();
	  	  setTimeout(function(){ inputControl.css("border","1px solid #cccccc")  },1000);
	  	  return false;
		 	}
		 	else{
		   	 functionid = $(ev).parents(".component_matchdetail_rows").attr("functionid");
		   	 AppConfigManager.hint = (functionid==null || functionid=="") ? true:false;
		   	 var index = $(ev).parents(".component_matchdetail_rows").index();
		   	 while ( functionid==""){
					   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
					   if(oneApp.getInterData(theid) == null){
					   	  functionid = theid;
					   	  break;
					   }
					   else{
					   	 index++;
					   }
				 }
				 $(ev).parents(".component_newslist_item").attr("functionid",functionid);
		 	}
	  }	 	
 	  if ( component_matchdetail.save()){
 	    functionid = special_funcid!="" ? special_funcid:functionid;
	 	  AppConfigManager.interNodeSelected(functionid)
	  }
	},
	save:function(){
		this._para.attrs.title = $.trim($("#text_title").val());
		if ( this.checkurl($("#text_url"))) return;
	  this._para.attrs.url = $.trim($("#text_url").val());
	  if ( this.checkurl($("#text_comment_url"))) return;
	  this._para.attrs.comment_url = $.trim($("#text_comment_url").val());
	  this._para.attrs.para_code = $.trim($("#text_para_code").val());	  
	  if ( this.checkurl($("#text_list_url"))) return;
	  var list = new Object;
	  list.listurl = $.trim($("#text_list_url").val());
	  list.listurlpara = $.trim($("#text_list_para").val());
	  var list_functionid = $("#list_function").attr("functionid");
	  if ( list_functionid==null || list_functionid=="")
	    list_functionid = this._para.functionid+"-"+this._para.cindex+"-"+this.functionid+"-0";
	  var  list_target = $("#list_function").attr("target");
	  list_target = (list_target==null || list_target =="") ? "self" : list_target;
	  list.functionid =  { "text":list_functionid,"target":list_target }; 		
	  if (ComponentAttr.checkExists(".component_matchdetail_rows .itemname","不能有相同的操作名称！"))
	     return false;
	  var controls = $("#matchdetail_area").children();
		var items = new Array(),itemicon = "",itemname="",inputcontrol=null,parameter="";
		for (var i = 0; i < controls.length; i++) {
			inputcontrol = controls.eq(i).find(".itemname");
		  itemicon = controls.eq(i).find(".component_menuitem_img").attr("src");
		  itemicon = itemicon==null ? "":itemicon;		  
			itemname = $.trim(inputcontrol.val());
			if ( itemname=="" && this.currentRow == null){
				inputcontrol.focus();
				inputcontrol.css("border","1px solid #CC3300");
				ComponentAttr.showMessage("请输入操作名称！");
				setTimeout(function(){inputcontrol.css("border","1px solid #cccccc");},2000);
				return false;
			}
			//数据接口
			inputcontrol = controls.eq(i).find(".parameter");
			parameter = $.trim(inputcontrol.val());
			var functionid = new Object;
			functionid.text = controls.eq(i).attr("functionid");
			var j=i;
			while ( functionid.text==""){
			   var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
			   if(oneApp.getInterData(theid) == null){
			   	  functionid.text = theid;
			   	  break;
			   }
			   else{
			   	 j++;
			   }
			}
			functionid.target = controls.eq(i).attr("target");
			oneApp.updateInterInfo(functionid.text,"functionname",itemname);				
		  items.push({"functionid":functionid,"icon":itemicon,"text":itemname,"para":parameter});
		}
	  this._para.attrs.list = list;
	  this._para.attrs.functionbar.items = items;
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		if ( this.currentRow == null ){
	    for(var i=0;i<items.length;i++){
 		    ComponentEdit.functionDev(items[i].functionid.text,items[i].text);
	    }
	    if ( list_functionid!=null && list_functionid!="")
	       ComponentEdit.functionDev(list_functionid,"商品列表详细内容");
		  ComponentAttr.cancel();
		}
		else
			this.currentRow = null;
		return true;
	},
	checkurl:function(control){
		var url = $.trim(control.val());
		var result = false;
		if (url!="" && url.match(/^http:\/\/.+\..+/i)==null){
		  control.focus();
			control.css("border","1px solid #CC3300");
			ComponentAttr.showMessage("请输入正确的\"数据接口地址\"");
			setTimeout(function(){ control.css("border","1px solid #cccccc");},2000);
			result = true;
	  }
	  return result;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

//功能按钮组件
var component_functionbar = {
	_para: null,
	itemcount:5, //允许添加的最大列表项数
	maxcount:1, // 同一页面允许添加的最大数量，n表示多个
	tagName:"functionbar",
	desc:"用于定义一组功能按钮。", //组件描述
	name:"功能按钮组件",
	type:"user", //用户自定义组件
	currentRow:null,
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_functionbar' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_list_icon'></span><span>功能按扭</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_functionbar' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_list_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>功能按钮</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	selectIcon: function(e) {
		var imgid = $(e).attr("id");
		ComponentAttr.currentEle = {
			"component": this._para.code,
			"htmlelement":imgid
		};
	},
	setappresource: function(imgid) {
		var resource = getappresource();
		if ( resource != null) {
			 var url = "";
			 if ( resource.resourcetype=="system")
			    url = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
			 else
			 	  url = file_webserver_url + resource.resourceid;
			$("#"+imgid).attr("src", url);
		}
	},		
	remove: function(e) {
	  if ( $("#functionbar_area").children().length == 1) {
      ComponentAttr.showMessage("至少应保留一项！");	
			return;
		}
		this.currentRow = $(e).parents(".component_functionbar_rows");
		showConfirmBox("确定删除该本项吗？", "component_functionbar.deleteItem()");
	},
	deleteItem: function() {
	  var functionid = this.currentRow.attr("functionid");
		this.currentRow.remove();
		
		if ( $("#functionbar_area").children().length < parseInt(component_functionbar.itemcount))
		  $("#addItem").show();
    else
    	$("#addItem").hide();
		if ( functionid!=null && functionid!="") {
		  component_functionbar.deleteFunctions(null,functionid);
		  AppConfigManager.hint = false;
		  component_functionbar.save();
		}
		else{
			this.currentRow = null;
		}		
		hideConfirmBox();
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","functionbar"),null,function(){
			 $(".component_edit_buttom").show();
		   BundleFunctionBar(params.attrs);
		});
	},
	customize:function(ev){  //页面详细定制
		var inputControl = $(ev).parents(".component_functionbar_rows").find(".itemname");
 	  var inputtext = $.trim(inputControl.val());
 	  if ( inputtext =="" ){
 	  	 ComponentAttr.showMessage("请输入名称！" );
 	  	 inputControl.css("border","1px solid #CC3300");
 	  	 inputControl.focus();
 	  	 setTimeout(function(){ inputControl.css("border","1px solid #AAAAAA")  },1000);	 	  	
 	  }
 	  else{
 	   	 var functionid = $(ev).parents(".component_functionbar_rows").attr("functionid");
 	   	 AppConfigManager.hint = (functionid==null || functionid=="") ? true:false;
 	   	 var index = $(ev).parents(".component_functionbar_rows").index();
 	   	 while ( functionid==""){
				   var theid = this._para.functionid + "-" + this._para.cindex + "-" + index;
				   if(oneApp.getInterData(theid) == null){
				   	  functionid = theid;
				   	  break;
				   }
				   else{
				   	 index++;
				   }
			 }
			 $(ev).parents(".component_functionbar_rows").attr("functionid",functionid);
			 component_functionbar.save();
 	     AppConfigManager.interNodeSelected(functionid);
 	   }
	},
	save:function(){
	  if (ComponentAttr.checkExists(".component_functionbar_rows .itemname","不能有相同的项！"))
	     return false;
	  var controls = $("#functionbar_area").children();
		var items = new Array(),itemicon = "",itemname="",inputcontrol= null,dataurl="";
		for (var i = 0; i < controls.length; i++) {
			inputcontrol = controls.eq(i).find(".itemname");
			itemname = $.trim(inputcontrol.val());
			if ( itemname=="" && this.currentRow == null){
				inputcontrol.focus();
				inputcontrol.css("border","1px solid #CC3300");
				ComponentAttr.showMessage("请输入名称");
				setTimeout(function(){inputcontrol.css("border","1px solid #AAAAAA");},2000);
				return;
			}
			//数据接口
			inputcontrol = controls.eq(i).find(".dataurl");
			dataurl = $.trim(inputcontrol.val());
		　if ( dataurl!="" && dataurl.match(/^http:\/\/.+\..+/i)==null && this.currentRow==null){
				inputcontrol.focus();
				inputcontrol.css("border","1px solid #CC3300");
				ComponentAttr.showMessage("请输入正确的数据接口地址");
				setTimeout(function(){inputcontrol.css("border","1px solid #AAAAAA");},2000);
				return;
		  }
			itemicon = controls.eq(i).find("img").attr("src");
			itemicon = itemicon==null ? "":itemicon;
			var functionid = new Object;
			functionid.text = controls.eq(i).attr("functionid");
			var j=i;
			while ( functionid.text==""){
			   var theid = this._para.functionid + "-" + this._para.cindex + "-" + j;
			   if(oneApp.getInterData(theid) == null){
			   	  functionid.text = theid;
			   	  break;
			   }
			   else{
			   	 j++;
			   }
			}
			functionid.target = controls.eq(i).attr("target");
			oneApp.updateInterInfo(functionid.text,"functionname",itemname);
			var para = $.trim(controls.eq(i).find(".para").val());
			para = para==null ? "":para;
			//按钮样式
			var _radio = controls.eq(i).find("#style_1").find("input");
			var _style = _radio.eq(0).attr("checked");
			_style = _style==null ? _radio.eq(1).val():_radio.eq(0).val();
		 //排列方式
		  _radio = controls.eq(i).find("#style_2").find("input");
			var _align = _radio.eq(0).attr("checked");
			_align = _align == null ? _radio.eq(1).val():_radio.eq(0).val();
		  items.push({"functionid":functionid,"icon":itemicon,"text":itemname,"dataurl":dataurl,"para":para,
		  	          "style":_style,"arrangement":_align });
		}
	  this._para.attrs.items = items;
	  this._para.attrs.color =	componentColor.formatRgb($("#fontcolor").css("background-color"));
	  this._para.attrs.bgcolor =	componentColor.formatRgb($("#bgcolor").css("background-color"));
	  var position = $("#radio_bottom").attr("checked");
	  position = position==null ? $("#radio_relative").val():$("#radio_bottom").val();
	  this._para.attrs.position = position;	  
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		if ( this.currentRow == null ){
	    for(var i=0;i<items.length;i++){
 		    ComponentEdit.functionDev(items[i].functionid.text,items[i].text);
	    }
		  ComponentAttr.cancel();
		}
		else
			this.currentRow = null;
		return true;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

//商品详细组件
var component_goodsdetail = {
	_para: null,
	itemcount:5, //允许添加的最大列表项数
	maxcount:1, //同一页面允许添加的最大数量，n表示多个
	tagName:"goodsdetail",
	desc:"用于对商品进行详细描述", //组件描述
	name:"商品详细组件",
	type:"native", //用户自定义组件
	joinFunction:function(vOwnerFunctionID,vItemIndex,vFunctinid){		 
	},
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		 return "<div component='component_goodsdetail' type='1' dom_index='"+para.dom_index+"' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span>商品详细</span></div>";
	},
	getHtmlEle2:function(para){
		 return "<div component='component_goodsdetail' type='1' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>商品详细</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
    this._para = params;
		this.loadStyleList();
		params.outer.html("");
		LoadComponent(params.outer,componentattrediturl.replace("componentname","goodsdetail"),null,function(){
			 $(".component_edit_buttom").show();
		   BundleAttr(params.attrs);
		});
	},
	save:function(){
	  if (ComponentAttr.checkExists(".component_goodsrow>input","不能有相同的地址！"))
	     return false;
	  var controls = $("#component_goodsrow>input");
	  this._para.attrs.title = $.trim($("#text_title").val());	  
	  if (this.checkurl($("#text_url"))) return;
	  this._para.attrs.url = $.trim($("#text_url").val());
	  if (this.checkurl($("#text_price_url"))) return;
	  this._para.attrs.price_url =   $.trim($("#text_price_url").val());
	  if (this.checkurl($("#text_spec_url"))) return;
	  this._para.attrs.spec_url =   $.trim($("#text_spec_url").val());
	  if (this.checkurl($("#text_color_url"))) return;
	  this._para.attrs.color_url =   $.trim($("#text_color_url").val());
	  if (this.checkurl($("#text_stock_url"))) return;
	  this._para.attrs.stock_url =   $.trim($("#text_stock_url").val());
	  if (this.checkurl($("#text_buy_url"))) return;
	  this._para.attrs.buy_url =   $.trim($("#text_buy_url").val());
	  if (this.checkurl($("#text_join_url"))) return;
	  this._para.attrs.join_url =   $.trim($("#text_join_url").val());
	  //评论
	  if (this.checkurl($("#text_comment_url"))) return;
	  this._para.attrs.comment.attrs.url =   $.trim($("#text_comment_url").val());
	  this._para.attrs.comment.attrs.para =   $.trim($("#text_comment_para").val());
	  //收藏
	  if (this.checkurl($("#text_fav_url"))) return;
	  this._para.attrs.fav.attrs.url =   $.trim($("#text_fav_url").val());
	  this._para.attrs.fav.attrs.para =  $.trim($("#text_fav_para").val());
	  	  
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
		return true;
	},
	checkurl:function(control){
		var url = $.trim(control.val());
		var result = false;
		if (url!="" && url.match(/^http:\/\/.+\..+/i)==null){
		  control.focus();
			control.css("border","1px solid #CC3300");
			ComponentAttr.showMessage("请输入正确的\"数据接口地址\"");
			setTimeout(function(){ control.css("border","1px solid #cccccc");},2000);
			result = true;
	  }
	  return result;
	},
	deleteFunctions:function(vMasterFunctionID,vItemIndex) {
		if(vItemIndex!=null) {
			oneApp.removeInterByFunctionid(vItemIndex);
			AppConfigManager.removeTreeNode(vItemIndex,this._para.index);
			return;
		}
	}
};

var component_groupnews={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"群组动态组件。该组件为系统原生功能组件", //组件描述
	tagName:"groupnews",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_groupnews' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_groupnews_icon'></span><span>群组动态</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_groupnews' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_groupnews_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>群组动态</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	  params.outer.html('<div style="color: rgb(153, 153, 153); text-align: center; line-height: 300px;">该组件暂不支持动态编辑功能！</div>');
	},
	save:function(){
		ComponentAttr.cancel();
	}
};
var component_circlenews={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"圈子动态组件。该组件为系统原生功能组件", //组件描述
	tagName:"circlenews",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_circlenews' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_circlenews_icon'></span><span>圈子动态</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_circlenews' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_circlenews_icon'></span><span class='application_component_name' style='text-align:left'>圈子动态</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
		this._para = params;
		params.outer.html(getHtmlByNativeTile());
		$("#native_title").val(params.attrs.title);
		$("#native_title").focus();
	},
	save:function(){
	  this._para.attrs.title = $.trim($("#native_title").val());
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
	}
};

var component_publicaccount={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"公众号组件。该组件为系统原生功能组件", //组件描述
	tagName:"publicaccount",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_publicaccount' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_publicaccount_icon'></span><span>公众号</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_publicaccount' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_publicaccount_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>公众号</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	  params.outer.html('<div style="color: rgb(153, 153, 153); text-align: center; line-height: 300px;">该组件暂不支持动态编辑功能！</div>');
	},
	save:function(){
		ComponentAttr.cancel();
	}	
};

var component_repository={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"知识库组件。该组件为系统原生功能组件", //组件描述
	tagName:"repository",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_repository' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_repository_icon'></span><span>知识库</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_repository' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_repository_icon'></span><span class='application_component_name' style='text-align:left'>知识库</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	  params.outer.html('<div style="color: rgb(153, 153, 153); text-align: center; line-height: 300px;">该组件暂不支持动态编辑功能！</div>');
	},
	save:function(){
		ComponentAttr.cancel();
	}	
};
var component_contacts={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"通讯录组件。该组件为系统原生功能组件", //组件描述
	tagName:"contacts",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_contacts' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_contacts_icon'></span><span>通讯录</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_contacts' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_contacts_icon'></span><span class='application_component_name' style='text-align:left;margin-top: 3px;'>通讯录</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -7px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
		this._para = params;
		params.outer.html(getHtmlByNativeTile());
		$("#native_title").val(params.attrs.title);
		$("#native_title").focus();
	},
	save:function(){
		this._para.attrs.title = $.trim($("#native_title").val());
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
	}	
};

//消息中心
var component_message ={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"消息中心组件。该组件为系统原生功能组件", //组件描述
	tagName:"message",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_message' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_message_icon'></span><span>消息中心</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_message' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_message_icon'></span><span class='application_component_name' style='text-align:left'>消息中心</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	  this._para = params;
		params.outer.html(getHtmlByNativeTile());
		$("#native_title").val(params.attrs.title);
		$("#native_title").focus();
	},
	save:function(){
		this._para.attrs.title = $.trim($("#native_title").val());
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();		
	}	
};

//企业微博
var component_enoweibo ={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"企业微博组件。该组件为系统原生功能组件", //组件描述
	tagName:"enoweibo",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_enoweibo' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span>企业微博</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_enoweibo' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_enoweibo_icon'></span><span class='application_component_name' style='text-align:left'>企业微博</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	  params.outer.html('<div style="color: rgb(153, 153, 153); text-align: center; line-height: 300px;">该组件暂不支持动态编辑功能！</div>');
	},
	save:function(){
		ComponentAttr.cancel();
	}	
};

//设置
var component_setting ={
	_para:null,
	maxcount:"1", //同一页面允许添加的最大数量
	desc:"设置组件。该组件为系统原生功能组件", //组件描述
	tagName:"setting",
	type:"native", //系统原生功能组件
	loadStyleList: function() {
	},
	getHtmlEle:function(para){
		return "<div component='component_setting' dom_index='"+para.dom_index+"'  class='application_component_icon'><span class='application_component_setting_icon'></span><span>设置</span></div>";
	},	
	getHtmlEle2:function(para){
		return "<div component='component_setting' dom_index='"+para.dom_index+"' style='width:100%' class='application_component_icon'><span class='application_component_setting_icon'></span><span class='application_component_name' style='text-align:left'>设置</span><span class='application_sub_menu_addcomponent_active' style='margin-top: -10px; display: none;cursor:pointer' title='添加该组件到当前页面'></span><span  class='application_component_desc' style='width: 80%; text-align: left;'>"+this.desc+"</span></div>";
	},
	edit:function(params){
	 	this._para = params;
		params.outer.html(getHtmlByNativeTile());
		$("#native_title").val(params.attrs.title);
		$("#native_title").focus();
	},
	save:function(){
		this._para.attrs.title = $.trim($("#native_title").val());
		oneApp.setInterComponent(this._para.functionid, this._para.cindex, this._para);
		SaveApplicationConfig(oneApp);
		ComponentAttr.cancel();
	}	
};

function getHtmlByNativeTile(){
	var html = new Array();
	html.push("<div class='native_title_area'>");
	html.push("<div class='native_title_row'>");
	html.push(" <span>组件标题：</span><input type='text' id='native_title' />");
	html.push(" </div>");
	html.push("<span style='float:right;margin-right:5px;'>");
	html.push("  <span class='btn btn-primary' onclick='ComponentAttr.save()'>确认修改</span>"+
	          "  <span class='btn' onclick='ComponentAttr.deleteComponent()'>删除</span> "+
	          "  <span class='btn' onclick='ComponentAttr.cancel()'>取消</span>"+
	          "</span>");
	html.push("</div>");
  return html.join("");
}

//禁止输入非数字(对输入数据的判断)
function InputNumber(ev){
	var inputValue = $(ev).val(); 
  $(ev).val(inputValue.replace(/\D|^0/g,'')); 
}

//组件颜色选择对象
var componentColor = {
	hideTransparent:false,       //是否隐藏无色
	curcolor:"transparent",  //当前颜色,默认为无色
	container:null, 
	onSelected:null,
	onMouseMove:null,
  parentobj:null,
  beforeColor:null,
  onClose:null,
	setTitle:function(title){
		$(".portals_staff_windowtitle>span:first").text(title);
	},
	//选择无色事件
	transparent:function(){
		this.curcolor = "transparent";
		componentColor.setObjColor();
	},
	Confirm:function(){
		var color = $.trim($(".component_color_definedcolor>input").val()) || $(".component_color_attr span:eq(1)").text();
		if (color=="") return;
		this.curcolor = color;
		componentColor.setObjColor();
		$('.component_color').hide();
	},
	Init:function(obj){
		this.parentobj = obj;
	  if( obj.children().length == 0 ){
	  	var html = new Array();
	  	html.push("  <div class='portals_staff_windowtitle'><span></span>");
	  	html.push("     <span onclick='componentColor.fromclose();' class='component_color_close' style='float:right;' >×</span>");
	  	html.push("  </div>");
	  	html.push("  <div class='component_color_area'></div>");
	  	html.push("  <div class='component_color_attr'>");
	  	html.push("      <span></span><span></span><span onclick='componentColor.transparent();' style='cursor:pointer;'>无色</span>");
	  	html.push("  </div>");
	  	html.push(" <div class='component_color_definedcolor'>");
	  	html.push("    <span style='float:left;margin-left:10px;'>自定义：</span><input type='text' >");
	  	html.push("    <span class='appconfig_button component_color_button' onclick='componentColor.Confirm();'>确定</span>");
	  	html.push("    <span class='appconfig_button component_color_button' style='margin-left:0px;' onclick='componentColor.fromclose();'>取消</span>");
	  	html.push(" </div>");
	  	obj.html(html.join(''));
	  	this.container = $(".component_color_area")[0];
	    componentColor.showDialog();
	    //移动事件
	    $(".component_color_area>span").live("mouseover",function(){
	    	 var color = $(this).css("background-color");
	    	 $(".component_color_attr>span:first").css("background-color",color);
	    	 color = componentColor.formatRgb(color).toUpperCase();
	    	 $(".component_color_attr>span").eq(1).text(color);
	    	 componentColor.mouseMove(color);
	    });
      //单击事件
	    $(".component_color_area>span").live("click",function(){
	    	 var color = componentColor.formatRgb($(this).css("background-color"));
	    	 componentColor.curcolor = color.toUpperCase();
	    	 componentColor.setObjColor();
	    });	    
	  }
	  obj.show();
	  if ( this.hideTransparent) {
	  	$(".component_color_attr>span:last").hide();
		  $(".component_color_attr>span").css("width","80px");
	  }     
	},
	fromclose:function()	{
		if ( this.onClose != null){
			this.beforeColor = componentColor.formatRgb(this.beforeColor).toUpperCase();
		  this.onClose(this.beforeColor);
		  this.parentobj.hide();
	  }
	},
	showDialog:function(){
		var ColorHex=new Array('00','33','66','99','CC','FF');
    var SpColorHex=new Array('FF0000','00FF00','0000FF','FFFF00','00FFFF','FF00FF');
    var colorItem ;
    //循环出调色板
	  for(b=0;b<6;b++){
	    for(a=0;a<3;a++){
	     for(i=0;i<6;i++){
	       colorItem = document.createElement("span");
	       colorItem.style.backgroundColor="#"+ColorHex[a]+ColorHex[i]+ColorHex[b];
	       this.container.appendChild(colorItem);
	     }
	    }
	  }
		for(b=0;b<6;b++){
		  for(a=3;a<6;a++){
		   for(i=0;i<6;i++){
		     colorItem = document.createElement("span");
		     colorItem.style.backgroundColor="#"+ColorHex[a]+ColorHex[i]+ColorHex[b];
		     this.container.appendChild(colorItem);
		   }    
		  }
		}
    for(i=0;i<6;i++){
      colorItem = document.createElement("span");
      colorItem.style.backgroundColor="#"+ColorHex[0]+ColorHex[0]+ColorHex[0];
      this.container.appendChild(colorItem);
    }
	  for(i=0;i<6;i++){
	    colorItem = document.createElement("span");
	    colorItem.style.backgroundColor="#"+ColorHex[i]+ColorHex[i]+ColorHex[i];
	    this.container.appendChild(colorItem);
	  } 
    for(i=0;i<6;i++){
      colorItem = document.createElement("span");
      colorItem.style.backgroundColor="#"+SpColorHex[i];
      this.container.appendChild(colorItem);
    }
	},
	formatRgb:function(rgb){
		rgb = rgb == null ? "":rgb;
		if ( rgb.indexOf("rgb") == -1 || rgb.indexOf("rgba")>-1 ) return rgb;
    rgb = rgb.replace("rgb","");
    rgb = rgb.replace("(","");
    rgb = rgb.replace(")","");
    format = rgb.split(",");
    a = eval(format[0]).toString(16);
    b = eval(format[1]).toString(16);
    c = eval(format[2]).toString(16);
    rgb = "#"+checkFF(a)+checkFF(b)+checkFF(c);
    function checkFF(str){
      if(str.length == 1){
        str = str+""+str;
        return str;
      }
      else{
        return str;
      }
    }
    return rgb;
	},
	mouseMove:function(color){
		if ( this.onMouseMove != null)
		 this.onMouseMove(color);
	},
	setObjColor:function(){
		if(this.onSelected!=null) {
		  this.onSelected(this.curcolor);//选中回调事件
		  this.parentobj.hide();
		}
	}
};