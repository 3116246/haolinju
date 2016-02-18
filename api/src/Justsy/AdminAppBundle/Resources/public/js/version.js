var Version = {
	upload_url:"",
	search_url:"",
	delete_url:"",
	isupload:false,
	issearch:false,
	record:12,
	search_state:true,
	hander:null,
	showupload:function(){
	    $(".upload_file_area .filename").text("");
	    $("input").val("");
	    $("#update_content").val("");
	 	  $('#upload_file').show();
	 	  $(".selected_icon,.ios_plist").hide();
	 	  this.isupload=false;
	 	  $(".set_plist").hide();
	 	  $("#plist_weburl").val("");
	 	  $("#check_plist").attr("checked",false);
	 	  this.showhint("");
	},
	//选择文件变化事件
	change:function(){
		var file = $("#filedata").val();
		$(".upload_file_area .filename").text(file);
		var file = file.toLowerCase();
		var suffix = file.substring(file.lastIndexOf(".")+1);
	  if ( suffix!="exe" && suffix!="apk" && suffix!="ipa" ){
	 	   this.showhint("请选择.apk、.exe、.ipa的安装包。");
	 	   return false;
	  }
	  else{
	   	 $(".version_type img").hide();
	   	 $(".version_type img[apptype='"+suffix+"']").show();
	   	 if ( suffix == "ipa")
	   	   $(".ios_plist").show();
	   	 else{
	   	 	 $(".ios_plist").hide();
	   	 	 $(".set_plist").hide();
	   	 	 $(".edition_content").css("height","135px");
	   	 }
	  }
	  return true;
	},
  uploadFile:function(){
	 	 if (this.isupload) return;
	 	 $(".upload_content").show();
	 	 var file = $("#filedata").val().toLowerCase();
	 	 if( file =="") {
       this.showhint("请选择发布文件*.apk、*.exe、*.ipa");
       return;
	   }
	   else{
	 	   if (!this.change()) return;
	   }	   	   
	   if ( $.trim($("#version_1").val())==""){
	   	 this.showhint("请输入发布版本号！");
	   	 $("#version_1").focus();
	   	 return;
	   }
	   else if ( $.trim($("#version_2").val())==""){
	   	 this.showhint("请输入发布版本号！");
	   	 $("#version_2").focus();
	   	 return;
	   }
	   else if ( $.trim($("#version_3").val())==""){
	   	 this.showhint("请输入发布版本号！");
	   	 $("#version_3").focus();
	   	 return;
	   }
	   else if ( $.trim($("#version_4").val())==""){
	   	 this.showhint("请输入发布版本号！");
	   	 $("#version_4").focus();
	   	 return;
	   }
	   if ($("#plist_weburl:visible").length==1)  //如果为ios文件
	   {
	   	 var url = $.trim($("#plist_weburl").val());
	   	 if ( url==""){
	   	 	 this.showhint("远程*.plist地址不允许为空！");
	   	   $("#plist_weburl").focus();
	   	 	 return;
	   	 }
       else if ( url.substring(0,5)!="https" || url.length<10){
	   	 	 this.showhint("请输入正确的远程*.plist地址！");
	   	   $("#plist_weburl").focus();
	   	 	 return;
	   	 }
	   	 else{
	   	 	 var url_array =  url.split(".");
	   	 	 if ( url_array.length==0 || ( url_array.length>0 && url_array[url_array.length-1]!="plist")){
	   	 	 	 this.showhint("请输入正确的远程*.plist地址！");
	   	 	 	 $("#plist_weburl").focus();
	   	 	 	 return;
	   	 	 }
	   	 }
	   	 $("#plist_url").val(url);
	   }
	   else
	   {
	      $("#plist_url").val();
	   }
	   $(".hint_message").html("<img src='/bundles/fafatimewebase/images/loading.gif' /><span>正在上传文件，请稍候...</span>");
	   this.isupload = true;
	   Version.checknetwork();
	   $("#frm_import").submit();
	},
	showhint:function(message){
	 	 message = message==null ? "":message;
	 	 $(".hint_message").html(message);
	 	 setTimeout(function(){
	 	 	 $(".hint_message").html("");
	 	 },2000);
	},
	upload_callback:function(data){
	 	 this.isupload = false;
	 	 if(data.success){
	 	 	 Version.showhint("发布成功");
       $("#filedata").val("");
	 	   $(".upload_file_area .filename").text("");	 	 	 
	 	 	 setTimeout(function(){
	 	 	 	 $("#text_version").val("");
	 	 	 	 $("#update_content").val("");
	 	 	 	 $(".version_type img").hide();
	 	 	 	 $('#upload_file').hide();
	 	     Version.showhint("");
	 	     Version.SearchData(1);
	     },500);
	 	 }
	 	 else{
	 	    Version.showhint(data.msg);
	 	 }
	 	 if ( Version.hander !=null)
	 	 {
	 	    clearInterval(Version.hander);
	 	    Version.hander = null;	 	    
	 	 }
	},
  pageselectCallback:function(page_index){
	 	 if ( Version.issearch )
	 	   Version.SearchData(page_index+1);
	},
	pageInit:function(){
	 	  var opt = {callback: Version.pageselectCallback};
      opt.items_per_page = Version.record;
      opt.num_display_entries = 5;
      opt.num_edge_entries=5;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	},
	SearchData:function(pageindex){
	 	 if ( !this.search_state) return;
     this.search_state = false;
     var parameter = { "pageindex":pageindex,"record":Version.record};
	 	 $.post(this.search_url,parameter,function(returndata){
	 	 	  Version.search_state = true;
	 	 	  if ( pageindex==1 ){
	 	 	  	if ( returndata.recordcount <= Version.record){
	 	 	  		$("#Pagination").hide();
	 	 	  	}
	 	 	  	else{
		 	 	  	Version.issearch = false;
		 	 	  	var optInit = Version.pageInit();
		 	 	  	$("#Pagination").show();
		 	 	  	$("#Pagination").pagination(returndata.recordcount,optInit);
		 	 	  	Version.issearch = true;
	 	 	    }	 	 	  	
	 	 	  }
	 	 	  else{
	 	 	  	Version.issearch = true;
	 	 	  }
	 	 	  var data = returndata.datasource;
	 	 	  var html = new Array();
	 	 	  if ( data != null && data.length>0){
	 	 	  	var row = null;
		 	 	  for(var i=0;i<data.length;i++){
		 	 	  	row = data[i];
		 	 	  	html.push("<tr  id='"+row.id+"'>");		 	 	  	
		 	 	  	html.push(" <td align='center' width='150' style='height:35px;line-height:35px;'>"+row.date+"</td>");
		 	 	  	html.push(" <td align='center' width='120' style='height:35px;line-height:35px;'>"+row.version+"</td>");		 	 	  	
		 	 	  	html.push(" <td align='left' width='442' style='height:35px;line-height:35px;'><span class='update_content'>"+row.update_content+"</span></td>");
		 	 	  	html.push(" <td align='center' width='100' style='height:35px;line-height:35px;'>"+row.apptype+"</td>");
		 	 	  	html.push(" <td align='center' width='85' style='height:35px;line-height:35px;'>"+row.nick_name+"</td>");
		 	 	  	html.push(" <td align='center' width='102' style='height:35px;line-height:35px;'>");
		 	 	  	html.push("   <a class='mb_download_icon' style='margin-left:12px;margin-top:9px;' href='"+row.down_url+"' title='下载安装包'></a>");
		 	 	  	html.push("   <span onclick='Version.Delete(this);' class='mb_delete_button' style='margin-top:9px;' title='删除'></span></td>");		 	 	  	
		 	 	  	html.push("</tr>");
		 	 	  }
	 	 	  }
	 	 	  else{
	 	 	  	html.push("<span class='mb_common_table_empty'>未查询到发布数据记录</span>");
	 	 	  }
	 	 	  $(".mb_common_table tbody").html(html.join(""));
	 	 });
	},
	Delete:function(ev){
		var curRow = $(ev).parents("tr");
		var id = curRow.attr("id");
		var html = "<span style='float:left;display:block;width:100%;height:100%;text-align:center;line-height:30px;'>删除该记录将同时删除安装包文件，请谨慎操作！</span>";
	  showDialog.Query("",html);
    showDialog.callback=function(result){
  	  if(result=="Yes"){
  	 	 $.post(Version.delete_url,{"id":id},function(data){
  	 	 	 if (data.success){
  	 	 	 	 showDialog.Success("操作成功","删除记录及安装包文件成功！");
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
    }
	},
	viewplist:function()
	{
		if ( $("#check_plist").attr("checked")==null) {
			$(".set_plist").hide();
			$(".edition_content").css("height","135px");
		}
		else{
			$(".set_plist").show();
			$(".edition_content").css("height","100px");
			$("#plist_weburl").focus();
		  var val = $.trim($("#plist_weburl").val());
		  val = val=="" ? "https://":val;
		  $("#plist_weburl").val(val);
		}
	},
    checknetwork:function()
    {
        Version.hander = setInterval(function()
        {
            var imghtml="<img src='/bundles/fafatimewebase/images/zq.gif' onerror='Version.online_err();' />";
            $("#check_network").html(imghtml);     
        },10000);
    },
    online_err:function()
    {
        if ( Version.hander != null)
        {
           clearInterval(Version.hander);
           Version.hander = null;
        }
        Version.isupload = false;
       $(".hint_message").html("网络已经断开，文件上传失败！");
    }
};