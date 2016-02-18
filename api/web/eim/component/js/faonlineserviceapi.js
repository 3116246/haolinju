
function _fa(id) {
    return document.getElementById(id);
}
var os=null;
function OnlineService(To) {
    os=this;
    this.version = "1.0";
    this.Eno = ""; //企业号
    this.From = "";
    this.ToJid = To;
    this._chat_rosters = new HashMap();
    this.Server = ""; //服务器.未设置时采用默认服务器
    this.html = ['   <div style="position: absolute; width: 34px; height: 25px; z-index: 7; left: 1px; top: 1px;line-height:25px;text-align:center;background-image:url(http://fafaim.com/images/login/favicon.ico);background-size:24px 24px;background-repeat:no-repeat;" id="webim_icon"></div>',
                '    <div style="position: absolute; width: 567px; height: 318px; z-index: 1; left: 1px; top: 29px; border: 1px solid #000080; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px" id="webim_content">',
		        '        <div style="position: absolute; width: 563px; height: 62px; z-index: 1; left: 0px; top: 1px" id="webim_roster">',
			    '            <div style="position: absolute; width: 67px; height: 54px; z-index: 1; left: 3px; top: 4px; border: 1px solid #008080; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px" id="webim_roster_headpic">头像</div>',
			    '            <div style="position: absolute; width: 463px; height: 22px; z-index: 2; left: 97px; top: 4px" id="webim_roster_name">用户名称 个性签名</div>',
			    '            <div style="position: absolute; width: 463px; height: 24px; z-index: 3; left: 97px; top: 31px" id="webim_roster_info">公司名称</div>',
		        '        </div>',
		        '        <div style="position: absolute; width: 557px; height: 126px; z-index: 2; left: 4px; top: 67px; border: 1px solid #00FFFF; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px; background-color: #FFFFFF;overflow:auto;display:none" id="webim_chat_log"></div>',
		        '        <div style="position: absolute; width: 565px; height: 24px; z-index: 3; left: 0px; top: 197px; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px; background-color: #CCCCCC" id="webim_chat_tool"></div>',
		        '        <div style="position: absolute; width: 480px; height: 87px; z-index: 4; left: 4px; top: 226px; border: 1px solid #00FFFF; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px; background-color: #FFFFFF" id="webim_chat_input"></div>',
		        '        <div style="position: absolute; width: 62px; height: 87px;line-height:87px; z-index: 5; left: 500px; top: 226px; border-style: solid; border-width: 1px; padding-left: 4px; padding-right: 4px; padding-top: 1px; padding-bottom: 1px;overflow:auto" id="webim_btn_send">发送</div>',
	            '    </div>',
                '    <div style="position: absolute; width: 229px; height: 30px; z-index: 4; left: 304px; top: 220px;display:none" contentEditable=true id="webim_chat_hint"></div>',
	            '    <div style="position: absolute; width: 476px; height: 25px;line-height:25px; z-index: 2; left: 37px; top: 3px; background-color:#808080" id="webim_tab"></div>',
	            '    <div style="position: absolute; width: 21px; height: 24px; z-index: 3; left: 519px; top: 3px" id="webim_btn_min">—</div>',
	            '    <div style="position: absolute; width: 21px; height: 24px; z-index: 3; left: 546px; top: 3px" id="webim_btn_close">X</div>',
                '    <div style="position: absolute; width: 580px; height: 26px; z-index: 6; left: 1px; top: 355px;background-color:#CCCCCC" id="webim_status"></div>',
                '    <div style="position: absolute; width: 574px; height: 320px; z-index: 5; left: 2px; top: 30px; background-color:#FFFFFF;line-height:320px;text-align:center" id="webim_connecting">'
                ];
    //显示聊天窗口:chatinput.setAttribute("contentEditable", true);
    //args包括需要显示的单位名称（orgname）、联系人名称(fn)、昵称(nickname)、位置（location:默认为窗口正中间）、大小(size:默认为500，300)等信息
    this.Show = function (args) {
        if (document.getElementById("webim_window") == null) {
            var chat = document.createElement("DIV");
            chat.id = "webim_window";
            with (chat.style) {
                position = "absolute";
                top = "50%";
                left = "50%";
                margin = "-235px 0 0 -190px";
                width = "580px";
                height = "381px";
                border = "1px solid #c0c0c0";
                zIndex = 10000;
                backgroundColor = "#c0c0c0";
            }
            if (args != null && args.location != null) {
                chat.style.left = args.location.X;
                chat.style.top = args.location.Y;
            }
            document.body.appendChild(chat);
            chat.innerHTML = this.html.join("");
            document.getElementById("webim_chat_input").setAttribute("contentEditable", true);
            document.getElementById("webim_btn_close").onclick = function () {
                var obj = os;
                if (obj.Tab.Tabs.count() == 0) {
                    obj.Disconnect();
                }
                else {
                    obj.Tab.Remove(obj.ToJid);
                }
            }
            document.getElementById("webim_btn_send").onclick = function () {
                var obj = os;
                var msg = document.getElementById("webim_chat_input").innerHTML;
                if (msg.replace(/ /g, "") == "") {
                    document.getElementById("webim_chat_hint").style.display = '';
                    document.getElementById("webim_chat_hint").innerHTML = "不能发送空消息";
                    window.setTimeout("document.getElementById('webim_chat_hint').style.display = 'none'", 1000);
                    return;
                }
                obj.Send(msg);
            }
        }
        if (args == null || args.data == null) {
            document.getElementById("webim_connecting").style.display = '';
            document.getElementById("webim_connecting").innerHTML = 'now AD time!!!';
            document.getElementById("webim_status").innerHTML = "正在连接服务器...";
        }
        else {
            //个人信息数据获取成功
            var tab_obj = document.getElementById("webim_tab_" + args.jid);
            if (tab_obj != null) {
                document.getElementById("webim_tab_" + args.jid).innerHTML = "<b>" + args.data.fn + "</b>";
                var ns = document.getElementsByName('r_'+ args.jid);
                if (ns.length > 0) {for (var t1 in ns) {t1.innerHTML = args.data.fn;t1.id = '';}}
                
            }
            document.getElementById("webim_roster_name").innerHTML = "<a>" + args.data.fn + "</a>";
            document.getElementById("webim_roster_info").innerHTML = "<a>" + args.data.orgname + "</a>";
            //document.getElementById("webim_chat_log").innerHTML = "<span style='color:#c0c0c0'>您好，" + args.data.orgname + "<a>" + args.data.fn + "</a>正在为您服务，请问能有什么帮您的吗？</span>";
            document.getElementById("webim_connecting").style.display = "none";
            document.getElementById("webim_status").innerHTML = "完成";
        }
    }
    //连接服务器。登录名不能是公共帐号
    this.Connection = function (OrgId, U, P) {
        if (Strophe == null || FaFaMessage == null || OrgId == null || U == null || P == null)
            return;
        if (OrgId.replace(/ /g, "") == "" || U.replace(/ /g, "") == "" || P.replace(/ /g, "") == "") return;
        var _u = Jid.Parse(U).user;
        if (_u == "service" || _u == "sale" || _u == "front" || _u == "admin") return;
        this.Eno = OrgId;
        this.From = U;
        FaFaMessage.ConnectionStateChange(function (status, info) {
            if (status == 5) {
                document.getElementById("webim_status").innerHTML = "正在获取信息...";
                //获取企业信息
                FaFaEnterprise.GetInfo(To, function (data) {
                    os.Tab.Tabs.put(To, { jid: To, "data": data });
                    os.Tab.Add(To);
                    os.Tab.Focus(To);
                });
            }
            else if (status == 4) {
                document.getElementById("webim_connecting").style.display = '';
                document.getElementById("webim_connecting").innerHTML = 'now AD time!!!';
                document.getElementById("webim_status").innerHTML = "连接已断开,用户帐号或者密码不正确";
            }
            else if (status == 6) {
                document.getElementById("webim_connecting").style.display = '';
                document.getElementById("webim_connecting").innerHTML = 'now AD time!!!';
                document.getElementById("webim_status").innerHTML = "连接已断开，您需要<a href='javascript:'>重新连接</a>";
            }
        });
        FaFaMessage.GetMessage(function (msg) {
            if (os.GetMessage != null) os.GetMessage(msg);
        });
        FaFaMessage.RosterStateChange(function (msg) {
            if (os.RosterStateChange != null) os.RosterStateChange(msg);
        });
        if (this.Server != "") FaFaMessage.Server = this.Server;
        FaFaMessage.Connection(U, P);
    }
    this.Disconnect = function () {
        FaFaMessage.Disconnect();
        document.getElementById("webim_window").innerHTML = "";
        document.getElementById("webim_window").removeNode(true);
    }
    this.GetMessage = function (msg) {
        var jid = msg.From.user + "@" + msg.From.server;
        var userInfo = os.Tab.Tabs.get(jid);
        os.Tab.Add(jid);
        document.getElementById("webim_chat_log_" + jid).innerHTML += "<div>&nbsp;&nbsp;" + (userInfo != null ? userInfo.data.fn : ("<span name='r_" + jid + "'>" + msg.From.user + "</span>")) + "(" + msg.Time.toLocaleTimeString() + ")&nbsp;&nbsp;说：</div><div>" + (msg.Body.text || msg.Body.textContent) + "</div>";
        if (os.ToJid != jid) os.Tab.Flash(jid);
    };
    ///发送消息.消息内容长度不能超过300
    this.Send = function (Msg) {
        FaFaMessage.Send(this.From, this.ToJid, Msg);
        var d = new Date();
        document.getElementById("webim_chat_log_" + this.ToJid).innerHTML += "<div>&nbsp;&nbsp;我" + (new Date().toLocaleTimeString()) + "说：</div><div>" + Msg + "</div>";
        document.getElementById("webim_chat_input").innerHTML = "";
    }
    this.Tab = {
        webCtl: null,
        owner: this,
        Tabs: new HashMap(),
        Add: function (toJid) {
            var tabTmp = document.getElementById("webim_tab_" + toJid);
            if (this.owner.ToJid == toJid && tabTmp != null) return;
            //for (var key in this.Tabs.keySet()) {
            //    var ctl = document.getElementById("webim_chat_log_" + key);
            //    if (ctl != null) ctl.style.display = 'none';
            //}
            //this.owner.ToJid = toJid;
            if (this.webCtl == null)
                this.webCtl = document.getElementById("webim_tab");
            if (tabTmp == null) {
                tabTmp = document.createElement("span");
                tabTmp.id = "webim_tab_" + toJid;
                tabTmp.style.cssText = "cursor:pointer";
                this.webCtl.appendChild(tabTmp);
                tabTmp.innerHTML = "<b>" + toJid + "</b>";
                tabTmp.onclick = function () {
                    os.Tab.Focus(this.id.replace("webim_tab_", ""));
                }
                var chatLog = document.getElementById("webim_chat_log").cloneNode(true);
                chatLog.innerHTML = "";
                chatLog.style.display = 'none';
                chatLog.id = "webim_chat_log_" + toJid;
                document.getElementById("webim_chat_log").parentNode.appendChild(chatLog);
            }
            var toInfo = this.Tabs.get(toJid);
            if (toInfo == null) {
                //获取个人信息
                FaFaEnterprise.GetInfo(toJid, function (data) {
                    os.Tab.Tabs.put(toJid, { jid: toJid, "data": data });
                    os.Tab.Focus(toJid);
                    //os.Tab.Flash(toJid);
                });
            }
            else {
                //os.Show(toInfo);
                //var chatLog = document.getElementById("webim_chat_log_" + toJid);
                //if (chatLog != null) chatLog.style.display = '';
            }
        },
        Remove: function (toJid) {
            var tabTmp = document.getElementById("webim_tab_" + toJid);
            if (tabTmp != null) {
                tabTmp.parentNode.removeChild(tabTmp);
                document.getElementById("webim_chat_log_" + toJid).style.display = 'none';
                this.owner.ToJid = null;
            }
        },
        Focus: function (toJid) {
            this.owner.ToJid = toJid;
            var _flashTimer = this._timerHander[toJid];
            if (_flashTimer != null) {
                clearTimeout(_flashTimer);
                this._timerHander[toJid] = null;
                document.getElementById("webim_tab_" + toJid).style.color = "#000000";
            }
            for (var key in this.Tabs.keySet()) {
                var ctl = document.getElementById("webim_chat_log_" + key);
                if (ctl != null) ctl.style.display = 'none';
            }
            document.getElementById("webim_chat_log_" + toJid).style.display = '';
            this.owner.Show(this.Tabs.get(toJid));
        },
        Flash: function (jid) {
            var ctl = document.getElementById("webim_tab_" + jid);
            if (ctl == null || this._timerHander[jid] != null) return;
            var _timer = setTimeout("os.Tab._falshprocess('" + jid + "')", 500);
            this._timerHander[jid] = _timer;
        },
        _falshprocess: function (jid) {
            if (this._timerHander[jid] == null) return;
            var ctl = document.getElementById("webim_tab_" + jid);
            var froeColor = ctl.style.color;
            ctl.style.color = froeColor == "rgb(0, 0, 0)" ? "#ffffff" : "#000000";
            setTimeout("os.Tab._falshprocess('" + jid + "')", 500);
        },
        _timerHander: []
    }
}
if (window.OnlineServiceAPI.init != null) window.OnlineServiceAPI.init();

