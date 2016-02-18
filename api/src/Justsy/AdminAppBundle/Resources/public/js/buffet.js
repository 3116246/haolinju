var Setting = {
	 edit_url:"",
	 searchbyid_url:"",
	 search_url:"",
	 delete_url:"",
	 id:0,
   search_state:true,
	 issearch:false,
	 record:16,	
	 showedit:function(id){
		$("#edit_dialog").show();
		this.id = parseInt(id);
		if ( this.id==0){
			$("#textKey").val("");
			$("#textTitle").val("");
			$("#textContent").val("");
		}
		else{
			$.post(this.searchbyid_url,{"id":this.id},function(data){
				 $(".buffet_hint").text("");
				 if ( data.success){
				 	 var row = data.data[0];
				 	 $("#textKey").val(row.keyword);		 	 
				 	 $("#textTitle").val(row.title);
				 	 $("#textContent").val(row.content);
				 }
				 else{
				 }
			});
		}		
	 },
	 edit:function(){
		 var keyword = $.trim($("#textKey").val());
		 if (keyword==""){
		 	 this.hint("请输入自助关键字");
		 	 return;
		 }
		 var title = $.trim($("#textTitle").val());
		 if ( title==""){
		 	 this.hint("请输入自助标题！");
		 	 return;
		 }
		 var content = $.trim($("#textContent").val());
		 if ( content==""){
		 	 this.hint("请输入自助内容");
		 	 return;
		 }
		 var parameter = {"id":this.id,"keyword":keyword,"title":title,"content":content };		
		 $.post(Setting.edit_url,parameter,function(data){
			 if ( data.success){
			 	 Setting.hint(parameter.id=="" ? "添加问题设置成功！" : "修改问题设置成功！");
			 	 setTimeout(function(){
			 	 	 $("#edit_dialog").hide();
			 	 },2000);
			 }
			 else{
			 }
		 });
	 },
	 removeRow:function(id){
		 this.id = parseInt(id);
     var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除记录后将无法恢复，请谨慎操作！</span>";
		 showDialog.Query("",html);
     showDialog.callback=function(result){
	  	 if(result=="Yes"){
	  	 	 $.post(Setting.delete_url,{"id":id},function(data){
	  	 	 	 if (data.success){
	  	 	 	 	 showDialog.Success("提示！","删除该自助设置信息成功！");
	  	 	 	 	 showDialog.callback = function(res){
	  	 	 	 	 	 if (result=="Yes"){
	  	 	 	 	 	 	 $(".mb_tables #"+Setting.id).remove();
	  	 	 	 	 	 }
	  	 	 	 	 };
	  	 	 	 }
	  	 	 	 else{
	  	 	 	 	 showDialog.Success("警告！",data.msg);
	  	 	 	 	 showDialog.callback = null;
	  	 	 	 }
	  	 	 });
	  	 }
  	 }		
	 },
   pageselectCallback:function(page_index){
	 	 if ( Setting.issearch )
	 	   Setting.buffet_search(page_index+1);
	 },
	 pageInit:function(){
	 	  var opt = {callback: Setting.pageselectCallback};
      opt.items_per_page = Setting.record;
      opt.num_display_entries = 5;
      opt.num_edge_entries=5;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	 },
	 buffet_search:function(pageindex){
	   if ( !this.search_state) return;
	     this.search_state = false;
	     var parameter = {
	     	                 "keyword":$.trim($("#searchKeyword").val()),
	     	                 "record":this.record,
	     	                 "pageindex":pageindex
	     	               };
		 	 $.post(this.search_url,parameter,function(returndata){
		 	 	  Setting.search_state = true;
		 	 	  if (returndata.success){
			 	 	   if ( pageindex==1 ){
				 	 	  	if ( returndata.recordcount <= Setting.record){
				 	 	  		$("#Pagination").hide();
				 	 	  	}
				 	 	  	else{
					 	 	  	Setting.issearch = false;
					 	 	  	var optInit = Setting.pageInit();
					 	 	  	$("#Pagination").show();
					 	 	  	$("#Pagination").pagination(returndata.recordcount,optInit);
					 	 	  	Setting.issearch = true;
				 	 	    }
			 	 	   }
			 	 	   else{
			 	 	   	Setting.issearch = true;
			 	 	   }
			 	 	   Setting.fulldata(returndata.datasource);
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
	 	 	  	html.push("<tr id='"+row.id+"'>");
	 	 	  	html.push(" <td width='140' align='center'>"+row.date+"</td>");
  		    html.push(" <td width='550'><span style='padding-left:8px;'>"+row.keyword+"</span></td>");
  		    html.push(" <td width='90' align='center'>"+row.nick_name+"</td>");
  		    html.push(" <td width='98' style='border-right:none;' align='center'><span style='margin-left:10px;' onclick='Setting.showedit("+row.id+");' title='编辑/查看' class='mb_edition_button'></span>");
  		    html.push("                    <span style='margin-left:10px;' onclick='Setting.removeRow("+row.id+");' class='mb_delete_button' title='删除' onclick=''></span></td>");
  		    html.push("</tr>");
 	 	   }
		 }
		 else{
		   html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		 }
		 $("#table_search tbody").html(html.join(""));	
   },
	 hint:function(message){
			message = message==null ? "":message;
			$(".buffet_hint").text(message);
			setTimeout(function(){
				$(".buffet_hint").text("");
			},2000);
	 }
};

var HotLine = {
	 default_staff:"",
	 search_url:"",
	 edit_hotline_url:"",
	 editvisit_url:"",
	 eidtscheme_url:"",
	 delete_url:"",
	 delete_scheme_url:"",
	 delete_visit_url:"",
	 schemedesc_url:"",
	 visit_url:"",
	 searchbyid_url:"",
	 issearch:false,
	 record:5,
	 search_state:true,
	 schemeid:"",
	 visitid:"",
	 hotid:"",
	 editpanel:null,
	 flag:1, //1:热线解决方案;2:热线回访
	 curpageindex:1,
	 mindate:"",
	 maxdate:"",
	 setdate:function(){
	 	 var date = new Date();
	 	 var setting = {
	 	 	               "skin":"whyGreen",
	 	 	               "minDate":date.getFullYear()+"-"+(date.getMonth()-1)+"-"+date.getDate(),
	 	 	               "maxDate":date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate(),
	 	 	               "dateFmt":"yyyy-MM-dd HH:mm"
	 	               };
	 	 WdatePicker(setting);	 	 
	 },
	 ViewEdit:function(ev){
	 	 this.editpanel = $("#edit_dialog");
	 	 var classname = $(ev).attr("class");
	 	 if (classname=="mb_edition_button"){
	 	 	 this.schemeid = $(ev).parents(".schemedesc").attr("schemeid");
	 	 	 var control = $(ev).parents(".hotline_schemeContent").children();
	 	 	 $("#edit_scheme #scheme_date").val(control.eq(0).find("span").text());
	 	 	 $("#edit_scheme #scheme_staff").val(control.eq(1).find("span").text());
	 	 	 $("#edit_scheme #scheme_content").val($(ev).parents(".hotline_schemeContent").prev().text()); 	  	 	 
	 	 }
	 	 else {
	 	 	 this.hotid = "";
	 	 	 $("#edit_dialog").show();
	 	 	 $("#edit_dialog input").val("");
	 	 	 $("#edit_dialog #textReceive").val(this.default_staff);
	 	 	 $("#edit_dialog input").attr("disabled",false);
	 	 	 $("#hotline_number").attr("disabled",true);
	 	 	 $("#combox_source").attr("disabled",false);
	 	 	 $("#combox_source").val(0);
	 	 	 $("#combox_source>option[value='1']").hide();
	 	 	 $("#receipt_date").val(this.getcurDate());
	 	 	 $("#hotline_number").val("热线编号");
	 	 	 $("#textcontent").val("");
	 	 	 $("#comobx_scheme").val(0);
	 	 	 $("#combox_grade").val(0);
	 	 	 $("#textcontent").attr("disabled",false);
	 	 	 $("#textcontent").val("");	 	 	 
	 	 }
	 },
   pageselectCallback:function(page_index){
	 	 if (HotLine.issearch )
	 	   HotLine.SearchData(page_index+1);
	 },
	 pageInit:function(){
	 	  var opt = {callback: HotLine.pageselectCallback};
      opt.items_per_page = HotLine.record;
      opt.num_display_entries = 5;
      opt.num_edge_entries=5;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	 },
   SearchData:function(pageindex){
   	  this.curpageindex=pageindex;
	    if ( !this.search_state) return;
	     this.search_state = false;
	     var parameter = { "startdate":$(".search_area #startdate").val(),
	     	                 "enddate":$(".search_area #enddate").val(),
	     	                 "keyword":$.trim($(".search_area #searchKeyword").val()),
	     	                 "record":this.record,
	     	                 "pageindex":pageindex };
		 	 $.post(this.search_url,parameter,function(returndata){
		 	 	  HotLine.search_state = true;
		 	 	  if (returndata.success){
			 	 	   if ( pageindex==1 ){
				 	 	  	if ( returndata.recordcount <= HotLine.record){
				 	 	  		$("#Pagination").hide();
				 	 	  	}
				 	 	  	else{
					 	 	  	HotLine.issearch = false;
					 	 	  	var optInit = HotLine.pageInit();
					 	 	  	$("#Pagination").show();
					 	 	  	$("#Pagination").pagination(returndata.recordcount,optInit);
					 	 	  	HotLine.issearch = true;
				 	 	    }
			 	 	   }
			 	 	   else{
			 	 	   	HotLine.issearch = true;
			 	 	   }
			 	 	   HotLine.fulldata(returndata.datasource);
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
	 	 	  	if ( i == data.length-1)
	 	 	  	  html.push("<tr colspan='4' id='"+row.id+"' style='height:80px;border-bottom:none;'>");
	 	 	  	else
	 	 	  		html.push("<tr colspan='4' id='"+row.id+"' style='height:80px;'>");	 	 	  		 	
	 	 	  	html.push("<td style='display:block;height:80px;border-right:none;'>");
	 	 	  	html.push("<div class='hotline_search_row1'>");
	 	 	  	html.push("  <span class='hotline_number'>"+row.number+"</span>");
	 	 	  	html.push("  <span class='hotline_date'>"+row.receivedate+"</span>");
	 	 	  	html.push("  <span class='hotline_name'>"+row.name+"</span>");
	 	 	  	html.push("  <span class='hotline_content'>"+row.content+"</span>");
	 	 	  	html.push(" </div>");
	 	 	  	html.push("<div class='hotline_search_row2'>");
	 	 	  	html.push("  <span class='hotline_label'>热线来源：<span>"+row.source+"</span></span>");
	 	 	  	html.push("  <span class='hotline_label'>响应级别：<span class='hotline_grad' >"+row.grade_desc+"</span></span>");
	 	 	  	html.push("  <span class='scheme_visit_style' flag='"+row.scheme+"' onclick='HotLine.viewScheMe(this);' style='margin-left:50px;'>解决方案</span>");
	 	 	  	html.push("  <span class='scheme_visit_style' flag='"+row.visit+"'  onclick='HotLine.viewVisit(this);'>后续回访</span>");
	 	 	  	html.push("  <span class='hotline_label'>");
	 	 	  	html.push("    <span style='float:left;color:#bbb;'>热线操作：</span>");
	 	 	  	html.push("    <span class='mb_edition_button' title='查看/编辑' onclick='HotLine.showEdit("+row.id+");' style='margin:7px 0 0 5px;'></span>");
	 	 	  	html.push("    <span onclick='HotLine.removeRow(this);' class='mb_delete_button' title='删除' style='margin:7px 0 0 15px'></span>");
	 	 	  	html.push("  </span>");
	 	 	  	html.push(" </div>");
	 	 	  	html.push("</td></tr>");
 	 	   }
		 }
		 else{
		   html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		 }
		 $("#table_search tbody").html(html.join(""));
   },
   //显示修改热级
   showEdit:function(id){
   	 this.hotid = id;
   	 $.post(this.searchbyid_url,{"hotid":id},function(data){
   	 	  if (data.success){
   	 	  	$("#edit_dialog").show();
   	 	  	var datas = data.returndata;
   	 	  	if ( datas.length>0){
   	 	  		var row = datas[0];
   	 	  		//接收情况
   	 	  		$("#hotline_number").val(row.number);
   	 	  		if ( row.receivestaff==""){
   	 	  		  $("#textReceive").val(HotLine.default_staff);
   	 	  		  $("#textReceive").attr("disabled",true);
   	 	  		}
   	 	  		else{
   	 	  			$("#textReceive").val(row.receivestaff);
   	 	  			$("#textReceive").attr("disabled",false);
   	 	  		}
   	 	  		if ( row.source=="1"){
   	 	  		  $("#combox_source").attr("disabled",true);
   	 	  		  $("#receipt_date").attr("disabled",true);
   	 	  		  $("#text_worknum").attr("disabled",true);
   	 	  		  $("#text_workname").attr("disabled",true);
   	 	  		  $("#textcontent").attr("disabled",true);
   	 	  		}
   	 	  		else{
   	 	  			$("#combox_source").attr("disabled",false);
   	 	  			$("#receipt_date").attr("disabled",false);
   	 	  			$("#text_worknum").attr("disabled",false);
   	 	  			$("#text_workname").attr("disabled",false);
   	 	  			$("#textcontent").attr("disabled",false);
   	 	  		}
   	 	  		$("#receipt_date").val(row.receivedate);
   	 	  	  $("#combox_source").val(row.source);
   	 	  	  //员工情况
   	 	  	  $("#text_worknum").val(row.staff_number);
   	 	  	  $("#text_workname").val(row.name);   	 	  	  
   	 	  	  $("#text_dept1").val(row.dept1);
   	 	  	  $("#text_dept2").val(row.dept2);
   	 	  	  $("#text_address").val(row.address);
   	 	  	  $("#text_workduty").val(row.duty);
   	 	  	  $("#text_in_date").val(row.in_date);
   	 	  	  $("#textcontact").val(row.contact);
   	 	  	  if ( row.source=="1"){
   	 	  	  	 $("#text_dept1").attr("readonly",true);
   	 	  	  	 $("#text_workduty").attr("readonly",true);
   	 	  	  	 $("#text_dept1").css({"background":"#eee"});
   	 	  	  	 $("#text_workduty").css({"background":"#eee"});
   	 	  	  }
   	 	  	  else{
   	 	  	  	$("#text_dept1").attr("readonly",false);
   	 	  	  	$("#text_workduty").attr("readonly",false);
   	 	  	  	$("#text_dept1").css({"background":"white"});
   	 	  	  	$("#text_workduty").css({"background":"white"});
   	 	  	  }
   	 	  	  //处理情况
   	 	  	  $("#textcontent").val(row.content);
   	 	  	  $("#comobx_scheme").val(row.scheme);
   	 	  	  $("#combox_grade").val(row.grade);
   	 	  	}
   	 	  }   	 	 
   	 });
   },
   viewScheMe:function(ev){
   	 this.flag = 1;
   	 HotLine.hotid = $(ev).parents("tr").attr("id");
   	 //显示热线
   	 var control = $(ev).parents("tr");
   	 $("#hotlinedesc").text(control.find(".hotline_content").text());
   	 var html = "接收日期：<span>"+control.find(".hotline_date").text()+"</span>";
   	 $("#hotline_receivedate").html(html);
   	 html = "咨询人员：<span>"+control.find(".hotline_name").text()+"</span>";
   	 $("#hotline_receivename").html(html);
   	 html =  "响应级别：<span>"+control.find(".hotline_grad").text()+"</span>";
   	 $("#hotline_receivegrade").html(html);
   	 $(".hotline_scheme_visit").hide();
   	 $("#scheme_area").show(); 	 
   	 $("#edit_scheme").show();  
   	 $("#delRecord").hide(); 	 
   	 this.schemeid = "";
   	 if ( $(ev).attr("flag")=="0"){
   	 	 $("#scheme_date").val(HotLine.getcurDate());
	 	   $("#scheme_staff").val(HotLine.default_staff);
   	   $("#scheme_content").val("").focus();
   	 }
   	 else{
   	 	 $.post(this.schemedesc_url,{"hotid":this.hotid},function(data){
	 	 	 	 if(data.success){
	 	 	 	 	 $("#delRecord").show();
	 	 	 	 	 var datasource = data.datasource;
	 	 	 	 	 var row = datasource[0];
		 	 	 	 HotLine.schemeid = row.id;
		 	 	 	 $("#scheme_date").val(row.date);
	 	       $("#scheme_staff").val(row.scheme_staff);
	 	       $("#scheme_content").val(row.scheme);
	 	 	 	 }
   	   });
   	 }
   	 
   	 
   },
   viewVisit:function(ev){
   	 if ( $(ev).prev().attr("flag")=="0"){
   	 	 var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>热线尚未进行解决，不允许登记热线回访记录！</span>";
		   showDialog.Error("",html);
       showDialog.callback=null;
       return;
   	 }
   	 this.flag = 2;
     //状态处理
   	 $(".hotline_scheme_visit").hide();
   	 $("#visit_area").show();
   	 HotLine.hotid = $(ev).parents("tr").attr("id");
   	 var control = $(ev).parents("tr");
   	 $("#hotlinedesc").text(control.find(".hotline_content").text());
   	 var html = "接收日期：<span>"+control.find(".hotline_date").text()+"</span>";
   	 $("#hotline_receivedate").html(html);
   	 html = "咨询人员：<span>"+control.find(".hotline_name").text()+"</span>";
   	 $("#hotline_receivename").html(html);
   	 html =  "响应级别：<span>"+control.find(".hotline_grad").text()+"</span>";
   	 $("#hotline_receivegrade").html(html);   	 
   	 $("#edit_scheme").show();
   	 $("#delRecord").hide();
   	 this.visitid = "";
   	 if ( $(ev).attr("flag")=="0"){
   	 	 $("#visit_area input").val("");
   	   $("#visit_area textarea").val("");
   	   $("#visit_area select").val("");
   	   $("#text_hrsatisfied").focus();   	   
   	 }
   	 else{
	 	 	 $.post(this.visit_url,{"hotid":this.hotid},function(data){
	 	 	 	 if(data.success){
	 	 	 	 	 $("#delRecord").show();
	 	 	 	 	 var htm = Array();
	 	 	 	 	 var row = null;
	 	 	 	 	 for(var i=0;i<data.datasource.length;i++){
	 	 	 	 	 	 row = data.datasource[i];
	 	 	 	 	 	 HotLine.visitid = row.visitid;
	 	 	 	 	 	 $("#text_hrsatisfied").val(row.hr_satisfied);
	 	 	 	 	 	 $("#text_zbsatisfied").val(row.zb_satisfied);
	 	 	 	 	 	 $("#text_suggest").val(row.suggest);
	 	 	 	 	 	 $("#cmb_question1").val(row.question1);
	 	 	 	 	 	 $("#cmb_question2").val(row.question2);
	 	 	 	 	 	 $("#cmb_question2").val(row.question2);
	 	 	 	 	 	 $("#text_visitnote").val(row.note);
	 	 	 	 	 }
	 	 	 	 }
	 	 	 });
 	  }
   },   
   //获得当前日期时间
   getcurDate:function()
   {
   	  var date = new Date();
   	  return date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate() +" "+date.getHours()+":"+date.getMinutes();
   },
   //添加或修改热线解决方案
   EditScheme:function(){
   	 var parameter = { "schemeid":this.schemeid,"hotid":this.hotid,"date":$.trim($("#edit_scheme #scheme_date").val()),
   	 	                 "staff":$.trim($("#edit_scheme #scheme_staff").val()),"content":$.trim($("#edit_scheme #scheme_content").val()) };
   	 this.editpanel = $("#edit_scheme");
   	 if (parameter.date==""){
   	 	 this.showhint("请选择解决日期！");
   	 	 $("#edit_scheme #scheme_date").focus();
   	 	 return;
   	 }
   	 if (parameter.staff==""){
   	 	 this.showhint("请输入解决人员！");
   	 	 $("#edit_scheme #scheme_staff").focus();
   	 	 return;
   	 }
   	 if (parameter.content==""){
   	 	 this.showhint("请输入解决方案内容！");
   	 	 $("#edit_scheme #scheme_content").focus();
   	 	 return;
   	 }
   	 $.post(this.eidtscheme_url,parameter,function(data){
   	 	  if (data.returncode=="0000"){
   	 	  	$("#edit_scheme").hide();
   	 	  	$("#table_search tr[id='"+HotLine.hotid+"']").find(".scheme_visit_style").first().attr("flag",1);
   	 	  }
   	 	  else{
   	 	  	HotLine.showhint(data.msg);
   	 	  }
   	 });
   },
   //添加或修改热线回访记录
   EditVisit:function(){	 
     var parameter = { "visitid":this.visitid,"hotid":this.hotid,"hr_satisfied":$.trim($("#text_hrsatisfied").val()),"zb_satisfied":$.trim($("#text_zbsatisfied").val()),"suggest":$.trim($("#text_suggest").val()),"question1":$("#cmb_question1").val(),"question2":$("#cmb_question2").val(),"note":$.trim($("#text_visitnote").val()) };
   	 this.editpanel = $("#edit_scheme");
   	 if (parameter.hr_satisfied==""){
   	 	 this.showhint("请输入HR热线服务满意度！");
   	 	 $("#text_hrsatisfied").focus();
   	 	 return;
   	 }
   	 if (parameter.zz_satisfied==""){
   	 	 this.showhint("请输入转办人员服务满意度！");
   	 	 $("#text_zbsatisfied").focus();
   	 	 return;
   	 }
   	 $.post(this.editvisit_url,parameter,function(data){
   	 	  if (data.returncode=="0000"){
   	 	  	 $("#edit_scheme").hide();
   	 	  	 $("#table_search tr[id='"+HotLine.hotid+"']").find(".scheme_visit_style").last().attr("flag",1);
   	 	  }
   	 	  else{
   	 	  	HotLine.showhint(data.msg);
   	 	  }
   	 });	 
   },
   Edition:function(){
   	 if ( this.flag==1)
   	   this.EditScheme();
   	 else
   	 	 this.EditVisit();
   },
   showhint:function(message){
   	 this.editpanel.find(".hotline_hint").html(message);
   },
	 removeRow:function(ev){
	 	 var curRow = $(ev).parents("tr");
	 	 this.hotid = curRow.attr("id");
     var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除热线记录将同时删除解决方案及后续回访记录，请谨慎操作！</span>";
		 showDialog.Query("",html);
     showDialog.callback=function(result){
	  	 if(result=="Yes"){
	  	 	 $.post(HotLine.delete_url,{"hotid":HotLine.hotid},function(data){
	  	 	 	 if (data.success){
	  	 	 	 	 showDialog.Success("提示！","删除热线记录成功！");
	  	 	 	 	 showDialog.callback = function(res){
	  	 	 	 	 	 if (result=="Yes"){
	  	 	 	 	 	 	 curRow.remove();
	  	 	 	 	 	 	 var record = $("#table_search tbody>tr").length;
	  	 	 	 	 	 	 var pageindex = HotLine.curpageindex;
	  	 	 	 	 	 	 if ( record==0) 	 	 	 	 	 	 	 
	  	 	 	 	 	 	 	 pageindex = pageindex-1 < 1 ? 1 : pageindex-1;
	  	 	 	 	 	 	 HotLine.SearchData(pageindex);
	  	 	 	 	 	 }
	  	 	 	 	 };
	  	 	 	 }
	  	 	 	 else{
	  	 	 	 	 showDialog.Success("警告！",data.msg);
	  	 	 	 	 showDialog.callback = null;
	  	 	 	 }
	  	 	 });
	  	 }
  	 }		
	 },
	 removeScheme:function(){
     var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除热线处理方案后不可恢复，请谨慎操作！</span>";
		 showDialog.Query("",html);
     showDialog.callback=function(result){
	  	 if(result=="Yes"){
	  	 	 $.post(HotLine.delete_scheme_url,{"schemeid":HotLine.schemeid},function(data){
	  	 	 	 if (data.success){
	  	 	 	 	 showDialog.Success("提示！","删除热线处理方案成功！");
	  	 	 	 	 showDialog.callback = function(res){
	  	 	 	 	 	 if (res=="Yes"){
	 	 	  	       $("#edit_scheme").hide();
   	 	  	       $("#table_search tr[id='"+HotLine.hotid+"']").find(".scheme_visit_style").first().attr("flag",0);
	  	 	 	 	 	 }
	  	 	 	 	 };
	  	 	 	 }
	  	 	 	 else{
	  	 	 	 	 showDialog.Success("警告！",data.msg);
	  	 	 	 	 showDialog.callback = null;
	  	 	 	 }
	  	 	 });
	  	 }
  	 }
	 },
	 removeVisit:function(){
     var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除热线回访后不可恢复，请谨慎操作！</span>";
		 showDialog.Query("",html);
     showDialog.callback=function(result){
	  	 if(result=="Yes"){
	  	 	 $.post(HotLine.delete_visit_url,{"visitid":HotLine.visitid},function(data){
	  	 	 	 if (data.success){
	  	 	 	 	 showDialog.Success("提示！","删除热线回访记录成功！");
	  	 	 	 	 showDialog.callback = function(res){
	  	 	 	 	 	 if (res=="Yes"){
	 	 	  	       $("#edit_scheme").hide();
   	 	  	       $("#table_search tr[id='"+HotLine.hotid+"']").find(".scheme_visit_style").last().attr("flag",0);
	  	 	 	 	 	 }
	  	 	 	 	 };
	  	 	 	 }
	  	 	 	 else{
	  	 	 	 	 showDialog.Success("警告！",data.msg);
	  	 	 	 	 showDialog.callback = null;
	  	 	 	 }
	  	 	 });
	  	 }
  	 }
	 },	 
	 Save:function(){
	 	 var parameter = {};
	 	 parameter.hotid = this.hotid;
	 	 if ( $.trim($("#textReceive").val())=="") {
	 	 	 this.showhint("请输入接收人员！");
	 	 	 $("#textReceive").focus();
	 	 	 return;
	 	 }
	 	 else{
	 	 	 parameter.receivestaff = $.trim($("#textReceive").val());
	 	 }
	 	 if ( $("#combox_source").val()==0){
	 	 	  this.showhint("请选择热线信息来源！");
	 	 	 $("#combox_source").focus();
	 	 	 return;
	 	 }
	 	 else{
	 	 	 parameter.source = $("#combox_source").val();
	 	 }
	 	 if ( $.trim($("#receipt_date").val())=="") {
	 	 	 this.showhint("请输入接收日期时间！");
	 	 	 $("#receipt_date").focus();
	 	 	 return;
	 	 }
	 	 else{
	 	 	 parameter.receivedate = $.trim($("#receipt_date").val());
	 	 }
	 	 parameter.staff_number = $.trim($("#text_worknum").val());	 	 
	 	 if ( $.trim($("#text_workname").val())=="") {
	 	 	 this.showhint("请输入姓名");
	 	 	 $("#text_workname").focus();
	 	 	 return;
	 	 }
	 	 else{
	 	 	 parameter.name = $.trim($("#text_workname").val());
	 	 }
	 	 parameter.dept1 = $.trim($("#text_dept1").val());
	 	 parameter.dept2 = $.trim($("#text_dept2").val());
	 	 parameter.address = $.trim($("#text_address").val());
	 	 parameter.duty = $.trim($("#text_workduty").val());
	 	 parameter.in_date = $.trim($("#text_in_date").val());
	 	 parameter.contact = $.trim($("#textcontact").val());
	 	 if ( $.trim($("#textcontent").val())=="") {
	 	 	 this.showhint("请输入姓名！");
	 	 	 $("#textcontent").focus();
	 	 	 return;
	 	 }
	 	 else{
	 	 	 parameter.content = $.trim($("#textcontent").val());
	 	 }
	 	 if ( $("#comobx_scheme").val()==0){
       this.showhint("请选择处理方式！");
	 	 	 $("#comobx_scheme").focus();
	 	 	 return;	 	 	
	 	 }
	 	 else{
	 	 	 parameter.scheme = $.trim($("#comobx_scheme").val());
	 	 }
	 	 if ( $("#combox_grade").val()==0){
       this.showhint("请选择处理方式！");
	 	 	 $("#combox_grade").focus();
	 	 	 return;	 	 	
	 	 }
	 	 else{
	 	 	 parameter.grade = $.trim($("#combox_grade").val());
	 	 }
	 	 $.post(this.edit_hotline_url,parameter,function(data){
	 	 	  if ( data.success){
	 	 	  	$("#edit_dialog").hide();
	 	 	  	HotLine.SearchData(HotLine.curpageindex);
	 	 	  }
	 	 	  else{
	 	 	    this.showhint(data.msg);
	 	 	  }
	 	 });
	 },
	 //删除热线方案或回访
	 Delete:function() {
	 	  if ( this.flag==1){
	 	  	this.removeScheme();
	 	  }
	 	  else{
	 	  	this.removeVisit();
	 	  }
	 },
   selected_start:function(){
	 	 var date = new Date();
	 	 var setting = {
	 	 	               "skin":"whyGreen",
	 	 	               "minDate":this.mindate,
	 	 	               "maxDate":this.maxdate,
	 	 	               "dateFmt":"yyyy-MM-dd"
	 	               };
	 	 WdatePicker(setting);
	 },
   selected_end:function(){
	 	 var date = new Date();
	 	 var setting = {
	 	 	               "skin":"whyGreen",
	 	 	               "minDate":this.mindate,
	 	 	               "maxDate":this.maxdate,
	 	 	               "dateFmt":"yyyy-MM-dd"
	 	               };
	 	 WdatePicker(setting);
	 }
};