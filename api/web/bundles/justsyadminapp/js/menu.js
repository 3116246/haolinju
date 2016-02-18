var menu = {
	search_url:"",
	getmenu_url:"",
	save_url:"",
	cleare_url:"",
	issearch:false,
	record:14,
	search_state:true,
	login_account:"",
  pageselectCallback:function(pageindex){
		 if (menu.issearch )
		   menu.Search(pageindex+1);
	},
	pageInit:function(){
 	  var opt = {callback: menu.pageselectCallback};
    opt.items_per_page = menu.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex){
		if ( !this.search_state) return;
     this.search_state = false;
     var parameter = { "staff":$.trim($("#text_staff").val()),"pageindex":pageindex,"record":this.record };
	 	 $.post(this.search_url,parameter,function(returndata){
	 	 	  menu.search_state = true;
	 	 	  if (returndata.success){
		 	 	   if ( pageindex==1 ){
			 	 	  	if ( returndata.recordcount <= menu.record){
			 	 	  		$(".pagestyle").hide();
			 	 	  	}
			 	 	  	else{
				 	 	  	menu.issearch = false;
				 	 	  	var optInit = menu.pageInit();
				 	 	  	$(".pagestyle").show();
				 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
				 	 	  	menu.issearch = true;
			 	 	    }
		 	 	   }
		 	 	   else{
		 	 	   	menu.issearch = true;
		 	 	   }
		 	 	   menu.fulldata(returndata.datasource);
	 	 	  }
	 	 	  else{
	 	 	  }
	 	 });
	},
	fulldata:function(data){		 
	   var html = new Array();
  	 if ( data != null && data.length>0){
 	 	  	var row = null;
	 	 	  for(var i=0;i<data.length;i++){
	 	 	  	row = data[i];
	 	 	  	html.push("<tr>");
	 	 	  	var login_account = row.login_account;
	 	 	  	if ( login_account.length>28)
	 	 	  	    html.push(" <td width='265' title='"+login_account+"' align='left'>"+login_account+"</td>");
	 	 	  	else
	 	 	  	    html.push(" <td width='265' align='left'>"+row.login_account+"</td>");
  		    html.push(" <td width='232' align='left'>"+row.staff+"</td>");
  		    html.push(" <td width='102' align='center'><span style='margin-left:35px;margin-top:7px;' staff='"+row.staff + "' login_account='"+row.login_account+"' onclick='menu.loadTree(this)' title='设置用户菜单权限' class='mb_role_button'></span></td>");
  		    html.push("</tr>");
 	 	   }
		 }
	  else {
	  	html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		}
		$(".mb_common_table tbody").html(html.join(""));
	},
	loadTree:function(ev){
		$(".area_right").show();
		$(".area_right .menu_staff").text($(ev).attr("staff"));
		this.login_account = $(ev).attr("login_account");
		$(".menu_bottom").hide();
		$.getJSON(this.getmenu_url,{"login_account":this.login_account},function(data) {
			  if (data.success){
			    var zTreeSetting = {
			    	check:{
			    		enable:true
			    	},
			    	data:{
			    		simpleData:{
			    			enable:true
			    		}
			    	}
			    };
				  $.fn.zTree.init($("#tree_menu"), zTreeSetting, data.menus);
				  if ( data.exists)
				    $("#cleare_role").show();
				  else
				    $("#cleare_role").hide();
				  $(".menu_bottom").show();
			  }
	  });
	},
	Save:function(){
	  var menuid = $.map($.fn.zTree.getZTreeObj("tree_menu").getCheckedNodes(true),
  	function(item, index) {
  	  return item.id;
    });
    $.post(this.save_url,{"login_account":this.login_account,"menuid":menuid},function(data){
    	 if (data.success){
    	 	 showDialog.Success("提示！","保存用户菜单权限成功！");
			 	 showDialog.callback = null;
    	 }
    	 else{
    	 	 showDialog.Error("提示！","保存用户菜单权限失败！");	 
    	 }    	 
    });  
	},
	cleareRole:function(){
		$.post(this.cleare_url,{"login_account":this.login_account},function(data){
			 if ( data.success){
			 	 showDialog.Success("提示！","清除用户【"+$(".menu_staff").text()+"】菜单权限成功！");
			 	 showDialog.callback = function(res){
	 	 	 	 	 if (res=="Yes"){
	 	 	 	 	 	 $(".area_right").hide();
	 	 	 	 	 }
	  	 	 };
			 }
			 else{
			 	 showDialog.Error("提示！","清除用户【"+$(".menu_staff").text()+"】菜单权限失败！");
			 }
		});
	}
};

