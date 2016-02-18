var group =
{
	identical_url:"",
	edit_url:"",
	groupid:"",
	record:8,
	issearch:false,
	role:"",
	group_default_logo:"/bundles/fafatimewebase/images/default_group.png",
	loading_icon:"/bundles/fafatimewebase/images/loading.gif",
	basice:{},
	allow_deptid:Array(),
	allow_jid:Array(),
	remove_jid:Array(),
	groupinfo:Array(),
	exists:false,
	create_goup:function(type){
		group.exists = false;
		$('#viewCreate').show();
		$(".group_hint_area").hide();
		$(".group_hint").text("");
		$("#group_name,#max_val,#group_desc").val("");
		$("#group_name").attr("groupname","");
		
		$("#group_logo").attr("src",this.group_default_logo);
		$("#group_logo").attr("fileid","");
				
		$("#selected_department").text("");
		$("#selected_staff").text("");
		$("#selected_remove").text("");
		
		$(".group_set_member,.group_set_maxnumber").hide();
		$(".group_set_info").show();
		
		if ( type=="add"){
		    $("#group_title").text("创建默认群组");
		    $(".group_menu_bar").hide();
		    $("#selected_staff,#selected_remove").css("height","70px");
			  this.groupid = "";
			  $("#max_val").val($("#max_val").attr("max_member"));
			  $("#add_dept").show();
		}
		else if ( type=="edit"){
		  $("#selected_staff,#selected_remove").css("height","55px");
		  var parameter = { "module":"group","action":"getGroupInfo","params":{"groupid":this.groupid} };
		  $.post(this.identical_url,parameter,function(returndata){
		  	if (returndata.success)
		  	{
		  	    group.allow_deptid = Array();
		  	    group.allow_jid = Array();
		  	    group.remove_jid = Array();
		  		  //基本信息
		  		  var basic = returndata.basic;
            group.basice = basic;
            $("#group_name").val(basic.groupname);
            $("#group_name").attr("groupname",basic.groupname);
            $("#group_desc").val(basic.groupdesc);
            $("#max_val").val(basic.max_number);
            if ( basic.url!="")
            {
                $("#group_logo").attr("src",basic.url);
                $("#group_logo").attr("fileid",basic.logo);
            }
            else
            {
                $("#group_logo").attr("src",group.group_default_logo);
                $("#group_logo").attr("fileid","");
            }
            //默认群组人员范围（部门、允许人员、排除人员）
            var member_area = returndata.member_area;	  		
            var html_dept=Array(),html_allow = Array(),html_remove = Array();
            var row = null;
            for(var i=0;i < member_area.length;i++)
            {
                 row = member_area[i];
                 if ( row.status=="1")
                 {
                     group.allow_deptid.push(row.objid);
                 	 	 html_dept.push("<span deptid='"+row.objid+"' class='group_label_area' ><span style='cursor:default;'>"+row.objname+"</span></span>");
                 }
                 else if ( row.status=="2")
                 {
                    group.allow_jid.push(row.objid);
                 	  html_allow.push("<span fafa_jid='"+ row.objid +"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+ row.objname +"</span><i class='delete_lable_empty' title='移除人员' onclick='removeItem(this);'></i></span>");
                 }
                 else if ( row.status=="3")
                 {
                    group.remove_jid.push(row.objid);
                 	  html_remove.push("<span fafa_jid='"+ row.objid +"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+ row.objname +"</span><i class='delete_lable_empty' title='移除人员' onclick='removeItem(this);'></i></span>");
                 }
            }
            //部门部分
            if ( html_dept.length>0)
                $("#selected_department").html(html_dept.join(""));
            else
                $("#selected_department").html("");
            //允许加入人员部分
            if ( html_allow.length>0)
                $("#selected_staff").html(html_allow.join(""));
            else
                $("#selected_staff").html("");
            //排除人员部分
            if ( html_remove.length>0)
                $("#selected_remove").html(html_remove.join(""));
            else
                $("#selected_remove").html("");
		  	}
		  });
		}
	},
	check_group:function(val)
	{
		var gname = $("#group_name").attr("groupname");
		var groupname = $.trim($("#group_name").val());		
		if ( groupname=="" || (gname!="" && gname==groupname))
		{
		    $(".group_hint_area").hide();
		    return;
	  }
		var para = { "groupid":this.groupid,"groupname":$.trim($("#group_name").val()) };
		var parameter = { "module":"group","action":"checkGroupName","params":para };
		$(".group_hint_area").show();
		$(".group_hint_area>img").show();
		$("#check_icon").hide();
		$("#check_msg").text("正在检查");
		$.post(this.identical_url,parameter,function(returndata){
			$(".group_hint_area>img").hide();
			$("#check_icon").show();
			$("#group_name").attr("groupname",groupname);
			if ( returndata.success){
				group.exists = returndata.exists;
				if ( returndata.exists)
				{
					$("#check_icon").attr("class","group_error_icon");
					$("#check_msg").text("群组已存在");
				}
				else
				{
					$("#check_icon").attr("class","group_success_icon");
				  $("#check_msg").text("");
				}
		  }
		  else{
		  	$("#check_icon").attr("class","group_error_icon");
				$("#check_msg").text("发生错误");
		  }
	  });
	},
	window_close:function()
	{
		$('.selected_pic_box').modal('hide');
		$("#uplod_loading").hide();
   	$(".upload_hint").text("");
	},
	selectedImg:function(evn){
		$(".selected_pic_box").modal("show");
	},
	uploadfile:function(evn)
	{
		 $("#uplod_loading").attr("src","/bundles/fafatimewebase/images/loading.gif");
   	 $("#uplod_loading").show();
   	 $(".upload_hint").text("正在上传应用Logo，请稍候……");
     uploadObj[0].doSave();
	},		
	pageselectCallback:function(pageindex){
		 if (group.issearch )
		   group.Search(pageindex + 1);
	},
	pageInit:function(){
 	  var opt = {callback: group.pageselectCallback};
    opt.items_per_page = group.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex)
	{
		var para = { "pageindex":pageindex,
			           "record":this.record,
			           "groupname":$.trim($("#textgroupname").val())
			         };
		var parameter = { "module":"group","action":"searchDefaultGroup","params":para };
		//动画
		var html = Array();
		html.push("<div class='group_search_loading'><div>");
		html.push(" <img src='"+group.loading_icon+"' />");
		html.push(" <span>正在查询群组数据，请稍候……</span>");
		html.push("</div></div>");
		$("#search_page").hide();
		$(".group_table>tbody").html(html.join(""));
		$.post(this.identical_url,parameter,function(returndata){
			if ( returndata.success){
				
			  if ( pageindex==1 ){
		 	 	  	if ( returndata.recordcount <= group.record){
		 	 	  		$("#search_page").hide();
		 	 	  	}
		 	 	  	else{
			 	 	  	group.issearch = false;
			 	 	  	var optInit = group.pageInit();
			 	 	  	$("#search_page").show();
			 	 	  	$("#search_page").pagination(returndata.recordcount,optInit);
			 	 	  	group.issearch = true;
		 	 	    }
	 	 	   }
	 	 	   else{
	 	 	   	 $("#search_page").show();
	 	 	   	 group.issearch = true;
	 	 	   }
	 	 	   group.fulldata(returndata.list);
			}
			else{				
			}
		});
	},
	fulldata:function(data){
		var html = Array();
		var row = null;
		var imgurl = "";
		if ( data.length==0){
		  html.push("<span class='mb_common_table_empty' style='border-bottom:1px solid #ccc;height:32px;line-height:30px;'>未查询到群组数据记录！</span>");
		}
		else{
			for(var i=0;i<data.length;i++)
			{
				row = data[i];
				imgurl = row.logo;
				var role = row.role;
				if ( imgurl=="")
				  imgurl = "/bundles/fafatimewebase/images/default_group.png";
				html.push("<tr groupid='"+row.groupid+"' role='"+role+"' class='group_tr'>");
				html.push("<td width='220'><div class='group_name'><img class='group_img' src='"+imgurl+"' /><span>"+row.groupname+"</span></td>");
				html.push("<td width='90' align='center'>"+(row.group_type=="1"?"默认群组":"群聊群组")+"</td>");
				html.push("<td width='85' align='center'>"+row.number+"</td>");
				html.push("<td width='85' calss='max_number' align='center'>"+row.max_number+"</td>");
				html.push("<td width='105' align='center'>"+row.last_date+"</td>");				
				var staff = "<span style='color:#0088cc;'>"+row.creator+"</span>";
				var manager = row.manager;
				if ( manager != "")
				   staff += "<span style='color:#cc3300;margin-left:5px;'>"+manager+"</span>";
				html.push("<td width='302' align='left'><div class='group_admin'>"+staff+"</div></td>");
				html.push("<td width='112' align='center'>");
				if ( role=="")  //没有管理权限
				{
					html.push("  <i class='glyphicon glyphicon-cog glyphicon_group_enable'  title='群组设置'></i>");
					html.push("  <i class='glyphicon glyphicon-trash  glyphicon_group_enable' title='解散群组'></i>");
			  }
			  else{
			  	if ( row.group_type=="1")
						html.push("  <i class='glyphicon glyphicon-cog glyphicon_group' status='1' group_type='1' onclick='group.editgroup(this);' title='群组设置'></i>");
				  else
				  	html.push("  <i class='glyphicon glyphicon-cog glyphicon_group' status='1' group_type='0' onclick='group.editgroup(this);' title='群组设置'></i>");
					
					html.push("  <i class='glyphicon glyphicon-trash  glyphicon_group' status='3' onclick='group.editgroup(this);' title='解散群组'></i>");
			  }
				html.push("</td></tr>");
			}
		}
		$(".group_table>tbody").html(html.join(""));
	},
	Save:function(evn){
		if ( group.exists)
		{
			this.hint("已存在群组名称,请重新输入！");
			$("#group_name").focus();
			return;
		}
		var parameter = this.getVal();
		if ( parameter == false ){
			 return;
		}
	  parameter.groupid = this.groupid;
	  //修改时的处理	  
	  if (this.groupid != "")
	  {
        //基本信息的比较
        var basic = group.basice;
        var isedit = true;
        if ( basic.groupname==parameter.group_name && basic.max_number==parameter.max_number && basic.logo==parameter.group_logo && basic.groupdesc == parameter.group_desc )
        {
          isedit = false;
        }
        //部门的处理
        var new_deptid = Array();
        var old_groupid = group.allow_deptid;
        //新加的部门
        var allow_deptid = parameter.deptid;
        for(var i=0;i < allow_deptid.length;i++)
        {
            var deptid = allow_deptid[i];
            if ( !group.contain(old_groupid,deptid))
               new_deptid.push(deptid);
        }
        parameter.allow_deptid = new_deptid;
        //删除的部门id
        var remove_deptid = Array();
        for(var i=0;i< old_groupid.length;i++)
        {
            var deptid = old_groupid[i];
            if ( !group.contain(allow_deptid,deptid))
               remove_deptid.push(deptid);          
        }
        parameter.remove_deptid = remove_deptid;
        var new_jid = Array();
        var jids = "";
        var allow_jid = parameter.allow_jid;
        var jid = "";
        //允许加入人员的处理
        if ( allow_jid.length >0 &&  group.allow_jid.length>0 )
        {
          //添加的人员
          jids = group.allow_jid.join("");
          for(var i=0;i<allow_jid.length;i++)
          {
             jid = allow_jid[i];
             if (jids.length==0)
               new_jid.push(jid);
             else if ( jids.indexOf(jid)==-1)
             {
                new_jid.push(jid);             
             }
          }
          parameter.allow_jid = new_jid;
          //删除的人员
          new_jid = Array();
          jids = allow_jid.join("");
          for(var i=0;i<group.allow_jid.length;i++)
          {
             jid = group.allow_jid[i];
             if (jids.length==0)
               new_jid.push(jid);
             else if ( jids.indexOf(jid)==-1)
             {
                new_jid.push(jid);             
             }	   	                
          }
          parameter.allow_del= new_jid;         
        }
        else if (group.allow_jid.length > 0 && allow_jid.length==0)
        {
            parameter.allow_del = group.allow_jid;
        }
        else
        {
            parameter.allow_del = Array();
        }
        //排除人员的处理
        new_jid = [];
        var remove_jid = parameter.remove_jid;
        if ( remove_jid.length >0 &&  group.remove_jid.length>0 )
        {
          //添加的人员
          jids = group.remove_jid.join("");
          for(var i=0;i<remove_jid.length;i++)
          {
             jid = remove_jid[i];
             if (group.remove_jid.length == 0)
               new_jid.push(jid);
             else if ( jids.indexOf(jid)==-1)
               new_jid.push(jid);
          }
          parameter.remove_jid = new_jid;
          //删除的人员
          new_jid = [];
          jids = remove_jid.join("");
          for(var i=0;i<group.remove_jid.length;i++)
          {
             jid = group.remove_jid[i];
             if (remove_jid.length==0)
               new_jid.push(jid);
             else if ( jids.indexOf(jid)==-1)
               new_jid.push(jid);
          }
          parameter.remove_del= new_jid;
        }
        else if ( group.remove_jid.length > 0 && remove_jid.length==0)
        {
           parameter.remove_del = group.remove_jid;
        }
        else 
        {
            parameter.remove_del = Array();
        }
        
        if ( !isedit && parameter.allow_deptid.length==0 && parameter.remove_deptid.length==0 && parameter.allow_jid.length==0 && parameter.allow_del.length==0 && parameter.remove_jid.length==0 && parameter.remove_del.length==0 )
        {
            this.hint("未对群组作任何修改！");
            return;
        }
	  }
	  var btnsave = $(evn);
	  btnsave.attr("disabled","disabled");
		$.post(this.edit_url,parameter,function(data){
			  if ( data.success){
				  var groupid = data.groupid;
				  $('#viewCreate').hide();
				  group.Search(1);
			  }
			  btnsave.removeAttr("disabled");
		});
	},
	contain:function(array,obj)
	{
	    for(var i=0;i< array.length;i++)
	    {
	        if ( array[i]== obj)
	          return true;
	    }
	    return false;
	},
	editgroup:function(evn)
	{
		var status = parseInt($(evn).attr("status"));
	  this.groupid = $(evn).parents("tr").attr("groupid");
		var groupname = $(evn).parents("tr").find(".group_name>span").text();
		this.role = $(evn).parents("tr").attr("role");
		$(".group_menu_bar").show();		
		if ( status==1 ) //群组设置
		{
		    groupMember.state = 1;
		    $("#table_member tbody").html("");
		    group.groupinfo.group_type = $(evn).attr("group_type");
		    group.groupinfo.groupname  = groupname;
		    group.groupinfo.max_number = $(evn).parents("tr").children().eq(3).text();
		    $("#viewCreate").show();
		    $(".group_menu_bar>span").attr("class","menu_item");
		    $(".group_menu_bar>span:first").attr("class","menu_item_active");		    
		    $("#group_title").html("默认群组(<span style='font-weight:bold;padding:0px 2px;'>"+groupname+"</span>)信息设置");
		  	group.create_goup('edit'); 
		}
	  else if ( status==3 ) //解散群组
  	{
  		$('#delete_group').show();
  		var html = "是否解散群组<span style='font-weight:bold;padding:0px 2px;color:#cc3300;'>"+groupname+"</span>，解散后将不可恢复?";
  		$("#hint_content").html(html);
  	}
	},
	//删除群组
	remove:function()
	{
		var para = { "groupid":this.groupid };
		var parameter = { "module":"group","action":"delDefaultGroup","params":para };
		$.post(this.identical_url,parameter,function(returndata){
			  if ( returndata.success){
			  	$(".group_table tbody tr[groupid='"+group.gorupid+"']").remove();
			  	group.Search(1);
			  	$('#delete_group').hide();
			  }
			  else{
			  }
	  });	
	},
	getVal:function(){
		var fields = {};
		var name = $.trim($("#group_name").val());
		if ( name==""){
			this.hint("请输入群组名称！");
			$("#group_name").focus();
			return false;
		}
		else{
			fields.group_name = name;
		}		
		var max_val = $.trim($("#max_val").val());
		if ( max_val==""){
			this.hint("请输入成员上限！");
			$("#max_val").focus();
			return false;
		}
		if (isNaN(max_val))
		{
			 this.hint("成员上限请输入数字类型！");
			$("#max_val").focus();
			return false;
		}
		else
		{
			var min_val = $("#max_val").attr("min_member");
			if ( min_val !="")
			{
				min_val = parseInt(min_val);
				max_val = parseInt(max_val);
				if ( max_val<min_val)
				{
					 this.hint("成员上限必须大于上限最小值"+min_val);
			     $("#max_val").focus();
			     return false;
				}
			}
		}
		fields.max_number = max_val;
		fields.group_desc = $.trim($("#group_desc").val());
		fields.group_logo = $("#group_logo").attr("fileid");
		//获得组织部门id
		var deptid = Array();
		var ctl = $("#selected_department>span");
		for(var i=0;i< ctl.length;i++)
		{
		  deptid.push(ctl.eq(i).attr("deptid"));
		}
		fields.deptid = deptid;
		//获得允许加入的人员
		var staff = Array();
		ctl = $("#selected_staff>span");
		for(var i=0;i<ctl.length;i++){
			staff.push(ctl.eq(i).attr("fafa_jid"));
		}		
		fields.allow_jid = staff;
		if ( deptid.length==0 && staff.length==0)
		{
		  this.hint("允许加入的部门或人员不能同时为空！");
			return false;
		}
		//获得禁止加入的人员
		staff = Array();
		ctl = $("#selected_remove>span");
		for(var i=0;i<ctl.length;i++){
			staff.push(ctl.eq(i).attr("fafa_jid"));
		}
		fields.remove_jid= staff;
		//群组图片
		fields.logo = $("#group_logo").attr("fileid");
		return fields;
	},
	hint:function(message){
		$("#create_hint").text(message);
		setTimeout(function() { $(".group_hint").text(""); },2500);
	},
	//设置群组成员最大数量
	setMaxNumber:function()
	{		 
		//条件判断
		var max_number = $.trim($("#max_number").val());
		if ( max_number==""){
			this.setmaxnumberHint("请输入成员上限！",true);
			$("#max_number").focus();
			return false;
		}
		if (isNaN(max_number))
		{
			this.setmaxnumberHint("成员上限请输入数字类型！",true);
			$("#max_number").focus();
			return false;
		}
		else
		{
			var min_val = $("#max_number").attr("min_member");
			if ( min_val !="")
			{
				min_val = parseInt(min_val);
				max_number = parseInt(max_number);
				if ( max_number<min_val)
				{
					 this.setmaxnumberHint("成员上限必须大于上限最小值",true);
			     $("#max_number").focus();
			     return false;
				}
			}
			//当前群成员数量
			var number = $(".group_table tbody tr[groupid='"+this.groupid+"']").children().eq(1).text();
			if ( number!=null && number!="")
			  number = parseInt(number);
			if ( max_number < number)
			{
				this.setmaxnumberHint("成员上限不得小于群当前群成员数！",true);
			  $("#max_number").focus();
				return;
			}
		}
		var para = { "groupid":this.groupid,"max_number":max_number };
		var parameter = { "module":"group","action":"setMaxNumber","params":para };
		$.post(this.identical_url,parameter,function(returndata){
			if ( returndata.success){
				group.setmaxnumberHint("修改默认群组成员上限数量成功！",false);
				//修改默认群组列表数据
				$(".group_table tbody tr[groupid='"+group.groupid+"']").children().eq(3).text(max_number);
		  }
		  else
		  	group.setmaxnumberHint("修改默认群组成员上限数量失败！",true);
		});
	},
	setmaxnumberHint:function(message,error)
	{
		$("#setMaxNumber_hint").text(message);
		if ( error )
		  $("#setMaxNumber_hint").css("color","#cc3300");
		else
			$("#setMaxNumber_hint").css("color","#666666");

		setTimeout(function() { $("#setMaxNumber_hint").text(""); },3000);
	},
	groupname_change:function(evn)
	{
		var groupname = $.trim($(evn).val());
		if ( groupname=="")
		{
			$(evn).next().hide();
		}
	},
	menu_click:function(evn)
	{
    var state = $(evn).attr("state");
    $(".menu_item_active").attr("class","menu_item");
    $(evn).attr("class","menu_item_active");
    $(".group_set_info,.group_set_member,.group_set_maxnumber").hide();
    $(".group_set_"+state).show();
    if ( state=="member")
    {
        if ( groupMember.state==1 && $("#table_member tbody").children().length==0)
        {
             groupMember.search_groupmember(1);
             groupMember.state = 0;
        }
    }
  }
};

var groupTree = 
{
	getstaff_url:"",
	url:"",
	type:1,
	viewTree:function(evn){
		var state = $(evn).attr("state");
		this.type = state;
		$('#selectdept').show();
		var title = "";
		if ( $("#tree_depart").children().length==0)
		  this.init_zzjg();
		if ( state=="1"){
			title = "选择部门";
			$(".group_staff_box").hide();
			$("#tree_depart").show();
		}
		else{
			if ( state=="2")
			  title="选择特定人员";
			else
				title="排除特定人员";
			$("#tree_depart").hide();
			$(".group_staff_box").show();
		}
		$("#selectdept .title").text(title);
	},
	init_zzjg : function () {
		var zTreeSetting = {
	    	check:{
	    		enable:true,
	    		chkboxType:{ "Y": "s", "N": "s"}
	    	},
	    	data:{
	    		simpleData:{
	    			enable:true
	    		}
	    	},
	    	callback: {
	    		beforeCheck:this.zTreeBeforeCheck,
	    		onClick:groupTree.TreeClick
			  }
	  };
		var setting = {
	    	data:{
	    		simpleData:{
	    			enable:true
	    		}
	    	},
	    	callback: {
	    		onClick:groupTree.selectedClick
	      }
	  };
    $.getJSON(this.url,{ t:new Date().getTime()}, 
    	function(data, textStatus) {
			  $.fn.zTree.init($("#tree_depart"), zTreeSetting, data.datasource);
			  $.fn.zTree.init($("#tree_dept_staff"), setting, data.datasource);
      }    
    );
	},
	TreeClick:function(event, treeId, treeNode)
	{
		 var id = treeNode.id;
		 if ( !treeNode.isParent)
		 {
		 	 if ( treeNode.state == "0") return;
		 	 var parameter = {"deptid":treeNode.id};
		 	 $.getJSON(groupTree.url,parameter,function(data) {
		 	 	  if (data.success)
		 	 	  {
		 	 	  	if (data.datasource.length==0)
		 	 	  	{
		 	 	  		treeNode.state = 0;
		 	 	  	}
		 	 	  	else
		 	 	  	{
			 	 	    var treeObj = $.fn.zTree.getZTreeObj("tree_depart");
	            treeObj.addNodes(treeNode,data.datasource);
	            treeNode.state = 0;
            }
          }
		 	 });
		 }
	},
	zTreeBeforeCheck:function(treeId, treeNode){
		if ( treeNode.checked){
			if (treeNode.getParentNode()!=null && treeNode.getParentNode().checked)
			  return false;
		}
	},
	selectedClick:function (event, treeId, treeNode) 
	{
		//读取下级部门数据记录
		var id = treeNode.id;
	  if ( !treeNode.isParent)
	  {
		 	 if ( treeNode.state != "0")
		 	 {
			 	 var parameter = {"deptid":treeNode.id};
			 	 $.getJSON(groupTree.url,parameter,function(data) {
			 	 	  if (data.success)
			 	 	  {
			 	 	  	if (data.datasource.length==0)
			 	 	  	{
			 	 	  		treeNode.state = 0;
			 	 	  	}
			 	 	  	else
			 	 	  	{
				 	 	    var treeObj = $.fn.zTree.getZTreeObj("tree_dept_staff");
		            treeObj.addNodes(treeNode,data.datasource);
		            treeNode.state = 0;
		          }
		        }
			 	 });
		   }
	  }
	  
	  //是否已经读取部门下人员
	  if ( treeNode.readstate == "1")
	  {
	  		  	
	  }
	  else
	  {
			//读取下级人员
			$.post(groupTree.getstaff_url,{"deptid":id},function(data){
				var html = Array();
				if (data.success){
					var staff = data.list;
					var row = null;
					for(var i=0;i<staff.length;i++)
					{
						row = staff[i];
						html.push("<span onclick='selectstaff(this);' fafa_jid='"+row.fafa_jid+"'>"+row.nick_name+"</span>");					
					}
					if ( html.length>0)
					  $(".group_option_staff").html(html.join(""));
					else{
						html.push("<span>未查询到人员</span>");
						$(".group_option_staff").html(html.join(""));
					}
				}
			});
	  }
	},
	selectedDept:function(){
		var html = Array();
		if ( this.type==1){
			var treeObj = $.fn.zTree.getZTreeObj("tree_depart");
			var nodes = treeObj.getCheckedNodes(true);
			var parentid = "";
			for(var i=0;i<nodes.length;i++)
			{
				var node = nodes[i];
				var deptid = node.id;
				if ( node.isParent && deptid.indexOf("v")!=-1){
					html.push("<span deptid='"+deptid+"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+node.name+"</span><i class='delete_lable_empty' title='移除部门' onclick='removeItem(this);'></i></span>");
				  break;
				}
				else if (node.isParent){
					parentid += node.id+";";
				}
				var pid = node.pId;
				if ( parentid.indexOf(pid)==-1)
				{
					html.push("<span deptid='"+deptid+"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+node.name+"</span><i class='delete_lable_empty' title='移除部门' onclick='removeItem(this);'></i></span>");
        }				
			}
			$("#selected_department").html(html.join(""));   
	  }
	  else{
	  	var staffs = $(".group_selected_staff>span");
	  	for(var i=0;i<staffs.length;i++){
	  		html.push("<span fafa_jid='"+ staffs.eq(i).attr("fafa_jid") +"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+ staffs.eq(i).text() +"</span><i class='delete_lable_empty' title='移除人员' onclick='removeItem(this);'></i></span>");
	  	}
	  	if ( this.type==2){
	  		$("#selected_staff").html(html.join(""));
	  	}
	  	else{
	  		$("#selected_remove").html(html.join(""));
	  	}
	  }
		$('#selectdept').hide();
	}
}
	
//群组成员管理
var groupMember = {
    state:0,
    record:9,
	  issearch:false,
	  exists:false,
    pageselectCallback:function(pageindex){
        if (groupMember.issearch )
          groupMember.search_groupmember(pageindex + 1);
	  },
    pageInit:function(){
        var opt = {callback: groupMember.pageselectCallback};
        opt.items_per_page = groupMember.record;
        opt.num_display_entries = 3;
        opt.num_edge_entries = 3;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
    },
    search_groupmember:function(pageindex)
    {
      var nick_name = $.trim($("#text_nickname").val());  
    	var para = { "groupid":group.groupid,"nick_name":nick_name,"pageindex":pageindex,"record":this.record };
    	var parameter = { "module":"group","action":"getGroupMember","params":para };
    	$(".group_loadding").show();
    	$.post(group.identical_url,parameter,function(returndata){
    	    $(".group_loadding").hide();
          if ( returndata.success)
          {
            	if ( pageindex==1 ){
            	  	if ( returndata.recordcount <= groupMember.record){
            	  		$("#search_member").hide();
            	  	}
            	  	else{
                 	  	groupMember.issearch = false;
                 	  	var optInit = groupMember.pageInit();
                 	  	$("#search_member").show();
                 	  	$("#search_member").pagination(returndata.recordcount,optInit);
                 	  	groupMember.issearch = true;
            	    }
              }
              else{
              	 $("#search_member").show();
              	 groupMember.issearch = true;
              }
              groupMember.fullGroupMember(returndata.list);
          }
    	});
    	
    },
    //填充群组成员
    fullGroupMember:function(list){
        var html = Array();
        var row = null,url=null,role=null;
        if ( list.length==0)
        {
            html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;' class='mb_common_table_empty'>未查询到群成员数据记录！</span>");
        }
        else
        {
        		for(var i=0;i<list.length;i++)
        		{
                row = list[i];
                role = row.role;
                if ( role=="2")
                    html.push("<tr jid='"+row.fafa_jid+"' role='2' style='color:#0088cc;'>");
                else if ( role=="1")
                    html.push("<tr jid='"+row.fafa_jid+"' role='1' style='color:#cc3300;'>");
                else
                    html.push("<tr jid='"+row.fafa_jid+"' role='0' >");
                html.push("  <td class='nick_name' width='170'><span style='width:169px;' class='group_item'>"+row.nick_name+"</span></td>");
                html.push("  <td width='265'><span style='width:264px;' class='group_item'>"+row.deptname+"</span></td>");
                if ( role=="2")
                    html.push("  <td width='100' align='center'>群创建者</td>");
                else if ( role=="1")
                    html.push("  <td width='100' align='center'>群管理员</td>");
                else 
                    html.push("  <td width='100' align='center'>普通成员</td>");
                html.push("<td width='100' align='center' class='operator'>");
                if ( role!="2")
                {
                    html.push("<span onclick='groupMember.SetManager(this);' class='glyphicon glyphicon-user' style='cursor:pointer;margin-right:25px;' title='" +(role=="1"?"取消管理员":"设置管理员")+"'></span>");
                    html.push("<span onclick='groupMember.delMember(this)' class='glyphicon glyphicon-trash glyphicon_group' title='删除群成员'></span>");
                }
                html.push("</td>");
                html.push("</tr>");
        		}
        }
        $("#table_member tbody").html(html.join(""));
    },
    SetManager:function(evn)
    {
        var curRowe = $(evn).parents("tr");
        var role = curRowe.attr("role");
        var jid = curRowe.attr("jid");
        var member = curRowe.find(".nick_name").text();
        var para = { "groupid":group.groupid,"jid":jid,"role":role,"member":member};
        var parameter = { "module":"group","action":"setGroupMember","params":para };
        var html = Array();
        html.push("<div class='group_opration'>");
        html.push("  <img src='/bundles/fafatimewebase/images/loading.gif' />");
        html.push("  <span>正在设置...</span>");
        html.push("</div>");
        $(evn).parent().html(html.join(""));        
        $.post(group.identical_url,parameter,function(returndata){
            html = [];
            if ( returndata.success)
            {
                if (role=="1")
                {
                    curRowe.css("color","#666666");
                    curRowe.attr("role",0);
                    html.push("<span title='设置管理员' style='cursor:pointer;margin-right:25px;' class='glyphicon glyphicon-user' onclick='groupMember.SetManager(this);'></span>");
                    html.push("<span title='删除群成员' class='glyphicon glyphicon-trash glyphicon_group' onclick='groupMember.delMember(this)'></span>");
                    curRowe.find(".operator").html(html.join(""));
                }
                else
                {
                    curRowe.css("color","#cc3300");
                    curRowe.attr("role",1);
                    html.push("<span title='取消管理员' style='cursor:pointer;margin-right:25px;' class='glyphicon glyphicon-user' onclick='groupMember.SetManager(this);'></span>");
                    html.push("<span title='删除群成员' class='glyphicon glyphicon-trash glyphicon_group' onclick='groupMember.delMember(this)'></span>");
                    curRowe.find(".operator").html(html.join(""));
                }
            }
        });
    },
    //删除群成员
    delMember:function(evn)
    {
        var curRowe = $(evn).parents("tr");
        var role = curRowe.attr("role");
        var jid = curRowe.attr("jid");
        var member = curRowe.find(".nick_name").text();
        var para = { "groupid":group.groupid,"jid":jid,"role":role,"member":member};
        var parameter = { "module":"group","action":"setGroupMember","params":para };
        var html = Array();
        html.push("<div class='group_opration'>");
        html.push("  <img src='/bundles/fafatimewebase/images/loading.gif' />");
        html.push("  <span>正在删除...</span>");
        html.push("</div>");
        $(evn).parent().html(html.join(""));
        
        var para = { "groupid":group.groupid,"jid":jid,"member":member };
        var parameter = { "module":"group","action":"delGroupMember","params":para };
        $.post(group.identical_url,parameter,function(returndata){
        	if ( returndata.success)
        	{
        		curRowe.remove();
        		groupMember.search_groupmember(1);
        	}
        });
    }
    
}