//工资字段维护管理
var salaryFields = {
	 update_url:"",
	 getFields_url:"",
	 delet_url:"",
	 icon:new Object,
	 salarytype:true,  //主项
	 stafftype:false,
	 FieldsItem:Array(),
	 fieldId:0,
	 fieldbigId:0,
	 toggle:function(ev){
	 	 if ( $(ev).attr("class")=="salary_menu_active") return;	 	 
	 	 $(".salary_menu_active").attr("class","salary_menu");
	 	 $(ev).attr("class","salary_menu_active");
	 	 var type = $(ev).attr("salarytype");
	 	 $(".salary_filed_area").hide();
	 	 $(".salary_filed_area[state='"+type+"']").show();
	 	 this.salarytype = type=="bigitem" ? true:false;
	 },
	 createRow:function(type){
	 	 var html = new Array();
	 	 var radioid = "";
	 	 if (type){
	 	 	 html.push("<div class='salary_field_row' fieldid=''>");
	 	 	 html.push(" <input class='xh' onpaste='salaryFields.checkNumber(this)' onkeyup='salaryFields.checkNumber(this)' type='text' style='width:80px;text-align:center;'>");
	 	 	 html.push(" <input class='codename' type='text' style='width:120px;'> ");
	 	 	 html.push(" <input class='desc' type='text' style='width:355px;'>");
	 	 	 html.push("</div>");
	 	 }
	 	 else{
 	 	 	 html.push("<div class='salary_field_row' fieldid=''>");
 	 	 	 html.push(" <input type='text' class='xh' style='width:60px;text-align:center;'>");
 	 	 	 html.push(" <input type='text' class='codename' style='width:120px;'>");
 	 	 	 html.push(" <input type='text' style='width:120px;' class='sapname'>");
 	 	 	 html.push(" <input type='text' style='width:120px;' class='code'>");
 	 	 	 html.push(" <span class='salary_span'>所属大类</span>");
 	 	 	 html.push("</div>");
	 	 }
	 	 return html.join('');
	 },
	 show:function(){
	 	 $('#salaryfield_area').show();
	 	 this.salarytype=true;
	 	 $(".salary_content>div [salarytype='bigitem']").attr("class","salary_menu_active");
	 	 $(".salary_content>div [salarytype='subitem']").attr("class","salary_menu");
	 	 $(".salary_filed_area[state='bigitem']").show();
	 	 $(".salary_filed_area[state='subitem']").hide();
	 	 var html = "";
	 	 if ( $("#Content1>div").length==0){
	 	 	 html = this.createRow(true);
	 	 	 $("#Content1").html(html); 
	 	 }
	 	 if ( $("#Content2>div").length==0){
	 	 	 html = this.createRow(false);
	 	 	 $("#Content2").html(html);
	 	 }
	 },
	 additem:function(){
	 	 var html  = this.createRow(this.salarytype);
	 	 if (this.salarytype )
	 	 	 $("#Content1").append(html);
	 	 else
	 	 	 $("#Content2").append(html);
	 },
	 Save:function(){
	 	 var val = this.getVal();
	 	 if ( val==null ) return;
	 	 var parameter = new Object;
	 	 if ( val != null && val.length>0)
	 	    parameter.data = val;
	 	 $.post(this.update_url,parameter,function(data){
	 	 	  
	 	 });
	 },
	 getVal:function(){
	 	 var result = new Array();
	 	 var controls = null,child = null;
	 	 if (this.salarytype)
	 	   controls = $("#Content1>div");
	 	 else
	 	 	 controls = $("#Content2>div"); 	 
	 	 var fieldid="",xh="",codename="",flag=0,sapname="",code="",bigid="";
	 	 for(var i=0;i<controls.length;i++){
	 	 	 child = controls.eq(i);
	 	 	 fieldid = child.attr("fieldid");
	 	 	 xh = $.trim($(child.find(".xh")).val());
	 	 	 codename = $.trim($(child.find(".codename")).val());
	 	 	 if ( xh=="" && codename=="") continue;
	 	 	 if (xh==""){
	 	 	 	 this.showHint("请输入序号",this.icon.error);
	 	 	 	 $(child.find(".xh")).focus();
	 	 	 	 return null;
	 	 	 }
	 	 	 if (codename==""){
	 	 	 	 this.showHint("请输入工资项目！",this.icon.error);
	 	 	 	 $(child.find(".codename")).focus();
	 	 	 	 return null;
	 	 	 }
	 	 	 //工资子项	 	   
	 	   if ( !this.salarytype){
	 	   	 sapname = $.trim($(child.find(".sapname")).val());
	 	   	 if ( sapname==""){
	 	 	 	   this.showHint("请输入对应SAP工资项目！",this.icon.error);
	 	 	 	   $(child.find(".sapname")).focus();
	 	 	 	   return null;
	 	   	 }
	 	   	 code = $.trim($(child.find(".code")).val());
	 	   	 if ( sapname==""){
	 	 	 	   this.showHint("请输入工资项编码！",this.icon.error);
	 	 	 	   $(child.find(".code")).focus();
	 	 	 	   return null;
	 	   	 }
	 	   	 bigid = $(".salary_select").val();
	 	   	 if ( bigid =="0"){
	 	   	 	 this.showHint("请选择工资项目所属大类！",this.icon.error);
	 	   	 	 return null;
	 	   	 }	 	   	 
	 	   }
	 	   result.push({"id":fieldid,"sort":xh,"bigid":bigid,"codename":codename,"sapname":sapname,"code":code });
	 	 }
	 	 if (result.length==0){
	 	 	  this.showHint("请至少输入一项内容！",this.icon.error);
	 	   	return null;
	 	 }
	 	 return result;
	 },
	 update:function(){
	 	 var val = this.getupdateVal();
	 	 if ( val==null ) return;
	 	 var parameter = new Object;
	 	 if ( val != null && val.length>0)
	 	    parameter.data = val;
	 	 $.post(this.update_url,parameter,function(data){
	 	 	  if (data.success){
	 	 	    salaryFields.showHint("修改数据记录成功！",salaryFields.icon.success);
	 	 	    setTimeout(function(){ salaryFields.showHint(null,null);$('#salaryfield_edit').hide();},2000);
  	 	  }
  	 	  else{
  	 	  	salaryFields.showHint("修改数据记录失败！",salaryFields.icon.error);
  	 	  }
	 	 });
	 },
	 getupdateVal:function() {
	 	 var xh = "";codename="",sapname="",code="";
	 	 if (this.salarytype){
	 	 	  xh = $.trim($("#salaryfield_edit #salary_big .sort").val());
	 	 	  if ( xh==""){
	 	 	  	this.showHint("请输入对应排序号！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_big .sort").focus();
	 	 	 	  return null;
	 	 	  }
	 	 	  codename = $.trim($("#salaryfield_edit #salary_big .itemname").val());
	 	 	  if ( codename==""){
	 	 	  	this.showHint("请输入工资项目！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_big .itemname").focus();
	 	 	 	  return null;	 	 	  	
	 	 	  }
	 	 }
	 	 else{
	 	 	  xh = $.trim($("#salaryfield_edit #salary_sub .sort").val());
	 	 	  if ( xh==""){
	 	 	  	this.showHint("请输入对应排序号！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_sub .sort").focus();
	 	 	 	  return null;
	 	 	  }
	 	 	  codename = $.trim($("#salaryfield_edit #salary_sub .itemname").val());
	 	 	  if ( codename==""){
	 	 	  	this.showHint("请输入工资项目！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_sub .itemname").focus();
	 	 	 	  return null;	 	 	  	
	 	 	  }
	 	 	  sapname = $.trim($("#salaryfield_edit #salary_sub .sapitem").val());
	 	 	  if ( sapname==""){
	 	 	  	this.showHint("请输入对应SAP工资项目！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_sub .sapitem").focus();
	 	 	 	  return null;	 	 	 	  	
	 	 	  }
	 	 	  code = $.trim($("#salaryfield_edit #salary_sub .fieldcode").val());
	 	 	  if ( code=="" ){
	 	 	  	this.showHint("请输入工资项编码！",this.icon.error);
	 	 	  	$("#salaryfield_edit #salary_sub .fieldcode").focus();
	 	 	 	  return null;	 	 	 	  	
	 	 	  }
	 	 }	 	 
	 	 var result = new Array();
	 	 result.push({"id":this.fieldId,"sort":xh,"bigid":"","codename":codename,"sapname":sapname,"code":code });
	 	 return result;
	 },
	 showHint:function(message,icon){
	 	 if ( message == null || message == "")
	 	   $(".salary_hint").html("");
	 	 else
	 	 	 $(".salary_hint").html("<img src='"+icon+"'><span>"+message+"</span>");
	 },
	 checkNumber:function(ev){
	 	 var tmptxt=$(ev).val(); 
     $(ev).val(tmptxt.replace(/\D|^0/g,'')); 
	 },
	 selectcombox:function(){
	 	 var val = $(".salary_select").val();
	 	 var text = $(".salary_select").find("option:selected").text();
	 	 $(".salary_span").text(text);
	 	 $(".salary_span").attr("value",val);
	 },
	 //加载字段内容
	 LoadFields:function(){
	 	$.post(this.getFields_url,function(calldata){
	 		 $(".mb_tables tbody").html("");
	 		 var html = Array();
	 		 if ( calldata.success){
	 		 	 salaryFields.stafftype = calldata.isadmin;
	 		 	 var data = calldata.data;
	 		 	 salaryFields.FieldsItem = data;
		 		 if (data!=null && data.length>0){
		 		 	 var bigid="",id="",itemcount=0;
		 		 	 for(var i=0;i<data.length;i++){
		 		 	 	 var row = data[i];
		 		 	 	 id = row.id;
		 		 	 	 bigid = row.bigid;
		 		 	 	 itemcount = row.itemcount;
		 		 	 	 if (id==bigid){
		 		 	 	 	 if (itemcount==0){
			 		 	 	 	 html.push("<tr class='mb_tables_row' bigid='"+bigid+"'>");
			 		 	 	 	 html.push(" <td>"+row.codename+"</td>");
			 		 	 	 	 html.push(" <td></td><td></td><td></td>");
			 		 	 	 	 html.push(" <td>");
				         if (salaryFields.stafftype=="1"){
				         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:5px;' title='编辑' class='mb_edition_button'></span>");
				         	 html.push("<span onclick='salaryFields.removeField(this);' id='"+id+"' style='margin-left:10px;' title='删除' class='mb_delete_button'></span>");
				         }
				         else{
				         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:22px;' title='编辑' class='mb_edition_button'></span>");
				         } 			 		 	 	 	 
			 		 	 	 	 html.push("</td></tr>");
		 		 	 	   }
		 		 	 	   else{
		 		 	 	   	 html.push("<tr class='mb_tables_row' bigid='"+bigid+"'>");
		 		 	 	   	 html.push(" <td rowspan='"+itemcount+"'>"+row.codename+"</td>");
		 		 	 	   	 html.push(salaryFields.getSubHtml(i+1,i+parseInt(itemcount),data));
		 		 	 	   	 i = i+parseInt(itemcount);
		 		 	 	   }
		 		 	 	 }
		 		 	 }
		 		 }		 		 
		 		 $(".mb_tables tbody").html(html.join(""));
	 		 }
	 	});
	 },
	 getSubHtml:function(start,end,table) {
	 	 var html = Array();
	 	 var start1 = start;
	 	 var codename="",sapname="",code="";
	 	 while(start<=end){
	 	 	 var row = table[start];
	 	 	 var id = row.id;
	 	 	 var bigid = row.bigid;
	 	 	 codename = row.codename;
	 	 	 sapname = row.sapname;
	 	 	 code = row.code;	 	
	 	 	 if ( start1==start){
	 	 	 	 html.push("<td>"+codename+"</td>");
         html.push("<td>"+sapname+"</td>");
         html.push("<td align='center' >"+code+"</td>");
         html.push("<td id='"+id+"'>");
         if (salaryFields.stafftype=="1"){
         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:5px;' title='编辑' class='mb_edition_button'></span>");
         	 html.push("<span onclick='salaryFields.removeField(this);' id='"+id+"' style='margin-left:10px;' title='删除' class='mb_delete_button'></span>");
         }
         else{
         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:22px;' title='编辑' class='mb_edition_button'></span>");
         }         
         html.push("</td></tr>");
	 	 	 } 
	 	 	 else{
	 	 	 	 html.push("<tr class='mb_tables_row' bigid='"+bigid+"'>");
	 	 	 	 html.push("<td>"+codename+"</td>");
         html.push("<td>"+sapname+"</td>");
         html.push("<td align='center'>"+code+"</td>");
         html.push("<td>");
         if (salaryFields.stafftype=="1"){
         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:5px;' title='编辑' class='mb_edition_button'></span>");
         	 html.push("<span onclick='salaryFields.removeField(this);' id='"+id+"' style='margin-left:10px;' title='删除' class='mb_delete_button'></span>");
         }
         else{
         	 html.push("<span onclick=\"salaryFields.edit('"+id+"');\" style='margin-left:22px;' title='编辑' class='mb_edition_button'></span>");
         }   
         html.push("</td></tr>");         
       }
	 	 	 start = start + 1;
	 	 }
	 	 return html.join("");
	 },
	 edit:function(id){
	 	 this.fieldId = id;
	 	 $("#salaryfield_edit").show();
	 	 this.showfields();
	 },
	 showfields:function(){
	 	for(var i=0;i<this.FieldsItem.length;i++)
	 	{
	 		$("#salaryfield_edit .salary_hint").html("");
	 		$("#updatecontent #salary_big").hide();	
	 		$("#updatecontent #salary_sub").hide();
	 		if (this.FieldsItem[i].id==this.fieldId){
	 			var html = Array();
	 			var row = this.FieldsItem[i];
	 			this.fieldId = row.id;
	 			this.fieldbigId = row.bigid;
	 			if ( this.fieldId==this.fieldbigId && row.itemcount==0){  //只有大类
	 				this.salarytype = true;
	 				$("#salaryfield_edit .salary_menu_area>span:first").hide();
	 	      $("#salaryfield_edit .salary_menu_area>span:last").attr("class","salary_menu_active");
	 				$("#updatecontent #salary_big").show();
	 				$("#updatecontent #salary_big .sort").val(row.sort);
	 				$("#updatecontent #salary_big .itemname").val(row.codename);
	 			}
	 			else{
	 				$("#salaryfield_edit .salary_menu_area>span:first").show();
	 				$("#salaryfield_edit .salary_menu_area>span:first").attr("class","salary_menu_active");
	 	      $("#salaryfield_edit .salary_menu_area>span:last").attr("class","salary_menu");
	 				$("#updatecontent #salary_sub").show();
	 				//大类内容
	 				for( var j=0;j<this.FieldsItem.length;j++){
	 					var row2 = this.FieldsItem[j];
	 					if ( row2.id == this.fieldbigId) {
	 				    $("#updatecontent #salary_big .sort").val(row2.sort);
	 				    $("#updatecontent #salary_big .itemname").val(row2.codename);
	 				    break;
	 				  }
	 			  }
	 			  this.salarytype = false;	 				
	 				//小类内容
	 				$("#updatecontent #salary_sub .sort").val(row.sort);
	 				$("#updatecontent #salary_sub .itemname").val(row.codename);
	 				$("#updatecontent #salary_sub .sapitem").val(row.sapname);
	 				$("#updatecontent #salary_sub .fieldcode").val(row.code);
	 				
	 			}
	 			break;
	 		}
	 	}
	 	 
	 },
	 edit_toggle:function(type){
	 	 $("#salaryfield_edit .salary_menu_area>span").attr("class","salary_menu");
	 	 if ( type==1 ){
	 	 	 this.salarytype = true;
	 	 	 $("#salaryfield_edit .salary_menu_area>span:last").attr("class","salary_menu_active");
	 	 	 $("#salaryfield_edit #salary_sub").hide();
	 	 	 $("#salaryfield_edit #salary_big").show();
	 	 }
	 	 else{
	 	 	this.salarytype = false;
	 	 	$("#salaryfield_edit .salary_menu_area>span:first").attr("class","salary_menu_active");
	 	 	$("#salaryfield_edit #salary_sub").show();
	 	 	$("#salaryfield_edit #salary_big").hide();
	 	 }
	 },
	 removeField:function(ev){
	 	 this.fieldbigId=$(ev).parents(".mb_tables_row").attr("bigid");
	 	 this.fieldId = $(ev).attr("id");
	 	 $("#salaryfield_dele").show(); 	 
	 },
	 DeleteField:function(){
	 	 var type = $("#del_big").attr("checked") == null ? false : true;
	 	 var id = type ? this.fieldbigId : this.fieldId;
	 	 type = type?"1":"0";
	 	 $.post(this.delet_url,{"type":type,"id":id},function(data){
	 	 	  if (data.success){
	 	 	  	$("#del_hint").text(data.msg);
	 	 	  	setTimeout(function(){
	 	 	  		$("#del_hint").text("");
	 	 	  	  salaryFields.LoadFields();	 	 	  	  
	 	 	  	  salaryFields.fieldbigId=0;
	 	 	  	  salaryFields.fieldId=0;
	 	 	  	  $('#salaryfield_dele').hide();
	 	 	  	},2000);
	 	 	  }
	 	 	  else{
	 	 	  	$("#del_hint").text(data.msg);
	 	 	  	setTimeout(function(){
	 	 	  		$("#del_hint").text("");
	 	 	  	},2000);
	 	 	  }
	 	 });
	 }
};

var Salary = {
	 icon:new Object,
	 upload_url:"",
	 delete_url:"",
	 search_url:"",
	 record:null,
	 isupload:false, //是否正在上传
	 showupload:function(){
	 	 $('#upload_file').show();
	 	 $("#AddHint").hide();
	 	 this.selectfilehint(null,null);
	 	 this.record = null;
	 },
	 SearchData:function(){
	 	 var parameter = { "date":$("#search_date").val(),"filename":$.trim($(".mb_textbox").val()) };
	 	 $.post(this.search_url,parameter,function(calldata){
	 	 	 if (calldata.success){
	 	 	 	 Salary.fulldata(calldata.data);
	 	 	 }
	 	 	 else{
	 	 	 	 
	 	 	 }
	 	 });
	 },
	 InitData:function(searchdata,init_date){
	 	  var html = new Array();
	 	  var row = null;
	 	 	//加载日期
	 	 	html.push("<option value=''>全部日期</option>");
	 	 	if ( init_date !=null && init_date.length>0){
		 	 	for(var i=0;i<init_date.length;i++) {
		 	 		row = init_date[i];
		 	 		html.push("<option value='"+row.date_val+"'>"+row.date+"</option>");
		 	 	}
	 	  }
	 	 	$("#search_date").html(html.join(""));
	 	 	//加载数据
	 	 	this.fulldata(searchdata);
	 },
	 fulldata:function(data){
	 	 var html=new Array();
	 	 if ( data!=null && data.length>0){
	 	 	for(var i=0;i< data.length;i++){
	 	 		var row = data[i];
	 	 		html.push("<tr class='mb_tables_row' id='"+row.id+"' fileid='"+row.fileid+"'>");
	 	 		html.push(" <td align='center' style='padding-left:0px'>"+row.filedate+"</td>");
	 	 		html.push(" <td><a href='"+row.url+"' target='_black' title='查看或下载文件'>"+row.filename+"</a></td>");
	 	 		html.push(" <td>"+row.note+"</td>");
	 	 		html.push(" <td align='center' style='padding-left:0px;'>"+row.nick_name+"</td>");
	 	 		html.push(" <td><span class='mb_delete_button' title='删除' onclick='Salary.Delete(this);'></span></td></tr>");	 	 		
	 	 	}
	   } 	 
	 	 $(".mb_tables tbody").html(html.join(""));
	 },
	 Delete:function(ev) {
	 	 var curRow = $(ev).parents("tr");
	 	 var id = $(ev).parents("tr").attr("id");
	 	 var fileid = $(ev).parents("tr").attr("fileid");
     var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除该记录将同时删除员工工资，请谨慎操作！</span>";
	 	 
		 showDialog.Query("",html);
     showDialog.callback=function(result){
  	  if(result=="Yes"){
  	 	 $.post(Salary.delete_url,{"id":id,"fileid":fileid},function(data){
  	 	 	 if (data.success){
  	 	 	 	 showDialog.Success("操作成功","删除文件成功！");
  	 	 	 	 showDialog.callback = function(res){
  	 	 	 	 	 if (result=="Yes") curRow.remove();
  	 	 	 	 };
  	 	 	 }
  	 	 	 else{
  	 	 	 	 showDialog.Success("操作失败",data.msg);
  	 	 	 	 showDialog.callback = null;
  	 	 	 }
  	 	 });
  	 }
    };
	 },
	 closeDialog:function()
	 {
	 	 if (this.isupload) return;
	 	 $("#upload_file").hide();
	 },
	 importData:function(){
	 	 $.post(Salary.upload_url,function(data){
	 	 });
	 },
	 uploadFile:function(file){
	 	 if (this.isupload) return;
	 	 this.selectfilehint(null,null);
	 	 $("#upload_content").show();
	 	 $("#AddHint").hide();
	 	 var file = $("#filedata").val();
	 	 if( file =="") {
       this.selectfilehint("请选择工资文件(*.xlsx或*.xls)。",this.icon.error);
       return;
	   }
	   else{
	 	   var suffix = file.substring(file.lastIndexOf(".")+1);
	 	   if ( suffix!="xls" && suffix!="xlsx"){
	 	 	   this.selectfilehint("请选择Excel文件。",this.icon.error);
	 	 	   return;
	 	   }
	   }
	   this.record = null;
	   this.selectfilehint("正在上传文件,请稍候……",this.icon.loading);
	   this.isupload = true;
	   $("#frm_import").submit();
	 },
	 import_callback:function(data){
	 	 var html = "";
	 	 this.isupload = false;
	 	 $("#filedata").val("");
	 	 $(".upload_file_area .filename").text("");
	 	 if(data.success){
	 	 	 var msg = data.msg;
	 	 	 this.record = msg;
	 	 	 if (msg.length>0){
	 	 	 	 html = "<span>操作成功！但有<span onclick='Salary.showAddhint();' class='upload_file_addhint' title='查看/隐藏'>提示信息</span></span>";
	 	 	 	 $(".upload_file_hint").html(html);
	 	 	 }
	 	 	 else{
	 	 	 	 //重新加载显示页面
	 	 	 	 this.selectfilehint("操作成功！",this.icon.success);
	 	 	 	 setTimeout(function() {
	 	 	 	   $('#upload_file').hide();
	 	 	   },2000);
	 	 	 }
	 	 }
	 	 else{
	 	 	 this.selectfilehint(data.msg[0],this.icon.error);
	 	 }
	 },
	 showAddhint:function(){
	 	 if ($("#AddHint").is(":hidden")){
		 	 $("#upload_content").hide();
		 	 $("#AddHint").show();
		 	 var html = new Array();
		 	 var data = this.record;
		 	 for(var i=0;i<data.length;i++){
		 	 	 html.push("<div class='upload_file_addhint_content'>"+data[i]+"</div>");
		 	 }
		 	 $("#AddHint").html(html.join(""));
	   }
	   else{
	   	 $("#upload_content").show();
		 	 $("#AddHint").hide();
	   }
	 },
	 selectfilehint:function(message,icon){
	 	 var html = "";
	 	 if ( message != null && message !="")
	 	   var html = "<img src='"+icon+"' ><span>"+message+"</span>";
	 	 $(".upload_file_hint").html(html);
	 },
	 change:function(){
	 	 $(".upload_file_area .filename").text($("#filedata").val());
	 }
};