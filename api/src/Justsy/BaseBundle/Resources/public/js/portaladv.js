var RESTYPE={
	iosdesc:"iosdesc",
	androiddesc:"androiddesc",
	iosversion:"iosversion",
	androidversion:"androidversion",
	iosdeploying:"iosdeploying",				// IOS是否正在编译发布中 0/1
	androiddeploying:"androiddeploying",		// Android是否正在编译发布中 0/1
	androidapp:"androidapp",					// Android APK mongoid
	iosapp:"iosapp",
	appname:"appname",
	icon29:"icon29",//应用图标
	icon58:"icon58",//
	icon60:"icon60",//
	icon120:"icon120",
	start_iphone4:"start_iphone4",//启动图片
	start_iphone5:"start_iphone5",
	start_android:"start_android",
	guide640_960:"guide640_960",//引导图
	guide640_1138:"guide640_1138",//
	guide720_1280:"guide720_1280",
	logo:"logo",
	identify:"identify"//版权标识
}
var DEVICE={
	IOS:"IOS",
	Android:"Android"
}
//order 正整数 标识启动图，引导图循序

var PortaladvObj={
	appid:"PORTAL",
	get_res_url:'',
	set_res_url:'',
	del_res_url:'',
	publish_url:'',
	check_publish_url:"",
	publish_down_url:"",
	get_enoinfo_url:'',
	web_file_path:'',
	curr_res:[],
	isEdit:false,
	icon:false,
	setResAdv:function(callback){
		ajaxLoading("正在保存，请稍候……");
		$(".currAjax").css({"margin-top":"100px","left":"70%"});
		$.post(PortaladvObj.set_res_url,{"res":PortaladvObj.curr_res},function(data){
			if(callback){
				callback(data);
			}
		});
	},
	delResAdv:function(callback){
		$.post(PortaladvObj.del_res_url,{"appid":PortaladvObj.appid},function(data){
			if(callback){
				callback(data);
			}
		});
	},
	getResAdv:function(callback){
		$.post(PortaladvObj.get_res_url,{"appid":PortaladvObj.appid},function(data){
			if(callback){
				callback(data);
			}
		});
	},
	publish_OnClick : function(sender){		
		if($(sender).attr("loading")=='1')return;
		$(sender).text("发布中...");
		$(sender).attr("loading","1");
		PortaladvObj.isEdit=true;

		$(".after_publish").popover("destroy");
		$(".adv_card").find(".divdeploying").show().siblings().hide();
		PortaladvObj.publish($(sender).attr("device"),function(data){
			$(".after_publish[loading='1']").attr("loading","0").text("编译发布");
			PortaladvObj.check_publish();
		});

	},
	publish:function(device){
		var callafter=arguments[1]?arguments[1]:function(d){};
		$.post(PortaladvObj.publish_url,{"device":device},function(data){
			callafter(data);
		});		
	},
	
	// 检查发布是否完成
	check_publish : function(){
		$.getJSON(PortaladvObj.check_publish_url, {t: new Date().getTime()}, function(data, textStatus) {
			if (data.IsComplete == "1"){ 
				$(".divdeploying").hide().siblings().show();

				var tpl = '<div class="alert"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>提醒！</strong>高级定制应用已发布完成，请前往 <a href="'+PortaladvObj.publish_down_url+'">应用下载</a> ！</div>';
				$("#main_area").prepend(tpl);
			}
			else {
				setTimeout(function(){PortaladvObj.check_publish();}, 1000 * 30);
			}
		});
	},
  
  selectappicon:function(resource){
  	if ( resource != null){
  		 var img_src = "";
  		 if ( resource.resourcetype=="system")
  		    img_src = file_webserver_url.replace("/getfile/","")+"/bundles/fafatimewebase/images/icons2/"+resource.resourceid;
  		 else
  		 	  img_src = file_webserver_url + resource.resourceid;
  		 
  		$(".appicons img").attr("src",img_src);
  	}
  },
  //adv_res
	saveappresource:function(){
		var callafter=arguments[0]?arguments[0]:function(d){};
		var control = null;
		if(PortaladvObj.curr_res!=null){
			for(var i=0;i<PortaladvObj.curr_res.length;i++){
				if(PortaladvObj.curr_res[i].restype!=RESTYPE.identify && PortaladvObj.curr_res[i].restype!=RESTYPE.appname && PortaladvObj.curr_res[i].restype.indexOf("version")<0 && PortaladvObj.curr_res[i].restype.indexOf("desc")<0){
					var resource = getappresource();
					var res_fileid = "";
					if ( resource != null){
						res_fileid = resource.resourceid;
					}					
					if(res_fileid==PortaladvObj.curr_res[i].resvalue) return;
					PortaladvObj.curr_res[i].resvalue=res_fileid;
				}
				else{
					PortaladvObj.curr_res[i].resvalue=$(".adv_res[restype='"+PortaladvObj.curr_res[i].restype+"']").val();
				}
				PortaladvObj.curr_res[i].appid=PortaladvObj.appid;
			}
			PortaladvObj.setResAdv(function(data){
				  ajaxLoadEnd();
					if(data.s=="1"){
						PortaladvObj.fillData(data.arr);
						showSuccessBox("保存成功！");
					}
					else{
						showErrBox(data.msg);
					}
					PortaladvObj.curr_res=[];
					PortaladvObj.isEdit=false;
					callafter(data);
				});
		}
	},
	loadCard:function(tag){
		$(".adv_card").children("div").hide();
		$(".adv_card").children("div[tag='"+tag+"']").show();
	},
	loadGuideCard:function(tag){
		$(".guidecard").hide();
		$currCard=$(".guidecard[tag='"+tag+"']");
		$currCard.show();
	},
	registerEvent:function(){
		$(".adv_ul").find("li").bind('click',function(){
			var $this=$(this);
			if($this.attr("check")=="1")return;
			$(".after_publish").popover("hide");
			$old=$(".adv_ul li[check='1']");
			$this.siblings().attr("check","0");
			$old.children("div:first").attr("class","i_tag"+$old.attr("tag"));
			$this.siblings().removeClass("advi_selected");
			$this.addClass("advi_selected");
			$this.children("div:first").attr("class","i_tag"+$this.attr("tag")+"_active");
			$this.attr("check","1");
			var tag=$this.attr("tag");
			PortaladvObj.loadCard(tag);
		});
		$(".lvs_div").bind("click",function(){
			var $this=$(this);
			if($this.attr("check")=="1")return;
			$this.parent().siblings("li").find(".lvs_div").attr("check","0");
			$this.parent().siblings("li").find(".lvs_div").removeClass("lvs_selected");
			$this.addClass("lvs_selected");
			$this.attr("check","1");
			var tag=$this.parent().attr("tag");
			PortaladvObj.loadGuideCard(tag);
		});
		$("img.adv_res").bind("click",function(){
			PortaladvObj.curr_res.push({
				resid:$(this).attr("resid"),
				restype:$(this).attr("restype"),
				resvalue:$(this).attr("resvalue"),
				order:$(this).attr("order")
			});
			PortaladvObj.isEdit=true;
			var appicon = $(this).attr("appicon");
			if ( appicon != null && appicon=="1")
			  PortaladvObj.icon=true;
			else
				PortaladvObj.icon=false;
		});
		$(".save_identify").unbind().bind("click",function(){
			if($(this).attr("loading")=='1')return;
			$(this).text("保存中...");
			$res=$(".adv_res[restype='identify']");
			PortaladvObj.curr_res.push({
				resid:$res.attr("resid"),
				restype:$res.attr("restype"),
				resvalue:$res.val(),
				order:$res.attr("order")
			});
			PortaladvObj.isEdit=true;
			PortaladvObj.saveappresource(function(data){
				$(".save_identify").attr("loading","0");
				$(".save_identify").text("保存");
			});
			return false;
		});
		$(".save_appname").unbind().bind("click",function(){
			if($(this).attr("loading")=='1')return;
			$(this).text("保存中...");
			$(this).attr("loading","1");
			$res=$(".adv_res[restype='appname']");
			PortaladvObj.curr_res.push({
				resid:$res.attr("resid"),
				restype:$res.attr("restype"),
				resvalue:$res.val(),
				order:$res.attr("order")
			});
			PortaladvObj.isEdit=true;
			PortaladvObj.saveappresource(function(data){
				$(".save_appname").attr("loading","0");
				$(".save_appname").text("保存");
			});
			return false;
		});
		$(".android_tag").bind("click",function(){
			$(this).addClass("tag_device_active");
			$(".ios_tag").removeClass("tag_device_active");
			$(".devicepublish").hide();
			$(".devicepublish[tag='1']").show();
		});
		$(".ios_tag").bind("click",function(){
			$(this).addClass("tag_device_active");
			$(".android_tag").removeClass("tag_device_active");
			$(".devicepublish").hide();
			$(".devicepublish[tag='2']").show();
		});
		$(".before_save").bind("click",function(){
			if($(this).attr("tag")=="1"){
				$res1=$(".adv_res[restype='androidversion']");
				$res2=$(".adv_res[restype='androiddesc']");
				PortaladvObj.curr_res.push({
					resid:$res1.attr("resid"),
					restype:$res1.attr("restype"),
					resvalue:$res1.val(),
					order:$res1.attr("order")
				});
				PortaladvObj.curr_res.push({
					resid:$res2.attr("resid"),
					restype:$res2.attr("restype"),
					resvalue:$res2.val(),
					order:$res2.attr("order")
				});
			}
			else if($(this).attr("tag")=="2"){
				$res1=$(".adv_res[restype='iosversion']");
				$res2=$(".adv_res[restype='iosdesc']");
				PortaladvObj.curr_res.push({
					resid:$res1.attr("resid"),
					restype:$res1.attr("restype"),
					resvalue:$res1.val(),
					order:$res1.attr("order")
				});
				PortaladvObj.curr_res.push({
					resid:$res2.attr("resid"),
					restype:$res2.attr("restype"),
					resvalue:$res2.val(),
					order:$res2.attr("order")
				});
			}
			if($(this).attr("loading")=='1')return;
			$(this).text("保存中...");
			$(this).attr("loading","1");
			PortaladvObj.isEdit=true;
			PortaladvObj.saveappresource(function(data){
				$(".before_save[loading='1']").attr("loading","0").text("保存");
			});
			return false;
		});
		//$(".after_publish").popover({"trigger":"hover",title:"提示", placement: "top", html: true, content: "<h5 style='font-size: 14px; font-weight: bold;'>开始编译并发布，请确认！</h5> <input type='button' class='btn btn-primary' value='编译并发布' onclick='PortaladvObj.publish_OnClick(this)'></input>"});
		
	},
	init:function(params){
		PortaladvObj.set_res_url=params.set_res_url;
		PortaladvObj.del_res_url=params.del_res_url;
		PortaladvObj.get_res_url=params.get_res_url;
		PortaladvObj.publish_url=params.publish_url;
		PortaladvObj.check_publish_url=params.check_publish_url;
		PortaladvObj.publish_down_url=params.publish_down_url;		
		PortaladvObj.web_file_path=params.web_file_path;
		PortaladvObj.registerEvent();
		PortaladvObj.fillData(params.portalData);
		PortaladvObj.loadGuideCard("1");
	},
	fillData:function(data){
		for(var i=0;i<data.length;i++)
		{
			var resid=data[i].id;
			var restype=data[i].restype;
			var resvalue=data[i].resvalue;
			var order=data[i].order==null?"":data[i].order;
			
			$ele=$(".adv_card").find(".adv_res[restype='"+restype+"'][order='"+order+"']");
			if($ele.length>0){
				$ele.attr({
						"resid":resid,
						"restype":restype,
						"resvalue":resvalue,
						"order":order
					});
				if($ele[0].nodeName=="IMG"){
					$ele.attr("src",PortaladvObj.web_file_path+resvalue);
				}
				else if($ele[0].nodeName=="INPUT" && resvalue!=""){
					$ele.val(resvalue);
				}
				else if($ele[0].nodeName=="TEXTAREA"){
					$ele.val(resvalue);
				}
			}
			if(restype=="icon60"){
				$("#app_icon").children("img:first").attr("src",PortaladvObj.web_file_path+resvalue);
			}
			else if(restype=="appname"){
				$("#app_name").children("span").text(resvalue);
			}
			else if (restype == RESTYPE.androiddeploying)
			{
				if (resvalue == "1") {
					$(".adv_card").find(".divdeploying").show().siblings().hide();
					PortaladvObj.check_publish();
				}
				else $(".adv_card").find(".divdeploying").hide().siblings().show();
			}
			else if (restype == RESTYPE.iosdeploying)
			{
				if (resvalue == "1") {
					$(".adv_card").find(".divdeploying").show().siblings().hide();
					PortaladvObj.check_publish();
				}
				else $(".adv_card").find(".divdeploying").hide().siblings().show();
			}
		}
	}
}

$(".after_publish:visible").live("mouseover",function(){
	 var role = $(this).attr("role");
	 if ( role !=null && role.toLowerCase()=="s")
	   $(this).find(".popover").show();
});

$(".after_publish:visible").live("mouseout",function(){
	 $(this).find(".popover").hide();
});