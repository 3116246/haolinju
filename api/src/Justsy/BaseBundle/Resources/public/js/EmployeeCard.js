var getEventCoord = function( e )
{
	var evt = e||event, d = document,
	scrollEl = /^b/i.test( d.compatMode ) ? d.body : d.documentElement,
	supportPage = typeof evt.pageX == 'number',
	supportLayer = typeof evt.layerX == 'number';
	return {
		pageX : supportPage ? evt.pageX : evt.clientX + scrollEl.scrollLeft,
		pageY : supportPage ? evt.pageY : evt.clientY + scrollEl.scrollTop,
		clientX : evt.clientX,
		clientY : evt.clientY,
		layerX : supportLayer ? evt.layerX : evt.offsetX,
		layerY : supportLayer ? evt.layerY : evt.offsetY
	}
};
/** 
 * 下面是一些基础函数，解决mouseover与mouserout事件不停切换的问题（问题不是由冒泡产生的） 
 */  
function checkHover(e, target) {  
    if (getEvent(e).type == "mouseover") {  
        return !contains(target, getEvent(e).relatedTarget  
                || getEvent(e).fromElement)  
                && !((getEvent(e).relatedTarget || getEvent(e).fromElement) === target);  
    } else {  
        return !contains(target, getEvent(e).relatedTarget  
                || getEvent(e).toElement)  
                && !((getEvent(e).relatedTarget || getEvent(e).toElement) === target);  
    }  
}  
  
function contains(parentNode, childNode) {  
    if (parentNode.contains) {  
        return parentNode != childNode && parentNode.contains(childNode);  
    } else {  
        return !!(parentNode.compareDocumentPosition(childNode) & 16);  
    }  
}  
//取得当前window对象的事件  
function getEvent(e) {  
    return e || window.event;  
} 
var Employees={ 
	 hoverTimer:null,
	 outTimer:null,
	 _account:"",
	 _ajaxObj:null,
	 empCards :[],
	 modalDlag:null,
	 Aurl:"",
	 personUrl:"",
	 sendMsgUrl:"",
	 attentionUrl:"",
	 getaccountUrl:"",
	 attentionBind:function()
	 {
     $(".attention,.employee_card_attention").text(function(){
				  var _st = $(this).attr("state");
				  return _st=="0"?"关注":(_st=="1"?"已关注":"互相关注");
			});
			$(".attention").attr("class",function(){
				  var bothState = $(this).attr("state");
				  return bothState=="0"?"attention attention_concern":
				    (bothState=="1" ? "attention attention_already":"attention attention_mutual");
			});
			$(".employee_card_attention").attr("class",function(){
				  var bothState = $(this).attr("state");
				  return bothState=="0"?"employee_card_attention employee_card_attention_concern":
				    (bothState=="1" ? "employee_card_attention employee_card_attention_already":"employee_card_attention employee_card_attention_mutual");
			});			
			$(".attention").live("mouseover",function(){
				   if($(this).attr("state")!="0")
				   {
				   	 $(this).attr("class","attention attention_escconcern");
				   	 $(this).text("取消关注");
				   }
			});
			$(".employee_card_attention").live("mouseover",function(){
				   if($(this).attr("state")!="0")
				   {
				   	 $(this).attr("class","employee_card_attention employee_card_attention_escconcern");
				   	 $(this).text("取消关注");
				   }
			});			
			$(".attention").live("mouseout",function(){
				   var bothState = $(this).attr("state");
				   if(bothState!="0")
				   {
				   	 var classId = bothState=="1"? "attention attention_already":"attention attention_mutual";
				   	 $(this).attr("class",classId);
				   	 $(this).text(bothState=="1"? "已关注":"互相关注");
				   }
			});
			$(".employee_card_attention").live("mouseout",function(){
				   var bothState = $(this).attr("state");
				   if(bothState!="0")
				   {
				   	 var classId = bothState=="1"? "employee_card_attention employee_card_attention_already":"employee_card_attention employee_card_attention_mutual";
				   	 $(this).attr("class",classId);
				   	 $(this).text(bothState=="1"? "已关注":"互相关注");
				   }
			});			
			//添加关注
			$(".attention_concern,.employee_card_attention_concern").live("click",function(){
				  var obj = $(this);
				  if(obj.attr("submit")=="1") return;
				  obj.attr("submit","1");
				  if(obj.attr("class").indexOf("employee_card_attention")>-1)
				      obj.attr({"class":"employee_card_attention employee_card_attention_loading"});
				  else
				      obj.attr({"class":"attention attention_loading"});
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
				  $.post(Employees.attentionUrl+"/"+obj.attr("login_account"),"",function(d){
				     	if(d.succeed){
				     		var classId = d.both=="1"? "attention attention_already":"attention attention_mutual";
				     		if(obj.attr("class").indexOf("employee_card_attention")>-1)
				     		    classId = d.both=="1"? "employee_card_attention employee_card_attention_already":"employee_card_attention employee_card_attention_mutual";
				     		(obj).attr({"state":d.both,"class":classId});
				     		(obj).html(d.both=="1"? "已关注":"互相关注");
				     		$(".attention[login_account='"+obj.attr("login_account")+"']").attr({"state":d.both,"class":classId});
				     		$(".attention[login_account='"+obj.attr("login_account")+"']").text(d.both=="1"? "已关注":"互相关注");
				     		obj.removeAttr("submit");
				     		Employees.empCards[obj.attr("login_account")]=null;				     		
				     	}
				  });
			})
			//取消关注
			$(".attention_escconcern,.employee_card_attention_escconcern").live("click",function(){
				  var obj = $(this);
				  if(obj.attr("submit")=="1") return;
				  obj.attr("submit","1");
				  if(obj.attr("class").indexOf("employee_card_attention")>-1)
				      obj.attr({"class":"employee_card_attention employee_card_attention_loading"});
				  else
				      obj.attr({"class":"attention attention_loading"});
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
          $.post(Employees.attentionUrl.replace("attention","cancelattention")+"/"+obj.attr("login_account"),"",function(d){
				     	if(d.succeed){
				     		if(obj.attr("class").indexOf("employee_card_attention")>-1)
				     		{
					     		(obj).attr({state:"0","class":"employee_card_attention employee_card_attention_concern"});
					     		(obj).html("关注");
					     		$(".attention[login_account='"+obj.attr("login_account")+"']").attr({"state":"0","class":"employee_card_attention employee_card_attention_concern"});
					     		$(".attention[login_account='"+obj.attr("login_account")+"']").text("关注");				     			
				     		}
				     		else
				     		{
					     		(obj).attr({state:"0","class":"attention attention_concern"});
					     		(obj).html("关注");
					     		$(".attention[login_account='"+obj.attr("login_account")+"']").attr({"state":"0","class":"attention attention_concern"});
					     		$(".attention[login_account='"+obj.attr("login_account")+"']").text("关注");	
				     	  }			     		
				     		obj.removeAttr("submit");
				     		Employees.empCards[obj.attr("login_account")]=null;
				     	}
				  });				
			})	 	
	 },
	 employeeBind:function()
	 {
			//鼠标移除层区域后，触发mouseout事件，把整个层隐藏  
			$('.employee_name').live('mouseout', function(e) {				  
			    if(checkHover(e,this)){
			    	   clearTimeout(Employees.hoverTimer);
			         Employees.outTimer = setTimeout("Employees.hide()",500);
			    }
			});
			$('.employee_name').live('mouseover',function(e) {  
			        	  clearTimeout(Employees.outTimer);
			            if(checkHover(e,this)){
			            	  var ex = getEventCoord(e);			  
			            	  var txt = $(this).text();  
			            	  var acc = $(this).attr("login_account");	  
			            	  if(acc!=null && acc!="")
			            	  {
			            	  	  $(this).attr("target","_blank");
			            	  	  $(this).attr("href",Employees.personUrl.replace("foo",acc));
			            	  	  txt = acc;
			            	  }
			            	  else if(Employees.empCards[txt]==null)
			            	  {			            	  	 
			            	      var isload = $(this).attr("isload");
			            	      if(isload!="1" && isload!="2") //1：已获取数据 2：正在获取数据
			            	      {
			            	      	  $(this).attr("target","_self");
			            	          $(this).attr("href","javascript:Employees.getEmpAccount('"+txt+"')");
			            	      }
			            	  }
			                Employees.hoverTimer = setTimeout(" Employees.show("+(ex.pageX)+","+(ex.pageY)+",'"+txt+"')",500);
			            }
			});
			$("#employee_card_dlag_sendmsg").live("click",function(){
				  //判断web im连接成功没
				  if(FaFaMessage._conn==null || !FaFaMessage._conn.connected) return;
				  //实时消息发送
				  var p = $(this).parent().parent();
				  var newRoster = new roster();
				  newRoster.jid = $(".fafa_jid").text();
				  newRoster.name = $(".personalname").text();
				  newRoster.dept = $(".personaldept").text();
				  FaFaChatWin.AddRoster(newRoster);
				  FaFaChatWin.ShowRoster(newRoster.jid);
				  $("#employee_card_dlag").modal("hide");
			});
			$("#employee_card_dlag_storage").live("click",function(d){
				  //收藏名片
				  var obj = $(this);
				  var p = obj.attr("login_account");
				  if(typeof(p)=="undefined" || p=="")return;
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中");
				  $.post(obj.attr("url")+"?typeid=M001&editType=copy&addr_account="+p,"",function(d){
				  	if(d.s=="1")
				  	{
				  		obj.html("已收藏");
				  		obj.removeAttr("login_account");
				  		Employees.empCards[obj.attr("login_account")]=null;	
				  	}
				  	else obj.html("<font style='color:red'>提交失败</font>");
				  });				  
			});				
	 },
	 getEmpAccount:function(empName)
	 {
	 	  var es = $("a.employee_name:contains('"+empName+"'):not([login_account])");
	 	  es.attr("isload","2");
	 	  $.post(Employees.getaccountUrl+"/"+encodeURIComponent(empName),"",function(d){
	 	  	  if(d=="") return;
	 	  	  es.attr("isload","1");
	 	  	  es.attr("login_account",d);
			 	  es.attr("href",Employees.personUrl.replace("foo",d));
			 	  //$(es[0]).trigger("click");
			 	  window.open(es.attr("href"),"","");
	 	  });
	 },
	 load:function(_Aurl,_personUrl,_sendMsgUrl,_attentionUrl,_getaccountUrl)
	 {
	 	  this.Aurl=_Aurl;
	 	  this.personUrl=_personUrl;
	 	  this.sendMsgUrl=_sendMsgUrl;
	 	  this.attentionUrl=_attentionUrl;
	 	  this.getaccountUrl=_getaccountUrl;
	 	  var _cx = $("#employee_card_dlag");
	 	  if(_cx.length==0)
	 	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="employee_card_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="411px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="0px 0px 0px 0px";
	 	  	}
	 	  	_cx.innerHTML="<div id='employee_card_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	this.modalDlag = $("#employee_card_dlag"); 
	 	  	if(this.modalDlag.modal==null) return;
	 	  	this.modalDlag.modal({show:false,backdrop:false});
	 	  	this.modalDlag.on('shown', {Aurl: _Aurl}, Employees.getInfo);	 
	 	  	$("#employee_card_dlag_sendmsg").bind("click",function(){
	 	  		  var fafa_jid = $("#emp_card_detail .fafa_jid").text();
	 	  		  if(fafa_jid=="") return;
	 	  	});	 	  	 	  	
	 	  }
			this.modalDlag.live('mouseout', function(e) {
				  clearTimeout(Employees.hoverTimer);
			    if(checkHover(e,this)){
			         Employees.outTimer = setTimeout("Employees.hide()",500);
			    }
			});  
			this.modalDlag.live('mouseover',function(e) {  
			        	  clearTimeout(Employees.outTimer);			            
			});
			//-----------------------------------------------------------------
			//绑定所有人员姓名标签事件、样式及状态切换
			//-----------------------------------------------------------------
      this.employeeBind();
			//-----------------------------------------------------------------
			//绑定所有关注按钮事件、样式及状态切换
			//-----------------------------------------------------------------
			this.attentionBind();
	 },
	 getInfo:function(para){
	 	  $("#employee_card_dlag_body").html("");
			if (Employees.empCards[Employees._account]==null)
			{
				    $("#employee_card_dlag .modal-footer").css({"display":"none"});
			      $("#employee_card_dlag_body").append("<div class='urlloading'><div /></div>");
				    Employees._ajaxObj=$.get( para.data.Aurl+"&account="+encodeURIComponent(Employees._account), {t: new Date().getTime()},
				    function (d) 
				    {
				    	Employees._ajaxObj=null;
				    	if(d.length==0) return;
				    	$("#employee_card_dlag_body").html(d);
				    	if($("a.employee_name:contains('"+Employees._account+"')").attr('onlyshow')=="1")
				    	{
				    	    $(".pesonalbutton").css("display","none");
				    	}
				    	else
				    	{
								$("#employee_card_dlag_personweb").attr("href",$("#emp_card_detail .personweburl").text());
								$(".pesonalname").attr("href",$("#emp_card_detail .personweburl").text());
					      $("#employee_card_dlag_body urlloading").remove();
					      $("#employee_card_dlag .modal-footer").css({"display":"","padding":"5px"});		
					      Employees.autoXY();
								$("#employee_card_dlag .employee_card_attention").text(function(){
									  var _st = $("#emp_card_detail .employee_card_attention").attr("state");
									  return _st=="0"?"关注":(_st=="1"?"已关注":"互相关注");
								});
								$("#employee_card_dlag .employee_card_attention").attr("class",function(){
									  var bothState = $("#emp_card_detail .employee_card_attention").attr("state");
									  return bothState=="0"?"employee_card_attention employee_card_attention_concern": (bothState=="1"? "employee_card_attention employee_card_attention_already":"employee_card_attention employee_card_attention_mutual");
								});
								Employees.empCards[Employees._account] = $("#employee_card_dlag_body").html();
								$("a.employee_name:contains('"+Employees._account+"'):not([login_account])").attr("href",$("#employee_card_dlag .personweburl").text());
								$("a.employee_name:contains('"+Employees._account+"'):not([login_account])").attr("login_account",$("#employee_card_dlag .attention").attr("login_account"));
						 	}
					 	});
			}
			else
			{
			    $("#employee_card_dlag_body").html(Employees.empCards[Employees._account]);
          Employees.autoXY();
          $("a.employee_name:contains('"+Employees._account+"'):not([login_account])").attr("href",$("#employee_card_dlag .personweburl").text());
					$("a.employee_name:contains('"+Employees._account+"'):not([login_account])").attr("login_account",$("#employee_card_dlag .employee_card_attention").attr("login_account"));
			}
	 },
	 autoXY:function(){
	 	var _a1 = $("#employee_card_dlag_attention");
	 	var _a2 = $("#emp_card_detail #attention");
    var attentionState = _a2.text();
		if($("#emp_card_detail .fafa_jid").text()=="")
		{
				    		  $("#employee_card_dlag .pesonalbutton").html("");
    }
		else
		{
				    		 $("#employee_card_dlag .pesonalbutton").css({"display":""});
				    		 _a1.attr("state",attentionState);
				    		 _a1.attr("login_account",_a2.attr("login_account"));
				    		 if(attentionState!="-1")
				    		     _a1.css({"display":""});
				    		 else
				    		 	   _a1.css({"display":"none"});
		}	 	
	 	var tmpDlg = 		  Employees.modalDlag; 
	  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
		t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
		l = l<150?l+20:l;
		l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
		tmpDlg.css({"top":t,"left":l});		
		$("#employee_card_dlag_sendmsg").attr("title",(FaFaMessage._conn==null || !FaFaMessage._conn.connected)?"WebIM未登录，不能发送消息":"立即给TA发送消息");
	 },
	 show:function(x,y,account){
	 	  this._account = account;
	 	  var l = x-($(document).scrollLeft()),t = y-$(document).scrollTop();
	 	  Employees.modalDlag.attr("x",l);
	 	  Employees.modalDlag.attr("y",t);
	 	  Employees.modalDlag.css({"top":t,"left":l,"margin":0});	 	      	
	    if(Employees.modalDlag.css("display")!="none")
	    {
	    	 if (Employees.empCards[Employees._account]!=null){
	    	    $("#employee_card_dlag_body").html(Employees.empCards[Employees._account]);
	    	    Employees.autoXY();
	    	 }
	    	 else
	    	 	  Employees.modalDlag.trigger("shown");
	    }
	    else
	       Employees.modalDlag.modal("show");	
	 },
	 hide:function()
	 {
	 	  this._account = "";
	 	  if(Employees._ajaxObj!=null)
	 	  {
	 	  	  $("#employee_card_dlag_body urlloading").remove();
	 	  	  Employees._ajaxObj.abort();//立即终止请求
	 	  }
	 	  $("#employee_card_dlag_body").html("");
			Employees.modalDlag.modal("hide");
	 }
};
