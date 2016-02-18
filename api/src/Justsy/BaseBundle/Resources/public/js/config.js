	function avatar_success()
	{
		$(".modal_savelog").hide();
		  $("#invitedphoto").modal("hide");		  
		  savePhoto();
	}
	function saveConfigInfo(e){
		if(typeof(resajax)!='undefined' && resajax!=null)return;
		var res_name=$(".step_1 #res_name").val();
		var fileid=$(".step_1 .res_logo_div").attr("fileid");
		var temp_type=$(".step_2 .curr_temp").attr('temp_type');
		var temp_id=$(".step_2 .curr_temp").attr('temp_id');
		var temp_name=$(".step_2 .curr_temp").text();
		var temp_url=$(".step_3 #temp_url").val();
		var appid = $("#apptitle").attr("appid");
		var res_id=$("#res_id").val();
		var res_parent=$("#res_parent").val();
		var error=false;
		if(res_name==''){
			$("#config_hint").text("请输入名称！");
			error=true;
		}
		else if(temp_id=='' || temp_type==''){
			$("#config_hint").text("请选择模板");
			error=true;
		}
		else if(temp_url==''){
			//$("#config_hint").text("请确定数据源接口！");
			//error=true;
		}
		else if(!(/((?:https?|mailto):\/\/.*?)(\s|&nbsp;|<br|\'|\"|：|，|。|！|$)/).test(temp_url))
		{
			$("#config_hint").text("接口地址不是有效的URL！");
			error=true;
		}
		if(error){
			setTimeout(function(){
				$("#config_hint").text("");
			},3000);
			return;
		}
		var params={
			appid:appid,
			res_id:res_id,
			res_name:res_name,
			res_type:temp_type,
			temp_id:temp_id,
			temp_url:temp_url,
			res_logo:fileid,
			res_parent:res_parent
		}
		$(e).text("处理中...");
		if(res_id==''){//新增
			resajax=$.post("{{path('JustsyMobilePlatformBundle_resource_add')}}",params,function(d){
				resajax=null;
				$(e).text("确定");
				if(d.s=='1'){
					html="<li res_id='"+d.res_id+"' class='res_list_li'><div class='res_list_div'><div class='res_item_name'>"+res_name+"</div><div class='res_item_logo'><img src='"+(fileurl+fileid)+"' onerror=\"this.src='/bundles/fafatimemobilplatform/images/menu_basic_default.png'\"/></div><div class='delimg'></div></div><div class='res_direct' style='display:none;'><img src=\""+del_icon+"\"/></div></li>";
					$(".res_div[res_parent='"+res_parent+"']").find('ul').append(html);
					if($(".res_div[res_parent='"+res_parent+"']").find("li").length==7){
						$(".res_div[res_parent='"+res_parent+"']").find(".res_nex_div,.res_prev_div").removeClass("res_div_hide");
					}
					params.res_id=d.res_id;
					resources.push({
						res_id:params.res_id,
						res_name:params.res_name,
						res_type:params.res_type,
						temp_id:params.temp_id,
						temp_url:params.temp_url,
						temp_name:temp_name,
						res_logo:params.res_logo,
						parent_res:params.res_parent
					});
					$("#resconfig").modal("hide");
				}
				else{
					$("#config_hint").text("添加失败！");
				}
			});
		}
		else{
			resajax=$.post("{{path('JustsyMobilePlatformBundle_resource_edit')}}",params,function(d){
				resajax=null;
				$(e).text("确定");
				if(d.s=='1'){
					$li=$("li.res_list_li[res_id='"+pres+"']");
					$li.find(".res_item_logo img").attr("src",fileurl+fileid);
					$li.find(".res_item_name").text(res_name);
					for(var i=0;i<resources.length;i++)
					{
						if(res_id==resources[i].res_id){
							resources[i].res_name=params.res_name,
							resources[i].res_type=params.res_type,
							resources[i].temp_id=params.temp_id,
							resources[i].temp_url=params.temp_url,
							resources[i].temp_name=temp_name,
							resources[i].res_logo=params.res_logo,
							resources[i].parent_res=params.res_parent
						}
					}
					$("#resconfig").modal("hide");
				}
				else{
					$("#config_hint").text("更改失败！");
				}
			});
		}
	}
	function delres(e){
		if(typeof(delresajax)!='undefined' && delresajax!=null)return;
		var res_id=$("#del_res").attr("res_id");
		delresajax=$.post("{{path('JustsyMobilePlatformBundle_resource_del')}}",{'res_id':res_id},function(d){
			delresajax=null;
			$(e).text("确定");
			if(d.s=='1'){
				$("#areyousure").modal("hide");
				for(var i=0;i<resources.length;i++)
				{
					if(res_id==resources[i].res_id){
						resources.splice(i,1);
						break;
					}
				}
				loadResTree($("#del_res").attr("res_parent"));
			}
			else{
				
			}
		});
		$(e).text("处理中...");
	}
	function savePhoto(){
		$("#loadlog").show();
		var res_id=$("#res_id").val();
		$.post(save_logo_url,{res_id:res_id},function(d){
			$("#loadlog").hide();
			if(d.s=='1'){
				$(".res_logo_div").find("img").attr("Src",fileurl+d.file);
				$(".res_logo_div").attr('fileid',d.file);
			}
			else{
				
			}
		});
	}
	function stopProp(evt) {
    evt.stopPropagation ? evt.stopPropagation() : (evt.cancelBubble = true);
    if (evt.preventDefault) evt.preventDefault();
	}
	function saveHead()
	{
		$(".modal_savelog").show();
	   uploadObj[0].doSave();
	}
	function showtemplate(e){
		var temptype=$(e).find("option:selected").val();
		$(".templatelist div[temptype]").hide();
		$(".templatelist div[temptype='"+temptype+"']").show();
	}
	function selecttitle(e){
		var classname=$(e).attr('class');
			  if(classname.indexOf('title_1')>-1){
			  	$(".config_steps .step_1").show().siblings().hide();
			  }
			  else if(classname.indexOf('title_2')>-1){
			  	$(".config_steps .step_2").show().siblings().hide();
			  }
			  else if(classname.indexOf('title_3')>-1){
			  	$(".config_steps .step_3").show().siblings().hide();
			  }
			  $(".title_1,.title_2,.title_3").removeClass("selectedtitle");
			  $(".title_1,.title_2,.title_3").removeClass("active");
			  $(e).addClass("selectedtitle");
			  $(e).addClass("active");
	}
	var bindResEvt=function(){
		$(document).on('click','.res_logo_div img',function(){
			$("#invitedphoto").modal("show");
		});
		$(document).on('click','.templatelist div[temptype]',function(){
			$(this).siblings().removeClass('selectedtemp');
			$(this).addClass('selectedtemp');
			$(".curr_temp").attr({'temp_id':$(this).attr('temp_id'),'temp_type':$(this).attr('temptype')});
			$(".curr_temp").text($(this).find(".template_name").text());
		});
		$(document).on('click','.title_1,.title_2,.title_3',function(){
			  var classname=$(this).attr('class');
			  if(classname.indexOf('title_1')>-1){
			  	$(".config_steps .step_1").show().siblings().hide();
			  }
			  else if(classname.indexOf('title_2')>-1){
			  	$(".config_steps .step_2").show().siblings().hide();
			  }
			  else if(classname.indexOf('title_3')>-1){
			  	$(".config_steps .step_3").show().siblings().hide();
			  }
			  $(".title_1,.title_2,.title_3").removeClass("selectedtitle");
			  $(".title_1,.title_2,.title_3").removeClass("active");
			  $(this).addClass("selectedtitle");
			  $(this).addClass("active");
		});
		$(document).on('click','.res_list_li',function(){
			if($(this).find(".res_list_div").attr('class').indexOf('selectedres')>-1){
					showConfigModal($(this).attr('res_id'));
			}
			else
				loadResTree($(this).attr('res_id'));
		});
		$(document).on('click','.res_nex_div',function(){
			$lis=$(this).siblings("ul.res_list").find("li.res_list_li");
			var n=0;
			var p=[];
			for(var i=0;i<$lis.length;i++)
			{
				if(n==7)break;
				if($($lis[i]).css('display').indexOf('none')>-1)continue;
				else{
					p.push($lis[i]);
					n++;
				}
			}
			if(n==7){
				for(var i=0;i<6;i++)
				{
					$(p[i]).hide();
				}
			}
		});
		$(document).on('click','.res_prev_div',function(){
			$lis=$(this).siblings("ul.res_list").find("li.res_list_li");
			var n=0;
			for(var i=($lis.length-1);i>=0;i--)
			{
				if(n==6)break;
				if($($lis[i]).css('display').indexOf('none')>-1){
					$($lis[i]).show();
					n++;
				}
			}
		});
		$(document).on('click','.res_add_div',function(){
			showConfigModal();
			$("#res_parent").val($(this).parent().attr("res_parent"));
		});
		$(document).on('mouseover','.res_list_div',function(event){
			if(checkHover(event,this))
			{
				$(this).find(".delimg").show();
			}
		});
		$(document).on('mouseout','.res_list_div',function(event){
			if(checkHover(event,this))
			{
				$(this).find(".delimg").hide();
			}
		});
		$(document).on('click','.delimg',function(event){
			var ev=event||window.event;
			var res_id=$(this).parent().parent().attr('res_id');
			var res_parent=$(this).parent().parent().attr('res_parent');
			$("#del_res").attr({'res_id':res_id,'res_parent':res_parent});
			$("#del_res").text($(this).siblings(".res_item_name").text());
			$("#areyousure").modal("show");
			stopProp(ev);
		});
	}
	var loadResTree=function(){
//		var pres=arguments[0]?arguments[0]:'';
//		delResTree(pres);
//		var items=[];
//		for(var i=0;i<resources.length;i++)
//		{
//			if(resources[i].parent_res==pres){
//				items.push(resources[i]);	
//			}
//		}
//		showResList(pres,items);
//		if(items.length>0){
//			loadResTree(items[0].res_id);
//		}
      var zNodes=[];
      var appid = $("#apptitle").attr("appid");
      zNodes.push({id:appid,pId:0,name:$("#apptitle").text(),open:true});
      var parentres = "";

      for(var i=0;i<resources.length;i++)
      {      	 
      	if ((resources[i].parent_res=="" ? "11" : resources[i].parent_res) != parentres)
      	{
      		 parentres = resources[i].parent_res==""? "11" : resources[i].parent_res;
      		 zNodes.push({id:parentres,pId:appid,name:"菜单项",open:true}); 
      	}
      	zNodes.push({id:resources[i].res_id,pId:parentres,name:resources[i].res_name});
      }
      $.fn.zTree.init($("#treeDemo"), setting, zNodes); 
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	var delResTree=function(pres){
		var $e=$("li.res_list_li[res_id='"+pres+"']").parent().parent();
		while($e.next(".res_div").length!=0)
		{
			$e.next(".res_div").remove();
		}
	}
	var showResList=function(pres,items){
		var html=[];
		html.push("<div class='res_div' res_parent='"+pres+"'>");
		html.push("<div class='res_nex_div res_div_hide'><div></div></div>");
		html.push("<ul class='res_list'>");
		for(var i=0;i<items.length;i++)
		{
			html.push("<li res_id='"+items[i].res_id+"' res_parent='"+items[i].parent_res+"' class='res_list_li'><div class='res_list_div'><div class='res_item_name'>"+items[i].res_name+"</div><div class='res_item_logo'><img src='"+(fileurl+items[i].res_logo)+"' onerror=\"this.src='/bundles/fafatimemobilplatform/images/menu_basic_default.png'\"/></div><div class='delimg'></div></div><div class='res_direct' style='display:none;'><img src=\""+del_icon+"\"/></div></li>");
		}
		html.push("</ul>");
		html.push("<div class='res_add_div'><img src=\""+addlogo+"\"/></div>");
		html.push("<div class='res_prev_div res_div_hide'><div></div></div>");
		html.push("</div>");
		$div=$(html.join(''));
		if(items.length>6){
			$div.find(".res_nex_div,.res_prev_div").removeClass("res_div_hide");
		}
		if(pres!=''){
			$li=$("li.res_list_li[res_id='"+pres+"']");
			$li.siblings().find(".res_list_div").removeClass('selectedres');
			$li.siblings().find(".res_direct").hide();
			$li.find(".res_list_div").addClass('selectedres');
			$li.find(".res_direct").show();
			$li.parent().parent().after($div);
		}
		else{
			$("div.app_head").after($div);
		}
	}
	var showConfigModal=function()
	{
		var res_id=arguments[0]?arguments[0]:'';
		var selected=null;
		for(var i=0;i<resources.length;i++)
		{
			if(resources[i].res_id==res_id){
				selected=resources[i];
				break;
			}
		}
		if(selected==null){
			$("#res_name").val('');
			$(".step_1 .res_logo_div").attr("fileid",'');
			$(".step_1 .res_logo_div img").attr('src','/bundles/fafatimemobilplatform/images/1_48.jpg');
			$(".step_2 .curr_temp").attr('temp_type','');
			$(".step_2 .curr_temp").attr('temp_id','');
			$(".step_2 .curr_temp").text('');
			$(".step_3 #temp_url").val('');
			$(".restypes option[value='module']").attr('selected','selected');
			showtemplate($(".restypes")[0]);
			$(".template_div").removeClass("selectedtemp");
			$("#res_id").val('');
		}
		else{
			$("#res_name").val(selected.res_name);
			$(".step_1 .res_logo_div").attr("fileid",selected.res_logo);
			$(".step_1 .res_logo_div img").attr('src',fileurl+selected.res_logo);
			$(".step_2 .curr_temp").attr('temp_type',selected.res_type);
			$(".step_2 .curr_temp").attr('temp_id',selected.temp_id);
			$(".step_2 .curr_temp").text(selected.temp_name);
			$(".step_3 #temp_url").val(selected.temp_url);
			$(".restypes option[value='"+selected.res_type+"']").attr('selected','selected');
			showtemplate($(".restypes")[0]);
			$(".template_div").removeClass("selectedtemp");
			$(".template_div[temp_id='"+selected.temp_id+"']").addClass("selectedtemp");
			$("#res_id").val(selected.res_id);
			$("#res_parent").val(selected.parent_res);
		}
		selecttitle($(".title_1")[0]);
		$("#resconfig").modal("show");
	}
	