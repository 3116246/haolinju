var toolIndex = 1;
var srcs = [], FaChatMain_domain;
if (document.currentScript == null) {
    var scripts = document.getElementsByTagName("script");
    var reg = /fachat_window([.-]\d)*\.js(\W|$)/i
    for (var i = 0, n = scripts.length; i < n; i++) {
        var src = !!document.querySelector ? scripts[i].src
                          : scripts[i].getAttribute("src", 4);
        if (src && reg.test(src)) {
            srcs = src.split("/");
            break;
        }
    }
}
else {
    srcs = document.currentScript.src.split("/");
}
FaChatMain_domain = srcs[0] + "//" + srcs[2];
var organ_OnlineIco = FaChatMain_domain +"/bundles/fafawebimimchat/images/employee.png";
var organ_OfflineIco = FaChatMain_domain + "/bundles/fafawebimimchat/images/employeeoff.png";
var Sound = { Msg:FaChatMain_domain + "/bundles/fafawebimimchat/music/NewMsg.wav"};

var getEventCoord = function (e) {
  var evt = e || event, d = document,
	scrollEl = /^b/i.test(d.compatMode) ? d.body : d.documentElement,
	supportPage = typeof evt.pageX == 'number',
	supportLayer = typeof evt.layerX == 'number';
    return {
        pageX: supportPage ? evt.pageX : evt.clientX + scrollEl.scrollLeft,
        pageY: supportPage ? evt.pageY : evt.clientY + scrollEl.scrollTop,
        clientX: evt.clientX,
        clientY: evt.clientY,
        layerX: supportLayer ? evt.layerX : evt.offsetX,
        layerY: supportLayer ? evt.layerY : evt.offsetY
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

//加载页面需要的资源
LoadCSS.load(FaChatMain_domain + "/bundles/fafawebimimchat/css/FaFaChatRoster.css");
LoadCSS.load(FaChatMain_domain + "/bundles/fafawebimimchat/css/zTreeStyle.css");
LoadJs.load(FaChatMain_domain + "/bundles/fafawebimimchat/js/jquery.jplayer.min.js");
LoadJs.load(FaChatMain_domain + "/bundles/fafawebimimchat/js/jquery.ztree.core-3.4.min.js");
LoadJs.load(FaChatMain_domain + "/bundles/fafatimewebase/js/bootstrap.js");

var FaFaChatMain = {conn_count:10, h: 400, w: 200, minHeight: "30px", obj: null, totalRoster: 0, mainPanel: null,onlineCache:new HashMap(),owner: null, 
	                  password: null, isExpand: false,isNewMessageFlash: null,newMessageJidList: new HashMap(), msglisttimer: null, msglisttimer_hide: null,
	                  headerflashlist:new HashMap(),Isheaderflash:new HashMap(),resourceList:new HashMap(),rosterState:"online",GroupInfo:new HashMap(),
                    MemberList:new HashMap(),isOne:true,IsAfresh:0,famainOrgList:new Array(),fawindowOrgList:new Array(),fafa_title:null,GroupList:new Array()};

//格式化jid
FaFaChatMain.FormateJid = function (jid) {
  return jid.replace(/@|\./g, '_');
};

//好友列表状态
FaFaChatMain.ChangeRosterStatus = function (rosterjid,state,show,showdesc,resource) {
	 var jid = FaFaChatMain.FormateJid(rosterjid);
   if($("#"+jid).length >0) {   	 
     var resources = FaFaChatMain.resourceList.get(rosterjid);
     var currentUI = $("#" + jid).parent();
     var header_device = $("#" + jid + " .webim_main_roster_header [id='roster_device']");  //设备类型
     var header_photo = $("#" + jid + " .webim_main_roster_header_photo");
     var header_status = $("#" + jid + " [id='webim_main_roster_header_status']");
     var li_html = "";    
     if (header_status.length == 0) return false;
     if (state == "offline" ) {
       if (resources.split(',').length==1) {
         header_status.attr("state", state);
         header_photo.addClass("webim_user_offline");
         header_device.hide();
         header_status.attr("class", "webim_main_roster_header_offline");
         li_html = $("#" + jid)[0].outerHTML;
         $("#" + jid).remove();
         currentUI.append(li_html);
         clearTimeout(FaFaChatMain.resourceList.get(rosterjid));
         FaFaChatMain.OnlineMsg();
         FaFaChatMain.SubGroupCount(false,$("#"+jid).parent().attr("id"));
         return; }
       else if(resources.indexOf(",")>-1) {
         resources = resources.replace(","+resource,"").replace(resource+",","");
         FaFaChatMain.resourceList.put(rosterjid,resources);}
     }
      header_device.show();
      if(resources.indexOf('FaFaWin')>-1) {
          header_device.hide();
      }
      else if (resources.indexOf("iPad")>-1) {
          header_device.attr("class", "webim_main_roster_header_device_iPad");
          header_device.attr("title","iPad在线");
      }      
      else if (resources.indexOf("FaFaIPhone")>-1) {
          header_device.attr("class", "webim_main_roster_header_device_Iphone");
          header_device.attr("title","iphone在线");
      }
      else if (resources.indexOf('FaFaAndroid')>-1) {
          header_device.attr("class", "webim_main_roster_header_device_Androd");
          header_device.attr("title","android在线");
      }
      else if (resources.indexOf('FaFaWeb')>-1) {
          header_device.attr("class", "webim_main_roster_header_device_web");
          header_device.attr("title","webIM在线");
      }
      else
          header_device.hide();
               
      if (show == "away" || show == 'xa') //离开
        header_status.attr("class", "webim_main_roster_header_leave");
      else if (show == "dnd") {             
        if (showdesc == "请勿打扰")
          header_status.attr("class", "webim_main_roster_header_disturb");
        else
          header_status.attr("class", "webim_main_roster_header_busy");
      }
      else
          header_status.attr("class", "webim_main_roster_header_online");
      header_photo.removeClass("webim_user_offline");
      var UIfirstLi = $("#" + currentUI.attr("id") + ">li:first");  //求所属分组栏
      li_html = $("#" + jid)[0].outerHTML;
      $("#" + jid).remove();
      $(li_html).insertAfter(UIfirstLi);
      FaFaChatMain.OnlineMsg();
      FaFaChatMain.SubGroupCount(false,$("#"+FaFaChatMain.FormateJid(rosterjid)).parent().attr("id"));
   }
};

//组织机构在线状态判断
FaFaChatMain.ChangeOrganStatus = function (jid, state ,resource) {
    var treeObj = $.fn.zTree.getZTreeObj("treeDepart");
    if (treeObj != null)
    {
      var Node = treeObj.getNodesByParam("id", jid);
      if (Node.length>0)
      {
        var html="";
        var nodeLi = Node[0].tId;
        var nodeIco = nodeLi+"_ico";
        if (state=="offline")
        {
           var all_resource = FaFaChatMain.resourceList.get(jid).split(',').length;
           if (all_resource == 1)
           {
             $("#"+nodeIco).attr("style","background:url("+organ_OfflineIco+") center center no-repeat;");
             //移动到后面的处理
             html = $("#"+nodeIco).parent().parent()[0].outerHTML;
             $("#"+nodeIco).parent().parent().remove();
             $("#"+nodeIco).parent().parent().parent().append(html);
             clearTimeout(FaFaChatMain.resourceList.get(jid));
           }
           else
           {
              all_resource = all_resource.replace(","+resource,"").replace(resource +",","");
              FaFaChatMain.resourceList.put(jid,all_resource);
           }     
        }
        else
        {
          $("#"+nodeIco).attr("style","background:url("+organ_OnlineIco+") center center no-repeat;");
          html = $("#"+nodeIco).parent().parent()[0].outerHTML;
          var root = $("#"+nodeIco).parent().parent().parent().attr("id");
          $("#"+nodeIco).parent().parent().remove();
          //判断第一项是否有子项
          if( $("#"+root+">li:first").children().attr("class").indexOf('open')>-1 || typeof($("#"+root+">li:first").children().attr("disabled"))!="undefined")
	          $(html).insertAfter($("#"+root+">li:first"));
	        else
	          $(html).insertBefore($("#"+root+">li:first"));	           
        }
      }
    }
};

//获得头像
FaFaChatMain.GetHeadsCulpture = function (jid) {
    FaFaEmployee.Query(jid, function (data) {
       if ( FaFaChatMain.MemberList.get(jid)==null){
          var obj = {groupid:0,name:data.name,mobile:data.mobile,email:data.email,img:data.photo};
          FaFaChatMain.MemberList.put(jid,obj);
       }
       jid = FaFaChatMain.FormateJid(jid);
       $("#" + jid + " .webim_main_roster_header_photo").attr("src", data.photo);
       $("#" + jid + " .webim_main_roster_header_desc").text(data.dept);
    });
};

FaFaChatMain.Connection = function (FaFa, pwd) {
    this.owner = new roster();
    this.owner.jid = FaFa;
    this.password = pwd;    
    if (this.mainPanel == null && typeof(FaFa)!='undefined') {
        setTimeout("FaFaChatMain.Connection(FaFaChatMain.owner.jid,FaFaChatMain.password)", 100);
        return;
    }
    //连接提示
    var hint_html = '';
    try {
    	  if(FaFaChatMain.conn_count<=0){
    	  	this.mainPanel.html("<div class='webim_main_errorhint'></div><div class='webim_main_descloading' style='cursor:default;'>&nbsp;&nbsp;连接失败！<div>");
    	  	return;
    	  }
        hint_html = "<div style='float:left;width:100%;height:30px;'><img style='width:30px;height:30px;margin-left:10px;' src='/bundles/fafatimewebase/images/loading.gif' /><span class='webim_main_urlloading_word'>WebIM 正在登录...</span></div>";
        this.mainPanel.html(hint_html);
        if(typeof(FaFa)=='undefined')
           FaFaMessage.RestartConn();
        else
        {
          FaFaMessage.Connection(FaFa, pwd);       
        }
        FaFaChatMain.conn_count--;
    }
    catch (Error) {
      hint_html = "<div class='webim_main_errorhint'></div><div class='webim_main_descloading' style='cursor:default;'>&nbsp;&nbsp;连接失败！<div>";
      this.mainPanel.html(hint_html);
    }
}

//取部门下人员
FaFaChatMain.Get_Dept_Employee = function (treeName,treeId, treeNode,type) {
    var zNodeEmployee = new Array();
    var treeObj = $.fn.zTree.getZTreeObj(treeName);
    if(type){
      for (var i = 0; i < FaFaChatMain.famainOrgList.length; i++) {
      if (FaFaChatMain.famainOrgList[i] == treeNode.id)
        return;
      }
    }
    else{
      for (var i = 0; i < FaFaChatMain.fawindowOrgList.length; i++) {
      if (FaFaChatMain.fawindowOrgList[i] == treeNode.id)
        return;
      }
    }
    FaFaEnterprise.GetEmployees(treeNode.id, function (list) {
        var rosterJid = "";
        for (var j = 0; j < list.length; j++) {
          var empInfo = $(list[j]);
          rosterJid = empInfo.attr('loginname');
          var employeename = empInfo.attr('employeename');
          if (treeObj.getNodesByParam("id", rosterJid).length > 0)
            return;
          zNodeEmployee.push({ id: rosterJid, pId: treeNode.tId, name: employeename, icon:(type?organ_OfflineIco:organ_OnlineIco)});              
        }
        treeObj.addNodes(treeNode, zNodeEmployee);
        if(type)
          FaFaChatMain.famainOrgList.push(treeNode.id);
        else
          FaFaChatMain.fawindowOrgList.push(treeNode.id);        
        for (var k = 0; k < list.length; k++) {
          rosterJid = $(list[k]).attr("loginname");
          if (rosterJid == Jid.Bear(FaFaChatMain.owner.jid))
             FaFaChatMain.ChangeOrganStatus(rosterJid, "online","WeFaFa");
          if(FaFaChatMain.onlineCache.get(rosterJid) != null)
              FaFaChatMain.ChangeOrganStatus(rosterJid, FaFaChatMain.onlineCache.get(rosterJid).Type,Jid.Parse(FaFaChatMain.onlineCache.get(rosterJid).From).resource);
        }
    });
};

FaFaChatMain.ShowNoReadMessageList = function () {
    if (FaFaChatMain.msglisttimer == null) return;
    //取出所有未读消息及数量
    var unreadDiv = document.createElement("div");
    unreadDiv.id = "unreadmsglist";
    var msgs=[],nick = null;
    for (var jid in FaFaChatMain.newMessageJidList.keySet()) {
        if ( typeof(jid)=='undefined' || jid.indexOf("@")== -1) continue;
        var jObj = FaFaChatMain.newMessageJidList.get(jid);
        if (jObj == undefined) break;        
        if (jid.indexOf("@") > 1 && $(jObj).attr("Type")=="groupchat") {
           nick = $("#"+$(jObj).attr("To").user).text().replace(/\s/g, "");
           msgs.push("<div jid='" + jid + "'><span>" + nick + " (" + jObj.length + ")</span></div>");
        }
        else if (jObj!=null && jObj[0]!=null) {
          if (typeof(jObj[0].Body)!="undefined" && jObj[0].Type =="business")
             msgs.push("<div jid='" + jid + "'><span><div class='webim_main_discuss'></div>&nbsp;您有新的系统消息(" + jObj.length + ")</span></div>");
          else if ( typeof(jObj[0].Type)!="undefined" && jObj[0].Type=="broadcast")
             msgs.push("<div jid='" + jid + "'><span>&nbsp;您有新的广播消息(" + jObj.length + ")</span></div>");
          else {
            nick = jObj[0].Nick;
            if(typeof(nick)=="undefined") {            
              nick = FaFaChatMain.MemberList.get(jid);
              if(typeof(nick)=="undefined" || nick=="")
                FaFaEmployee.Query(jid, function (data) {
                  nick = data.name;
                });
              else
                nick = nick.name;
            }
            msgs.push("<div jid='" + Jid.Bear(Jid.toString(jObj[0].From)) + "'><span>" + nick + " (" + jObj.length + ")</span></div>");
          }
        }
    }           
    unreadDiv.innerHTML = msgs.join("");
    $(unreadDiv).attr("width","200px");
    document.body.appendChild(unreadDiv);    
    var xy = $(".webim_main_header").offset();
    var h = $(unreadDiv).height();
    $(unreadDiv).attr("class", "webim_main_unreadmsglist");
    if (!FaFaChatMain.isExpand) //最小化时向上展开
        $(unreadDiv).css({ "top": (xy.top - h - 12) + "px", "left": xy.left + "px" });
    else                       //最大化时向下展开
        $(unreadDiv).css({ "top": (xy.top + 24) + "px", "left": xy.left + "px" });

    $(unreadDiv).unbind("mouseover");
    $(unreadDiv).live("mouseover", function () {
        clearTimeout(FaFaChatMain.msglisttimer_hide);
    });
    $(unreadDiv).unbind("mouseout");
    $(unreadDiv).live("mouseout", function (e) {
        clearTimeout(FaFaChatMain.msglisttimer);
        if (checkHover(e, this)) FaFaChatMain.msglisttimer_hide = setTimeout("$('#unreadmsglist').remove()", 500);
    });
    
    $("#unreadmsglist div").unbind("click");
    $("#unreadmsglist div").live("click", function () {
        var newList = new HashMap();
        for (var jid in FaFaChatMain.newMessageJidList.keySet()) {
            if (jid.indexOf("@") > 1 && $(this).attr("jid").indexOf(jid) > -1) {
                var new_jid = $(this).attr("jid");
                if( $(FaFaChatMain.newMessageJidList.get(jid)).attr("Type")=="groupchat")
                  new_jid = "gid"+ new_jid;
                else { //清除好友列表内的闪烁
                  clearTimeout(FaFaChatMain.Isheaderflash.get(FaFaChatMain.FormateJid(jid)));
                  $("#webim_main_newmsg_button").attr("class","webim_online_img");
                  $(".webim_main_header").css("background","#B5E7EB");
                  $("#roster_header_"+FaFaChatMain.FormateJid(jid)).show();
                  document.title= FaFaChatMain.fafa_title;
                }
                FaFaChatWin.ShowRoster(new_jid);
                break;
            }
            else if (jid.indexOf("@") > 1) 
              newList.put(jid, FaFaChatMain.newMessageJidList.get(jid));
        }
        FaFaChatMain.newMessageJidList = newList;
        if (newList.count() == 0) {
           clearTimeout(FaFaChatMain.isNewMessageFlash);
           $("#webim_main_newmsg_button").attr("class", "webim_online_img");
        }
    });
};

//加载主面板
FaFaChatMain.load = function () {       
    var mainbox = $("#fafaocsfontbox");
    var mainselectedstatus = $("#fafastatusbox");  //选择状态列表框
    var disconnect = $("#webim_disconnect_hint");
    var main_search_panel = $("#search_panel");
    if (mainbox.length == 0) {
        mainbox = document.createElement("div");
        mainbox.id = "fafaocsfontbox";
        document.body.appendChild(mainbox);
        mainbox = $("#fafaocsfontbox");
        mainbox.css("width", FaFaChatMain.w + "px");
        mainbox.css("height", FaFaChatMain.minHeight);        
        mainselectedstatus = document.createElement("div");
        mainselectedstatus.id = "fafastatusbox";
        document.body.appendChild(mainselectedstatus);                
        main_search_panel = document.createElement("div");
        main_search_panel.id = "search_panel";    
        document.body.appendChild(main_search_panel);
        $("#search_panel").attr("class","webim_search_panel");
        var _audioDiv = $("#audio");       
        _audioDiv  = document.createElement("div");
        _audioDiv.id="audio";
        document.body.appendChild(_audioDiv);
        $("#audio").jPlayer({
          ready: function (event) {
           $("#audio").jPlayer("setMedia", {wav:''});
          },
          swfPath: "js",
          supplied: "wav",
          wmode: "window"
        });
        $("#audio").hide();
        disconnect = document.createElement("div");
        disconnect.id = "webim_disconnect_hint";
        document.body.appendChild(disconnect);        
        var html="  <ul>" + 
                 "   <li id='li_online'><div class='webim_status_select_online'><span class='webim_status_select_word'>在线</span></div></li>" +
                 "   <li id='li_leave'><div class='webim_status_select_leave'><span class='webim_status_select_word'>离开</span></div></li>" +
                 "   <li id='li_busy'><div class='webim_status_select_busy'><span class='webim_status_select_word'>忙碌</span></div></li>" +
                 "   <li id='li_dnd'><div class='webim_status_select_disturb'><span class='webim_status_select_word'>请勿打扰</span></div></li>" +
                 "   <li id='li_offline'><div class='webim_status_select_offline'><span class='webim_status_select_word'>离线</span></div></li>" +
                 "</ul>";
        $("#fafastatusbox").addClass("webim_status_select_list");
        $("#fafastatusbox").append(html);
        window.onscroll = this.moveRightBottom;
        window.onresize = this.moveRightBottom;
        hint_html = '<div style="width:100%;float:left;"><span style="float:left;margin-left:10px;">WebIM即时沟通</span><div id="btn_webimchat_login" style="background-color: rgb(112, 180, 57); border-radius: 2px; float: right; height: 22px; line-height: 22px; margin-right: 10px; margin-top: 4px; text-align: center; width: 60px; cursor: pointer; color: rgb(243, 243, 243);">立即登录</div></div>';
        mainbox.html(hint_html);
        $("#btn_webimchat_login").on("click",function(){
        	//打开登录窗口
        	var loginWin = 	'<div style="margin: 5px 30px;"><div><span for="username">登录帐号：</span>'+
        					'<input type="text" id="webim_username" value="" maxlength="32"></div>'+
							'<div><span for="password">登录密码：</span><span>'+
							'<input type="password" id="webim_password" onpaste="return false;" maxlength="20"></span></div>'+
							'<div><span id="webim_login" style="background-color: rgb(112, 180, 57); height: 22px; text-align: center; width: 60px; cursor: pointer; color: rgb(243, 243, 243); margin-top: 10px; line-height: 22px; float: right; border-radius: 2px;">登 录</span>'+
							'</div></div>';
        	$("#fafaocsfontbox").html(loginWin).css("height","170px");
	        $("#webim_login").on("click",function(){
	        	var _u = $.trim($("#webim_username").val());
	        	var _p = $.trim($("#webim_password").val());
	        	if(_u=="" || _p=="")
	        	{
	        		return;
	        	}
	        	$("#fafaocsfontbox").html("").css("height",FaFaChatMain.minHeight);
	        	FaFaChatMain.Connection(_u,_p);
	        });        	
        });
    }
    this.mainPanel = mainbox;
    this.moveRightBottom();
};
///主面板始终定位在窗口右下角
FaFaChatMain.moveRightBottom = function () {
    if (FaFaChatMain.mainPanel == null) return;
    if (FaFaChatMain.isExpand) FaFaChatMain.Expand();
    else FaFaChatMain.Min();
}

FaFaChatMain.Error = function (error) {
    if (this.obj == null) return;
    this.obj.find(".webim_modal-body").html("");
    this.obj.find(".webim_modal-body").append("<div class='webim_errorhint'></div><div class='webim_descloading'>" + error + "</div>");
};

FaFaChatMain.NewMessageHint = function () {
    var webim_main_newmsg_flash = $("#webim_main_newmsg_button.webim_online_img");
    if (webim_main_newmsg_flash.length > 0){
      $(".webim_main_header").css("background","#B5E7EB");
      document.title= "【　　　】"+FaFaChatMain.fafa_title;
      webim_main_newmsg_flash.attr("class", "webim_online_img2");
    }
    else{
      $("#webim_main_newmsg_button").attr("class", "webim_online_img");
      document.title= "【新消息】" + FaFaChatMain.fafa_title;
      $(".webim_main_header").css("background","PaleGoldenrod");
    }
    if (FaFaChatMain.isNewMessageFlash != null) clearTimeout(FaFaChatMain.isNewMessageFlash);
    FaFaChatMain.isNewMessageFlash = setTimeout("FaFaChatMain.NewMessageHint()", 400);
};

//头像
FaFaChatMain.RosterHeaderHint = function (jid) {
   if (FaFaChatMain.headerflashlist.get(jid) != null){
     $("#" + jid + " .webim_main_roster_header_photo").toggle();
     if (FaFaChatMain.Isheaderflash.get(jid) !=null ) clearTimeout(FaFaChatMain.Isheaderflash.get(jid))
     var Isheaderflash = setTimeout("FaFaChatMain.RosterHeaderHint('"+jid+"')",500);
     FaFaChatMain.Isheaderflash.put(jid,Isheaderflash);
   }
};

FaFaChatMain.Offline = function (reason) {
    if(FaFaChatMain.isExpand)
       FaFaChatMain.Min();
    //清除已有列表，并设置为最小化状态
    $(".webim_main_body").hide();
    $(".webim_main_organ").hide();
    $(".webim_main_group").hide();    
    //设置离线状态
    $(".webim_online_img").attr("class", "webim_roster_offline");
    if (reason == "other-connectioned") {
      var header = $("#fafaocsfontbox").offset();       
      $("#webim_disconnect_hint").css({ "bottom":($("#fafaocsfontbox").height()+1)+"px", "left": (header.left) + "px","z-index":"9200"});
      if ($("#webim_disconnect_hint").html()==""){
        $("#webim_disconnect_hint").html("<span class='webim_disconnect_hint_word'>连接已断开，在其他地方登录。</span><div title='重新连接' class='webim_refresh_connection'></div>");
      }
      $(".webim_main_nickname").text("即时沟通[0/" + (FaFaChatMain.totalRoster) + "]");
      $("#webim_disconnect_hint").show();
      
      $(".webim_refresh_connection").unbind("click");
      $(".webim_refresh_connection").live("click",function(){
        var html = "<div style='float:left;width:100%;height:30px;'><img style='width:30px;height:30px;margin-left:10px;' src='/bundles/fafatimewebase/images/loading.gif' /><span class='webim_main_urlloading_word'>WebIM 正在登录...</span></div>";
        $("#fafaocsfontbox").html(html);
        $("#webim_disconnect_hint").hide();
        FaFaMessage.RestartConn();
      });
      return;
    }
    if (reason == "other-connectioned" || reason == "conflict" || reason == "manual"){
       clearTimeout(FaFaChatMain.IsAfresh);
       return; //其他地方登录该帐号或者人为离线
    }
    clearTimeout(FaFaChatMain.IsAfresh);
    FaFaChatMain.IsAfresh = setTimeout("FaFaChatMain.Connection()", 3000); //3秒后自动重连
};

//窗口初始化
FaFaChatMain.init = function () {
	  FaFaChatMain.fafa_title = document.title;
    if (typeof (FaFaMessage) == "undefined" || typeof ($.fn.modal) == "undefined" || typeof($.fn.jPlayer) =="undefined" || typeof($.fn.zTree)=="undefined" ) {
        setTimeout("FaFaChatMain.init()", 200);
        return;
    }
    this.load();
    //好友列表
    FaFaMessage.RosterAfter(function (list) {        
        if (FaFaMessage.isAttached && $(".webim_main_group").children().length==0)
           FaFaChatMain.getGroupInfo();
        FaFaChatMain.totalRoster = list.length;
        var fafaocscontent="",GroupText="";
        
        for (var i = 0; i < list.length; i++) {
          var rosterInfo = list[i];
          var jid = Jid.Bear(rosterInfo.Jid);
          var _jid = FaFaChatMain.FormateJid(jid);
          GroupText = rosterInfo.Group;
          if (GroupText=="" || GroupText==null) {
          	GroupText = "我的好友";
            //FaFaChatMain.totalRoster--;
            //continue;
          }
          if ($("#" + _jid).length > 0) {
              FaFaChatMain.totalRoster--;
              continue;
          }
          var fromjid  = Jid.Bear(rosterInfo.From);
          //判断是否同一企业
	       if ( Jid.Parse(jid).user.split('-')[1]!=Jid.Parse(fromjid).user.split('-')[1])
	           GroupText="企业联系人";
          fafaocscontent = FaFaChatMain.LiveRoster(GroupText);
          var online_cache = FaFaChatMain.onlineCache.get(jid)
          var li = ["<li class='rostersendmsg' id='" + _jid + "'>"];
          li.push("<div class='webim_main_roster_header'>");
          li.push("  <div id='roster_device' class='webim_main_roster_header_device'></div>");
          if( online_cache != null)
            li.push("  <div id='roster_header'><img id='roster_header_" + _jid + "' class='webim_main_roster_header_photo webim_user_online' src='"+FaChatMain_domain+"/bundles/fafawebimimchat/images/nophoto.jpg' /></div>");
          else
          	li.push("  <div id='roster_header'><img id='roster_header_" + _jid + "' class='webim_main_roster_header_photo webim_user_offline' src='"+FaChatMain_domain+"/bundles/fafawebimimchat/images/nophoto.jpg' /></div>");
          li.push("  <span class='webim_main_roster_header_nickname' jid='" + jid + "'>" + rosterInfo.name + "</span>");
          li.push("  <span class='webim_main_roster_header_desc'></span>");
          li.push("</div>");
          if( online_cache != null)
             li.push("<div id='webim_main_roster_header_status' class='webim_main_roster_header_online'></div>");          
          else{
          	li.push("<div id='webim_main_roster_header_status' class='webim_main_roster_header_offline'></div>");
          }
          li.push("</li>");
          fafaocscontent.append(li.join(""));
          FaFaChatMain.GetHeadsCulpture(jid);
          for(var temp in FaFaChatMain.onlineCache.keySet()){
            if (temp.indexOf("@") > 1)
            {
              var _pre = FaFaChatMain.onlineCache.get(temp);
            	FaFaChatMain.ChangeRosterStatus(temp,_pre.Type,_pre.Show,_pre.Status,Jid.Parse(temp).resource);
            }
          }
        }
        FaFaChatMain.SubGroupCount(true,null);
        FaFaChatMain.OnlineMsg();
        if($(".webim_main_body").children().length>1)
        {
          //分组排序处理(内部联系人放第一)
          if($("#roster_内部联系人").length>0) {
          	var html = $("#roster_内部联系人")[0].outerHTML;
            $(".webim_main_body ul[id='roster_内部联系人']").remove();
            $(html).insertBefore($(".webim_main_body").children()[0]);
          }
          //折叠内部联系人外的组
          if ($(".webim_main_body").length>0) {
            for( var j=0;j<$(".webim_main_body").children().length;j++) {
              var uid = $(".webim_main_body").children()[j].id;
              if( j> 0 ){
                $("#"+uid + ">li:not(:first)").hide();
                $("#"+uid + ">li:first>div").attr("class","webim_main_group_image_close");
              }
              //排序
              var len = $("#"+uid).children().length;
              if(len > 2){
	              for(var k=1;k < len  ;k++){
	              	if($($("#"+uid).children()[k]).find(".webim_user_offline").length==1)
	              		$("#"+uid).append( $("#"+uid).children()[k] );  		        		          		 	              		
	              }
              }
            }
          }
        }
        
        $(".rostersendmsg").unbind("dblclick");
        $(".rostersendmsg").live("dblclick", function () {
            var jid = $("#" + $(this).attr("id") + ">.webim_main_roster_header").find(".webim_main_roster_header_nickname").attr("jid");
            if (FaFaChatWin.toList.count()>=5)
            {
            	 if(FaFaChatWin.toList.get(jid)==null)
            	   return;
            } 
            FaFaChatMain.Cancel_Select_Style(jid,false);
            $(this).css("background-color","PaleGoldenrod");
            FaFaChatMain.SendMsgByMember(jid);
            $(this).parent().find(".webim_main_roster_header_photo").show();
        });

        $(".webim_main_roster_header_nickname").unbind("click");
        $(".webim_main_roster_header_nickname").live("click", function () {
            var jid = $(this).attr("jid");
            if ( $(".webim_modal-header .webim_nickname>span").length>=5)
            {
            	 if ( $(".webim_modal-header .webim_nickname a[jid='" + jid + "']").length==0)
            	 {
            	 	 FaFaChatWin.Hint("最多同时显示5个联系人！", 3000);
            	   return;
            	 }
            } 
            FaFaChatMain.Cancel_Select_Style(jid,false);
            $(this).parent().parent().css("background-color","PaleGoldenrod");
            $(this).parent().find(".webim_main_roster_header_photo").show();
            $(".webim_main_header").css("background","#B5E7EB");
            $("#webim_main_newmsg_button").attr("class","webim_online_img");
            document.title=FaFaChatMain.fafa_title;
            FaFaChatMain.SendMsgByMember(jid);
        });
        
        $(".webim_main_roster_header_photo").unbind("click");
        $(".webim_main_roster_header_photo").live("click", function () {
            var jid = $(this).parent().parent().find(".webim_main_roster_header_nickname").attr("jid");
            if ( $(".webim_modal-header .webim_nickname>span").length>=5)
            {
            	 if ( $(".webim_modal-header .webim_nickname a[jid='" + jid + "']").length==0)
            	 {
            	 	 FaFaChatWin.Hint("最多同时显示5个联系人！", 3000);
            	   return;
            	 }
            } 
            FaFaChatMain.Cancel_Select_Style(jid,false);
            $(this).parent().parent().parent().css("background-color","PaleGoldenrod");
            $(this).find(".webim_main_roster_header_photo").show();
            $(".webim_main_header").css("background","#B5E7EB");
            document.title=FaFaChatMain.fafa_title;
            $("#webim_main_newmsg_button").attr("class","webim_online_img");
            FaFaChatMain.SendMsgByMember(jid);
        });

        $(".webim_main_body .webim_first_li").unbind("click").bind("click", function () {
            var ulId = this.parentNode.id;
            if (this.firstChild.getAttribute("class")=="webim_main_group_image_open")
              $(this.firstChild).attr("class","webim_main_group_image_close");
            else{
              $(this.firstChild).attr("class","webim_main_group_image_open");
            }
            $("#"+ulId + ">li:not(:first)").toggle();
        });
              
    });
    
    FaFaMessage.GetIQ(function (iq) {
        //判断是否打开了聊天窗口，打开了FaFaChatWindow时不处理。
        if ("undefined" != typeof (FaFaChatWin)) {
            if (FaFaChatWin.active != null && FaFaChatWin.active.jid != "") return;
        }
        var fileResource = Jid.Parse(iq.From).resource;
        var fromJid = Jid.Bear(iq.From);
        //有文件请求到达
        if (iq.tagName == "si" || (iq.tagName == "fafawebfile") && "request" == iq.Body.getAttribute("action")) {
            if (FaFaChatMain.owner.resource.join(",").indexOf("FaFaWin")>-1 || FaFaChatMain.owner.resource.join(",").indexOf("FaFaAndroid")>-1) return false
            //提示栏提示
            FaFaChatMain.NewMessageHint();
            //判断展开状态，并查找消息发送人，找到则头像闪烁
            var formatejid = FaFaChatMain.FormateJid(fromJid);
            if (FaFaChatMain.headerflashlist.get(formatejid) == null)
                FaFaChatMain.headerflashlist.put(formatejid, 1);
            FaFaChatMain.RosterHeaderHint(fromJid);
        }
        return true;
    });
    
    //出席状态
    FaFaMessage.GetPresence(function (pre) {
        var fromJid = Jid.Bear(pre.From),jidResource = Jid.Parse(pre.From).resource,jidBear=Jid.Bear(FaFaChatMain.owner.jid),isSelf=(fromJid==jidBear);
        var rawrequest=$(pre.Body).find("rawrequest");
        if ( rawrequest.length>0)
    	  {
    	  	   //FIX:好像登录前，已在线的设备不会好送出席过来
    	  	   //判断是否只有web在线，是则发送不支持提示
    	  	   //var _soc=FaFaChatMain.owner.resource;
    	  	   //if(_soc==null || _soc.length>1) return;
    	  	   //var _to =rawrequest.attr("groupid");
    	      // FaFaMessage.SendGroupMessage(pre.To,_to,"<span style='color:#999'>【当前正在使用WebIM，不支持语音或视频。】</span>",'');
    	       return;    	 	  
    	  }
        //用户登录设置处理
        if(isSelf) {
           FaFaChatMain.owner.addResource(jidResource);
        }
        if (pre.Type == "hasofflinefile") {
            //离线文件、图片
            //判断是否打开了聊天窗口，打开了FaFaChatWindow时不处理。
            if ("undefined" != typeof (FaFaChatWin)) {
                if (FaFaChatWin.active != null && FaFaChatWin.active.jid != "") return;
            }
            if (FaFaChatMain.owner.resource.join(",").indexOf("FaFaWin")>-1
                || FaFaChatMain.owner.resource.join(",").indexOf("FaFaAndroid")>-1) return;
            //将消息发送者存入未读消息列表中
            FaFaChatMain.SoundControl(Sound.Msg);
            if (FaFaChatMain.newMessageJidList.get(fromJid) == null) 
              FaFaChatMain.newMessageJidList.put(fromJid,pre);
            else {
              var msgs = FaFaChatMain.newMessageJidList.get(fromJid);
              msgs.push(pre);
              FaFaChatMain.newMessageJidList.put(fromJid, msgs);
            }
            //提示栏提示
            FaFaChatMain.NewMessageHint();
            var formatejid = FaFaChatMain.FormateJid(fromJid);
            if (FaFaChatMain.headerflashlist.get(formatejid) == null)
            FaFaChatMain.headerflashlist.put(formatejid, 1);
            FaFaChatMain.RosterHeaderHint(formatejid);
        }
        else {
          if (!isSelf) {          	 
          	var isloaded =  FaFaChatMain.resourceList.get(fromJid);
            if (isloaded == null)
              FaFaChatMain.resourceList.put(fromJid, jidResource);
            else {
              if (isloaded.indexOf(jidResource) == -1) {
                var r_list = isloaded + "," + jidResource;
                clearTimeout(isloaded);
                FaFaChatMain.resourceList.put(fromJid, r_list);
              }
            }
          }
          if (pre.Type == "online") {
              if (isSelf) return;
              if (FaFaChatMain.onlineCache.get(fromJid) == null && isloaded==null)
                  FaFaChatMain.onlineCache.put(fromJid,pre);
              FaFaChatMain.ChangeRosterStatus(fromJid, pre.Type, pre.Show, pre.Status, jidResource);
              FaFaChatMain.ChangeOrganStatus(fromJid,pre.Type,jidResource);
          }
          else {
            if (isSelf) {//如果是自己离线了
              if(jidResource=="FaFaWeb")
                FaFaChatMain.Offline();
            }
            else {
              FaFaChatMain.ChangeRosterStatus(fromJid, pre.Type, pre.Show, pre.Status, jidResource);
              FaFaChatMain.ChangeOrganStatus(fromJid,pre.Type,jidResource);
              //离线时清除缓存
              clearTimeout(FaFaChatMain.onlineCache.get(fromJid));
            }
          }
          return true;
        }
    });
    
    //收到新消息时，在提示栏提示，如果列表已展开，同时需要针对当前消息发送人进行头像闪烁
    FaFaMessage.GetMessage(function (Msg) {
        FaFaChatMain.SoundControl(Sound.Msg);
        //判断是否打开了聊天窗口，打开了FaFaChatWindow时不处理。
        if ("undefined" != typeof (FaFaChatWin)) {
            if (FaFaChatWin.active != null && FaFaChatWin.active.jid != "") return;
        } 
        var fromJid = Msg.From.user == "guest" ? Jid.toString(Msg.From) : Msg.From.user + "@" + Msg.From.server;
        var type = Msg.Type;
        if (type == "groupchat")
            fromJid = Msg.To.user + "@" + Msg.To.server;
        //将消息发送者存入未读消息列表中
        if (FaFaChatMain.newMessageJidList.get(fromJid) == null)
            FaFaChatMain.newMessageJidList.put(fromJid, [Msg]);
        else {
            var msgs = FaFaChatMain.newMessageJidList.get(fromJid);
            msgs.push(Msg);
            FaFaChatMain.newMessageJidList.put(fromJid, msgs);
        }
        //提示栏提示
        FaFaChatMain.NewMessageHint();
        //判断展开状态，并查找消息发送人，找到则头像闪烁
        var formatejid = FaFaChatMain.FormateJid(fromJid);
        if (FaFaChatMain.headerflashlist.get(formatejid) == null)
            FaFaChatMain.headerflashlist.put(formatejid, 1);
        FaFaChatMain.RosterHeaderHint(formatejid);
        return true;
    });

    FaFaMessage.ConnectionStateChange(function (state, info) {
        if (state > 3 && state != Strophe.Status.CONNECTED) {
            FaFaChatMain.Offline(info);
        }
        else if (state == Strophe.Status.CONNECTED) {
        	 if ( $(".webim_disconnect_hint_word").length>0)
           {
        	   if($("#webim_disconnect_hint").is(":visible"))
        	     $("#webim_disconnect_hint").hide();
           } 
            FaFaChatMain.conn_count = 10; //恢复连接次数
            FaFaChatMain.mainPanel.html("");
            FaFaChatMain.OwnerInit(); //初始化当前登录人信息          
            if($(".webim_main_group").children().length==0)
               FaFaChatMain.getGroupInfo();  //当前登录人群组信息
            FaFaEmployee.QueryDept(function (list) {
                //获取当前企业的所有部门
                var zNodes = new Array();
                for (var i = 0; i < list.length; i++) {
                    var organInfo = $(list[i]);
                    var pid = organInfo.attr('pid');
                    var deptid = organInfo.attr('deptid');
                    var deptname = organInfo.attr('deptname');
                    var noorder = organInfo.attr('noorder');
                    if (pid == -10000)
                        zNodes.push({ id: deptid, pId: pid, name: deptname, open: true, icon:FaChatMain_domain+"/bundles/fafawebimimchat/images/org_root.png" });
                    else
                        zNodes.push({ id: deptid, pId: pid, name: deptname, icon:FaChatMain_domain+ "/bundles/fafawebimimchat/images/tree_node_close.png", iconOpen:FaChatMain_domain+"/bundles/fafawebimimchat/images/tree_node_open.png", iconClose:FaChatMain_domain+"/bundles/fafawebimimchat/images/tree_node_close.png" });
                }
                var setting = { data: { simpleData: { enable: true} },
                    callback:
                {
                    onClick: function (event, treeId, treeNode) {
                        if (treeNode.id.indexOf("@") > -1)
                        {
                          if (treeNode.id != Jid.Bear(FaFaChatMain.owner.jid)){
                            if(FaFaChatWin.active!=null && FaFaChatWin.active.jid!=""){
                              var jid = treeNode.id;              
                              FaFaChatMain.Cancel_Select_Style(FaFaChatWin.active.jid,true);
                              if(FaFaChatMain.GroupInfo.get(jid.split("@")[0])==null)
                                jid= FaFaChatMain.FormateJid(jid);
                              else
                                jid= jid.split("@")[0];
                              if($("#"+jid).length==1)
                                $("#"+jid).css("background-color","PaleGoldenrod");
                            }
                            FaFaChatWin.ShowRoster(treeNode.id);
                          }
                        }
                        else {
                            FaFaChatMain.Get_Dept_Employee("treeDepart",treeId, treeNode,true);
                            $.fn.zTree.getZTreeObj("treeDepart").expandNode(treeNode); //单击展开或折叠事件
                        }
                    },
                    onExpand: function (event, treeId, treeNode) {
                        if (treeNode.id.indexOf("@") > -1)
                            FaFaChatWin.ShowRoster(treeNode.id);
                        else
                            FaFaChatMain.Get_Dept_Employee("treeDepart",treeId, treeNode,true);
                    }
                }
                }
                $.fn.zTree.init($("#treeDepart"), setting, zNodes);
            });
        }
    });
};

FaFaChatMain.Connection_State = function(){
   var xmlHttp = null;
   if(window.ActiveXObject)
     xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
   else if (window.XMLHttpRequest)
     xmlHttp=new XMLHttpRequest();
   //状态调用函数
   xmlHttp.onreadystatechange = function(){
     if ( xmlHttp.readyState == 4 ){
       FaFaMessage.Send(FaFaChatMain.owner.jid, FaFaChatMain.owner.jid, xmlHttp.status, '');
       if (xmlHttp.status != 200){
          //FaFaChatMain.Connection(FaFaChatMain.owner.jid,FaFaChatMain.password);
       }
       setTimeout("FaFaChatMain.Connection_State()",5000);
     }
   }
   //发送请求
   xmlHttp.open("GET","http://www.baidu.com" ,true);
   xmlHttp.send();
};

//获得群组信息
FaFaChatMain.getGroupInfo = function(){	
  FaFaEmployee.QueryGroup(function (list) {
  	 FaFaChatMain.GroupList = list;
  	 setTimeout("FaFaChatMain.LoadGroupInfo()",2000);
  });
};

//加载群组信息
FaFaChatMain.LoadGroupInfo = function(){
	   if(FaFaChatMain.is_group_load) return;
	   list = FaFaChatMain.GroupList;
  	 for(var i=0;i<list.length;i++)
  	 {
  	 	  var groupid = list[i].getAttribute("groupid");
        var groupname = list[i].getAttribute("groupname");
        var groupclass=list[i].getAttribute("groupclass");
        if ( groupclass=="" || groupname=="") continue;
        if (FaFaChatMain.GroupInfo.get(groupid)==null)
           FaFaChatMain.GroupInfo.put(groupid,{"logo":"/bundles/fafatimewebase/images/"+(groupclass=="circlegroup"?"default_circle.png":(groupclass=="meeting"||groupclass=="discussgroup"?"default_meeting.png":"default_group.png")),"groupclass":groupclass,desc:$(list[i]).attr("groupdesc"),post:$(list[i]).attr("grouppost").replace(/＜br＞/g,"<br/>"),isload:false,name:groupname});
        if(typeof(FaFaChatWin)!="undefined")
        {
       	   var groupidToJid = groupid+"@fafacn.com";
           if (FaFaChatWin.groupName.get(groupidToJid) == null || FaFaChatWin.groupName.get(groupidToJid)=="")
            FaFaChatWin.groupName.put(groupidToJid, groupname);
           $webim_nickname = $(".webim_nickname a[jid='"+groupidToJid+"']");
           if($webim_nickname.length>0 && $webim_nickname.text()!="")
           {
           	   $webim_nickname.text(groupname);
           }
        }
        if ( groupclass=="discussgroup" || groupclass=="meeting")
            groupclass="meeting";
	 	    else if (groupclass!="circlegroup")
	 	       groupclass="group";
        FaFaChatMain.liveGroupNameUI(groupclass,groupid,groupname);
  	 }
  	 //对群组信息按序
     $(".webim_main_group").prepend($(".groupclass_meeting"));
     $(".webim_main_group").prepend($(".groupclass_circlegroup"));
     $(".webim_main_group").prepend($(".groupclass_group"));
     
     var control = $(".webim_main_group>ul");
     for(var i=0;i<control.length;i++)
     {
     	 var classname = $(control[i]).attr("class");
     	 if ( classname!="undefined" && classname!="")
     	    classname = "."+classname;
     	 if ( i > 0 )
     	 {
     	   $(classname +">li:first>div").attr("class","webim_main_group_image_close");
     	   $(classname + ">li:not(:first)").hide();
       }
       $(classname+" .webim_roster_member").text("["+(control[i].children.length-1)+"]");
     }     
     FaFaChatMain.is_group_load = true;
};

//发送消息
FaFaChatMain.SendMsgByMember = function(jid){
	  clearTimeout(FaFaChatMain.isNewMessageFlash);
    clearTimeout(FaFaChatMain.Isheaderflash.get(FaFaChatMain.FormateJid(jid)));
    $("#" + FaFaChatMain.FormateJid(jid) + " .webim_main_roster_header [id='roster_header_"+FaFaChatMain.FormateJid(jid)+"']").attr("class","webim_main_roster_header_photo");
    if ($("#"+FaFaChatMain.FormateJid(jid)).find("#webim_main_roster_header_status").attr("class")=="webim_main_roster_header_offline")
      $("#"+FaFaChatMain.FormateJid(jid)).find(".webim_main_roster_header").find(".webim_main_roster_header_photo").addClass("webim_user_offline");
    FaFaChatWin.ShowRoster(jid);
    clearTimeout(FaFaChatMain.newMessageJidList.get(jid));
};

//取消选中样式
FaFaChatMain.Cancel_Select_Style = function(_jid,iscancle){
  var active = FaFaChatWin.active;
  if(active==null || active.jid=="") return;
  var jid = active.jid;
  if(iscancle==false && jid==_jid) return;
  //判断是否为群用户
  if(FaFaChatMain.GroupInfo.get(jid.split("@")[0])==null){
    jid= FaFaChatMain.FormateJid(jid);    
    if ($("#"+jid).length==0 || $("#"+jid).attr("style")==null) return;
    if($("#"+jid).attr("style").indexOf("none")>-1){
      $("#"+jid).removeAttr("style");
      $("#"+jid).css("display","none");
    }
    else
      $("#"+jid).removeAttr("style");
  }
  else{
    jid = jid.split("@")[0];
    $("#webim_"+jid).removeAttr("style");
  }
};

//创建常用联系UI
FaFaChatMain.LiveRoster = function (text) {
    var fafaocscontent = $(".webim_main_body ul[id='roster_" + text + "']");
    if (fafaocscontent.length == 0) {
        var html = "<ul id='roster_" + text + "'><li class='webim_first_li' id='"+text+"' style='cursor:pointer;vertical-align:middle;list-style:none;height:18px;line-height:18px;text-align:left;position:relative;padding-top: 5px;'>" +
                   "<div class='webim_main_group_image_open'></div><span class='webim_roster_list'>" + text + "</span><span class='webim_roster_member'></span></li></ul>";
        $(".webim_main_body").append(html);
        fafaocscontent = $(".webim_main_body ul[id='roster_" + text + "']");
    }
    return fafaocscontent;
};

//创建群组UI
FaFaChatMain.liveGroupNameUI = function(groupclass,groupid,groupname){
	 var ctr = $(".webim_main_group .groupclass_" + groupclass);
	 var html = "";
	 if ( ctr.length==0)
	 {
	 	   var name="";
	 	   if (groupclass == "circlegroup")
	 	      name = "我的圈子";
       else if (groupclass=="discussgroup" || groupclass=="meeting")
          name="我的会议";
       else
          name="我的群组";
	 	   html = "<ul class='groupclass_"+ groupclass + "'>"+
	 	         "  <li class='webim_first_li' style='cursor:pointer;vertical-align:middle;list-style:none;height:18px;line-height:18px;text-align:left;position:relative;padding-top: 5px;'>"+
	 	         "    <div class='webim_main_group_image_open' style=''></div>"+
	 	         "    <span class='webim_roster_list'>"+name+"</span>"+
	 	         "    <span class='webim_roster_member'></span>"+
	 	         "  </li>"+
             "  <li class='groupstyle' id='webim_"+groupid+"' jid='" + groupid + "'>"+
             "    <div><div class='webim_main_group_img'></div>"+
             "    <span jid='"+groupid+"' class='webim_main_groupName'>" + groupname + "</span></div></li>"+
             "</ul>"; 
      $(".webim_main_group").append(html);
      
      $(".webim_main_group .webim_first_li").unbind("click").bind("click", function () {
        var parentClass=$(this.parentNode).attr("class");
        if (parentClass!="undefined" && parentClass!="")
            parentClass="."+parentClass;
        if (this.children[0].getAttribute("class")=="webim_main_group_image_open")
        {
          $(this.children[0]).attr("class","webim_main_group_image_close");
          $(parentClass + ">li:not(:first)").hide();
        }
        else
        {
          $(this.children[0]).attr("class","webim_main_group_image_open");  
          $(parentClass + ">li:not(:first)").show();
        }
      });
	 }
	 else
	 {
	 	  html= "<li class='groupstyle' id='webim_"+groupid+"' jid='" + groupid + "'>"+
            "  <div><div class='webim_main_group_img'></div>"+
            "       <span jid='"+groupid+"' class='webim_main_groupName'>" + groupname + "</span>"+
            "  </div>"+
            "</li>";
      ctr.append(html);
	 }
};

//初始化当前登录者信息
FaFaChatMain.OwnerInit = function () {
    var ownerDiv = $(".webim_main_header");
    if (ownerDiv.length == 0) {
        var text_style="color:#999999;border:0 none;font-size:12px;font-family:'宋体','Arial','Helvetica','Verdana','sans-serif';height:15px;line-height:15px;width:200px;position:absolute;top:30px;";
        var html = "<div class='webim_main_header'><span id='webim_main_window_size' class='webim_main_expand' title='最大化'>&nbsp;&nbsp;&nbsp;&nbsp;</span>" +
                   "  <div id='webim_main_newmsg_button' class='webim_online_img'></div><div class='webim_status_selected'></div><div class='webim_main_nickname'></div>" +
                   "  <div class='webim_main_desc'></div>" +
                   "</div>" +
                   "<div class='webim_main_searchRoster'><input type='text' maxlength='20' class='webim_search_roster_default' style=\""+text_style+"\" id='txtSearchRoster' value='搜索联系人、群组' />"+
                   "<div class='webim_main_searchRoster_button'></div><div class='webim_main_searchRoster_cancel'></div><div class='webim_main_searchRoster_enter'></div></div>" +
                   "<div class='webim_main_function_tool'>" +
                   "  <span id='tool_roster' class='function_tool_roster_active' title='我的联系人'>&nbsp;</span><span class='function_tool_organ' id='tool_organ' title='企业通讯录'>&nbsp;</span><span class='function_tool_group' id='tool_group' title='群/组'>&nbsp;</span>" +
                   "</div>" +
                   "<div class='webim_main_body'></div> <div class='webim_main_organ'><ul id='treeDepart' class='ztree' style='border:0px;max-height:100000px;min-height:303px;background-color: #FFFFFF;'></ul></div><div class='webim_main_group'></div> ";
        FaFaChatMain.mainPanel.html(html);             
        $("#txtSearchRoster").live("focus",function(){
           if ($("#txtSearchRoster").val()=='搜索联系人、群组'){
             $("#txtSearchRoster").val('');
             var text_style="color:black;border:0 none;font-size:12px;font-family:'宋体','Arial','Helvetica','Verdana','sans-serif';height:15px;line-height:15px;width:200px;position:absolute;top:32px;";
             $("#txtSearchRoster").attr("style",text_style);
           }
        });
        
        $("#txtSearchRoster").live("blur",function(){
           if ($("#txtSearchRoster").val()==''){
             $("#txtSearchRoster").val('搜索联系人、群组');
             var text_style="color:#999999;border:0 none;font-size:12px;font-family:'宋体','Arial','Helvetica','Verdana','sans-serif';height:15px;line-height:15px;width:200px;position:absolute;top:32px;";
             $("#txtSearchRoster").attr("style",text_style);
             $(".webim_main_searchRoster_button").show();
           }
        });
        
        //文本框变化事件
        $("#txtSearchRoster").live("keyup",function(ev){
           FaFaChatMain.SearchTextChange();
                      
           $(".webim_main_searchRoster_cancel").unbind().bind("click",function(){
             var val = $("#txtSearchRoster").val();
             $("#txtSearchRoster").val(val.substring(0,val.length-1));
             FaFaChatMain.SearchTextChange();
             $("#txtSearchRoster").focus();
           }); 
           
           $(".webim_main_searchRoster_enter").unbind().bind("click",function(){
             var val = $("#txtSearchRoster").val();
             if ( val!="")
             {
                var jid = $("#search_panel li:contains('"+val+"')").attr("jid");
                if ( jid != null)
                {
                   FaFaChatWin.ShowRoster(jid);
                   $("#search_panel").hide();                   
                }            
             }
           });
                      
           $(".search_list").live("click",function(){
             $("#txtSearchRoster").val($(this).text());
             $("#txtSearchRoster").focus();
           });     
           
           $(".search_list").live("dblclick",function(){
             $("#txtSearchRoster").val($(this).text());
             var jid = $(this).attr("jid");
             if ( $(this).attr("isgroup")=="1")
               jid = "gid"+jid;
             FaFaChatWin.ShowRoster(jid);
             $("#search_panel").hide();
             $("#txtSearchRoster").focus();
           });
        });

        $("#webim_main_window_size").unbind().bind("click", function () {
            //最大化窗口
            if (!FaFaChatMain.isExpand)
            {
              if ( $("#webim_main_newmsg_button").attr("class")!="webim_roster_offline")
                FaFaChatMain.Expand();
            }
            else 
              FaFaChatMain.Min();
        });
        
        $(".webim_main_header").unbind().bind("dblclick", function () {
            //最大化窗口
            if (!FaFaChatMain.isExpand)
            {
              if ( $("#webim_main_newmsg_button").attr("class")!="webim_roster_offline")
                FaFaChatMain.Expand();
            }
            else 
              FaFaChatMain.Min();
        });
        //新消息事件
        var webim_main_newmsg_flash = $("#webim_main_newmsg_button");
        webim_main_newmsg_flash.unbind("mouseout");
        webim_main_newmsg_flash.unbind("mouseover");
        webim_main_newmsg_flash.unbind("click");
        webim_main_newmsg_flash.live("mouseout", function () {
          //移出
          clearTimeout(FaFaChatMain.msglisttimer);
          if ($("#unreadmsglist").length > 0) FaFaChatMain.msglisttimer_hide = setTimeout(' $("#unreadmsglist").remove()', 500);
        });
        
        webim_main_newmsg_flash.live("mouseover", function () {
            //是否有未读消息，有则显示消息列表
            if (FaFaChatMain.newMessageJidList.count() == 0) return;
            FaFaChatMain.msglisttimer = setTimeout("FaFaChatMain.ShowNoReadMessageList()", 500);
        });
                
        webim_main_newmsg_flash.live("click", function () {
            clearTimeout(FaFaChatMain.isNewMessageFlash);
            if(FaFaChatMain.isNewMessageFlash==null){  //如果没有消息时则为选择用户状态
              var xy = $(".webim_main_header").offset();      
               var h = $("#fafastatusbox").height();
               if (!FaFaChatMain.isExpand)
               {
                 $("#fafastatusbox").css({ "top": (xy.top - h- 4) + "px", "left": (xy.left) + "px" });
                 $("#fafastatusbox").toggle();
               }
               return false;
            }
            FaFaChatMain.isNewMessageFlash = null; //停止闪烁
            $("#webim_main_newmsg_button").attr("class","webim_online_img");
            $(".webim_main_header").css("background","#B5E7EB");
            document.title=FaFaChatMain.fafa_title;
            //还原状态图片
            if ( FaFaChatMain.rosterState=="online")
               $(this).attr("class", "webim_online_img");
            else if (FaFaChatMain.rosterState=="leave")
               $(this).attr("class", "webim_roster_leave");
            else if (FaFaChatMain.rosterState=="busy")
               $(this).attr("class", "webim_roster_busy");
            else if (FaFaChatMain.rosterState=="dnd")
               $(this).attr("class", "webim_roster_dnd");
            else if (FaFaChatMain.rosterState=="offline")
               $(this).attr("class", "webim_roster_offline");
            if ("undefined" != typeof (FaFaChatWin)) {
                if (FaFaChatWin.active != null && FaFaChatWin.active.jid != "") return;
            }
            for (var jid in FaFaChatMain.newMessageJidList.keySet()) {
                if (jid.indexOf("@") > 1)
                {
                   if ($(FaFaChatMain.newMessageJidList.get(jid)).attr("Type")=="groupchat")
                     FaFaChatWin.ShowRoster("gid"+jid);
                   else
                   {
                     FaFaChatWin.ShowRoster(jid);
                     $(".webim_main_roster_header_nickname").parent().find("#roster_header_"+FaFaChatMain.FormateJid(jid)).show();
                   }
                   clearTimeout(FaFaChatMain.Isheaderflash.get(FaFaChatMain.FormateJid(jid)));
                }
            }
            FaFaChatMain.newMessageJidList = new HashMap();//清空未读消息的jid列表
        });
        
         $(".webim_status_selected").unbind().bind("click", function () {
            var xy = $(".webim_main_header").offset();           
            if (FaFaChatMain.isExpand)
              $("#fafastatusbox").css({ "top": (xy.top + 24) + "px", "left": xy.left+15 + "px" }); 
            $("#fafastatusbox").toggle();
         });
         
         $(".webim_status_select_list").unbind().bind("mouseout", function (e) {
            if (checkHover(e, this)) $("#fafastatusbox").hide();
         });
        
        //点击好友面板
        $("#tool_roster").unbind().bind("click", function () {
            toolIndex = 1;
            $("#tool_roster").attr("class", "function_tool_roster_active");
            $("#tool_organ").attr("class", "function_tool_organ");
            $("#tool_group").attr("class", "function_tool_group");
            $(".webim_main_body").show();
            $(".webim_main_organ").hide();
            $(".webim_main_group").hide();
        });
        //点击组织机构面板(用于切换)
        $("#tool_organ").unbind().bind("click", function () {
            toolIndex = 2;
            $("#tool_roster").attr("class", "function_tool_roster");
            $("#tool_group").attr("class", "function_tool_group");
            $("#tool_organ").attr("class", "function_tool_organ_active");
            $(".webim_main_organ").show();
            $(".webim_main_body").hide();
            $(".webim_main_group").hide();
        });
        //点击群组面板
        $("#tool_group").unbind().bind("click",function(){
            toolIndex = 3;
            $("#tool_roster").attr("class", "function_tool_roster");
            $("#tool_organ").attr("class", "function_tool_organ");
            $("#tool_group").attr("class", "function_tool_group_active");
            $(".webim_main_body").hide();
            $(".webim_main_organ").hide();
            $(".webim_main_group").show();          
        });

        $(".groupstyle").unbind("click");
        $(".groupstyle").live("click",function(){
            var groupid = $(this).attr("jid");
            if ( $(".webim_modal-header .webim_nickname>span").length>=5)
            {
            	 if ( $(".webim_modal-header .webim_nickname a[jid='" + groupid + "']").length==0)
            	 {
            	 	 FaFaChatWin.Hint("最多同时显示5个联系人！", 3000);
            	   return;
            	 }
            }
            FaFaChatMain.Cancel_Select_Style(groupid+"@"+Jid.Parse(FaFaChatWin.owner.jid).server,false);
            $(this).css("background-color","PaleGoldenrod");
            if (!FaFaChatMain.GroupInfo.get(groupid).isload) FaFaChatMain.getGroupMember(groupid);
            groupid = "gid" + groupid;
            FaFaChatWin.ShowRoster(groupid);
        });
         
        //状态更改
        $("#li_online").unbind().bind("click", function () {
            $('#webim_main_newmsg_button').attr('class', 'webim_online_img');
            $(".webim_status_select_list").hide();            
            FaFaMessage.ChangeState("",FaFaChatMain.owner.desc,"");
            FaFaChatMain.rosterState = "online";
        });
        //离开
        $("#li_leave").unbind().bind("click", function () {
            $('#webim_main_newmsg_button').attr('class', 'webim_roster_leave');
              $(".webim_status_select_list").hide();
              FaFaMessage.ChangeState("away",FaFaChatMain.owner.desc,"");
              FaFaChatMain.rosterState = "leave";
        });
        //忙碌状态
        $("#li_busy").unbind().bind("click", function () {
            $('#webim_main_newmsg_button').attr('class', 'webim_roster_busy');
            $(".webim_status_select_list").hide();
            FaFaMessage.ChangeState("dnd",FaFaChatMain.owner.desc,"");
            FaFaChatMain.rosterState = "busy";
        });
        //请勿打扰
        $("#li_dnd").unbind().bind("click", function () {
            $('#webim_main_newmsg_button').attr('class', 'webim_roster_dnd');
            $(".webim_status_select_list").hide();
            FaFaMessage.ChangeState("dnd",FaFaChatMain.owner.desc, "请勿打扰");
            FaFaChatMain.rosterState = "dnd";
        });
        //离线
        $("#li_offline").unbind().bind("click", function () {
            $('#webim_main_newmsg_button').attr('class', 'webim_roster_offline');
            $(".webim_status_select_list").hide();
            FaFaChatMain.Offline("manual");
            FaFaMessage.Disconnect("manual");
            FaFaChatMain.rosterState = "offline";
            
        });
    }
        
    if(typeof(FaFaChatMain.owner.jid)!='undefined')
    {
      FaFaEmployee.Query(FaFaChatMain.owner.jid, function (data) {          
          ownerDiv = $(".webim_main_header");
          data.isLoad = true;
          data.state = "online";
          FaFaChatMain.owner = data;
          FaFaMessage.ChangeState("",data.desc,"");//用户登录成功，并且获取到个人信息后，立即再次发送自己的出席，这次是带个性签名的
      });
    }
}

//搜索文本变化框
FaFaChatMain.SearchTextChange = function()
{
   var val = $("#txtSearchRoster").val().replace(/\s/g,'');
   if(val=="")
   {
     $("#search_panel").hide();
     $(".webim_main_searchRoster_button").show();
     $(".webim_main_searchRoster_cancel").hide();
     $(".webim_main_searchRoster_enter").hide();
     return false;
   }
   $(".webim_main_searchRoster_button").hide();
   $(".webim_main_searchRoster_cancel").show();
   $(".webim_main_searchRoster_enter").show();
   $("#search_panel").html("");
   $("#search_panel").show();
   var img="",nick="",jid="",state=false;
   var onlineHTML="",offlineHTML="";
   for (jid in FaFaChatMain.MemberList.keySet()) {
      if ( jid.indexOf("@")== -1) continue;
      nick = FaFaChatMain.MemberList.get(jid).name;
      email = FaFaChatMain.MemberList.get(jid).email;
      phone = FaFaChatMain.MemberList.get(jid).mobile;     
      if (nick.indexOf(val)>-1 || email.indexOf(val)>-1 || phone.indexOf(val)>-1)
      {
         state = FaFaChatMain.onlineCache.get(jid) != null ? true:false;
         img  = FaFaChatMain.MemberList.get(jid).img;
         if (img != null)
         {
           if (state)
             onlineHTML += "<li isgroup='0' class='search_list' jid ='" + jid + "'><img class='webim_search_img"+
                    "' src='" + img + "' /><span class='webim_search_nick'>" + nick +"</span></li>";
           else
             offlineHTML += "<li  isgroup='0' class='search_list' jid ='" + jid + "'><img class='webim_search_img webim_user_offline"+
                    "' src='" + img + "' /><span class='webim_search_nick'>" + nick +"</span></li>";
         }
         else
         {
           if (state)
             onlineHTML += "<li  isgroup='0' class='search_list' jid ='" + jid + "' state='noPhoto'><img id='"+FaFaChatMain.FormateJid(jid)+"' class='webim_search_img' />"+
                           "<span class='webim_search_nick'>" + nick +"</span></li>";
           else
             offlineHTML+= "<li  isgroup='0' class='search_list' jid ='" + jid + "' state='noPhoto'><img id='"+FaFaChatMain.FormateJid(jid)+"' class='webim_search_img webim_user_offline' />"+
                           "<span class='webim_search_nick'>" + nick +"</span></li>";             
         }
      }
   }
   
   
   for (jid in FaFaChatMain.GroupInfo.keySet()) {
      nick = FaFaChatMain.GroupInfo.get(jid).name;  
      if (nick.indexOf(val)>-1)
      {
         img  = FaFaChatMain.GroupInfo.get(jid).logo;
         if (img != null)
         {
             onlineHTML += "<li  isgroup='1' class='search_list' jid ='" + jid + "'><img class='webim_search_img"+
                           "' src='" + img + "' /><span class='webim_search_nick'>" + nick +"</span></li>";
          
         }
         else
         {
             onlineHTML += "<li  isgroup='1' class='search_list' jid ='" + jid + "' state='noPhoto'><img id='"+FaFaChatMain.FormateJid(jid)+"' class='webim_search_img' />"+
                           "<span class='webim_search_nick'>" + nick +"</span></li>";           
         }
      }
   }
   
   $("#search_panel").html("<ul class='webim_search_roster'></ul>");
   if (onlineHTML !="")
      $(".webim_search_roster").append(onlineHTML);
   if (offlineHTML!="")
      $(".webim_search_roster").append(offlineHTML);
   $(".webim_search_roster>li:first").css("background-color","rgb(181, 231, 235)");
 
};

//计算在线人数及总数
FaFaChatMain.OnlineMsg = function () {
    var onlineMsg = "";
    if (FaFaChatMain.totalRoster == 0)
        onlineMsg = "即时沟通[0/0]";
    else {
       var onlineNum = FaFaChatMain.totalRoster - $(".webim_main_roster_header_offline").length;
       onlineMsg = "即时沟通[" + onlineNum + "/" + (FaFaChatMain.totalRoster) + "]";
    }
    $(".webim_main_header").find(".webim_main_nickname").text(onlineMsg);
    
};

FaFaChatMain.SubGroupCount = function (isOne,ulid) {
   var total=0,online=0,ctrl="",groupText="";
   if(isOne)  {
      var ulList = $(".webim_main_body").children();
      for(var i=0;i<ulList.length;i++)
      {
         ctrl = ulList.eq(i);
         total = ctrl.children().length-1;
         online = $("#"+ctrl.attr("id")+" .webim_user_offline").length;
         online = total - online;
         ctrl = $("#"+ctrl.attr("id")+" li:first>.webim_roster_member");
         var left = $("#"+ctrl.attr("id")+" li:first>#webim_roster_list").attr("width");
         ctrl.text("["+online+"/"+total+"]");
      }
   }
   else
   {
      ctrl = $("#"+ulid);
      total = ctrl.children().length-1;
      online = $("#"+ctrl.attr("id")+" .webim_user_offline").length;
      online = total - online;
      ctrl = $("#"+ctrl.attr("id")+" li:first>.webim_roster_member");
      ctrl.text("["+online+"/"+total+"]");
   }
};

FaFaChatMain.Expand = function () {
    $(".webim_main_function_tool").show();
    $(".webim_main_searchRoster").show();
    $(".webim_status_selected").show();
    $("#fafastatusbox").hide();
    if (toolIndex == 1)
        $(".webim_main_body").show();
    else if (toolIndex == 2)
        $(".webim_main_organ").show();
    else if (toolIndex == 3)
        $(".webim_main_group").show();
    FaFaChatMain.mainPanel.css("height", FaFaChatMain.h + "px");
    FaFaChatMain.isExpand = true;
    $("#webim_main_window_size").attr("class", "webim_main_min");
    $("#webim_main_window_size").attr("title", "最小化");
};

FaFaChatMain.Min = function () {
    FaFaChatMain.mainPanel.css("height", FaFaChatMain.minHeight);
    FaFaChatMain.isExpand = false;
    $(".webim_main_function_tool").hide();
    $(".webim_main_searchRoster").hide();
    $("#search_panel").hide();
    $(".webim_main_body").hide();
    $(".webim_main_organ").hide();
    $(".webim_main_group").hide();
    $(".webim_status_select_list").hide();    
    $(".webim_status_selected").hide();
    $("#webim_main_window_size").attr("class", "webim_main_expand");
    $("#webim_main_window_size").attr("title", "最大化");
};

//获得群成员列表
FaFaChatMain.getGroupMember = function(gid){
  FaFaEmployee.QueryGroupEmployee(gid,function(list){  	
  	var ownerBear = Jid.Bear(FaFaChatMain.owner.jid);
    for(var i=0;i<list.length;i++)
    {
       var jid = $(list[i]).attr("employeeid");
       var gObj = FaFaChatMain.MemberList.get(jid);
       if ( jid==ownerBear) continue;         
       var nick = $(list[i]).attr("employeenick");
       if(gObj==null)
         FaFaChatMain.GetMemberInfo(gid,jid,nick);
       else
       {
         var old_groupid = gObj.groupid;
         if (old_groupid == "0")
           gObj.groupid = gid;
         else
           gObj.groupid = old_groupid +","+gid;
       }
    }
    FaFaChatMain.GroupInfo.get(gid).isload=true;
  });  
};

//获得群组成员相关数据信息
FaFaChatMain.GetMemberInfo = function (gid,jid,nick) {
    FaFaEmployee.Query(jid, function (data) {
       nick = nick==""?data.name:nick;
       var obj = {groupid:gid,name:nick,mobile:data.mobile,email:data.email,img:data.photo};
       FaFaChatMain.MemberList.put(jid,obj);
    });
};

//上线声及消息声音处理
FaFaChatMain.SoundControl = function(src){
  var isIE=browser.ie;
  if(isIE) {
    var html = "<embed src='"+src+"' loop='false' type='audio/mpeg' autostart='true' style='width:0px;height:0px;'></embed>";
    $("#audio").html(html);
  }
  else
     $("#audio").jPlayer("setMedia", {wav:src}).jPlayer("play");
};