/*组件托放操作*/
var componentDrop={
	isinit:false
};
//事件定义
componentDrop.init=function(){
	//if(this.isinit) return;
	//this.isinit=true;
	/*try{
		$(".component_nameitem").draggable( 'destroy' );
		$(".runtime_component_toolbar_move").draggable( 'destroy' );
	}
	catch(e){}
	try{
		$( ".interface" ).droppable( "destroy" );
	}
	catch(e){}
	try{
		$( ".tabs_div" ).droppable( "destroy" );
	}
	catch(e){}

 	this.addDorpSource($( ".component_nameitem" ));
 	this.addDorpSource($( ".runtime_component_toolbar_move" ));

	//this.addDropTarget($(".runtime_main_content"));
	//this.addDropTarget($(".cp_title"),$( ".component_nameitem[attrcode='component_menu']" ));
	this.addDropTarget($(".interface"),$( ".component_nameitem" ));
	this.addDropTarget($(".tabs_div"),$( ".component_nameitem" ));
	this.addDropTarget($(".component"),$( ".component_nameitem" ));
	this.addMoveTarget($(".component,.interface_head,.interface_center,.interface_foot"),$(".runtime_component_toolbar_move"));
	//this.addDropTarget($(".interface_root"),$( ".component_nameitem[attrcode='component_nav']" ));
	*/
	this.addDorpSource($("#componentitem_list_select .application_component_icon"));
	this.addDropTarget($("#componentitem_list_select"),$("#componentitem_list_select .application_component_icon"));
};

componentDrop.addDorpSource=function($ele)
{
	$ele.draggable({
		cursor: "move",
		revert: "invalid"	
	});
};
componentDrop.addMoveTarget=function($ele,$acceptEle){
	$ele.droppable({
		accept:$acceptEle==null? ".runtime_component_toolbar_move" : $acceptEle,
		greedy: true,
		hoverClass:"dropHover",
		drop:function( event, ui )
		{
			$target = $(this);
			var oldindex=$(".selectedComponent").attr("aindex");
			if($target.attr("class").indexOf("interface_head")>-1){
				return;
			}
			else if($target.attr("class").indexOf("interface_center")>-1){
				var classname=$(".selectedComponent").attr("class");
				if(classname.indexOf("cp_title")>-1 || classname.indexOf("cp_menu")>-1 || classname.indexOf("cp_nav"))return;
				
				var newindex=$($(".interface[functionid='"+oneApp.cFuncid+"']").find(".interface_center").children(".component").last()).attr("aindex");
				oneApp.moveInterComponent(oneApp.cFuncid,newindex,"next",oldindex);
			}
			else if($target.attr("class").indexOf("interface_foot")>-1){
				return;
			}
			else{
				if($target.attr("class").indexOf("cp_tabs")>-1){
					var newindex='';
					$last=$target.find("tab_selected").children().last();
					if($last.length==0){
						newindex=$target.attr("aindex")+"-"+(1+parseInt($target.find("tab_selected").attr("tabsindex"))).toString()+"-1";
						oneApp.moveInterComponent(oneApp.cFuncid,newindex,"next",oldindex);
					}
					else{
						newindex=$last.attr("aindex");
						oneApp.moveInterComponent(oneApp.cFuncid,newindex,"pre",oldindex);
					}
				}
				else{
					var newindex=$target.attr("aindex");
					oneApp.moveInterComponent(oneApp.cFuncid,newindex,"pre",oldindex);
				}
			}
		}
	});
};
componentDrop.addDropTarget=function($ele,$acceptEle){
	$ele.droppable({
		accept:$acceptEle==null? ".component_nameitem" : $acceptEle,
		greedy: true,
		hoverClass:"dropHover",
		drop:function( event, ui )
		{

			var $source = ui.draggable,$target = $(this),component_type=$source.attr("attrcode");
			var component = null,currfunctionid=oneApp.cFuncid, appXMLDom = $(oneApp.xmlBuilder.xmlDom.childNodes[0]).find("function>functionid:contains('"+currfunctionid+"')").parent();
			if(appXMLDom.length==0) return;
			if(appXMLDom.find("functiontype").text()!="1") return;
			//判断是否是操作的导航页面,是则将当前组件添加到当前导航页面
			var isNav = appXMLDom.children("template").children('nav');
			if(isNav.length>0)
			{
				if(component_type=="component_nav") 
					return;//一个界面只能有一个底部导航组件
			}
			var $class = $target.attr("class"),$targetXMLElement = null;
			$(document.body).css("cursor","default");
			if($class.indexOf("interface")>-1)
			{
				//托放到主页面内时
				//判断当前页面是否有导航
				if(appXMLDom.find("nav").length==0)
				{
					if(component_type=="component_nav")
					{
						//用户操作确定提示
						wefafaWin2.weconfirm(this,"警告","添加导航组件将导致页面内容<font color=red>全部重置</font>。<br>确定添加吗？",
							function(para){
								oneApp.addInterComponent(oneApp.cFuncid,para.aindex,para.direct,para.component_type);
								SaveApplicationConfig(oneApp);
							},
							{
								"component_type":component_type,
								"aindex":"1",
								"direct":"next"
							}
						);
						return;
					}
					else
					{
						oneApp.addInterComponent(oneApp.cFuncid,"1","next",component_type);
						SaveApplicationConfig(oneApp);
					}
					return;
				}
				else
				{
					//获取当前操作的nav item
					var $nav = $("#template_left .interface .cp_nav"), currNavItemIndex = $nav.find(".nav_li[check='1']").attr("order");
					if(currNavItemIndex=="") currNavItemIndex=0;
					var currItem=isNav.find("navitem:eq("+currNavItemIndex+")"),curritemtype=currItem.children("actiontype").text();
					if(currItem.length==0 || curritemtype!="TEMPLATE") return;
									
					var aindex = $nav.attr("aindex")+"-"+(currNavItemIndex*1+1)+"-1";
					oneApp.addInterComponent(oneApp.cFuncid,aindex,"next",component_type);
					SaveApplicationConfig(oneApp);					
					return;
				}
			}
			else if($class.indexOf("tabs_div")>-1)
			{
				//托放到tab页内时
				var $tabs = $target.parent(),aindex = $tabs.attr("aindex"),tabsitemindex = $tabs.find(".tab_selected").attr("tabsindex")*1+1;
				oneApp.addInterComponent(oneApp.cFuncid,aindex+"-"+tabsitemindex+"-1","next",component_type);//默认插入到目标组件下方
				$(document.body).css("cursor","default");
				SaveApplicationConfig(oneApp);
				return;
			}
			else if($class.indexOf("component")>-1)
			{
				//托放到某个组件上时
				var aindex = $target.attr("aindex");
				oneApp.addInterComponent(oneApp.cFuncid,aindex,"next",component_type);//默认插入到目标组件下方
				$(document.body).css("cursor","default");
				SaveApplicationConfig(oneApp);
				return;
			}			
		}
	});
};

