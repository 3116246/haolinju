/*
 * 鲁军(lujun) 
 * http://www.cnblogs.com/idche/
 * email: idche@qq.com
 * GTalk: jelle.lu
 * 2010-10-30
 * 根据WEFAFA需要进行修改
 */

/*
 * user auto tips css
	.recipients-tips{ font-family:Tahoma, Arial;position:absolute; background:#f7f7f2; z-index:2147483647; padding:2px; border:1px solid #6b9228; display:none;}
	.recipients-tips li a{display:block; padding:2px 5px; color:#333; cursor:pointer; font-family:Tahoma, Arial;}
	.recipients-tips li a em{font-weight:700; color:#000; font-family:Tahoma, Arial;}
	.autoSelected{background:#b8c796; font-family:Tahoma, Arial;}
	
 * 数据格式
 	 [{nick_name, eshortname}, ...]
 
 * 使用方法
   userAutoTips({id:'textarea1', url : 'getjsondata.php'});
   或预先设置全局JS变量g_userAutoTipsUrl，然后给textarea指定 class: userAutoTips
 */
(function(){
var config = {
		boxID:"autoTalkBox",
		valuepWrap:'autoTalkText',
		wrap:'recipientsTips',
		listWrap:"autoTipsUserList",
		position:'autoUserTipsPosition',
		positionHTML:'<span id="autoUserTipsPosition">&nbsp;123</span>',
		className:'autoSelected',
		url: ''  //取数url, 应在AutoTips构造时传入
	};
var html = '<div id="autoTalkBox"style="z-index:-2000;top:$top$px;left:$left$px;width:$width$px;height:$height$px;z-index:1;position:absolute;scroll-top:$SCTOP$px;overflow:hidden;overflow-y:auto;visibility:hidden;word-break:break-all;word-wrap:break-word;*letter-spacing:0.6px;"><span id="autoTalkText"></span></div><div id="recipientsTips" class="recipients-tips"><div style="margin: 2px 0px 2px 2px; border-bottom: 1px solid #ccc;">选择最近@的或直接输入</div><ul id="autoTipsUserList"></ul></div>';
var listHTML = '<li><a rel="$ID$" >@$NAME${$ESHORTNAME$}</a></li>';


/*
 * D 基本DOM操作
 * $(ID)
 * DC(tn) TagName
 * EA(a,b,c,e)
 * ER(a,b,c)
 * BS()
 * FF
 */
var D = {
	$:function(ID){
		return document.getElementById(ID)
	},
	DC:function(tn){
		return document.createElement(tn);
	},
//    EA:function(a, b, c, e) {
//        if (a.addEventListener) {
//            if (b == "mousewheel") b = "DOMMouseScroll";
//            a.addEventListener(b, c, e);
//            return true
//        } else return a.attachEvent ? a.attachEvent("on" + b, c) : false
//    },
//    ER:function(a, b, c) {
//        if (a.removeEventListener) {
//            a.removeEventListener(b, c, false);
//            return true
//        } else return a.detachEvent ? a.detachEvent("on" + b, c) : false
//    },
	BS:function(){
		var db=document.body,
			dd=document.documentElement,
			top = db.scrollTop+dd.scrollTop;
			left = db.scrollLeft+dd.scrollLeft;
		return { 'top':top , 'left':left };
	},

	FF:(function(){
		var ua=navigator.userAgent.toLowerCase();
		return /firefox\/([\d\.]+)/.test(ua);
	})()
};

/*
 * TT textarea 操作函数
 * info(t) 基本信息
 * getCursorPosition(t) 光标位置
 * setCursorPosition(t, p) 设置光标位置
 * add(t,txt) 添加内容到光标处
 */
var TT = {
	
	info:function(t){
		var o = t.getBoundingClientRect();
		var w = t.offsetWidth;
		var h = t.offsetHeight;
		return {top:o.top, left:o.left, width:w, height:h};
	},
	
	getCursorPosition: function(t){
		if (document.selection) {
			t.focus();
			var ds = document.selection;
			var range = null;
			range = ds.createRange();
			var stored_range = range.duplicate();
			stored_range.moveToElementText(t);
			stored_range.setEndPoint("EndToEnd", range);
			t.selectionStart = stored_range.text.length - range.text.length;
			t.selectionEnd = t.selectionStart + range.text.length;
			return t.selectionStart;
		} else return t.selectionStart
	},
	
	setCursorPosition:function(t, p){
		var n = p == 'end' ? t.value.length : p;
		if(document.selection){
			var range = t.createTextRange();
			range.moveEnd('character', -t.value.length);         
			range.moveEnd('character', n);
			range.moveStart('character', n);
			range.select();
		}else{
			t.setSelectionRange(n,n);
			t.focus();
		}
	},
	
	add:function (t, txt){
		var val = t.value;
		var wrap = wrap || '' ;
		if(document.selection){
			document.selection.createRange().text = txt;  
		} else {
			var cp = t.selectionStart;
			var ubbLength = t.value.length;
			t.value = t.value.slice(0,t.selectionStart) + txt + t.value.slice(t.selectionStart, ubbLength);
			this.setCursorPosition(t, cp + txt.length); 
		};
	},
	
	del:function(t, n){
		var p = this.getCursorPosition(t);
		var s = t.scrollTop;
		t.value = t.value.slice(0,p - n) + t.value.slice(p);
		this.setCursorPosition(t ,p - n);
		D.FF && setTimeout(function(){t.scrollTop = s},10);
		
	}

}


/*
 * DS 数据查找
 * inquiry(data, str, num) 数据, 关键词, 个数
 * Download by http://www.codefans.net
 */

var DS = {
	inquiry:function(data, str, num){
		if(str == '') return friendsData.slice(0, num);

		var reg = new RegExp(str, 'i');
		var i = 0;
		//var dataUserName = {};
		var sd = [];

		while(sd.length < num && i < data.length){
			if(reg.test(data[i]['user'])){
				sd.push(data[i]);
				//dataUserName[data[i]['user']] = true;
			}
			i++;
		}			
		return sd;
	},
	
	lastAjaxObj: null,
	
	//从服务器上返回取得的数据
	getData:function (Aurl, str, cb) 
  {
    if (this.lastAjaxObj) this.lastAjaxObj.abort();
    this.lastAjaxObj = $.getJSON(Aurl, {query: str, t: new Date().getTime()}, function (data) 
    {
      cb(data);
    });
  }
}


/*
 * selectList
 * _this
 * index
 * list
 * selectIndex(code) code : e.keyCode
 * setSelected(ind) ind:Number
 */


var selectList = {
	_this:null,
	index:-1,
	list:null,
	selectIndex:function(code){
		if(D.$(config.wrap).style.display == 'none') return true;
		var i = selectList.index;
		switch(code){
		   case 40:
			 i = i + 1;
			 break
		   case 38:
			 i = i - 1;
			 break
		   case 13:
			return selectList._this.enter();
			break
		}

		i = i >= selectList.list.length ? 0 : i < 0 ? selectList.list.length-1 : i;
		return selectList.setSelected(i);
	},
	setSelected:function(ind){
		if(selectList.index >= 0) selectList.list[selectList.index].className = '';
		selectList.list[ind].className = config.className;
		selectList.index = ind;
		return false;
	}

}



/*
 *
 */
var AutoTips = function(A){
	var elem = A.id ? D.$(A.id) : A.elem;
	var checkLength = 5;
	var _this = {};
	var key = '';
	config.url = A.url ? A.url : '';

	_this.start = function(){
		if(!D.$(config.boxID)){
			var h = html.slice();
			var info = TT.info(elem);
			var div = D.DC('DIV');
			var bs = D.BS();
			h = h.replace('$top$',(info.top + bs.top)).
					replace('$left$',(info.left + bs.left)).
					replace('$width$',info.width).
					replace('$height$',info.height).
					replace('$SCTOP$','0');
			div.innerHTML = h;
			document.body.appendChild(div);
		}else{
			_this.updatePosstion();
		}
	}
	
  	_this.keyupFn = function(e){
		var e = e || window.event;
		var code = e.keyCode;
		if(code == 38 || code == 40 || code == 13) {
			if(code==13 && D.$(config.wrap).style.display != 'none'){
				_this.enter();
			}
			return false;
		}
		var cp = TT.getCursorPosition(elem);
		if(!cp) return _this.hide();
		var valuep = elem.value.slice(0, cp);
		var val = valuep.slice(-checkLength);
		//var chars = val.match(/(\w+)?@(\w+)$|@$/);
		var chars = val.match(/(.+)?@([\w\u4e00-\u9fa5]+)$|@$/);
		if(chars == null) return _this.hide();
		var Achar = chars[2] ? chars[2] : '';
		D.$(config.valuepWrap).innerHTML = valuep.slice(0,valuep.length - Achar.length).replace(/\n/g,'<br/>').
											replace(/\s/g,'&nbsp;') + config.positionHTML;
		_this.showList(Achar);
	}
	
	_this.showList = function(Achar){
		key = Achar;
		DS.getData(config.url, Achar, _this._showList);		
	}
	
	_this._showList = function (data) 
  {
		var html = listHTML.slice();
		var h = '';
		var len = data.length;
		if(len == 0){_this.hide();return;}
		var reg = new RegExp(key);
		var em = '<strong>'+ key +'</strong>';
		for(var i=0; i<len; i++){
			var hm = data[i]['nick_name'].replace(reg,em);
			h += html.replace('$NAME$',hm)
			         .replace(/\$ESHORTNAME\$/g,data[i]['eshortname'])
						   .replace('$ID$',data[i]['nick_name']+'{'+data[i]['eshortname']+'}');
		}
		
		_this.updatePosstion();
		var p = D.$(config.position).getBoundingClientRect();
		var bs = D.BS();
		var d = D.$(config.wrap).style;
		d.top = p.top + 20 + bs.top + 'px';
		d.left = p.left - 5 + 'px';
		D.$(config.listWrap).innerHTML = h;
		_this.show();
  }
	
	
	_this.KeyDown = function(e){
		var e = e || window.event;
		var code = e.keyCode;
		if(code == 38 || code == 40 || code == 13){
			return selectList.selectIndex(code);
		}
		return true;
	}
	
	_this.updatePosstion = function(){
		var p = TT.info(elem);
		var bs = D.BS();
		var d = D.$(config.boxID).style;
		d.top = p.top + bs.top +'px';
		d.left = p.left + bs.left + 'px';
		d.width = p.width+'px';
		d.height = p.height+'px';
		D.$(config.boxID).scrollTop = elem.scrollTop;
	}
	
	_this.show = function(){
		selectList.list = D.$(config.listWrap).getElementsByTagName('li');
		selectList.index = -1;
		selectList._this = _this;
		_this.cursorSelect(selectList.list);
		//elem.onkeydown = _this.KeyDown;
		$(elem).bind("keydown", _this.KeyDown)
		D.$(config.wrap).style.display = 'block';	
	}
	
	_this.cursorSelect = function(list){
		for(var i=0; i<list.length; i++){
			list[i].onmouseover = (function(i){
				return function(){selectList.setSelected(i)};
			})(i);
			list[i].onclick = _this.enter;
		}
	}
	
	_this.hide = function(){
		selectList.list = null;
		selectList.index = -1;
		selectList._this = null;
		//D.ER(elem, 'keydown', _this.KeyDown);
		$(elem).unbind('keydown', _this.KeyDown);
		D.$(config.wrap).style.display = 'none';
	}
	
	_this.bind = function(){
		
		//elem.onkeyup = _this.keyupFn;
		$(elem).bind("keyup", _this.keyupFn);
		//elem.onclick = _this.keyupFn;
		$(elem).bind("click", _this.keyupFn);
		//elem.onblur = function(){setTimeout(_this.hide, 500)}
		$(elem).bind("blur", function(){setTimeout(_this.hide, 500)});
		//elem.onkeyup= fn;
		//D.EA(elem, 'keyup', _this.keyupFn, false)
		//D.EA(elem, 'keyup', fn, false)
		//D.EA(elem, 'click', _this.keyupFn, false);
		//D.EA(elem, 'blur', function(){setTimeout(_this.hide, 100)}, false);
	}
	
	_this.enter = function(){
		TT.del(elem, key.length, key);
		TT.add(elem, selectList.list[selectList.index].getElementsByTagName('A')[0].rel+' ');
		_this.hide();
		return false;
	}
	
	return _this;
	
}

window.userAutoTips = function(args){
		var a = AutoTips(args);
			a.start();
			a.bind();
	};
	
$(".userAutoTips").live("focus", function () 
{
  var $this = $(this);
  if ($this.attr("hasInituserAutoTips")) return;
  $this.attr("hasInituserAutoTips", "1");
  userAutoTips({elem: this, url: g_userAutoTipsUrl});  
});
		
})();

