var circle_setting={
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
getEvent:function()
{
 if(document.all)    return window.event;//如果是ie
 var func=this.getEvent.caller;
        while(func!=null){
            var arg0=func.arguments[0];
            if(arg0){
            	if((arg0.constructor==Event || arg0.constructor ==MouseEvent) || (typeof(arg0)=="object" && arg0.preventDefault && arg0.stopPropagation))
            	{
            		return arg0;
            	}
            }
            func=func.caller;
        }
       return null;
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
checkCircle_name:function(path,v){
	 var circle_name=$(v).val();
	 var pattern=/\s*\S{2,}\s*/;
	 if(!pattern.test(circle_name))
	 {
	 	$(v).siblings("span").text("名称不得少于两个字符！");
	 	$(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 	$(v).siblings("img").show();
	 	$(v).focus();
	 	g_return=false;
	 	return false;
	 }
	 $.post(path,{"circle_name":circle_name},function(json){
	 	 if(json.exist)
	 	 {
	 	 	$(v).siblings("span").text("圈子名称已经存在！");
	 	 	$(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 	 	$(v).siblings("img").show();
	 	 	$(v).focus();
	 	 	g_return=false;
	 	 }
	 	 else
	 	 {
	 	 	g_return=true;
	 	 	$(v).siblings("span").text("");
	 	 	$(v).siblings("img").hide();
	 	 	document.getElementById('btnSave').disabled=false;
	 	 }
	 	},"json");
},
datasource:function(q,process)
{
	$.getJSON(manager_query_url,{q:this.query,network_domain:network_domain,t:new Date().getTime()},function(json)
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
manager_matcher:function(item){
	 if(this.query)
	 {
	 	return ~item.login_account.toLowerCase().indexOf(this.query.toLowerCase())|| ~item.nick_name.indexOf(this.query);

	 } 
   return false;
},
manager_sorter:function(items ){
	return items;
},
manager_highlighter:function(item)
{
	 		return "<strong>"+item.nick_name+"("+item.login_account+")</strong>";
},
manager_updater:function(itemIndex)
{
	a=datasource;
	if ($("input[value='"+a[itemIndex].login_account+"']", $("#InputNotifyArea")).length == 0)
  {
  		$("#InputNotifyArea").append(GetNotifyTemplate(a[itemIndex].login_account,a[itemIndex].nick_name)); 
	}
	return "";
}
};

var CircleCard={ 
	 hoverTimer:null,
	 outTimer:null,
	 _circle_id:"",
	 _ajaxObj:null,
	 circleCards :[],
	 modalDlag:null,
	 Aurl:"",
	 
	 bind:function()
	 {
			//鼠标移除层区域后，触发mouseout事件，把整个层隐藏  
			$('.circle_name').live('mouseout', function(e) {
				  
			    if(checkHover(e,this)){
			    	   clearTimeout(CircleCard.hoverTimer);
			         CircleCard.outTimer = setTimeout("CircleCard.hide()",500);
			    }
			});
			$('.circle_name').live('mouseover',function(e) {  
			        	  clearTimeout(CircleCard.outTimer);
			            if(checkHover(e,this)){
			            	  var ex = getEventCoord(e);			  
//			            	  var txt = $(this).text();  
			            	  var acc = $(this).attr("circle_id");	  
//			            	  if(acc!=null && acc!="")
//			            	  {
////			            	  	  $(this).attr("target","_blank");
////			            	  	  $(this).attr("href",CircleCard.personUrl.replace("foo",acc));
//			            	  	  txt = acc;
//			            	  }
//			            	  else if(CircleCard.circleCards[txt]==null)
//			            	  {			            	  	 
//			            	      var isload = $(this).attr("isload");
//			            	      if(isload!="1" && isload!="2") //1：已获取数据 2：正在获取数据
//			            	      {
////			            	      	  $(this).attr("target","_self");
////			            	          $(this).attr("href","javascript:CircleCard.getEmpAccount('"+txt+"')");
//			            	      }
//			            	  }
                      if (!$(this).attr("href")) $(this).attr("href", "javascript:;");
			                CircleCard.hoverTimer = setTimeout(" CircleCard.show("+(ex.pageX)+","+(ex.pageY)+",'"+acc+"')",500);
			            }
			});	
	 },
	 
	 load:function(_Aurl)
	 {
	 	  this.Aurl=_Aurl;
	 	  var _cx = $("#circle_card_dlag");
	 	  if(_cx.length==0)
	 	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="circle_card_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="411px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="0px 0px 0px 0px";
	 	  		zIndex = 1000000;
	 	  	}
	 	  	_cx.innerHTML="<div id='circle_card_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	this.modalDlag = $("#circle_card_dlag"); 
	 	  	if(this.modalDlag.modal==null) return;
	 	  	this.modalDlag.modal({show:false,backdrop:false});
	 	  	this.modalDlag.on('shown', {Aurl: _Aurl}, CircleCard.getInfo);	 
	 	  }
			this.modalDlag.live('mouseout', function(e) {
				  clearTimeout(CircleCard.hoverTimer);
			    if(checkHover(e,this)){
			         CircleCard.outTimer = setTimeout("CircleCard.hide()",500);
			    }
			});  
			this.modalDlag.live('mouseover',function(e) {  
			        	  clearTimeout(CircleCard.outTimer);			            
			});
			//-----------------------------------------------------------------
			//绑定所有人员姓名标签事件、样式及状态切换
			//-----------------------------------------------------------------
      this.bind();
	 },
	 getInfo:function(para){
	 	  $("#circle_card_dlag_body").html("");
			if (CircleCard.circleCards[CircleCard._circle_id]==null)
			{
				    $("#circle_card_dlag .modal-footer").css({"display":"none"});
			      $("#circle_card_dlag_body").append("<div class='urlloading'><div /></div>");
				    CircleCard._ajaxObj=$.get( para.data.Aurl, {circle_id : CircleCard._circle_id, t: new Date().getTime()},
				    function (d) 
				    {
				    	CircleCard._ajaxObj=null;
				    	if(d.length==0) return;
				    	$("#circle_card_dlag_body").html(d);
				    	
				      $("#circle_card_dlag_body urlloading").remove();
				      $("#circle_card_dlag .modal-footer").css({"display":"","padding":"5px"});		
				      CircleCard.autoXY();
							CircleCard.circleCards[CircleCard._circle_id] = $("#circle_card_dlag_body").html();
					 	});
			}
			else
			{
			    $("#circle_card_dlag_body").html(CircleCard.circleCards[CircleCard._circle_id]);
          CircleCard.autoXY();
			}
	 },
	 autoXY:function(){
	 	var tmpDlg = 		  CircleCard.modalDlag; 
	  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
		t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
		l = l<150?l+20:l;
		l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
		tmpDlg.css({"top":t,"left":l});		 	 	
	 },
	 show:function(x,y,account){
	 	  this._circle_id = account;
	 	  var l = x-($(document).scrollLeft()),t = y-$(document).scrollTop();
	 	  CircleCard.modalDlag.attr("x",l);
	 	  CircleCard.modalDlag.attr("y",t);
	 	  CircleCard.modalDlag.css({"top":t,"left":l,"margin":0,"z-index":312323232});	 	      	
	    if(CircleCard.modalDlag.css("display")!="none")
	    {
	    	 if (CircleCard.circleCards[CircleCard._circle_id]!=null){
	    	    $("#circle_card_dlag_body").html(CircleCard.circleCards[CircleCard._circle_id]);
	    	    CircleCard.autoXY();
	    	 }
	    	 else
	    	 	  CircleCard.modalDlag.trigger("shown");
	    }
	    else
	       CircleCard.modalDlag.modal("show");	
	 },
	 hide:function()
	 {
	 	  this._circle_id = "";
	 	  if(CircleCard._ajaxObj!=null)
	 	  {
	 	  	  $("#circle_card_dlag_body urlloading").remove();
	 	  	  CircleCard._ajaxObj.abort();//立即终止请求
	 	  }
	 	  $("#circle_card_dlag_body").html("");
			CircleCard.modalDlag.modal("hide");
			if($(".circle_list").css("display")!="none")
			{
			    hidePanel($("#topmenu_circle_list"));	
			}
	 }
};

var GroupCard={ 
	 hoverTimer:null,
	 outTimer:null,
	 _group_id:"",
	 _ajaxObj:null,
	 groupCards :[],
	 modalDlag:null,
	 Aurl:"",
	 
	 bind:function()
	 {
			//鼠标移除层区域后，触发mouseout事件，把整个层隐藏  
			$('.group_name').live('mouseout', function(e) {				  
			    if(checkHover(e,this)){
			    	   clearTimeout(GroupCard.hoverTimer);
			         GroupCard.outTimer = setTimeout("GroupCard.hide()",500);
			    }
			});
			$('.group_name').live('mouseover',function(e) {  
			        	  clearTimeout(GroupCard.outTimer);
			            if(checkHover(e,this)){
			            	  var ex = getEventCoord(e);			  
//			            	  var txt = $(this).text();  
			            	  var acc = $(this).attr("group_id");	  
//			            	  if(acc!=null && acc!="")
//			            	  {
////			            	  	  $(this).attr("target","_blank");
////			            	  	  $(this).attr("href",GroupCard.personUrl.replace("foo",acc));
//			            	  	  txt = acc;
//			            	  }
//			            	  else if(GroupCard.groupCards[txt]==null)
//			            	  {			            	  	 
//			            	      var isload = $(this).attr("isload");
//			            	      if(isload!="1" && isload!="2") //1：已获取数据 2：正在获取数据
//			            	      {
////			            	      	  $(this).attr("target","_self");
////			            	          $(this).attr("href","javascript:GroupCard.getEmpAccount('"+txt+"')");
//			            	      }
//			            	  }
                      if (!$(this).attr("href")) $(this).attr("href", "javascript:;");
			                GroupCard.hoverTimer = setTimeout(" GroupCard.show("+(ex.pageX)+","+(ex.pageY)+",'"+acc+"')",500);
			            }
			});	
	 },
	 
	 load:function(_Aurl)
	 {
	 	  this.Aurl=_Aurl;
	 	  var _cx = $("#group_card_dlag");
	 	  if(_cx.length==0)
	 	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="group_card_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="411px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="0px 0px 0px 0px";
	 	  	}
	 	  	_cx.innerHTML="<div id='group_card_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	this.modalDlag = $("#group_card_dlag"); 
	 	  	if(this.modalDlag.modal==null) return;
	 	  	this.modalDlag.modal({show:false,backdrop:false});
	 	  	this.modalDlag.on('shown', {Aurl: _Aurl}, GroupCard.getInfo);	 
	 	  }
			this.modalDlag.live('mouseout', function(e) {
				  clearTimeout(GroupCard.hoverTimer);
			    if(checkHover(e,this)){
			         GroupCard.outTimer = setTimeout("GroupCard.hide()",500);
			    }
			});  
			this.modalDlag.live('mouseover',function(e) {  
			        	  clearTimeout(GroupCard.outTimer);			            
			});
			//-----------------------------------------------------------------
			//绑定所有人员姓名标签事件、样式及状态切换
			//-----------------------------------------------------------------
      this.bind();
	 },
	 getInfo:function(para){
	 	  $("#group_card_dlag_body").html("");
			if (GroupCard.groupCards[GroupCard._group_id]==null)
			{
				    $("#group_card_dlag .modal-footer").css({"display":"none"});
			      $("#group_card_dlag_body").append("<div class='urlloading'><div /></div>");
				    GroupCard._ajaxObj=$.get( para.data.Aurl, {group_id : GroupCard._group_id, t: new Date().getTime()},
				    function (d) 
				    {
				    	GroupCard._ajaxObj=null;
				    	if(d.length==0) return;
				    	$("#group_card_dlag_body").html(d);
				    	
				      $("#group_card_dlag_body urlloading").remove();
				      $("#group_card_dlag .modal-footer").css({"display":"","padding":"5px"});		
				      GroupCard.autoXY();
							GroupCard.groupCards[GroupCard._group_id] = $("#group_card_dlag_body").html();
					 	});
			}
			else
			{
			    $("#group_card_dlag_body").html(GroupCard.groupCards[GroupCard._group_id]);
          GroupCard.autoXY();
			}
	 },
	 autoXY:function(){
	 	var tmpDlg = 		  GroupCard.modalDlag; 
	  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
		t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
		l = l<150?l+20:l;
		l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
		tmpDlg.css({"top":t,"left":l});		 	 	
	 },
	 show:function(x,y,account){
	 	  this._group_id = account;
	 	  var l = x-($(document).scrollLeft()),t = y-$(document).scrollTop();
	 	  GroupCard.modalDlag.attr("x",l);
	 	  GroupCard.modalDlag.attr("y",t);
	 	  GroupCard.modalDlag.css({"top":t,"left":l,"margin":0});	 	      	
	    if(GroupCard.modalDlag.css("display")!="none")
	    {
	    	 if (GroupCard.groupCards[GroupCard._group_id]!=null){
	    	    $("#group_card_dlag_body").html(GroupCard.groupCards[GroupCard._group_id]);
	    	    GroupCard.autoXY();
	    	 }
	    	 else
	    	 	  GroupCard.modalDlag.trigger("shown");
	    }
	    else
	       GroupCard.modalDlag.modal("show");	
	 },
	 hide:function()
	 {
	 	  this._group_id = "";
	 	  if(GroupCard._ajaxObj!=null)
	 	  {
	 	  	  $("#group_card_dlag_body urlloading").remove();
	 	  	  GroupCard._ajaxObj.abort();//立即终止请求
	 	  }
	 	  $("#group_card_dlag_body").html("");
			GroupCard.modalDlag.modal("hide");
	 }
};
