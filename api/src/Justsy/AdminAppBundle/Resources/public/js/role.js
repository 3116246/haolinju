var role = {
	search_url:"",
	getmenu_url:"",
	save_url:"",
	issearch:false,
	record:14,
	search_state:true,
	roleid:"",
	pageindex:1,
  pageselectCallback:function(pageindex){
		 if (role.issearch )
		   role.Search(pageindex+1);
	},
	pageInit:function(){
 	  var opt = {callback: role.pageselectCallback};
    opt.items_per_page = role.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex){
		if ( !this.search_state) return;
		 this.pageindex = pageindex;
     this.search_state = false;
     var para = { "role":$.trim($("#text_role").val()),"pageindex":pageindex,"record":this.record };
     var parameter = { "module":"roleFunc","action":"search_role","params":para };
     $.post(this.search_url,parameter,function(returndata){
	 	 	  role.search_state = true;
	 	 	  if (returndata.success){
		 	 	   if ( pageindex==1 ){
			 	 	  	if ( returndata.recordcount <= role.record){
			 	 	  		$(".pagestyle").hide();
			 	 	  	}
			 	 	  	else{
				 	 	  	role.issearch = false;
				 	 	  	var optInit = role.pageInit();
				 	 	  	$(".pagestyle").show();
				 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
				 	 	  	role.issearch = true;
			 	 	    }
		 	 	   }
		 	 	   else{
		 	 	   	role.issearch = true;
		 	 	   }
		 	 	   role.fulldata(returndata.rolelist);
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
	 	 	    html.push("<tr roleid='"+row.id+"'>");
	 	 	  	html.push(" <td width='285' align='left' class='rolename'>"+row.name+"</td>");
  		    html.push(" <td width='285' align='left' class='rolecode'>"+row.code+"</td>");
  		    html.push(" <td width='285' align='left' class='roletype'>"+row.role_type+"</td>");
  		    html.push(" <td width='143' align='center'>");
  		    html.push("   <span class='glyphicon glyphicon-pencil glyphicon_space' onclick='role.show_edit(this)' flag='edit' title='编辑角色'></span>");
  		    html.push("   <span class='glyphicon glyphicon-trash glyphicon_space'  onclick='role.show_del(this)' title='删除角色'></span>");
  		    html.push("</td>");
  		    html.push("</tr>");
 	 	   }
		 }
	  else {
	  	html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		}
		$(".mb_common_table tbody").html(html.join(""));
	},
	show_del:function(evn)
	{
	    this.roleid = $(evn).parents("tr").attr("roleid");
	    var rolename = $(evn).parents("tr").find(".rolename").text();
	    var html="<span style='padding-right:5px'>是否删除角色：</span><span style='color: #cc3300;font-weight: bold'>"+rolename+"</span>";
	    $("#remove_role .content").html(html);
	    $("#remove_role").modal("show");
  },
  del_role:function()
  {
     var parameter = { "module":"roleFunc","action":"del_role","params":{"roleid":this.roleid} };
     $.post(this.search_url,parameter,function(returndata){
        if ( returndata.success)
        {
           $(".mb_common_table tbody tr[roleid='"+role.roleid+"']").remove();            
           $("#remove_role").modal("hide");
           if ( $(".pagestyle").is(":visible"))
           {
               if ($(".mb_common_table tbody tr").length==0)
                  role.Search(role.pageindex-1);
               else
                  role.Search(role.pageindex);
           }
        }
     });
  },
  show_edit:function(evn)
  {
     var type = $(evn).attr("flag");
     if ( type=="add")
     {
        this.roleid="";
        $("#edit_role .content input[type='text']").val("");
        $("#edit_role .title").text("创建角色");
     }
     else
     {
        var trObj = $(evn).parents("tr");
        this.roleid = trObj.attr("roleid");
        $("#text_name").val(trObj.find(".rolename").text());
        $("#text_code").val(trObj.find(".rolecode").text());
        $("#text_type").val(trObj.find(".roletype").text());
        $("#edit_role .title").text("修改角色");                
     }
     $("#edit_role").modal("show");
     $("#text_name").focus();
  },
	editRole:function()
	{
	   var tmp = $.trim($("#text_name").val());
	   if ( tmp=="")
	   {
	      this.hint($("#text_name"),"请输入角色名称！");
	      return;
	   }	   
	   var para = { "id":this.roleid,
	                "name":tmp,
	                "code":$.trim($("#text_code").val()),
	                "type":$.trim($("#text_type").val())
	              }
     var parameter = { "module":"roleFunc","action":"editRole","params":para };
     $.post(this.search_url,parameter,function(returndata){
        if ( returndata.success)
        {
            $("#edit_role .content input[type='text']").val("");
            if (para.id=="")
            {
                var html = Array();
                html.push("<tr roleid='"+returndata.id+"'>");
                html.push("  <td width='285' align='left' class='rolename'>"+para.name+"</td>");
                html.push("  <td width='285' align='left' class='rolecode'>"+para.code+"</td>");
                html.push("  <td width='285' align='left' class='roletype'>"+para.type+"</td>");
                html.push("  <td width='143'>");
                html.push("    <span title='编辑角色' flag='edit' onclick='role.show_edit(this)' class='glyphicon glyphicon-pencil glyphicon_space'></span>");
                html.push("    <span title='删除角色' onclick='role.show_del(this)' class='glyphicon glyphicon-trash glyphicon_space'></span>");
                html.push("  </td>");
                html.push("</tr>");
                if ( $(".mb_common_table tbody tr").length==0)
                  $(".mb_common_table tbody").html(html.join(""));
                else
                  $(".mb_common_table tbody tr:first").before(html.join(""));
                if ( $(".mb_common_table tbody tr").length >=role.record)
                  $(".mb_common_table tbody tr:last").remove();
                role.hint($("#text_name"),"添加角色名称成功");
            }
            else
            {
                var trObj = $(".mb_common_table tbody tr[roleid='"+para.id+"']");
                trObj.find(".rolename").text(para.name);
                trObj.find(".rolecode").text(para.code);
                trObj.find(".roletype").text(para.type);
                $("#edit_role").modal("hide");
            }
        }
        else
        {
            if ( returndata.exists)
            {
                role.hint($("#text_name"),"已存在角色名称，请重新输入！");
            }
            else
            {
                
            }
        }
     });
	},
	hint:function(container,msg)
	{
	    if ( container!=null)
	    {
	       container.focus();
	       container.css("border","1px solid #cc3300");
	    }
	    if ( msg!=null && msg!="")
	      $(".hint_msg").html(msg);
	    setTimeout(function(){
	        $(".hint_msg").html("");
	        if ( container!=null)
	           container.css("border","1px solid #aba");
	    },2000);	    
	}
};

var Func = {
	search_url:"",
	getmenu_url:"",
	save_url:"",
	issearch:false,
	record:14,
	search_state:true,
	funcid:"",
	pageindex:1,
  pageselectCallback:function(pageindex){
		 if (Func.issearch )
		   Func.Search(pageindex+1);
	},
	pageInit:function(){
 	  var opt = {callback: Func.pageselectCallback};
    opt.items_per_page = Func.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex){
		if ( !this.search_state) return;
		 this.pageindex = pageindex;
     this.search_state = false;
     var para = { "func":$.trim($("#text_func").val()),"pageindex":pageindex,"record":this.record };
     var parameter = { "module":"roleFunc","action":"search_func","params":para };
     $.post(this.search_url,parameter,function(returndata){
	 	 	  Func.search_state = true;
	 	 	  if (returndata.success){
		 	 	   if ( pageindex==1 ){
			 	 	  	if ( returndata.recordcount <= Func.record){
			 	 	  		$(".pagestyle").hide();
			 	 	  	}
			 	 	  	else{
				 	 	  	Func.issearch = false;
				 	 	  	var optInit = Func.pageInit();
				 	 	  	$(".pagestyle").show();
				 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
				 	 	  	Func.issearch = true;
			 	 	    }
		 	 	   }
		 	 	   else{
		 	 	   	 Func.issearch = true;
		 	 	   }
		 	 	   Func.fulldata(returndata.funclist);
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
	 	 	    html.push("<tr funcid='"+row.id+"'>");
	 	 	  	html.push(" <td width='285' align='left' class='funcname'>"+row.name+"</td>");
  		    html.push(" <td width='285' align='left' class='funccode'>"+row.code+"</td>");
  		    html.push(" <td width='285' align='left' class='functype'>"+row.type+"</td>");
  		    html.push(" <td width='143' align='center'>");
  		    html.push("   <span class='glyphicon glyphicon-pencil glyphicon_space' onclick='Func.show_edit(this)' flag='edit' title='编辑功能点'></span>");
  		    html.push("   <span class='glyphicon glyphicon-trash glyphicon_space'  onclick='Func.show_del(this)' title='删除功能点'></span>");
  		    html.push("</td>");
  		    html.push("</tr>");
 	 	   }
		 }
	  else {
	  	html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		}
		$(".mb_common_table tbody").html(html.join(""));
	},
	show_del:function(evn)
	{
	    this.funcid = $(evn).parents("tr").attr("funcid");
	    var funcname = $(evn).parents("tr").find(".funcname").text();
	    var html="<span style='padding-right:5px'>是否删除功能点：</span><span style='color: #cc3300;font-weight: bold'>"+funcname+"</span>";
	    $("#remove_func .content").html(html);
	    $("#remove_func").modal("show");
  },
  del_func:function()
  {
     var parameter = { "module":"roleFunc","action":"del_func","params":{"funcid":this.funcid} };
     $.post(this.search_url,parameter,function(returndata){
        if ( returndata.success)
        {
           $(".mb_common_table tbody tr[funcid='"+Func.funcid+"']").remove();            
           $("#remove_func").modal("hide");
           if ( $(".pagestyle").is(":visible"))
           {
               if ($(".mb_common_table tbody tr").length==0)
                  Func.Search(Func.pageindex-1);
               else
                  Func.Search(Func.pageindex);
           }
        }
     });
  },
  show_edit:function(evn)
  {
     var type = $(evn).attr("flag");
     if ( type=="add")
     {
        this.funcid="";
        $("#edit_func input[type='text']").val("");
        $("#edit_func.title").text("创建功能点");
     }
     else
     {
        var trObj = $(evn).parents("tr");
        this.funcid = trObj.attr("funcid");
        $("#text_name").val(trObj.find(".funcname").text());
        $("#text_code").val(trObj.find(".funccode").text());
        $("#text_type").val(trObj.find(".functype").text());
        $("#edit_func .title").text("修改功能点");                
     }
     $("#edit_func").modal("show");
  },
	editFunc:function()
	{
	   var tmp = $.trim($("#text_name").val());
	   if ( tmp=="")
	   {
	      this.hint($("#text_name"),"请输入功能点名称！");
	      return;
	   }	   
	   var para = { "id":this.funcid,
	                "name":tmp,
	                "code":$.trim($("#text_code").val()),
	                "type":$.trim($("#text_type").val())
	              }
     var parameter = { "module":"roleFunc","action":"editfunc","params":para };
     $.post(this.search_url,parameter,function(returndata){
        if ( returndata.success)
        {
            $("#edit_func .content input[type='text']").val("");
            if (para.id=="")
            {
                var html = Array();
                html.push("<tr funcid='"+returndata.id+"'>");
                html.push("  <td width='285' align='left' class='rolename'>"+para.name+"</td>");
                html.push("  <td width='285' align='left' class='rolecode'>"+para.code+"</td>");
                html.push("  <td width='285' align='left' class='roletype'>"+para.type+"</td>");
                html.push("  <td width='143' align='center'>");
                html.push("    <span title='编辑功能点' flag='edit' onclick='Func.show_edit(this)' class='glyphicon glyphicon-pencil glyphicon_space'></span>");
                html.push("    <span title='删除功能点' onclick='Func.show_del(this)' class='glyphicon glyphicon-trash glyphicon_space'></span>");
                html.push("  </td>");
                html.push("</tr>");
                $(".mb_common_table tbody tr:first").before(html.join(""));
                if ( $(".mb_common_table tbody tr").length >=Func.record)
                  $(".mb_common_table tbody tr:last").remove();
                Func.hint($("#text_name"),"添加功能点名称成功");
            }
            else
            {
                var trObj = $(".mb_common_table tbody tr[funcid='"+para.id+"']");
                trObj.find(".funcname").text(para.name);
                trObj.find(".funccode").text(para.code);
                trObj.find(".functype").text(para.type);
                $("#edit_func").modal("hide");
            }
        }
        else
        {
            if ( returndata.exists)
            {
                Func.hint($("#text_name"),"已存在功能点名称，请重新输入！");
            }
            else
            {
                Func.hint($("#text_name"),"编辑功能点出错，请重试！");           
            }
        }
     });
	},
	hint:function(container,msg)
	{
	    if ( container!=null)
	    {
	       container.focus();
	       container.css("border","1px solid #cc3300");
	    }
	    if ( msg!=null && msg!="")
	      $(".hint_msg").html(msg);
	    setTimeout(function(){
	        $(".hint_msg").html("");
	        if ( container!=null)
	           container.css("border","1px solid #aba");
	    },2000);	    
	}
};