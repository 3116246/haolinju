//fafa好友/人员选择组件
var WefafaFriend={_element:null},LoadCSS={},srcs = [],currentScriptSrc="";
if(document.currentScript==null)
{
        var scripts = document.getElementsByTagName("script");
        var  reg = /fafriendapi([.-]\d)*\.js(\W|$)/i
        for(var i = 0 , n = scripts.length ; i <n ; i++){
            var src = !!document.querySelector ? scripts[i].src 
                          :scripts[i].getAttribute("src",4);
            if(src && reg.test(src)){
            	  currentScriptSrc = src;
                srcs	 = currentScriptSrc.split("/");
                break;
            }
        } 
}
else
{
	  currentScriptSrc = document.currentScript.src;
	  srcs = currentScriptSrc.split("/");    
}
LoadCSS.domain = srcs[0]+"//"+ srcs[2];
LoadCSS.load=function(cssfile){
	 var isRef = false;
    var scripts = document.getElementsByTagName("LINK");
    if (scripts != null) {
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].href.split("?")[0].indexOf(cssfile) > -1) {
                isRef = true;
                break;
            }
        }
    }	
    if(!isRef){
		  var head=document.getElementsByTagName('head').item(0);	
			css=document.createElement('link');
			css.href= cssfile.indexOf("/") == -1 ?(LoadCSS.domain+"/bundles/fafatimeweopenapi/css/" + cssfile):cssfile;
			css.rel='stylesheet';
			css.type='text/css';
			head.appendChild(css);
  	}
}
LoadCSS.load("fafafriend.css");
var WefafaFriend={
	  id:"wefafa_friend_4294967295",
	  svr:LoadCSS.domain,
	  appid:"",
	  token:"",
	  openid:"",
	  friendData:[],
	  groupData:[],
	  circleData:[],
	  relationData:[],
	  foucsType:"f",
	  bindInput:null,
	  itemHtml_down:"<div class='menu_item' id='WefafaAutoCompleteMenu_downlistitem_{0}' key='{0}'  unselectable='on'>{1}{2}</div>",
	  itemHtml:"<div class='wefafafriendAutoComplete_item' id='WefafaAutoCompleteMenu_{0}' key={1}  unselectable='on' title={2}><img style='width:48px;height:48px' src='{3}' onerror=\"this.src='{4}'\"><div class='wefafafriendAutoComplete_item_name'>{2}</div><input type='checkbox' class='wefafafriendAutoComplete_item_checkbox'></div>",
	  itemHtml_radio:"<div class='wefafafriendAutoComplete_item' id='WefafaAutoCompleteMenu_{0}' key={1}  unselectable='on' title={2}><img style='width:48px;height:48px' src='{3}' onerror=\"this.src='{4}'\"><div class='wefafafriendAutoComplete_item_name'>{2}</div><input type='radio' name='wefafafriendAutoComplete_item_radio' class='wefafafriendAutoComplete_item_radio'></div>",
	  _autoHidden:true,
	  _errorName:[],
	  init:function(args){
	  	  if(args!=null && args.id!=null)
	  	     this.id = args.id;
	  	  if(args!=null && args.server!=null)
	  	     this.svr = args.server;
	  	  if(args!=null && args.appid!=null) this.appid=args.appid;
	  	  if(args!=null && args.access_token!=null) this.token=args.access_token;
	  	  if(args!=null && args.openid!=null) this.openid=args.openid;
	  	  var s_id = this.id;
	  	  this._element = $("#"+s_id);
	  	  if(this._element.length>0)
	  	  {
	  	  	  this._element.remove();
	  	  }
	  	  this._element=$("<div id='"+s_id+"_autolist' class='wefafafriendAutoComplete' >"+
	  	                  "<div class='wefafafriendAutoComplete_bg'>"+
	  	                  "<div id='"+s_id+"_title' class='wefafafriendAutoComplete_title'><span class='wefafafriendAutoComplete_title_icon'></span><span class='wefafafriendAutoComplete_title_label'>请选择对象</span><span type='f' class='wefafafriendAutoComplete_title_item wefafafriendAutoComplete_title_item_active'>好友</span><span type='g' class='wefafafriendAutoComplete_title_item'>群组</span><span type='c' class='wefafafriendAutoComplete_title_item'>圈子</span><span class='wefafafriendAutoComplete_title_close'>x</span></div>"+
	  	                  "<DIV id='"+s_id+"' style='overflow-y: auto; height: 173px; line-height: 21px; width: 360px;position: relative;' unselectable='on'></DIV><div class='wefafafriendAutoComplete_foot'><span value=1 class='wefafafriendAutoComplete_switch'>换一组</span><span class='wefafafriendAutoComplete_close'>关闭</span><span class='wefafafriendAutoComplete_ok'>确定</span></div><div><div>");
	  	  document.body.appendChild(this._element[0]);
	  	  this._downlist=$("#"+s_id+"_downlist");
	  	  if(this._downlist.length>0)this._downlist.remove();
	  	  this._downlist=$("<div id='"+s_id+"_downlist' class='wefafafriendAutoComplete' ><div class='wefafafriendAutoComplete_bg'><DIV id='"+s_id+"_downlist_items' style='overflow-y: auto; height: 200px; line-height: 21px; width: 358px;position: relative;' unselectable='on'></DIV></div</div>");
	  	  document.body.appendChild(this._downlist[0]);
	  	  
	  	  this.loadData();
	  },
	  bind:function(ele){
$(".wefafafriendAutoComplete").live("mouseenter",function(e){
	  	      	WefafaFriend._autoHidden=false;
	  	  }); 
	  	  $(".wefafafriendAutoComplete").live("mouseleave",function(e){
	  	      	WefafaFriend._autoHidden=true;
	  	  });	 	  
	  	  $(".wefafafriendAutoComplete_item,.wefafafriendAutoComplete_item_selected").live("click",function(e){
	  	  	  var $this = $(this);
	  	  	  var inp = $this.find("input"),itemData=null;
	  	  	  WefafaFriend.bindInput.focus();
	  	  	  
	  	  	  if(WefafaFriend.foucsType=="f") itemData=WefafaFriend.friendData[$this.attr("key")*1];
	  	  	  else if(WefafaFriend.foucsType=="g")itemData=WefafaFriend.groupData[$this.attr("key")*1];
	  	  	  else if(WefafaFriend.foucsType=="c")itemData=WefafaFriend.circleData[$this.attr("key")*1]; 
	  	  	  itemData.checked=!inp[0].checked; 
	  	  	  if(!inp[0].checked)
	  	  	  {
	  	  	  	  if(inp[0].type=="radio"){
	  	  	  	  	WefafaFriend._element.find(".wefafafriendAutoComplete_item_selected").attr("class","wefafafriendAutoComplete_item");
	  	  	  	  	WefafaFriend._element.find(".wefafafriendAutoComplete_item_radio").css("display","none");
	  	  	  	  }
	  	          inp.css("display","block").attr("checked",true);
	  	          $this.attr("class","wefafafriendAutoComplete_item_selected");
	  	      }
	  	      else
	  	      {
	  	      	  WefafaFriend.bindInput.val(WefafaFriend.bindInput.val().replace((itemData.name||itemData.group_name||itemData.circle_name)+";",""));
	  	      	  $this.attr("class","wefafafriendAutoComplete_item");
	  	      	  inp.css("display","none").attr("checked",false);
	  	      }	  	      
	  	  });
	  	  $(".wefafafriendAutoComplete_title_item").live("click",function(e){
	  	  	  WefafaFriend._element.find(".wefafafriendAutoComplete_title_item_active").attr("class","wefafafriendAutoComplete_title_item");
	  	  	  $(this).attr("class","wefafafriendAutoComplete_title_item wefafafriendAutoComplete_title_item_active");
	  	  	  WefafaFriend.bindInput.focus();
	  	  	  if(WefafaFriend.foucsType == $(this).attr("type")) return;
	  	  	  WefafaFriend.foucsType = $(this).attr("type");
	  	  	  if(WefafaFriend.foucsType=="f") WefafaFriend.addItem("");
	  	  	  else if(WefafaFriend.foucsType=="g")WefafaFriend.addGroupItem();
	  	  	  else if(WefafaFriend.foucsType=="c")WefafaFriend.addCircleItem();
	  	  	  //判断是否需要显示 换一组
	  	  	  var $switch = WefafaFriend._element.find(".wefafafriendAutoComplete_switch");
	  	  	  if(WefafaFriend.foucsType=="f" && WefafaFriend.friendData.length>10) $switch.css("display","block");
	  	  	  else if(WefafaFriend.foucsType=="g" && WefafaFriend.groupData.length>10) $switch.css("display","block");
	  	  	  else if(WefafaFriend.foucsType=="c" && WefafaFriend.circleData.length>10) $switch.css("display","block");
	  	  	  else $switch.css("display","none");
	  	  	  $switch.attr("value","1");
	  	  });
	  	  $(".wefafafriendAutoComplete_title_close,.wefafafriendAutoComplete_close").live("click",function(e){
	  	  	 WefafaFriend._element.css("display","none");
	  	  });
	  	  $(".wefafafriendAutoComplete_ok").live("click",function(e){	  	  	 
	  	  	 var items = WefafaFriend.selectedItems();
	  	  	 WefafaFriend._element.css("display","none");
	  	  	 if(WefafaFriend.bindInput==null) return;
	  	  	 var names=[],values=[],va = WefafaFriend.bindInput.val().replace(/ /g,""),selecttype=WefafaFriend.bindInput.attr("selecttype");
	  	  	 if(WefafaFriend.foucsType=="f" && selecttype=="friend")
	  	  	 {
	  	  	     if(va!="" && !/;$/g.test(va)) va += ";";
	  	  	 }
	  	  	 else va="";
	  	  	 for(var i=0;i<items.items.length; i++)
	  	  	 {
	  	  	 	  if(WefafaFriend.foucsType=="f" && va.indexOf(items.items[i].name+";")>-1) continue;
	  	  	    names.push(items.items[i].name||items.items[i].group_name||items.items[i].circle_name);
	  	  	    values.push(items.items[i].fafa_jid||items.items[i].group_id||items.items[i].circle_id);
	  	  	 }
	  	  	 var ns = names.join(";");
	  	  	 if(ns!="") ns= ns+";";
	  	  	 WefafaFriend.bindInput.val(va+ns);
	  	  	 //WefafaFriend.bindInput.attr("ids",values.join(";"));
	  	  	 WefafaFriend.bindInput.attr("selecttype",WefafaFriend.foucsType=="f"?"friend":(WefafaFriend.foucsType=="g"?"group":"circle"));
	  	  });
	  	  $(".wefafafriendAutoComplete_switch").live("click",function(e){
	  	  	    var $this = $(this);
	  	      	$this.attr("value",$this.attr("value")*1+1);
	  	  	    if(WefafaFriend.foucsType=="f") WefafaFriend.addItem("");
	  	  	    else if(WefafaFriend.foucsType=="g")WefafaFriend.addGroupItem();
	  	  	    else if(WefafaFriend.foucsType=="c")WefafaFriend.addCircleItem();	  	      	
	  	  });
	  	  $(".menu_item").live("click",function(e){
	  	      	var $this=$(this),key=$this.attr("key");
	  	      	var itemNmae =key.indexOf("f")==0? WefafaFriend.friendData[key.substring(1)].name: WefafaFriend.relationData[key].name;
	  	      	var va = WefafaFriend.bindInput.val(),vUnits = va.split(";");
	  	      	WefafaFriend.bindInput.focus();
              if(va.indexOf(itemNmae+";")>-1) return; //已存在人员不再添加
	  	      	if(vUnits.length==1) vUnits[0]=itemNmae+";";
	  	      	else vUnits[vUnits.length-1]=itemNmae+";";
	  	      	WefafaFriend.bindInput.val(vUnits.join(";"));	  	      	
	  	  });	  	
	  	var Input = typeof(ele)=="string"? $("#"+ele) :ele;
	  	Input.live("click",function(e){
	  		 WefafaFriend.bindInput=$(this);
	  		 var xy = WefafaFriend.bindInput.offset();
	  		 WefafaFriend._downlist.css({"display":"none"});
	  		 WefafaFriend._element.css({"display":"block","top":(xy.top+WefafaFriend.bindInput.height()+3)+"px","left":xy.left+"px","z-index":100001});
	  	});
	  	Input.live("keydown",function(e){	
	  		  WefafaFriend.bindInput=$(this);
	  		  var selecttype=WefafaFriend.selectType();
	  		  if(selecttype!="friend"){WefafaFriend.bindInput.attr("selecttype","friend");WefafaFriend.bindInput.val("")};	
	  	});
	  	Input.live("keyup",function(e){	  		  
	  		  WefafaFriend.bindInput=$(this);
	  		  var xy = WefafaFriend.bindInput.offset();
	  		  //过虑人员
	  		  var v = WefafaFriend.bindInput.val();
	  		  v = v.split(";");
	  		  WefafaFriend.filter(v[v.length-1]);
	  		  WefafaFriend._element.css({"display":"none"});
	  		  WefafaFriend._downlist.css({"display":"block","top":(xy.top+WefafaFriend.bindInput.height()+3)+"px","left":xy.left+"px","z-index":100001});
	  	});
	  	Input.live("blur",function(e){
	  		    if(!WefafaFriend._autoHidden) return;
	  		    var va=$(this).val();
	  		    if(va!="" && !/;$/g.test( va)) $(this).val(va+";");
	  	    	WefafaFriend._downlist.css({"display":"none"});
	  	    	WefafaFriend._element.css({"display":"none"});
	  	});
	  },
	  filter:function(chars){
	  	 if(chars=="") return null;
	  	 var chars = chars.replace(/ /g,""),accountStr=[];
	  	 var div=this._downlist.find("#"+this.id+"_downlist_items"),list=[];
	  	 if(chars=="" ) return;
	  	 
			 for(var i=0;i<this.friendData.length;i++){
			  	     if(this.friendData[i].login_account.indexOf(chars)>-1 || this.friendData[i].name.indexOf(chars)>-1)
			  	     {
			  	     	  accountStr.push(this.friendData[i].login_account);
			  	     	  list.push(this.itemHtml_down.replace(/\{0\}/g,"f"+i).replace(/\{1\}/g,this.friendData[i].name).replace(/\{2\}/g,"&lt;"+this.friendData[i].login_account+"&gt;") );
			  	     }
			 }
			 accountStr = accountStr.join(";");
		   for(var i=0;i<this.relationData.length;i++){
		  	     if(chars=="" || this.relationData[i].login_account.indexOf(chars)>-1 || this.relationData[i].name.indexOf(chars)>-1)
		  	     {
		  	     	  //判断列表中是否已存在
		  	     	  if(accountStr.indexOf(this.relationData[i].login_account)>-1) continue;
		  	     	  list.push(this.itemHtml_down.replace(/\{0\}/g,i).replace(/\{1\}/g,this.relationData[i].name).replace(/\{2\}/g,"&lt;"+this.relationData[i].login_account+"&gt;") );
		  	     }
		   }
	  	 div.html(list.join(""));
	  },
	  loadData:function()
	  {
	  	  $("#"+this.id).html("<div style='line-height: 60px;text-align: center;'>数据正在加载中... <img style='vertical-align:middle;' src='"+this.svr+"/bundles/fafatimewebase/images/loadingsmall.gif'></div>");
	  	  var URL = [this.svr,"/api/http/getmyrelation","?jsoncallback=?&","access_token=",this.token,"&","appid=",this.appid,"&","openid=",this.openid];
	  	  $.getJSON(URL.join(""),{},function(data){
	  	  	  if(data.returncode=="0000")
	  	  	  {
	  	  	  	  WefafaFriend.friendData=data.list.friends;
	  	  	  	  if(WefafaFriend.friendData.length>10) $(".wefafafriendAutoComplete_switch").css("display","block");
	  	  	  	  WefafaFriend.groupData=data.list.groupsbyadmin;
	  	  	  	  WefafaFriend.circleData=data.list.circlesbyadmin;
	              WefafaFriend.addItem("");
	  	  	  }
	  	  	  else
	  	  	  {
	  	  	  	  $("#"+WefafaFriend.id).html("<div style='text-align: center; line-height: 100px; color: red;'>数据访问授权无效或已过期，您需要重新登录系统！</div>");
	  	  	  }
	  	  });
	  	  //加载人脉
	  	  URL = [this.svr,"/api/http/getenostaff","?jsoncallback=?&","access_token=",this.token,"&","appid=",this.appid,"&","openid=",this.openid];
	  	  $.getJSON(URL.join(""),{},function(data){
	  	  	 if(data.returncode=="0000") WefafaFriend.relationData=data.list;	  	  	 
	  	  });
	  },
	  getInvalidName:function()
	  {
	  	  if(this._errorName.length==0) return "";
	  	  return this._errorName.join(";");
	  },
	  selectType:function()
	  {
	  	 if(WefafaFriend.bindInput==null) return "friend";
	  	 return WefafaFriend.bindInput.attr("selecttype");
	  },
	  selectedValues:function(){
	  	 if(this.bindInput==null) return "";
	  	 var ids=[];
	  	 this._errorName=[];
	  	 var v = this.bindInput.val();
	  	 v = v.split(";");
	  	 for(var i=0;i<v.length; i++)
	  	 {
	  	 	    if(v[i].replace(/ /g,"")=="") continue;
	  	     	var obj = this.searchFriend(v[i]);
	  	     	if(obj==null) obj = this.searchRelation(v[i]);
	  	     	if(obj==null) obj = this.searchGroup(v[i]);
	  	     	if(obj==null) obj = this.searchCircle(v[i]);
	  	     	if(obj==null){
	  	     		 this._errorName.push(v[i]);
	  	     		 continue;
	  	     	}
	  	     	ids.push(obj.fafa_jid||obj.group_id||obj.circle_id);
	  	 }
	  	 return ids.join(",");
	  },
	  selectedItems:function(){
	  	    var items = this.getSelectedItem();
	  	    var chk_value =[]; 
	  	    items.each(function(){
	  	    	var $thisP = $(this).parent();
	  	    	var key = $thisP.attr("key");
	  	    	if(WefafaFriend.foucsType=="f") chk_value.push(WefafaFriend.friendData[key]);
	  	    	else if(WefafaFriend.foucsType=="g") chk_value.push(WefafaFriend.groupData[key]);
	  	    	else if(WefafaFriend.foucsType=="c") chk_value.push(WefafaFriend.circleData[key]);	
	  	    }); 
	      	if(this.foucsType=="f") return {"type":"friend","items":chk_value};
	      	if(this.foucsType=="g") return {"type":"group","items":chk_value};
	      	if(this.foucsType=="c") return {"type":"circle","items":chk_value};
	  },
	  addItem:function(letter){
	  	    if(this.friendData.length==0)
	  	    {
	  	    	  $("#"+this.id).html("<div style='text-align: center; line-height: 100px; color: red;'>您还没添加任何好友，登录Wefafa添加好友吧！</div>");
	  	        return;	
	  	    }
	  	    $("#"+this.id).html("");
	  	    var pno= WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value")*1;
	  	    var start=(pno-1)*10,end=Math.min(pno*10,this.friendData.length);
	  	    if(start>end){
	  	       WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value","1");
	  	       pno=1;	
	  	       start=(pno-1)*10;
	  	       end=Math.min(pno*10,this.friendData.length);
	  	    }
	  	  	for(var i=start;i<end; i++)
	  	  	{
	  	  	   var iem = this.itemHtml.replace("{0}",i).replace("{1}",i).replace(/\{2\}/g,this.friendData[i].name).replace("{3}",this.svr+"/getfile/"+this.friendData[i].photo_path).replace("{4}",this.svr+"/bundles/fafatimewebase/images/no_photo.png");
	  	  	   $("#"+this.id).append(iem);
	  	  	}
	  },
	  addGroupItem:function(){
	  	    if(this.groupData.length==0)
	  	    {
	  	    	  $("#"+this.id).html("<div style='text-align: center; line-height: 100px; color: red;'>您还没有创建或可管理的群组！</div>");
	  	        return;	
	  	    }
	  	    $("#"+this.id).html("");
	  	    var pno= WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value")*1;
	  	    var start=(pno-1)*10,end=Math.min(pno*10,this.groupData.length);
	  	    if(start>end){
	  	       WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value","1");
	  	       pno=1;	
	  	       start=(pno-1)*10;
	  	       end=Math.min(pno*10,this.groupData.length);
	  	    }	  	    
	  	  	for(var i=start;i< end; i++)
	  	  	{	  	  		 
	  	  	   var iem = this.itemHtml_radio.replace("{0}",i).replace("{1}",i).replace(/\{2\}/g,this.groupData[i].group_name).replace("{3}",this.svr+"/getfile/"+this.groupData[i].group_photo_path).replace("{4}",this.svr+"/bundles/fafatimewebase/images/default_group.png");
	  	  	   $("#"+this.id).append(iem);
	  	  	}
	  },
	  addCircleItem:function(){
	  	    if(this.circleData.length==0)
	  	    {
	  	    	  $("#"+this.id).html("<div style='text-align: center; line-height: 100px; color: red;'>您还没有创建或可管理的圈子！</div>");
	  	        return;	
	  	    }
	  	    $("#"+this.id).html("");
	  	    var pno= WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value")*1;
	  	    var start=(pno-1)*10,end=Math.min(pno*10,this.circleData.length);
	  	    if(start>end){
	  	       WefafaFriend._element.find(".wefafafriendAutoComplete_switch").attr("value","1");
	  	       pno=1;	
	  	       start=(pno-1)*10;
	  	       end=Math.min(pno*10,this.circleData.length);
	  	    }	  	    
	  	  	for(var i=start;i<end; i++)
	  	  	{
	  	  	   var iem = this.itemHtml_radio.replace("{0}",i).replace("{1}",i).replace(/\{2\}/g,this.circleData[i].circle_name).replace("{3}",this.svr+"/getfile/"+this.circleData[i].logo_path).replace("{4}",this.svr+"/bundles/fafatimewebase/images/default_circle.png");
	  	  	   $("#"+this.id).append(iem);
	  	  	}
	  },
	  getSelectedItem:function()
	  {
	  	   return WefafaFriend._element.find(".wefafafriendAutoComplete_item_checkbox:checked,.wefafafriendAutoComplete_item_radio:checked");
	  },
	  searchFriend:function(name){
			  	 for(var i=0;i<this.friendData.length;i++){
			  	     if(this.friendData[i].name==name)
			  	     {
			  	     	   return this.friendData[i];
			  	     }
			  	 }
			  	 return null; 	 
	  },
	  searchGroup:function(name){
			  	 for(var i=0;i<this.groupData.length;i++){
			  	     if(this.groupData[i].group_name==name)
			  	     {
			  	     	   return this.groupData[i];
			  	     }
			  	 }
			  	 return null;
	  },
	  searchCircle:function(name){
			  	 for(var i=0;i<this.circleData.length;i++){
			  	     if(this.circleData[i].circle_name==name)
			  	     {
			  	     	   return this.circleData[i];
			  	     }
			  	 }
			  	 return null;	  	 
	  },
	  searchRelation:function(name){
			  	 for(var i=0;i<this.relationData.length;i++){
			  	     if(this.relationData[i].name==name)
			  	     {
			  	     	   return this.relationData[i];
			  	     }
			  	 }
			  	 return null;	  	 
	  }	   	  
};
