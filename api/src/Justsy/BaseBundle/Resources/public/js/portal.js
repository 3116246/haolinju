var portal =
{
    get_url:"",
    isuploading:false,
    update_icon_url:"",
    save_icon_url:"",
    enterprise_eno:"",
    image_type:1,
    loadinfo:function(info)
    {
        if ( info==null ) return;
        var temp = info.portalname;
        if (info.logo!=null && info.logo!="")
        {
            $("#portal_icon").attr("src",webserver_url+info.logo);
            $(".portal_icon").html("<img src='"+webserver_url+info.logo+"' /><span>"+temp+"</span>");
        }
        else
        {
            $("#portal_icon").attr("src","/bundles/fafatimewebase/images/home/logo_we.png");
        }
        temp = temp==null ? "":temp;
        $("#portal_name").text(temp);
        $("#text_portalname").val(temp);
        
        temp = info.login_image;
        if ( temp==null || temp=="")
          temp = "/bundles/fafatimewebase/images/home/login_logo.png";
        else
          temp = webserver_url+temp;
        $("#login_image").attr("src",temp);
        
        temp = info.provision;
        temp = temp==null ? "":temp;
        $("#text_provision").val(temp);
        
        temp = info.start_image;
        if ( temp==null || temp=="")
          temp = "/bundles/fafatimewebase/images/home/startup.png";
        else
          temp = webserver_url+temp;
        $("#start_image").attr("src",temp);
        
        temp = info.guide0;
        if ( temp==null || temp=="")
          temp = "/bundles/fafatimewebase/images/home/h1.png";
        else
          temp = webserver_url+temp;
        $("#guide_0").attr("src",temp);
        temp = info.guide1;
        if ( temp==null || temp=="")
          temp = "/bundles/fafatimewebase/images/home/h2.png";
        else
          temp = webserver_url+temp;          
        $("#guide_1").attr("src",temp);
        temp = info.guide2;
        if ( temp==null || temp=="")
          temp = "/bundles/fafatimewebase/images/home/h3.png";
        else
          temp = webserver_url+temp;          
        $("#guide_2").attr("src",temp);
    },
    //修改名称
    editprovision:function(type)
    {
        if ( type < 3)
        {
            var params = {};
            if ( type==1)
            {
                var portalname = $.trim($("#text_portalname").val());
                if ( portalname=="" ) return;
                params = { "name":portalname};
            }
            else if ( type==2)
            {
                var provision = $.trim($("#text_provision").val());
                if ( provision=="" ) return;
                params = { "provision":provision};
            }
            var parameter = { "module":"portal","action":"updatePortal","params":params };
            $.post(access_url,parameter,function(data){
                if ( data.success)
                {
                    if ( type==1)
                    {
                        $("#portal_name,#update_portalname").toggle();
                        $("#portal_name").text(portalname);
                    }
                    else if ( type==2)
                    {
                        $(".provision_area").append("<span>修改服务条款成功！</span>");
                        setTimeout(function(){ $(".provision_area>span").remove() },2000);
                    }
                }
            });
       }
       else if ( type==3)
       {
          $("#portal_name").show();
          $("#update_portalname").hide();
       }
    },
    image_select_dialog:function(type)
    {
        var caption = "";
        if ( type==1)
           caption="<span class='select_img_title'>门户Logo</span>设置";
        else if ( type==2)
           caption="<span class='select_img_title'>登录界面Logo<span>设置";
        else if ( type==3)
           caption="<span class='select_img_title'>引导图</span>设置";
        $("#select_image .title").html(caption);
        portal.image_type=type;
        $("#select_image").show();
    },
    uploadportalicon:function(e){
			if(this.isuploading==true)return;
			//检查文件类型
			var filename=$(e).val();
			var filedrex=filename.split(".")[filename.split(".").length-1];
			var regex="png,gif,jpg,jpeg,bmp";
			if(regex.indexOf(filedrex.toLocaleLowerCase())<0){
				this.upload_hint("请选择图片格式的文件");
				return;
			}
			this.isuploading=true;
			this.upload_hint("正在上传中...");
			$("#logouploadsubmit").ajaxSubmit({
				url:portal.update_icon_url+"?filename=uploadlogofile",
				type:'post',
				dataType:'json',
				success:function(data){		
					portal.isuploading=false;		
					if(data.success)
					{
						portal.upload_hint("logo上传成功");
						$("#logocontener").html("<img id='logo' style='width:200px;height:200px;' src='"+webserver_url+data.fileid+"'/>");
						$("#mobilehomelogo").val(data.fileid);
						initJcrop();
					}
					else{
						portal.upload_hint(data.msg);
					}
				}
			});
		},
    saveportalicon:function()
    {
        //检查文件类型
        var filename=$("#uploadlogofile").val();
        if ( filename=="")
        {
            this.upload_hint("请选择上传的图片文件");
            return;
        }
        var filedrex=filename.split(".")[filename.split(".").length-1];
        var regex="png,gif,jpg,jpeg,bmp";
        if(regex.indexOf(filedrex.toLocaleLowerCase())<0){
            this.upload_hint("请选择图片格式的文件");
            return;
        }
        var params={
            fileid:$("#mobilehomelogo").val(),
            crop:'{"x":"'+$.trim($("#x").val())+'","y":"'+$.trim($("#y").val())+'","w":"'+$.trim($("#w").val())+'","h":"'+$.trim($("#h").val())+'"}',
            appid:this.enterprise_eno,
            type:portal.image_type
        };
        portal.upload_hint("正在保存中...");
        $.post(portal.save_icon_url,params,function(data){
            if(data.success){
            portal.upload_hint("图标保存成功,2秒后自动关闭");
            window.setTimeout(function(){
                $("#dialog_portalicon").hide();
                $("#portal_icon").attr("src",webserver_url+data.fileid);						
            },2000);
            }
            else{
                portal.upload_hint("图标保存失败！");
            }
        });
    },
		upload_hint:function(message)
		{
		    $(".up_icon_msg").html(message);
		    setTimeout(function(){
		        $(".up_icon_msg").html("");
		    },2000);		    
		},
		simulate:function(evn,type)
		{
		    var imgsrc = $(evn).attr("src");
		    var html = Array();
		    html.push("<img src='"+imgsrc+"' />");
		    if ( type==2)
		    {
		        var i = $(evn).attr("index");
		        html.push("<ol class='carousel-indicators' id='mobile_swith' style='position:relative;bottom:35px;'>");
		        html.push("<li style='margin-right:8px;' onclick='portal.mobile_back_select(this);'" + (i==0 ? " class='active'" : "") + "></li>");
		        html.push("<li style='margin-right:8px;' onclick='portal.mobile_back_select(this);'" + (i==1 ? " class='active'" : "") + "></li>");
		        html.push("<li onclick='portal.mobile_back_select(this);'" + (i==2 ? " class='active'" : "") + "></li>");
            html.push("</ol>");
		    }
		    $(".mobile_backgroudimg").html(html.join(""));
		},
		mobile_back_select:function(evn)
		{
		    $("#mobile_swith>li").removeClass("active");
        $(evn).addClass("active");
        var index = $(evn).index();
        $(".mobile_backgroudimg>img").attr("src",$("#guide_"+index).attr("src"));
		},
		image_switch:function(isstop)
		{
		    if ( isstop)
		    {
		        		        
		    }
		    else
		    {
		        
		    }
		}
};