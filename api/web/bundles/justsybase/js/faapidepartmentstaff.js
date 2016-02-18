var srcs = null;
if (document.currentScript == null) {
    var scripts = document.getElementsByTagName("script");
    var reg = /faapidepartmentstaff([.-]\d)*\.js(\W|$)/i;
    for (var i = 0, n = scripts.length; i < n; i++) {
        var src = !!document.querySelector ? scripts[i].src:
                    scripts[i].getAttribute("src", 4);
        if (src && reg.test(src)) {
            srcs = src.split("/");
            break;
        }
    }
}
else {
    srcs = document.currentScript.src.split("/");
}
var departmentstaff_domain = srcs[0] + "//" + srcs[2];

$(document).ready(function(){
   var cssNode = document.createElement("link");
   cssNode.rel = "stylesheet";
   cssNode.type="text/css";
   cssNode.media="screen";
   cssNode.href = departmentstaff_domain+"/bundles/fafatimewebase/css/departmentstaff.css?t="+new Date().getTime(); 
   document.getElementsByTagName("head")[0].appendChild(cssNode);	
});

var obj_title = "";

var fafa_department_staff = {
	staff_check:false,
	staff_info:[],
	department_info:[],
	
	//显示人员选择组件
	//parentControlId:传入的父控件id
	//isDouble:是否多选(值为true时为多选)
	//textbox_width:文本框长度
	show_staff_window:function(parentControlId,isDouble,textbox_width){
		 fafa_department_staff.staff_check = isDouble;
		 var html = "<div class='staff_body_area'>"+
		            " <div class='staff_search_area'>"+
		            "    <span style='position:relative;float:left;'>账号或姓名：</span><input type='text' class='staff_search_textbox' />"+
		            "   	<span id='drop_fafa_staff' class='fafa_common_down'></span>"+
		            "     <span class='staff_select_panel'></span>"+
		            " </div>"+
		            " <div class='staff_list_body_area'>"+
		            "   <div class='staff_list_left'><div class='staff_letter_div'></div></div>"+
		            " 	<div class='staff_list_area'>"+
		            "   	<div class='staff_search_loadding'>"+
		            "    	  <img src='/bundles/fafatimewebase/images/loading.gif' class='staff_image_loadding' />"+
		            "     </div>" + 
                " 	  <ul id='staff_list' class='staff_list_area ul'></ul>" + 
		            " </div> </div></div>";
		 $("#"+parentControlId).append(html);
		 wefafa_show_letter();
		 
		 if(isNaN(textbox_width))
		    textbox_width = textbox_width.replace("px","");
		 if(!isNaN(textbox_width) && textbox_width>0){
		 	 if(textbox_width<100)
		 	    textbox_width = 100;
		 	 else if(textbox_width>400)
		 	 	  textbox_width = 400;		 	 	  
		 	 var cz = $(".staff_search_textbox").width() - textbox_width;
		 	 //设置长度
		 	 $(".staff_search_textbox").css("width",textbox_width+"px");
		 	 $(".staff_body_area").css("width",($(".staff_body_area").width() - cz)+"px");
		 	 $(".staff_list_body_area").css("width",($(".staff_list_body_area").width() - cz)+"px");
		 	 $(".staff_select_panel").css("width",($(".staff_select_panel").width() - cz)+"px");
		 	 $(".staff_search_loadding").css("width",($(".staff_search_loadding").width() - cz)+"px");
		 	 $("#staff_list").css("width",($("#staff_list").width() - cz)+"px");
		 	 $(".staff_select_panel").css("margin-left",($(".staff_select_panel").css("margin-left").replace("px","") *1 + cz)+"px");
		 }
		 obj_title = $(".staff_select_panel");
		 return fafa_department_staff;
  },
  
  //没有搜索功能的人员选择组件
  show_staff_nohead_window:function(parentControlId,isDouble){
		 fafa_department_staff.staff_check = isDouble;
  	 var area_width = $(".department_list_body_area").width()+"px";
		 var html = " <div class='staff_list_body_area' style='width:"+area_width+";display:block;margin-top:6px;'>"+
		            "   <div class='staff_list_left'><div class='staff_letter_div'></div></div>"+
		            " 	<div class='staff_list_area'>"+
		            "   	<div class='staff_search_loadding'>"+
		            "    	  <img src='/bundles/fafatimewebase/images/loading.gif' class='staff_image_loadding' />"+
		            "     </div>" + 
                " 	  <ul id='staff_list'></ul>" + 
		            " </div> </div>";
		 $("#"+parentControlId).append(html);
		 wefafa_show_letter();
  },
  
  show_department_window:function(parentControlId,filedsName,textbox_width){
  	var fileds ="";
  	if(filedsName=="" || filedsName==null || filedsName.length>4)
  	  fileds = "部门名称：";
  	else
  		fileds = filedsName+"：";  	
    var html = "<div class='department_body_area'> "+
               "	 <div class='staff_search_area'> <span style='position:relative;float:left;'>"+fileds+"</span> <input type='text' class='department_search_textbox'> "+
               "   <span class='fafa_common_down' id='drop_fafa_department'></span> <span class='department_select_panel'></span> "+
               "</div>"+
               "<div class='department_list_body_area'>"+
               "  <div class='department_loading'> <img class='staff_image_loadding' src='/bundles/fafatimewebase/images/loading.gif'>  </div>"+
               "  <ul  id='tree_org' style='border:0px;background:none repeat scroll 0 0 transparent;padding-left:2px;padding-top:2px;' class='ztree'></ul>"+
               "</div>"+
               "</div>";
     $("#"+parentControlId).append(html);          
     if(isNaN(textbox_width))
		    textbox_width = textbox_width.replace("px","");
		 if(!isNaN(textbox_width) && textbox_width>0){
		 	 if(textbox_width<100)
		 	    textbox_width = 100;
		 	 else if(textbox_width>200)
		 	 	  textbox_width = 200;		 	 	  
		 	 var cz = $(".department_search_textbox").width() - textbox_width;
		 	 //设置长度
		 	 $(".department_search_textbox").css("width",textbox_width+"px");
		 	 $(".department_body_area").css("width",($(".department_body_area").width() - cz)+"px");
		 	 $(".department_list_body_area").css("width",($(".department_list_body_area").width() - cz)+"px");
		 	 $(".department_select_panel").css("width",($(".department_select_panel").width() - cz)+"px");
		 	 $(".department_select_panel").css("margin-left",($(".department_select_panel").css("margin-left").replace("px","") *1 + cz)+"px");
		 }
		 obj_title = $(".department_select_panel");
		 return fafa_department_staff; 
  },
  
  //没有搜索功能的部门选择组件
  show_department_nohead_window:function(parentControlId,d_value){
  	var area_width = "245px";
  	if(d_value !=0)
  	  area_width = (245 - d_value)+"px";
    var html = "<div class='department_list_body_area' style='width:"+area_width+";display:block;margin-top:6px;'>"+
               "  <div class='department_loading'> <img class='staff_image_loadding' src='/bundles/fafatimewebase/images/loading.gif'>  </div>"+
               "  <ul  id='tree_org' style='border:0px;background:none repeat scroll 0 0 transparent;padding-left:2px;padding-top:2px;' class='ztree'></ul>"+
               "</div>";
     $("#"+parentControlId).append(html);
     loadding_department("tree_org");
  },
  
  
  show_group_window:function(parentid,field_description,textbox_width){
  	if(field_description==null || field_description=="" ||  field_description.length>4)
  	   field_description ="用户账号：";
  	else
  		field_description+="：";  	
  	var html=	"<div class='group_window' style='display:block;'>"+
              "  <div style='height:25px;line-height:28px;position:relative;float:left;'>"+
				 	    "    <span id='fileds_description' style='margin-left:8px;position:relative;float:left;'>"+field_description+"</span>"+
				 	    "    <input id='group_search_text' type='text' class='staff_search_textbox' />"+
				 	    "    <span id='dropbox_group' class='fafa_common_down'></span>"+
				 	    "    <span class='group_select_panel' style='display:none;'></span>"+
				      " </div>"+
				      " <div id='group_body_area' style='display:none;'>"+
				      "   <div class='group_title'>"+
					    "     <span id='group_department' class='group_select_title' style='margin-left:8px;'> 组织部门 </span>"+
					 	  "     <span id='group_staff' class='group_noselect_title' style='margin-left:5px;'> 员工信息 </span>"+
					    "   </div>"+
					    "   <div id='group_department_area' style='margin-top:6px;'></div>"+
					    "   <div id='group_staff_area'></div>"+
	            " </div>"+
			        "</div>";			
  	$("#"+parentid).append(html);
  	//样式处理
  	var D_value=0;
  	if(isNaN(textbox_width))
		    textbox_width = textbox_width.replace("px","");
		if(!isNaN(textbox_width) && textbox_width>0){
		 	if(textbox_width<=100)
		 	    textbox_width = 100;
		 	 else if(textbox_width>=200)
		 	 	  textbox_width = 200;
		 	 D_value = $("#group_search_text").width() - textbox_width;
		 	 //设置长度
		 	 $("#group_search_text").css("width",textbox_width+"px");
		 	 $(".group_window").css("width",($(".group_window").width() - D_value)+"px");
		 	 $(".group_select_panel").css("width",($(".group_select_panel").width() - D_value)+"px");
		}
  	fafa_department_staff.show_department_nohead_window("group_department_area",D_value);
  	obj_title = $(".group_select_panel");
  	return fafa_department_staff;
  },
  
  getstaff_info:function(){
  	return fafa_department_staff.staff_info;
  },
  getdepart_info:function(){
  	return fafa_department_staff.department_info;
  }
};

  //显示字母
function wefafa_show_letter(){
	var letter="",html="";
  $(".staff_letter_div").append("<span class='staff_letter_style' style='width:100%' id='letter_all'>ALL</span><BR/>");
  for(var i=65;i<91;i++){
  	letter = String.fromCharCode(i);
  	html = "<span class='staff_letter_style' id='letter_"+letter+"'>"+letter+"</span>";
  	$(".staff_letter_div").append(html);
  }
};

//按字母检索人员
$(".staff_letter_style").live("click",function(){
	 var letter = $(this).text();
	 $(".staff_letter_style").css("color","#0088cc");
	 this.style.color="#ff6600"
   var url = departmentstaff_domain + "/interface/baseinfo/getenostaff";
   var parameter = {"nick":"","letter":letter};
   $("#staff_list").hide();
   $(".staff_search_loadding,.staff_image_loadding").show();
   $.getJSON(url,parameter,function(result){
   	  var data = result.rows;
   	  $("#staff_list").html("");
   	  var html = "";
   	  if(data.length==0){
   	  	$(".staff_image_loadding").hide();
   	  }
   	  else{
   	  	$("#staff_list").show();
   	  	$(".staff_search_loadding").hide();
        if(fafa_department_staff.staff_check){
        	var select_obj = obj_title.children();
	   	    for(var i=0;i<data.length;i++){
	   	    	if(select_obj.length==0){
	   	    	  html += "<li class='wefafa_staff' jid='"+data[i].login_account+"'><input type='checkbox' style='margin-top:5px;*margin-top:2px;' />"+
	   	    	          "<span style='position:absolute;margin-left:2px;'>"+ data[i].name+"</span>"+
	   	    	          "</li>";
	   	    	}
	   	    	else{
	   	    		var jid = data[i].login_account.replace(/@|\./g,'')
	   	    		var flag = $("span #wefafa_"+jid).length>0?true:false;
	   	    	  html += "<li class='wefafa_staff' jid='"+data[i].login_account+"'>"+
	   	    	          "  <input type='checkbox' style='margin-top:5px;' " + (flag?"checked='true'":"") + " />"+
	   	    	          "  <span style='position:absolute;margin-left:2px;'>"+ data[i].name+"</span>"+
	   	    	          "</li>";
	   	    		 
	   	    	}
	   	    }
   	    }
   	    else{
   	    	for(var i=0;i<data.length;i++){
	   	    	html += "<li class='wefafa_staff' jid='"+data[i].login_account+"'>"+data[i].name+"</li>";
	   	    }
   	    }
   	    $("#staff_list").append(html);
   	  }
   });
});

//添加数组数据
function array_innsert(arrayName,id,name,obj){
	arrayName.push({"id":id,"name":name});
	id = id.replace(/@|\./g,"");
	html = "<span id='wefafa_"+id+"'>" + 
	      (obj.children().length==0 ? name : "，"+name) + "</span>";
	obj.append(html);
	obj.attr("title",obj.text());
  obj.show();
}

//删除数组数据
function array_delete(arrayName,id,obj){
	for(var i=0;i<arrayName.length;i++){
		if(id==arrayName[i].id){
			arrayName.splice(i,1);
			id = id.replace(/@|\./g,"");
			obj.find("#wefafa_"+id).remove(); //在父控件移除元素
			if(obj.children().length==0)
			  obj.hide();
			else{
				var temp = $(obj.children()[0]).text();
				if (temp.substring(0,1)=="，")
            $(obj.children()[0]).text(temp.substring(1));
			}
			obj.attr("title",obj.text());
			break;
		}
	}
}

//选择用户
$(".wefafa_staff").live("click",function(){
	//允许多选
  var jid = this.getAttribute("jid");
  var nick = $(this).text().replace(/\s/g,'');
	var f_jid = "wefafa_" + jid.replace(/@|\./g,"");	 
	var html = "";
	if(fafa_department_staff.staff_check){
	  //添加至面板
		if(obj_title.find("#"+f_jid).length==0){
			array_innsert(fafa_department_staff.staff_info,jid,nick,obj_title);
			$(this).find("input")[0].checked=true;
		}
		else{
			$(this).find("input")[0].checked=false;
			array_delete(fafa_department_staff.staff_info,jid,obj_title); 
		}
	}
	else{  //单选择人员性息
		fafa_department_staff.staff_info=[];
		obj_title.html("");
		if(obj_title.find("#"+f_jid).length==0){
			array_innsert(fafa_department_staff.staff_info,jid,nick,obj_title);
		}
		else{
			array_delete(fafa_department_staff.staff_info,jid,obj_title);
			$(".staff_search_textbox").focus();
		}
		$(".staff_list_body_area").hide();
	}
});

//$(".staff_list_body_area").live("mouseout",function(){
//	//$(".staff_list_body_area").hide();
//});
//
//$("#group_body_area").live("mouseout",function(){
//	 $("#group_body_area").hide();
//});


//下拉列表事件
$("#drop_fafa_staff").live("click",function(){
	$(".staff_list_body_area").toggle();
});

//部门下拉列表事件
$("#drop_fafa_department").live("click",function(){
	var depart_control = $(".department_list_body_area");
	depart_control.toggle();
	if($("#tree_org").children().length==0){ //获得组织机构
    loadding_department("tree_org");
	}
});

//加载部门树
function loadding_department(tree_id){
	$(".department_loading").show();		
  $.getScript(departmentstaff_domain + "/bundles/fafatimewebase/js/jquery.ztree.core-3.4.min.js");
  
	var url = departmentstaff_domain + "/interface/baseinfo/getenotree";
	$.getJSON(url,function(list){
		  var zNodes = new Array();
	    for (var i = 0; i < list.length; i++) {
	       var info = list[i];
	       if (info.pId == 0)
	         zNodes.push({ id: info.id, pId: info.pId, name: info.name, open: true,icon:departmentstaff_domain+"/bundles/fafatimewebase/images/org_root.png" });
	       else
	         zNodes.push({ id: info.id, pId: info.pId, name: info.name, open:true, icon:departmentstaff_domain+ "/bundles/fafatimewebase/images/tree_node_close.png",iconOpen:departmentstaff_domain+"/bundles/fafatimewebase/images/tree_node_open.png",iconClose:departmentstaff_domain+"/bundles/fafatimewebase/images/tree_node_close.png" });
	    }
	    var setting = {check:{enable:true,chkStyle:"checkbox",chkboxType:{"Y":"","N":"" }},data:{ simpleData: { enable: true} },
	       callback:
	       {
	      	 beforeCheck:before_Check,
	         onClick: function (event, treeId, treeNode) {
	         	  $.fn.zTree.getZTreeObj(treeId).expandNode(treeNode);
	         }
	       }
	   }
	   $.getScript(departmentstaff_domain + "/bundles/fafatimewebase/js/jquery.ztree.excheck-3.4.min.js",function(data){
	   	 //加载完js文件绑定组织机构树
	   	 $(".department_loading").hide();
	   	 $.fn.zTree.init($("#"+tree_id), setting, zNodes);
	   });
  });
};

function remove_depart(){
	var id="";
	for(var i=0;i<fafa_department_staff.department_info.length;i++){
		 id = "wefafa_"+fafa_department_staff.department_info[i].id;
		 if(obj_title.find("#"+id).length>0)
		   obj_title.find("#"+id).remove();
	}
	if(obj_title.children().length==0)
    obj_title.hide();
  else{
	  var temp = $(obj_title.children()[0]).text();
		if (temp.substring(0,1)=="，")
      $(obj_title.children()[0]).text(temp.substring(1));
	}
	obj_title.attr("title",obj_title.text());
}

//选择用户
function before_Check(treeId,treeNode){
	 var check_status = !treeNode.checked;
	 var treeObj = $.fn.zTree.getZTreeObj(treeId);
   if(treeNode.level==0 ){
   	  remove_depart();
   	  fafa_department_staff.department_info=[];
      if(check_status){
   	    treeObj.checkAllNodes(false);   	    
   	    array_innsert(fafa_department_staff.department_info,treeNode.id,treeNode.name,obj_title);
   	  }
   	  else{
   	  	array_delete(fafa_department_staff.department_info,treeNode.id,obj_title);
   	  }
   	  return;
   }
   //取消上级选择状态
   var node_parent = treeNode.getParentNode();
   while (node_parent.level>-1){
 	   if(node_parent.checked){
 	   	 node_parent.checked = false;
 	   	 array_delete(fafa_department_staff.department_info,node_parent.id,obj_title);
 	   }
 	   if(node_parent.level==0)  break;
 	   node_parent = node_parent.getParentNode();
   }
   //对本次节点处理   	   
   if(treeNode.isParent){
   	 if(check_status){
   	   array_innsert(fafa_department_staff.department_info,treeNode.id,treeNode.name,obj_title);
     	 var tree_children = treeNode.children;
     	 var len = tree_children.length;
     	 for(var j=0;j<len;j++){
     	 	 if(tree_children[j].checked){
     	 	 	 tree_children[j].checked=false;
     	 	 	 array_delete(fafa_department_staff.department_info,tree_children[j].id,obj_title);
     	 	 }
     	 }
     }
     else{
     	 array_delete(fafa_department_staff.department_info,treeNode.id,obj_title);
     }
   }
   else	{
   	 if(check_status)
   	   array_innsert(fafa_department_staff.department_info,treeNode.id,treeNode.name,obj_title);
     else
     	 array_delete(fafa_department_staff.department_info,treeNode.id,obj_title);
   }
   treeObj.refresh();
};

//---------------------------------------------------带组织部门的人员选择------------------------------------------------

//选择部门标签
$("#group_department").live("click",function(){
	 if($("#group_department").attr("class")=="group_noselect_title"){
	 	 $("#group_department_area").show();	 	   
	 	 $("#group_department").attr("class","group_select_title");	 	 
	 	 $("#group_staff").attr("class","group_noselect_title"); 	 
	 	 $("#group_staff_area").hide();
	 	 $(".group_department_area").show();
	 }
});

//选择人员标签
$("#group_staff").live("click",function(){
	 if($("#group_staff").attr("class")=="group_noselect_title"){
	 	 if($(".staff_letter_div").children().length==0){
	 	   fafa_department_staff.show_staff_nohead_window("group_staff_area",true);
	 	   var d_value = 252-($(".staff_list_body_area").width());
		 	 $(".staff_list_area").css("width",($(".staff_list_area").width() - d_value)+"px");
		 	 $("#staff_list").css("width",($("#staff_list").width() - d_value)+"px");
		 	 $(".staff_search_loadding").css("width",$("#staff_list").width()+"px");
	 	 }
	 	 $("#group_staff").attr("class","group_select_title");
	 	 $("#group_department").attr("class","group_noselect_title");
	 	 $("#group_department_area").hide();
	 	 $("#group_staff_area").show();	 	 
	 }
});

$("#dropbox_group").live("click",function(){
	 $("#group_body_area").toggle();
});