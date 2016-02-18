var srcs = [];
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
var WebIM_domain = srcs[0] + "//" + srcs[2];

var MessageUtil = {
    faces: {},
    Get: function () {
        $.getJSON(WebIM_domain+"/api/get/faces?jsoncallback=?", {}, function (data) {
            MessageUtil.faces = eval("(" + data + ")");
        });
    },
    //解析URL
    //判断是否以<div xmlns="http://www.w3.org/1999/xhtml">开头，是则直接去除
    //判断msg中是否有url链接，有则替换成固定样式<a style="padding: 2px; color: #0078B4;" target="_blank" title="http://j.map.baidu.com/RavEu" href="http://j.map.baidu.com/RavEu"><img src="/bundles/fafatimewebase/images/face/../link16.png"> 链接地址</a>
    //判断msg中是否有A标签，如：测试地址<A href="http://www.test.com/BugID=16" on="1">http://www.test.com/BugID=16</A>
    ParseUrl:function(text)
    {
        if (text == null || text=="") return "";
        var STATIC_DIV = '<div xmlns="http://www.w3.org/1999/xhtml">',DIV_LEN = STATIC_DIV.length;
        if(text.length>DIV_LEN && text.substring(0,DIV_LEN)==STATIC_DIV)
        {
            text = text.substring(DIV_LEN);
            text = text.substring(0,text.length-1-6);
        }
        var re = /((?:https?|mailto|ftp):\/\/.*?)(\s|&nbsp;|\'|\"|：|；|，|。|！|>|<)/gi;
        //先处理原有的A标签
        var as = /<a.*?<\/a>/gi;
        text = text.replace(/<a.*?>.*?<\/a>/gi,function(tagA){
            if(!/href/gi.test(tagA))    return tagA;
            var urls = tagA.match(re);
            //判断a标签中的链接地址数，如果只出现了1个，则认为是href属性，需要替换，否则会被后面的http替换为标准显示操作替换丢。
            //如果出现了2个，以标签连接描述为准
            if(urls!=null && urls.length>0)
            {
                  if(urls.length==1) return urls[0].replace(/(\s|&nbsp;|\'|\"|：|；|，|。|！|>|<)$/i," ");
                  return urls[1].replace(/(\s|&nbsp;|\'|\"|：|；|，|。|！|>|<)$/i," ");
            }
            return tagA;
        });
        var has = (text).match(re);
      if (has == null) return text;
      for(var i=0; i<has.length; i++)
      {
          has[i] = has[i].replace(/(\s|&nbsp;|\'|\"|：|；|，|。|！|>|<)$/i, "");
            var href ='<a style="padding:2px;color: #0078B4;" target="_blank" title="'+has[i]+'" href="'+has[i]+'"><img src="https://www.fafaim.com:8443/bundles/fafawebimimchat/images/link16.png"> 链接地址</a>'; 
            text = text.replace(has[i],href);
      }
      return text;
    },
    //解析表情
    ParseFaces: function (text) {
        if (text == null) text = "";
        if (/\/[a-z]{1,5}/g.test(text) == false) return text;
        if (text.length > 7 && text.substring(0, 7) == "FaFaWeb")
            text = text.substring(7);
        var r1 = "";
        text = text.replace(/\/[a-z]{1,5}/g, function ($r) {
            if ((r1 = /\/[a-z]{1,5}/g.exec($r)) != null) {
                var fd = false;
                for (k in MessageUtil.faces) {
                   if ("/" + MessageUtil.faces[k][1] == r1[0]) { fd = true; break; }
                }
                if (fd)
                    return "<SPAN contentEditable='false'><img title='" + MessageUtil.faces[k][0] + "' src='" + WebIM_domain + "/bundles/fafawebimimchat/js/editor/xheditor_emot/default/" + k + ".gif'/></SPAN>";
                else
                    return $r;
            }
            else $r;
        });
        return text;
    },    
    DealFace: function (msg) {
        //<SPAN contentEditable="false">敲打/qd</SPAN><SPAN contentEditable="false">板砖/bz</SPAN>
        //msg.replace(/<img.*?\/>/g,"A")
        if (/\/[a-z]{1,5}/g.test(msg) == false) return msg;
        msg = msg.replace(/<img.*?\/>/g, function ($r) {
            if (/(js\/editor\/xheditor_emot\/default\/\d{1,5}\.gif)/g.test($r)) {
                var r1 = /\d{1,5}\.gif/g.exec($r);
                var ind = r1[0].split(".")[0];
                return "/" + MessageUtil.faces[ind][1];
            }
            else $r;
        });
        return msg;
    }
};

LoadCSS.load(WebIM_domain + "/bundles/fafawebimimchat/css/FaFaChatWindow.css");
LoadCSS.load(WebIM_domain + "/bundles/fafawebimimchat/css/chatmsg.css");
LoadJs.load(WebIM_domain + "/bundles/fafawebimimchat/js/editor/xheditor-1.1.13-zh-cn.min.js");
LoadJs.load(WebIM_domain + "/bundles/fafawebimimchat/js/imgZoom.js");

var FaFaChatFileUpload = { sendingId: "" ,sendToJID:""};
//需要上传文件功能时，应把fafa_webim_file_upload.html文件放在web应用的根目录下。且不能修改该文件的代码
FaFaChatFileUpload.Init = function () {
    var callUrl = window.location.protocol + "//" + window.location.host + (window.location.port == "" ? "" : ":" + window.location.host)+"/fafa_webim_file_upload.html";
    var action = WebIM_domain + "/api/webim/file?c=" + callUrl;
    var file = $('<div><form id="webim_upload_file" name="webim_upload_file" action=' + action + ' target="fafachatfileuploadfrm" method="post" enctype="multipart/form-data"><label for="xheImgUrl">本地文件: </label><span class="xheUpload"><input type="text" tabindex="-1" style="visibility:hidden;"><input type="text" class="xheText" readonly="" value="" id="xheImgUrl"><input type="button" tabindex="-1" class="xheBtn" value="选择"><input type="file" tabindex="-1" name="fafa_webim_filedata" id="fafa_webim_filedata" onchange="FaFaChatFileUpload.Request()" size="13" class="xheFile" multiple=""></span></form><IFRAME id="fafachatfileuploadfrm" name="fafachatfileuploadfrm" src="about:blank" frameborder="0" height="0" width="0"></IFRAME></div>');
    return file;
};

FaFaChatFileUpload.AsynResult = function (r) {
    var fn = FaFaChatFileUpload.sendingId;
    var html = "";
    try {
        var obj = eval("(" + r[0].replace(/%22/g, "\"") + ")");
        fn = obj.fileid;
        if (obj.msg == "1") {
                      //判断当前发送的是否手机客户端
                      if(FaFaChatFileUpload.sendToJID.indexOf("FaFaAndroid")>-1 || FaFaChatFileUpload.sendToJID.indexOf("FaFaIphone")>-1)
                    {
                          //发送离线文件通知
                          FaFaMessage.SendOfflineFileRequest(FaFaChatWin.owner.GetJid(), FaFaChatFileUpload.sendToJID, obj.oldfile, fn,FaFaChatWin.owner.name);
                    }
            else
            {
                    FaFaMessage.SendFileConfirm(FaFaChatWin.owner.GetJid(), FaFaChatFileUpload.sendToJID, obj.oldfile, obj.newfile, fn);
            }
            html = "文件已发送";
        }
        else {
            html = "文件发送失败";
        }
    } catch (e) {
        html = "文件发送失败";
    }
    FaFaChatFileUpload.changeFileStatus(fn, FaFaChatFileUpload.sendToJID, html, true);
    FaFaChatWin.isSendFile = false;
    $("#xhe0_Tool [cmd='sendFile']").attr("title", "文件发送");
    FaFaChatFileUpload.sendingId = "";
    FaFaChatFileUpload.sendToJID = "";
};

FaFaChatFileUpload.Request = function () {
    if (FaFaChatWin.active.state != "online") {
        FaFaChatWin.Hint("好友已离线，不能给对方发送文件！", 10000);
        return;
    }
    var fns = $("#fafa_webim_filedata").val().split("\\");
    fn = fns.pop();
    var fileId = FaFaChatWin.active.jid.replace(/[_@\.-]/g,"")+hashCode( fn + (new Date()));
    if (FaFaChatWin.isSendFile || document.getElementById(fileId) != null) {
        FaFaChatWin.Hint("已有文件正在发送中，请等待对方返回或立即取消发送...", 10000);
        return;
    }
    $("#xhe0_Tool [cmd='sendFile']").attr("title", "文件正在发送中...");
    FaFaChatFileUpload.sendToJID = FaFaChatWin.active.GetJid();
    //var sz=document.getElementById("fafa_webim_filedata").files[0].size;  
    FaFaChatWin.msginputCtl.loadBookmark();
    FaFaChatWin.WriteMsg(FaFaChatWin.active.jid, "<div contentEditable=\"false\" id='" + fileId + "'><span>[" + fn + "]&nbsp;&nbsp;</span><a href=\"javascript:FaFaChatFileUpload.Cancel('" + fileId + "')\">取消发送</a></div>", FaFaChatWin.active.resource);
    FaFaChatWin.msginputCtl.hidePanel();
    //判断 是否是向手机端发送，是则直接上传离线文件
    if(FaFaChatFileUpload.sendToJID.indexOf("FaFaAndroid")>-1 || FaFaChatFileUpload.sendToJID.indexOf("FaFaIphone")>-1)
    {
         this.Upload(fileId);
    }
    else
    {
       //发送一个请求iq
       FaFaMessage.SendFileRequest(FaFaChatWin.owner.GetJid("FaFaWeb"), FaFaChatFileUpload.sendToJID, fn, fileId, FaFaChatWin.owner.name == "" ? null : FaFaChatWin.owner.name);
    }
    FaFaChatWin.isSendFile = true;
};
FaFaChatFileUpload.Upload = function (fn) {
    FaFaChatFileUpload.sendingId = fn;
    $("#webim_upload_file")[0].action = $("#webim_upload_file")[0].action+"&fileid="+fn;
    $("#webim_upload_file").submit();    
};
FaFaChatFileUpload.changeFileStatus = function (fID, jid, desc,clearID) {
    var f = $("#"+fID)[0];
    if(f==null) return;
    if (clearID != null && clearID == true) f.id = "";
    f.innerHTML = (f.children[0].tagName!="IMG" ? f.children[0].outerHTML:"[图片]") + "<span>" + desc + "</span>";
    //处理历史记录
    var _rosterHisList = FaFaChatWin.msghis.get(jid);
    if (_rosterHisList != null) {
        for (var i = 0; i < _rosterHisList.length; i++) {
            if (_rosterHisList[i].indexOf("id='" + fID + "'") > -1 || _rosterHisList[i].indexOf("id=\"" + fID + "\"") > -1|| _rosterHisList[i].indexOf("id=" + fID) > -1) {
                _rosterHisList[i] = "<div>" + f.innerHTML + "</div>";
                break;
            }
        }
    }
};

FaFaChatFileUpload.Cancel = function (fn) {
    //发送文件取消iq    
    FaFaMessage.CancelSendFileRequest(FaFaChatWin.owner.GetJid("FaFaWeb"), FaFaChatWin.active.GetJid(), fn);
    FaFaChatWin.isSendFile = false;
    $("#xhe0_Tool [cmd='sendFile']").attr("title", "文件发送");
    FaFaChatFileUpload.sendingId = "";
    FaFaChatFileUpload.changeFileStatus(fn, FaFaChatFileUpload.sendToJID, "已取消", true);
};

FaFaChatFileUpload.Reject = function (fn, fileid) {
    //发送文件拒绝IQ
    var resource = $("#" + fileid + " a").attr("resource");    
    FaFaMessage.RejectFile(FaFaChatWin.owner.GetJid("FaFaWeb"), FaFaChatWin.active.GetJid(resource), fn, fileid);    
    FaFaChatFileUpload.changeFileStatus(fileid, FaFaChatWin.active.jid, "&nbsp;&nbsp;&nbsp;&nbsp;您已拒绝接收文件",true);    
};
//需要准确获取当前发送文件好友的resource。因为好友可能登录了多个设备
FaFaChatFileUpload.Accept = function (fn, fnID, ftype, _jid) {
    //获取发送文件的resource
    var resource = $("#" + fnID + " a").attr("resource");
    FaFaChatFileUpload.changeFileStatus(fnID, _jid, "&nbsp;&nbsp;&nbsp;&nbsp;<a id='" + fnID + "'>文件正在传送...</a>");
    //如果当前发送文件是的web客户端，则发送同意iq,其他客户端，则直接从服务器上获取文件
    if (resource != "FaFaWeb") {
        FaFaChatFileUpload.Receive(fn, fnID, ftype, FaFaChatWin.active.jid);
        return;
    }
    FaFaMessage.AcceptFile(FaFaChatWin.owner.GetJid("FaFaWeb"), FaFaChatWin.active.jid + "/" + resource, fn, fnID);
},

FaFaChatFileUpload.ShowFile = function (jid, fileid, path, attr) {
    var fileCtl = $("#" + fileid);
    var t =(attr==null||attr.name==null)?" ": attr.name.split(".").pop();
    var imgHtml = "";
    if ("jpg,jpeg,bmp,gif,png".indexOf(t.toLocaleLowerCase()) > -1) {
          imgReady(path,function(){  //自动缩小
                w= this.width ;
                        h= this.height;
                var img_w = w;
                if (img_w != null && img_w > 400) {
                    var sacle = 400 / img_w;
                    var img_h = sacle * h;
                    imgHtml = "<a target=_blank href='" + path + "' title='点击查看原图'><img width=400 height=" + img_h + " src=\"" + path + "\"/></a>";
                }
                else if (img_w != null)
                    imgHtml = "<img width=" + img_w + " height=" + h + " src=\"" + path + "\"/>";
                else
                    imgHtml = "<img src=\"" + path + "\"/>";
                if (fileCtl.length > 0) {
                    if (fileCtl[0].tagName == "SPAN")
                        fileCtl.parent().html(imgHtml);
                    else
                        fileCtl.html(imgHtml);
                }
                FaFaChatWin.GetFileSucceedAfter(jid, fileid,imgHtml);
          });
        //imgHtml
    }
    else if ( t.toLocaleLowerCase().indexOf("amr")>-1) {
       imgHtml = "<div class='webim_stop_aduio' title='单击播放'><span style='display:none;'>" + path + "</span></div>";
       if (fileCtl.length > 0) {
           if (fileCtl[0].tagName == "SPAN")
               fileCtl.parent().html(imgHtml);
           else
                fileCtl.html(imgHtml);
       }
       $(".webim_stop_aduio").live("click",function(){
           $(this).attr("class","webim_play_aduio");
           $(this).attr("title","单击停止");
       });
       
       $(".webim_play_aduio").live("click",function(){
           $(this).attr("class","webim_stop_aduio");
           $(this).attr("title","单击播放");
       });
    }
    else {
        imgHtml = "&nbsp;&nbsp;&nbsp;&nbsp;<a target=_blank href='" + path + "'>打开</a>&nbsp;&nbsp;<a target=_blank href='" + path.replace("getfile","downloadfile") + "'>另存为</a>";
        fileCtl.find("span:last").html(imgHtml);
        imgHtml = fileCtl.html();
    }
    fileCtl.attr("id", "");
    setTimeout("FaFaChatWin.SetScroll()", 200);
    //处理历史记录
    FaFaChatWin.GetFileSucceedAfter(jid, fileid,imgHtml);
};

FaFaMain_PlayAudio = function(src,type){
  
};

FaFaChatFileUpload.Receive = function (fn, fileid, t, jid) {
    FaFaMessage.GetFileHttpPath(FaFaChatWin.owner.GetJid(), fn, fileid, function (data) {
        //var rIQ = $(iq);
        if (data.path !="") {
            var filehashvalue = data.fileid;
            FaFaChatFileUpload.ShowFile(jid, filehashvalue, (data.msg != null && data.msg != "") ? data.msg : data.path, data);
            return;
        }
        FaFaChatFileUpload.changeFileStatus(filehashvalue, jid, "已删除");
    });
};

FaFaChatFileUpload.RequestReceive = function (nick, _jid, fileName, fileId, senddate) {
    var jidObj = Jid.Parse(_jid);
    var _jid_bear = jidObj.Bear();
    //判断是否是图片
    var ts = fileName.split(".");
    var fixed = "," + ts[ts.length - 1] + ",";
    var msg = "<div><span style='color:red'><span class='webim_jidtoname'>" + (nick == null ? _jid_bear : nick) + "</span> " + senddate + "</span></div>";
    FaFaChatWin.WriteMsg(_jid_bear, msg, jidObj.resource);
    if (",jpg,jpeg,bmp,gif,png,".indexOf(fixed.toLocaleLowerCase()) > -1) {
        FaFaChatWin.WriteMsg(_jid_bear, "<div id='" + fileId + "'><img src=\"" + WebIM_domain + "/bundles/fafawebimimchat/images/load.gif\"/></div>", jidObj.resource);
        if (jidObj.resource == "FaFaWeb") FaFaMessage.AcceptFile(FaFaChatWin.owner.GetJid(), _jid, fileName, fileId);
        else
        //自动获取图片地址
            FaFaChatFileUpload.Receive(fileName, fileId, "img", _jid_bear);
    }
    else {
      FaFaChatWin.file_id = fileId;
      FaFaChatWin.WriteMsg(_jid_bear, "<div id='" + fileId + "'><span>文件: " + fileName + "&nbsp;&nbsp;&nbsp;&nbsp;</span><span><a resource='" + jidObj.resource + "' style='color:blue;' href=\"javascript:FaFaChatFileUpload.Accept('" + fileName + "','" + fileId + "','file','" + _jid_bear + "')\">接收</a>&nbsp;&nbsp;<a id='fileid"+fileId+"' style='color:blue;' href=\"javascript:FaFaChatFileUpload.Reject('" + fileName + "','" + fileId + "')\">拒绝</a></span></div>", jidObj.resource);
    }
};
//////////////////////////////////////////////////////////////////////////////////
var FaFaChatWin = { w: 500, h: 400, active: null, owner: null,ownerOpenid:'', toList: new HashMap(), msghis: new HashMap(), obj: null, msglogCtl: null, msginputCtl: null, allJid: new HashMap(), isSendFile: false,groupName:new HashMap(),tag_Name:new HashMap(),chatStyle:null,file_id:''};
var ChatMessage = {chatMsgId:0,currentPageIndex:1,everyPage:50,searchJid:"",searchDate:"",searchText:"",searchType:0,currentNick:null};

FaFaChatWin.Connection = function (FaFa, P, ConnMode) //ConnMode连接模式。显示模式和安静模式（3：不会出现连接提示）
{
    if (ConnMode == null || ConnMode != "3")
        this.load("正在连接服务器,请稍候...<br>如果长时间无反应，您可尝试&nbsp;&nbsp;<a href='javascript:window.location.reload()'>刷新</a>(F5)该页面");
    //$.ligerDialog.waitting(""); 
    FaFaMessage.Connection(FaFa, P);
}
FaFaChatWin.load = function (desc) {
    if (this.obj == null) return;
    this.obj.find(".webim_modal-body").html("");
    this.obj.find(".webim_modal-body").append("<div class='webim_urlloading'></div><div class='webim_descloading'>" + desc + "</div>");
};

FaFaChatWin.Error = function (error) {
    if (this.obj == null) return;
    this.obj.find(".webim_modal-body").html("");
    this.obj.find(".webim_modal-body").append("<div class='webim_errorhint'></div><div class='webim_descloading'>" + error + "</div>");
};
FaFaChatWin.Hint = function (msg, tlong) {
    $(".webim_fafa_chat_hint span").html(msg);
    $(".webim_fafa_chat_hint").show();
    if (tlong != null && tlong > 0) setTimeout('$(".webim_fafa_chat_hint").hide()', tlong);
};

FaFaChatWin.GetFileSucceedAfter=function(jid,fileid,imgHtml)
{
    if(imgHtml!=null && imgHtml!="")
    {
            var _rosterHisList = FaFaChatWin.msghis.get(jid);
            if (_rosterHisList != null) {
                for (var i = 0; i < _rosterHisList.length; i++) {
                    if (_rosterHisList[i].indexOf("id='" + fileid + "'") > -1 || _rosterHisList[i].indexOf("id=\"" + fileid + "\"") > -1 || _rosterHisList[i].indexOf("id=" + fileid ) > -1) {
                        _rosterHisList[i] = "<div>" + imgHtml + "</div>";
                        break;
                    }
                }
            }
            else
            {
                  //为了保证文件/图片能被处理，遍历所有日志 
                  var isFound = false;
                  for(var k in FaFaChatWin.msghis.keySet())
                  {
                          if (k != "indexOf" && k!="toJSON" && FaFaChatWin.msghis.hashTable[k] != undefined)
                          {
                              var _rosterHisList = FaFaChatWin.msghis.get(k);
                                            if (_rosterHisList != null) {
                                                for (var i = 0; i < _rosterHisList.length; i++) {
                                                    if (_rosterHisList[i].indexOf("id='" + fileid + "'") > -1 || _rosterHisList[i].indexOf("id=\"" + fileid + "\"") > -1 || _rosterHisList[i].indexOf("id=" + fileid ) > -1) {
                                                        _rosterHisList[i] = "<div>" + imgHtml + "</div>";
                                                        isFound=true;
                                                        break;
                                                    }
                                                }
                                            }
                          }
                          if(isFound) break;
                  }
            }
    }   
}

//提示有新消息到达
FaFaChatWin.hintJid = function (jid) {
    var nc = this.obj.find(".webim_modal-header .webim_nickname");
    var rosters = nc.find("a[jid='" + jid + "']");
    var r = this.allJid.get(jid);
    if (r == null) return;
    this.toList.put(jid, r);
    var nickname="";
    if ( FaFaChatWin.groupName.get(jid)!=null)
       nickname = FaFaChatWin.groupName.get(jid);
    else
       nickname = r.name;
    if (rosters.length == 0) {
        var newNick = "";
        var _jid = r.jid.replace(/@|\.|\//g,'');
        if(FaFaChatWin.groupName.get(jid)!=null)
          newNick = "<span class='webim_hint' ><span class='webim_group_online' id='" + _jid + "_webim_state' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><a jid=" + r.jid + " >" + nickname + "</a></span>";
        else
          newNick = "<span class='webim_hint' ><span class='webim_state_offline' id='" + _jid + "_webim_state' >&nbsp;&nbsp;&nbsp;&nbsp;</span><a jid=" + r.jid + " >" + nickname + "</a></span>";
        nc.append(newNick);
        rosters = nc.find("a[jid='" + jid + "']");
    }
    else {
        rosters.text(nickname);
        rosters.parent().attr("class", "webim_hint");        
    }
    if ( FaFaChatWin.groupName.get(jid)==null)
      FaFaChatWin.updateState(jid);
    if (FaFaChatWin.active != null && FaFaChatWin.active.jid == jid) 
       rosters.parent().attr("class", "webim_active");
    
};

FaFaChatWin.AddRoster = function (_jid, _rosterobj, isreplace) {
    var jid = (arguments.length == 1) ? _jid.jid : _jid;
    var rosterobj = (arguments.length == 1) ? _jid : _rosterobj;
    var tmpRoster = this.allJid.get(jid);
    this.toList.put(jid, tmpRoster);
    if (tmpRoster != null && tmpRoster.isLoad && (isreplace == null || !isreplace)) return; //已缓存jid的信息，并且未要求强制替换    
    if (!rosterobj.isLoad && tmpRoster != null && (isreplace == null || !isreplace)) return;
    if ( FaFaChatWin.groupName.get(jid)!=null)
    {
       rosterobj.name = $("#"+jid.split('@')[0]).text().replace(/\s/g,'');
    }
    this.allJid.put(jid, rosterobj);
};
FaFaChatWin.SetScroll = function () {
    if (FaFaChatWin.msglogCtl == null || FaFaChatWin.msglogCtl.length == 0) return;
    FaFaChatWin.msglogCtl[0].scrollTop = 100000;
};

FaFaChatWin.GetRoster = function (jid, resource) {
    var tmpRoster = this.allJid.get(jid);
    if (jid.indexOf("guest@") > -1) return; 
    if (tmpRoster != null && tmpRoster.isLoad) {
        FaFaChatWin.AddRoster(jid, this.allJid.get(jid));
        return;
    }    
    if (FaFaChatWin.groupName.get(jid)!=null){
         FaFaEmployee.QueryGroupInfo(jid.split("@")[0],function(groupinfo){
            if(groupinfo==null) return;
            var tmpRoster = FaFaChatWin.allJid.get(groupinfo.jid);
            tmpRoster.isLoad = true;
            if(groupinfo.logo!="") tmpRoster.photo= groupinfo.logo;
            if(groupinfo.groupdesc!=null && groupinfo.groupdesc!="")tmpRoster.desc = groupinfo.groupdesc;
            if(groupinfo.groupname!=null && groupinfo.groupname!="")tmpRoster.name = groupinfo.groupname;
            tmpRoster.post = groupinfo.grouppost;
            if(groupinfo.groupclass=="meeting" ||groupinfo.groupclass=="discussgroup")tmpRoster.desc = groupinfo.groupdesc;
            tmpRoster.groupclass = groupinfo.groupclass;
            FaFaChatWin.AddRoster(groupinfo.jid, tmpRoster);
          $(".webim_modal-header .webim_nickname a[jid='" + groupinfo.jid + "']").text(tmpRoster.name);
          $(".webim_modal-header .webim_desc").text(tmpRoster.desc);
          $(".webim_fafa_chat_photo img").attr("src", tmpRoster.photo);
          $(".webim_group_photo img").attr("src", tmpRoster.photo);         
         });
         return;
    }
    
    FaFaEmployee.Query(jid, function (rosterInfo) {
        var tmpRoster = FaFaChatWin.allJid.get(jid);
        rosterInfo.jid = jid;
        rosterInfo.resource = tmpRoster != null ? tmpRoster.resource : (typeof (resource) == "string" ? [resource] : resource);
        rosterInfo.isLoad = true;
        rosterInfo.state = tmpRoster == null ? "online" : tmpRoster.state;
        FaFaChatWin.AddRoster(rosterInfo.jid, rosterInfo);
        if (FaFaChatWin.active && FaFaChatWin.active.jid == jid) {
            FaFaChatWin.active = rosterInfo;
        }
        $(".webim_modal-header .webim_nickname a[jid='" + jid + "']").text(rosterInfo.name);
        $(".webim_modal-header .webim_desc").text(rosterInfo.desc);
        $(".webim_fafa_chat_photo img").attr("src", rosterInfo.photo);
        //如果有未获取到姓名时的记录，替换其中的jid
        if (FaFaChatWin.msglogCtl != null && FaFaChatWin.msglogCtl.length>0) {
            var webim_jidtonames = FaFaChatWin.msglogCtl.find(".webim_jidtoname");
            if (webim_jidtonames.length > 0) { webim_jidtonames.removeAttr("class"); webim_jidtonames.text(rosterInfo.name) };
        }
    });
};

FaFaChatWin.GetGroup=function (groupJid)
{
      
} 

FaFaChatWin.WriteMsg = function (_jid, msg, resource) {
    var jid = _jid == null ? FaFaChatWin.active.jid : _jid;
    var msgHis = FaFaChatWin.msghis.get(jid);
    if (msgHis == null) {
        FaFaChatWin.msghis.put(jid, [msg]);
    }
    else {
        msgHis.push(msg);
        FaFaChatWin.msghis.put(msgHis);
    }
    if (resource != null && Jid.Parse(jid).user!="guest") FaFaChatWin.GetRoster(jid, resource);
    if ((FaFaChatWin.active && jid == FaFaChatWin.active.jid) || jid == null) {
        if (resource != null) FaFaChatWin.active.addResource(resource);
        FaFaChatWin.msglogCtl.append(msg); setTimeout("FaFaChatWin.SetScroll()", 200);
    }
    else
       FaFaChatWin.hintJid(jid);
};

FaFaChatWin.ShowRoster = function (jid, positionXY) {
    if (typeof(jid)=="undefined" || jid=="") return;
    var isGroup = false;
    if (jid.indexOf("gid") > -1) {
          isGroup = true;
        var _jid = jid.replace(/gid/g, "");
        jid = Jid.Parse(_jid).Bear();
        if (FaFaChatWin.groupName.get(jid) == null || FaFaChatWin.groupName.get(jid)=="")
            FaFaChatWin.groupName.put(jid, $("#" + _jid).text().replace(/\s/g, ""));
    }
    if (this.obj == null) {
        this.init();
    }
    if (this.obj == null || (FaFaChatWin.active && FaFaChatWin.active.jid == jid)) return;
    //判断联系人是否已存在
    var _exist = $(".webim_modal-header .webim_nickname a[jid='" + jid + "']");
    if(_exist.length>0)
    {
          //设置为活动联系人
         _exist.parent().attr("class", "webim_active");
         if (typeof(FaFaChatMain)=="undefined")
            return;
    }
    
    if (positionXY != null) {
        if (positionXY.left != null) this.obj.css("left", positionXY.left);
        if (positionXY.top != null) this.obj.css("top", positionXY.top);
        if (positionXY.marginLeft != null) this.obj.css("marginLeft", (this.obj.width() * -1 + positionXY.marginLeft) + "px");
        if (positionXY.marginTop != null) this.obj.css("marginTop", positionXY.marginTop + "px");
    }
    this.obj.modal("show");
    var objJid = Jid.Parse(jid);
    var bear = objJid.user == "guest" ? objJid.Bear() + "/" + objJid.resource : objJid.Bear();
    var data = this.allJid.get(bear);
    if (data == null) {
        data = new roster();
        data.jid = bear;
        if (FaFaChatWin.groupName.get(jid) != null) {
            data.name = $("#" + jid.split('@')[0]).text().replace(/\s/g, "")
        }
        data.resource = [objJid.resource];
        this.allJid.put(bear, data);
    }
    this.obj.find(".webim_modal-header .webim_desc").text(data.desc == null ? "" : data.desc);
    this.obj.find(".webim_modal-header .webim_fafa_chat_photo img").attr("src", data.photo);
    var body = this.obj.find(".webim_modal-body");
    if (this.obj.find(".webim_fafa_chat_input").length == 0) {
        body.html("");
        var inputHTML = "<div class='webim_fafa_chat_hint'><img src='" + WebIM_domain + "/bundles/fafawebimimchat/images/icon_point.png'><span></span><a>×</a></div><div class='webim_fafa_chat_history'></div><div id='textarea_content'><textarea class='webim_fafa_chat_input'></textarea>"+
                        "<div class=\"webim_modal-footer\"><A class='webim_btn webim_send' id='fafa_chat_window_send'><span>发送</span></A><A class='webim_btn webim_close' id='fafa_chat_window_close'><span>关闭</span></A></div></div>";
        body.append(inputHTML);
        var h = body.height() - 190;
        body.find(".webim_fafa_chat_history").css({ "height": h + "px" });
        
        var Plugin = {
                sendFile: { c: 'webim_sendFile', t: '文件传送', h: 1, e: function () {
                    var _this = this;
                    if (FaFaChatWin.isSendFile) return;
                    var jTest = FaFaChatFileUpload.Init();
                    _this.saveBookmark();
                    _this.showDialog(jTest);
                }
                },
                chatMsg:{c:'webim_tool_chatmsg',t:'聊天记录',h:0,e:function(){
                    FaFaChatWin.ShowChatMsg();
                  }
                }
            };
        var itemEmots = {'default':{name:'MSN',width:20,height:20,line:15,list:
             {'1':'你好','2':'再见','3':'微笑','4':'大笑','5':'偷笑','6':'憨笑','7':'坏笑','8':'抓狂','9':'晕','10':'冷汗',
              '11':'嘘','12':'疑问','13':'委屈','14':'调皮','15':'色','16':'糗大了','17':'亲亲','18':'难过','19':'流泪','20':'困',
              '21':'可爱','22':'可怜','23':'惊讶','24':'惊恐','25':'哈欠','26':'发呆','27':'害羞','28':'尴尬','29':'大哭','30':'出汗',
              '31':'得意','32':'示爱','33':'发怒','34':'鄙视','35':'闭嘴','36':'酷','37':'傲慢','38':'白眼','39':'擦汗','40':'睡',
              '41':'OK','42':'NO','43':'强','44':'弱','45':'差劲','46':'鼓掌','47':'胜利','48':'握手','49':'拳头','50':'沟引',
              '51':'拥抱','52':'抱拳','53':'拜托','54':'敬礼','55':'敲打','56':'猪头','57':'吐','58':'礼物','59':'饭','60':'饥饿'
             }}};
        this.msginputCtl = $('.webim_fafa_chat_input').xheditor({ plugins:Plugin, emots:itemEmots , width: '100%', height: '120px', skin: "o2007silver", forcePtag: false, tools: 'Emot,|,Fontface,FontSize,FontColor,|,sendFile,|,chatMsg', sourceMode: false });
        $(this.msginputCtl.doc.documentElement).bind("keydown", function (e) {
            if (e.which === 13) {
                if (!e.ctrlKey) {
                    FaFaChatWin.send();
                    return false;
                }
            }
        });
        this.msglogCtl = body.find(".webim_fafa_chat_history");     
        $(".xheBtnAbout").parent().parent().remove();
    }
    else {
        this.msglogCtl.html("");
    }
    if ( isGroup)
       $(".webim_sendFile").hide();
    else
         $(".webim_sendFile").show();
         
    var msgHis = this.msghis.get(jid);
    if (msgHis != null) {
        var html = msgHis.join("");
        //html = html.replace(/&lt;/g,"<").replace(/&gt;/g,">");
        //html = FaFaMessage.fomratHTML(html);
        this.msglogCtl.html(html);
        setTimeout("FaFaChatWin.SetScroll()", 200);
    }
    this.obj.find(".webim_modal-header .webim_nickname .webim_active").attr("class", "webim_none");
    this.obj.find(".webim_modal-header .webim_nickname a[jid='" + data.jid + "']").parent().attr("class", "webim_active");
    if (isGroup)
    {
        var g_photo = FaFaChatMain.GroupInfo.get(jid.split("@")[0]).logo;       
      this.obj.find(".webim_modal-header .webim_fafa_chat_photo img").attr("src", g_photo);
    }
    else
        this.obj.find(".webim_modal-header .webim_fafa_chat_photo img").attr("src", data.photo);
    
    FaFaChatWin.active = data;
    FaFaChatWin.hintJid(data.jid);
    FaFaChatWin.GetRoster(data.jid, data.resource);        
    var _cardDiv = "";
    if (FaFaChatWin.groupName.get(jid) == null) {
        FaFaChatWin.updateState(data.jid);
        cardDiv = $("#fafa_chat_employee_card");
        if (_cardDiv.length > 0 && _cardDiv.css("display") != null && _cardDiv.css("display") != "none")
            FaFaChatWin.ShowCard();
    }
    else {
        _cardDiv = $("#fafa_chat_group_card");
        if (_cardDiv.length > 0 && _cardDiv.css("display") != null && _cardDiv.css("display") != "none")
            FaFaChatWin.ShowGroupCard(jid.split('@')[0]);
    }
        
    if (FaFaChatWin.chatStyle!=null && FaFaChatWin.chatStyle!="") 
       FaFaChatWin.TextStyle(FaFaChatWin.chatStyle);
    else
       FaFaChatWin.TextStyle("font-family:Microsoft YaHei,font-size:14px");
    if(objJid.user!="admin" && $(".webim_modal-header .webim_nickname .webim_active span").attr("class")=="webim_state_offline")
       $("#rosterHeader img").css("opacity","0.1");
    else
         $("#rosterHeader img").css("opacity","1");
    this.msginputCtl.focus();
};

//刷新指定联系人的当前状态
FaFaChatWin.updateState = function (jid) {    
    var stateIcon = $("#" + jid.replace(/@|\.|\//g, "") + "_webim_state");
    if (stateIcon.length > 0) {
        var kl = FaFaChatWin.allJid.get(jid);
        var st = kl.state;
        if (kl.show != "") {
            st = kl.show;
            if (kl.show == "dnd") {
                if (kl.showDesc == "会议中") st = "meeting";
                else if (kl.showDesc == "请勿打扰") st = "ddt";
                else st = "busy";
            }
        }
        stateIcon.attr("class", "webim_state_" + st);
    } 
};

//刷新群成员当前状态(在群成员面板打开时)
FaFaChatWin.updateGroupMemberState = function (jid){
   //判断是群名片是否打开
   //if ( $("#fafa_chat_group_card").length>0 ) //&& $("#fafa_chat_group_card").attr("display")!="none")
   //{
      stateIcon = $("#group_jid_" + jid.replace(/@|\./g, '_'));
      if (stateIcon.length > 0)
      {
        var rosterStatus = FaFaChatWin.allJid.get(jid);
        var state = rosterStatus.state;
        var stateClassName="";
        var hint ="",html="";
        if (state=="online")
        {
          if(rosterStatus.show=="away" || rosterStatus.show=="xa")
          {
             hint="离开";
             stateClassName = "webim_chat_roster_header_leave";
          }
          else if (rosterStatus.show=="dnd")
          {
            if ( rosterStatus.showDesc == "请勿打扰")
            {
              hint="请勿打扰";
              stateClassName = "webim_chat_roster_header_disturb";
            }
            else
            {
              hint="忙碌";
              stateClassName = "webim_chat_roster_header_busy";
            }
          }
          else
             stateClassName = "webim_chat_roster_header_online";
          stateIcon.attr("class",stateClassName);
          stateIcon.parent().attr("title",hint);
          html = stateIcon.parent()[0].outerHTML;
          stateIcon.parent().remove();
          $(html).insertAfter($("#groupmember>li:first"));
        }
        else
        {
          hint="离线";
          stateClassName = "webim_chat_roster_header_offline";          
          stateIcon.attr("class",stateClassName);
          stateIcon.parent().attr("title",hint);
          html = stateIcon.parent()[0].outerHTML;
          stateIcon.parent().remove();
          $("#groupmember").append(html);
        }        
        var off = $("#groupmember .webim_chat_roster_header_offline").length;
        var total = $("#memberScalar").text().split('/')[1].replace(/\s/g,'');
        $("#memberScalar").text(" "+(total - off)+" / "+ total +" ");
      }
   //}
}

FaFaChatWin.init = function (ownerjid) {
    if (typeof ($.fn.ajaxSubmit) == "undefined") {
        LoadJs.load(WebIM_domain+"/bundles/fafatimewebase/js/jquery.form.js");
    }
    if (typeof (FaFaMessage) == "undefined" || typeof ($.fn.modal) == "undefined") {
        if(typeof ($.fn.modal) == "undefined")
            LoadJs.load(WebIM_domain+"/bundles/fafatimewebase/js/bootstrap.js");
        setTimeout("FaFaChatWin.init('"+ownerjid+"')", 200); //等待200毫秒后偿试再次初始化
        return;
    }
    if(FaFaChatWin.owner!=null) return;//已经初始化或正在初始化时，不执行后续的初始化调用
    this.owner = new roster();
    
    MessageUtil.Get(); //获取表情
    if (ownerjid != null) {
        FaFaChatWin.owner.jid = ownerjid;
    }
    else FaFaChatWin.owner.jid = FaFaMessage._jid;
    var _cx = $("#fafa_chat_window");
    if (_cx.length == 0) {
        _cx = document.createElement("DIV");
        _cx.id = "fafa_chat_window";
        _cx.className = "webim_chat_modal";
        with (_cx.style) {
            width = this.w + "px";
            height = this.h + "px";
            display = "none";
        }
        _cx.innerHTML = "<div class=\"webim_modal-header\">" +
                          "<a class=\"webim_close\" style='line-height:12px;font-size:16px'>×</a><div id='rosterHeader' class='webim_fafa_chat_photo'><img src='" + WebIM_domain + "/bundles/fafawebimimchat/images/nophoto.jpg'></div><div class='webim_nickname'></div><div class='webim_desc'></div></div>" +
                          "<div class=\"webim_modal-body\"></div>";
        document.body.appendChild(_cx);
        this.obj = $("#fafa_chat_window");
        this.obj.modal({ show: false, backdrop: false });
        this.obj.find(".webim_modal-header .webim_nickname span").live("click", function (){
          if(FaFaChatWin.active!=null && FaFaChatWin.active.jid!=""){
            var jid = $(this).find("a").attr("jid"),_jid="";
            if(typeof(FaFaChatMain)!="undefined") FaFaChatMain.Cancel_Select_Style(FaFaChatWin.active.jid,true);
            _jid = FaFaChatWin.isGroup(jid)?jid.split("@")[0]:jid.replace(/@|\./g, '_');
            if($("#"+_jid).length==1)
              $("#"+_jid).css("background-color","PaleGoldenrod");
          }
          FaFaChatWin.ShowRoster(jid);
        });
        this.obj.find(".webim_modal-header .webim_close").live("click", function () {
           FaFaChatWin.close();
        });
           
        this.obj.find(".webim_modal-header .webim_fafa_chat_photo").live("mouseover", function (){
           if (FaFaChatWin.groupName.get(FaFaChatWin.active.jid) == null)
             $(".webim_modal-header #rosterHeader").attr("title","点击查看成员资料");
           else
             $(".webim_modal-header #rosterHeader").attr("title","点击查看群组资料");
        });                
        this.obj.find(".webim_modal-header .webim_fafa_chat_photo").live("click", function (){
           var jid = FaFaChatWin.active.jid;
           if (FaFaChatWin.groupName.get(jid) == null)
           {
             if($("#fafa_chat_group_card").length>0)
               $("#fafa_chat_group_card").hide();
             FaFaChatWin.ShowCard();
           }
           else
           {
             if($("#fafa_chat_employee_card").length>0)
               $("#fafa_chat_employee_card").hide();
             FaFaChatWin.ShowGroupCard(jid.split('@')[0]);
           }
        });
        this.obj.find("#fafa_chat_window_close").live("click", function () { FaFaChatWin.close() });
        this.obj.find("#fafa_chat_window_send").live("click", function () { FaFaChatWin.send() });
        this.obj.find(".webim_fafa_chat_hint a").live("click", function () { $(this).parent().hide() });
        this.obj.find(".webim_modal-body").css({ "height": this.h-50});
    }    
    //开始注册事件处理程序。要在加载fachat_window.js之前加载faapi.js文件
    FaFaMessage.GetPresence(function (pre) {
        if (pre.From=="") return;
        var rawrequest=$(pre.Body).find("rawrequest");
        if (rawrequest.length>0)
          {
               //FIX:好像登录前，已在线的设备不会好送出席过来
               //判断是否只有web在线，是则发送不支持提示
               //if(typeof(FaFaChatMain)!="undefined") return;
               //var _soc=FaFaChatWin.owner.resource;
               //if(_soc==null || _soc.length>1) return;
               //var _to =rawrequest.attr("groupid");
               //FaFaMessage.SendGroupMessage(pre.To,_to,"<span style='color:#999'>【当前正在使用WebIM，不支持语音或视频。】</span>",'');
               return;            
          }        
        var _jid = Jid.Parse(pre.From);
        var _jid_bear = _jid.user + "@" + _jid.server;
        var loaded = FaFaChatWin.allJid.get(_jid_bear);
        //用户登录设置处理
        if(typeof(FaFaChatMain)!=   "undefined" && _jid_bear==Jid.Bear(FaFaChatMain.owner.jid)) {
           FaFaChatWin.owner.addResource(_jid.resource);
        }  
        if (pre.Type == "hasofflinefile") {
            if (FaFaChatWin.owner.resource.join(",").indexOf("FaFaWin")>-1 || FaFaChatWin.owner.resource.join(",").indexOf("FaFaAndroid")>-1) return;
            //收到离线文件、图片
            var fileId = pre.Body.getAttribute("filehashvalue");
            var fileName = pre.Body.getAttribute("filename");
            FaFaChatFileUpload.RequestReceive(loaded.name, pre.From, fileName, fileId, $(pre.Body).attr("senddate"));
        }
        else if (pre.Body != null && pre.Body.tagName=="business") //评论消息
        {
          var html = "<div><span style='color:red'>"+ $($(pre.Body).find("sendername")).text()+"&nbsp;&nbsp;"+ $($(pre.Body).find("sendtime")).text()+"</span></div>"+
                     "<div style='height:18px;margin-top:5px'><span style='color:black;font-family:Arial,Verdana,sans-serif;font-size:12px;line-height:12px;margin-left:10px;'>"+$($(pre.Body).find("body")).text()+"</span></div>";
          if(FaFaChatWin.msghis.get(pre.From)==null)
             FaFaChatWin.msghis.put(pre.From,[html]);
          else{
            var msg1=FaFaChatWin.msghis.get(pre.From);
            msg1.push(html);
            FaFaChatWin.msghis.put(pre.From,msg1);
          }
        }
        else if (pre.Type == "online" || pre.Type == "offline") //好友上下线
        {
            //判断好友是否已加载过
            if (loaded != null) {
                if (pre.Type == "offline")
                    loaded.removeResource(_jid.resource);
                else
                    loaded.addResource(_jid.resource);
                if (FaFaChatWin.active && loaded.jid == FaFaChatWin.active.jid && FaFaChatWin.active.state != pre.Type) {
                    if (pre.Type == "offline" && loaded.resource.length == 0){
                       FaFaChatWin.WriteMsg(loaded.jid, "<div>好友下线了</div>");
                       $("#rosterHeader img").css("opacity","0.1");
                    }
                    else if (pre.Type == "online")
                    {
                         $("#rosterHeader img").css("opacity","1");
                       var online_resource="";
                       switch(Jid.Parse(pre.From).resource)
                       {
                         case "FaFaWin":
                           online_resource ="<div>好友通过<span style='color:red;'>&nbsp;PC&nbsp;</span>上线了</div>";
                           break;
                         case "FaFaAndroid":
                           online_resource ="<div>好友通过<span style='color:red;'>&nbsp;Android&nbsp;</span>上线了</div>";
                           break;
                         case "FaFaIphone":
                           online_resource ="<div>好友通过<span style='color:red;'>&nbsp;Iphone&nbsp;</span>上线了</div>";
                           break;
                         default:
                           online_resource ="<div>好友上线了</div>";
                           break;
                       }
                       FaFaChatWin.WriteMsg(loaded.jid,online_resource, _jid.resource);
                    }
                    if (pre.Type == "offline" && loaded.resource.length > 0) FaFaChatWin.active.resource = loaded.resource[0];
                }
            }
            else {
                loaded = new roster();
                loaded.jid = _jid.user + "@" + _jid.server;
                loaded.resource = [_jid.resource];
            }
            loaded.state = (pre.Type == "offline" && loaded.resource.length > 0) ? "online" : pre.Type;
            loaded.show = pre.Show;
            loaded.showDesc = pre.Status;
            FaFaChatWin.allJid.put(loaded.jid, loaded);                        
            FaFaChatWin.updateState(loaded.jid);
            FaFaChatWin.updateGroupMemberState(loaded.jid);
        }
    });
    
    FaFaMessage.GetIQ(function (iq) {
        if (iq.tagName == "fafawebfile") {
            var action = iq.Body.getAttribute("action");
            var fileId = iq.Body.getAttribute("fileid");
            var fn = iq.Body.getAttribute("filename");
            var jid = Jid.Bear(iq.From);
            if (action == "cancel")
            {
                if(Jid.Parse(iq.From).resource=="FaFaWin")
                   FaFaChatFileUpload.changeFileStatus(fileId, jid, "&nbsp;&nbsp;&nbsp;&nbsp;好友拒绝接收文件", true);
                else
                  FaFaChatFileUpload.changeFileStatus(fileId, jid, "&nbsp;&nbsp;&nbsp;&nbsp;好友已取消发送", true);
                
                FaFaChatWin.isSendFile = false;
                $("#xhe0_Tool [cmd='sendFile']").attr("title", "文件发送");
                FaFaChatFileUpload.sendingId = "";
            }
            else if (action == "reject") {
                //对方拒绝接收文件
                FaFaChatFileUpload.changeFileStatus(fileId, jid, "&nbsp;&nbsp;&nbsp;&nbsp;好友拒绝接收文件", true);
                FaFaChatWin.isSendFile = false;
                $("#xhe0_Tool [cmd='sendFile']").attr("title", "文件发送");
                FaFaChatFileUpload.sendingId = "";
            }
            else if (action == "accept") {
                FaFaChatFileUpload.changeFileStatus(fileId, FaFaChatFileUpload.sendToJID, "<a id='" + fileId + "'>正在发送...</a>");
                //对方同意接收文件，开始上传文件
                FaFaChatFileUpload.Upload(fileId);         
            }
            else if (action == "request") {
                //对方请求传送文件，让用户决定操作
                FaFaChatFileUpload.RequestReceive(iq.Body.getAttribute("nick"), iq.From, fn, fileId, iq.Body.getAttribute("sendtime"));
            }
            else if (action == "send") {
                //对方文件已发送完成，可以开始接收
                fn = iq.Body.getAttribute("oldfilename");
                var path = iq.Body.getAttribute("path");
                //FaFaChatFileUpload.Receive(fn, fileId, "", jid);
                FaFaChatFileUpload.ShowFile(jid, fileId,path,{"name":fn});
            }
        }
        else if (iq.tagName == "si") {
            var file = $(iq.Body).find("file");
            if (file.length == 0) return;
            var fn = $(file).attr("name");
            var ts = fn.split(".");
            var fixed = "," + ts.pop() + ",";
            //自动获取图片地址
            if (",jpg,jpeg,bmp,gif,png,".indexOf(fixed.toLocaleLowerCase()) > -1) {
               FaFaChatFileUpload.Receive(fn, ts.join(""), "img", Jid.Bear(iq.From));
            }
            else if ( fixed.toLocaleLowerCase().indexOf("amr")>-1)
            {
              FaFaChatFileUpload.Receive(fn, ts.join(""), "audio", Jid.Bear(iq.From));       
            }
            
        }
        else if (iq.tagName == "chatshiftresult") //通话被转接
        {
            var chatto = iq.Body.getAttribute("chatto");
            var nick = iq.Body.getAttribute("nickname");
            var _jid = Jid.Parse(chatto);
            FaFaChatWin.WriteMsg(chatto, "<div><span style='color:red'>尊敬的客户，您好！<br>您的疑问或问题已转交" + nick + "为您解答和处理。</div>", _jid.resource);
            //打开新处理人的新窗口，并成为当前窗口
            //应该要在窗口中显示一句专业问候用语
            FaFaChatWin.ShowRoster(chatto);
            //订阅新的处理人
            FaFaEmployee.Subscribe(chatto,function(data){
                var d = data;   
            });
        }
    });
    
    //截图消息："<SPAN contentEditable=false>{[CADC9CA6A38BAFBFC8A592A2C2626FFA.png]}</SPAN>"
    FaFaMessage.GetMessage(function (msg) {
        var text = msg.Body.text +(msg.Link!=null?" "+msg.Link+" ":" ");
        //判断msg中是否有url链接，有则替换成固定样式<a style="padding: 2px; color: #0078B4;" target="_blank" title="http://j.map.baidu.com/RavEu" href="http://j.map.baidu.com/RavEu"><img src="/bundles/fafatimewebase/images/face/../link16.png"> 链接地址</a>
        if ( msg.Type=="business")
        {
              if($(msg.Body.innerHTML).parent().find("type").text()!="wemicromessage_normal")  //如为微信消息不作url处理
                  text = MessageUtil.ParseUrl(text);
        }
        else
          text = MessageUtil.ParseUrl(text);
        text = text.replace(/＇/g,"'");
        text = MessageUtil.ParseFaces(text);
        
        //判断 是不否是通过web端发送的消息
        if (msg.From.resource.indexOf("FaFaWeb") > -1 && text.indexOf("FaFaWeb") == 0)
          text = text.substring(7);          
        var rosterInfo = new roster();
        var fromUser="";
        if ( msg.Type=="groupchat")
        {
          fromUser = msg.To;
          if (FaFaChatWin.groupName.get(fromUser.Bear())==null)
             FaFaChatWin.groupName.put(fromUser.Bear(),$("#"+msg.To.user).text().replace(/\s/g,""));
        }
        else {
          fromUser = msg.From;
        }
        if(msg.Nick != null && msg.Nick != "")
        {
           rosterInfo.name =    msg.Nick;
        }
        else
        {
          var tmp_cacheRoster= FaFaChatWin.allJid.get( fromUser.Bear());
          rosterInfo.name = tmp_cacheRoster==null? fromUser.user:tmp_cacheRoster.name;
        }
        rosterInfo.jid = fromUser.user == "guest" ? Jid.toString(msg.From) : fromUser.Bear();
        if (msg.Delay == "") rosterInfo.state = "online"; //只要不是收到离线消息，就说明用户在线
        FaFaChatWin.WriteMsg(rosterInfo.jid, "<div><span style='color:red'>" + (rosterInfo.name) + " " + (msg.Delay != "" ? msg.Delay : msg.Time) + "</span></div>", msg.From.resource);
        FaFaChatWin.AddRoster(rosterInfo.jid, rosterInfo);
        if(msg.Caption!=null && msg.Caption!="")
        {
                var $busButtons = msg.LinkText;
                if($busButtons!=null && $busButtons.length>0)
                {
                       var buts =$busButtons.find("button");
                       for(var i=0; i<buts.length; i++)
                           text += FaFaChatWin.CreateButtons($(buts[i]),msg.Link);
                }
                var caption = msg.Caption;
                if (msg.Caption=="trend-reply")
                   caption="Wefafa评论消息";
              text = "<div><b>"+caption+"</b></div><div>"+text+"</div>";
        }
        //格式化文本内容
        //text = FaFaMessage.fomratHTML(text);
        //判断消息来源类型
        switch(msg.From.resource)
        {
           case "FaFaWin":
             text+="<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自PC)</div>";
             break;
           case "FaFaAndroid":
             text+="<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自Android)</div>";
             break;
           case "FaFaIPhone":
             text+="<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自IPhone)</div>";
             break;
           case "iPad":
             text+="<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自iPad)</div>";
             break;
        }
        text = text.replace("＂","'").replace("＂","'").replace("&nbsp;","");
        text = text+"<div style='height:4px;background-color:#e2f5ff;'></div>";
        FaFaChatWin.WriteMsg(rosterInfo.jid, "<div style='margin-left:25px;'>" + text + "</div>", msg.From.resource);
    });
    
    
    
    FaFaMessage.ConnectionStateChange(function (status, info) {
        if (status == 5 || status == 8) {
            var selfInfo = new roster();
            selfInfo.jid = FaFaMessage._jid;
            FaFaChatWin.chatStyle=FaFaChatWin.ReadCookie(Jid.Bear(FaFaMessage._jid));
            selfInfo.state = "online";
            FaFaChatWin.owner = selfInfo;
            FaFaEmployee.Query(FaFaChatWin.owner.jid, function (rosterInfo) {
                rosterInfo.state = "online";
                rosterInfo.openid=FaFaChatWin.ownerOpenid;
                FaFaChatWin.owner = rosterInfo;
            });
            FaFaChatWin.Hint("服务器连接成功！", 2000);
            if (FaFaChatWin.active != null) {
                var _jid = FaFaChatWin.active.jid;
                FaFaChatWin.active = null;
                FaFaChatWin.ShowRoster(_jid);
            }
        }
        else if (status == "4") {
            //$.ligerDialog.closeWaitting();
            //$.ligerDialog.waitting("连接服务器失败：用户名或密码不正确"); 
            FaFaChatWin.Hint("连接服务器失败：用户名或密码不正确");
        }
        else if (info != null && info != "manual" && (status == "6" || status == "7")) {
            //$.ligerDialog.closeWaitting();
            FaFaChatWin.Hint("连接服务器失败：" + info + ",您可尝试：&nbsp;&nbsp;<a href='javascript:FaFaMessage.RestartConn()'>重新连接</a>");
            //$.ligerDialog.waitting(); 
        }
    });
};
FaFaChatWin.SetOwnerOpenid=function(openid){
   this.ownerOpenid=openid;
};
FaFaChatWin.CreateButtons=function($button,$Link){
        var _t = $button.find("text").text();
        if(_t=="") return "";
        var href=$button.find("link").text(),_m=$button.find("m").text(),_blank=$button.find("blank").text(),$para =$button.find("code").text()+"="+$button.find("value").text() ;
        if(href=="" && $Link=="") return "";
        href = href=="" ? $Link : href;
        href = href+( href.indexOf("?")>0 ? "&":"?")+$para+"&jsoncallbak=?&_urlSource=FaFaWeb&_wefafauid="+FaFaChatWin.owner.openid+"&_wefafauname="+FaFaChatWin.owner.name;
        //判断是否需要显示说明输入框
        var $showremark = $button.find("showremark");
        if($showremark.length>0 && $showremark.text()=="1")
        {
              var label = $button.find("remarklabel").text();
              label  = label=="" ? "备注": label;
              
            if(_m=="POST") href= "javascript:FaFaChatWin.promptMessage('"+href+"','"+label+"','post')";
            else if(_blank=="0") href= "javascript:FaFaChatWin.promptMessage('"+href+"','"+label+"','get')";
        }
        else
        {
            if(_m=="POST") href= "javascript:$.getJSON('"+href+"')";
            else if(_blank=="0") href= "javascript:$.getJSON('"+href+"')";
        }
        var r = "<a href=\""+href+"\">["+_t+"]</a>";
        return r;
}
;
FaFaChatWin.promptMessage=function(href,v,m){
      var t = window.prompt(v,'请输入'+v);
      $.getJSON(href+'&_remark='+t,{},function(d){
      });
};

FaFaChatWin.close = function () {
      if($("#fafa_chat_group_card").is(":visible"))
           $("#fafa_chat_group_card").hide();
    if (FaFaChatWin.active == null) {
        this.obj.modal("hide");
        $("#fafa_chat_employee_card").hide();
        $("#fafa_chat_group_card").hide();
        return;
    }
    if ( FaFaChatWin.file_id != '')
    {
       var ctrl = $("#fileid"+FaFaChatWin.file_id);
       if (ctrl.length == 1)
       {
         var filename = $("#"+FaFaChatWin.file_id+" :first").text();
         filename = filename.replace('文件:','').replace(/\s/g,'');       
         FaFaChatFileUpload.Reject(filename,FaFaChatWin.file_id);
         FaFaChatWin.file_id = '';
       }
    }
    this.msglogCtl.html("");
    var jid = FaFaChatWin.active.jid;
    var nc = this.obj.find(".webim_modal-header .webim_nickname a[jid='" + jid + "']");
    nc.parent().remove();
    if(typeof(FaFaChatMain)!="undefined"){
      if (FaFaChatWin.groupName.get(jid) == null)
        if ($("#fafa_chat_employee_card").css("display") != "none") 
          $("#fafa_chat_employee_card").hide();
      else
        if ($("#fafa_chat_group_card").css("display") != "none") 
          $("#fafa_chat_group_card").hide();
    }
    this.toList.keySet()[FaFaChatWin.active.jid] = undefined;
    nc = this.obj.find(".webim_modal-header .webim_nickname span");
    if (nc.length == 0) {
          if(typeof(FaFaChatMain)!="undefined")
            FaFaChatMain.Cancel_Select_Style(FaFaChatWin.active.jid,true);
        FaFaChatWin.active = new roster();
        this.obj.modal("hide");
        $("#fafa_chat_employee_card").hide();
    }
    else{
        for (var i = 0; i < nc.length; i++) {
            jid = $(nc[i]).find("a").attr("jid");
            if(typeof(FaFaChatMain)!="undefined"){
              FaFaChatMain.Cancel_Select_Style(FaFaChatWin.active.jid,true);
              jid = FaFaChatWin.isGroup(jid)?jid.split("@")[0]:jid.replace(/@|\./g, '_');
            }
          $("#"+jid).css("background-color","PaleGoldenrod");
          this.ShowRoster($(nc[i]).find("a").attr("jid"));
          break;
        }
    }
};

FaFaChatWin.open = function () {
    //this.obj.modal("show");
};

FaFaChatWin.ShowCard = function () {
    var _cx = $("#fafa_chat_employee_card");
    if (_cx.length == 0) {
        _cx = document.createElement("DIV");
        _cx.id = "fafa_chat_employee_card";
        _cx.className = "webim_chat_modal";
        with (_cx.style) {
            width = "180px";
            height = this.h + "px";
            left = (this.obj.offset().left - 180 - 3) + "px";
            marginLeft = "0px";
        }
        _cx.innerHTML = "<div class=\"webim_modal-header\">" +
                          "<a class=\"webim_close\" style='line-height:1px;font-size:16px'>×</a><div style='cursor:default;' class='webim_fafa_chat_photo'><img src='/bundles/fafawebimimchat/images/nophoto.jpg'></div><div style='color:blue;margin-top:2px;margin-left:50px;' class='webim_nickname'></div><div style='font-size:12px;line-height:22px;margin-left:55px;' class='webim_dept'></div></div>" +
                          "<div class=\"webim_modal-body\"><div style='margin-top:2px;'><span>&nbsp;&nbsp;企业名称：</span><br/>&nbsp;<span class='webim_ename'></span></div><div><span>&nbsp;&nbsp;职务：</span><br/>&nbsp;<span class='webim_duty'></span></div><div><span>&nbsp;&nbsp;手机号码：</span><br/>&nbsp;<span class='webim_mobile'></span></div><div><span>&nbsp;&nbsp;联系电话：</span><br/>&nbsp;<span class='webim_phone'></span></div><div><span>&nbsp;&nbsp;电子邮箱：</span><br/>&nbsp;<span class='webim_email'></span></div></div>";
        document.body.appendChild(_cx);
        cx = $("#fafa_chat_employee_card");
        cx.modal({ show: false, backdrop: false });
        cx.find(".webim_modal-header .webim_close").live("click", function () { $("#fafa_chat_employee_card").hide() });
        cx.find(".webim_modal-body").css({ "height": this.h - (50 + 20) - (20 + 20) - 2 });
    }
    cx.find(".webim_nickname").text(FaFaChatWin.active.name);
    cx.find(".webim_dept").text(FaFaChatWin.active.dept);
    
    cx.find(".webim_ename").text("　　"+FaFaChatWin.active.ename);
    cx.find(".webim_mobile").text("　　"+FaFaChatWin.active.mobile);
    cx.find(".webim_phone").text("　　"+FaFaChatWin.active.phone);
    cx.find(".webim_email").text("　　"+FaFaChatWin.active.email);
    cx.find(".webim_fafa_chat_photo img").attr("src", FaFaChatWin.active.photo);
    cx.modal("show");
    cx.show();
};

//聊天消息对话框
FaFaChatWin.ShowChatMsg = function(){
  ChatMessage.currentNick = FaFaChatWin.active.name;
  var _chatmsg = $("#webim_chat_windows");
  if (_chatmsg.length==0) {
    _chatmsg = document.createElement("div");
    _chatmsg.id = "webim_chat_windows";      
    document.body.appendChild(_chatmsg);
    
    $("#webim_chat_windows").css({"width":"650px","height":"400px","position":"fixed"});
        
    var msg_html="<div class='modal-header webim_chatmsg_header'><div class='webim_chatmsg_title_flag'></div><span class='webim_chatmsg_title_word'>聊天消息管理</span>"+
                 "<span class='webim_chatmsg_close' onclick=\"javascript:$('#webim_chat_windows').hide();\" >×</span></div>"+
                 "<div class='webim_chagmsg_tool'>"+
                 "  <span id='chatmsg_roster' class='webim_chatmsg_tool_roster_select' title='我的联系人'></span>"+
                 "  <span id='chatmsg_org' class='webim_chatmsg_tool_org' title='组织机构'></span>"+
                 "  <span id='chatmsg_group' class='webim_chatmsg_tool_group' title='群/组'></span>"+
                 "  <span id='chatmsg_systmMsg' class='webim_chatmsg_tool_systemMsg' title='系统消息'> </span>"+
                 "  <ul class='nav nav-pills' style='float:right;'><li class='dropdown' id='menutest1' style='float:right;'><span id='delete_signle_id' class='webim_chatmsg_tool_delete'></span><span id='delete_doublue_id' class='webim_chatmsg_tool_downdelete' data-toggle='dropdown' href='#menutest1'></span>"+
                 "   <ul id='ul_list' class='dropdown-menu webim_chatmsg_tool_deleteWord'><li id='chatmsg_all_delete'><a style='margin-top:-3px;'>全部删除</a></li></ul></li>"+
                 "   <li style='float:right;width:55px'><span id='chatmsg_export' class='webim_chatmsg_tool_export' title='数据导出'></span></li>"+
                 "   <li style='float:right;width:55px'><span id='chatmsg_search' class='webim_chatmsg_tool_search' title='数据搜索'></span></li>"+
                 " </ul></div>"+
                 "<div class='modal-body webim_chatmsg_body'>"+
                 "  <div id='chatmsg_contacts' class='webim_chatmsg_body_roster'></div>"+
                 "  <div id='chatmsg_showorg' class='webim_chatmsg_body_org'><ul id='treeorg' style='margin-left:-6px;border:none;background:none;' class='ztree'></ul></div>"+
                 "  <div id='chatmsg_showgroup' class='webim_chatmsg_body_roster' style='display:none;'></div>"+
                 "  <div id='chatmsg_showsystem' class='webim_chatmsg_body_roster' style='display:none;'></div>"+
                 "  <div id='webim_chatmsg_right' class='webim_chatmsg_body_right'></div>"+
                 "  <div class='modal-footer webim_chatmsg_body_footer'>"+
                 "    <span style='margin-left:5px;'>总记录数：</span><span id='total_record'></span>"+
                 "    <span style='margin-left:50px;'>总&nbsp;页&nbsp;数：</span><span id='total_paging'></span><div id='chatPager' style='float:right;cursor:pointer;margin-right:25px;'>"+
                 "    <span id='first_pager' class='webim_chatmsg_first_pager' title='第一页'>|<</span>"+
                 "    <span id='prev_pager'  class='webim_chatmsg_prev_pager' title='上一页'><</span>"+
                 "    <span id='next_pager'  class='webim_chatmsg_next_pager' title='下一页'>></span>"+
                 "    <span id='last_pager'  class='webim_chatmsg_last_pager' title='最后页'>>|</span></div>"+
                 "  </div>"+
                 "</div>"+
                 "<div id='chatmsg_serach_condition' class='popover fade bottom in webim_chatmsg_searchCondition' style='margin-top:-335px;margin-left:375px;'>"+
                 "    <div class='arrow'></div>"+
                 "    <div class='popover-inner'>"+             
                 "    <div class='popover-content'>"+
                 "      <div class='webim_chatmsg_searchCondition_word'><span>开始日期&nbsp;&nbsp;<input type='text' id='textstartdate' readonly='readonly' style=\"border:1px solid black;font-family:'宋体','Arial','Helvetica','Verdana','sans-serif';font-size:12px;height:12px;line-height:15px;width:125px;margin-top:4px;\" /></div>"+
                 "      <div class='webim_chatmsg_searchCondition_word'><span>查找内容&nbsp;&nbsp;<input type='text' id='textcontent' style=\"border:1px solid black;font-family:'宋体','Arial','Helvetica','Verdana','sans-serif';font-size:12px;height:12px;line-height:15px;width:125px;margin-top:8px;\" /></div>"+
                 "<span class='btn webim_chatmsg_btn' style='left:60px;'  id='btnSearchMsg'><span style='margin-left:-4px;'>查&nbsp;询</span></span>"+
                 "<span class='btn webim_chatmsg_btn' style='left:100px;' id='btnSearchCancel'><span style='margin-left:-4px;'>取&nbsp;消</span></span>"+
                 "    </div>"+
                 "   </div>"+
                 "</div>"+
                 "<div style='display:none'>"+
                 "  <iframe frameborder='no' marginheight='0' marginwidth='0' border='0' name='fafawin_downloadmsg' id='fafawin_downloadmsg'></iframe>"+
                 "</div>";
      $("#webim_chat_windows").html(msg_html);
      $("#webim_chat_windows").addClass("webim_chatmsg_main_window");
      FaFaChatWin.ShowSearchRoster();
      
      //删除单条
      $(".webim_chatmsg_tool_delete").unbind().bind("click",function(){
         ChatMessage.chatMsgId = ChatMessage.chatMsgId.replace("msg","");
         if(ChatMessage.chatMsgId>0){
           FaFaChatWin.ChatMsgReomveById(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.chatMsgId);
           $("#webim_chatmsg_right #msg"+ChatMessage.chatMsgId).remove();
         }
      });
      
      //删除多条数据记录   
      $("#chatmsg_all_delete").unbind().bind("click",function(){
          var ownerjid = Jid.Bear(FaFaChatWin.owner.jid);
          for(var i=0;i<$("#webim_chatmsg_right").children().length;i++){
            var id = $($("#webim_chatmsg_right").children()[i]).attr("id");
            if(typeof(id)!="undefined"){
              FaFaChatWin.ChatMsgReomveById(ownerjid,id.replace("msg",""));
            }
          }
          $(".webim_chatmsg_tool_alldelete").hide();
          $("#webim_chatmsg_right").attr("class","webim_chatmsg_body_right_empty");
     　　 $("#webim_chatmsg_right").text("没有聊天记录！");
          $("#menutest1").attr("class","dropdown");
      });
      
      //查询聊天记录信息
      $("#btnSearchMsg").live("click",function(){
         ChatMessage.searchDate = $("#textstartdate").val();
         ChatMessage.searchText = $("#textcontent").val();
         if(ChatMessage.searchText==""){           
           return false;
         }; 
         FaFaChatWin.ShowLoading(2);
         ChatMessage.searchJid="";
         ChatMessage.searchType="";
         ChatMessage.currentNick="SearchRecord";       
         FaFaChatWin.searchChatMsg(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.searchJid,ChatMessage.searchDate,ChatMessage.searchText,ChatMessage.searchType,function(data){
             FaFaChatWin.DisplayChatMsg(data);
         });
         $("#chatmsg_serach_condition").hide();
      });

      $("#btnSearchCancel").live("click",function(){
         $("#chatmsg_serach_condition").hide();
      });
      
      $("#textstartdate").unbind().bind("click",function(){
         var date = new Date();
         var min_date = (date.getFullYear()-1)+"-"+(date.getMonth()+1)+"-"+date.getDate();
         WdatePicker({minDate:min_date,maxDate:date});
      });
      
      //点击好友面板
      $("#chatmsg_roster").live("click", function () {
          FaFaChatWin.ShowSearchRoster();
      });
      
      //组织机构
      $("#chatmsg_org").live("click",function(){
         $("#chatmsg_org").attr("class","webim_chatmsg_tool_org_select");
         $("#chatmsg_contacts").hide();
         $("#chatmsg_showgroup").hide();
         $("#chatmsg_showsystem").hide();
         $("#chatmsg_showorg").show();
         FaFaChatWin.ShowSearchOrg();
      });
      
      //群组
      $("#chatmsg_group").unbind().bind("click",function(){
        //显示群组信息
        FaFaChatWin.ShowSearchGroup();
      });
      
      //系统消息
      $("#chatmsg_systmMsg").live("click",function(){
         $("#chatmsg_contacts").hide();
         $("#chatmsg_showorg").hide();
         $("#chatmsg_showgroup").hide();
         $("#chatmsg_showsystem").show();
         FaFaChatWin.ShowSearchSystemMsg();        
      });
      
      //搜索工具栏
      $("#chatmsg_search").live("click",function(){
         $("#chatmsg_serach_condition").toggle();
      });
      
      //导出聊天记录
      $("#chatmsg_export").live("click",function(){
        var url = WebIM_domain + "/api/wechatmsg/export?ownerjid="+Jid.Bear(FaFaChatWin.owner.jid)+
                  "&jid="+ChatMessage.searchJid+
                  "&date="+ChatMessage.searchDate+
                  "&content="+ChatMessage.searchText+
                  "&msgtype="+ChatMessage.searchType+
                  "&currentnick="+ChatMessage.currentNick;
        $("#fafawin_downloadmsg").attr("src",url);
      });
      
      //首页
      $(".webim_chatmsg_first_pager").live("click",function(){
        FaFaChatWin.ShowLoading(1);
        ChatMessage.currentPageIndex = 1;
        var record = $("#total_record").text(),limit="";
        if (record%ChatMessage.everyPage==0)
          limit = "0,"+ChatMessage.everyPage;
        else
          limit = (ChatMessage.currentPageIndex-1)*ChatMessage.everyPage+","+ChatMessage.everyPage;
        $("#first_pager").attr("class","webim_chatmsg_pager_enable");
        $("#prev_pager").attr("class","webim_chatmsg_pager_enable");
        $("#next_pager").attr("class","webim_chatmsg_next_pager");
        $("#last_pager").attr("class","webim_chatmsg_last_pager");

        FaFaChatWin.pagingChatMsg(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.searchJid,'','',limit,function(data){
          FaFaChatWin.DisplayChatMsg(data);
        });
      });
   
      //末页
      $(".webim_chatmsg_last_pager").live("click",function(){
        ChatMessage.currentPageIndex = $("#total_paging").text();
        var limit = (ChatMessage.currentPageIndex-1)*ChatMessage.everyPage+","+ChatMessage.currentPageIndex*ChatMessage.everyPage
        FaFaChatWin.ShowLoading(1);
        $("#first_pager").attr("class","webim_chatmsg_first_pager");
        $("#prev_pager").attr("class","webim_chatmsg_prev_pager");
        $("#next_pager").attr("class","webim_chatmsg_pager_enable");
        $("#last_pager").attr("class","webim_chatmsg_pager_enable");
        
        FaFaChatWin.pagingChatMsg(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.searchJid,'','',limit,function(data){
          FaFaChatWin.DisplayChatMsg(data);
        });
      });
   
      //上一页
      $(".webim_chatmsg_prev_pager").live("click",function(){
        FaFaChatWin.ShowLoading(1);
        ChatMessage.currentPageIndex = ChatMessage.currentPageIndex-1;
        var limit=(ChatMessage.currentPageIndex-1)*ChatMessage.everyPage+","+ChatMessage.currentPageIndex*ChatMessage.everyPage;
        if(ChatMessage.currentPageIndex==1){
          $("#first_pager").attr("class","webim_chatmsg_pager_enable");
          $("#prev_pager").attr("class","webim_chatmsg_pager_enable");
          $("#next_pager").attr("class","webim_chatmsg_next_pager");
          $("#last_pager").attr("class","webim_chatmsg_last_pager");
        }        
        FaFaChatWin.pagingChatMsg(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.searchJid,'','',limit,function(data){
          FaFaChatWin.DisplayChatMsg(data);
        });        
      });
   
      //下一页
      $(".webim_chatmsg_next_pager").live("click",function(){
        FaFaChatWin.ShowLoading(1);
        ChatMessage.currentPageIndex=ChatMessage.currentPageIndex+1;
        var limit = (ChatMessage.currentPageIndex-1)*ChatMessage.everyPage+","+ChatMessage.currentPageIndex*ChatMessage.everyPage;        
        if(ChatMessage.currentPageIndex==$("#total_paging").text()){
          $("#first_pager").attr("class","webim_chatmsg_first_pager");
          $("#prev_pager").attr("class","webim_chatmsg_prev_pager");
          $("#next_pager").attr("class","webim_chatmsg_pager_enable");
          $("#last_pager").attr("class","webim_chatmsg_pager_enable");
        }
        FaFaChatWin.pagingChatMsg(Jid.Bear(FaFaChatWin.owner.jid),ChatMessage.searchJid,'','',limit,function(data){
          FaFaChatWin.DisplayChatMsg(data);
        });
      });
      
      //关闭聊天消息窗口
      $(".modal-header .close").unbind().bind("click",function(){
        //取消查询用户样式
        $("#webim_chat_windows").hide();
      });
  }
  else
    $("#webim_chat_windows").show();
  
  //折叠组
  var ul = $("#chatmsg_contacts").children();
  var _id="";
  for(var j=0;j<ul.length;j++){
    _id = ul[j].getAttribute("id");
    $("#"+_id+">li:first").children().eq(0).attr("class","webim_main_group_image_close");
    $("#"+_id+">li:not(:first)").hide();
  }
  var active_jid = FaFaChatWin.active.jid;
  if(FaFaChatWin.isGroup(active_jid)){
    FaFaChatWin.ShowSearchGroup();
    var obj= $("#chatmsg_contacts").children();
    if(obj.length>0){
      var _id = obj[0].getAttribute("id");
      $("#"+_id+">li:first").children().eq(0).attr("class","webim_main_group_image_open");
      $("#"+_id+">li:not(:first)").show();
    }
  }
  else{
    $("#chatmsg_group").attr("class","webim_chatmsg_tool_group");
    $("#chatmsg_org").attr("class","webim_chatmsg_tool_org");
    $("#chatmsg_roster").attr("class", "webim_chatmsg_tool_roster_select");
    $("#chatmsg_showorg").hide();
    $("#chatmsg_showgroup").hide();
    $("#chatmsg_showsystem").hide();
    $("#chatmsg_contacts").show();
    _id = active_jid.replace(/\@|[.]/g,"");
    _id = $("#"+_id).parent().attr("id");
    $("#"+_id+">li:first").children().eq(0).attr("class","webim_main_group_image_open");
    $("#"+_id+">li:not(:first)").show();
  }
  FaFaChatWin.setListStyle(active_jid);//设置样式
  ChatMessage.searchJid=active_jid;
  ChatMessage.searchDate="";
  ChatMessage.searchText="";  
  ChatMessage.currentPageIndex=1;
  ChatMessage.searchType = FaFaChatWin.isGroup(active_jid)?1:0;  
  FaFaChatWin.ShowMsgContent(ChatMessage.searchJid,ChatMessage.searchDate,ChatMessage.searchText,ChatMessage.searchType);
  if(FaFaChatWin.isGroup(active_jid)==false) {
    active_jid = active_jid.replace(/\@|[.]/g,"");
    if($("#"+active_jid).index()!=1) {
      var ulid = $("#"+active_jid).parent().attr("id");
      $("#"+ulid+" li:first").after($("#"+active_jid));
    }
  }
};

//群卡片
FaFaChatWin.ShowGroupCard = function (gid) {
    var _cx = $("#fafa_chat_group_card");
    if (_cx.length == 0) {
        _cx = document.createElement("DIV");
        _cx.id = "fafa_chat_group_card";
        _cx.className = "webim_group_modal";
        with (_cx.style) {
           // position = "absolute";
            width = "180px";
            height = this.h + "px";
            left = (this.obj.offset().left - 180 - 3) + "px";
            marginLeft = "0px";
        }
        _cx.innerHTML = "<div class='webim_group_header'>" +
                            "<a class='webim_group_close'>×</a><div class='webim_group_photo'><img style='width:50px;height:50px' src='https://www.fafaim.com/bundles/fafawebimimchat/images/head_default_group.gif'></div><div style='color: blue; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; display: block; float: left; width: 100px;' class='webim_groupname'></div><div style='margin-left:55px'><span class='webim_groupdesc'></span></div></div>" +
                            "<div class='webim_group_body'><div class='webim_grouppost'></div><div style='border: 1px solid white;'><span style='height:20px;line-height:20px;font-weight:bold;margin-left:5px;'><a class='webim_group_memberlabel'>群成员</a> [</span><span id='memberScalar'></span>]</div><div class='webim_groupmember'><ul id='groupmember'></ul></div></div>";
        document.body.appendChild(_cx);
        _cx = $("#fafa_chat_group_card")
        _cx.modal({ show: false, backdrop: false });
        _cx.find(".webim_group_header .webim_group_close").live("click", function () { $("#fafa_chat_group_card").hide();});        
        _cx.find(".webim_group_body").css({ "height": this.h - $(".webim_modal-group_header").height()});
    }
    $(".webim_groupname").text(FaFaChatWin.active.name);
    var groupinfo = FaFaChatWin.allJid.get(gid+"@fafacn.com");
    var desc = groupinfo.post;
    if ( desc==null || desc=="undefined")
        desc="";
    if(groupinfo.groupclass=="circlegroup"){
        $(".webim_grouppost").html("<div style='margin-left:5px;'><b>圈子公告：</b><br/>"+desc+"</div>");
        $(".webim_group_memberlabel").text("圈子成员");
    }
    else if(groupinfo.groupclass=="meeting" || groupinfo.groupclass=="discussgroup")
    {
          $(".webim_grouppost").html("<div style='margin-left:5px;'><b>会议主题：</b><br/>"+desc+"</div>");
          $(".webim_group_memberlabel").text("会议成员");
    }
    else
    {
          $(".webim_grouppost").html("<div style='margin-left:5px;'><b>群公告：</b><br/>"+desct+"</div>");
          $(".webim_group_memberlabel").text("群组成员");
    }
    var groupdesc = groupinfo.desc;
    if ( groupdesc.length>15)
    {
       $(".webim_groupdesc").text(groupdesc.substring(0,15)+'...');
       $(".webim_groupdesc").attr('title',groupdesc);
    }
    else
    {
       $(".webim_groupdesc").text(groupdesc);
    }
    _cx.find(".webim_group_photo img").attr("src", FaFaChatWin.active.photo);
    FaFaChatWin.ShowGroupMember(gid);  
    _cx.modal("show");
    _cx.show();
};

FaFaChatWin.ShowGroupMember = function(gid){
    if(FaFaChatMain.GroupInfo.get(gid).isload==false)
    {
       setTimeout("FaFaChatWin.ShowGroupMember("+gid+")", 100);
       return false;
    }
    $("#groupmember").html("");
    var onlineHTML="",offlineHTML="",count = 1;
    for (jid in FaFaChatMain.MemberList.keySet()) {
      if ( jid.indexOf("@")== -1) continue;
      var member = FaFaChatMain.MemberList.get(jid);
      if (member.groupid.toString().indexOf(gid)>-1)
      {
         count++;
         var name = member.name;             
         var pre = FaFaChatMain.onlineCache.get(jid);
         var rosterStyle="",rosterStatus="";
         if (pre != null) {
           if (pre.Type=="online")  //在线
           {
              if(pre.Show=="away" || pre.Show=="xa")
              {
                 rosterStatus="离开";
                 rosterStyle = "webim_chat_roster_header_leave";
              }
              else if (pre.Show=="dnd")
              {
                if(rosterStatus == "请勿打扰")
                  rosterStyle = "webim_chat_roster_header_disturb";
                else
                {
                  rosterStatus="忙碌";
                  rosterStyle = "webim_chat_roster_header_busy";
                }
              }
              else
                 rosterStyle = "webim_chat_roster_header_online";
           }
           onlineHTML += "<li id='g_jid_"+jid.replace(/@|\./g,'_')+"' class='li_groupmember' jid='"+jid+"' title='"+rosterStatus+"'>"+
                         "  <div id='group_jid_" + jid.replace(/@|\./g,'_') + "' class='" + rosterStyle + "'></div>" +
                         "  <span style='margin-left:35px;'>" + name + "</span>"+
                         "</li>";
         }
         else
         {
           offlineHTML += "<li id='g_jid_"+jid.replace(/@|\./g,'_')+"' class='li_groupmember' jid='"+jid+"' title='离线'>"+
                          "  <div id='group_jid_" + jid.replace(/@|\./g,'_') + "' class='webim_chat_roster_header_offline'></div>"+
                          "  <span style='margin-left:35px;'>" + name + "</span>" + 
                          "</li>";            
         }
      }
    }
    //添加自己
    jid = Jid.Bear(FaFaChatWin.owner.jid);
    name = FaFaChatWin.owner.name;
    onlineHTML += "<li id='g_jid_"+jid.replace(/@|\./g,'_')+"' class='li_groupmember' jid='"+jid+"' title='"+rosterStatus+"'>"+
                  "  <div id='group_jid_" + jid.replace(/@|\./g,'_') + "' class='webim_chat_roster_header_online'></div>" +
                  "  <span style='margin-left:35px;'>" + name + "</span>"+
                  "</li>";   
    $("#groupmember").append(onlineHTML);
    if (offlineHTML!="")
      $("#groupmember").append(offlineHTML);
    //群成员统计
    var off = $("#groupmember .webim_chat_roster_header_offline").length;
    $("#memberScalar").text(" "+(count - off)+" / "+count+" ");
    
    $(".li_groupmember").live("dblclick",function(){
      var _jid = $(this).attr("jid");
      if(_jid != Jid.Bear(FaFaChatWin.owner.jid))
        FaFaChatWin.ShowRoster(_jid);
    });
};

//判断jid是否群组
FaFaChatWin.isGroup = function(jid){
  jid = jid.split("@")[0];
  if(typeof(FaFaChatMain)=="undefined") return false;
  return FaFaChatMain.GroupInfo.get(jid)==null?false:true;
};

//显示搜索联系人
FaFaChatWin.ShowSearchRoster = function() {
  $("#chatmsg_group").attr("class","webim_chatmsg_tool_group");
  $("#chatmsg_org").attr("class","webim_chatmsg_tool_org");
  $("#chatmsg_roster").attr("class", "webim_chatmsg_tool_roster_select");
  $("#chatmsg_showorg").hide();
  $("#chatmsg_showgroup").hide();
  var list=null,html="",_id="";
  if($("#chatmsg_contacts").children().length==0) {
    var roster = $(".webim_main_body").children();
    for(var i=0;i<roster.length;i++) {
      list = roster.eq(i).children();
      html="";
      for(var j=0;j<list.length;j++) {
        if(j==0) {
          var listname = list.eq(j).text();
          var array_list = listname.split('[');
          if(array_list.length>1){
            listname = array_list[0]+ "&nbsp;&nbsp;["+ array_list[1].split('/')[1];
          }
          _id = "ul_"+i+""+j;
          html += "<ul id='"+_id+"'><li class='webim_chatmsg_first' id='"+_id.replace('ul_','li_')+"'><div class='webim_main_group_image_close' style='left:2px;'></div><span style='line-height:25px;margin-left:10px;'>"+listname+"</span></li>";
        }
        else {
          _id = list.eq(j).attr("id");
          var user = $("#"+_id+" .webim_main_roster_header_nickname");
          html += "<li style='display:none;' class='webim_chatmsg_not_first' id='"+_id.replace(/_/g,"")+"' jid='"+user.attr("jid")+"'>"+
                  "<img class='webim_chatmsg_img' src='"+$("#"+_id+" .webim_main_roster_header_photo").attr("src")+"' />"+
                  "<span class='webim_chatmsg_list'>"+user.text()+"</span></li>"+(j==list.length-1 ? "</ul>" : "");
        }
      }
      $("#chatmsg_contacts").append(html);
      //切换组
      $(".webim_chatmsg_first").unbind().bind("click",function(){
        var ul_id = this.id.replace('li','ul');           
        if($("#"+ul_id+">li:first").children().eq(0).attr("class")=="webim_main_group_image_open")
          $("#"+ul_id+">li:first").children().eq(0).attr("class","webim_main_group_image_close");
        else
          $("#"+ul_id+">li:first").children().eq(0).attr("class","webim_main_group_image_open");
        $("#"+ul_id+">li:not(:first)").toggle();
      });
      
      //单击单个好友
      $(".webim_chatmsg_not_first").unbind().bind("click",function(){
        var jid = this.getAttribute("jid");
        FaFaChatWin.setListStyle(jid); 
        ChatMessage.searchJid = jid;
        ChatMessage.currentNick = $(this).find("span").text();
        ChatMessage.searchDate="";
        ChatMessage.searchText="";
        ChatMessage.searchType=0;
        ChatMessage.currentPageIndex=1;
        FaFaChatWin.ShowMsgContent(jid,ChatMessage.searchDate,ChatMessage.searchText,ChatMessage.searchType);
      });
    }
  }
  $("#chatmsg_contacts").show();
};

//显示聊天内容
FaFaChatWin.ShowMsgContent = function(jid,date,content,msgType){
   FaFaChatWin.ShowLoading(1);
   FaFaChatWin.searchChatMsg(Jid.Bear(FaFaChatWin.owner.jid),jid,date,content,msgType,function(data){
     if (data==null){
       $("#webim_chatmsg_right").attr("class","webim_chatmsg_body_right_empty");
       $("#webim_chatmsg_right").text("没有聊天记录！");
       $("#total_record").text("0");
       $("#total_paging").text("0");
       $("#chatPager").hide();
       $("#chatmsg_export").attr("class","webim_chatmsg_tool_export_enable");
       $("#delete_signle_id").attr("class","webim_chatmsg_tool_delete_enable");
       $("#delete_doublue_id").attr("class","webim_chatmsg_tool_downdelete_enable");
     }
     else{
       FaFaChatWin.DisplayChatMsg(data);
     }
   });
};

FaFaChatWin.ShowSearchOrg = function(){
  $("#chatmsg_roster").attr("class","webim_chatmsg_tool_roster");
  $("#chatmsg_group").attr("class","webim_chatmsg_tool_group");  
  if($("#treeorg").children().length==0){

    FaFaEmployee.QueryDept(function (list) {
      var zNodes = new Array();
      for (var i = 0; i < list.length; i++) {
        var organInfo = $(list[i]);
        var pid = organInfo.attr('pid');
        var deptid = organInfo.attr('deptid');
        var deptname = organInfo.attr('deptname');
        var noorder = organInfo.attr('noorder');
        if (pid == -10000)
          zNodes.push({ id: deptid, pId: pid, name: deptname, open: true, icon:WebIM_domain+"/bundles/fafawebimimchat/images/org_root.png" });
        else
          zNodes.push({ id: deptid, pId: pid, name: deptname, icon:WebIM_domain+ "/bundles/fafawebimimchat/images/tree_node_close.png", iconOpen:WebIM_domain+"/bundles/fafawebimimchat/images/tree_node_open.png", iconClose:WebIM_domain+"/bundles/fafawebimimchat/images/tree_node_close.png" });
      }
      var setting = { data: { simpleData: { enable: true} },
          callback:{
            onClick: function (event, treeId, treeNode) {
                if (treeNode.id.indexOf("@") > -1) {
                  if (treeNode.id != Jid.Bear(FaFaChatWin.owner.jid))
                    FaFaChatWin.ShowMsgContent(treeNode.id,"","",0);
                }
                else {
                  FaFaChatMain.Get_Dept_Employee("treeorg",treeId, treeNode,false);
                  $.fn.zTree.getZTreeObj("treeorg").expandNode(treeNode); //单击展开或折叠事件
                }
            },
            onExpand: function (event, treeId, treeNode) {
                if (treeNode.id.indexOf("@") > -1)
                    FaFaChatWin.ShowMsgContent(treeNode.id,"","",0);
                else
                    FaFaChatMain.Get_Dept_Employee("treeorg",treeId, treeNode,false);
            }
         }
      }
      $.fn.zTree.init($("#treeorg"), setting, zNodes);
    });
  };
};

//搜索信息时动画
FaFaChatWin.ShowLoading = function(type){
   $("#webim_chatmsg_right").attr("class","webim_chatmsg_body_right_empty");
   var html="";
   if(type==1)
     html="<span class='webim_chatmsg_loading'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;正在提交，请稍等...</span>";
   else
     html="<span class='webim_chatmsg_loading'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;正在搜索，请稍等...</span>";
   $("#webim_chatmsg_right").html(html);
};
//显示群组
FaFaChatWin.ShowSearchGroup = function(){
   $("#chatmsg_roster").attr("class","webim_chatmsg_tool_roster");
   $("#chatmsg_org").attr("class","webim_chatmsg_tool_org");
   $("#chatmsg_group").attr("class","webim_chatmsg_tool_group_select");
   $("#chatmsg_showsystem").hide();
   $("#chatmsg_contacts").hide();
   $("#chatmsg_showorg").hide();
   if($("#chatmsg_showgroup").children().length==0) {
     var html=$(".webim_main_group").html();
     html = html.replace(/groupstyle/g,"group_style");
     html = html.replace(/groupclass_meeting/g,"groupclass_meeting1");
     html = html.replace(/groupclass_circlegroup/g,"groupclass_circlegroup1");
     html = html.replace(/groupclass_group/g,"groupclass_group1");
     $("#chatmsg_showgroup").append(html);
     
     $(".group_style").unbind().bind("click",function(){
       var jid = this.getAttribute("jid");
       jid = jid +"@"+ Jid.Parse(FaFaChatWin.owner.jid).server;
//       $(".group_style").removeAttr("style");
//       $(this).attr("style","background-color: PaleGoldenrod;");
       ChatMessage.currentNick = this.textContent.replace(/\s/g,"");
       ChatMessage.searchJid = jid;
       FaFaChatWin.ShowMsgContent(jid,"","",1);
     });
     
      $("#chatmsg_showgroup .webim_first_li").unbind("click").bind("click", function () {
        var parentClass=$(this.parentNode).attr("class");
        if (parentClass!="undefined" && parentClass!="")
            parentClass="#chatmsg_showgroup ."+parentClass;
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
   $("#chatmsg_showgroup").show();
};

//设置选择列表样式
FaFaChatWin.setListStyle = function(jid){
  //取消之前的选择样式
  var _jid = ChatMessage.searchJid;
  if(jid==_jid) return false;
  if (_jid!=""){
    _jid = _jid.replace(/\@|[.]/g,"");
    if(FaFaChatWin.isGroup(ChatMessage.searchJid)==false){
      var styles = $("#"+_jid).attr("style");
      if(styles!=null || typeof(styles)!="undefined"){
        if(styles.indexOf("display")==-1)
          $("#"+_jid).removeAttr("style");
        else{
          var array_list = styles.split(';');
          var new_style="";
          for(var i=0;i<array_list.length;i++){
            if(array_list[i].indexOf("background")==-1)
              new_style += array_list[i];
          }
          $("#"+_jid).attr("style",new_style);
        }
      }
    }
    else{
     $("#"+_jid).removeAttr("style");
    }
  }
  if(jid!=null){
    jid = jid.replace(/\@|[.]/g,"");
    $("#"+jid).css("background-color","PaleGoldenrod");
  }
};

//系统消息
FaFaChatWin.ShowSearchSystemMsg = function() {
  if($("#chatmsg_showsystem").children().length==0) {
    var html="<ul><li id='btn_business'>&nbsp;&nbsp;评论回复</li><li id='broadcast'>&nbsp;&nbsp;广播消息</li></ul>";
    $("#chatmsg_showsystem").append(html);
    
    $("#btn_business").unbind().bind("click",function(){
      FaFaChatWin.ShowMsgContent("admin@"+Jid.Parse(FaFaChatWin.owner.jid).server,"","",2);
    });
  }
  $("#discuss").unbind().bind("click",function(){
    FaFaChatWin.ShowMsgContent("","","",2);
  });
  $("#broadcast").unbind().bind("click",function(){
    FaFaChatWin.ShowMsgContent("","","",3);
  });  
};

FaFaChatWin.DisplayChatMsg = function(data){
   $("#webim_chatmsg_right").attr("class","webim_chatmsg_body_right");
   var html="";
   $("#webim_chatmsg_right").html("");
   var content=$("#textcontent").val();
   if(data.length==2){
     $("#chatPager").show();
     $("#chatmsg_export").attr("class","webim_chatmsg_tool_export");
     $("#delete_signle_id").attr("class","webim_chatmsg_tool_delete");
     $("#delete_doublue_id").attr("class","webim_chatmsg_tool_downdelete");
     $("#first_pager").attr("class","webim_chatmsg_first_pager");
     $("#prev_pager").attr("class","webim_chatmsg_prev_pager");
     $("#next_pager").attr("class","webim_chatmsg_pager_enable");
     $("#last_pager").attr("class","webim_chatmsg_pager_enable");
     
     $("#total_record").text(data[1]);
     var total_page = Math.ceil(data[1]/ChatMessage.everyPage);
     if(total_page<2)
       $("#chatPager").hide();
     else
       $("#chatPager").show();
     $("#total_paging").text(total_page);
     ChatMessage.currentPageIndex = total_page;
   }
   var ownerjid = Jid.Bear(FaFaChatWin.owner.jid);
   var nick=null,time=null,date=null,tempdate=null,msg=null,recordId=0;
   var rows = data[0].recordcount;
   for(var i=0;i<rows;i++){
     html = "";
     date = data[0].rows[i].ymd;
     nick = data[0].rows[i].fromnick;
     time = data[0].rows[i].time;
     msg =  data[0].rows[i].styletext;
     msgText = data[0].rows[i].msgtext;
     recordId = "msg"+data[0].rows[i].id;
     if(content!="" && msgText.indexOf(content)>-1){
        var newText = msgText.replace(content,"<span style='color:red;'>"+content+"</span>");
        msg = msg.replace(msgText,newText);
     }
     if (date != tempdate) {
       tempdate=date;
       html += "<div style='text-align:center'><img class='webim_chatmsg_line' src="+WebIM_domain+"/bundles/fafawebimimchat/images/line_left.png /><span style='font-size:12px;color:blue;float:left'>" + date + "</span><img class='webim_chatmsg_line' src="+WebIM_domain+"/bundles/fafawebimimchat/images/line_right.png /></div>";
     }
     if(ownerjid == data[0].rows[i].to) {
       html += "<div style='padding-bottom:5px;padding-left:5px;' class='webim_chatmsg_noselected' id='"+recordId+"'><div style='color:red;'>"+nick+"&nbsp;&nbsp;"+time+"</div>"+
               "<div style='margin-left:20px;'>"+msg+"</div></div>";
     }
     else {
       html += "<div style='padding-bottom:5px;padding-left:5px;' class='webim_chatmsg_noselected' id='"+recordId+"'><div>"+nick+"&nbsp;&nbsp;"+time+"</div>"+
               "<div style='margin-left:20px;'>"+msg+"</div></div>";
     }
     $("#webim_chatmsg_right").append(html);       
   }
   document.getElementById("webim_chatmsg_right").scrollTop=document.getElementById("webim_chatmsg_right").scrollHeight;
   $(".webim_chatmsg_noselected").live("click",function(){
     $(".webim_chatmsg_selected").attr("class","webim_chatmsg_noselected");
       ChatMessage.chatMsgId = this.id;
       $(this).attr("class","webim_chatmsg_selected");
   });
};

FaFaChatWin.send = function () {
    var msg = (this.msginputCtl.getSource());
    if (msg.replace(/&nbsp;/g,"").replace(/(^((<br \/>*))|((<br \/>)*)$)/gi, "").replace(/\s/g,"") == "") {
        FaFaChatWin.Hint("不能发送空消息", 3000);
        $('.webim_fafa_chat_input').val("");
        this.msginputCtl.focus();
        return;
    }
    msg = msg.replace(/(^((<br \/>*))|((<br \/>)*)$)/gi, "");
    if(msg.indexOf("log")==0)
    {
          FaFaChatWin.msglogCtl.html("");
          this.msginputCtl.setSource("");
          var cnt = msg.replace("log","").replace(" ","");
          cnt = (cnt==null ||cnt=="")?FaFaMessageLog.logs.length:cnt;
          //for( var i=FaFaMessageLog.logs.length-cnt*1;i<FaFaMessageLog.logs.length; i++)
          //   FaFaChatWin.msglogCtl.append(FaFaMessageLog.logs[i]+"<br>");
        return; 
    }
    if (FaFaMessage._conning) {
        FaFaChatWin.Hint("正在连接服务器，请稍后再试...", 3000);
        return;
    }
    if (FaFaMessage._conn == null || !FaFaMessage._conn.connected || FaFaChatWin.owner.state == "offline") {
        FaFaChatWin.Hint("连接已断开，您可能需要 <span style='color:blue;cursor:pointer;font-size:12px' onclick='FaFaMessage.RestartConn()'><u>重新连接</u> 服务器</span>", -1);
        return;
    }
    msg = msg.replace("alt","title");
    var msg2 = MessageUtil.DealFace(msg);
    //msg2 = FaFaChatWin.FormateMsg(msg2,false);
    if (FaFaChatWin.groupName.get(this.active.jid) != null) {
        var tojid = this.active.jid.replace(/gid/, '');
        FaFaChatWin.WriteChatMsg(tojid,Jid.Bear(this.owner.jid),this.active.name,this.owner.name,null,MessageUtil.ParseFaces(msg2),1,Jid.Bear(this.owner.jid),function(data){});
        tojid = tojid.split("@")[0];
        FaFaMessage.SendGroupMessage(this.owner.jid, tojid, msg2, this.owner.name == "" ? null : this.owner.name);
    }
    else {
       FaFaChatWin.WriteChatMsg(this.active.jid,Jid.Bear(this.owner.jid),this.active.name,this.owner.name,null,MessageUtil.ParseFaces(msg2),0,Jid.Bear(this.owner.jid),function(data){});
       FaFaMessage.Send(this.owner.jid, this.active.jid, (msg2), this.owner.name == "" ? null : this.owner.name);
    }    
    var now = new Date();
    var hour = now.getHours();
    hour = hour < 10 ? "0" + hour : "" + hour;
    var minute = now.getMinutes();
    minute = minute < 10 ? "0" + minute : "" + minute;
    var second = now.getSeconds();
    second = second < 10 ? "0" + second : "" + second;
    this.WriteMsg(this.active.jid, "<div>我 " + (hour + ":" + minute + ":" + second) + "<br> <div style='margin-left:20px;'>" +MessageUtil.ParseFaces(msg2)+ "</div></div>");
    this.msginputCtl.setSource("");
    this.msginputCtl.focus();
};

FaFaChatWin.WriteCookie = function(styles)
{
   var exdate=new Date();
   exdate.setDate(exdate.getDate() + 30);
   var jid = Jid.Bear(FaFaChatWin.owner.jid);
   var font_style = FaFaChatWin.ReadCookie(jid);
   if (font_style=="" || font_style==null)
     document.cookie = jid + "=" + escape(styles) +";expires="+exdate.toGMTString();
   else {
     var styleflag="";
     if (styles.indexOf("font-size")>-1)
        styleflag = "font-size";
     else if(styles.indexOf("font-family")>-1)
        styleflag = "font-family";
     else
        styleflag="color";
     if (font_style.indexOf(styleflag)==-1)  //不存在
       document.cookie = jid +"="+ escape(font_style+","+styles ) +";expires="+exdate.toGMTString();
     else  {
       var list = font_style.split(',');
       var newstyle = "";
       for(var i=0;i<list.length;i++) {
         if (list[i].indexOf(styleflag)>-1){
           newstyle += styles+","; }
         else
           newstyle += list[i]+",";
       }
       newstyle = newstyle.substring(0,newstyle.length-1);
       document.cookie = jid +"="+ escape(newstyle) + ";expires=" + exdate.toGMTString();
     }
   }
};

FaFaChatWin.ReadCookie = function(jid)
{
  var result = null;
  if (document.cookie.length>0)
  {
    var c_start = document.cookie.indexOf(jid + "=")
    if (c_start >-1)
    { 
       c_start = c_start + jid.length + 1;
       var c_end=document.cookie.indexOf(";",c_start)
       if (c_end==-1) c_end = document.cookie.length
       result = unescape(document.cookie.substring(c_start,c_end));
    }
  }
  return result; 
};

//格式化发送或接收消息
FaFaChatWin.FormateMsg = function(msg,isSetHeight)
{
  var ctrl = $(document.getElementById('xhe0_iframe').contentWindow.document.body);
  msg = "<span style=\"color:"+ctrl.css("color")+";font-family:"+ctrl.css("font-family")+";font-size:"+ctrl.css("font-size")+
        (isSetHeight?";line-height:"+ctrl.css("font-size"):"")+";\">"+Strophe.xmlescape(msg)+"</span>";
  return msg;
}

FaFaChatWin.TextStyle = function(_styles){
   var _style = _styles.split(',');
   var ctrl = $(document.getElementById('xhe0_iframe').contentWindow.document.body);
   for(var i=0; i<_style.length;i++)
   {
     if (_style[i].indexOf("font-size")>-1)
       ctrl.css("font-size",_style[i].split(':')[1]);
     else if (_style[i].indexOf("font-family")>-1)
       ctrl.css("font-family",_style[i].split(':')[1]);
     else if (_style[i].indexOf("color")>-1)
       ctrl.css("color",_style[i].split(':')[1]);
   }
};
   
    //将聊天信息写入数据库  
FaFaChatWin.WriteChatMsg=function (_tojid,_fromjid,_tonick,_fromnick,_date,_msg,_msgtype,_ownerjid,func){
        if(_fromjid.indexOf("guest")==0) return;//在线用户聊天不写日志 
      //将消息写入
      if(_date==null) {
        var now = new Date();
        var hour = now.getHours();
        _date = now.getHours() +":"+now.getMinutes()+":"+now.getSeconds();
      }
      var msg = "";
      try{
            _msg = MessageUtil.ParseFaces(_msg);
            msg = _msg;
            //纯文本聊天内容
            msg = $(_msg).length>0?$(_msg).text():_msg;
      }catch(e){}
      //判断设备资源
      if(_fromjid != _ownerjid){
        if(_fromjid.indexOf("FaFaWin")>-1)
           _msg += "<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自PC)</div>";
        else if(_fromjid.indexOf("FaFaAndroid")>-1)
           _msg += "<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自手机Android)</div>";
        else if(_fromjid.indexOf("FaFaIphone")>-1)
           _msg += "<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自手机Iphone)</div>";
        else if(_fromjid.indexOf("Ipad")>-1)
           _msg += "<div style='color:#999999;font-size:11px;padding-left:0px;'>(来自iPad)</div>";
      }
      var url = WebIM_domain+ "/api/wechatmsg/writemsg?jsoncallback=?";
      var parameter = {"tojid":_tojid,"fromjid":Jid.Bear(_fromjid),"tonick":_tonick,"fromnick":_fromnick,
                       "date":_date,"styletext":_msg,"msgtext":msg,"msgtype":_msgtype,"ownerjid":_ownerjid };
      $.getJSON(url,parameter,function(data){
         if(!data.succeed)
         {
            FaFaMessageError.push(_tojid+" writemsg("+msg+") fail");    
         }
      });
    };
    
    //查询
FaFaChatWin.searchChatMsg=function(_ownerjid,_otherjid,_date,_content,_msgType,func)
    {
      var url = WebIM_domain +"/api/wechatmsg/searchmsg?jsoncallback=?";
      var parameter = {"ownerjid":_ownerjid,"jid":_otherjid,"date":_date,"content":_content,"msgtype":_msgType};
      $.getJSON(url,parameter,function(data){
         func(data);
      });
    };
    
FaFaChatWin.pagingChatMsg=function(_ownerjid,_otherjid,_date,_content,_pageindex,func)
    {
      var url = WebIM_domain +"/api/wechatmsg/paging?jsoncallback=?";
      var parameter = {"ownerjid":_ownerjid,"jid":_otherjid,"date":_date,"content":_content,"pageindex":_pageindex};
      $.getJSON(url,parameter,function(data){
         func(data);
      });
    };
    
FaFaChatWin.ChatMsgReomveById=function(_ownerjid,_id){
       var url = WebIM_domain + "/api/wechatmsg/deleteById?jsoncallback=?";
       var parameter = {"ownerjid":_ownerjid,"id":_id};
       $.getJSON(url,parameter,function(data){
       });
    };