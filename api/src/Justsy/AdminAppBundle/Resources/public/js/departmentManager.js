var AddDepartment = {
	add_url:"",	
	search_url:"",
	editobj:null,
	dept_info: {"dept_id":"","parent_dept_id":"","dept_name":"","parent_dept_name":""},
	viewdialog:function(ev) {
	  this.editobj = $(ev);
    $("#dept_area").hide();
    if ($(ev).attr("class")=="mb_title_button_area")
    {
    	$("#viewAdd .title").text("添加组织部门");
    	$(".staff_add_row input").val("");
    	$(".staff_add_hint").text("");
    	AddDepartment.dept_info = {"dept_id":"","parent_dept_id":"","dept_name":"","parent_dept_name":""};
    }
    else{
    	$("#viewAdd .title").text("修改组织部门");
    	AddDepartment.dept_info.dept_id = $(ev).attr("dept_id");
    	AddDepartment.dept_info.parent_dept_id = $(ev).attr("parent_dept_id");
    	AddDepartment.dept_info.parent_dept_name = $(ev).attr("parent_dept_name");
    	AddDepartment.dept_info.dept_name = $(ev).attr("dept_name");
    	$("#text_parent_name").val(AddDepartment.dept_info.parent_dept_name);
    	$("#text_parent_name").attr("deptid",AddDepartment.dept_info.parent_dept_id);
    	$("#text_dept_name").val(AddDepartment.dept_info.dept_name);
    }
    $("#viewAdd").show();
	},
	search_name:function(){
	  var dept_name =	$.trim($("#text_parent_name").val());
	  if (dept_name==""){
	  	this.showhint("请输入上级部门");
	  	return;
	  }
	  $.post(this.search_url,{"dept_name":dept_name},function(data){
	  	var html=new Array();
	  	if ( data.success){
	  		var datatable = data.datasource;
	  		if ( datatable!=null && datatable.length>0){
	  			var row = null;
	  			for(var i=0;i<datatable.length;i++){
	  				row = datatable[i];
	  				html.push("<li onclick='AddDepartment.selectdept(this);' deptid='"+ row.deptid+"'>"+row.deptname+"</li>");
	  			}
	  			$(".depart_searchArea").show();
	  	    $(".depart_searchArea").html(html.join(""));
	  		}
	  		else{
	  			$(".depart_searchArea").html("");
	  			$(".depart_searchArea").hide();
	  			AddDepartment.showhint("未查询到部门数据");
	  		}
	  		
	  	}
	  	else{
	  		 $(".depart_searchArea").html("");
	  		 $(".depart_searchArea").hide();
	  		 AddDepartment.showhint(data.msg);
	  	}
	  	
	  });		
	},
	selectdept:function(ev){
		var deptid = $(ev).attr("deptid");		
		$("#text_parent_name").attr("deptid",deptid);
		$("#text_parent_name").val($(ev).text());
		$(".depart_searchArea").hide();
	},
	Save:function(){
		var parent_id = $("#text_parent_name").attr("deptid");
		if ( parent_id=="")
		{
			$("#text_parent_name").focus();
			this.showhint("请输入部门所属的上级部门");
			return;
		}
		var dept_name = $.trim($("#text_dept_name").val());
		if ( dept_name==""){
			this.showhint("请输入部门名称!");
			$("#text_dept_name").focus();
			return;
		}
		var dept_info = AddDepartment.dept_info;
		if (dept_info.dept_id!="")
		{
		  if ( dept_info.parent_dept_id == parent_id && dept_info.dept_name == dept_name){
		  	this.showhint("未作任何修改!");
			  $("#text_dept_name").focus();
			  return;
		  }
	  }
		var parameter = { "p_deptid":parent_id,"deptid":AddDepartment.dept_info.dept_id,"deptname":dept_name };
		$.post(this.add_url,parameter,function(data){
			 if (data.success){
			 	 if ( parameter.dept_id==""){  //添加
			 	   AddDepartment.showhint("添加组织部门名称成功！");
			 	   $("#text_parent_name").attr("deptid","");
			 	   $(".staff_add_row input").val("");
			 	   Import.search_import(1);
			   }
			   else {			   	 	
			     var editobj = AddDepartment.editobj;
			     editobj.parents("tr").find(".deptname").text(dept_name);
			     editobj.attr("dept_name",dept_name);
			     editobj.attr("parent_dept_name",$("#text_parent_name").val());
			   	 $("#viewAdd").hide();
			   }
			 }
			 else{
			 	 AddDepartment.showhint(data.msg);
			 }
		});
 	  
	},
	showhint:function(message){
		$("#viewAdd .staff_add_hint").text(message);
		setTimeout(function() { $("#viewAdd .staff_add_hint").text("");},2000);
	}
};

var Field ={
	 dept_id: {"zh_name":"部门ID","index":"","isNull":0,"hidden":true},
	 dept_name:   {"zh_name":"部门名称","index":"","isNull":0,"hidden":false},
	 parent_dept: {"zh_name":"上级部门","index":"","isNull":0,"hidden":false},
	 parent_dept_id: {"zh_name":"上级部门ID","index":"","isNull":0,"hidden":true},
};

var Import = {
	 error_data:"",
	 import_url:"",
	 sql_url:"",
	 search_url:"",
	 statistics_url:"",
	 dele_url:"",
	 identical_url:"",
	 datasource:null,
	 import_type:1,
	 search_state:true,
	 issearch:false,
	 record:16,
	 filepath:"",
	 totalrecord:0,
	 totalpage:0,
	 importdata_type:true, //导入数据类型(true:部门名称式;false：部门ID式)
	 pageindex:0,
	 export_err:Array(),	 
	 radio_change:function(ev){
	 	 var id = $(ev).attr("id");
	 	 $("#setcontent>div").hide();
	 	 if ( id=="type1"){
	 	   $("#setcontent>div").eq(1).show();	 
	 	   $("#setcontent>div").eq(2).show();
	 	 }
	 	 else{
	 	 	 $("#setcontent>div").eq(0).show();	 
	 	   $("#setcontent>div").eq(1).show();
	 	   $("#setcontent>div").eq(3).show();
	 	 }	 	 
	 },
	 viewdialog:function(){
	 	 $("#viewImport").show();	 	 
	 },
	 step:function(state){
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
	 	  $(".staffmanager_hint").html("");
	 	 else {
	 	 	$(".staffmanager_hint").html(message);
	 	 	if ( isempty )
	 	 	  setTimeout(function(){ $(".staffmanager_hint").html(""); },2000);
	 	 }
	 },
	 viewData:function(){	 	
	 	 //初始相关操作
	   Field.dept_id = {"zh_name":"部门ID","index":"","isNull":0,"hidden":true};
	   Field.dept_name = {"zh_name":"部门名称","index":"","isNull":0,"hidden":false};
	   Field.parent_dept = {"zh_name":"上级部门","index":"","isNull":0,"hidden":false};
	   Field.parent_dept_id = {"zh_name":"上级部门ID","index":"","isNull":0,"hidden":true};
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
		 	   	 	 temphtml.push("<td ondblclick='Import.setRoot(this);' "+td_style+">"+ row[j] +"</td>");	 	   	 	 
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   	 temphtml.push("</tr>");
		 	   	 html_body.push(temphtml.join(""));
		 	   }
		 	   else{
		 	   	 html_body.push("<tr class='staff_table_row'>");
		 	   	 for(k in row){
		 	   	 	 html_body.push("<td ondblclick='Import.setRoot(this);'"+td_style+">"+ row[k] +"</td>");	 	
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
	 setRoot:function(evn)
	 {
	    if ( $.trim($("#rootdeptid").val())=="")
	      $("#rootdeptid").val($.trim($(evn).text()));
	 },
	 //显示字段映射关系面板
	 viewField:function(){
	 	 $(".staff_field_content").show();
     var html = new Array();
     if ( $(".staff_field_content .staff_field_row").length==0){
			 for(j in Field){
			 	 var item = Field[j];
			 	 html.push("<div class='staff_field_row'"+(item.hidden?" style='display:none;'":"")+">");
			 	 html.push("<div>");
			 	 var must = false;
			 	 var fieldname = "";
			 	 for(i in item){
			 	 	 if ( i=="zh_name"){
			 	 	  html.push("<span class='staff_fieldname'>请指定<span style='padding-left:2px;padding-right:2px;color:black;'>"+item[i]+"</span>映射关系：</span>");
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
	 	 var control = $(".staff_field_row .mb_combox:visible");
	 	 //判断是否选择
	 	 var val = "";
	 	 for(var i=0;i<control.length;i++){
	 	 	 val =  control.eq(i).val();
	 	 	 if ( val=="" && control.eq(i).attr("must")=="1"){
	 	 	 	 $(".setfield_hint").text("必须指定【"+control.eq(i).attr("fieldname")+"】字段的映射关系！");
	 	 	 	 control.eq(i).focus();
	 	 	 	 return;
	 	 	 }
	 	 }
	 	 Field.dept_id.index = "";
	 	 Field.dept_name.index = "";
	 	 Field.parent_dept.index = "";
	 	 Field.parent_dept_id.index = ""; 
     //更改Field对象
	 	 for(var j=0;j<control.length;j++){
	 	 	 val = control.eq(j).val();
	 	 	 var field = control.eq(j).attr("field");
	 	 	 if ( field=="dept_id")
	 	 	   Field.dept_id.index = val;
	 	 	 if ( field=="dept_name")
	 	 	   Field.dept_name.index = val;
	 	 	 if ( field=="parent_dept")
	 	 	   Field.parent_dept.index = val;
	 	 	 if ( field=="parent_dept_id")
	 	 	   Field.parent_dept_id.index = val;	 	 	   
	 	 }
	 	 if ( $(".dept_setField_hint #type2").attr("checked")==null)
	 	    Import.importdata_type = true ;
	 	 else
	 	 	  Import.importdata_type = false;
	 	 $("#btn_start_import").show();
	 	 $(".staff_field_content").hide();
	 },
	 //开始导入数据
	 start:function(index){
	 	  var rootdeptid = $.trim($("#rootdeptid").val());
	 	  if ( rootdeptid=="")
	 	  {
	 	  	$("#viewImport .staffmanager_hint").text("请选择根部门id");
	 	  	$("#rootdeptid").focus();
	 	  	return;
	 	  }
	 	  else
 	  	{
 	  		$("#viewImport .staffmanager_hint").text("");
 	  	}
      if ( index==1)
      {
      	$("#btn_start_import").attr("disabled","disabled");
	      $(".import_process").show();
	      $(".import_process_bar").css("width","1px");
	      $(".import_process_text").text("正在开始导入数据，请稍候...");
      }
	 	  var parameter = { "rootdeptid":rootdeptid,"relation":Field,"datatype":Import.importdata_type ? 1 : 0,"file":Import.filepath,"totalrecord":Import.totalrecord,"index":index};
	 	 	$.post(this.import_url,parameter,function(data){
	 	 		$(".loading").hide();
	 	 	  var pageindex = data.index;
	 	 	  var errlist = data.errorData;
	 	 	  if ( errlist.length>0)
	 	 	  {
	 	 	  	 var html = Array();
	 	 	  	 for(var i=0;i< errlist.length;i++)
	 	 	  	 {
	 	 	  	 }
	 	 	  	 Import.export_err.push();	 	 	  	
	 	 	  }
	 	 	 	if ( pageindex <= Import.totalpage){
	 	 	 	 	 Import.start(pageindex);
	 	 	 	 	 //显示进度
	 	 	 	 	 var _width = (index / Import.totalpage)*100;
	 	       $(".import_process_bar").css("width",_width+"%");
	 	       _width = _width.toFixed(2)+"%";
	 	       $(".import_process_text").text("当前导入进度: "+_width);
	 	 	 	}
	 	 	 	else{
	 	 	 	  $.post(Import.statistics_url,{"file":Import.filepath},function(data){
	 	 	 	    if ( data.success){
	 	 	 	   	  Import.showhint("导入部门数据成功！",true);
	 	 	 	   	  $("#btn_start_import").removeAttr("disabled");
	 	          $(".import_process_bar").css("width","100%");
	 	          $(".import_process_text").text("100.00%");
	 	          setTimeout(function() {
	 	          	 $(".import_process").hide();   
	 	          	 $(".import_process_bar").css("width","1px");
	 	          	 $(".import_process_text").text(" ");
	 	          	 Import.setdeptpath();
	 	          },2000);
	 	 	 	   	}
	 	 	 	   	else{
	 	 	 	   	  Import.showhint("导入部门数据失败！",false);
	 	 	 	   	}
	 	 	 	  });	 	 	 	   	 
	 	 	 	}
	 	 	});
	 },	 
	 //设置部门path
	 setdeptpath:function()
	 {
	 	  var iscomplete = 0;
			var parameter = { "module":"Dept","action":"setDeptPath" };
			$.post(this.identical_url,parameter,function(returndata){
				 if ( iscomplete==0 && returndata.success )
				 {
				 	 if (  returndata.iscomplete==false)
				 	  Import.setdeptpath();
				 }
			});
	 },
	 //查看错误提示
	 viewError:function(){
	 	 var error = this.error_data;
	 	 if ( error.length>0){
	 	 	 $("#viewError").show();
	 	 	 var html = new Array();
	 	 	 for(var i=0;i<error.length;i++){
	 	 	 	 html.push("<span class='depart_errorhint'>"+error[i]+"</span>");
	 	 	 }
	 	 	 $("#viewError .content").html(html.join(""));
	 	 }
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
	 	   Import.search_import(page_index+1);
	 },
	 pageInit:function(){
	 	  var opt = {callback: Import.pageselectCallback};
      opt.items_per_page = Import.record;
      opt.num_display_entries = 2;
      opt.num_edge_entries=2;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	 },
	 delPid:function()
	 {
	    $("#text_pid").val("");
	    $(".del_pid_icon").hide();
	    DeptOrder.pid = "";	    
   },
	 search_import:function(pageindex){
	 	 this.pageindex = pageindex<=0 ? 1:pageindex;	 	 
	 	 $("#btnSearch").attr("disabled","disabled");
	 	 var parameter = { "pageindex":this.pageindex,
	 	 	                 "pid":DeptOrder.pid,"dept_name": $.trim($(".dept_search_area input:last").val()),
	 	 	                 "record":Import.record,"first":$("#text_pid").attr("state")
	 	 	               };
	 	 $("#text_pid").attr("state","");
     this.pageindex = pageindex;
	 	 $.post(this.search_url,parameter,function(returndata){
	 	    $("#btnSearch").removeAttr("disabled");
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
		 	 	  	html.push("<tr class='staff_table_row'>");
		 	 	  	html.push(" <td style='float:left;width:355px;' class='deptname'>"+row.deptname+"</td>");
		 	 	  	html.push(" <td style='float:left;width:85px;padding:1px;'><input deptid='"+row.deptid+"' type='text' class='dept_order' onkeyup='Import.enableNaN(this);' maxlength='10' value='"+row.noorder+"' style='width:100%;text-align:center;'/></td>");
		 	 	  	html.push(" <td style='float:left;padding-top:2px;width:105px;border-right:none;'>");
		 	 	  	html.push("   <span class='mb_edition_button' style='margin-left:10px;' parent_dept_name='"+row.parent_dept_name+"' dept_id='"+row.deptid+"' parent_dept_id='"+row.parent_dept_id+"' dept_name='"+row.deptname+"'      onclick='AddDepartment.viewdialog(this);'></span>");
		 	 	  	html.push("   <span onclick='Import.remove(this);' dept_name='"+row.deptname+"' dept_id='"+row.deptid+"' class='mb_delete_button'></span></td>");		
		 	 	  	html.push("</tr>");
		 	 	  }
	 	 	  }
	 	 	  else{
	 	 	  	html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;'class='mb_common_table_empty'>未搜索到部门信息！</span>");
	 	 	  }
	 	 	  $("#table_search tbody").html(html.join(""));
	 	 });
	 },
	 enableNaN:function(evn)
	 {
	    var tmptxt= $.trim($(evn).val());
      if ( tmptxt != "")
      $(evn).val(tmptxt.replace(/\D|^0/g,''));
   },
	 remove:function(ev){
	  var curRow = $(ev).parents(".staff_table_row");
	  var dept_id = $(ev).attr("dept_id");
	  var dept_name = $(ev).attr("dept_name");
		showDialog.Query("","确定要删除部门名称<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+dept_name+"</span>");
    showDialog.callback=function(result){
  	 if(result=="Yes"){
  	 	 $.post(Import.dele_url,{"dept_id":dept_id},function(data){
  	 	 	 if (data.success){	 	 	 	 
  	 	 	 	 showDialog.Success("操作成功","删除部门名称<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+dept_name+"</span>成功！");
  	 	 	 	 showDialog.callback = function(res){
  	 	 	 	 	 if (result=="Yes") {
  	 	 	 	 	 	 var page_index = Import.pageindex;
  	 	 	 	 	   if ($("#table_search tbody>tr").length==1)
  	 	 	 	 	     page_index = page_index - 1;
  	 	 	 	 	   page_index = page_index < 0 ? 1 : page_index;
  	 	 	 	 	   Import.search_import(page_index);
  	 	 	 	 	   //同时删除节点
  	 	 	 	 	   var treeObj = $.fn.zTree.getZTreeObj("tree_menu");
               var node = treeObj.getNodeByParam("id",dept_id, null);
               treeObj.removeNode(node);
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
		Import.totalrecord = data.recordcount;
		Import.totalpage = data.total_page;
		Import.viewData();
		var row = data.recordcount > 1 ? data.recordcount - 1 : 0;
		Import.showhint("上传文件共计"+ row +"条数据记录，目前仅显示前"+data.DataSource.length+"条数据记录！" ,false);
	}
	else{
		 Import.showhint("上传文件出现错误，请重试！",false);
	}
}
								
var DeptOrder ={
	pid:"",
	deptid:"",
	getdepart_url:"",
	editorder_url:"",
	loadTree:function(){
		$.getJSON(this.getdepart_url,function(data) {
			  if (data.success){
			    var zTreeSetting = {
			    	data:{
			    		simpleData:{
			    			enable:true
			    		}
			    	},
			    	callback:{
			    		onClick:DeptOrder.TreeClick
			    	}
			    };
				  $.fn.zTree.init($("#tree_menu"), zTreeSetting, data.datasource);
				  var treeObj = $.fn.zTree.getZTreeObj("tree_menu");
				  var nodes = treeObj.getNodes();
				  if ( nodes.length>0)
				    setMark(nodes[0].children);
			  }
	  });
	},
	TreeClick:function(event, treeId, treeNode)
	{
		 var id = treeNode.id;
		 if ( !treeNode.isParent)
		 {
		 	 if ( treeNode.state == "0") return;
		 	 var parameter = {"deptid":treeNode.id};
		 	 $.getJSON(DeptOrder.getdepart_url,parameter,function(data) {
		 	 	  if (data.success)
		 	 	  {
		 	 	  	if (data.datasource.length==0)
		 	 	  	{
		 	 	  		treeNode.state = 0;
		 	 	  	}
		 	 	  	else
		 	 	  	{
			 	 	    var treeObj = $.fn.zTree.getZTreeObj("tree_menu");
	            treeObj.addNodes(treeNode,data.datasource);
	            treeNode.state = 0;
	            setMark(treeNode.children);
            }
          }		 	 	  
		 	 });
		 }
		 
		 $("#text_pid").val(treeNode.name);
		 $(".del_pid_icon").show();
		 DeptOrder.pid = id;
		 $("#text_searchname").val("");
		 Import.search_import(1);
	},
	edit_order:function(ev){
		var control = $("#table_search tbody tr input");
		var noorders = Array();
		var deptid="",val="";
		for(var i=0;i<control.length;i++){
			val = control.eq(i).val();
			if ( val=="") continue;
			deptid = control.eq(i).attr("deptid");
			noorders.push({"deptid":deptid,"noorder":val});
		}
		$.post(this.editorder_url,{"noorders":noorders},function(data){
			if ( data.success)
		  {
				showDialog.Success("操作成功",data.msg);
				Import.search_import(Import.pageindex);
		  }
			else
				showDialog.Success("操作失败",data.msg);
			showDialog.callback = null;
		});
	},
  showdept:function()
	{
	    if ( $("#depttree").children().length==0)
	    {
	        LoadDept.tree_Id = "depttree";
	        LoadDept.inputText = $("#text_parent_name");
	        LoadDept.loadTree(this.getdepart_url);
	    }
	    $("#dept_area").show();
	}	
}

//加载部门
var LoadDept = {
  tree_Id:"",
  deptId:"",
	url:"",
	inputText:null,
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
	   $("#btnSelected").show();
		 var id = treeNode.id;
		 if ( !treeNode.isParent)
		 {
		 	 if ( treeNode.state == "0") return;
		 	 
		 	 LoadDept.deptId = treeNode.id;
		 	 LoadDept.deptName = treeNode.name;
		 	 
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
	            setMark(treeNode.children);
            }
          }
		 	 });
		 }
	},
  selected_dept:function()
	{
	    var treeObj = $.fn.zTree.getZTreeObj("depttree");
		  var node = treeObj.getSelectedNodes();
		  if ( node.length>0)
		  {
		    var treenode = node[0];
		    LoadDept.inputText.val(treenode.name);
		    LoadDept.inputText.attr("deptid",treenode.id);
		    $("#dept_area").hide();
		  }
  }
}