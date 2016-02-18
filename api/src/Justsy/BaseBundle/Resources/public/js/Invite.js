//判断邮件是否正确
function validEmails(mail)
{
	    var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
	    return reg.test(mail);
}

var invite={
	  _paras:null,
    //是否加载完成
    isStaffLoad:false,       //同事加载标识
    isFriendLoad:false,      //好友加载标识
    isMemberLoad:false,      //圈子成员加载标识
    InviteData:new Array(),  //缓存同事、好友
    InviteMember:new HashMap(), //缓存每个圈子成员
    img_friend_selected:"/bundles/fafatimewebase/images/invest_friend_selected.png",
    img_friend:"/bundles/fafatimewebase/images/invest_friend.png",
    img_email_selected:"/bundles/fafatimewebase/images/invest_email_selected.png",
    img_email:"/bundles/fafatimewebase/images/invest_email.png",
    img_receive_selected:"/bundles/fafatimewebase/images/invet_receive_selected.png",
    img_receive:"/bundles/fafatimewebase/images/invest_receive.png",
    CurrenCircleID:"",	       //当前圈子
    menu:{
    	  showStaff:function(){
	            $("#invite_li_staff>img").attr("src",invite.img_friend_selected);
	            $("#invite_li_email>img").attr("src",invite.img_email);
	            $("#invite_li_inviteme>img").attr("src",invite.img_receive);	  
	            $("#sendHint").hide();
				    	$(".invite_content_left_menu_active").removeAttr("class");
				    	$("#invite_li_staff").attr("class", "invite_content_left_menu_active");    	  	
    	      	$("#invite_right_staff").show();
	            $("#invite_right_email").hide();
	            $(".invite_right_bottom").show();
	            $(".invite_content_right_head").show();
	            $("#invite_title_text").show();
	            $("#receiveinvite").hide();          
    	  },
    	  showEmail:function(){
	            $("#invite_li_staff>img").attr("src",invite.img_friend);
	            $("#invite_li_email>img").attr("src",invite.img_email_selected);
	            $("#invite_li_inviteme>img").attr("src",invite.img_receive);	                	  	
				    	$(".invite_content_left_menu_active").removeAttr("class");
				    	$("#invite_li_email").attr("class", "invite_content_left_menu_active");    	  	
	            $("#invite_loading").hide();
	            $("#sendHint").hide();
	            $("#invite_right_staff").hide();
	            $("#invite_right_email").show();
	            $(".invite_right_bottom").show();
	            $(".invite_content_right_head").show();
	            $("#invite_title_text").hide();
	            $("#receiveinvite").hide();
    	  },
    	  showEcircle:function(){   //显示企业圈子菜单 
    	     	this.showEmail();
    	     	$("#invite_li_staff").hide();    	     	
    	  },
    	  showReveice:function(){
    	  	    $("#invite_li_staff>img").attr("src",invite.img_friend);
	            $("#invite_li_email>img").attr("src",invite.img_email);
	            $("#invite_li_inviteme>img").attr("src",invite.img_receive_selected);	 
	            $("#sendHint").hide();
              $(".invite_content_left_menu_active").removeAttr("class");
				    	$("#invite_li_inviteme").attr("class", "invite_content_left_menu_active");
	            $("#invite_loading").show();
	            $(".invite_content_right_head").hide();
	            $("#invite_right_staff").hide();
	            $("#invite_right_email").hide();
	            $(".invite_right_bottom").hide();
	            $("#invite_title_text").hide();
	            $("#receiveinvite").hide();
	            invite.getInvite();
    	  },
    	  init:function(){
				    //菜单项事件
				    $("#ul_invite>li").live("click", function () {
				        if (this.id == "invite_li_staff") {
				            invite.menu.showStaff();
				            invite.getCircleMemeber(invite.CurrenCircleID);
				        }
				        else if (this.id == "invite_li_email") {
				           invite.menu.showEmail();
				        }
				        else if (this.id == "invite_li_inviteme") {
				           invite.menu.showReveice();
				           
				        }
				    });
    	  }
    },
    _circleChange:function()
    {
    	  var curControl = $("#mycircle").find("#" + invite.CurrenCircleID);
    	  $(".invite_title").attr("circleid", invite.CurrenCircleID);
	      $(".invite_title").text(curControl.find("a").text());
	      $(".cur_circle_name").html(curControl.text());
	      $(".invite_box_ico").attr("src", curControl.find("img").attr("src"));
    	  $("#subject").val(g_nick_name+"邀请您加入"+$(".invite_title").text());
    	  //$("#email_title_lable").text(invite.CurrenCircleID==invite._paras.circleid?"请输入同事的邮箱地址：":"请输入同事、好友的邮箱地址：");
	        if (invite.CurrenCircleID == invite._paras.circleid) {
	            invite.menu.showEcircle();
	            return;
	        }
	        else {
	            invite.menu.showStaff();
	        } 
	        invite.getCircleMemeber(invite.CurrenCircleID);   	  
    },
	  load:function(parameters){
	  	this._paras=parameters;
	  	$(".invite_title").attr("circleid",g_curr_circle_id);
	  	invite.CurrenCircleID=g_curr_circle_id;		  
	    $(".invite_member_small_box").live("click", function () {
	        if (this.id == "") return;
	        var html = "<span id='" + this.id + "' style='display:block;' jid='" + $(this).attr("jid") + "' title='" + $(this).attr("title") + "' class='invite_member_box'> " +
	                "  <img onerror=\"javascript:this.src='/bundles/fafatimewebase/images/tx.jpg'\" src='" + $(this).find("img").attr("src") + "' class='invite_member_image_box' />" +
	                "  <span class='invite_member_name'>" + $(this).find("span").text() + "</span></span>";
	        if ($("#invite_member").children().length==0)
	          $("#invite_member").append(html);
	        else
	        	$(html).insertBefore($("#invite_member>span:visible:first"));
	        if ($("#invite_member>span:visible").length > 12)
	            $("#invite_member>span:visible:last").hide();
	        $(this).remove();
	        html = "<span class='invite_member_small_box' jid=''><span class='invite_element' style='display:block;width:38px;height:38px;border:1px solid #afafaf;'></span></span>";
	        $("#invite_selected_member").append(html);
	    });
	    
	    //关闭按钮
	    $(".invite_title_close").live("click",function(){
	    	 
	    	 $(".body_filter").hide();
	    	 $('#invite_box').hide();
	    });
	
	    //选择上面人员
	    $(".invite_member_box").live("click", function () {
	        var image_url = $(this).find(".invite_member_image_box").attr("src");
	        var nick = $(this).attr("title");
	        var html = "<img src='" + image_url + "' /><span style='height: 20px;line-height: 20px;'>" + nick + "</span>";
	        var selectCtl = $("#invite_selected_member").children();
	        var count = 0;
	        var isexist=false;
	        for (var i = 0; i < 10; i++) {
	        		if(selectCtl[i].getAttribute('jid')==$(this).attr("jid")){
	        			isexist=true;
	        			setTimeout(function(){
   	      			//$("#emaillist_hint").html("您已经选择了该用户。");	
   	      			},2000);
	        			break;
	        		}
	            if (selectCtl[i].getAttribute("jid") == "") {
	                $(selectCtl[i]).html(html);
	                $(selectCtl[i]).attr("id", this.id);
	                $(selectCtl[i]).attr("jid", $(this).attr("jid"));
	                $(selectCtl[i]).attr("title", $(this).attr("title"));
	                count = i + 1;
	                break;
	            }
	            else if (i == 9) {
	                //每次最多允许邀请10个用户，请下次再邀请。
	                return;
	            }
	        }
	        //显示下一个元素（如果存在的话）
	        var controls = $("#" + this.id + "~span:not(:visible):first")
	        if (controls.length == 1)
	            controls.show();
					if(!isexist)
	        	$(this).remove();
	        //动态显示页数
	        invite.page.setPage(1, $("#invite_member>span").length);
	    }); 
      //添加10个空选择框
      $("#invite_selected_member").html("");
      for (var j = 0; j < 10; j++) {
            html = "<span class='invite_member_small_box' jid=''><span class='invite_element'></span></span>";
            $("#invite_selected_member").append(html);
      }
      this.menu.init();
      $(".invite_button").unbind("click").bind("click",function(){invite.sendInvite(this)});
      var $invite_circle_list = $("#invite_circle_list");
      
      $("#mycircle div").each(function(){
      	  var $this = $(this);
      	  var ctrlId = $this.attr("id");
      	  if(ctrlId != "10000")
      	  {
      	  	 var imgsrc = $this.find("img").attr("src");      	  	 
      	     $invite_circle_list.append("<li title='切换到该圈子' circleid='"+$this.attr("id")+"'><img src='"+imgsrc+"' /><span style='float:left;margin-left:8px;width:90px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;'>"+ $this.find("a").text()+"</span></li>");
      	  }
      });
      $invite_circle_list.find("li").live("click",function(){
      	    $("#invite_circle_list>li").removeAttr("style");
      	    var $this = $(this);
      	    $this.css("background-color","#00ABD9");
      	    $this.css("color","white");
          	invite.CurrenCircleID = $this.attr("circleid");
          	invite._circleChange();
          	$("#invite_circle_list").hide();
          	$('#ul_invite').show();
      });
	  },
	  onPaste:function(event){
   	  setTimeout(function(){
   	      	var t = $("#emaillist").val().split(/[;|,| ，|；|\n|\t]/g),newAry=[],newMap=new HashMap();
   	      	for(var i=0;i<t.length; i++)
   	      	{
   	      	    if($.trim(t[i])=="" || t[i].indexOf("@")==-1 || newMap.get(t[i])!=null) continue;
   	      	    newAry.push(t[i]);
   	      	    newMap.put(t[i],"");
   	      	    if(newAry.length>9) return;
   	      	}   	      	
   	      	if(newAry.length==0)
   	      	{
   	      	    $("#emaillist_hint").html("<font style='color:red'>您粘贴的内容中没有解析出符合格式的邮箱地址！</font>");	
   	      	}
   	      	setTimeout(function(){
   	      		$("#emaillist_hint").html("支持右键粘贴或Ctrl+V粘贴多个邮箱地址。");	
   	      	},3000);
   	      	if(newAry.length>0)
   	      	    $("#emaillist").val(newAry.join(";")+";");
   	      	newAry=null;
   	      	newMap=null;
   	  },100);
	  },
	  sendInvite:function(obj){
	  	    var $saveBtn = $(".invite_button"),notPassAry=[],jids = new Array(),invite_type = 1;
	    	  if($saveBtn.attr("commit")=="1") return;
	        if ($("#invite_right_staff").is(":visible")) {
	            var member_list = $("#invite_selected_member").children();
	            var jid = "";
	            for (var i = 0; i < member_list.length; i++) {
	                jid = $(member_list[i]).attr("jid");
	                if (jid != "") {
	                    jids.push(jid);
	                }
	                else {
	                    break;
	                }
	            }
	        }
	        else {
	        	var t = $("#emaillist").val().split(/[;|,| ，|；|\n|\t]/g),newMap=new HashMap();
	        	for(var i=0;i<t.length;i++)
	        	{
	        		 if($.trim(t[i])=="" || t[i].indexOf("@")==-1 ||newMap.get(t[i])!=null) continue;
	        		 if(validEmails(t[i])){ jids.push(t[i]);newMap.put(t[i],"")}
	        		 else notPassAry.push(t[i]);
	        		 if(jids.length>9) break;
	        	}
	        	newMap=null;
	          invite_type = 0;
	        }
	        if ( jids.length==0)
	        {
	        	 $("#send_error").show();        	 
	        	 $("#send_error").text(invite_type==1 ?"请选择邀请的同事、好友！":"请输入正确的邮箱地址！");
	        	 setTimeout(function(){$("#send_error").hide()},5000);
	        	 return;
	        }
	        else
	        {
	          $("#send_error").hide();
	        }
	        if(invite_type==0 && notPassAry.length>0)
	        {
	            	$("#emaillist_hint").html("<font style='color:red'>部分邮箱地址格式不正确，这部分邮箱将不会提交！</font>");	
	            	$("#emaillist").val(notPassAry.join(";"));
	            	setTimeout(function(){$("#emaillist_hint").html("支持右键粘贴或Ctrl+V粘贴多个邮箱地址。");},3000);
	        }
	        //提交数据
	        $saveBtn.attr("commit","1").html("发送中...");
	        //邀请加入企业时需要传递eno参数
	        var eno = invite.CurrenCircleID!=invite._paras.circleid ? "" : invite._paras.enoId;
	        var subject = invite_type==1?"": $("#subject").val();
	        var invMsg = invite_type==1?"": $("#content").val();
	        $.post(this._paras.inviteURL, { "acts": jids, "circleId": invite.CurrenCircleID,"eno":eno, "invMsg": invMsg,"subject":subject, "invRela": invite_type },
	        function (data) {
	        	$saveBtn.attr("commit","0").html("发送邀请");
	        	$("#send_error").show(); 
	          if (data == "1") {
	            $("#send_error").text("邀请已成功发送，您可以继续邀请！");
	            invite._resetSelectStaff();
	            invite._resetEmailList();
	          }
	          else {
	            $("#send_error").text("发送邀请失败！");
	          }
	          setTimeout(function(){$("#send_error").hide()},5000);
	        });	  	  
	  },
	  getInvite:function(){
	      //receiveinvite	
	      $("#receiveinvite").load(this._paras.recvedUrl,function(){
	      	 $("#invite_loading").hide();
	      	 $("#receiveinvite").show();
	      });
	  },
	  loadData:function(){
	  	  if(this._paras==null) return;
				//$("#invite_loading").show();
        //$("#invite_right_staff").hide();
        //$("#invite_right_email").hide();
        var circleid = $(".invite_title").attr("circleid");
        $(".cur_circle_name").html($(".invite_title").text());
        $("#subject").val(g_nick_name+"邀请您加入"+$(".invite_title").text());
        //获取好友
        if(invite.InviteMember.get("f")==null){
		        var _url = this._paras.url3;
		        $.post(_url, "openid=" + g_curr_openid, function (data) {
		                invite.InviteMember.put("f", data.list.friends);//好友
		                invite.isFriendLoad=true;
                    if(invite.isStaffLoad && invite.isMemberLoad && invite.isFriendLoad )
                    {
                       invite.outHtml();
                    }
		        });
        }        
        //获得企业圈子成员
        this.getCircleMemeber(invite._paras.circleid);    
        if(circleid!=invite._paras.circleid)  
        {
        	 invite.menu.showStaff();
        	 this.getCircleMemeber(circleid);
        }
        else
        {
        	 invite.menu.showEmail();
        }
	  },
	  getCircleMemeber:function(circleid){
	  	 $("#invite_right_email").hide();
	  	 $("#invite_member").html("");
       invite._resetSelectStaff();
	  	 var currenMember = this.InviteMember.get(circleid);
	  	 if(circleid==invite._paras.circleid) $("#invite_li_staff").hide(); else{ $("#invite_li_staff").show();invite.isMemberLoad=false;}
	  	 if(currenMember==null)
	  	 {
	  	 	    $("#invite_loading").show();
	  	 	    $(".invite_right_bottom").hide();
	  	 	    $("#invite_right_staff").hide();
            $.post(this._paras.url1, "circleid=" + circleid, function (data) {
                if (data != null) {
                	  $("#invite_loading").hide();
                    invite.InviteMember.put(circleid, data);                    
                    if(circleid==invite._paras.circleid)
                    {
                    	  invite.InviteMember.put("s",data);//同事
                    	  invite.isStaffLoad=true;
                    }
                    else invite.isMemberLoad=true;
                    if(invite.isStaffLoad && invite.isMemberLoad && invite.isFriendLoad )
                    {                    	 
                       invite.outHtml();
                    }
                }
            });
	  	 }
	  	 else if(circleid!=invite._paras.circleid) invite.outHtml();
	  },
	  outHtml:function(){
	  	  $(".invite_right_bottom").show();
	  	  $("#invite_right_staff").show();
	  	  $("#sendHint").hide();
	  	  var w=invite.InviteMember.get("f"),filterMs = invite.InviteMember.get(invite.CurrenCircleID);
	  	  if(w!=null)w=w.concat(invite.InviteMember.get("s"));
	  	  else w=invite.InviteMember.get("s");
	  	  var filterAccount = [];
	  	  if(filterMs!=null)
	  	  {
	  	     for(var i=0;i<filterMs.length; i++)
	  	        	filterAccount.push(filterMs[i].login_account);
	  	  }
	  	  if(w==null)
	  	  {
	  	  	//alert(invite.InviteMember.get("s"));
	  	  	//alert(invite.InviteMember.get("f"));
	  	  	}
	  	  filterAccount = filterAccount.join(",")+",";
	  	  var html=[],isshowlist=[];
        for (var i = 0; i < w.length; i++) {
            if (w[i]==null || w[i].login_account == invite._paras.owner || filterAccount.indexOf(w[i].login_account+",")>-1) continue;//过滤圈子成员
            var name = w[i].name||w[i].nick_name;
            if(isshowlist[w[i].login_account]!=null) continue; //已加载
            var imgsrc = w[i].photo_path;
            var img_err = invite._paras.defaultimg;            
            if (imgsrc == "")
                imgsrc = img_err;
            else {
                imgsrc = invite._paras.getfile + "/" + imgsrc;
            }
            isshowlist[w[i].login_account]="1";
            html.push( "<span class='invite_member_box' id='invite_" + i + "' title='" + name + "' jid='" + w[i].login_account + "'" + (html.length > 11 ? " style='display:none;'" : "") + ">" +
              "  <img class='invite_member_image_box' src='" + imgsrc + "' onerror=\"javascript:this.src='" + img_err + "'\" />" +
              "  <span class='invite_member_name'>" + name + "</span>" +
              "</span>");
        }
        isshowlist=null;
        $("#invite_member").html(html.join(""));
        invite.page.setPage(1, $("#invite_member>span").length);
        if(html.length==0){
        	  $("#invite_right_staff").hide();
            $(".invite_right_bottom").hide();
            $("#sendHint span").html("没找到更多可邀请的同事、好友。建议您试试【邮件邀请】").parent().show();	
        }
	  },
	  outSearchResult:function(w){
	  	  var html=[],isshowlist=[],filterMs = invite.InviteMember.get(invite.CurrenCircleID);
        var filterAccount = [];
	  	  if(filterMs!=null)
	  	  {
	  	     for(var i=0;i<filterMs.length; i++)
	  	        	filterAccount.push(filterMs[i].login_account);
	  	  }
	  	  filterAccount = filterAccount.join(",")+",";
        for (var i = 0; i < w.length; i++) {
            if (w[i]==null || w[i].login_account == invite._paras.owner || filterAccount.indexOf(w[i].login_account+",")>-1) continue;//过滤圈子成员
            var name = w[i].name||w[i].nick_name;
            if(isshowlist[w[i].login_account]!=null) continue; //已加载
            var imgsrc = w[i].photo_path;
            var img_err = invite._paras.defaultimg;            
            if (imgsrc == "")
                imgsrc = img_err;
            else {
                imgsrc = invite._paras.getfile + "/" + imgsrc;
            }
            isshowlist[w[i].login_account]="1";
            html.push( "<span class='invite_member_box' id='invite_" + i + "' title='" + name + "' jid='" + w[i].login_account + "'" + (html.length > 11 ? " style='display:none;'" : "") + ">" +
              "  <img class='invite_member_image_box' src='" + imgsrc + "' onerror=\"javascript:this.src='" + img_err + "'\" />" +
              "  <span class='invite_member_name'>" + name + "</span>" +
              "</span>");
        }
        isshowlist=null;
        $("#invite_member").html(html.join(""));
        invite.page.setPage(1, html.length);
        if(html.length==0)
        {
           //没搜索到数据
           $("#invite_right_staff").hide();
           $(".invite_right_bottom").hide();
           $("#sendHint span").html("没找到相关的同事、好友信息！<br>1、您可以更改查询条件重新搜索<br>2、从现有的<a href='javascript:invite.getCircleMemeber(invite.CurrenCircleID)'>同事、好友</a>中选择。").parent().show();
        }
        else
        {
		  	  $(".invite_right_bottom").show();
		  	  $("#invite_right_staff").show();
		  	  $("#sendHint").hide();        	
        }
	  },
	  search:function(){
	  	 var v = $("#invite_title_text").val();
	  	 if($.trim(v)==""){invite.outHtml(); return;}
	  	 $("#invite_loading").show();
	  	 $("#sendHint").hide();
	  	 $(".invite_right_bottom").hide();
	  	 $("#invite_right_staff").hide();
	  	 $.getJSON(this._paras.queryUrl,{"v":v},function(d){
          $("#invite_loading").hide();
	  	 	  if(d==null){invite.outHtml();	return;}          
          invite.outSearchResult(d);
	  	 });
	  },
	  page:{
		    //动态设置页码　
		    setPage:function(curindex, pagecount) {
		        pagecount = Math.ceil(pagecount / 12);
		        $("#invite_page").html("");
		        if(pagecount<=1) return;
		        var html = "";
		        $("#invite_page").html("");
		        for (var j = 0; j < pagecount; j++) {
		            html += "<span class='invite_page_word' state=1 id='page" + (j + 1) + "'>" + (j + 1) + "</span>";
		        }
		        $("#invite_page").html(html);
		        $("#page" + curindex).css({ "background-color": "#00AAD8", "color": "white" });
		
		        //翻页
		        $(".invite_page_word").live("click", function () {
		            if ($(this).attr("state") == 1) {
		                $("#invite_page>span").removeAttr("style");
		                $("#invite_page>span").attr("state", 1);
		                $(this).css({ "background-color": "#00AAD8", "color": "white" });
		                $(this).attr("state", 0);
		                invite.page.movePage($(this).text());
		            }
		        });
		    },		
		    //翻页
		    movePage:function(index) {
		        $("#invite_member>span:visible").hide();
		        var startindex = 12 * (index - 1);
		        var endindex = 12 * index;
		        $("#invite_member>span").slice(12 * (index - 1), 12 * index).show();
		    }
		    	  	
	  },
	  _resetSelectStaff:function(){
	  	 $("#invite_selected_member .invite_member_small_box").attr("jid","");
	  	 $("#invite_selected_member .invite_member_small_box").html("<span class='invite_element'></span>");
	  },
	  _resetEmailList:function()
	  {
	  	$("#emaillist").val("");
	  },
	  inviteAgree:function(id,_url){
	  	 var $tr = $("#invited_table_"+id), old_html = $tr.html();
	  	 $tr.find("td:last").remove();
	  	 $tr.find("td:last").attr("colspan","2").html("提交中...");
	    $.getJSON(_url,{"_urlSource":"wefafa_json"},function(d){
       if(d.succeed==1)
       {
           $tr.remove();
           $("#invite_hint").prepend(_InfoHint.replace("$info$", "你已成功加入 "+d.name+" 圈子,<a href='"+d.circleurl+"'>现在进入</a>"));
           window.setTimeout(function(){$("#infohint").remove()},3000);
           var num=parseInt($(".icon_invite_text").text());
           if(num>0){
         		$(".icon_invite_text").text(num-1);
         		$("#invite_li_inviteme").find(".tip_new_num").text(num-1);
         		if(num==1)
         			$(".icon_invite_text").text('').hide();
         			$("#invite_li_inviteme").find(".tip_new_num").hide();
         	 }
       }
       else
       {
           $("#invite_hint").prepend(_InfoHint.replace("$info$",d.msg));
           $tr.html(old_html); //提交失败时还原tr内容
           window.setTimeout(function(){$("#infohint").remove()},5000);
       }
       });
	  },
	  inviteReject:function(id,_url){
	  	 var $tr = $("#invited_table_"+id), old_html = $tr.html();
	  	 $tr.find("td:last").remove();
	  	 $tr.find("td:last").attr("colspan","2").html("提交中...");	  	
	  	 $.post(_url,{"_urlSource":"wefafa_json"}, function(data) 
       {
		      if (data=="1")
		      {
		      	$("#invite_hint").prepend(_InfoHint.replace("$info$","操作成功"));
		        $tr.remove();
		        window.setTimeout(function(){$("#infohint").remove()},3000);
		        var num=parseInt($(".icon_invite_text").text());
           if(num>0){
           	$(".icon_invite_text").text(num-1);
           	if(num==1)
           	 $(".icon_invite_text").text('').hide();  
         	 }
		      }
		      else
		      {
		      	 $("#invite_hint").prepend(_InfoHint.replace("$info$","操作失败"));
		         $tr.html(old_html);
		         window.setTimeout(function(){$("#infohint").remove()},5000);
		      }
       });
	  },
	  inviteuser:function(e,callback){
	  	$(e).siblings('.bnt_corr').remove();
	  	var path=addr_invite_url;
	  	var addr_mail=$(e).parent().parent().prev().find('.mail').val();
	  	var id=$(e).parent().parent().prev().find('.id').val();
	  	var acts=new Array(addr_mail);
	  	$.getJSON(path,{
	  		acts:acts,
	  		id:id,
	  		circleId:-1
	  		},
	  		function(json){
	  			loaded(json,"invite");
	  		}
	    );
	  	callback(e);
	  }
};