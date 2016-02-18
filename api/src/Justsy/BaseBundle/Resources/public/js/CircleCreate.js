var CircleCreate={
	circle_default_logo:null,
	user_default_logo:null,
	formid:null,
	saveUrl:null,
	mainUrl:null,
	circleType:[],
	pid:null,
	cid:null,
	re:null,
	creating:false,
	load:function(paras){
		CircleCreate.circle_default_logo=paras.circle_default_logo;
		CircleCreate.user_default_logo=paras.user_default_logo;
		CircleCreate.formid=paras.formid;
		CircleCreate.saveUrl=paras.saveUrl;
		CircleCreate.mainUrl=paras.mainUrl;
		CircleCreate.re=paras.re;
		CircleCreate.setCoverDiv();
		CircleCreate.init();
	},
	hidetrans:function(){
		$("#div_create_circle").find(".div_transparent").css('visibility','hidden');
	},
	setRightTrans:function(para){
		$("#div_create_circle").find("#right_trans").hide().css(para);
	},
	setPosition:function(){
		var coodirate=arguments[0]?arguments[0]:null;
		if(coodirate!=null){
			$("#div_create_circle").css({
				'left':coodirate.X.toString()+"px",
				'top':coodirate.Y.toString()+"px"
			});
			return;
		}
		var le=$(CircleCreate.re).offset().left;
		var to=$(CircleCreate.re).offset().top;
		var hei=$(CircleCreate.re).height();
		var wid=$(CircleCreate.re).width();
		$("#div_create_circle").css({
			'left':le.toString()+"px",
			'top':(to+hei+25).toString()+"px"
		});
	},
	init:function(){
		if($("#div_create_circle").length ==0){
			var html=[];
			html.push("<div id='div_create_circle'><span id='right_trans' style='display:none;' class='bordertransparent'></span>");
			html.push("<div style='width:auto;' class='circle_create_head circlesclass-title'><span>创建圈子</span>");
			html.push("<span class='div_transparent'></span><span class='div_circle_close'>×</span></div>");
			html.push("<div id='div_content_circle'></div>");
			html.push("</div>");
			$(document.body).append(html.join(''));
			$("#div_create_circle span.div_circle_close").click(function(){
				CircleCreate.close();
			});
			$("#div_content_circle").append("<div class='urlloading'><div /></div>");
			$("#div_content_circle").load(CircleCreate.mainUrl,{},function(){
				$("#div_content_circle urlloading").remove();
				CircleCreate.InviteManager.showNextMember();
			});
		}
		else{
			$("#div_create_circle").show();
		}
		CircleCreate.setPosition();
	},
	setCoverDiv:function(){
		var html=[];
		html.push("<div class='coverdivcircle'></div>");
		$(document.body).append(html.join(''));
	},
	seBntClick:function(id){
		
	},
	loadCircleType:function(json,pid,cid){
		CircleCreate.circleType=json;
		CircleCreate.pid=pid;
		CircleCreate.cid=cid;
		CircleCreate.setPClassify();
		CircleCreate.setClassify($("#"+CircleCreate.pid +" option:first").val());
		$("#"+CircleCreate.pid).change(function(){
			CircleCreate.setClassify($("#"+CircleCreate.pid +" option:selected").val());
		});
	},
	setPClassify:function(){
		var html=[];
		for(var i=0;i<CircleCreate.circleType.length;i++){
			var curr_type=CircleCreate.circleType[i];
			if(curr_type.id==curr_type.parent || curr_type.parent=='')
				html.push("<option value='"+curr_type.id+"'>"+curr_type.name+"</option>");
		}
		$("#"+CircleCreate.pid).html(null).append(html.join(''));
	},
	setClassify:function(pid){
		var html=[];
		for(var i=0;i<CircleCreate.circleType.length;i++){
			var curr_type=CircleCreate.circleType[i];
			if(curr_type.parent==pid)
				html.push("<option value='"+curr_type.id+"'>"+curr_type.name+"</option>");
		}
		$("#"+CircleCreate.cid).html(null).append(html.join(''));
	},
	showinviteput:function(e){
		$(e).find("span.invite_note").hide();
		var input=$(e).find("input[type='text']");
		input.val('').show().focus();
	},
	InviteManager:{
		currpage:0,
		pagecount:0,
		container:null,
		prebnt:null,
		nexbnt:null,
		loaded:[],
		getNajax:null,
		get_N_url:null,
		init:function(paras){
			var InviteManager=CircleCreate.InviteManager;
			InviteManager.container=paras.container;
			InviteManager.prebnt=paras.prebnt;
			InviteManager.nexbnt=paras.nexbnt;
			InviteManager.pagecount=paras.pagecount;
			InviteManager.get_N_url=paras.get_N_url;
			$(InviteManager.prebnt).hide();
			$(InviteManager.nexbnt).hide();
			if(InviteManager.pagecount>0){
				$("#div_create_circle").find(".circle_invite_member").show();
			}
		},
		setPreStatus:function(bool){
			var InviteManager=CircleCreate.InviteManager;
			if(bool){
				$(InviteManager.prebnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.prebnt).css({'cursor':'default'});
			}
		},
		setNexStatus:function(bool){
			var InviteManager=CircleCreate.InviteManager;
			if(bool){
				$(InviteManager.nexbnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.nexbnt).css({'cursor':'default'});
			}
		},
		getPageli:function(pageindex){
			var InviteManager=CircleCreate.InviteManager;
			return $(InviteManager.container).find("li[page='"+pageindex+"']");
		},
		showPreMember:function(){
			var InviteManager=CircleCreate.InviteManager;
			if(InviteManager.currpage==1)return;
			var curr=(InviteManager.currpage-1);
			if(curr==1){
				InviteManager.setPreStatus(false);
			}
			InviteManager.setNexStatus(true);
			if(InviteManager.getNajax!=null){
				InviteManager.getNajax.abort();
				InviteManager.getNajax=null;
			}
			InviteManager.showpage(curr);
		},
		showNextMember:function(){
			var InviteManager=CircleCreate.InviteManager;
			if(InviteManager.currpage==InviteManager.pagecount)return;
			var curr=(InviteManager.currpage+1);
			if(curr==InviteManager.pagecount)
			{
				InviteManager.setNexStatus(false);
			}
			if(curr==1){
				InviteManager.setPreStatus(false);
			}
			else
				InviteManager.setPreStatus(true);
			InviteManager.showpage(curr);
		},
		showpage:function(pageindex){
			var InviteManager=CircleCreate.InviteManager;
			var $lis=InviteManager.getPageli(pageindex);
			if($lis.length>0)
			{
				InviteManager.currpage=pageindex;
				$(InviteManager.container).find("li").hide();
				$lis.fadeIn(200);
			}
			else{
				if(InviteManager.getNajax!=null)return;
				InviteManager.currpage=pageindex;
				InviteManager.getNMember();
			}
		},
		check:function(e){
			var InviteManager=CircleCreate.InviteManager;
			var hid=$(e).parent().find("a[login_account]");
			if(hid.attr('check')=='1'){
				hid.attr('check','0');
				$(e).parent().css('border','1px solid #ccc');
				$(e).find("input[type='checkbox']").attr('checked',false);
			}
			else{
				hid.attr('check','1');
				$(e).parent().css('border','2px solid #F5B426');
				$(e).find("input[type='checkbox']").attr('checked','checked');
			}
			return;
			var d={
				login_account:hid.attr('login_account'),
				nick_name:hid.text()
			};
			InviteManager.addto(d);
		},
		addto:function(d){
			var e=$("#set_area_input");
			if($(e).siblings('span').find("input[login_account='"+d.login_account+"']").length>0){
				return;
			}
			var html="<span style='border:1px solid #CCC;cursor:default;margin-left:5px;margin-bottom:3px;line-height:15px;display:inline-block;'><input type='hidden' login_account='"+d.login_account+"' value='"+d.login_account+"'/>"+d.nick_name+"<span style='border:1px solid #CCC;cursor:pointer;' onclick='$(this).parent().remove();'>×</span></span>";
			$(e).before(html);
			$(e).siblings("span.invite_note").hide();
			$(e).show();
		},
		showbnt:function(){
			var InviteManager=CircleCreate.InviteManager;
			if(InviteManager.pagecount >1){
				$(InviteManager.prebnt).show();
				$(InviteManager.nexbnt).show();
			}
			else{
				$(InviteManager.prebnt).hide();
				$(InviteManager.nexbnt).hide();
			}
		},
		getNMember:function()
		{
				var InviteManager=CircleCreate.InviteManager;
		  	if(InviteManager.getNajax!=null)return;
		  	$("#memberloading").show();
				InviteManager.getNajax=$.post(InviteManager.get_N_url,{'pageindex':InviteManager.currpage},function(d){
					$("#memberloading").hide();
					if(d.length >0)
					{
						InviteManager.loadMember(d,InviteManager.currpage);
					}
					else{
					}
					InviteManager.getNajax=null;
					InviteManager.showbnt();
				});
		},
		loadMember:function(json,page)
		{
			var InviteManager=CircleCreate.InviteManager;
		  if (json == null) return;
		  var pn =$(InviteManager.container).find('ul');
		  pn.find("li").hide();
		  var html=[];
		  for(var i=0; i< json.length; i++)
		  {
		    var s='<li page="'+page+'" class="list" style="text-align: center;padding-top:5px;margin-left:13px;float:left;list-style:none outside none;"><div style="width:48px;height:48px;border:1px solid #CCC;"><div onclick="CircleCreate.InviteManager.check(this);" style="height:48px;"><img src="';
		    if (json[i].photo_path==null || json[i].photo_path=='')
		    {
		      s+=CircleCreate.user_default_logo;
		    }
		    else
		    {
		      s+=json[i].photo_path;
		    }
		    s+='" width="48" height="48" title="'+json[i].nick_name+'"><input type="checkbox" style="position: relative; right: -18px; top: -18px;" style="position:relative;"></div>';
		    s+='<div class="text" style="cursor:pointer;white-space: nowrap;width:50px;text-overflow: ellipsis; overflow: hidden; display: block;" title="'+json[i].nick_name+'"><a login_account="'+json[i].login_account+'" style="color: #666666;">'+json[i].nick_name+'</a></div></div>';
		    s+='<span><input type="hidden" value="'+json[i].login_account+'" /></span>';
		    s+='</li>';
		    html.push(s);
		  }
		  $s=$(html.join(''));
		  $s.hide();
		  pn.append($s);
		  $s.fadeIn(200);
		}
	},
	setInputEvent:function(e,url){
		var curr=e;
		var InviteManager=CircleCreate.InviteManager;
		var evobj=new WordMind(e,url,InviteManager.addto);
		evobj.init();
	},
	CheckCircleOrNetWork: function(values)
  {
     if (values=="")
     {
         $("#txtcircle").siblings('span.errspan').text('请输入圈子名称').show();
          $("#txtcircle").siblings('img.ok').hide();
         $('#txtcircle').siblings('img.loading').hide();
         //$('#txtcircle').focus();
         return false;
     }
     else
     {
     		 if($("#txtcircle").attr('check')=='1')return true;
     		 $("#txtcircle").siblings('span.errspan').hide();
     		 $("#txtcircle").siblings('img.ok').hide();
         $('#txtcircle').siblings('img.loading').show();
         $.post($('#txtcircle').attr("checkurl"),"type=1&parameter="+$('#txtcircle')[0].value,function(r)
         {
           if(r.exist)
           {
           	 $("#txtcircle").attr('check','0');
           	 $("#txtcircle").siblings('span.errspan').show().text("圈子名称已存在");
		     		 $("#txtcircle").siblings('img.ok').hide();
		         $('#txtcircle').siblings('img.loading').hide();           
           }
           else
           {
           	 $("#txtcircle").attr('check','1');
             $("#txtcircle").siblings('span.errspan').hide();
		     		 $("#txtcircle").siblings('img.ok').show();
		         $('#txtcircle').siblings('img.loading').hide();
		         if(CircleCreate.toSubmit)
		         	 CircleCreate.createSubmit($("div.createcirclebnt")[0]);
           }
         });
         return false;
     }
  },
	createSubmit:function(e){
		if(CircleCreate.creating)return;
		var InviteManager=CircleCreate.InviteManager;
		CircleCreate.toSubmit=true;
		if(!CircleCreate.CheckCircleOrNetWork($("#txtcircle").val()))return;
		
		var $vas=$("#circle_invite_container a[login_account][check='1']");
    	var s='';
    	for(var i=0;i<$vas.length;i++){
    		if(i==$vas.length-1){
    			s+=$($vas[i]).attr('login_account');
    		}
    		else
    			s+=$($vas[i]).attr('login_account')+";";
    	}
		var h=$(".circle_invite_set textarea").val().split(/[;|,| ，|；|\n|\t]/g);
		for(var i=0;i<h.length;i++){
			if(h[i]!='' && h[i]!=null && !(/^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/).test(h[i])){
				$("#inputerror").text('请填写正确的邮箱地址').css('color','red');
				setTimeout(function(){$("#inputerror").text('');},3000);
				return;
			}
			else{
				if(i==h.length-1)
					s+=h[i];
				else
					s+=h[i]+";";
			}
		}
		CircleCreate.creating=true;
	    $("#invitedmemebers").val(s);
	    $("#"+CircleCreate.formid).attr('action',CircleCreate.saveUrl);
	    $(e).find('span').text("提交中...");
		$("#"+CircleCreate.formid)[0].submit();
		//creating=false;
	},
	close:function(){
		$("#div_create_circle").hide();
		$(document.body).children("div.coverdivcircle").remove();
	}
};
var WordMind=function(res,loadurl,callback){
	this.res=res;
	this.loadurl=loadurl;
	this.keyword=null;
	this.enocontainter=null; 
	this.callback=callback;
	this.searchajax=null;
	this.init=function(){
		var obj=this;
		$(this.res).keyup(function(){
			var ev=event||window.event;
			if(ev.keyCode==13){
			}
				
		});
		/*
		$(this.res).blur(function(){
		setTimeout(function(){
			$(".share_invite_list").remove();
		},200);
	});
	$(this.res).keydown(function(event){
  		var ev=event||window.event;
  		if(ev.keyCode==13)
  		{
  			$ul=$(".share_invite_list");
  			if($ul.length >0)
  				obj.check($ul.find("li[sel='1']")[0]);
  			else{
  				if(/^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/.test($(this).val()) && obj.searchajax==null){
  					var d={
  						login_account:$(this).val(),
  						nick_name:'未注册用户'
  					};
  					obj.callback(d);
  					$(this).val('');
						$(".share_invite_list").remove();
  				}
  			}
  		}
  	});
	//联想事件
  	$(this.res).keyup(function(event){
  		var ev=event||window.event;
  		var $this=$(this);
  		//上下键
  		if(ev.keyCode==38 || ev.keyCode==40)
  		{
  			$ul=$(".share_invite_list");
  			if($ul.find('li').length>1)
  			{
  				$li=$ul.find("li[sel='1']");
  				var $curr;
  				if(ev.keyCode==40){
	  				$curr=$li.next().length==0?$ul.find("li:first"):$li.next();
	  			}
	  			else{
	  				$curr=$li.prev().length==0?$ul.find("li:last"):$li.prev();
	  			}
	  			obj.selectli($curr[0]);
  			}
  		}
  		//Enter键
      if (typeof(obj.keyword) != null && obj.keyword == $this.val())
          return;
      obj.keyword = $this.val();
      if (obj.enocontainter == null) {
          obj.enocontainter = new HashTable();
      }
      if (true) {
      	obj.LoadData(null,{'searchby':obj.keyword}, function(d) {
              if (d.length > 0) {
              		obj.enocontainter.clear();
                 	for(var i=0;i<d.length;i++)
                 	{
                 		obj.enocontainter.push(d[i].login_account,d[i].nick_name);
                 	}
                  obj.createEnoList(obj.keyword);
              }
              else{
              	$(".share_invite_list").remove();
              }
          });
      }
  	});
  	*/
	};
	this.selectli=function(e)
	{
		$(e).siblings().css({'background-color':''}).attr('sel','0');
		$(e).css({'background-color':'#0080c0'}).attr('sel','1');
	};
	this.check=function(e)
	{
		var d={
			login_account:$(e).find('a').attr('login_account'),
			nick_name:$(e).find('a').text()
		};
		this.callback(d);
		$(this.res).val('');
		$(".share_invite_list").remove();
	};
	this.createEnoList=function(keyword)
	{
		var obj=this;
		$(".share_invite_list").remove();
		var html=[];
		html.push("<ul style='z-index:30000;' class='share_invite_list'>");
		for(var i=0;i<this.enocontainter.length;i++)
		{
			html.push("<li><a login_account='"+this.enocontainter.array[i].key+"'>"+this.enocontainter.array[i].val+"</a>("+this.enocontainter.array[i].key+")</li>");
		}
		html.push("</ul>");
		$ul=$(html.join(''));
		var $sysmanager=$(this.res);
		var le= $sysmanager.offset().left;
		var to= $sysmanager.offset().top;
		var wid= $sysmanager.height();
		$ul.css({left:le.toString()+'px',top:(to+wid).toString()+'px'});
		$ul.find('li').mouseover(function(event){
			if(checkHover(event,this))
			{
				obj.selectli(this);
			}
		});
		$ul.find("li").click(function(){
			obj.check(this);
		});
		obj.selectli($ul.find('li:first')[0]);
		//$ul.find('li:first').css({'background-color':'#0080c0'});
		$(document.body).append($ul);
	};
	this.LoadData=function(e_curr,params, callback) {
    if(this.searchajax!=null)
  		this.searchajax.abort();
  	var obj=this;
    this.searchajax=$.post(this.loadurl, params, function(data) {
        callback(data);
        obj.searchajax=null;
    });
	};
};
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
var EditCircle={
	circle_default_logo:null,
	user_default_logo:null,
	formid:null,
	saveUrl:null,
	mainUrl:null,
	circleType:[],
	pid:null,
	cid:null,
	re:null,
	toSubmit:true,
	load:function(paras){
		EditCircle.circle_default_logo=paras.circle_default_logo;
		EditCircle.user_default_logo=paras.user_default_logo;
		EditCircle.formid=paras.formid;
		EditCircle.saveUrl=paras.saveUrl;
		EditCircle.mainUrl=paras.mainUrl;
		EditCircle.re=paras.re;
		EditCircle.setCoverDiv();
		EditCircle.init();
	},
	hidetrans:function(){
		$("#div_create_circle").find(".div_transparent").css('visibility','hidden');
	},
	setRightTrans:function(para){
		$("#div_create_circle").find("#right_trans").show().css(para);
	},
	setPosition:function(){
		var coodirate=arguments[0]?arguments[0]:null;
		if(coodirate!=null){
			$("#div_create_circle").css({
				'left':coodirate.X.toString()+"px",
				'top':coodirate.Y.toString()+"px"
			});
			return;
		}
		var le=$(EditCircle.re).offset().left;
		var to=$(EditCircle.re).offset().top;
		var hei=$(EditCircle.re).height();
		var wid=$(EditCircle.re).width();
		$("#div_create_circle").css({
			'left':le.toString()+"px",
			'top':(to+hei+25).toString()+"px"
		});
	},
	init:function(){
		if($("#div_create_circle").length ==0){
			var html=[];
			html.push("<div id='div_create_circle'><span id='right_trans' style='display:none;' class='bordertransparent'></span>");
			html.push("<div style='width:auto;' class='circle_create_head circlesclass-title'><span>圈子设置</span>");
			html.push("<span class='div_transparent'></span><span class='div_edit_close'>×</span></div>");
			html.push("<div id='div_content_circle'></div>");
			html.push("</div>");
			$(document.body).append(html.join(''));
			$("#div_create_circle span.div_edit_close").click(function(){
				EditCircle.close();
			});
			$("#div_content_circle").append("<div class='urlloading'><div /></div>");
			$("#div_content_circle").load(EditCircle.mainUrl,{},function(){
				$("#div_content_circle urlloading").remove();
				EditCircle.InviteManager.showNextMember();
			});
		}
		else{
			$("#div_create_circle").show();
		}
		EditCircle.setPosition();
	},
	setCoverDiv:function(){
		var html=[];
		html.push("<div class='coverdiv'></div>");
		$(document.body).append(html.join(''));
	},
	seBntClick:function(id){
		
	},
	loadCircleType:function(json,pid,cid){
		EditCircle.circleType=json;
		EditCircle.pid=pid;
		EditCircle.cid=cid;
		EditCircle.setPClassify();
		EditCircle.setClassify($("#"+EditCircle.pid +" option:first").val());
		$("#"+EditCircle.pid).change(function(){
			EditCircle.setClassify($("#"+EditCircle.pid +" option:selected").val());
		});
	},
	setPClassify:function(){
		var html=[];
		for(var i=0;i<EditCircle.circleType.length;i++){
			var curr_type=EditCircle.circleType[i];
			if(curr_type.classify_id==curr_type.parent_classify_id || curr_type.parent_classify_id=='')
				html.push("<option value='"+curr_type.classify_id+"'>"+curr_type.classify_name+"</option>");
		}
		$("#"+EditCircle.pid).html(null).append(html.join(''));
	},
	setClassify:function(pid){
		var html=[];
		for(var i=0;i<EditCircle.circleType.length;i++){
			var curr_type=EditCircle.circleType[i];
			if(curr_type.parent_classify_id==pid)
				html.push("<option value='"+curr_type.classify_id+"'>"+curr_type.classify_name+"</option>");
		}
		$("#"+EditCircle.cid).html(null).append(html.join(''));
	},
	showinviteput:function(e){
		$(e).find("span.invite_note").hide();
		var input=$(e).find("input[type='text']");
		input.val('').show().focus();
	},
	InviteManager:{
		currpage:0,
		pagecount:0,
		container:null,
		prebnt:null,
		nexbnt:null,
		loaded:[],
		getNajax:null,
		get_N_url:null,
		init:function(paras){
			var InviteManager=EditCircle.InviteManager;
			InviteManager.container=paras.container;
			InviteManager.prebnt=paras.prebnt;
			InviteManager.nexbnt=paras.nexbnt;
			InviteManager.pagecount=paras.pagecount;
			InviteManager.get_N_url=paras.get_N_url;
			$(InviteManager.prebnt).hide();
			$(InviteManager.nexbnt).hide();
			if(InviteManager.pagecount>0){
				$("#div_create_circle").find(".circle_invite_member").show();
			}
		},
		setPreStatus:function(bool){
			var InviteManager=EditCircle.InviteManager;
			if(bool){
				$(InviteManager.prebnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.prebnt).css({'cursor':'default'});
			}
		},
		setNexStatus:function(bool){
			var InviteManager=EditCircle.InviteManager;
			if(bool){
				$(InviteManager.nexbnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.nexbnt).css({'cursor':'default'});
			}
		},
		getPageli:function(pageindex){
			var InviteManager=EditCircle.InviteManager;
			return $(InviteManager.container).find("li[page='"+pageindex+"']");
		},
		showPreMember:function(){
			var InviteManager=EditCircle.InviteManager;
			if(InviteManager.currpage==1)return;
			var curr=(InviteManager.currpage-1);
			if(curr==1){
				InviteManager.setPreStatus(false);
			}
			InviteManager.setNexStatus(true);
			if(InviteManager.getNajax!=null){
				InviteManager.getNajax.abort();
				InviteManager.getNajax=null;
			}
			InviteManager.showpage(curr);
		},
		showNextMember:function(){
			var InviteManager=EditCircle.InviteManager;
			if(InviteManager.currpage==InviteManager.pagecount)return;
			var curr=(InviteManager.currpage+1);
			if(curr==InviteManager.pagecount)
			{
				InviteManager.setNexStatus(false);
			}
			if(curr==1){
				InviteManager.setPreStatus(false);
			}
			else
				InviteManager.setPreStatus(true);
			InviteManager.showpage(curr);
		},
		showpage:function(pageindex){
			var InviteManager=EditCircle.InviteManager;
			var $lis=InviteManager.getPageli(pageindex);
			if($lis.length>0)
			{
				InviteManager.currpage=pageindex;
				$(InviteManager.container).find("li").hide();
				$lis.fadeIn(200);
			}
			else{
				if(InviteManager.getNajax!=null)return;
				InviteManager.currpage=pageindex;
				InviteManager.getNMember();
			}
		},
		check:function(e){
			var InviteManager=EditCircle.InviteManager;
			var hid=$(e).parent().find("a[login_account]");
			if(hid.attr('check')=='1'){
				hid.attr('check','0');
				$(e).parent().css('border','1px solid #ccc');
			}
			else{
				hid.attr('check','1');
				$(e).parent().css('border','2px solid #F5B426');
			}
			return;
			var d={
				login_account:hid.attr('login_account'),
				nick_name:hid.text()
			};
			InviteManager.addto(d);
		},
		addto:function(d){
			var e=$("#set_area_input");
			if($(e).siblings('span').find("input[login_account='"+d.login_account+"']").length>0){
				return;
			}
			var html="<span style='border:1px solid #CCC;cursor:default;margin-left:5px;margin-bottom:3px;line-height:15px;display:inline-block;'><input type='hidden' login_account='"+d.login_account+"' value='"+d.login_account+"'/>"+d.nick_name+"<span style='border:1px solid #CCC;cursor:pointer;' onclick='$(this).parent().remove();'>×</span></span>";
			$(e).before(html);
			$(e).siblings("span.invite_note").hide();
			$(e).show();
		},
		showbnt:function(){
			var InviteManager=EditCircle.InviteManager;
			if(InviteManager.pagecount >1){
				$(InviteManager.prebnt).show();
				$(InviteManager.nexbnt).show();
			}
			else{
				$(InviteManager.prebnt).hide();
				$(InviteManager.nexbnt).hide();
			}
		},
		getNMember:function()
		{
				var InviteManager=EditCircle.InviteManager;
		  	if(InviteManager.getNajax!=null)return;
		  	$("#memberloading").show();
				InviteManager.getNajax=$.post(InviteManager.get_N_url,{'pageindex':InviteManager.currpage},function(d){
					$("#memberloading").hide();
					if(d.length >0)
					{
						InviteManager.loadMember(d,InviteManager.currpage);
					}
					else{
					}
					InviteManager.getNajax=null;
					InviteManager.showbnt();
				});
		},
		loadMember:function(json,page)
		{
			var InviteManager=EditCircle.InviteManager;
		  if (json == null) return;
		  var pn =$(InviteManager.container).find('ul');
		  pn.find("li").hide();
		  var html=[];
		  for(var i=0; i< json.length; i++)
		  {
		    var s='<li page="'+page+'" class="list" style="text-align: center;padding-top:5px;margin-left:13px;float:left;list-style:none outside none;"><div style="width:48px;height:48px;border:1px solid #CCC;"><div onclick="EditCircle.InviteManager.check(this);" style="height:48px;"><img style="*height:100%;" src="';
		    if (json[i].photo_path==null || json[i].photo_path=='')
		    {
		      s+=EditCircle.user_default_logo;
		    }
		    else
		    {
		      s+=json[i].photo_path;
		    }
		    s+='" width="48" height="48" title="'+json[i].nick_name+'"><input type="checkbox" style="position: relative; right: -18px; top: -18px;" style="position:relative;"></div>';
		    s+='<div class="text" style="cursor:pointer;white-space: nowrap;width:50px;text-overflow: ellipsis; overflow: hidden; display: block;" title="'+json[i].nick_name+'"><a login_account="'+json[i].login_account+'" style="color: #666666;">'+json[i].nick_name+'</a></div></div>';
		    s+='<span><input type="hidden" value="'+json[i].login_account+'" /></span>';
		    s+='</li>';
		    html.push(s);
		  }
		  $s=$(html.join(''));
		  $s.hide();
		  pn.append($s);
		  $s.fadeIn(200);
		}
	},
	setInputEvent:function(e,url){
		var curr=e;
		var InviteManager=EditCircle.InviteManager;
		var evobj=new WordMind(e,url,InviteManager.addto);
		evobj.init();
	},
	CheckCircleOrNetWork: function(values)
  {
     if (values=="")
     {
         $("#circle_name").siblings('span.errspan').text('请输入圈子名称').show();
          $("#circle_name").siblings('img.ok').hide();
         $('#circle_name').siblings('img.loading').hide();
         //$('#txtcircle').focus();
         return false;
     }
     else
     {
     		 if($("#circle_name").attr('check')=='1'){return true};
     		 $("#circle_name").siblings('span.errspan').hide();
     		 $("#circle_name").siblings('img.ok').hide();
         $('#circle_name').siblings('img.loading').show();
         $.post($('#circle_name').attr("checkurl"),"type=1&circle_name="+$('#circle_name')[0].value,function(r)
         {
           if(r.exist)
           {
           	 $("#circle_name").attr('check','0');
           	 $("#circle_name").siblings('span.errspan').show().text("圈子名称已存在");
		     		 $("#circle_name").siblings('img.ok').hide();
		         $('#circle_name').siblings('img.loading').hide();
		         EditCircle.createSubmit($("div.createcirclebnt")[0]);           
           }
           else
           {
           	 $("#circle_name").attr('check','1');
             $("#circle_name").siblings('span.errspan').hide();
		     		 $("#circle_name").siblings('img.ok').show();
		         $('#circle_name').siblings('img.loading').hide();
		         EditCircle.createSubmit($("div.createcirclebnt")[0]);  
           }
         });
         return false;
     }
  },
	createSubmit:function(e){
		var InviteManager=EditCircle.InviteManager;
		EditCircle.toSubmit=true;
		if(!EditCircle.CheckCircleOrNetWork($("#txtcircle").val()))return;
		var array_manager=$.unique(
		  $("#InputNotifyArea input").map(function(){return $(this).val();}).toArray()
		 		 ).join(";");
		 $("#array_manager").val(array_manager);
    $("#"+EditCircle.formid).attr('action',EditCircle.saveUrl);
    $(e).find('span').text("提交中...");
    var sender=e;
		$("#"+EditCircle.formid).ajaxSubmit({
			url:EditCircle.saveUrl,
			type:'post',
			dataType:'json',
			success:function(d){
				$(sender).find('span').text("提交");	
				if(d.success){
					$("#submiterror").text("修改成功").css('color','#5592CB');
				}
				else{
					$("#submiterror").text("修改失败").css('color','red');
				}
			}
		});
	},
	close:function(){
		$("#div_create_circle").hide();
		$(document.body).children("div.coverdiv").remove();
	}
};