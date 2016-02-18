//报表文件管理
var reportManage = {
	upload_url:"",
	icon:new Object,
	upload_url:"",
	edit_url:"",
	detail_url:"",
	delete_url:"",
	search_url:"",
	is_upload:false,
	fileid:"",
	schoolid:"",	
	search_state:true,
	issearch:false,
	record:13,
	pageindex:1,
	toggle:function(){
		this.fileid = "";
		this.schoolid = "";
		$("#report_main").hide();
		$("#sub_area").show();
		$("#sub_area input").val("");
		$("#sub_area textarea").val("");
		$(".selcontent").addClass("show");
	},
	backtrack:function(){
		$('#sub_area').hide();
    $("#report_main").show();
	},
	uploadfile:function(){  //上传文件
		if (this.is_upload) return;
		var file = $.trim($(".report_area_filebox").val());
		$(".report_filename").val(file);
		if ( file!="" ){
			 var filea = file.split(".");
			 if ( filea.length == 1){
				 $(".report_uploadhine").text("请选择正确的文件！");
				 setTimeout(function() {$(".report_uploadhine").text("");},3000);
				 this.is_update = false;
				 return;
			 }
			 else if ( filea[filea.length-1]=="exe"){
			 	 $(".report_uploadhine").text("不允许选择可执行文件（*.exe）！");
			 	 setTimeout(function() {$(".report_uploadhine").text("");},3000);
				 this.is_update = false;
				 return;
			 }
		}
		//将文件名作为标题
		var array1 = file.split("\\");
    if ( array1.length>0){
   	  var title = array1[array1.length-1];
   	  $("#filename").val(title);
   	  if ( file!=""){
	   	  array1 = title.split(".");
	   	  array1[array1.length-1]="";
	   	  $("#report_title").val(array1.join(""));
   	  }
    }
		this.is_upload = true;		
	  //上传文件
	  $.ajaxFileUpload({
       url:this.upload_url,
       secureuri:false,
       fileElementId:'uploadrepeat',
       dataType: 'json',
       success: function (data) {
       	 reportManage.is_upload = false;
         if ( data.success){
         	 reportManage.fileid = data.fileid;
        	 $(".report_uploadhine").text("文件上传成功！");          				    
         }
         else{
        	 $(".report_uploadhine").text("文件上传失败！");          	       	 
         }
         setTimeout(function() {$(".report_uploadhine").text("");},2000);
       },
       error: function (data, status, e) {
       	 reportManage.is_upload = false;
       	 $(".report_uploadhine").text("文件上传失败！");
       	 setTimeout(function() {$(".report_uploadhine").text("");},2000);
       }
    });
		
	},	
	Save:function(){		
		if ( this.schoolid ==""){
			 if ( $(".report_filename").val()==""){
				 $(".report_uploadhine").text("请选择上传文件！");
				 $(".report_filename").css("border","1px solid #cc3300");
	       setTimeout(function() {
	       	 $(".report_uploadhine").text("");
	       	 $(".report_filename").css("border","1px solid #0088cc");
	       },3000);
				 return;
			  }
		}
		if ( $.trim($("#report_title").val())==""){
			this.showhint("请输入标题名称！",this.icon.error);
			return false;
		}
    var staffobj = mb_seluser.getSelValue();
    var file=$("#filename").val();
    var parameter = {"schoolid":this.schoolid,"title":$.trim($("#report_title").val()),"fileid":this.fileid,"filename":file,"staff":staffobj};
    $.post(this.edit_url,parameter,function(calldata){
    	 if (calldata.success){
    	   reportManage.schoolid="";
    	   reportManage.fileid="";
    	   reportManage.backtrack();
    	   reportManage.Search(reportManage.pageindex);
    	 }
    	 else{
    	 	 reportManage.showhint("添加数据记录失败！",reportManage.icon.error);
    	 }    	
    });
	},
	//删除上传的报表文件
	Delete:function(ev){
		var report_id = $(ev).attr("schoolid");
		var file_id = $(ev).attr("fileid");	
    var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除记录后将无法恢复，请谨慎操作！</span>";
		showDialog.Query("",html);
    showDialog.callback=function(result){
	  	 if(result=="Yes"){
	  	 	 $.post(reportManage.delete_url,{"schoolid":report_id,"fileid":file_id },function(data){
	  	 	 	 if (data.success){
	  	 	 	 	 showDialog.Success("提示！","删除文件成功！");
	  	 	 	 	 showDialog.callback = function(res){
	  	 	 	 	 	 if (result=="Yes"){
	  	 	 	 	 	 	 reportManage.Search(reportManage.pageindex);
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
		 if ( reportManage.issearch )
		   reportManage.Search(page_index+1);
	},
	pageInit:function(){
 	  var opt = {callback: reportManage.pageselectCallback};
    opt.items_per_page = reportManage.record;
    opt.num_display_entries = 5;
    opt.num_edge_entries=5;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex){
		if ( !this.search_state) return;
     this.search_state = false;
     var parameter = {"date":$("#select_date").val(),
     	                "staffid":$("#select_staff").val(),
     	                "title":$("#text_filename").val(),
     	                "pageindex":pageindex,
     	                "record":this.record
     	               };
	 	 $.post(this.search_url,parameter,function(returndata){
	 	 	  reportManage.search_state = true;
	 	 	  reportManage.pageindex = pageindex;
	 	 	  if (returndata.success){
		 	 	   if ( pageindex==1 ){
			 	 	  	if ( returndata.recordcount <= reportManage.record){
			 	 	  		$("#Pagination").hide();
			 	 	  	}
			 	 	  	else{
				 	 	  	reportManage.issearch = false;
				 	 	  	var optInit = reportManage.pageInit();
				 	 	  	$("#Pagination").show();
				 	 	  	$("#Pagination").pagination(returndata.recordcount,optInit);
				 	 	  	reportManage.issearch = true;
			 	 	    }
		 	 	   }
		 	 	   else{
		 	 	   	reportManage.issearch = true;
		 	 	   }
		 	 	   reportManage.fulldata(returndata.datasource);
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
	 	 	  	if (i==data.length-1)
	 	 	  	  html.push("<tr style='border-bottom:none;'>");
	 	 	  	else
	 	 	  		html.push("<tr>");
	 	 	  	html.push(" <td width='140' align='center'>"+row.date+"</td>");
  		    html.push(" <td width='570'><a href='"+row.url+"' style='text-decoration:none;' target='_Blank'><span style='padding-left:8px;'>"+row.title+"</span></a></td>");
  		    html.push(" <td width='90' align='center'>"+row.staff+"</td>");
  		    html.push(" <td width='98' style='border-right:none;' align='center'>");
  		    html.push("   <span style='margin-left:20px;' fileid='"+row.fileid+"' filename='"+row.filename+"' title='"+row.title+"' id='"+row.id+"' onclick='reportManage.viewdetail(this);' title='编辑/查看' class='mb_edition_button'></span>");
  		    html.push("   <span style='margin-left:20px;' fileid='"+row.fileid+"' schoolid='"+row.id+"' onclick='reportManage.Delete(this);' class='mb_delete_button' title='删除' onclick=''></span></td>");
  		    html.push("</tr>");
 	 	   }
		 }
	  else {
	  	html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		}
		$("#table_search tbody").html(html.join(""));
	},
	viewdetail:function(ev){
		$("#report_main").hide();
		this.schoolid = $(ev).attr("id");
		this.fileid = $(ev).attr("fileid");
		$(".report_filename,#filename").val( $(ev).attr("filename"));
		$("#report_title").val($(ev).attr("title"));
		$(".selcontent").addClass("show");
		$("#sub_area").show();
		$.post(this.detail_url,{"schoolid":this.schoolid},function(calldata){
			 if (calldata.success){
			 	 var data = calldata.data;
			 	 if ( data != null && data.length>0)
			 	   setCheckBox(data);
			 }
		});
	},
	showhint:function(message,icon){
		var html = "<img src='"+icon+"'/><span>"+message+"</span>";
		$(".report_hint").html(html);
		setTimeout(function(){ $(".report_hint").html("");},3000);		
	}
};