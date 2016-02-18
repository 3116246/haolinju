var AppRole = {
	 appid:"",  //应用id
	 icon:new Object,
	 save_url:"",
	 get_url:"",
	 search_url:"",
	 count:0,
	 fulldata:function(data){
	 	 var html = new Array();
	 	 $(".app_role_table").html(html.join(""));
	 	 if (data!=null && data.length>0){
	 	 	 var row = null;
	 	 	 for(var i=0;i<data.length;i++){
	 	 	   row = data[i];
	 	     html.push("<div class='app_role_rows' count='"+row.count+"' title='设置或查看应用权限' id='"+row.appid+"' onclick='AppRole.ViewRole(\""+row.appid+"\")'>");
	 	     html.push("  <img  class='app_role_applog'src='"+row.applogo+"' onerror=\"this.src='/bundles/fafatimewebase/images/no_photo.png'\" />");
	 	     html.push("  <div  class='app_role_info'>");
	 	     html.push("    <span class='app_role_appname'>"+row.appname+"</span>");
	 	     html.push("    <span class='app_role_appversion'>"+row.version+"</span>");
	 	     html.push("  </div>");
	 	     html.push("</div>");
	 	   }
	 	   $(".app_role_table").html(html.join(''));
	 	   //设置页
			 if ( data.length>36){
				 pageControl.every = 36;
			   pageControl.maxIndex = 6;
			   pageControl.status = false;
			   pageControl.control = $(".app_role_table .app_role_rows");
			   pageControl.totalIndex = Math.ceil(data.length / 36);
			   pageControl.container = $(".pagecontainer");
			   pageControl.callback = null;
			   pageControl.setting();
			 }
	 	 }
	 	 else{
	 	 	 html.push("<div class='search_empty'>该用户账号无对应权限的应用！</div>");
	 	 	 $(".app_role_table").html(html.join(''));
	 	 }
	 },
	 black:function(){
	 	 $(".applist").show();
	 	 $(".app_role_editrole").hide();
	 },
	 ViewRole:function(appid){
	 	 this.appid = appid;	 	 
	 	 var control = $("#"+appid);
	 	 this.count = parseInt(control.attr("count"));
	 	 var html = Array();
	 	 html.push("<div class='app_role_edit'><img src='"+control.find("img").attr("src")+"'>");
	 	 html.push("<span class='app_role_edit_name'>"+control.find(".app_role_appname").text()+"</span>");
	 	 html.push("<span  class='app_role_edit_version'>"+control.find(".app_role_appversion").text()+"</span>");
	 	 html.push("</div>");
	 	 $("#appinfo").html(html.join(""));
	 	 $(".applist").hide();
	 	 $(".app_role_editrole").show();
	 	 $(".selcontent").addClass("show");
	 	 $.getJSON(this.get_url,{"appid":appid},function(data){
	 	 	 setCheckBox(data);
	 	 });
	 },
	 Save:function(){
	 	 //获得节点数据
	 	 var roles = mb_seluser.getSelValue();
     var cleardata = 0;
     if (roles.zzjg.length == 0 && roles.zjwd.length == 0 && roles.ryfl.length == 0 && roles.ygh.length == 0 && parseInt(this.count)==1)
       cleardata = 1;
     var parameter = { "appid":this.appid,"roles":roles,"clear":cleardata};
     this.showHint("正在保存应用权限，请稍候……",this.icon.loading);
     $.post(this.save_url,parameter,function(data){
     	 if (data.success){
     	 	 AppRole.showHint(data.message,AppRole.icon.success);
     	 	 $("#"+AppRole.appid).attr("count",data.count);
     	 	 setTimeout(function() {
     	 	 	 $(".app_role_hint").html("");
     	 	 	 AppRole.black();
     	 	 },2000);
     	 }
     	 else{
     	 	 AppRole.showHint(data.message,AppRole.icon.error);
     	 }     	  
     });
	 },
	 checkSelUser:function(seluservalue){
		if (!seluservalue 
        || (seluservalue.zzjg.length == 0 && seluservalue.zjwd.length == 0
            && seluservalue.ryfl.length == 0 && seluservalue.ygh.length == 0 && parseInt(this.count)==0)) {
        this.showHint("请选择人员范围！",this.icon.error);
        return false;
    }
    return true;
	 },
	 showHint:function(message,icon){
		 var html = "<img src='"+icon+"'><span style='margin-left:2px;'>"+message+"</span>";
		 $(".app_role_hint").html(html);
	 },
	 Search:function(){
	 	 var parameter = {"worknumber":$.trim($("#userAccount").val())};
	 	 $(".search_hint").hide();
	 	 $.post(this.search_url,parameter,function(calldata){
	 	 	  if ( calldata.returncode=="0000")
	 	 	    AppRole.fulldata(calldata.data);
	 	 	  else{
	 	 	  	$(".search_hint").show();
	 	 	  	$(".search_hint").text(calldata.msg);
	 	 	  }
	 	 });	 	 
	 }
};
var ResetPwd ={
	 defaultHeader:"",
	 login_account:"",
	 icon:new Object,
	 search_url:"",
	 reset_url:"",
	 clear_url:"",
	 toggle:function(ev){
	 	 var flag = $(ev).attr("pwd_type");
	 	 $(".mb_menu_area>span").attr("class","mb_menu");
	 	 $(ev).attr("class","mb_menu_active");
	 	 $(".reset_password_area").hide();
	 	 $(".reset_password_area[flag='"+flag+"']").show();
	 },
	 searchAccount:function(){
	 	 var account = $.trim($("#resetAccount").val());
	 	 var html = new Array();
	 	 if (account==""){
	 	 	 this.showHint("请输入需要重置密码的账号！",this.icon.error,true);
	 	 	 return;
	 	 }
	 	 else if (!this.validateMobileOrEmail(account)){
	 	 	 this.showHint("输入的账号格式错误，请重新输入！",this.icon.error,true);
	 	 	 return;
	 	 }
	 	 this.showHint("正在检验账号，请稍候…………",this.icon.loading,true);
	 	 $.post(this.search_url,{"account":account},function(data){
	 	 	 if ( data.length==1){
	 	 	 	 ResetPwd.login_account = account;
	 	 	 	 ResetPwd.showHint(null,null,true);
	 	 	 	 if ( data[0].salary_password =="0"){
	 	 	 	 	 $(".mb_menu_area").hide();
	 	 	 	 }
	 	 	 	 else{
	 	 	 	 	 $(".mb_menu_area").show();
	 	 	 	 }
	 	 	 	 $(".reset_password_area[flag='login']").show();
	 	 	 	 $(".reset_password_area[flag='salary']").hide();
	 	 	 	 $(".reset_hint").html("&nbsp;");
	 	 	 	 $("#content1").hide();
	 	 	 	 $("#content2").show();
	 	 	 	 $("#showaccount").text(account);
	 	 	 	 var html=new Array();
	 	 	 	 var temp = data[0].header;
	 	 	 	 if ( temp==null || temp=="")
	 	 	 	   html.push("<img src='"+ResetPwd.defaultHeader+"'>");
	 	 	 	 else
	 	 	 	 	 html.push("<img src='"+temp+"' onerror=\"this.src='"+ResetPwd.defaultHeader+"'\" >");
	 	 	 	 html.push("<div><span class='reset_account_name'>"+data[0].nick_name+"</span>");
	 	 	 	 temp = data[0].mobile;
	 	 	   if ( temp==null || temp=="")
	 	 	      html.push("<span class='reset_account_mobile'>Mobile:</span>");
	 	 	   else
	 	 	 	    html.push("<span class='reset_account_mobile'>Mobile:"+temp+"</span>");	 	 	 	 
	 	 	 	 html.push("</div>");
	 	 	 	 $(".reset_account_info").html(html.join(''));
	 	 	 	 var card = data[0].card;
	 	 	 	 card = card == null ? "": card;
	 	 	 	 if ( card==""){
	 	 	 	 	 $("#defaultarea").hide();
	 	 	 	 	 $("#password1,#password2").removeAttr("readonly");
	 	 	 	 	 $("#password1,#password2").css("background-color","#fff");
	 	 	 	 }
	 	 	 	 else{
	 	 	 	 	 $("#defaultarea").show();
	 	 	 	 	 $("#defaultcheck").attr("checked",true);
	 	 	 	 	 $("#password1,#password2").val(card);
	 	 	 	 	 $("#password1,#password2").attr("readonly",true);
	 	 	 	 	 $("#password1,#password2").css("background-color","#eee");
	 	 	 	 	 $("#password1,#password2").parents(".reset_user_account").css("background-color","#eee");
	 	 	 	 }
	 	 	 	 $("#defaultcheck").attr("card",card);	 	 	 	 
	 	 	 	 $("#btnstep").hide();
	 	 	 	 $("#btnreset").show();
	 	 	 	 $(".reset_user_hint2").html("");
	 	 	 	 $("#resetAccount").val("");
	 	 	 }
	 	 	 else{
	 	 	 	 ResetPwd.showHint("用户账号不存在，请重新输入！",ResetPwd.icon.error,true);
	 	 	 	 $("#resetAccount").focus();
	 	 	 }
	 	 });
	 },
	 resetPassword:function(){
	 	 var pwd1 = $("#password1").val();
	 	 var pwd2 = $("#password2").val();
	 	 if ( pwd1==""){
	 	 	 this.showHint("请输入新密码！",this.icon.error,false);
	 	 	 $("#password1").focus();
	 	 	 return;
	 	 }
	 	 else if (pwd2==""){
	 	 	 this.showHint("请输入确认密码！",this.icon.error,false);
	 	 	 $("#password2").focus();
	 	 	 return;	 	 	
	 	 }
	 	 if (pwd1!=pwd2){
	 	 	 this.showHint("两次输入密码不一致，请重新输入！",this.icon.error,false);
	 	 	 return;
	 	 }
	 	 else if (pwd2.length<6){
	 	 	this.showHint("请至少输入６位密码！",this.icon.error,false);
	 	 	return;
	 	 }
	 	 else{
	 	 	 $.post(this.reset_url,{"account":this.login_account,"password":pwd2},function(data){
	 	 	 	 if ( data.success)
	 	 	 	 {
	 	 	 	 	 ResetPwd.showHint("恭喜！密码修改成功",ResetPwd.icon.success,false);
	 	 	 	 	 setTimeout(function(){
	 	 	 	 	 	  $(".reset_hint").text("请输入要重置密码的用户账号：");
	 	 	 	 	    $(".reset_area input").val("");
	 	 	 	 	    $("#resetAccount").focus();
	 	 	 	 	    $("#content1").show();
	 	 	 	 	    $("#btnstep").show();
	 	 	 	 	    $("#content2").hide();	 	 	 	 	    
	 	 	 	 	    $("#btnreset").hide();
	 	 	 	   },3000);
	 	 	 	 }
	 	 	 	 else{
	 	 	 	 	 ResetPwd.showHint("密码修改失败，请重试！",ResetPwd.icon.error,false);
	 	 	 	 }
	 	 	 });
	 	 }
	 },
	 validateMobileOrEmail:function(mail){
	 	  var result=false;
      var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
      result = reg.test(mail);
      if(!result)
      {
      	reg = /^1[3|4|5|8][0-9]\d{8}$/;
      	result = reg.test(mail);
      }
      return result;
	 },
	 showHint:function(message,icon,type){
	 	var html = "";
	 	if ( message!=null && message != "")
	 	   html = "<img src='"+icon+"'><span style='padding-left:2px;'>"+message+"</span>";
	  if (type)
		  $(".reset_user_hint1").html(html);
		else
			$(".reset_user_hint2").html(html);
	  setTimeout(function(){
	  	if (type)
		    $(".reset_user_hint1").html("");
		  else
			  $(".reset_user_hint2").html("");	  	 
	  },2500);
		
	 },
	 backstep:function(){
	 	  $(".reset_hint").text("请输入要重置密码的用户账号：");
	    $("#resetAccount").focus();
	    $("#content1").show();
	    $("#btnstep").show();
	    $("#content2").hide();	 	 	 	 	    
	    $("#btnreset").hide();
	 },
	 selectcheck:function(){
	 	 var card = $("#defaultcheck").attr("card");
	 	 if ( $("#defaultcheck").attr("checked")==null){
	 	 	  $("#password1,#password2").val("");
	 	 	 	$("#password1,#password2").removeAttr("readonly");
	 	 	 	$("#password1,#password2").css("background-color","#fff");
	 	 	 	$("#password1,#password2").parents(".reset_user_account").css("background-color","#fff");
	 	 	 	$("#password1").focus();	 	 	 	
	 	 }
	 	 else
	 	 {
	 	 	 	$("#password1,#password2").val(card);
	 	 	 	$("#password1,#password2").attr("readonly",true);
	 	 	 	$("#password1,#password2").css("background-color","#eee");
	 	 	 	$("#password1,#password2").parents(".reset_user_account").css("background-color","#eee");
 	 	 }
	 },	 
	 clearPwd:function(){
	 	$.post(this.clear_url,{"login_account":this.login_account},function(data){
	 		 if (data.success){
		 		 showDialog.Success("提示！","清除工资独立登录密码成功！");
		 		 showDialog.callback = function(result){
	 	 	 	 	 if (result=="Yes"){
	 	 	 	 	 	 $("#content2").hide();
	 	 	 	 	 	 $("#content1").show();
	 	 	 	 	 }
		  	 };
	  	 }
	  });
	 }
};