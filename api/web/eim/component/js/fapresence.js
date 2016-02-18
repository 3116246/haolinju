if (document.currentScript == null) {
  var scripts = document.getElementsByTagName("script");
  var reg = /fapresence([.-]\d)*\.js(\W|$)/i
  for (var i = 0, n = scripts.length; i < n; i++) {
    var src = !! document.querySelector ? scripts[i].src : scripts[i].getAttribute("src", 4);
    if (src && reg.test(src)) {
      currentScriptSrc = src;
      srcs = currentScriptSrc.split("/");
      break;
    }
  }
} else {
  currentScriptSrc = document.currentScript.src;
  srcs = currentScriptSrc.split("/");
}
var fapresence_domain = srcs[0] + "//" + srcs[2];


//FaFaPresence：wefafa用户出席状态感知组件
//默认自动订阅当前所在企业的所有员工出席状态
var FaFaPresence = {
  userList: new HashMap()
};
//向组件中添加一个新的需要实时感知其出席状态的用户帐号
FaFaPresence.AddBind = function(eleID, user) {
  if (!this.checker()) {
    setTimeout(function() {
      FaFaPresence.AddBind(eleID, user)
    }, 100);
    return;
  }
  if (eleID == null && user == null) {
    var ctl = $(".fafa_webim_ocs_presence");
    if (ctl.length == 0) return;
    for (var i = 0; i < ctl.length; i++) {
      user = $(ctl[i]).attr("account");
      if (user == "") continue;
      FaFaPresenceHelper.Subscribe(user);
    }
    return;
  }
  var ctl = $("#" + eleID);
  if (user == null || user == "")
    user = ctl.attr("account");

  if (user == "") return;
  FaFaPresenceHelper.Subscribe(user);
};

FaFaPresence.OnError = null; //错误处理事件
//OnReady:组件初始化。该事件总是在连接成功后自动触发。如果有自定义的初始化处理，必须委托给该事件。
FaFaPresence.OnReady = null;
//OnStateChange：订阅的人员在线状态发生改变时处理事件。
//通常情况下，系统都应把当前的在线状态实时的反应给操作用户。
FaFaPresence.OnStateChange = null;
//OnSubscribed：人员订阅成功后触发事件，组件会自动将订阅的人员信息回传给回调方法。
//未指定自定义处理过程时，采用组件预设的默认处理过程。
//如果指定了该处理事件，在事件中总是应该根据人员信息进行控件初始化展现处理，以及人员初始状态显示处理
FaFaPresence.OnSubscribed = null;
//单击事件.单击联系人事件。未委托自定义处理过程时，将默认采用wefafa webim聊天对话框
FaFaPresence.OnClick = null;
FaFaPresence.Disconnect = function() {
  FaFaMessage.Disconnect("manual");
}
FaFaPresence.Connection = function(appid, openid, token) {
  if (!FaFaPresence.isReady) {
    setTimeout(function() {
      //FaFaMessage.OAuth2Connection(appid, openid, token);
      FaFaPresence.Connection(appid, openid, token);
    }, 200);
    return;
  }
  var r = FaFaMessage.OAuth2Connection(appid, openid, token);
  if (r == null && this.OnError != null) {
    this.OnError(FaFaPresenceHelper.createErrorMsg("12", "连接消息服务器失败，请检查参数(appid,openid,token)是否正确."));
  }
}
//组件检查
FaFaPresence.checker = function() {
  if (typeof(FaFaMessage) == "undefined") {
    if (this.OnError != null) {
      this.OnError(FaFaPresenceHelper.createErrorMsg("10", "FaFaMessage 组件未找到"));
    }
    return false;
  }
  //if (FaFaMessage._conn == null) {
  //  if (this.OnError != null) {
  //    this.OnError(FaFaPresenceHelper.createErrorMsg("11", "消息服务器还未连接"));
  //  }
  //  return false;
  //}
  return true;
};
FaFaPresence.isReady = false;
//FaFaPresenceHelper：
//辅助操作对象
var FaFaPresenceHelper = {
  stateCache: new HashMap()
};
FaFaPresenceHelper.createErrorMsg = function(errNo, err) {
  return {
    "Type": "error",
    "Number": errNo,
    "Message": err
  };
};
FaFaPresenceHelper.Subscribe = function(user) {
  var v_tmp_roster =FaFaPresence.userList.get(user);
  if (v_tmp_roster == null) {
    FaFaEmployee.Subscribe(user, function(d) {
      var roster = FaFaPresence.userList.get(d.jid);
      if (roster == null)
        FaFaPresence.userList.put(d.jid, d);
      else {
        //用缓存的状态和设备资源替换联系人信息对应的属性
        d.state = roster.state;
        d.resource = roster.resource;
        FaFaPresence.userList.put(d.jid, d);
      }
      FaFaPresence.userList.put(user, d);
      if (FaFaPresence.OnSubscribed == null)
        FaFaPresenceHelper.createEle(d);
      else
        FaFaPresence.OnSubscribed(d);
    });
  }
  else
  {
      if (FaFaPresence.OnSubscribed == null)
        FaFaPresenceHelper.createEle(v_tmp_roster);
      else
        FaFaPresence.OnSubscribed(v_tmp_roster);
  }
}
FaFaPresenceHelper.regEvent = function() {
  if (!FaFaPresence.checker()) {
    setTimeout(function() {
      FaFaPresenceHelper.regEvent()
    }, 100);
    return;
  }
  //有出席到达
  FaFaMessage.GetPresence(function(pre) {
    var jidObj = Jid.Parse(pre.From);
    var fromJid = jidObj.user + "@" + jidObj.server;
    var v_roster = FaFaPresence.userList.get(fromJid);
    if (v_roster == null) {
      v_roster = new roster();
      v_roster.jid = fromJid;
    }
    try {
      if (pre.Type == "offline")
        v_roster.removeResource(jidObj.resource);
      else
        v_roster.addResource(jidObj.resource);
      var dev = Jid.Parse(v_roster.GetJid()).resource;
      if (dev != "") v_roster.state = "online";
      else v_roster.state = "offline";
      v_roster.show = pre.Show;
      v_roster.showDesc = pre.Status;
      FaFaPresence.userList.put(fromJid, v_roster);
      if (FaFaPresence.OnStateChange == null) FaFaPresenceHelper.changeState($(".fafa_webim_ocs_presence span[jid='" + fromJid + "']"), v_roster.state, v_roster.show, v_roster.showDesc, dev);
      else FaFaPresence.OnStateChange(v_roster);
    } catch (e) {}
    if (typeof(FaFaChatWin) == "object") FaFaChatWin.allJid = FaFaPresence.userList; //将当前联系人数据共享给聊天窗口
  });
  //连接过程处理
  FaFaMessage.ConnectionStateChange(function(state, info) {
    if (state == Strophe.Status.CONNECTED) {
      FaFaEnterprise.Subscribe(); //订阅当前企业
      FaFaPresence.AddBind(null, null); //自动绑定
      if (FaFaPresence.OnReady != null) FaFaPresence.OnReady();
    } else if (state > 3 && state != Strophe.Status.CONNECTED) //连接断开
    {
      FaFaPresence.userList = null;
      FaFaPresence.userList = new HashMap();
      if (info == "other-connectioned" || info == "conflict" || info == "manual") {
        return; //其他地方登录该帐号或者人为离线
      }
      //异常断开，自动登录
      setTimeout(function() {
        FaFaMessage.RestartConn(false);
      }, 100);
    }
  });
  FaFaPresence.isReady = true;
  //判断是否已经连接成功，是则立即处理ready过程
  if (FaFaMessage._conn != null && FaFaMessage._conn.connected)
    if (FaFaPresence.OnReady != null) FaFaPresence.OnReady();
};
FaFaPresenceHelper.createEle = function(info) {
    var $p = $(".fafa_webim_ocs_presence");
    //var ctls = $p.find("span[jid='" + info.jid + "']");
    //if (ctls.length == 0) {
    var defaultCss = "fafa_webim_ocs_offline2";
    //如果返回有openid信息，优先根据openid查询元素
    if (info.openid != null) {
      $(".fafa_webim_ocs_presence[account='" + info.openid + "']").html("<span jid='" + info.jid + "' class='fafa_webim_ocs_offline2'></span>&nbsp;<span class='fafa_webim_ocs_employee_name'>" + info.name + "</span>");
    }
    $(".fafa_webim_ocs_presence[account='" + info.email + "']").html("<span jid='" + info.jid + "' class='fafa_webim_ocs_offline2'></span>&nbsp;<span class='fafa_webim_ocs_employee_name'>" + info.name + "</span>");
    var v_roster = FaFaPresence.userList.get(info.jid);
    if (v_roster != null) {
      if (FaFaPresence.OnStateChange == null) FaFaPresenceHelper.changeState($p.find("span[jid='" + v_roster.jid + "']"), v_roster.state, v_roster.show, v_roster.showDesc, Jid.Parse(v_roster.GetJid()).resource);
      else FaFaPresence.OnStateChange(v_roster);
    }
    //}
};
FaFaPresenceHelper.changeState = function(ctl, type, show, status, dev) {
  if (type == "online") {
    if (dev == "FaFaAndroid") {
      ctl.attr("class", "fafa_webim_ocs_android");
      ctl.parent().attr("title", "Android设备在线");
    } else if (dev == "FaFaIPhone") {
      ctl.attr("class", "fafa_webim_ocs_ios");
      ctl.parent().attr("title", "Iphone设备在线");
    } else {
      if (show == "") {
        ctl.attr("class", "fafa_webim_ocs_online2");
        ctl.parent().attr("title", "在线");
      } else if (show == "dnd" && (status == "请勿打扰" || status == "会议中")) {
        ctl.attr("class", "fafa_webim_ocs_disturb2");
        ctl.parent().attr("title", "请勿打扰");
      } else if (show == "dnd") {
        ctl.attr("class", "fafa_webim_ocs_busy2");
        ctl.parent().attr("title", "忙碌");
      } else if (show == "away") {
        ctl.attr("class", "fafa_webim_ocs_leave2");
        ctl.parent().attr("title", "离开");
      }
    }
  } else if (type == "offline") {
    ctl.attr("class", "fafa_webim_ocs_offline2");
    ctl.parent().attr("title", "离线");
  }
  ctl.attr("state", type);
};
FaFaPresenceHelper.loadOcsCss = function() {
  var oHead = document.getElementsByTagName('HEAD').item(0);
  var cssTag = document.createElement('link');
  cssTag.setAttribute('rel', 'stylesheet');
  cssTag.setAttribute('type', 'text/css');
  cssTag.setAttribute('href', fapresence_domain + '/eim/component/css/fafapresence.css');
  oHead.appendChild(cssTag);
};

$(document).ready(function() {
  FaFaPresenceHelper.loadOcsCss();
  FaFaPresenceHelper.regEvent();
  $(".fafa_webim_ocs_presence").on("click", function() {
    if (FaFaPresence.OnClick != null) {
      FaFaPresence.OnClick(this);
      return;
    }
    var jid = $(this).find("span").attr("jid");
    //判断中否引用并加载了聊天组件fachat_window.js
    if (typeof(FaFaChatWin) != "object" || jid == "") {
      return;
    }
    FaFaChatWin.init();
    FaFaChatWin.owner = FaFaPresence.userList.get(Jid.Bear(FaFaChatWin.owner.jid));
    FaFaChatWin.ShowRoster(jid);
  });
  //如果已连接，则自动绑定帐号
  if(typeof(FaFaMessage)=="object" && FaFaMessage._conn!=null && FaFaMessage._conn.connected)
  {
      FaFaPresence.AddBind(null, null); //自动绑定
  }

});