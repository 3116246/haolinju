var FaFaConnInterval = 2000,_WEFAFA_APPID="";
var srcs = [];
if (document.currentScript == null) {
    var scripts = document.getElementsByTagName("script");
    var reg = /faapi([.-]\d)*\.js(\W|$)/i
    for (var i = 0, n = scripts.length; i < n; i++) {
        var src = !! document.querySelector ? scripts[i].src : scripts[i].getAttribute("src", 4);
        if (src && reg.test(src)) {
            srcs = src.split("/");
            break;
        }
    }
} else {
    srcs = document.currentScript.src.split("/");
}
var mydomain = srcs[0] + "//" + srcs[2];
var d_d = {
    d: function(v) {
        return v
    }
};

function hashCode(str) {
    var hash = 1315423911,
        i, ch;
    for (i = str.length - 1; i >= 0; i--) {
        ch = str.charCodeAt(i);
        hash ^= ((hash << 5) + ch + (hash >> 2));
    }
    return (hash & 0x7FFFFFFF);
};

function reconvert(str) {
    str = str.replace(/%/g, "\\").replace(/(\\u)(\w{4}|\w{2})/gi, function($0, $1, $2) {
        return String.fromCharCode(parseInt($2, 16));
    })
    return str;
};
var FaFaMessageError = [];

function Api_AccessToKen()
{
	
}
/**
 * 联系人对象
 */

function roster() {
    this.jid = "";
    this.resource = [];
    this.name = "";
    this.ename = "";
    this.desc = "";
    this.dept = "";
    this.duty = "";
    this.mobile = "";
    this.phone = "";
    this.sex = "女";
    this.email = "";
    this.photo = mydomain + "/eim/component/images/no_photo.png";
    this.isLoad = false; //是否已加载详细数据
    this.state = "offline";
    this.show = "";
    this.showDesc = "";
    this.GetJid = function(_resource) {
        if (this.jid == "") return "";
        var _jid = Jid.Parse(this.jid);
        if (_jid.resource != "") return this.jid;
        if (_resource != null && _resource != "") return this.jid + "/" + _resource;
        if (this.resource.length == 1) return this.jid + "/" + this.resource[0];
        if (typeof(this.resource) != "string") {
            if (this.resource.join(",").indexOf("Win") > -1)
                return this.jid + "/Win";
            else if (this.resource.join(",").indexOf("Android") > -1)
                return this.jid + "/Android";
            else if (this.resource.join(",").indexOf("IPhone") > -1)
                return this.jid + "/IPhone";
            else if (this.resource.join(",").indexOf("iPad") > -1)
                return this.jid + "/iPad";
            else if (this.resource.join(",").indexOf("Web") > -1)
                return this.jid + "/Web";
        }
        return this.jid + ((this.resource != null && this.resource.length > 0) ? "/" + this.resource[0] : "");
    };
    this.addResource = function(source) {
        if (source == null || source == "") return;
        if (this.resource == null || this.resource.length == 0)
            this.resource = [source];
        else {
            var re = typeof(this.resource) == "string" ? this.resource : this.resource.join(",");
            if (re.indexOf(source) > -1) return;
            if (typeof(this.resource) == "string")
                this.resource = [this.resource, source];
            else
                this.resource.push(source);
        }
    };
    this.removeResource = function(source) {
        if (this.resource == null || this.resource.length == 0) return;
        var re = typeof(this.resource) == "string" ? this.resource : this.resource.join(",");
        if (re.indexOf(source) == -1) return;
        this.resource = re.replace(source, " ").replace(/ ,|, | /g, "");
        if (this.resource == "") this.resource = [];
        else this.resource = this.resource.split(",");
    }
};
/**
 *   fafa 消息接口
 */
var Jid = {
    /*
     * 解析jid。返回Jid对象
     */
    Parse: function(jidStr) {
        if (jidStr == null || jidStr.replace(/ /g, "") == "") return null;
        var r = jidStr.split(/[@|\/]/g);
        switch (r.length) {
            case 1:
                return {
                    user: r[0],
                    server: "justsy.com",
                    resource: "",
                    Bear: function() {
                        return this.user + "@" + this.server
                    }
                };
            case 2:
                return {
                    user: r[0],
                    server: r[1],
                    resource: "",
                    Bear: function() {
                        return this.user + "@" + this.server
                    }
                };
            case 3:
                return {
                    user: r[0],
                    server: r[1],
                    resource: r[2],
                    Bear: function() {
                        return this.user + "@" + this.server
                    }
                };
            default:
                return null;
        }
    },
    Bear: function(jidStr) {
        if (jidStr == null || jidStr.replace(/ /g, "") == "") return null;
        var r = jidStr.split(/[@|\/]/g);
        switch (r.length) {
            case 1:
                return r[0] + "@justsy.com";
            case 2:
            case 3:
                return r[0] + "@" + r[1];
            default:
                return null;
        }
    },
    toString: function(obj) {
        if(obj==null) return "";
        if (typeof(obj) == "string") return obj;
        else {
            var r = [obj.user];
            if (obj.server != "") {
                r.push(obj.server);
                if (obj.resource != "") {
                    return r.join("@") + "/" + obj.resource;
                } else
                    return r.join("@");
            } else
                return obj.user + "@justsy.com";
        }
    }
};
var FaFaMessage = {
    /*
     * 设置连接的服务器。默认为fafaim.com。非特殊说明不应修改该属性值
     */
    Server: "justsy.com",
    Resource: "Web",
    _conning: false,
    _conn: null,
    _jid: "",
    _t1: "",
    _user:"",
    _p:"",
    _rlist: [],
    _show: "", //连接时指定的上线状态
    isAttached: false,
    fomratString: function(txt) {
        return txt; //(typeof(Strophe.unxmlescape)=="function" ? Strophe.unxmlescape(txt):txt).replace(/(<|&lt;)/g, "＜").replace(/(>|&gt;)/g, "＞").replace(/(&amp;|&)/g, "＆").replace(/%/g, "％").replace(/\?/g, "？").replace(/#/g, "＃").replace(/\'/g, "＇").replace(/\"/g, "＂").replace(/\+/g, "＋");
    },
    fomratHTML: function(txt) {
        return (txt).replace(/(＜|&lt;)/g, "<").replace(/(＞|&gt;)/g, ">").replace(/&apos;/g, "'").replace(/&quot;/g, "\"").replace(/(＆|&amp;)/g, "&").replace(/％/g, "%").replace(/\？/g, "?").replace(/＃/g, "#");
    },
    //获取联系人列表
    GetRoster: function() {
        this._rlist = [];
        var iq = $iq({
            type: 'get'
        }).c('query', {
            xmlns: Strophe.NS.ROSTER
        });
        this._conn.sendIQ(iq, this.onRoster);
    },
    //返回当前是否可以进行连接操作
    CanConnection: function() {
        return !this._conning;
    },
    _onMessageHander: [],
    _onIQHander: [],
    _onRosterStateChange: [],
    _onConnectionStateChange: [],
    _onRosterAfter: [],
    GetMessage: function(func) {
        this._onMessageHander.push(func)
    }, //收到消息时的处理方法
    GetIQ: function(func) {
        this._onIQHander.push(func)
    }, //
    ConnectionStateChange: function(func) {
        this._onConnectionStateChange.push(func)
    }, //连接状态变化处理事件
    GetPresence: function(func) {
        this._onRosterStateChange.push(func)
    }, //联系人状态变化处理事件 
    RosterAfter: function(func) {
        this._onRosterAfter.push(func)
    }, //联系人获取事件
    Disconnect: function(reason) {
        try {
            FaFaMessageLog.unshift("Disconnect!  clearTimeout->" + keepConnectionTimer);
            FaFaMessageLog.unshift("Disconnect!  Old SID->" + FaFaMessage._conn.sid);
            clearTimeout(keepConnectionTimer);
            FaFaMessageLog.unshift("Disconnect!  clear timedHandlers Count->" + FaFaMessage._conn.timedHandlers.length);
            for (var i = 0; i < FaFaMessage._conn.timedHandlers.length; i++) {
                clearTimeout(FaFaMessage._conn.timedHandlers);
            }
            for (var i = 0; i < FaFaMessage._conn.addTimeds.length; i++) {
                clearTimeout(FaFaMessage._conn.addTimeds);
            }
        } catch (e) {}
        document.cookie = this._jid + "=;expires=0";
        if(this._conn!=null)
        {
	        this._conn.disconnect(reason);
	        this._conn = null;
	        this._conning = false;
    	}
    },
    RestartConn: function(show) {
        if (this._conn != null) {
            FaFaMessageLog.unshift("RestartConn!  clearTimeout->" + keepConnectionTimer);
            FaFaMessageLog.unshift("RestartConn!  Old SID->" + this._conn.sid);
            clearTimeout(keepConnectionTimer);
            FaFaMessageLog.unshift("RestartConn!  clear timedHandlers Count->" + this._conn.timedHandlers.length);
            try {
                for (var i = 0; i < this._conn.timedHandlers.length; i++) {
                    clearTimeout(this._conn.timedHandlers[i]);
                }
            } catch (e) {}
            try {
                for (var i = 0; i < this._conn.addTimeds.length; i++) {
                    clearTimeout(this._conn.addTimeds[i]);
                }
            } catch (e) {}
            this._conning = false;
            this._conn.connected = false;
            //this._conn._onDisconnectTimeout();
            this._conn = null;
        }
        document.cookie = this._jid + "=;expires=0";
        this.Connection(this._user, this._p, show);
    },
    OAuth2Connection: function(appid,openid,token) {
        if (this._conning) return true;
        if (this._conn != null && appid != _WEFAFA_APPID) return true;
        _WEFAFA_APPID = appid;
        var info =(openid==null|| token==null) ? GetAuth2Info(_WEFAFA_APPID) : {"access_token":token,"openid":openid,"appid":_WEFAFA_APPID};
        if (info == null) return null;
        var s1 = [mydomain, "/api/http/sendjid", "?", "access_token", "=", info.access_token, "&", "openid", "=", info.openid, "&jsoncallback=?"];
        $.getJSON(s1.join(""), function(r) {
            if(r.s==0) return; //token过期
            //根据token和openid获取用户jid信息
            document.cookie = "fa"+appid+"="+openid+"="+info.access_token;
            FaFaMessage.Connection(d_d.d(r.qa), d_d.d(r.xs));
        });
        return true;
    },
    Connection: function(User, P, show) {
        if (this._conning) return;
        if (this._conn == null || User != this._jid) {
            this._show = show;
            this._conning = true;
            this._user = User;
            this._p = P;
            //获取当前协议
            this._conn = new Strophe.Connection(document.location.protocol == "https:" ? Apis.get("Im-Server-https") : Apis.get("Im-Server-http"));
            this._t1 = P;
            window.setTimeout("FaFaMessage._conning=false", FaFaConnInterval); //防止连续提交连接请求
            //判断连接是否还有效，有效则不新建连接
            var arrStr = document.cookie.split("; ");
            for (var i = 0; i < arrStr.length; i++) {
                var temp = arrStr[i].split("=");
                if (temp[0] == this._jid) {
                    if (temp.length == 1 || temp[1] == "") break;
                    var sid = temp[1].split("|");
                    if (sid[0] == "" || sid[1] == "" || sid[0] == "null" || sid[1] == "null") break;
                    this._conn.attach(this._jid, sid[0], sid[1] * 1, this.onConnection);
                    return;
                }
            }
            var s1 = [mydomain, "/interface/logincheck", "?", "login_account", "=", User, "&", "password", "=", P, "&jsoncallback=?"];
        
            $.getJSON(s1.join(""),{},function(data){
                if(data.returncode!="0000") return null;
                FaFaMessage._jid = data.jid.indexOf("/") == -1 ? data.jid + "/" + FaFaMessage.Resource : data.jid;
                FaFaMessage._t1 = data.des;
                FaFaMessage._conn.connect(FaFaMessage._jid, FaFaMessage._t1, FaFaMessage.onConnection);
            });            
        }
        return this._conn;
    },
    onConnection: function(status, info) {
        if (status == Strophe.Status.DISCONNECTING) return;
        if (status == Strophe.Status.CONNECTED || status == Strophe.Status.ATTACHED) {
            FaFaMessageLog.unshift("The CONNECTED!  New SID->" + FaFaMessage._conn.sid);
            //委托事件处理
            FaFaMessage._conn.addHandler(FaFaMessage.onMessage, null, 'message', null, null, null);
            FaFaMessage._conn.addHandler(FaFaMessage.onPresence, null, "presence");
            FaFaMessage._conn.addHandler(FaFaMessage.onIQ, null, 'iq', null, null, null);
            //发送出席
            FaFaMessage.isAttached = false;
            if (status == Strophe.Status.CONNECTED) {
                if (this._show != null && this._show != "")
                    FaFaMessage._conn.send($pres().c("Priority", "1").up().c("show", this._show).tree());
                else
                    FaFaMessage._conn.send($pres().c("Priority", "1").tree());
                FaFaMessage.GetRoster();
            } else {
                FaFaMessage.isAttached = true;
                FaFaMessage._conn.send($pres().tree());
                //获取联系人
                setTimeout(function() {
                    FaFaMessage.GetRoster()
                }, 200);
            }

            status = Strophe.Status.CONNECTED;
        }
        if (FaFaMessage._onConnectionStateChange != null && FaFaMessage._onConnectionStateChange.length > 0) {
            for (var fun in FaFaMessage._onConnectionStateChange) {
                try {
                    FaFaMessage._onConnectionStateChange[fun](status, info);
                } catch (e) {
                    FaFaMessageError.push(e);
                }
            }
        }
    },
    onIQ: function(iq) {
        try {
            var to = iq.getAttribute('to');
            var from = iq.getAttribute('from');
            var type = iq.getAttribute("type");
            if (type == "error") {
                FaFaMessageError.push(iq);
                return;
            }
            var _d = {
                From: from,
                Type: type,
                To: to,
                tagName: (iq.firstChild == null ? "" : iq.firstChild.tagName),
                Body: iq.firstChild
            };
            if (FaFaMessage._onIQHander != null && FaFaMessage._onIQHander.length > 0) {
                for (var fun in FaFaMessage._onIQHander) {
                    try {
                        FaFaMessage._onIQHander[fun](_d);
                    } catch (e) {
                        FaFaMessageError.push(e);
                    }
                }
            }
        } catch (e) {
            FaFaMessageError.push(e);
        }
        return true;
    },
    onPresence: function(pre) {
        try {
            var to = pre.getAttribute('to');
            var from = pre.getAttribute('from');
            var type = pre.getAttribute("type");
            var $pre = $(pre);
            if ($pre.attr("type") == "error") {
                FaFaMessageError.push(pre);
                return;
            }
            type = type == "unavailable" ? "offline" : "online";
            if ($pre.find("hasofflinefile").length > 0)
                type = "hasofflinefile";
            var show = $pre.find("show").text();
            var status = $pre.find("status").text();
            var signature = $pre.find("signature").text();
            var _d = {
                From: from,
                Type: type,
                Show: show,
                Signature: signature,
                Status: status,
                To: to,
                Body: pre.firstChild
            };
            if (FaFaMessage._onRosterStateChange != null && FaFaMessage._onRosterStateChange.length > 0) {
                for (var fun in FaFaMessage._onRosterStateChange) {
                    try {
                        FaFaMessage._onRosterStateChange[fun](_d);
                    } catch (e) {
                        FaFaMessageError.push(e);
                    }
                }
            }
        } catch (e) {
            FaFaMessageError.push(e);
        }
        return true;
    },
    onRoster: function(rosterItem) {
        try {
            var _item = rosterItem;
            var from = _item.getAttribute('from');
            if (_item.firstChild != null && _item.firstChild.tagName == "query") {
                var rosters = _item.firstChild.childNodes;
                for (var i = 0; i < rosters.length; i++) {
                    var groupText = rosters[i].firstChild != null ? (rosters[i].firstChild.text || rosters[i].firstChild.textContent) : "";
                    FaFaMessage._rlist.push({
                        From: from,
                        Group: groupText,
                        Subscription: rosters[i].getAttribute('subscription'),
                        Jid: rosters[i].getAttribute('jid'),
                        name: rosters[i].getAttribute('name'),
                        item: rosters[i].xml
                    });
                }
            }
            if (FaFaMessage._onRosterAfter != null && FaFaMessage._onRosterAfter.length > 0) {
                for (var fun in FaFaMessage._onRosterAfter) {
                    try {
                        FaFaMessage._onRosterAfter[fun](FaFaMessage._rlist);
                    } catch (e) {
                        FaFaMessageError.push(e);
                    }
                }
            }
            //如果是直接附加的连接，需要重新发送获取出席iq
            if (FaFaMessage.isAttached) {
                var iq = $iq({
                    from: FaFaMessage._jid,
                    type: 'get'
                }).c('online', {
                    xmlns: 'http://im.private-en.com/namespace/userstate'
                }).tree();
                FaFaMessage._conn.send(iq);
            }
        } catch (e) {
            FaFaMessageError.push(e);
        }
        return true;
    },
    onMessage: function(msg) {
        var msgtype = 0; //消息类型
        try {
            var $msg = $(msg),
                to = '',
                from = '',
                isBusiness = '',
                type = '',
                elems = '',
                nick = '',
                _tm = '';
            if ($msg.attr("type") == "error") {
                FaFaMessageError.push(msg);
                return;
            }
            from = msg.getAttribute('from');
            var FromJid = Jid.Parse(from);
            isBusiness = $msg.find("business");
            if ($msg.find("groupchat").length > 0) { //群消息
                if ($msg.find("groupchat html").length > 0) {
                    try {
                        var bodyEle = $msg.find("groupchat html body")[0];
                        elems = bodyEle.innerHTML || bodyEle.outerHTML || bodyEle.xml || bodyEle.text;
                    } catch (e) {}
                }
                if (elems == null || elems == "") elems = $msg.find("groupchat text").text();
            } else if (msg.lastChild.tagName == "html" || msg.lastChild.tagName == "x") { //一般消息
                if ($msg.find("html body").length > 0) {
                    try {
                        elems = $msg.find("html body").html();
                    } catch (e) {}
                }
            }
            elems = elems.replace("＜", "<").replace("＞", ">").replace(/＂/g, "\"").replace(/＇/g, "'");
            if (elems.replace(/\s/g, '').indexOf("/") != 0) {
                try {
                    var $elems = $(elems);
                    if ($elems.length == 1)
                        elems = ($elems.length > 0 && $elems[0].tagName == "P") ? $elems.html() : elems;
                    else {
                        for (var j = 0; j < $elems.length; j++) {
                            if ($($elems[j]).text() != "") {
                                elems = $elems[j].outerHTML;
                                break;
                            }
                        }
                    }
                } catch (ex) {}
            }
            if (elems == null || elems == "") elems = $msg.find("body:eq(0)").text() || "";
            elems = reconvert(isBusiness.length > 0 ? isBusiness.find("body").html() : elems);
            var MessageType = $msg.children("type").text();
            //if(FromJid.resource.indexOf("FaFaWeb")==0)  //如果是web发送的消息，为了正确解析出样式定义标签和消息体中的html内容标签，需要解码2次，因为发送时对html内容标签多转义了一次。
            //  elems = FaFaMessage.fomratHTML(FaFaMessage.fomratHTML(elems));
            if ($msg.find("groupchat").length > 0) {
                msgtype = 1;
                to = $msg.find("groupchat").attr("groupid");
                type = "groupchat";
                _tm = $msg.find("groupchat").attr("sendtime");
                nick = $msg.find("groupchat html").length > 0 ? nick = $msg.find("groupchat").attr("nickname") : nick = $msg.find("groupchat").attr("nickname");
            } else if (MessageType == "broadcast") {
                msgtype = 2;
                to = $msg.attr("to");
                type = "broadcast";
                nick = $msg.children("sendername").text();
                if ($msg.find("delay").length > 0)
                    _tm = $msg.find("delay").attr("stamp");
                else {
                    var now = new Date();
                    var hour = now.getHours();
                    hour = hour < 10 ? "0" + hour : "" + hour;
                    var minute = now.getMinutes();
                    minute = minute < 10 ? "0" + minute : "" + minute;
                    var second = now.getSeconds();
                    second = second < 10 ? "0" + second : "" + second;
                    _tm = hour + ":" + minute + ":" + second;
                }
                elems = $msg.find("body").text();
            } else if (MessageType == "remind") {
                msgtype = 3;
                to = $msg.attr("to");
                type = "remind";
                nick = $msg.children("sendername").text();
                if ($msg.find("delay").length > 0)
                    _tm = $msg.find("delay").attr("stamp");
                else {
                    var now = new Date();
                    var hour = now.getHours();
                    hour = hour < 10 ? "0" + hour : "" + hour;
                    var minute = now.getMinutes();
                    minute = minute < 10 ? "0" + minute : "" + minute;
                    var second = now.getSeconds();
                    second = second < 10 ? "0" + second : "" + second;
                    _tm = hour + ":" + minute + ":" + second;
                }
                elems = $msg.find("body").text();
            } else {
                to = msg.getAttribute('to');
                type = isBusiness.length > 0 ? "business" : "message";
                nick = isBusiness.length > 0 ? isBusiness.children("sendername").text() : $msg.children("nick").text() + $msg.children("nickname").text();
                _tm = isBusiness.length > 0 ? isBusiness.children("sendtime").text() : $msg.children("sendtime").text();
            }
            if (elems.length > 0) {
                if (FaFaMessage._rlist.length > 0) {
                    var sendSub = true;
                    for (var tmpObj in FaFaMessage._rlist) {
                        if (tmpObj.From != null && tmpObj.From == from) {
                            sendSub = false;
                            break;
                        }
                    }
                    if (sendSub) {
                        //发送临时订阅
                        var reply = $iq({
                            from: to,
                            to: from,
                            type: 'set'
                        }).c("subscribe", {
                            xmlns: 'http://im.private-en.com/namespace/subscribe'
                        });
                        //this._conn.send(reply.tree());
                    }
                }
                var delay = $msg.find("delay");
                delay = delay.length == 0 ? "" : new Date(delay.attr("stamp").replace(/-/g, "\/").replace(/[TZ]/g, " "));
                delay = delay == "" ? "" : new Date(delay.setHours(delay.getHours() + 8));
                //判断是否是发送的图片，是则立即请求文件
                if (/\{[\[\(]\w{32}\.[\w]{3,5}[\]\)]\}/g.test(elems)) {
                    elems = elems.replace(/\{[\[\(]\w{32}\.[\w]{3,5}[\]\)]\}/g, function($r) {
                        var fn = $r.substring(2, $r.length - 2);
                        FaFaMessage.RequestFile(type == "groupchat" ? msg.getAttribute('to') : to, from, fn);
                        fn = fn.split(".");
                        fn.pop();
                        return "<SPAN id='" + fn.join("") + "' contentEditable=\"false\" class='webim_load_picture'></SPAN>";
                    });
                }
                if (elems.indexOf("font-size") > -1)
                    elems = FaFaMessage.FormateElement(elems);
                if (typeof(_tm) == "undefined" || _tm == "") {
                    var date = new Date();
                    _tm = (date.getHours() < 10 ? "0" + date.getHours() : date.getHours()) + ":" + (date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes()) +
                        ":" + (date.getSeconds() < 10 ? "0" + date.getSeconds() : date.getSeconds());
                }
                var _d = {
                    From: FromJid,
                    Nick: reconvert(nick),
                    To: Jid.Parse(to),
                    Time: _tm.length > 1 ? _tm : _tm.text(),
                    Type: type,
                    Delay: delay == "" ? "" : delay.getFullYear() + "-" + (delay.getMonth() + 1) + "-" + delay.getDate() + " " + delay.toLocaleTimeString(), //离线消息标识及发送时间
                    Body: {
                        text: (elems),
                        innerHTML: $msg.find("body:eq(0)")
                    }
                };
                var fromJid = "";
                if (_d.Type == "groupchat") {
                    fromJid = FromJid.resource;
                    fromJid = _d.To.user + "@" + _d.To.server + (fromJid != "" ? "/" + fromJid : "");
                } else
                    fromJid = $msg.attr("from");
                //写收到的消息
                var _tojid = Jid.Bear($msg.attr("to"));
                if (!(_d.To.resource.indexOf("FaFaWeb") == 0 && _d.To.resource != "FaFaWeb")) //匿名在线客户不写消息日志 
                    if (typeof(FaFaChatWin) != "undefined") FaFaChatWin.WriteChatMsg(_tojid, fromJid, typeof(FaFaChatMain) != "undefined" ? FaFaChatMain.owner.name : "匿名用户", _d.Nick, _d.Time, _d.Body.text, msgtype, _tojid);
                if (isBusiness.length > 0) {
                    _d.Caption = isBusiness.children("caption").text();
                    _d.Link = isBusiness.children("link").text();
                    _d.LinkText = isBusiness.children("buttons");
                }
                if (FaFaMessage._onMessageHander != null && FaFaMessage._onMessageHander.length > 0) {
                    for (var fun in FaFaMessage._onMessageHander) {
                        try {
                            FaFaMessage._onMessageHander[fun](_d);
                        } catch (e) {
                            FaFaMessageError.push(e);
                        }
                    }
                }
                else
                {
                    FaFaMessageHelper.pushmsg(_d);
                }
            }
        } catch (e) {
            FaFaMessageError.push(e);
        }
        return true;
    },
    //更改在线状态
    //show:当前在线状态代码
    //signature:个性签名
    //statusDesc:状态描述
    //{<presence xmlns="jabber:client"><status>站在食物链最顶端的贝爷，V587</status><priority>10</priority></presence>}
    //{<presence xmlns="jabber:client">signature>很多卖点等于没有卖点！</signature><show>dnd</show><status>站在食物链最顶端的贝爷，V587</status><priority>10</priority></presence>}
    ChangeState: function(show, signature, statusDesc) {
        if (this._conn == null || !this._conn.connected) {
            this.RestartConn(show);
            return;
        }
        var reply = show == "" ? $pres({
            xmlns: 'jabber:client'
        }).c('status', statusDesc).up().c('signature', signature) : $pres({
            xmlns: 'jabber:client'
        }).c('show', show).up().c('status', statusDesc).up().c('signature', signature);
        this._conn.send(reply.tree());
    },
    /*
    * 消息发送.//<nick xmlns="http://jabber.org/protocol/nick">李领</nick>
    * 完整的消息格式：
    <message xmlns="jabber:client" type="chat" to="10010@100156.justsy.com/FaFaWeb">
			<body>&amp;lt;xml&amp;gt;</body>
			<nick xmlns="http://jabber.org/protocol/nick">刘泽道</nick>
			<sendtime xmlns="http://jabber.org/protocol/time">21:06:48</sendtime>
			<html xmlns="http://jabber.org/protocol/xhtml-im">
   			<body xmlns="http://www.w3.org/1999/xhtml">
      		<P><SPAN style="color:#000000;"><SPAN style="font-style:normal;"><SPAN style="font-weight:normal;"><SPAN style="font-size:14px;"><SPAN style="font-family:微软雅黑;">&lt;xml&gt;</SPAN></SPAN></SPAN></SPAN></SPAN></P>
   			</body>
			</html>
		</message>
    */
    Send: function(From, To, Msg, Nick, Html) {
        if (!this._conn.connected) return;
        var now = new Date();
        var hour = now.getHours();
        hour = hour < 10 ? "0" + hour : "" + hour;
        var minute = now.getMinutes();
        minute = minute < 10 ? "0" + minute : "" + minute;
        var second = now.getSeconds();
        second = second < 10 ? "0" + second : "" + second;
        var reply = $msg({
            to: To,
            from: From,
            type: 'chat'
        })
            .c('sendtime', {
                xmlns: 'http://jabber.org/protocol/xhtml-im'
            }, hour + ":" + minute + ":" + second)
            .c('nick', {
                xmlns: 'http://jabber.org/protocol/nick'
            }, (Nick == null || Nick == "") ? '在线咨询客户' : Nick)
            .c('body', {}, this.fomratString(Msg))
            .c('html', {
                xmlns: 'http://jabber.org/protocol/xhtml-im'
            })
            .c('body', {
                xmlns: 'http://www.w3.org/1999/xhtml'
            }, Msg);
        this._conn.send(reply.tree(), function(item) {
            FaFaMessageError.push(item);
        });
    },
    /*
     *群消息发送
     */
    SendGroupMessage: function(From, To, Msg, Nick) {
        if (!this._conn.connected) return;
        var now = new Date();
        var hour = now.getHours();
        hour = hour < 10 ? "0" + hour : "" + hour;
        var minute = now.getMinutes();
        minute = minute < 10 ? "0" + minute : "" + minute;
        var second = now.getSeconds();
        second = second < 10 ? "0" + second : "" + second;
        var reply = $iq({
            xmlns: 'jabber:client',
            type: 'set',
            from: From
        })
            .c('groupchat', {
                xmlns: 'http://im.private-en.com/namespace/group',
                groupid: To,
                sendtime: hour + ":" + minute + ":" + second,
                nickname: (Nick == null || Nick == "") ? '在线咨询客户' : Nick
            })
            .c('html', {
                xmlns: 'http://jabber.org/protocol/xhtml-im'
            })
            .c('body', {
                xmlns: 'http://www.w3.org/1999/xhtml'
            }, this.fomratString(Msg)).up()
            .c('text', Msg);
        this._conn.send(reply.tree());
    },
    //通知对方有离线文件需要接收
    SendOfflineFileRequest: function(From, To, fn, fnID, Nick) {
        var now = new Date();
        var hour = now.getHours();
        hour = hour < 10 ? "0" + hour : "" + hour;
        var minute = now.getMinutes();
        minute = minute < 10 ? "0" + minute : "" + minute;
        var second = now.getSeconds();
        second = second < 10 ? "0" + second : "" + second;
        var reply = $iq({
            from: From,
            type: 'set'
        }).c('takeofflinefile', {
            xmlns: 'http://im.private-en.com/namespace/offlinefile',
            filehashvalue: fnID,
            filename: FaFaMessage.fomratString(fn),
            sendto: To,
            sendtime: hour + ":" + minute + ":" + second,
            nick: (Nick == null || Nick == "") ? '在线咨询客户' : Nick
        });
        this._conn.sendIQ(reply.tree(), function(item) {});
    },
    SendFileRequest: function(From, To, fn, fnID, Nick) {
        var now = new Date();
        var hour = now.getHours();
        hour = hour < 10 ? "0" + hour : "" + hour;
        var minute = now.getMinutes();
        minute = minute < 10 ? "0" + minute : "" + minute;
        var second = now.getSeconds();
        second = second < 10 ? "0" + second : "" + second;
        var reply = $iq({
            to: To,
            from: From,
            type: 'set'
        }).c('fafawebfile', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            fileid: fnID,
            fileName: FaFaMessage.fomratString(fn),
            action: "request",
            sendtime: hour + ":" + minute + ":" + second,
            nick: (Nick == null || Nick == "") ? '在线咨询客户' : Nick
        });
        this._conn.sendIQ(reply.tree(), function(item) {});
    },

    CancelSendFileRequest: function(From, To, fnID) {
        var reply = $iq({
            to: To,
            from: From,
            type: 'set'
        }).c('fafawebfile', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            fileid: fnID,
            action: "cancel"
        });
        this._conn.sendIQ(reply.tree(), function(item) {

        });
    },
    SendFileConfirm: function(From, To, oldFile, newPath, fileId) {
        var reply = $iq({
            to: To,
            from: From,
            type: 'set'
        }).c('fafawebfile', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            oldfilename: FaFaMessage.fomratString(oldFile),
            path: newPath,
            fileid: fileId,
            action: "send"
        });
        this._conn.sendIQ(reply.tree(), function(item) {

        });
    },
    RequestFile: function(From, To, Fn) {
        //请求文件传送。主要用于pc端向web直接传图片时，web端收到消息时，向pc端发起传送请求。请求IQ格式如下：
        //<iq xmlns="jabber:client" id="TyXMPP_137" to="10004@100082.justsy.com/FaFaWeb" type="get">
        //  <si xmlns="http://jabber.org/protocol/si" profile="http://jabber.org/protocol/si/profile/file-transfer" id="dff3720c-3277-4092-9378-93b3664ebb37">
        //     <file xmlns="http://jabber.org/protocol/si/profile/file-transfer" name="4533EF8067878D2CAF14B2F8483D037D.png" size="0"><desc /><auto>auto</auto><range /></file>
        //   </si>
        //</iq>
        var reply = $iq({
            from: From,
            to: To,
            type: 'get'
        }).c('si', {
            xmlns: 'http://jabber.org/protocol/si',
            profile: 'http://jabber.org/protocol/si/profile/file-transfer'
        }).c('file', {
            xmlns: 'http://jabber.org/protocol/si/profile/file-transfer',
            name: FaFaMessage.fomratString(Fn)
        }).c("auto", "auto");
        this._conn.sendIQ(reply.tree(), function(item) {});
    },
    RejectFile: function(From, To, fn, fnID) {
        //拒绝接收文件
        //先发送离线文件删除iq，删除离线文件由服务器处理，所以不需要to属性.只有pc/手机上向web端发送的离线文件才需这步操作
        //成功后向对方发送拒绝信息Iq
        var reply = $iq({
            from: From,
            type: 'set'
        }).c('delofflinefile', {
            xmlns: 'http://im.private-en.com/namespace/offlinefile',
            filehashvalue: fnID
        });
        this._conn.sendIQ(reply.tree(), function(item) {});

        var reply = $iq({
            to: To,
            from: From,
            type: 'set'
        }).c('fafawebfile', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            filename: FaFaMessage.fomratString(fn),
            fileid: fnID,
            action: 'reject'
        });
        this._conn.sendIQ(reply.tree(), function(item) {});

    },
    AcceptFile: function(From, To, fn, fnID) {
        //接收成功后
        //通知对方
        var reply = $iq({
            to: To,
            from: From,
            type: 'set'
        }).c('fafawebfile', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            filename: FaFaMessage.fomratString(fn),
            fileid: fnID,
            action: 'accept'
        });
        this._conn.sendIQ(reply.tree());
    },
    GetFileHttpPath: function(From, fn, fnID, func) {
        //删除离线文件数据 
        var reply = $iq({
            from: From,
            type: 'set'
        }).c('delofflinefilerecord', {
            xmlns: 'http://im.private-en.com/namespace/offlinefile',
            filehashvalue: fnID
        });
        this._conn.sendIQ(reply.tree());
        $.getJSON((WebIM_domain || FaChatMain_domain) + "/webim/getfilepath?jsoncallback=?&fileid=" + fnID, function(data) {
            func(data);
        });
    },
    FormateElement: function(elems) {
        var point = elems.indexOf("font-size");
        var before = elems.substring(0, point);
        var last = elems.substring(point);
        var temp = last.split(";")[0].replace("font-size", "").replace(":", "");
        var result = before + "line-height:" + temp + ";" + last;
        return result;
    }
};
/*
 * 个人用户接口
 */
var FaFaEmployee = {};
FaFaEmployee.Query = function(To, func) {
    var isopenid = To.indexOf("@")==-1;
    var para = {"staff": isopenid?To:Jid.Bear(To)};//判断是openid还是jid/帐号
    var parainfo = GetAuth2Info(_WEFAFA_APPID,para);
    $.getJSON(mydomain + "/api/baseinfo/getstaffcard?jsoncallback=?",parainfo, function(data) {
        var rosterInfo = new roster();
        rosterInfo.jid = To;
        rosterInfo.resource = ["Web"];
        if (data.returncode != "9999") {
            rosterInfo.jid = data.staff_full.jid;
            rosterInfo.name = data.staff_full.nick_name;
            rosterInfo.ename = data.staff_full.eshortname;
            rosterInfo.dept = data.staff_full.dept_name == null ? "" : data.staff_full.dept_name;
            rosterInfo.duty = data.staff_full.duty == null ? "" : data.staff_full.duty;
            rosterInfo.sex = "";
            rosterInfo.mobile = data.staff_full.mobile == null ? "" : data.staff_full.mobile;
            rosterInfo.phone = data.staff_full.work_phone == null ? "" : data.staff_full.work_phone;
            rosterInfo.desc = data.staff_full.self_desc == null ? "" : data.staff_full.self_desc;
            rosterInfo.email = data.staff_full.login_account;
            if(isopenid) rosterInfo.openid = To;
            if (data.staff_full.photo_path != null && data.staff_full.photo_path != "")
                rosterInfo.photo = data.staff_full.photo_path;
        } else {
            rosterInfo.name = Jid.Parse(To).user;
        }
        func(rosterInfo);
    });
};
//获取指定jid的群列表
// <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
//   <querygroup xmlns='http://im.private-en.com/namespace/group' ver='xxx'>
//   </querygroup>
// </iq>
FaFaEmployee.QueryGroup = function(func) {
    if (FaFaMessage._conn == null || FaFaMessage._jid == null) return null;
    var iq = $iq({
        id: FaFaMessage._conn.getUniqueId(),
        from: Jid.Bear(FaFaMessage._jid),
        type: 'get'
    }).c('querygroup', {
        xmlns: 'http://im.private-en.com/namespace/group'
    });
    FaFaMessage._conn.sendIQ(iq, function(iq) {
        var result = [];
        var eles = $(iq).find("item");
        for (var i = 0; i < eles.length; i++) {
            result.push(eles[i]);
        }
        func(result);
    });
};
FaFaEmployee.QueryGroupInfo = function(groupId, func) {
    var parainfo = GetAuth2Info(_WEFAFA_APPID,{"groupid": groupId});
    $.getJSON(mydomain + "/api/http/getgroupinfo?jsoncallback=?", parainfo, function(data) {
        data.group.jid = data.group.groupid + "@justsy.com";
        if (data.returncode == "0000" && data.group != null && data.group.groupid != null) {
            if (data.group.logo == null) data.group.logo = "";
            if (data.group.groupclass == "circlegroup" && data.group.logo == "")
                data.group.logo = mydomain + "/bundles/fafatimewebase/images/default_cirle.png";
            else if (data.group.groupclass == "meeting" && data.group.logo == "")
                data.group.logo = mydomain + "/bundles/fafatimewebase/images/default_meeting.png";
            else if (data.group.logo == "")
                data.group.logo = mydomain + "/bundles/fafatimewebase/images/default_group.png";
            else
                data.group.logo = data.group.logo;
            func(data.group);
            return;
        }
        data.group.logo = mydomain + "/bundles/fafatimewebase/images/default_group.png";
        func(data.group);
    });
}
//临时订阅指定帐号
FaFaEmployee.Subscribe = function(account, func) {
    FaFaEmployee.Query(account, function(data) {
        //发送订阅IQ
        var subscriptionIq = $iq({
            id: FaFaMessage._conn.getUniqueId(),
            from: Jid.toString(FaFaMessage._jid),
            type: 'set'
        }).c('subscribe', {
            xmlns: 'http://im.private-en.com/namespace/userstate'
        }).c('item', {
            rid: data.jid,
            rtype: '2',
            action: 'subscribe'
        });
        FaFaMessage._conn.sendIQ(subscriptionIq.tree(), function(r) {
            if (func != null) func(data);
        });
    });
};
//查询群成员
FaFaEmployee.QueryGroupEmployee = function(groupID, func) {
    if (FaFaMessage._conn == null || FaFaMessage._jid == null) return null;
    var iq = $iq({
        id: FaFaMessage._conn.getUniqueId(),
        from: Jid.Bear(FaFaMessage._jid),
        type: 'get'
    }).c('querygroupmember', {
        xmlns: 'http://im.private-en.com/namespace/group',
        groupid: groupID
    });
    FaFaMessage._conn.sendIQ(iq, function(iq) {
        var result = [];
        var eles = $(iq).find("item");
        for (var i = 0; i < eles.length; i++) {
            result.push(eles[i]);
        }
        func(result);
    });
};

FaFaEmployee.QueryDept = function(func) {
    FaFaEnterprise.GetDept(null, func);
};
//获取指定好友的状态
FaFaEmployee.GetPresence = function(roster, func) {
    if (FaFaMessage._conn == null || FaFaMessage._jid == null) return null;
    var iq = $pres({
        to: roster,
        from: Jid.Bear(FaFaMessage._jid),
        type: 'probe'
    });
    FaFaMessage._conn.send(iq.tree(), function(iq) {
        var result = [];
        func(result);
    });
};
/*
 *企业级应用接口
 *GetInfo:返回指定Jid的企业名称、姓名及昵称信息，返回格如下：
 *"<iq xmlns=\"jabber:client\" from=\"12315@8888.lli2\" to=\"yeyun@8888.lli2/FaFaWeb\" id=\"915\" type=\"result\"><vCardSearch xmlns=\"vcard-temp\"><orgname>FAFA</orgname><nickname>速度</nickname></vCardSearch></iq>"
 */
var FaFaEnterprise = {
    GetInfo: function(To, Call) {},
    GetDept: function(Deptid, func) {
        if (Deptid == null) {
            if (FaFaMessage._conn == null || FaFaMessage._jid == null) return null;
            var iq = $iq({
                id: FaFaMessage._conn.getUniqueId(),
                from: Jid.Bear(FaFaMessage._jid),
                type: 'get'
            }).c('query', {
                xmlns: 'http://im.private-en.com/namespace/dept',
                pid:Deptid!=null?Deptid:""
            });
            FaFaMessage._conn.sendIQ(iq, function(iq) {
                var result = [];
                var eles = $(iq).find("item");
                for (var i = 0; i < eles.length; i++) {
                    result.push(eles[i]);
                }
                func(result);
            });
        } else {
            //未实现  fix     	  
        }
    },
    GetEmployees: function(Deptid, func) {
        if (FaFaMessage._conn == null || FaFaMessage._jid == null) return null;
        //发送订阅IQ
        FaFaEnterprise.SubscribeDept(Deptid);
        //获取人员列表    
        var iq = $iq({
            id: FaFaMessage._conn.getUniqueId(),
            from: Jid.toString(FaFaMessage._jid),
            type: 'get'
        }).c('query', {
            xmlns: 'http://im.private-en.com/namespace/employee',
            deptid: Deptid
        });
        FaFaMessage._conn.sendIQ(iq, function(iq) {
            var result = [];
            var eles = $(iq).find("item");
            for (var i = 0; i < eles.length; i++) {
                result.push(eles[i]);
            }
            func(result);
        });
    },
    Subscribe: function() {
        //订阅当前企业。发送订阅IQ
        FaFaEnterprise.SubscribeEnterprise("");
    },
    SubscribeEnterprise: function(Eno) {
        //订阅指定企业。发送订阅IQ
        var subscriptionDeptIq = $iq({
            id: FaFaMessage._conn.getUniqueId(),
            from: Jid.toString(FaFaMessage._jid),
            type: 'set'
        }).c('subscribe', {
            xmlns: 'http://im.private-en.com/namespace/userstate'
        }).c('item', {
            rid: Eno,
            rtype: '3',
            action: 'subscribe'
        });
        FaFaMessage._conn.sendIQ(subscriptionDeptIq.tree());
    },
    SubscribeDept: function(Deptid) {
        //发送订阅IQ
        var subscriptionDeptIq = $iq({
            id: FaFaMessage._conn.getUniqueId(),
            from: Jid.toString(FaFaMessage._jid),
            type: 'set'
        }).c('subscribe', {
            xmlns: 'http://im.private-en.com/namespace/userstate'
        }).c('item', {
            rid: Deptid,
            rtype: '0',
            action: 'subscribe'
        });
        FaFaMessage._conn.sendIQ(subscriptionDeptIq.tree());
    }
};

//消息辅助管理器。主要帮助管理当没有定义消息处理器时的接收到历史消息，并提供查询接口
var FaFaMessageHelper={
    _list:new HashMap(),
    _lastgetid:new HashMap(),
    New:function(){
        var tmp=this._list.get(FaFaMessage._jid);
        if(tmp==null || tmp.length==0) return null;
        var id = this._lastgetid.get(FaFaMessage._jid);
        if(id==null) id=0;
        var re = [];
        for (var i = id; i < tmp.length; i++) {
            re.push(tmp[i]);
        };
        id=tmp.length-1;
        this._lastgetid.put(FaFaMessage._jid,id);
        return re;
    },
    All:function(){
        var tmp=this._list.get(FaFaMessage._jid);
        if(tmp==null) return null;
        this._lastgetid.put(FaFaMessage._jid,tmp.length-1);
        return tmp;
    },
    pushmsg:function(msg){
        var tmp=this._list.get(FaFaMessage._jid);
        if(tmp==null) tmp=[];        
        tmp.push(msg);
        this._list.put(FaFaMessage._jid,tmp);
    },
    clear:function(){
       this._list=new HashMap();
        this._lastgetid=new HashMap();
    }
};

if (window.MessageAPI.init != null) window.MessageAPI.init();