var HashTable=function(){
	this.length=0;
	this.array=new Array();
	this.get=function(key){
		for(var i=0;i<this.array.length;i++){
			if(this.array[i].key==key){
				return this.array[i].val;
			}
		}
		return null;
	};
	this.push=function(key,val){
		var obj={'key':key,'val':val};
		this.array.push(obj);
		this.length++;
	};
	this.clear=function(){
		this.array=[];
		this.length=0;
	};
};
var ContactCard={ 
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
     $(".attention,.Contact_card_attention").text(function(){
				  var _st = $(this).attr("state");
				  return _st=="0"?"关注":(_st=="1"?"已关注":"互相关注");
			});
			$(".attention").attr("class",function(){
				  var bothState = $(this).attr("state");
				  return bothState=="0"?"attention attention_concern":
				    (bothState=="1" ? "attention attention_already":"attention attention_mutual");
			});
			$(".Contact_card_attention").attr("class",function(){
				  var bothState = $(this).attr("state");
				  return bothState=="0"?"Contact_card_attention Contact_card_attention_concern":
				    (bothState=="1" ? "Contact_card_attention Contact_card_attention_already":"Contact_card_attention Contact_card_attention_mutual");
			});			
			$(".attention").live("mouseover",function(){
				   if($(this).attr("state")!="0")
				   {
				   	 $(this).attr("class","attention attention_escconcern");
				   	 $(this).text("取消关注");
				   }
			});
			$(".Contact_card_attention").live("mouseover",function(){
				   if($(this).attr("state")!="0")
				   {
				   	 $(this).attr("class","Contact_card_attention Contact_card_attention_escconcern");
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
			$(".Contact_card_attention").live("mouseout",function(){
				   var bothState = $(this).attr("state");
				   if(bothState!="0")
				   {
				   	 var classId = bothState=="1"? "Contact_card_attention Contact_card_attention_already":"Contact_card_attention Contact_card_attention_mutual";
				   	 $(this).attr("class",classId);
				   	 $(this).text(bothState=="1"? "已关注":"互相关注");
				   }
			});			
			//添加关注
			$(".attention_concern,.Contact_card_attention_concern").live("click",function(){
				  var obj = $(this);
				  if(obj.attr("submit")=="1") return;
				  obj.attr("submit","1");
				  if(obj.attr("class").indexOf("Contact_card_attention")>-1)
				      obj.attr({"class":"Contact_card_attention Contact_card_attention_loading"});
				  else
				      obj.attr({"class":"attention attention_loading"});
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16 title='提交中...'>");
				  $.post(ContactCard.attentionUrl+"/"+obj.attr("login_account"),"",function(d){
				     	if(d.succeed){
				     		var classId = d.both=="1"? "attention attention_already":"attention attention_mutual";
				     		if(obj.attr("class").indexOf("Contact_card_attention")>-1)
				     		    classId = d.both=="1"? "Contact_card_attention Contact_card_attention_already":"Contact_card_attention Contact_card_attention_mutual";
				     		(obj).attr({"state":d.both,"class":classId});
				     		(obj).html(d.both=="1"? "已关注":"互相关注");
				     		$(".attention[login_account='"+obj.attr("login_account")+"']").attr({"state":d.both,"class":classId});
				     		$(".attention[login_account='"+obj.attr("login_account")+"']").text(d.both=="1"? "已关注":"互相关注");
				     		obj.removeAttr("submit");
				     		ContactCard.empCards[obj.attr("login_account")]=null;
				     		ContactCard._attencall.execute('1','1');
				     		//if(OnAttened!=null) OnAttened(obj.attr("login_account"),d.both); //关注成功后事件处理
				     	}
				     	else{
				     		ContactCard._attencall.execute('1','0');
				     	}
				  });
			})
			//取消关注
			$(".attention_escconcern,.Contact_card_attention_escconcern").live("click",function(){
				  var obj = $(this);
				  if(obj.attr("submit")=="1") return;
				  obj.attr("submit","1");
				  if(obj.attr("class").indexOf("Contact_card_attention")>-1)
				      obj.attr({"class":"Contact_card_attention Contact_card_attention_loading"});
				  else
				      obj.attr({"class":"attention attention_loading"});
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
          $.post(ContactCard.attentionUrl.replace("attention","cancelattention")+"/"+obj.attr("login_account"),"",function(d){
				     	if(d.succeed){
				     		if(obj.attr("class").indexOf("Contact_card_attention")>-1)
				     		{
					     		(obj).attr({state:"0","class":"Contact_card_attention Contact_card_attention_concern"});
					     		(obj).html("关注");
					     		$(".attention[login_account='"+obj.attr("login_account")+"']").attr({"state":"0","class":"Contact_card_attention Contact_card_attention_concern"});
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
				     		ContactCard.empCards[obj.attr("login_account")]=null;
				     		ContactCard._attencall.execute('0','1');
				     	}
				     	else{
				     		ContactCard._attencall.execute('0','0');
				     	}
				  });				
			})	 	
	 },
	 ContactBind:function()
	 {
			//鼠标移除层区域后，触发mouseout事件，把整个层隐藏  
			$('.account_baseinfo').live('mouseout', function(event) {				  
			    if(checkHover(event,this)){
			    	   clearTimeout(ContactCard.hoverTimer);
			         ContactCard.outTimer = setTimeout("ContactCard.hide()",500);
			    }
			});
			$('.account_baseinfo').live('mouseover',function(event) {
			        	  clearTimeout(ContactCard.outTimer);
			            if(checkHover(event,this)){
			            	  var ex = getEventCoord(event);			  
			            	  var txt = $(this).text();  
			            	  var acc = $(this).attr("login_account");	  
			            	  if(acc!=null && acc!="")
			            	  {
			            	  	  $(this).attr("target","_blank");
			            	  	  $(this).attr("href",ContactCard.personUrl.replace("foo",acc));
			            	  	  txt = acc;
			            	  }
			            	  else if(ContactCard.empCards[txt]==null)
			            	  {			            	  	 
			            	      var isload = $(this).attr("isload");
			            	      if(isload!="1" && isload!="2") //1：已获取数据 2：正在获取数据
			            	      {
			            	      	  $(this).attr("target","_self");
			            	          $(this).attr("href","javascript:ContactCard.getEmpAccount('"+txt+"')");
			            	      }
			            	  }
			            	  var attencall=$(this).attr("attencall");
			            	  if(typeof(attencall)!='undefined')
			            	  {
			            	  	ContactCard._attencall.add(this,attencall);
			            	  }
			                ContactCard.hoverTimer = setTimeout(" ContactCard.show("+(ex.pageX)+","+(ex.pageY)+",'"+txt+"')",500);
			            }
			});
			$("#Contact_card_dlag_sendmsg").live("click",function(){
				  //实时消息发送
				  var p = $(this).parent().parent();
				  var newRoster = new roster();
				  newRoster.jid = $(".fafa_jid").text();
				  newRoster.name = $(".personalname").text();
				  newRoster.dept = $(".personaldept").text();
				  FaFaChatWin.AddRoster(newRoster);
				  FaFaChatWin.ShowRoster(newRoster.jid);
				  $("#Contact_card_dlag").modal("hide");
			});
			$("#Contact_card_dlag_storage").live("click",function(d){
				  //收藏名片
				  var obj = $(this);
				  var p = obj.attr("login_account");
				  obj.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
				  $.post(obj.attr("url")+"?typeid=M001&editType=add&addr_account="+p,"",function(d){
				  	if(d.s=="1")
				  	{
				  		obj.html("已收藏");
				  		ContactCard.empCards[obj.attr("login_account")]=null;	
				  	}
				  	else obj.html("<font style='color:red'>提交失败</font>");
				  });				  
			});				
	 },
	 getEmpAccount:function(empName)
	 {
	 	  var es = $(".account_baseinfo:contains('"+empName+"'):not([login_account])");
	 	  es.attr("isload","2");
	 	  $.post(ContactCard.getaccountUrl+"/"+encodeURIComponent(empName),"",function(d){
	 	  	  if(d=="") return;
	 	  	  es.attr("isload","1");
	 	  	  es.attr("login_account",d);
			 	  es.attr("href",ContactCard.personUrl.replace("foo",d));
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
	 	  var _cx = $("#Contact_card_dlag");
	 	  if(_cx.length==0)
	 	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="Contact_card_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="411px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="0px 0px 0px 0px";
	 	  	}
	 	  	_cx.innerHTML="<div id='Contact_card_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	this.modalDlag = $("#Contact_card_dlag"); 
	 	  	if(this.modalDlag.modal==null) return;
	 	  	this.modalDlag.modal({show:false,backdrop:false});
	 	  	this.modalDlag.on('shown', {Aurl: _Aurl}, ContactCard.getInfo);	 
	 	  	$("#Contact_card_dlag_sendmsg").bind("click",function(){
	 	  		  var fafa_jid = $("#contact_card_detail .fafa_jid").text();
	 	  		  if(fafa_jid=="") return;
	 	  	});	 	  	 	  	
	 	  }
			this.modalDlag.live('mouseout', function(e) {
				  clearTimeout(ContactCard.hoverTimer);
			    if(checkHover(e,this)){
			         ContactCard.outTimer = setTimeout("ContactCard.hide()",500);
			    }
			});  
			this.modalDlag.live('mouseover',function(e) {  
			        	  clearTimeout(ContactCard.outTimer);			            
			});
			//-----------------------------------------------------------------
			//绑定所有人员姓名标签事件、样式及状态切换
			//-----------------------------------------------------------------
      this.ContactBind();
			//-----------------------------------------------------------------
			//绑定所有关注按钮事件、样式及状态切换
			//-----------------------------------------------------------------
			this.attentionBind();
	 },
	 _attencall:{
	 		_ct:new HashTable(),
	 		contains:function(_e){
	 			var re=this._ct.get(_e);
	 			return re==null?false:true;
	 		},
	 		execute:function(atten,d){
	 			for(var i=0;i<this._ct.length;i++)
	 			{
		 			var re=this._ct.array[i].val;
		 			if(re!=null)
		 				eval(re+"('"+atten+"','"+d+"','"+ContactCard._account+"')");
		 			this._ct.clear();
		 		}
	 		},
	 		add:function(k,v)
	 		{
	 			this._ct.clear();
	 			this._ct.push(k,v);
	 		}
	 	},
	 getInfo:function(para){
	 	  $("#Contact_card_dlag_body").html("");
			if (ContactCard.empCards[ContactCard._account]==null)
			{
				    $("#Contact_card_dlag .modal-footer").css({"display":"none"});
			      $("#Contact_card_dlag_body").append("<div class='urlloading'><div /></div>");
				    ContactCard._ajaxObj=$.get( para.data.Aurl+"&account="+encodeURIComponent(ContactCard._account), {t: new Date().getTime()},
				    function (d) 
				    {
				    	ContactCard._ajaxObj=null;
				    	if(d.length==0) return;
				    	$("#Contact_card_dlag_body").html(d);
				    	if($(".account_baseinfo:contains('"+ContactCard._account+"')").attr('onlyshow')=="1")
				    	{
				    	    $(".pesonalbutton").css("display","none");
				    	}
				    	else
				    	{
								$("#Contact_card_dlag_personweb").attr("href",$("#contact_card_detail .personweburl").text());
								$(".pesonalname").attr("href",$("#contact_card_detail .personweburl").text());
					      $("#Contact_card_dlag_body urlloading").remove();
					      $("#Contact_card_dlag .modal-footer").css({"display":"","padding":"5px"});		
					      ContactCard.autoXY();
								$("#Contact_card_dlag .Contact_card_attention").text(function(){
									  var _st = $("#contact_card_detail .Contact_card_attention").attr("state");
									  return _st=="0"?"关注":(_st=="1"?"已关注":"互相关注");
								});
								$("#Contact_card_dlag .Contact_card_attention").attr("class",function(){
									  var bothState = $("#contact_card_detail .Contact_card_attention").attr("state");
									  return bothState=="0"?"Contact_card_attention Contact_card_attention_concern": (bothState=="1"? "Contact_card_attention Contact_card_attention_already":"Contact_card_attention Contact_card_attention_mutual");
								});
								ContactCard.empCards[ContactCard._account] = $("#Contact_card_dlag_body").html();
								$(".account_baseinfo:contains('"+ContactCard._account+"'):not([login_account])").attr("href",$("#Contact_card_dlag .personweburl").text());
								$(".account_baseinfo:contains('"+ContactCard._account+"'):not([login_account])").attr("login_account",$("#Contact_card_dlag .attention").attr("login_account"));
						 	}
					 	});
			}
			else
			{
			    $("#Contact_card_dlag_body").html(ContactCard.empCards[ContactCard._account]);
          ContactCard.autoXY();
          $(".account_baseinfo:contains('"+ContactCard._account+"'):not([login_account])").attr("href",$("#Contact_card_dlag .personweburl").text());
					$(".account_baseinfo:contains('"+ContactCard._account+"'):not([login_account])").attr("login_account",$("#Contact_card_dlag .Contact_card_attention").attr("login_account"));
			}
	 },
	 autoXY:function(){
	 	var _a1 = $("#Contact_card_dlag_attention");
	 	var _a2 = $("#contact_card_detail #attention");
    var attentionState = _a2.text();
		if($("#contact_card_detail .fafa_jid").text()=="")
		{
				    		  $("#Contact_card_dlag .pesonalbutton").html("");
    }
		else
		{
				    		 $("#Contact_card_dlag .pesonalbutton").css({"display":""});
				    		 _a1.attr("state",attentionState);
				    		 _a1.attr("login_account",_a2.attr("login_account"));
				    		 if(attentionState!="-1")
				    		     _a1.css({"display":""});
				    		 else
				    		 	   _a1.css({"display":"none"});
		}	 	
	 	var tmpDlg = 		  ContactCard.modalDlag; 
	  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
		t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
		l = l<150?l+20:l;
		l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
		tmpDlg.css({"top":t,"left":l});		 	 	
	 },
	 show:function(x,y,account){
	 	  this._account = account;
	 	  var l = x-($(document).scrollLeft()),t = y-$(document).scrollTop();
	 	  ContactCard.modalDlag.attr("x",l);
	 	  ContactCard.modalDlag.attr("y",t);
	 	  ContactCard.modalDlag.css({"top":t,"left":l,"margin":0});	 	      	
	    if(ContactCard.modalDlag.css("display")!="none")
	    {
	    	 if (ContactCard.empCards[ContactCard._account]!=null){
	    	    $("#Contact_card_dlag_body").html(ContactCard.empCards[ContactCard._account]);
	    	    ContactCard.autoXY();
	    	 }
	    	 else
	    	 	  ContactCard.modalDlag.trigger("shown");
	    }
	    else
	       ContactCard.modalDlag.modal("show");	
	 },
	 hide:function()
	 {
	 	  this._account = "";
	 	  if(ContactCard._ajaxObj!=null)
	 	  {
	 	  	  $("#Contact_card_dlag_body urlloading").remove();
	 	  	  ContactCard._ajaxObj.abort();//立即终止请求
	 	  }
	 	  $("#Contact_card_dlag_body").html("");
			ContactCard.modalDlag.modal("hide");
	 }
};				     		
