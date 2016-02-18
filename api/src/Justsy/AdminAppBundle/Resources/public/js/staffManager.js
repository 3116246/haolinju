var AddStaff = {
	add_url:"",
	getdepart_url:"",
	login_account:"",
	update_url:"",
	edit_row:null,
	//人员信息
	staffinfo:{"deptid":"","nick_name":"","sex":"","duty":"","mobile":""},
	viewdialog:function(ev) {
		this.showhint("");
		$("#dept_area").hide();
		AddStaff.login_account = "";
		if ( $(ev).attr("class")=="mb_edition_button"){
			$("#viewAdd .title").text("修改用户信息");
			AddStaff.login_account = $.trim($(ev).parents(".staff_table_row").attr("login_account"));
			$("#password_row input").attr("disabled","disabled");
			var deptid = $(ev).parents(".staff_table_row").attr("dept_id");
			$("#txtdept").attr("deptid",deptid);
			//性别的处理
			var sex = $(ev).parents(".staff_table_row").attr("sex");
			$("#checkboxsex1,#checkboxsex0").attr("checked",false);
			if ( sex=="女")
			  $("#checkboxsex0").attr("checked",true);
			else
				$("#checkboxsex1").attr("checked",true);
			var control = $(ev).parents(".staff_table_row").children();
			this.edit_row = control;
			$("#txtdept").val(control.eq(2).text());
			$("#realName").val(control.eq(1).text());
			$("#account").val(AddStaff.login_account);
			$("#account").attr("readonly","readonly");
			$("#account").css("background-color","#eee");
			$("#duty").val(control.eq(3).text());		
			$("#mobile").val(control.eq(4).text());
			//缓存用户信息
			this.staffinfo = {"deptid":deptid,"nick_name":$("#realName").val(),"sex":sex,"duty":$("#duty").val(),"mobile":$("#mobile").val()};
		}
		else{
		  this.staffinfo = null;
			this.edit_row = null;
			$("#viewAdd .title").text("添加用户信息");
			$("#checkboxsex1").attr("checked",true);
			$("#password_row").show();
			$("#password_row input").removeAttr("disabled");
			$("#account").removeAttr("readonly");
			$("#account").css("background-color","white");
		  $(".staff_add_row input").val("");
		  $(".staff_add_hint").text("");
	  }
	  $("#viewAdd").show();
	},
	validEmail:function(mail){
  	var result=false;
    var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
    result = reg.test(mail);
    if(!result)
    {
    	//reg = /^1[3|4|5|8][0-9]\d{8}$/;
    	result = reg.test(mail);
    }
    return result;
  },    
	Save:function(){
	    var state =  AddStaff.login_account == "" ? "add":"edit";
      if ( $.trim($("#realName").val())==""){
        $("#realName").focus();
        this.showhint("请输入用户姓名！");
        return;
      }
      if ( $.trim($("#account").val())==""){
    	  $("#account").focus();
    	  this.showhint("请输入登录账号！");
    	  return;
      } 
      if ( state=="add" )
      {
        var pass = $.trim($("#pass").val());
        if ( pass ==""){
        	 $("#pass").focus();
        	 this.showhint("请输入登录密码！");
        	 return;
        }
        else if ( pass.length<6)
        {
        	 $("#pass").focus();
        	 this.showhint("登录密码必须最小达到6位！");
        	 return;
        }
      }   
      if(!this.validEmail($("#account").val())){
    	   $("#account").focus();
    	   this.showhint("登录账号为邮箱格式，请检查！");
    	   return;
      }
      var sex = "";
      if ( $("#checkboxsex1").attr("checked")==null && $("#checkboxsex0").attr("checked")==null){
    	   this.showhint("请选择性别！");
    	   return;
      }
      else if ($("#checkboxsex1").attr("checked")==null)
        sex = "女";
      else
    	  sex = "男";
      var mobile = $.trim($("#mobile").val());
      if ( mobile != "")
      {
    	   if ( mobile.length != 11)
    	   {
    	  	 this.showhint("手机号必须为11位的数字！");
    	  	 $("#mobile").focus();
    	     return;
    	   }
      }
      //参数
      var parameter = {
                         "state":state,
                         "dept_id":$("#txtdept").attr("deptid"),
                         "nick_name":$.trim($("#realName").val()),
        	               "login_account":$.trim($("#account").val()),
        	               "password":$("#pass").val(),
        	               "sex":sex,
        	               "duty":$.trim($("#duty").val()),
        	               "mobile":mobile
                      };
        //判断是否修改了内容
       if ( state == "edit" && this.staffinfo.deptid==parameter.dept_id && this.staffinfo.nick_name==parameter.nick_name && this.staffinfo.sex==parameter.sex && this.staffinfo.duty==parameter.duty &&  this.staffinfo.mobile==parameter.mobile){
        	this.showhint("你未修改任何数据项！");
        	return;
       }       
	     $("#btnSave").attr("disabled","disabled");
	     $("#btnCancle").attr("disabled","disabled");
       $.post(AddStaff.update_url,parameter,function(data){
           $("#btnSave").removeAttr("disabled");
           $("#btnCancle").removeAttr("disabled");
        	 if (data.success){
        	    if ( state=="edit")
        	    {
            	    AddStaff.edit_row.eq(1).text(parameter.nick_name);//姓名
            	 	  AddStaff.edit_row.eq(2).text($.trim($("#txtdept").val())); //部门
            	 	  AddStaff.edit_row.eq(3).text(parameter.duty); //职务
            	 	  AddStaff.edit_row.eq(4).text(parameter.mobile); //手机
            	 	  AddStaff.edit_row.parent().attr("dept_id",parameter.dept_id);
            	 	  AddStaff.edit_row.parent().attr("sex",parameter.sex);
            	 	  $("#viewAdd").hide();
        	 	  }
        	 	  else
        	 	  {
                    var row = $("#table_search tbody tr").length;
                    var html = Array();
                    html.push("<tr class='staff_table_row' sex='"+parameter.sex+"' dept_id='"+parameter.dept_id+"' login_account='"+parameter.login_account+"'>");
                    html.push(" <td style='float:left;width:240px;'>"+parameter.login_account+"</td>");
                    html.push(" <td style='float:left;width:120px;'>"+parameter.nick_name+"</td>");
                    html.push(" <td style='float:left;width:235px;'>"+ $("#txtdept").val()+"</td>");
                    html.push(" <td style='float:left;width:120px;'>"+parameter.duty+"</td>");
                    html.push(" <td style='float:left;width:120px;'>"+parameter.mobile+"</td>");
                    html.push(" <td style='float:left;width:100px;padding-top:2px;'>");
                    html.push("   <span onclick='AddStaff.viewdialog(this);' style='margin-left:10px;' class='mb_edition_button'></span>");
                    html.push("   <span class='mb_delete_button' onclick='Import.remove(this);'></span>");
                    html.push(" </td>");
                    html.push("</tr>");
                    if ( row < Import.record)
                    {
                      if ( row==0)
                        $("#table_search tbody").html(html.join(""));
                      else
                        $("#table_search tbody tr:first").before(html.join(""));
                    }
                    else
                    {
                     $("#table_search tbody tr:first").before(html.join(""));
                     $("#table_search tbody tr:last").remove();
                    }
                    $(".staff_add_hint").text("添加用户账号成功！");
                    //初始化            			  
            		  	$("#checkboxsex1").attr("checked",true);
            			  $("#account").css("background-color","white");
            		    $(".staff_add_row input").val("");
            		    $("#txtdept").attr("deptid","");
            		    setTimeout(function() { $(".staff_add_hint").text("");},2000);   		    
        	 	  }
        	 }
        	 else{
        	 	  AddStaff.showhint(data.msg);
        	 }
       });
	},
	showhint:function(message){
		$("#viewAdd .staff_add_hint").text(message);
		setTimeout(function() { $("#viewAdd .staff_add_hint").text("");},3000);
	},
	showdept:function(id)
	{
	    if ( id == "depttree")
	    {
    	    if ( $("#depttree").children().length==0)
    	    {
    	        LoadDept.tree_Id = id;
    	        LoadDept.loadTree(this.getdepart_url);
    	        $("#dept_area").show();
    	    }
    	    else
    	    {
    	        $("#dept_area").toggle();
    	    }
	    }
	    else
	    {
	        if ( $("#option_dept").children().length==0)
    	    {
    	        LoadDept.tree_Id = id;
    	        LoadDept.loadTree(this.getdepart_url);
    	        $("#selected_dept_area").show();
    	    }
    	    else
    	    {
    	        $("#selected_dept_area").toggle();
    	    }
	        
	    }
	},

	
	selected_dept:function(id)
	{
	    var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
		  var node = treeObj.getSelectedNodes();
		  if ( node.length>0)
		  {
		    var treenode = node[0];
		    $("#"+id).val(treenode.name);
		    $("#"+id).attr("deptid",treenode.id);
		    if ( id=="txtdept")
		        $("#dept_area").hide();
		    else
		        $("#selected_dept_area").hide();
		  }
  }
};

var Field ={
	 login_account:{"zh_name":"用户账号","index":"","isNull":0},
	 nick_name:    {"zh_name":"用户昵称","index":"","isNull":0},
	 dept_id:      {"zh_name":"部门ID或名称","index":"","isNull":1},
	 password:     {"zh_name":"用户密码","index":"","isNull":0},
	 mobile:       {"zh_name":"手机号码","index":"","isNull":1},
	 duty:         {"zh_name":"职务","index":"","isNull":1}
};

var Import = {
	 import_url:"",
	 sql_url:"",
	 search_url:"",
	 dele_url:"",
	 datasource:null,
	 import_type:1,
	 filepath:"",
	 totalrecord:"",
	 totalpage:0,
	 search_state:true,
	 issearch:false,
	 record:16,
	 pageindex:0,
	 isDel:0, //是否删除文件标志
	 selectdept:function(ev){
	 	 var dept_id = $(ev).attr("dept_id");
		 $("#text_parent_name").attr("dept_id",dept_id);
		 $("#text_parent_name").val($(ev).text());
		 $(".depart_searchArea").hide();
	 },
	 viewdialog:function(){
	    
	    Import.step('P');
	 	   $("#viewImport").show();
	 },
	 step:function(state){
	    $("#filedata").val("");
	 	  $(".import_step1").hide();
	 	  $(".import_step2").hide();
	 	  $("#btn_start_import").hide();
	 	  $(".staffmanager_hint").html("");
	 	  if ( state=="N"){
    	 	 	 $(".staff_field_setting,.staff_field_content").hide();
    	 	 	 $("#select_datasorce tbody").html("");
    	 	 	 $(".import_step2").show();
    	 	 	 $(".import_step2_setting>div").hide();
    	 	 	 $("#btnPre").show();
    	 	 	 $("#btnNext").hide();
           var selectval = $("#excel_type").attr("checked");
           if ( selectval!=null ){
           	 this.import_type = 1;
           	 $("#excel_setting").show();
           	 return;     	      	      	 
           }
           selectval = $("#ldap_type").attr("checked");
             if ( selectval!=null ){
             this.import_type = 2;
           	 $("#excel_setting").show();
           	 return;
           }
           selectval = $("#database_type").attr("checked");
             if ( selectval!=null ){
             this.import_type = 3;
           	 $("#database_setting").show();
           	 return;	      	      	 
           }
           selectval = $("#api_type").attr("checked");
             if ( selectval!=null ){
             	 this.import_type = 4;
             	 $("#api_setting").show();
           	   return;	      	      	 
           }
	 	  }
	 	  else if ( state=="P"){
	 	 	  $(".import_step1").show();
	 	 	  $("#btnPre").hide();
	 	 	  $("#btnNext").show();
	 	  }
	 },
	 checkinput:function(id){
	 	  if (id=="excel_type"){
	 	  	var file = $("#filedata").val();
	 	 	 	if ( file == ""){
	 	 	 		 this.showhint("请选择导入的Excel文件！",true);
	 	 	 		 return false;
	 	 	 	}
	 	 	 	else{	 	 	 		 
	 	 	 		 var suffix = file.substring(file.lastIndexOf(".")+1);
	 	       if ( suffix!="xls" && suffix!="xlsx"){
	 	 	        this.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
	 	 	       return false;
	 	       }
	 	 	 	}
	 	 	}
	 	 	else if (id=="lsap_type"){
	 	 	}
	 	 	else if (id=="database_type"){
	 	 	}
	 	 	else if (id = "api_type"){
	 	 	}
	 	 return true;
	 },
	 //isempty:显示提示后是否清空;true:清空;false:不清空
	 showhint:function(message,isempty){
	 	 if ( message == null || message =="")
	 	  $(".staffmanager_hint").text("");
	 	 else {
	 	 	$(".staffmanager_hint").html(message);
	 	 	if ( isempty )
	 	 	  setTimeout(function(){ $(".staffmanager_hint").text(""); },2000);
	 	 }
	 },
	 viewData:function(){
	 	 //初始相关操作
	 	 Field.login_account = {"zh_name":"用户账号","index":"","isNull":1};
	   Field.nick_name     = {"zh_name":"用户昵称","index":"","isNull":0},
	   Field.dept_id       = {"zh_name":"部门ID或名称","index":"","isNull":1},
	   Field.password      = {"zh_name":"用户密码","index":"","isNull":0};
	   Field.mobile        = {"zh_name":"手机号码","index":"","isNull":1};
	   Field.duty          = {"zh_name":"职务",    "index":"","isNull":1};
	   
	   $("#btn_start_import,.staff_field_content").hide();
	   $(".staff_field_row .mb_combox").html("");	 	 
	 	 
	 	 $("#select_datasorce tbody").html("");
	 	 var html_body = new Array();
	 	 var data = this.datasource;
	 	 var  row = null;
	 	 var td_style = "";
	 	 if ( data!=null && data.length>0){
	 	 	 $(".staff_field_setting").show();
		 	 for(var i=0;i< data.length;i++){
		 	 	 row = data[i];
		 	 	 if ( i== 0 ){
		 	 	 	 var temphtml = new Array();
		 	 	 	 var len = 0;
		 	 	 	 for(j in row){
		 	 	 	 	 len += 1;
		 	 	 	 }
		 	 	 	 var td_width = 100 / len;
		 	 	 	 td_style = "style='float:left;width:"+td_width+"%;'";
		 	   	 html_body.push("<tr class='staff_table_head'>");
		 	   	 temphtml.push("<tr class='staff_table_row'>");
		 	   	 for(j in row){
		 	   	 	 html_body.push("<td title='设置映射字段' "+td_style+">"+j+"</td>");
		 	   	 	 temphtml.push("<td "+td_style+">"+ row[j] +"</td>");	 	   	 	 
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   	 temphtml.push("</tr>");
		 	   	 html_body.push(temphtml.join(""));  	 
		 	   }
		 	   else{
		 	   	 html_body.push("<tr class='staff_table_row'>");
		 	   	 for(k in row){
		 	   	 	 html_body.push("<td "+td_style+">"+ row[k] +"</td>");	 	
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   }
		 	 }
	   }
	   else{
	   	 $(".staff_field_setting").hide();
	   }
	 	 $("#select_datasorce tbody").html(html_body.join(""));
	 },
	 //显示字段映射关系面板
	 viewField:function(){
	 	 if ( this.import_type==3)
	 	 	 $(".staff_field_content").css("margin-top","-500px");
	 	 else
	 	 	 $(".staff_field_content").css("margin-top","-402px");
	 	 $(".staff_field_content").show();
     var html = new Array();
     if ( $(".staff_field_content .staff_field_row").length==0){
			 for(j in Field){
			 	 var item = Field[j];
			 	 html.push("<div class='staff_field_row'>");
			 	 html.push("<div>");
			 	 var must = false;
			 	 var fieldname = "";
			 	 for(i in item){
			 	 	 if ( i=="zh_name"){
			 	 	  html.push("<span class='staff_fieldname'>请指定<span style='padding-left:2px;padding-right:2px;color:black;'>"+item[i]+"</span>字段映射关系：</span>");
			 	 	  fieldname = item[i];
           }
			 	 	 if ( i=="isNull" && item[i]==0){
			 	 	 	 must = true;
			 	 	   html.push("<span class='staff_field_desc'>*该字段必须指定映射关系</span>");
			 	 	 }
			 	 }
			 	 html.push("</div>")
			 	 html.push("<select class='mb_combox' must='"+(must ? 1:0)+"' fieldname='"+fieldname+"' field='"+j+"'></select>");
			 	 html.push("</div>");
			 }
			 $(".staff_field_content #setcontent").html(html.join(""));
			 this.loadCombox();
		 }
		 else{
		 	 if ($(".staff_field_row .mb_combox:first").children().length==0)
		 	  this.loadCombox();
		 }		 		
	 },
	 loadCombox:function(){
	 	 var html = new Array();
	 	 html.push("<option value=''></option>");
	 	 var data = this.datasource;
	 	 if ( data != null && data.length>0){
	 	 	 for(var i=0;i<1;i++){
	 	 	 	 var row = data[i];
	 	 	 	 var index = 0;
	 	 	 	 for(j in row){
	 	 	 	 	 html.push("<option value='"+index+"'>"+j+"</option>");
	 	 	 	 	 index++;
	 	 	 	 }
	 	 	 } 	 	 
	 	 }
	 	 $(".staff_field_row .mb_combox").html(html.join(""));
	 }, 
	 setField:function(){
	 	 var control = $(".staff_field_row .mb_combox");
	 	 //判断是否选择
	 	 var val = "";
	 	 var flag = 0;
	 	 for(var i=0;i<control.length;i++){
	 	 	 val =  control.eq(i).val();
	 	 	 if ( val==""){
	 	 	    var filedname = control.eq(i).attr("fieldname");
	 	 	    if ( control.eq(i).attr("must")=="1")
	 	 	    {
    	 	 	    $(".setfield_hint").text("必须指定【"+filedname+"】字段的映射关系！");
    	 	 	 	  control.eq(i).focus();
    	 	 	 	  return;
	 	 	 	  }
	 	 	 	  else
	 	 	 	  {
	 	 	 	     if ( filedname=="用户账号" || filedname=="手机号码")
	 	 	 	       flag += 1;
	 	 	 	  }
	 	 	 }
	 	 }
	 	 if ( flag==2)
	 	 {
	 	    $(".setfield_hint").text("【用户账号】或【手机号码】必须选择一项！");
    	 	$("#setcontent select:first").focus();
    	 	return;
	 	 }
	 	 Field.login_account.index = "";
	 	 Field.nick_name.index = "";
	 	 Field.dept_id.index = "";
	 	 Field.password.index = "";
	 	 Field.mobile.index = "";
	 	 Field.duty.index = "";
     //更改Field对象
	 	 for(var j=0;j<control.length;j++){
	 	 	 val = control.eq(j).val();
	 	 	 var field = control.eq(j).attr("field");
	 	 	 if ( field=="login_account")
	 	 	   Field.login_account.index = val;
	 	 	 if ( field=="nick_name")
	 	 	   Field.nick_name.index = val;	 
	 	 	 if ( field=="dept_id")
	 	 	   Field.dept_id.index = val;
	 	 	 if ( field=="password")
	 	 	   Field.password.index = val;
	 	 	 if ( field=="mobile")
	 	 	   Field.mobile.index = val;		 	 	
	 	 	 if ( field=="duty")
	 	 	   Field.duty.index = val;
	 	 }
	 	 $("#btn_start_import").show();
	 	 $(".staff_field_content").hide();
	 }, 
	 //开始导入数据
	 start:function(index){
	 	  if ( Import.filepath=="" || Import.filepath==null)
	 	  {
	 	  	Import.showhint("请选择要导入的Excel文件！" ,false); 
	 	  	return;
	 	  }
	 	  if ($(".loading").length>0) $(".loading").remove();	 	  
	 	  $(".register_progress").show();	 	  
	 	  $("#btn_start_import").attr("disabled","disabled");	 	  
	 	  var parameter = { "relation":Field,"file":Import.filepath,"totalrecord":Import.totalrecord,"index":index,"isDel":0 };
	 	 	$.post(this.import_url,parameter,function(data){
	 	 		  //处理错误数据
	 	 		  if ( index==1)
				 	{
				 		$(".error_content").html("");
				  }
 	 		  	var errordata = data.errorData;
 	 		  	if ( errordata.length>0)
 	 		  	{
 	 		  		 var html = Array();
 	 		  		 for(var i=0;i<errordata.length;i++)
 	 		  		 {
 	 		  		 	 html.push("<span>"+errordata[i]+"</span>");
 	 		  		 }
 	 		  	   $(".error_content").html(html.join(""));
 	 		    }
 	 		     	 		    
	 	 		  var pageindex = data.index;
		 	 	 	if ( pageindex <= Import.totalpage){
		 	 	 	 	 Import.start(pageindex);
		 	 	 	 	 var percent = Math.round(pageindex/Import.totalpage*100);
		 	 	 	 	 $(".register_progress_number").text(percent+"%");
		 	 	 	 	 $(".register_progress_percent").css("width",percent+"%");
		 	 	 	}
		 	 	 	else {
            //是否有错误提示
		 	 	 		if ( $(".error_content").children().length==0)
		 	 	 		{
		 	 	 			Import.showhint("导入人员数据成功！" ,false);
		 	 	 		}
		 	 	 		else
		 	 	 		{
		 	 	 		  var err_title ="<span>操作已完成，但有<span onclick=\"$('.error_box').toggle();\" class='error_text'>错误</span>发生。</span>";
		 	 	 			$(".staffmanager_hint").html(err_title);
		 	 	 			$('.error_box').show();		 	 	 			
		 	 	 		}
		 	 	 		$("#btn_start_import").removeAttr("disabled");
		 	 	 	 	$(".register_progress_number").text("100.00%");
		 	 	 	  $(".register_progress_percent").css("width","100%");		 	 	 	 	 
		 	 	 	 	setTimeout(function() {
		 	 	 	 		 $(".register_progress").hide();
		 	 	 	 		 $(".register_progress_number").text("");
		 	 	 	     $(".register_progress_percent").css("width","0px");
		 	 	 	  },2000);
		 	 	 		//删除文件
		 	 	 		var para = { "relation":Field,"file":Import.filepath,"totalrecord":Import.totalrecord,"index":index,"isDel":1};
	 	 	      $.post(Import.import_url,para,function(data){
	 	 	      	 Import.filepath = "";
	 	 	      	 $("#filedata").val("");
		 	 	 	  });
		 	 	 	}
	 	 	});
	 },
	 ExecSQL:function(){
	 	 var dbtype = $("#dbtype").val();
	 	 if (dbtype==""){
	 	 	 this.showhint("请选择【数据库类型】",true);
	 	 	 return;
	 	 }
	 	 var server = $.trim($("#text_server").val());
	 	 if ( server==""){
	 	 	 this.showhint("请输入【数据库服务器】",true);
	 	 	 $("#text_server").focus();
	 	 	 return;
	 	 }
	 	 var database = $.trim($("#text_database").val());
	 	 if ( database==""){
	 	 	 this.showhint("请输入【数据库名】",true);
	 	 	 $("#text_database").focus();
	 	 	 return;
	 	 }	 	 
	 	 var user = $.trim($("#text_user").val());
	 	 if ( user==""){
	 	 	 this.showhint("请输入【用户账号】",true);
	 	 	 $("#text_user").focus();
	 	 	 return;
	 	 }		 	 
	 	 var pass = $.trim($("#text_password").val());
	 	 if ( pass==""){
	 	 	 this.showhint("请输入【用户密码】",true);
	 	 	 $("#text_password").focus();
	 	 	 return;
	 	 }	 	 
	 	 var sqlcomment = $.trim($("#text_sql").val());
	 	 if ( sqlcomment==""){
	 	 	 this.showhint("请输入【查询SQL语句】",true);
	 	 	 $("#text_sql").focus();
	 	 	 return;
	 	 }
	 	 var parameter = {  "dbtype":dbtype,"url":server,"dbname":database,"dbuser":user,"dbpwd":pass,"sqlcomment":sqlcomment};
	 	 $.post(this.sql_url,parameter,function(data){
	 	 	 	 if ( data.success){
		        Import.datasource = data.DataSource;
		        Import.viewData();
	       }
	       else{
		       Import.showhint(data.msg,false);
	       }
	 	 });
	 	  	 
	 },
   pageselectCallback:function(page_index){
	 	 if ( Import.issearch )
	 	   Import.searchStaff(page_index+1);
	 },
	 pageInit:function(){
	 	  var opt = {callback: Import.pageselectCallback};
      opt.items_per_page = Import.record;
      opt.num_display_entries = 5;
      opt.num_edge_entries=5;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	 },
	 searchStaff:function(pageindex){
	    $(".selected_dept_area").hide();
	    if ( !this.search_state) return;
	 	  var parameter = { "pageindex": pageindex,
	 	 	                  "dept_id": $("#text_parent_name").attr("deptid"),
	 	 	                  "login_account": $.trim($("#text_account").val()),
	 	 	                  "record":Import.record 
	 	 	                 };
      this.search_state = false;
      this.pageindex = pageindex;
	 	  $.post(this.search_url,parameter,function(returndata){
	 	 	  Import.search_state = true;
	 	 	  if ( pageindex==1 ){
	 	 	  	if ( returndata.recordcount <= Import.record){
	 	 	  		$("#Pagination").hide();
	 	 	  	}
	 	 	  	else{
		 	 	  	Import.issearch = false;
		 	 	  	var optInit = Import.pageInit();
		 	 	  	$("#Pagination").show();
		 	 	  	$("#Pagination").pagination(returndata.recordcount,optInit);
		 	 	  	Import.issearch = true;
	 	 	    }	 	 	  	
	 	 	  }
	 	 	  else{
	 	 	  	Import.issearch = true;
	 	 	  }
	 	 	  var data = returndata.datasource;
	 	 	  var html = new Array();
	 	 	  if ( data != null && data.length>0){
	 	 	  	var row = null;
		 	 	  for(var i=0;i<data.length;i++){
		 	 	  	row = data[i];
		 	 	  	html.push("<tr class='staff_table_row' login_account='"+row.login_account+"' dept_id='" + row.dept_id+"' sex='"+row.sex+"'>");		 	 	  	
		 	 	  	html.push(" <td style='float:left;width:240px;'>"+row.login_account+"</td>");
		 	 	  	html.push(" <td style='float:left;width:120px;'>"+row.nick_name+"</td>");		 	 	  	
		 	 	  	html.push(" <td style='float:left;width:235px;'>"+row.dept_name+"</td>");
		 	 	  	html.push(" <td style='float:left;width:120px;'>"+row.duty+"</td>");
		 	 	  	html.push(" <td style='float:left;width:120px;'>"+row.mobile+"</td>");
		 	 	  	html.push(" <td style='float:left;width:100px;padding-top:2px;'>");
		 	 	  	html.push("   <span class='mb_edition_button' style='margin-left:10px;' onclick='AddStaff.viewdialog(this);'></span>");
		 	 	  	html.push("   <span onclick='Import.remove(this);' class='mb_delete_button'></span></td>");		 	 	  	
		 	 	  	html.push("</tr>");
		 	 	  }
	 	 	  }
	 	 	  else{
	 	 	    html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;'class='mb_common_table_empty'>未搜索到指定的账号信息！</span>");
	 	 	  }
	 	 	  $("#table_search tbody").html(html.join(""));
	 	  });
	 },
	 remove:function(ev){
	  var curRow = $(ev).parents(".staff_table_row");
	  var login_account = curRow.attr("login_account");	  
		showDialog.Query("","确定要删除用户账号<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+login_account+"</span>");
    showDialog.callback=function(result){
  	 if(result=="Yes"){
  	 	 $.post(Import.dele_url,{"login_account":login_account},function(data){
  	 	 	 if (data.success){  	 	 	 	 
  	 	 	 	 showDialog.Success("操作成功","删除用户账号：<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+login_account+"</span>成功！");
  	 	 	 	 showDialog.callback = function(res){
  	 	 	 	 	 if (result=="Yes"){
  	 	 	 	 	 	  var page_index = Import.pageindex;
  	 	 	 	 	 	  if ( $("#table_search tbody>tr").length==1)
  	 	 	 	 	 	     page_index = page_index - 1;
  	 	 	 	 	 	  page_index = page_index<0 ? 1 : page_index;
  	 	 	 	 	    Import.searchStaff(page_index);
  	 	 	 	 	 }
  	 	 	 	 };
  	 	 	 }
  	 	 	 else{
  	 	 	 	 showDialog.Success("操作失败",data.msg);
  	 	 	 	 showDialog.callback = null;
  	 	 	 }
  	 	 });
  	 }
    };
	 } 
};

var ImportExcel = {
  //上传excel文件
	uploadfile:function(){
 	 	var file = $("#filedata").val();
 	 	if ( file == ""){
 	 		 Import.showhint("请选择导入的Excel文件！",true);
 	 		 return;
 	 	}
 	 	else{	 	 	 		 
 	 	   var suffix = file.substring(file.lastIndexOf(".")+1);
       if ( suffix!="xls" && suffix!="xlsx"){
 	        Import.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
 	       return;
       }
 	 	}
 	  $("#frm_import").submit();
	}
};

var Import_API = {
	getData:function(){
		var url = $.trim($("#api_url").val());
		if ( url==""){
			Import.showhint("请输入接口地址！",true);
			$("#api_url").focus();
			return;
		}
		$.post(url,function(data){
			 if ( typeof(data)=="object"){
			 	 for(j in data){
			 	 	 if( data[j]!=null && typeof(data[j])=="object"){
			 	 	 	 Import.datasource = data[j];
		         Import.viewData();
		         break;
			 	 	 }			 	 	 
			 	 }
			 }
		});		
	}
}

//上传文件后的返回数据
function import_callback(data)
{
	if ( data.success){
		Import.datasource = data.DataSource;
		Import.filepath = data.filepath;
		Import.totalpage = data.totalpage;
		Import.totalrecord = data.recordcount;
		Import.viewData();
		var row = data.recordcount>1?data.recordcount -1:0;
		if ( data.recordcount<=16){
			Import.showhint("上传文件共计"+row+"条数据记录！" ,false);
		}
	  else
	  	Import.showhint("上传文件共计"+row+"条数据记录，目前仅显示前"+data.DataSource.length+"条数据记录！" ,false);
	  
	}
	else{
		 Import.showhint("上传文件出现错误，请重试！",false);
	}
}

//加载部门
var LoadDept = {
  tree_Id:"",
  deptId:"",
	url:"",
	loadTree:function(url){
	    LoadDept.url = url;
		  $.getJSON(url,function(data) {
    	  if (data.success){
    	    var zTreeSetting = {
    	    	data:{
    	    		simpleData:{
    	    			enable:true
    	    		}
    	    	},
    	    	callback:{
    	    		onClick:LoadDept.TreeClick
    	    	}
    	    };  	    
    	    $("#"+LoadDept.tree_Id).show();
    		  $.fn.zTree.init($("#"+LoadDept.tree_Id),zTreeSetting, data.datasource);
    		  var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
				  var nodes = treeObj.getNodes();
				  if ( nodes.length>0)
				    setMark(nodes[0].children);
    	  }
	    });
	},
	TreeClick:function(event, treeId, treeNode)
	{
	   $(".op_dept_area").show();
		 var id = treeNode.id;
		 if ( !treeNode.isParent)
		 {
		 	 if ( treeNode.state == "0") return;
		 	 var parameter = {"deptid":treeNode.id};
		 	 $.getJSON(LoadDept.url,parameter,function(data) {
		 	 	  if (data.success)
		 	 	  {
		 	 	  	if (data.datasource.length==0)
		 	 	  	{
		 	 	  		treeNode.state = 0;
		 	 	  	}
		 	 	  	else
		 	 	  	{
			 	 	    var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
	            treeObj.addNodes(treeNode,data.datasource);
	            treeNode.state = 0;
	            if ( treeNode.length>0)
	              setMark(treeNode.children);
            }
          }
		 	 });
		 }
	},
	TreeClick2:function(event, treeId, treeNode)
	{
	   $(".op_dept_area").show();
		 var id = treeNode.id;
		 if ( !treeNode.isParent)
		 {
		 	 if ( treeNode.state == "0") return;
		 	 var parameter = {"deptid":treeNode.id};
		 	 $.getJSON(LoadDept.url,parameter,function(data) {
		 	 	  if (data.success)
		 	 	  {
		 	 	  	if (data.datasource.length==0)
		 	 	  	{
		 	 	  		treeNode.state = 0;
		 	 	  	}
		 	 	  	else
		 	 	  	{
			 	 	    var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
	            treeObj.addNodes(treeNode,data.datasource);
	            treeNode.state = 0;
	            if ( treeNode.length>0)
	              setMark(treeNode.children);	            
            }
          }
		 	 });
		 }
	}	
}