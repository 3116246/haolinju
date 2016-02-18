var GroupCreate={
	group_default_logo:null,
	user_default_logo:null,
	formid:null,
	saveUrl:null,
	mainUrl:null,
	groupType:[],
	pid:null,
	re:null,
	load:function(paras){
		GroupCreate.group_default_logo=paras.group_default_logo;
		GroupCreate.user_default_logo=paras.user_default_logo;
		GroupCreate.formid=paras.formid;
		GroupCreate.saveUrl=paras.saveUrl;
		GroupCreate.mainUrl=paras.mainUrl;
		GroupCreate.re=paras.re;
		GroupCreate.setCoverDiv();
		GroupCreate.init();
	},
	hidetrans:function(){
		$("#div_create_group").find(".div_transparent").css('visibility','hidden');
	},
	setRightTrans:function(para){
		$("#div_create_group").find("#right_trans").hide().css(para);
	},
	setLeftTrans:function(para){
		$("#div_create_group").find(".left_trans").css(para).hide();
	},
	showtransparent:function(direct){
		if(direct=='left')
			$("#div_create_group").find(".left_trans").hide();
	},
	setPosition:function(){
		var coodirate=arguments[0]?arguments[0]:null;
		if(coodirate!=null){
			$("#div_create_group").css({
				'left':coodirate.X.toString()+"px",
				'top':coodirate.Y.toString()+"px"
			});
			return;
		}
		var $p = $(GroupCreate.re).parent();
		var le=$p.offset(),hei=$p.height(),wid=$p.width();
		$("#div_create_group").css({
			'position':"fixed",
			'left':"50%",
			'margin-left':"-285px",
			'top':"70px"
		});
	},
	init:function(){
		if($("#div_create_group").length ==0){
			var html=[];
			html.push("<div id='div_create_group'>");
			html.push("<div style='width:auto;' class='circle_create_head circlesclass-title'><span>创建群组</span>");
			html.push("<span class='div_close'>×</span></div>");
			html.push("<div id='div_content_group'></div>");
			html.push("</div>");
			$(document.body).append(html.join(''));
			$("#div_create_group span.div_close").click(function(){
				GroupCreate.close();
			});
			$("#div_content_group").append("<div class='urlloading'><div /></div>");
			$("#div_content_group").load(GroupCreate.mainUrl,{},function(){
				$("#div_content_group urlloading").remove();
				GroupCreate.InviteManager.showNextMember();
			});
		}
		else{
			$("#div_create_group").show();
		}
		GroupCreate.setPosition();
	},
	setCoverDiv:function(){
		var html=[];
		html.push("<div class='coverdiv'></div>");
		$(document.body).append(html.join(''));
	},
	seBntClick:function(id){
		
	},
	loadGroupType:function(json,pid){
		GroupCreate.groupType=json;
		GroupCreate.pid=pid;
		GroupCreate.setPClassify();
	},
	setPClassify:function(){
		var html=[];
		for(var i=0;i<GroupCreate.groupType.length;i++){
			var curr_type=GroupCreate.groupType[i];
				html.push("<option value='"+curr_type.typeid+"'>"+curr_type.typename+"</option>");
		}
		$("#"+GroupCreate.pid).html(null).append(html.join(''));
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
			var InviteManager=GroupCreate.InviteManager;
			InviteManager.container=paras.container;
			InviteManager.prebnt=paras.prebnt;
			InviteManager.nexbnt=paras.nexbnt;
			InviteManager.pagecount=paras.pagecount;
			InviteManager.get_N_url=paras.get_N_url;
			$(InviteManager.prebnt).hide();
			$(InviteManager.nexbnt).hide();
			if(InviteManager.pagecount>0){
				$("#div_create_group").find(".group_invite_member").show();
			}
		},
		setPreStatus:function(bool){
			var InviteManager=GroupCreate.InviteManager;
			if(bool){
				$(InviteManager.prebnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.prebnt).css({'cursor':'default'});
			}
		},
		setNexStatus:function(bool){
			var InviteManager=GroupCreate.InviteManager;
			if(bool){
				$(InviteManager.nexbnt).css({'cursor':'pointer'});
			}
			else{
				$(InviteManager.nexbnt).css({'cursor':'default'});
			}
		},
		getPageli:function(pageindex){
			var InviteManager=GroupCreate.InviteManager;
			return $(InviteManager.container).find("li[page='"+pageindex+"']");
		},
		showPreMember:function(){
			var InviteManager=GroupCreate.InviteManager;
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
			var InviteManager=GroupCreate.InviteManager;
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
			var InviteManager=GroupCreate.InviteManager;
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
			var InviteManager=GroupCreate.InviteManager;
			var hid=$(e).parent().find("a[login_account]");
			if(hid.attr('check')=='1'){
				hid.attr('check','0');
				$(e).find("input[type='checkbox']").attr('checked',false);
				$(e).parent().css('border','1px solid #ccc');
			}
			else{
				hid.attr('check','1');
				$(e).find("input[type='checkbox']").attr('checked','checked');
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
			var InviteManager=GroupCreate.InviteManager;
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
				var InviteManager=GroupCreate.InviteManager;
		  	if(InviteManager.getNajax!=null)return;
		  	//$("#memberloading").show();
				InviteManager.getNajax=$.post(InviteManager.get_N_url,{'circleId':$("#circleId").val(),'page':InviteManager.currpage-1},function(d){
        //$("#memberloading").hide();
					if(d.json.length >0)
					{
						InviteManager.loadMember(d.json,InviteManager.currpage);
					}
					else{
					}
					InviteManager.getNajax=null;
					InviteManager.showbnt();
				});
		},
		loadMember:function(json,page)
		{
			var InviteManager=GroupCreate.InviteManager;
		  if (json == null) return;
		  var pn =$(InviteManager.container).find('ul');
		  pn.find("li").hide();
		  var html=[];
		  for(var i=0; i< json.length; i++)
		  {
		    var s='<li page="'+page+'" class="list" style="text-align: center;padding-top:5px;margin-left:13px;float:left;list-style:none outside none;"><div style="width:48px;height:48px;border:1px solid #CCC;"><div onclick="GroupCreate.InviteManager.check(this);" style="height:48px;"><img src="';
		    if (json[i].photo_path==null || json[i].photo_path=='')
		    {
		      s+=GroupCreate.user_default_logo;
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
		var InviteManager=GroupCreate.InviteManager;
		var evobj=new WordMind(e,url,InviteManager.addto);
		evobj.init();
	},
	CheckgroupOrNetWork: function(values)
  {
     if (values=="")
     {
         $("#gname").siblings('span.errspan').text('请输入群组名称').show();
          $("#gname").siblings('img.ok').hide();
         $('#gname').siblings('img.loading').hide();
         //$('#gname').focus();
         return false;
     }
     else
     {
     		 if($("#gname").attr('check')=='1')return true;
     		 $("#gname").siblings('span.errspan').hide();
     		 $("#gname").siblings('img.ok').hide();
         $('#gname').siblings('img.loading').show();
         var circleId = $("#circleId").val();
				  $.post($('#gname').attr("checkurl"),{'gname':values,'circleId':circleId},function(data) 
				  {
				    if (data!='' && data!=null)
				    {
				      $("#gname").attr('check','0');
           	  $("#gname").siblings('span.errspan').show().text("群组名称已存在");
		     		  $("#gname").siblings('img.ok').hide();
		          $('#gname').siblings('img.loading').hide();
				    }
				    else
				    {
				      $("#gname").attr('check','1');
              $("#gname").siblings('span.errspan').hide();
		     		  $("#gname").siblings('img.ok').show();
		          $('#gname').siblings('img.loading').hide();
				    }
				  });
         return false;
     }
  },
	createSubmit:function(e){
		var InviteManager=GroupCreate.InviteManager;
		if(!GroupCreate.CheckgroupOrNetWork($("#gname").val()))return;
		var $vas=$("#group_invite_container a[login_account][check='1']");
    	var s='';
    	for(var i=0;i<$vas.length;i++){
    		if(i==$vas.length-1){
    			s+=$($vas[i]).attr('login_account');
    		}
    		else
    			s+=$($vas[i]).attr('login_account')+";";
    	}
		var h=$(".group_invite_set textarea").val().split(/[;|,| ，|；|\n|\t]/g);
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
    $("#invs").val(s);
    $("#"+GroupCreate.formid).attr('action',GroupCreate.saveUrl);
    $(e).find('span').text("提交中...");
		$("#"+GroupCreate.formid)[0].submit();
	},
	close:function(){
		$("#div_create_group").hide();
		$(document.body).children("div.coverdiv").remove();
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