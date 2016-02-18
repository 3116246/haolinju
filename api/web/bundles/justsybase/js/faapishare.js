var srcs = null;
if (document.currentScript == null) {
    var scripts = document.getElementsByTagName("script");
    var reg = /faapishare([.-]\d)*\.js(\W|$)/i;
    for (var i = 0, n = scripts.length; i < n; i++) {
        var src = !!document.querySelector ? scripts[i].src:
                    scripts[i].getAttribute("src", 4);
        if (src && reg.test(src)) {
            srcs = src.split("/");
            break;
        }
    }
}
else {
    srcs = document.currentScript.src.split("/");
}
var shareDomain = srcs[0] + "//" + srcs[2];

var regexps =/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;

//更换账号
$("#wefafa_change_account").live("click",function(){
	 //注：当前分享的内容参数等，传递不好处理。页面上已取消更换帐号功能
	 $.get( shareDomain+"/logout",function(){window.location.href = shareDomain+"/share/index";});   
});

//更换圈子时加载群组信息
$("#sharecircle").live("change",function(){
   FaFaShare.LoadingGroupName(this.value);
});

//写入分享数据
$("#wefafa_write_share").live("click",function(){
	if($(this).attr("submit")!=null) return;
  var group_id = $("#selected_group").val();
  var circle_id = $("#sharecircle").val();    
  var reason = $("#wefafa_share_content").val(),share_content = $(".wefafa_share_content").text();
  if(reason == "请输入您的分享理由！" || reason.replace(/ /g,"")=="")
    reason = "";
  if(share_content=="")
    return;
	$(this).attr("submit","1");
	$(this).text("提交中...");	    
  var url = shareDomain + "/share/wefafaShare?jsoncallback=?";
  var account = $("#text_id").val();
  var parameter = {"account":account,"reason":reason,"content":share_content,"group_id":group_id,"circle_id":circle_id,"ref_url":$("#ref_url").val()};
  $.getJSON(url,parameter,function(data){
     if(data){
       $(".wefafa_share_contentbox").hide();
       $("#share_succeed").show();
     }
  });
});


var FaFaShare = {
	//设置图片
	Icon16:function(controlId){
	  this.Icon(controlId,16);
	},
	Icon32:function(controlId){
	  this.Icon(controlId,32);
	},	
	Icon48:function(controlId){
	  this.Icon(controlId,48);
	},
	Icon:function(controlId,size){
		var imgurl= shareDomain + "/bundles/fafatimewebase/images/we"+size+".png";
		var ctr_style = "cursor:pointer;width:"+size+"px;height:"+size+"px;";
		var html = "<img src='"+imgurl+"' style='"+ctr_style+"' />";
		$("#"+controlId).html(html);
	},	
  //分享窗口
  share_Show_Window:function(content){
      var ctrl = $("#wefafa_share_body");
        ctrl = document.createElement("div");
        ctrl.id = "wefafa_share_body";
        with(ctrl.style)
        {
        	border="1px solid #CCCCCC";
				  height="320px";
				  left="50%";
				  marginLeft="-200px";
				  marginTop="-150px";
				  position="fixed";
				  top="50%";
				  width="500px";
				  zIndex="10000";	
        }
        document.body.appendChild(ctrl);
        if(content.length>200)
           content = content.substring(0,200)+"...";
        var url = shareDomain + "/share/index?title="+encodeURIComponent(content);
        var form_html ="<span id='wefafa_window_close' title='关闭分享' style='position: absolute;cursor: pointer;font-size: 16px;font-weight: bold;left: 480px;top:5px'>×</span><iframe src=\"" + url +"\" id='share_iframe' name='share_iframe' width='500' height='320' frameborder='0' scrolling='no'></iframe>";
        $(ctrl).attr("class","wefafa_share_body").append(form_html);
				$("#wefafa_window_close").unbind("click").bind("click",function(){
					  $("#wefafa_share_body").hide();
					  $("#wefafa_share_body").remove();
				});        
  },
  login_account:function(account,password){
    var url = shareDomain+"/interface/logincheck?jsoncallback=?";
    $.getJSON(url,{"login_account":account,"password":password},function(data){
      if(data.returncode=="0000"){
         window.location.href =shareDomain + "/share/index";
      }
      else{
        $("#span_password_error").text("账号或密码有误，请重新登录！");
        $(".wefafa_share_password_error").show();
        setTimeout("$('.wefafa_share_password_error').hide()",2000);
      }
    });
  },  
  //获得圈子
  LoadingCircleName:function(){
  	$("#wefafa_span_loading").show();
  	$("#wefafa_loading_word").text("正在加载圈子");
    var url = shareDomain+"/interface/baseinfo/getcircles?jsoncallback=?";
    $.getJSON(url,function(data){
        var circle = data.circles;
        var html="";
        for(var i=0;i<circle.length;i++){
           html = "<option "+(circle[i].enterprise_no!=null&&circle[i].enterprise_no!=""?"selected":"")+" value='"+circle[i].circle_id+"'>"+circle[i].circle_name+"</option>";
           if(circle[i].enterprise_no!=null)
             $("#enterpriseIN").append(html);
           else
             $("#enterpriseOut").append(html);
        };
        FaFaShare.LoadingGroupName($("#sharecircle").val());
    });
  },
  //加载群组
  LoadingGroupName:function(circle_id){
  	$("#wefafa_span_loading").show();
  	$("#wefafa_loading_word").text("正在加载群组");
    $("#selected_group").hide(); 
    var url = shareDomain+"/interface/baseinfo/getgroups?jsoncallback=?";
    $.getJSON(url,{"circle_id":circle_id},function(data){
       $("#selected_group").html("<option value='ALL'>全体成员</option>");
       if(data.groups.length>0){
         var html="";
         for(var i=0;i<data.groups.length;i++){
           html = "<option value='"+data.groups[i].group_id+"'>"+data.groups[i].group_name+"</option>";
           $("#selected_group").append(html);
         }
       }
       $("#wefafa_span_loading").hide();
       $("#selected_group").show();  
    });
  }
}