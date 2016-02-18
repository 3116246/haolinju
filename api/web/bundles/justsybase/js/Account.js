/**
 * jQuery Maxlength plugin
 * @version		$Id: jquery.maxlength.js 18 2009-05-16 15:37:08Z emil@anon-design.se $
 * @package		jQuery maxlength 1.0.5
 * @copyright	Copyright (C) 2009 Emil Stjerneman / http://www.anon-design.se
 * @license		GNU/GPL, see LICENSE.txt
 */
 (function(A){A.fn.maxlength=function(B){var C=jQuery.extend({events:[],maxCharacters:200,status:true,statusClass:"status",statusText:"还能输入字符数:",notificationClass:"notification",showAlert:false,alertText:"You have typed too many characters.",slider:false},B);A.merge(C.events,["keyup"]);return this.each(function(){var G=A(this);var J=A(this).val().length;function D(){var K=C.maxCharacters-J;if(K<0){K=0}G.next("div").html(C.statusText+" "+K)}function E(){var K=true;if(J>=C.maxCharacters){K=false;G.addClass(C.notificationClass);G.val(G.val().substr(0,C.maxCharacters));I()}else{if(G.hasClass(C.notificationClass)){G.removeClass(C.notificationClass)}}if(C.status){D()}}function I(){if(C.showAlert){alert(C.alertText)}}function F(){var K=false;if(G.is("textarea")){K=true}else{if(G.filter("input[type=text]")){K=true}else{if(G.filter("input[type=password]")){K=true}}}return K}if(!F()){return false}A.each(C.events,function(K,L){G.bind(L,function(M){J=G.val().length;E()})});if(C.status){G.after(A("<div/>").addClass(C.statusClass).html("-"));D()}if(!C.status){var H=G.next("div."+C.statusClass);if(H){H.remove()}}if(C.slider){G.next().hide();G.focus(function(){G.next().slideDown("fast")});G.blur(function(){G.next().slideUp("fast")})}})}})(jQuery);

var Account={
login_account:"",
enterNext:function (obj,nextCtl)
{
    document.getElementById(obj).onkeypress= function(event)
		{
		      if((event||window.event).keyCode==13)
		      {
		            document.getElementById(nextCtl).focus();
				  }
		}	
},
inputPlusInteger:function (ctrl)
{
   var event = this.getEvent();
   var v = event.keyCode+event.charCode;
   if(document.all!=null)
   {   	 
	   if(v<48 || v>57)
	   {
	      event.keyCode=0;
	      return;
	   }
   }
   else
   {
	   if(v<48 || v>57)
	   {
	      return false;
	   }   
   }
   return true;
},


   //根据月份添加天数
load_day:function (month)
{
   var year = $('#dateYear').val();
   var before_day = $('#dateDay').val();
   var day = 0;
   if ( month == 2)
   {
      if (((year % 4 == 0) && (year % 100 != 0)) || (year % 400 == 0))
         day = 29;
      else
         day = 28;
   }
   else if ( month == 1 || month == 3 || month == 5 || month == 7 || month == 8 || month == 10 || month == 12)
      day = 31;
   else
      day= 30;     
   $('#dateDay').find('option').remove().end();
   var temp =""; 
   for( var i = 1;i<=day;i++)
   {
      temp = i<10?"0"+String(i):String(i);
      if ( temp == before_day)
        $("#dateDay").append("<option value='"+temp+"' selected='true'>"+temp+"</option>");
      else
        $("#dateDay").append("<option value='"+temp+"'>"+temp+"</option>");
   }
},
checkName:  function (v)
{
  	  var pn = $(v).parent();
      if ($.trim(v.value).length < 1)
      {
        pn.find("span").text("真实姓名不能为空！");
        pn.find("img").attr("src", g_resource_context+"bundles/fafatimewebase/images/errow.gif");
        pn.find("img").show();
        v.focus();
        return false;
      }
      return true;  	
},
checkYear:function (v)
{
  	  var pn = $(v).parent();
      if (v.value.length < 1)
      {
        pn.find("span").text("年份不能为空！");
        pn.find("img").attr("src", g_resource_context+"bundles/fafatimewebase/images/errow.gif");
        pn.find("img").show();
        v.focus();
        return false;
      }
      else if(v.value.length!=4  || !/\d\d\d\d/g.test(v.value))
      {
        pn.find("span").text("年份只能由4位数字组成！");
        pn.find("img").attr("src", g_resource_context+"bundles/fafatimewebase/images/errow.gif");
        pn.find("img").show();
        v.focus();
        return false;
      }
      else if (v.value*1<1900 || v.value*1>(new Date().getYear()+1900-16))
      {
        pn.find("span").text("年份必须在1900-"+(new Date().getYear()+1900-16)+"年之间");
        pn.find("img").attr("src", g_resource_context+"bundles/fafatimewebase/images/errow.gif");
        pn.find("img").show();
        v.focus();
        return false;   
      }
      pn.find("span").text("");
      pn.find("img").hide();
      return true;  	
},

//提交数据
submit_content:function(sender)
{
  var $sender = $(sender);
  var _btnSave = document.getElementById('btnSave');
  if(_btnSave.tagName=="SPAN"){
     if(!pcCheck())	return;
  }
  if($("#txtmobile").val()!=''){
    var ab=/^(13[0-9]|15[0-9]|18[0-9])\d{8}$/;
    if(!ab.test($("#txtmobile").val())){
      $('#hint').show();
      $('#hint_img').attr('src', $('#hint_img').attr("errurl"));
      $('#hint_msg').text('手机号码格式不正确');
      setTimeout("$('#hint').hide()",2000);
      return false;
    }
  }
	if(!this.checkName($("#txtname")[0]) ) return;
  _btnSave.disabled = true;
  if(_btnSave.tagName=="SPAN")
  {
     _btnSave.innerHTML="提交中...";
  }
  else
     _btnSave.value="提交中...";
  $("form").ajaxSubmit({
      	      dataType: 'json',//返回的数据类型
              url: $sender.attr("saveurl"),
              method: 'post',
              success:function(r){
				         if(r.succeed)
				         {
				             	$('#hint').show();
				              $('#hint_img').attr('src', $('#hint_img').attr("okurl"));
				              $('.allphoto #preview').attr("Src",r.path);
				              $('#hint_msg').text('基本信息保存成功');
				              setTimeout("$('#hint').hide()",2000);
				         }
				         else
				         {
				             	$('#hint').show();
				              $('#hint_img').attr('src', $('#hint_img').attr("errurl"));
				              $('#hint_msg').text('基本信息保存失败');
				              setTimeout("$('#hint').hide()",2000);
				         }
				         _btnSave.disabled = false;
				         if(_btnSave.tagName=="SPAN")
                    _btnSave.innerHTML="保存";
                 else
				            _btnSave.value = "保存"; 
			        }        
      });  
},

//取得积分历史
getPointsHisPage:function (index) 
{
  $("#point_his").empty();
  LoadComponent("point_his", $("#point_his").attr("hisurl"), {pageindex: index});
},
deptClick:null,
deptCheck:null,
loadDept:function(showCheckBox){
		if($("#deptdiv").attr("isload")=="1")
		{
				return ;
		}		
    var setting = {
				data: {
					simpleData: {
						enable: true
					}
				},
				check:{
					enable: showCheckBox==null?false:showCheckBox,
					chkStyle: "checkbox",
					chkboxType: { "Y": "s", "N": "s" }
				},
				callback: {
					onCheck:function(event, treeId, treeNode){
						if(Account.deptCheck!=null){
							Account.deptCheck(treeId, treeNode);
						  return;
						}
					},
				  beforeClick:function(treeId, treeNode){
				     if(treeNode.id=="new") return false;
				     return true;
				  },
					onClick: function(event,treeId, treeNode) {
						 if(Account.deptClick!=null)
						 {
						 	   Account.deptClick(treeId, treeNode);
						     return;	
						 }
					   if(event.target.tagName=="INPUT") return;
					   if($(event.target).parent().find("#newdept").length>0) return;
					   var treeObj = $.fn.zTree.getZTreeObj("depttree");
					   if($("#newdept").length>0)
					   {
					       var newNode = treeObj.getNodeByTId($("#newdept").parent().parent().parent()[0].id);
					       treeObj.removeNode(newNode);
					   }
					   if(event.target.tagName=="IMG")
					   {
					      if(event.target.parentNode.id=="adddept")
					      {
						      var newNode = {"id":"new","name":"","pId":treeId};						      
						      newNode=treeObj.addNodes(treeNode,newNode);						      
						      $("#"+newNode[0].tId).find("a span").css("float","left");
						      newNode = $("#"+newNode[0].tId).find("a span:eq(1)");
						      newNode.html("<span id='newdept' style='float:left'><input maxlength=10 type=text style='height:14px;width:100px;font-size:12px;float: left;'><img title='保存数据' src='/bundles/fafatimewebase/images/save_dept.png' style='left:96px;width:16px;height:16px;float: left;'><a style='color:red'></a></span>");
					        setTimeout('$("#newdept input").focus()',100);
					      }
					      else if(event.target.parentNode.id=="deldept")
					      {
					          //删除部门		    		      	
		    		      	$.post($("#deptdiv").attr("delurl"),"deptid="+treeNode.id,function(r){	    		      		  
		    		      		  if(r.s){
		    		      		  	 if(treeNode.id==$("#txtdeptid").val())
		    		      		  	 {
		    		      		  	    	$("#txtdeptid").val("");
		    		      		  	    	$("#txtdept").val("");
		    		      		  	 }
							             treeObj.removeNode(treeNode);
		    		            }
		    		      	});					          	
					      }
					      return;
					   }
					   
    		     $("#txtdept").val(treeNode.name);
    		     $("#txtdeptid").val(treeNode.id);	
    		     $("#txtdept").parent().find("span").hide();  
    		       		     					  
					}
				}
			};
			$("#deptdiv").attr("isload","1");      
    	$.get($("#deptdiv").attr("url"),"",function(d){
    		  zNodes = d;
    		  if(zNodes.length==1)
    		  {
    		     $("#txtdept").val(zNodes[0].name);
    		     $("#txtdeptid").val(zNodes[0].id);
    		  }
    		  $.fn.zTree.init($("#depttree"), setting, zNodes);   
    		  $("#txtdept").parent().find("span div:eq(0)").hide();
    		  $("#deptdiv").show(); 
    		  
    		  $("#depttree").find("a").live("mouseover",function(){
    		  	if($("#adddept").length==0) return;
    		  	if($(this).find("#adddept").length>0||$(this).find("#newdept").length>0) return;
    		  	if($(this).find("span:eq(1)").text()=="") return;
    		  	$(this).append($("#adddept")[0].outerHTML.replace("none",""));
    		  	var node = $.fn.zTree.getZTreeObj("depttree").getNodeByTId(this.parentNode.id);
    		  	if(!node.isParent &&
    		  	    node.owner == Account.login_account
    		  	  )
    		  		$(this).append($("#deldept")[0].outerHTML.replace("none",""));
    		  }); 
    		  $("#depttree").find("a").live("mouseout",function(e){
    		  	  if($("#adddept").length==0) return;
    		  	  if(checkHover(e,this)){
    		      	$(this).find("#adddept").remove();
    		      	$(this).find("#deldept").remove();
    		      }
    		  });
    		  $("#deptdiv").unbind().bind("mouseout",function(e){
    		  	 if(checkHover(e,this))
    		  	 {
    		  	     Account.hideDept();
    		  	 }
    		  });
    		  $("#newdept img").live("click",function(){
    		  	    var v = $("#newdept input").val().replace(/ /g,"");
    		  	    $("#newdept a").html("");
                var treeObj = $.fn.zTree.getZTreeObj("depttree");
    		      	var nodes = treeObj.getSelectedNodes(); 	    
    		      	if(v=="" || v.length<3)
    		      	{
    		      		  $("#newdept input").focus();
    		      		  $("#newdept a").html("不少于3个字");
    		      		  return;
    		      	}
    		      	$("#newdept a").html("提交中...");
    		      	$.post($("#deptdiv").attr("saveurl"),"deptname="+v+"&pid="+nodes[0].id,function(d){
    		      		  if(d.s){
                       $("#txtdept").val(d.name);
    		               $("#txtdeptid").val(d.id);
    		               var newNode = treeObj.getNodeByTId($("#newdept").parent().parent().parent()[0].id);
					             treeObj.removeNode(newNode);
					             if(treeObj.getNodeByParam("id",d.id,null)==null){
					                 var newNode = {"id":d.id,"name":d.name,"pId":d.pId,owner:Account.login_account};
					                 treeNode = treeObj.getNodeByParam("id",d.pId,null);
						               newNode=treeObj.addNodes(treeNode,newNode);
						           }
					             $("#txtdept").parent().find("span").hide();
    		            }
    		            else
    		            	$("#newdept a").html("保存失败！"); 		  
    		      	});
    		  });    		    
    	});
},
showDept:function()
{
	  var deptDiv = 	$("#deptdiv").parent();
	  deptDiv.show();
    deptDiv.find("span").show();	
},
hideDept:function()
{
	  var deptDiv = 	$("#deptdiv").parent();
    deptDiv.hide();	
},
login_account_datasource:function(q,process){
	$.getJSON(q_path,{q:this.query,t:new Date().getTime()},function(json)
        	  {
        	  	datasource=json;
        	  	for (var i=0; i<datasource.length; i++)
              {
	              datasource[i].index = i; 
	              datasource[i].toString = function(){return this.index};
              }
              process(datasource);
            });
   return null;
},
login_account_matcher:function(item)
   {
   	 if(this.query)
   	 {
   	 	 return ~item.login_account.toLowerCase().indexOf(this.query.toLowerCase())|| ~item.nick_name.indexOf(this.query);
   	 }
   	 return false;
   },
login_account_sorter:function (items)
   {
   	 return items;
   },
login_account_highlighter:function (item)
   {
   	 return "<strong>"+item.nick_name+"("+item.login_account+")</strong>";
   },
report_object_login_account_updater:function(itemIndex)
   {
   	 var b=datasource;
   	 if($("#InputNotifyArea_report_object input[value='"+b[itemIndex].login_account+"']").length==0&&$("#InputNotifyArea_report_object input").length==0)
   	 {
   	 	$(".nosetxiashu").remove();
   	 	$("#InputNotifyArea_report_object").append(GetNotifyTemplate(b[itemIndex].login_account,b[itemIndex].nick_name).replace("NotifyClose_OnClick","Account.NotifyClose_OnClick"));
   	 	$("#report_object").hide();
   	 }
   	 return "";
},
direct_manages_login_account_updater:function(itemIndex)
{
  var a=datasource;
  if ($("input[value='"+a[itemIndex].login_account+"']", $("#InputNotifyArea")).length == 0)
  {
  		$(".nosetxiashu").remove();
  		$("#InputNotifyArea").append(GetNotifyTemplate(a[itemIndex].login_account,a[itemIndex].nick_name)); 
	}
	return "";
},
NotifyClose_OnClick:function(sender) 
{
  $sender = $(sender);
  $sender.parent().remove();
  $("#report_object").show();
},
txtNotify_OnKeyUp:function (e) 
{
  if (e.keyCode == 8) // backspace
  {
    if ($(this).val() == "")
      $("#InputNotifyArea_report_object .NotifyObj:last").remove();
  }
  else if (e.keyCode == 13)
  {
    var $this = $(this);
    v = $this.val();
    if (v == "") return;
    if (v.indexOf("@") <= 0)
    { 
      $this.val("");
      return;
    }
    var $InputNotifyArea = $("#InputNotifyArea");
    if ($("input[value='"+v+"']", $InputNotifyArea).length == 0)
      $InputNotifyArea.append(GetNotifyTemplate(v, v));
    $this.val("");
  }
}
};
