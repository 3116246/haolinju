/*************说明*************/
/* AppObj的初始化
----方法一  若该应用有对应的xml文件请使用此方法
var oneApp=new AppObj();
oneApp.init({
xmlString:''//xml字符串
device:'',//值为'IOS'或'Android'
appid:'',//应用id
p:'',//模拟器容器dom对象
ComponentSelected:function(params){},//当模拟器中控件被选中时触发
ErrorOccured:function(error){},//当发生错误时触发 error 为当前错误对象
params 返回参数说明:
{
	functionid:'',//控件所属的界面
	index:'',//控件在xml文档中排列序号 从1开始
	code:'',//控件类型标识
	name:'',//控件类型名称
	attrs:{}//属性表
}
BeforeAddComponent:function(para){}, //当插入控件时发生
InterfaceSelected:function(sender,functionid){},//当页面被加载时触发
para 返回参数说明:
{
	functionid:'',//控件所属的界面
	index:'',//控件在xml文档中排列序号 从1开始
	code:'',//控件类型标识
	name:'',//控件类型名称
	area:'' //当前返回的控件所属的区域,  title-标题栏   content-正文区域   navigation-导航区域
}
});
-----方法二  若该应用为第一次编辑，还未生成任何xml文件请使用此方法
var oneApp=new AppObj();
oneApp.initWithAppInfo({
appinfo:{
	appid:'',//应用id
	appname:'',//应用名称
	appversion:'',//版本
	appicon:'',//图标
	bindurl:'',//绑定地址
},
device:'',//值为'IOS'或'Android'
appid:'',//应用id
p:'',//模拟器容器dom对象
ComponentSelected:'',//当模拟器中控件被选中时触发
ErrorOccured:function(error){},//当发生错误时触发 error 为当前错误对象
BeforeAddComponent:function(para){}, //当插入控件时发生
});
*/
/*AppObj方法调用说明
1、getInterObj(functionid)  //获取一个已加载的InterfaceObj对象 functionid 为页面唯一标识
2、loadInterObj(functionid)  //加载一个InterfaceObj对象 functionid 为页面唯一标识
3、updateAppInfo:function(key,val) //跟新应用基本信息 key 属性名 val 属性值
5、updateInterInfo:function(functionid,key,val) //跟新页面基本信息 key 属性名 val 属性值
4、setRootInterface:function(functionid) //设置根界面 
5、addInterface:function(info)  //新增界面 info为页面对象
6、addInterfaceWidthTempXml:function(functionid,functionname,tempxmlstring) //新增模板界面
7、setInterHTML5:function(functionid,info) //通过html5跟新界面 info为html5对象
8、setInterNative:function(functionid,info) //通过native跟新界面 info为native对象
9、setInterTempWithXml:function(functionid,xmlString) //通过界面xml字符串跟新界面 xmlString为页面xml
10、setInterTemplate:function(functionid,info) //通过template跟新界面 info为template对象
11、setInterComponent:function(functionid,index,info) //设置组件 index为组件在xml模板中的序号 info为组件对象
11、setTemplateTitle:function(functionid,index,info) //设置界面标题 info为标题控件对象
12、setTemplateMenu:function(functionid,index,info) //设置界面菜单 info为菜单控件对象
13、setTemplateList:function(functionid,index,info) //新增界面列表 info为列表控件对象
14、getXmlDom:function() //获取xml对象
15、getRootFunctionid:function() //获取应用的根界面的唯一标识
16、getAppData();//获取应用数据
17、getAppOrgData();//获取应用树形结构数据
17、getInterData:function(functionid) //获取界面对象
18、getTemplateData:function(functionid) //获取模板对象
19、getInterComponent:function(functionid,index) //获取页面中的某个组件的数据 返回组件对象
20、getSourceComponent:function(functionid)  // 获取页面加载的
19、getHtml5Data:function(functionid) //获取html5对象
20、getNativeData:function(functionid) //获取native对象
21、getRootInterData:function() //获取根界面对象
22、load() //加载或重新加载应用 若未设置首页此方法不会执行
23、getXmlString:function() //获取应用的xml字符串
24、getLogs() //错误信息 返回错误对象集合
25、removeInterByFunctionid:function(functionid) //删除界面
26、removeInterComponent:function(functionid,index) //移除组件
27、addInterComponent:function(functionid,index,direct,code)//添加组件  direct插入方向  pre-向上插入  next-向下插入  code 要插入的控件标识
28、moveInterComponent:function(functionid,index,direct,oindex) // oindex-被移动控件 index-移动到控件

/*对象说明
应用对象
{
	appid:'',//应用id
	appname:'',//应用名称
	appversion:'',//版本
	appicon:'',//图标
	bindurl:'',//绑定地址
	rootfunctionid:'',//根界面标识
	functions:''//界面对象集合
}
界面对象
{
	functionid:'',//界面标识
	functionname:'',//界面名称
	functiontype:'',//界面类型 1-模板 2-html5 3-native
	template:'', //template对象 当界面类型为1时有效
	html5:'',//html5对象 当界面类型为2时有效
	native:''//native对象 当界面类型为1时有效
}
template对象
[{
	code:'',//控件类型标识
	name:'',//控件类型名称
	attrs:{},//属性表
}...]
html5对象
{
	startpage:''//字符串，该功能应调用的html5起始页
}
native对象
{
	type:'',//原生功能类型：scanbarcode/scanbcard/groupnews/circlenews
	actionurl:''//原生功能获取的数据提交地址，提交参数为data
}
标题控件对象
{
	code:'',
	name:'',
	attrs:{
		text:''//标题,
		color:''//字体颜色，
		pic:''//标题背景图片
	}
}
菜单控件对象
{
	code:'',
	name:'',
	attrs:{
		position:'',//菜单位置，R指右上角，L指左上角
		menuitems:[{
			itemname:'',//菜单名
			itemicon:'',//菜单图标
			functionid:'',//菜单跳转的界面标识
		}...]
	}
}
列表控件对象
{
	code:'',
	name:'',
	attrs:{
		type:'',//1表示静态列表，2表示动态列表
		style:'',//NORMAL/GRID3/GRID4
		listitems:{
			itemname:'',//列表项名
			itemicon:'',//列表项图标
			functionid:'',//列表项跳转的界面标识
		},//静态列表 为静态列表时有效
		listurl:'',//动态列表获取数据的URL 为动态列表时有效
		listurlpara:'',//动态列表获取数据的URL参数名称 为动态列表时有效
		functionid:''//跳转的界面标识 为动态列表时有效
	}
}
应用列表控件对象
{
	code:'',
	name:'',
	attrs:{
		style:''//NORMAL/GRID3/GRID4
	}
}
轮询控件对象
{
	code:'',
	name:'',
	attrs:{
		timer:1,/时间间隔
		pics:[{
			text:''/图片描述
			url:''/轮询的图片地址
		}...],
		listurl:'' //动态获取地址
	}
}
搜索控件对象
{
	code:'',
	name:'',
	attrs:{
		url:'',
		text:'',
		functionid:''
	}
}
导航控件
{
	code:'',
	name:'',
	attrs:{
		bgcolor,
		bgcolor_active,
		navitems:[
		{
			itemname:'',
			itemicon:'',
			itemicon_active:'',
			actiontype,// 字符串，点击导航按钮后的动作类型 MSGCENER：消息中心，对应目前APP中首页 COMMUNICATE：沟通/通讯录对应目前APP中的沟通 CIRCLE：圈子/微博/动态，对应目前APP中的圈子 SETTING：设置，对应目前APP中的设置 TEMPLATE：模板，显示XML中的UI定义
			functionid,
			target,
			template //template对象
		}...]
	}
}
tabs控件
{
	code:'',
	name:'',
	attrs:{
		bgcolor,
		bgcolor_active,
		tabitems:[{
			itemname:'',
			itemname:'',
			itemicon_active:'',
			functionid,
			target,
			template:template对象集合
		}...]
	}
}
userprofile控件
{
	code:'',
	name:'',
	attrs:{
		items:[{
			itemname:'',
			itemicon:'',
			dataurl:'',
			functionid:''
		}...],
		bgpic:'',
		header:'',
		color:'',
		bgcolor:''
	}
}
userbasicinfo控件
{
	code:'',
	name:'',
	attrs:{
		style:'',
		functionid:'',
		color:'',
		bgcolor:''
	}
}
错误对象
'9999' 系统级错误
'0001' 应用级错误
'0002' 页面级错误
'0003' 组件级错误
{
	returncode:'',//错误码
	appid:'',//
	functionid:'',//
	code,//
	index,//
	msg//错误描述
}
/***************说明结束***************/

//构造器
var BUSIERRORCODE="";
var XmlBuilder=function(){
	this.appid=null
	this.xmlDom=null;
	var xmlToString=function(xmlData) {
		var xmlString;
		if (typeof window.XMLSerializer != "undefined" ) {
		  xmlString = (new XMLSerializer()).serializeToString(xmlData);
		}
		else {
		xmlString = xmlData.xml;
		}
		var result = xmlString.replace(/xmlns\=\"\"/g, "");
		return result.replace("<mapp>","<mapp xmlns=\"http://im.fafacn.com/namespace/mapp\">");
	};
	this.getXmlString=function(){
		return xmlToString(this.xmlDom);
	};
}
XmlBuilder.prototype={
	initWithXmlString:function(xmlString){
		this.xmlDom=$.parseXML(xmlString);
	},
	initWithXml:function(xmldom){
		this.xmlDom=xmldom;
	},
	bRoot:function(appinfo){
		if(this.xmlDom==null || this.xmlDom.children.length==0 || this.xmlDom.childNodes[0].nodeName!="mapp")
		{
			this.xmlDom=$.parseXML("<mapp/>");
			$(this.xmlDom).children("mapp").attr("xmlns","http://im.fafacn.com/namespace/mapp");
		}
		var html=[];
		html.push("<basicinfo>");
		html.push("<appid>"+appinfo.appid+"</appid>");
    	html.push("<appversion>"+(appinfo.appversion==null?"":appinfo.appversion)+"</appversion>");
    	html.push("<appname>"+(appinfo.appname==null?"":appinfo.appname)+"</appname>");
    	html.push("<appicon>"+(appinfo.appicon==null?"":appinfo.appicon)+"</appicon>");
    	html.push("<bindurl>"+(appinfo.bindurl==null?"":appinfo.bindurl)+"</bindurl>");
    	html.push("<rootfunctionid>"+(appinfo.rootfunctionid==null?"":appinfo.rootfunctionid)+"</rootfunctionid>");
		html.push("</basicinfo>");
		$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
	},
	getRootFunctionid:function(){
		var $dom=$(this.xmlDom).children("mapp").children("basicinfo");
		return $dom.children("rootfunctionid").text();
	},
	setAppInfo:function(appinfo){
		$(this.xmlDom).children("basicinfo").remove();
		var html=[];
		html.push("<basicinfo>");
		html.push("<appid>"+appinfo.appid+"</appid>");
    	html.push("<appversion>"+appinfo.appversion+"</appversion>");
   		html.push("<appname>"+appinfo.appname+"</appname>");
    	html.push("<appicon>"+appinfo.appicon+"</appicon>");
    	html.push("<bindurl>"+appinfo.bindurl+"</bindurl>");
    	html.push("<rootfunctionid>"+appinfo.rootfunctionid+"</rootfunctionid>");
		html.push("</basicinfo>");
		$(this.xmlDom).prepend($($.parseXML(html.join(''))).children());
	},
	updateAppInfo:function(key,val){
		$(this.xmlDom).children("mapp").children("basicinfo").children(key).text(val);
	},
	updateInterInfo:function(functionid,key,val){
		var interDom=this.getInterByFunctionid(functionid);
		$(interDom).children(key).text(val);
	},
	setRootInterface:function(functionid){
		this.updateAppInfo("rootfunctionid",functionid);
	},
	addInterface:function(info){
		var html=[];
		if(this.getInterByFunctionid(info.functionid)==null){
			html.push("<function>");
			html.push("<functionid>"+info.functionid+"</functionid>");
			html.push("<functionname>"+info.functionname+"</functionname>");
			html.push("<functiontype>"+info.functiontype+"</functiontype>");
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
		}
		if(info.functiontype=='1'){
			this.setInterTemplate(info.functionid,info.template);
		}
		else if(info.functiontype=='2'){//html5
			this.setInterHTML5(info.functionid,info.html5);
		}
		else if(info.functiontype=='3'){
			this.setInterNative(info.functionid,info.native);
		}
		else if(info.functiontype=='4'){
			this.setInterWeb(info.functionid,info.webapp);
		}
		else if(info.functiontype=='5'){
			this.setInterMobile(info.functionid,info.mobileapp);
		}
		return info.functionid;
	},
	addInterfaceWidthTempXml:function(functionid,functionname,tempXml,navorder){
		if(this.getInterByFunctionid(functionid)==null){
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+functionid+"</functionid>");
			html.push("<functionname>"+functionname+"</functionname>");
			html.push("<functiontype>1</functiontype>");
			html.push("<template></template>");
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
			this.setInterTempWithXml(functionid,tempXml);
		}
		else{
			$(this.getInterByFunctionid(functionid)).children("functionname").text(functionname);
			this.setInterTempWithXml(functionid,tempXml,navorder);
		}
	},
	addInterfaceWithHTML5Xml:function(functionid,functionname,html5Xml){
		if(this.getInterByFunctionid(functionid)==null){
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+functionid+"</functionid>");
			html.push("<functionname>"+functionname+"</functionname>");
			html.push("<functiontype>2</functiontype>");
			html.push(html5Xml);
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
		}
	},
	addInterfaceWithWebXml:function(functionid,functionname,webXml){
		if(this.getInterByFunctionid(functionid)==null){
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+functionid+"</functionid>");
			html.push("<functionname>"+functionname+"</functionname>");
			html.push("<functiontype>4</functiontype>");
			html.push(webXml);
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
		}
	},
	addInterfaceWithMobileXml:function(functionid,functionname,MobileXml){
		if(this.getInterByFunctionid(functionid)==null){
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+functionid+"</functionid>");
			html.push("<functionname>"+functionname+"</functionname>");
			html.push("<functiontype>5</functiontype>");
			html.push(MobileXml);
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
		}
	},
	addInterfaceWithNativeXml:function(functionid,functionname,nativeXml){
		if(this.getInterByFunctionid(functionid)==null){
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+functionid+"</functionid>");
			html.push("<functionname>"+functionname+"</functionname>");
			html.push("<functiontype>3</functiontype>");
			html.push(nativeXml);
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
		}
	},
	moveInterComponent:function(functionid,index,direct,oindex){
		var interDom=this.getInterByFunctionid(functionid);
		var TemplateDom=$(interDom).children("template").get(0);
		var dom=this.getDomByPath(TemplateDom,oindex);
		var paths=oindex.toString().split('-');
		oindex=parseInt(paths[paths.length-1]);
		var old=$(dom).children().get(oindex-1);
		
		var dom2=this.getDomByPath(TemplateDom,index);
		var paths2=index.toString().split('-');
		index=parseInt(paths2[paths2.length-1]);
		
		if(index==1 && $(dom2).children().length==0){
			$(dom2).append(old);
			return;
		}
		if(direct=='pre'){
			$($(dom2).children().get(index-1)).before(old);
		}
		else if(direct=='next'){
			$($(dom).children().get(index-1)).after(old);
		}
	},
	addInterComponent:function(functionid,index,direct,code){
		var interDom=this.getInterByFunctionid(functionid);
		if(code=="component_groupnews"){
				this.setNativeGroupNews(functionid,index,null);
				return;
		}
		else if(code=="component_circlenews"){
				this.setNativeCircleNews(functionid,index,null);
				return;
		}
		else if(code=="component_publicaccount"){
				this.setNativeMicro(functionid,index,null);
				return;
		}
		else if(code=="component_repository"){
				this.setNativeRepository(functionid,index,null);
				return;
		}
		else if(code=="component_contacts"){
				this.setNativeContacts(functionid,index,null);
				return;
		}
		else if(code=="component_message"){
				this.setNativeMessage(functionid,index,null);
				return;
		}
		else if(code=="component_enoweibo"){
				this.setNativeBlog(functionid,index,null);
				return;
		}
		else if(code=="component_setting"){
				this.setNativeSetting(functionid,index,null);
				return;
		}
		else if(code=="component_matchlist"){
				this.setNativeMatchList(functionid,index,null);
				return;
		}
		else if(code=="component_matchdetail"){
				this.setNativeMatchDetail(functionid,index,null);
				return;
		}
		else if(code=="component_goodsdetail"){
				this.setNativeGoodsDetail(functionid,index,null);
				return;
		}
		else{
			if($(interDom).children("template").length==0){
				$(interDom).children("functiontype").text("1");
				$(interDom).children("html5,native,template,webapp,mobileapp").remove();
				var html=[];
				html.push("<template></template>");
				$(interDom).append($($.parseXML(html.join(''))).children());
			}
		}
		var TemplateDom=$(interDom).children("template").get(0);
		var dom=this.getDomByPath(TemplateDom,index);
		var paths=index.toString().split('-');
		var oldindex=index;
		index=parseInt(paths[paths.length-1]);
		if(code=="component_nav"){
				$(TemplateDom).children().remove();
				$(TemplateDom).append($($.parseXML(ComponentExpectAttrs.get(code))).children());
				return "1";
		}
		if(index==1 && $(dom).children().length==0){
			$(dom).append($($.parseXML(ComponentExpectAttrs.get(code))).children());
			return index;
		}
		if(direct=='pre'){
			$($(dom).children().get(index-1)).before($($.parseXML(ComponentExpectAttrs.get(code))).children());
		}
		else if(direct=='next'){
			$($(dom).children().get(index-1)).after($($.parseXML(ComponentExpectAttrs.get(code))).children());
		}
		var newindex="";
		for(var i=0;i<paths.length-1;i++){
			if(i==0){
				newindex+=paths[i];
			}
			else{
				newindex+="-"+paths[i];
			}
		}
		if(direct=='pre')
			newindex=oldindex;
		else{
			newindex+=((newindex==""?"":"-")+(parseInt(paths[paths.length-1])+1).toString());
		}
		return newindex;
	},
	getInterByFunctionid:function(functionid){
		var functions=$(this.xmlDom).children("mapp").children("function");
		if(typeof(functions)=='undefined' || functions.length==0)return null;
		for(var i=0;i<functions.length;i++){
			if(functionid==$(functions[i]).children("functionid").text())
				return functions[i];
		}
		return null;
	},
	setInterTempWithXml:function(functionid,tmpxml,navorder){
		var interDom=this.getInterByFunctionid(functionid);
		$(interDom).children("html5,native,webapp,mobileapp").remove();
		$(interDom).children("functiontype").text("1");
		$tempDom=$(typeof(tmpxml)=="string" ? $.parseXML(tmpxml) : tmpxml).children();
		if($tempDom[0].nodeName!="template")
			$tempDom=$tempDom.children("mapp").children();
		if($(interDom).children("template").length>0){
			var $oldDom=$(interDom).children("template");
			if(navorder!=null){
				interDom=$oldDom.children("nav").children().get(navorder);
				$oldDom=$($oldDom.children("nav").children().get(navorder)).children("template");
			}
			var comDoms=$oldDom.children();
			var cc=new CHashTable();
			for(var i=0;i<comDoms.length;i++){
				var n=cc.get(comDoms[i].nodeName)==null?0:cc.get(comDoms[i].nodeName);
				if($tempDom.children(comDoms[i].nodeName).length>n){
					var attrs=ComponentExpectAttrs.get(comDoms[i].nodeName);
					var $dom=$($tempDom.children(comDoms[i].nodeName).get(n));
					var rep=true;
					for(var j=0;j<attrs.length;j++){
						$dom.attr(attrs[j],$(comDoms[i]).attr(attrs[j]));
					}
					if(rep){
						if(window.navigator.appVersion.indexOf("MSIE")>-1){
							$dom.children().remove();
							var childs=comDoms[i].childNodes;
							while(childs.length>0){
								$dom.append(childs[0]);
							}
						}
						else{
							$dom.html(comDoms[i].innerHTML);
						}
						n++;
						cc.set(comDoms[i].nodeName,parseInt(n));
					}
				}
			}
			$oldDom.remove();
		}
		$(interDom).append($tempDom);
	},
	setInterHTML5:function(functionid,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom!=null){
			$(interDom).children("functiontype").text("2");
			$(interDom).children("html5,native,template,webapp,mobileapp").remove();
			var html=[];
			if(info.plugin!=null)
			{
				html.push("<plugin>");
				html.push("<hplugin_id>"+info.plugin.id+"</hplugin_id>");
				html.push("<hplugin_ver>"+info.plugin.version+"</hplugin_ver>");
				html.push("<hplugin_downurl>"+info.plugin.downurl+"</hplugin_downurl>");
				html.push("</plugin>");
				$(interDom).parent().children('plugin').remove();
				$(interDom).parent().append($($.parseXML(html.join(''))).children());
			}
			html=[];
			html.push("<html5>");
			if(info){
				html.push("<startpage>"+info.startpage+"</startpage>");
				html.push("<encrypt>"+info.encrypt+"</encrypt>");
			}
			html.push("</html5>");
			$(interDom).children('html5').remove();
			$(interDom).append($($.parseXML(html.join(''))).children());
		}
	},
	setInterWeb:function(functionid,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom!=null){
			$(interDom).children("functiontype").text("4");
			$(interDom).children("html5,native,template,webapp,mobileapp").remove();
			
			html=[];
			html.push("<webapp>");
			if(info){
				html.push("<url>"+info.url+"</url>");
				html.push("<encrypt>"+info.encrypt+"</encrypt>");
			}
			html.push("</webapp>");
			$(interDom).children('webapp').remove();
			$(interDom).append($($.parseXML(html.join(''))).children());
		}
	},
	setInterMobile:function(functionid,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom!=null){
			$(interDom).children("functiontype").text("5");
			$(interDom).children("html5,native,template,webapp,mobileapp").remove();
			
			html=[];
			html.push("<mobileapp>");
			if(info){
				html.push("<android_url>"+info.android_url+"</android_url>");
				html.push("<ios_url>"+info.ios_url+"</ios_url>");				
				html.push("<scheme>"+info.scheme+"</scheme>");
			}
			html.push("</mobileapp>");
			$(interDom).children('mobileapp').remove();
			$(interDom).append($($.parseXML(html.join(''))).children());
		}
	},
	setInterNative:function(functionid,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)
		{
			var html=[];
			html.push("<function>");
			html.push("<functionid>"+info.functionid+"</functionid>");
			html.push("<functionname>"+info.functionname+"</functionname>");
			html.push("<functiontype>3</functiontype>");
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
    		html.push("<actionurl>"+info.actionurl+"</actionurl>");
    		if(info.parameters!=null)
    			html.push(info.parameters);
			html.push("</native>");
			html.push("</function>");
			$(this.xmlDom).children("mapp").append($($.parseXML(html.join(''))).children());
			return;
		}
		if(interDom!=null){
			$(interDom).children("functiontype").text("3");
			$(interDom).children("html5,native,template,webapp,mobileapp").remove();
			var html=[];
			if(info){
				html.push("<native>");
				html.push("<type>"+info.type+"</type>");
    			html.push("<actionurl>"+info.actionurl+"</actionurl>");
    			if(info.parameters!=null)
    				html.push(info.parameters);
				html.push("</native>");
			}
			$(interDom).append($($.parseXML(html.join(''))).children());
		}
	},
	setInterTemplate:function(functionid,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom!=null){
			$(interDom).children("functiontype").text("1");
			$(interDom).children("html5,native,template,webapp,mobileapp").remove();
			if(info){
				var html=[];
				html.push("<template>");
				html.push("</template>");
				$(interDom).append($($.parseXML(html.join(''))).children());
				for(var i=0;i<info.length;i++){
					this.setInterComponent(functionid,i+1,info[i]);
				}
			}
		}
	},
	getInterTemplateHTML:function(info){
		var html=[];
		html.push("<template>");
		for(var i=0;i<info.length;i++){
			html.push(this.getInterComponentHTML(info[i]).join(''));
		}
		html.push("</template>");
		return html;
	},
	setInterComponent:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		if(info.code=="component_groupnews"){
				this.setNativeGroupNews(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_circlenews"){
				this.setNativeCircleNews(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_publicaccount"){
				this.setNativeMicro(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_repository"){
				this.setNativeRepository(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_contacts"){
			  this.setNativeContacts(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_message"){
			  this.setNativeMessage(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_enoweibo"){
			  this.setNativeBlog(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_setting"){
			  this.setNativeSetting(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_matchlist"){
				this.setNativeMatchList(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_matchdetail"){
				this.setNativeMatchDetail(functionid,index,info.attrs);
				return;
		}
		else if(info.code=="component_goodsdetail"){
				this.setNativeGoodsDetail(functionid,index,info.attrs);
				return;
		}
		var $TemplateDom=$(interDom).children("template");
		var paths=index.toString().split("-");
		var com=$(this.getDomByPath($TemplateDom[0],index)).children().get(parseInt(paths[paths.length-1])-1);
		if(info.code=="component_title"){//标题
				this.setTemplateTitle(functionid,index,info.attrs);
		}
		else if(info.code=="component_menu"){//菜单
				this.setTemplateMenu(functionid,index,info.attrs);
		}
		else if(info.code=="component_list"){
				this.setTemplateList(functionid,index,info.attrs);
		}
		else if(info.code=="component_switch"){
				this.setTemplateSwitch(functionid,index,info.attrs);
		}
		else if(info.code=="component_nav"){
				this.setTemplateNav(functionid,index,info.attrs);
		}
		else if(info.code=="component_search"){
				this.setTemplateSearch(functionid,index,info.attrs);
		}
		else if(info.code=="component_tabs"){
				this.setTemplateTabs(functionid,index,info.attrs);
		}
		else if(info.code=="component_applist"){
				this.setTemplateAppList(functionid,index,info.attrs);
		}
		else if(info.code=="component_userprofile"){
				this.setTemplateUserProfile(functionid,index,info.attrs);
		}
		else if(info.code=="component_userbasicinfo"){
				this.setTemplateUserBasicInfo(functionid,index,info.attrs);
		}
		else if(info.code=="component_functionbar"){
				this.setTemplateFunctionBar(functionid,index,info.attrs);
		}
		$(com).remove();
	},
	getInterComponentHTML:function(info){
		var html=[];
		if(info.code=="component_title"){//标题
				html=this.getTemplateTitleHTML(info.attrs);
		}
		else if(info.code=="component_menu"){//菜单
				html=this.getTemplateMenuHTML(info.attrs);
		}
		else if(info.code=="component_list"){
				html=this.getTemplateListHTML(info.attrs);
		}
		else if(info.code=="component_switch"){
				html=this.getTemplateSwitchHTML(info.attrs);
		}
		else if(info.code=="component_nav"){
				html=this.getTemplateNavHTML(info.attrs);
		}
		else if(info.code=="component_search"){
				html=this.getTemplateSearchHTML(info.attrs);
		}
		else if(info.code=="component_tabs"){
				html=this.getTemplateTabsHTML(info.attrs);
		}
		else if(info.code=="component_applist"){
				html=this.getTemplateAppListHTML(info.attrs);
		}
		else if(info.code=="component_userprofile"){
				html=this.getTemplateUserProfileHTML(info.attrs);
		}
		else if(info.code=="component_userbasicinfo"){
				html=this.getTemplateUserBasicInfoHTML(info.attrs);
		}
		else if(info.code=="component_functionbar"){
				html=this.getTemplateFunctionBarHTML(info.attrs);
		}
		return html;
	},
	parseIndex:function(index){
		return index.toString().split('-');
	},
	setNativeGroupNews:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_groupnews"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeCircleNews:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
			html.push("<title>"+info.title+"</title>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_circlenews"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeMicro:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_publicaccount"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeRepository:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_repository"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeContacts:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
			html.push("<title>"+info.title+"</title>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_contacts"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeMessage:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
			html.push("<title>"+info.title+"</title>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_message"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeBlog:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_enoweibo"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeMatchDetail:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<url>"+info.url+"</url>");
  		html.push("<para_code>"+info.para_code+"</para_code>");
  		html.push("<title>"+info.title+"</title>");
  		html.push("<comment_url>"+info.comment_url+"</comment_url>");
  		if(info.list){
  			html.push("<list>");
  			html.push("<listurl>"+info.list.listurl+"</listurl>");
  			html.push("<listurlpara>"+info.list.listurlpara+"</listurlpara>");
  			if(typeof(info.list.functionid)=="string")
					html.push("<functionid>"+info.list.functionid+"</functionid>");
				else
				{
					html.push("<functionid target='"+info.list.functionid.target+"'>"+info.list.functionid.text+"</functionid>");
				}
  			html.push("</list>");
  		}
  		if(info.functionbar){
  			html.push("<functionbar>");
  			if(info.functionbar.items){
  				for(var i=0;i<info.functionbar.items.length;i++){
  					var item=info.functionbar.items[i];
  					html.push("<item>");
  					html.push("<text>"+item.text+"</text>");
  					html.push("<para>"+item.para+"</para>");
  					html.push("<icon>"+item.icon+"</icon>");
  					if(typeof(item.functionid)=="string")
							html.push("<functionid>"+item.functionid+"</functionid>");
						else
						{
							html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
						}
  					html.push("</item>");
  				}
  			}
  			html.push("</functionbar>");
  		}
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_matchdetail"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeGoodsDetail:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<url>"+info.url+"</url>");
  		html.push("<title>"+info.title+"</title>");
  		html.push("<price_url>"+info.price_url+"</price_url>");
  		html.push("<spec_url>"+info.spec_url+"</spec_url>");
  		html.push("<color_url>"+info.color_url+"</color_url>");
  		html.push("<stock_url>"+info.stock_url+"</stock_url>");
  		html.push("<buy_url>"+info.buy_url+"</buy_url>");
  		html.push("<join_url>"+info.join_url+"</join_url>");
  		html.push("<comment>");
		  html.push("<native>");
		  html.push("<type>"+info.comment.attrs.type+"</type>");
		  html.push("<url>"+info.comment.attrs.url+"</url>");
		  html.push("<para>"+info.comment.attrs.para+"</para>");
		  html.push("</native>");
  		html.push("</comment>");
  		html.push("<fav>");
  		html.push("<native>");
		  html.push("<type>"+info.fav.attrs.type+"</type>");
		  html.push("<url>"+info.fav.attrs.url+"</url>");
		  html.push("<para>"+info.fav.attrs.para+"</para>");
		  html.push("</native>");
  		html.push("</fav>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_goodsdetail"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeMatchList:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
  		html.push("<url>"+info.url+"</url>");
  		html.push("<para_code>"+info.para_code+"</para_code>");
  		html.push("<title>"+info.title+"</title>");
  		if(typeof(info.functionid)=="string")
				html.push("<functionid>"+info.functionid+"</functionid>");
			else
			{
				html.push("<functionid target='"+info.functionid.target+"'>"+info.functionid.text+"</functionid>");
			}
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_matchlist"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setNativeSetting:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		$(interDom).children("functiontype").text("3");
		$(interDom).children("html5,native,template,webapp,mobileapp").remove();
		var html=[];
		if(info!=null){
			html.push("<native>");
			html.push("<type>"+info.type+"</type>");
			html.push("<title>"+info.title+"</title>");
  		html.push("<actionurl>"+info.actionurl+"</actionurl>");
			html.push("</native>");
		}
		else{
			html.push(ComponentExpectAttrs.get("component_setting"));
		}
		$(interDom).append($($.parseXML(html.join(''))).children());
	},
	setTemplateFunctionBar:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateFunctionBarHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateFunctionBarHTML:function(info){
		var html=[];
		if(info){
			html.push("<functionbar bgcolor='"+info.bgcolor+"' color='"+info.color+"' position='"+info.position+"'>");
			html.push("<items>");
			for(var i=0;i<info.items.length;i++){
				html.push("<item style='"+info.items[i].style+"' arrangement='"+info.items[i].arrangement+"'>");
				html.push("<text>"+info.items[i].text+"</text>");
				html.push("<icon>"+info.items[i].icon+"</icon>");
				html.push("<dataurl>"+info.items[i].dataurl+"</dataurl>");
				html.push("<para>"+info.items[i].para+"</para>");
				if(typeof(info.items[i].functionid)=="string")
  					html.push("<functionid>"+info.items[i].functionid+"</functionid>");
  				else
  				{
  					html.push("<functionid target='"+info.items[i].functionid.target+"'>"+info.items[i].functionid.text+"</functionid>");
  				}
				html.push("</item>");
			}
			html.push("</items>");
			html.push("</functionbar>");
		}
		return html;
	},
	setTemplateUserBasicInfo:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateUserBasicInfoHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateUserBasicInfoHTML:function(info){
		var html=[];
		if(info){
			html.push("<userbasicinfo bgcolor='"+info.bgcolor+"' color='"+info.color+"'>");
			html.push("<style>"+info.style+"</style>");
			if(typeof(info.functionid)=="string")
  					html.push("<functionid>"+info.functionid+"</functionid>");
  				else
  				{
  					html.push("<functionid target='"+info.functionid.target+"'>"+info.functionid.text+"</functionid>");
  				}
			html.push("</userbasicinfo>");
		}
		return html;
	},
	setTemplateUserProfile:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateUserProfileHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateUserProfileHTML:function(info){
		var html=[];
		if(info){
			html.push("<summary bgcolor='"+info.bgcolor+"' color='"+info.color+"'>");
			html.push("<bgpic>"+info.bgpic+"</bgpic>");
			html.push("<header>"+info.header+"</header>");
			html.push("<items>");
			for(var i=0;i<info.items.length;i++){
				var item = info.items[i];
				html.push("<item>");
				html.push("<itemname>"+item.itemname+"</itemname>");
				html.push("<itemicon>"+item.itemicon+"</itemicon>");
				html.push("<dataurl>"+item.dataurl+"</dataurl>");
  				if(typeof(item.functionid)=="string")
  					html.push("<functionid>"+item.functionid+"</functionid>");
  				else
  				{
  					html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
  				}				
				html.push("</item>");
			}
			html.push("</items>");
			html.push("</summary>");
		}
		return html;
	},
	setTemplateTabs:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateTabsHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getDomByPath:function(dom,index){
		var paths=this.parseIndex(index);
		var i=arguments[2]?arguments[2]:0;
		if(paths.length==i+1)
			return dom;
		else{
			var rooter=parseInt(paths[i]);
			var nodename=dom.nodeName;
			if(nodename=='template'){
				return this.getDomByPath($(dom).children().get(rooter-1),index,i+1);
			}
			else if(nodename=='title'){
					return dom;
			}
			else if(nodename=='menu'){
				  return dom;
			}
			else if(nodename=='list'){
				  return dom;
			}
			else if(nodename=='applist'){
					return dom;
			}
			else if(nodename=='switch'){
					return dom;
			}
			else if(nodename=='nav'){
					return this.getDomByPath($($(dom).children("navitem").get(rooter-1)).children("template").get(0),index,i+1);
			}
			else if(nodename=='search'){
					return dom;
			}
			else if(nodename=='tabs'){
					return this.getDomByPath($($(dom).children("tabitem").get(rooter-1)).children("template").get(0),index,i+1);
			}
			else if(nodename=='summary'){
				return dom;
			}
			else if(nodename=='userbasicinfo'){
				return dom;
			}
			else if(nodename=='functionbar'){
				return dom;
			}
			return null;
		}
	},
	getTemplateTabsHTML:function(info){
		var html=[];
		if(info){
			html.push("<tabs bgcolor='"+info.bgcolor+"' bgcolor_active='"+info.bgcolor_active+"'>");
			for(var i=0;i<info.tabitems.length;i++){
				var item = info.tabitems[i];
				html.push("<tabitem>");
				html.push("<itemname>"+item.itemname+"</itemname>");
				html.push("<itemicon>"+item.itemicon+"</itemicon>");
				html.push("<itemicon_active>"+item.itemicon_active+"</itemicon_active>");
  				if(typeof(item.functionid)=="string")
  					html.push("<functionid>"+item.functionid+"</functionid>");
  				else
  				{
  					html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
  				}				
				html.push("</tabitem>");
			}
			html.push("</tabs>");
		}
		return html;
	},
	setTemplateSearch:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateSearchHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateSearchHTML:function(info){
		var html=[];
		if(info){
			html.push("<search>");
			html.push("<url>"+info.url.replace(/&/g,'&amp;')+"</url>");
			html.push("<text>"+info.text+"</text>");
  			if(typeof(info.functionid)=="string")
  				html.push("<functionid>"+info.functionid+"</functionid>");
  			else
  			{
  				html.push("<functionid target='"+info.functionid.target+"'>"+info.functionid.text+"</functionid>");
  			}
			html.push("</search>");
		}
		return html;
	},
	setTemplateAppList:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateAppListHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateAppListHTML:function(info){
		var html=[];
		if(info){
			html.push("<list type='3' style='"+info.style+"'></list>");
		}
		return html;
	},
	setTemplateNav:function(functionid,index,info){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateNavHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateNavHTML:function(info){
		var html=[];
		if(info){
			html.push("<nav bgcolor='"+info.bgcolor+"' bgcolor_active='"+info.bgcolor_active+"'>");
			for(var i=0;i<info.navitems.length;i++){
				var item = info.navitems[i];
				html.push("<navitem>");
				html.push("<itemname>"+item.itemname+"</itemname>");
				html.push("<itemicon>"+item.itemicon+"</itemicon>");
				html.push("<itemicon_active>"+item.itemicon_active+"</itemicon_active>");
  				if(typeof(item.functionid)=="string")
  					html.push("<functionid>"+item.functionid+"</functionid>");
  				else
  				{
  					html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
  				}				
//				if(info.navitems[i].actiontype=="TEMPLATE"){
//					html.push(this.getInterTemplateHTML(info.navitems[i].template).join(''));
//				}
				html.push("</navitem>");
			}
			html.push("</nav>");
		}
		return html;
	},
	setTemplateSwitch:function(functionid,index,info)
	{
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		var html=[];
		if(info){
			html=this.getTemplateSwitchHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
				$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
			else
				$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateSwitchHTML:function(info){
		var html=[];
		if(info){
			html.push("<switch timer='"+info.timer+"'>");
			for(var i=0;i<info.pics.length;i++){
				html.push("<pic>");
				html.push("<url>"+info.pics[i].url+"</url>");
				html.push("<text>"+info.pics[i].text+"</text>");
				html.push("</pic>");
			}
			if(info.listurl!=null)
				html.push("<listurl>"+info.listurl.replace(/&/g,'&amp;')+"</listurl>");
			html.push("</switch>");
		}
		return html;
	},
	setTemplateTitle:function(functionid,index,info)
	{
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		//$TemplateDom.children("title").remove();
		var html=[];
		if(info){
			html=this.getTemplateTitleHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateTitleHTML:function(info){
		var html=[];
		if(info){
			html.push("<title color='"+info.color+"'><text>"+info.text+"</text><pic>"+info.pic+"</pic></title>");
		}
		return html;
	},
	setTemplateMenu:function(functionid,index,info)
	{
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		//$TemplateDom.children("menu").remove();
		var html=[];
		if(info){
			html=this.getTemplateMenuHTML(info);
			var paths=this.parseIndex(index);
			var rooter=parseInt(paths[paths.length-1]);
			var dom=this.getDomByPath($TemplateDom[0],index);
			if(rooter>1)
					$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
				else
					$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateMenuHTML:function(info){
		var html=[];
		if(info){
			if(info.position)
					html.push("<menu position='"+info.position+"'>");
			else
					html.push("<menu>");
			if(info.menuitems){
					for(var i=0;i<info.menuitems.length;i++){
						var item=info.menuitems[i];
						html.push("<menuitem>");
						html.push("<itemname>"+item.itemname+"</itemname>");
  						html.push("<itemicon>"+item.itemicon+"</itemicon>");
  						if(typeof(item.functionid)=="string")
  							html.push("<functionid>"+item.functionid+"</functionid>");
  						else
  						{
  							html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
  						}
						html.push("</menuitem>");
					}
			}
			html.push("</menu>");
		}
		return html;
	},
	setTemplateList:function(functionid,index,info)
	{
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		var $TemplateDom=$(interDom).children("template");
		//$TemplateDom.children("list").remove();
		if(info){
			  var	html=[];
				html=this.getTemplateListHTML(info);
				var paths=this.parseIndex(index);
				var rooter=parseInt(paths[paths.length-1]);
				var dom=this.getDomByPath($TemplateDom[0],index);
				if(rooter>1)
						$($(dom).children().get(rooter-2)).after($($.parseXML(html.join(''))).children());
					else
						$(dom).prepend($($.parseXML(html.join(''))).children());
		}
	},
	getTemplateListHTML:function(info){
		var	html=[];
		if(info){
				html.push("<list type='"+info.type+"' style='"+info.style+"'>");
				if(info.type=='1'){
					if(info.listitems){
						for(var i=0;i<info.listitems.length;i++)
						{
							var item=info.listitems[i];
							html.push("<listitem>");
							html.push("<itemname>"+item.itemname+"</itemname>");
    						html.push("<itemicon>"+item.itemicon+"</itemicon>");
	  						if(typeof(item.functionid)=="string")
	  							html.push("<functionid>"+item.functionid+"</functionid>");
	  						else
	  						{
	  							html.push("<functionid target='"+item.functionid.target+"'>"+item.functionid.text+"</functionid>");
	  						}    						
							html.push("</listitem>");
						}
					}		
				}
				else if(info.type=='2'){
					if(info.listurl!=null)
						html.push("<listurl>"+info.listurl.replace(/&/g,'&amp;')+"</listurl>");
    				html.push("<listurlpara>"+info.listurlpara+"</listurlpara>");
  					if(typeof(info.functionid)=="string")
  						html.push("<functionid>"+info.functionid+"</functionid>");
  					else
  					{
  						html.push("<functionid target='"+info.functionid.target+"'>"+info.functionid.text+"</functionid>");
  					}
				}
				html.push("</list>");
		}
		return html;
	},
	removeInterByFunctionid:function(functionid){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom!=null){
			$(interDom).remove();
		}
	},
	removeInterComponent:function(functionid,index){
		var interDom=this.getInterByFunctionid(functionid);
		if(interDom==null)return;
		if($(interDom).children("template").length==0){
			$(interDom).children("native,html5,webapp,mobileapp").remove();
			$(interDom).children("functiontype").text("1");
			$(interDom).append($($.parseXML("<template></template>")).children());
			return;
		}
		var tempdom=$(interDom).children("template").get(0);
		var dom=this.getDomByPath(tempdom,index);
		var paths=index.toString().split('-');
		var index=parseInt(paths[paths.length-1]);
		$($(dom).children().get(index-1)).remove();
	},
	getXmlDom:function(){
		return this.xmlDom;
	},
	clear:function(){
		//删除无用的页面
		var els=$(this.xmlDom).find("functionid");
		var rootFunctionid=this.getRootFunctionid();
		var willClear=[];
		for(var i=0;i<els.length;i++){
			var currFunctionid=$(els[i]).text();
			if(currFunctionid=="" || currFunctionid==rootFunctionid)continue;
			var bool=false;
			for(var j=0;j<els.length;j++){
				if(i!=j && currFunctionid==$(els[j]).text()){
					bool=true;
					break;
				}
			}
			if(bool)continue;
			willClear.push(currFunctionid);
		}
		
		for(var i=0;i<willClear.length;i++){
			this.removeInterByFunctionid(willClear[i]);
		}
	}
}
//解析器
var XmlParser=function(){
  this.appid=null
	this.xmlDom=null;
}

XmlParser.prototype={
	initWithXml:function(xmldom){
		this.xmlDom=xmldom;
	},
	getInterByFunctionid:function(functionid){
		var functions=$(this.xmlDom).children("mapp").children("function");
		if(typeof(functions)=='undefined' || functions.length==0)return null;
		for(var i=0;i<functions.length;i++){
			if(functionid==$(functions[i]).children("functionid").text())
				return functions[i];
		}
		return null;
	},
	getRootFunctionid:function(){
		var $dom=$(this.xmlDom).children("mapp").children("basicinfo");
		return $dom.children("rootfunctionid").text();
	},
	getAppData:function(){
		var re={};
		var $dom=$(this.xmlDom).children("mapp").children("basicinfo");
		re.appid=$dom.children("appid").text();
		re.appversion=$dom.children("appversion").text();
		re.appname=$dom.children("appname").text();
		re.appicon=$dom.children("appicon").text();
		re.bindurl=$dom.children("bindurl").text();
		re.rootfunctionid=$dom.children("rootfunctionid").text();
		re.functions=[];
		var fucs=$(this.xmlDom).children("mapp").children("function");
		for(var i=0;i<fucs.length;i++){
			var functionid=$(fucs[i]).children("functionid").text();
			re.functions.push(this.getInterData(functionid));
		}
		return re;
	},
	getAppOrgData:function(){
		var re=arguments[0]?arguments[0]:[];
		var data=arguments[1]?arguments[1]:this.getAppData();
		var pId=arguments[2]?arguments[2]:"";
		var interId=arguments[3]?arguments[3]:data.rootfunctionid;
		for(var i=0;i<data.functions.length;i++){
			if(data.functions[i].functionid==interId){
				re.push({
					id:interId,
					pId:pId,
					name:data.functions[i].functionname,
					type:"interface",
					functionid:interId,
					functiontype:data.functions[i].functiontype,
					icon:"/bundles/fafatimewebase/images/inter.png"
				});
				if(data.functions[i].template){
					var template=data.functions[i].template;
					for(var j=0;j<template.length;j++){
						var comId=template[j].functionid+"_"+template[j].index;
						re.push({
							id:comId,
							pId:interId,
							code:template[j].code,
							index:template[j].index,
							functionid:template[j].functionid,
							type:"component",
							name:template[j].name,
							icon:"/bundles/fafatimewebase/images/com.png"
						});
						if(template[j].code=="component_menu"){
							var menuitems=template[j].attrs.menuitems;
							for(var n=0;n<menuitems.length;n++){
								if(menuitems[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,menuitems[n].functionid.text);
							}
						}
						else if(template[j].code=="component_list"){
							if(template[j].attrs.listitems){
								var listitems=template[j].attrs.listitems;
								for(var n=0;n<listitems.length;n++){
									if(listitems[n].functionid.text!="")
										re=this.getAppOrgData(re,data,comId,listitems[n].functionid.text);
								}
							}
							else if(template[j].attrs.functionid){
								if(template[j].attrs.functionid!='')
									re=this.getAppOrgData(re,data,comId,template[j].attrs.functionid.text?template[j].attrs.functionid.text:template[j].attrs.functionid);
							}
						}
						else if(template[j].code=="component_tabs"){
							var tabitems=template[j].attrs.tabitems;
							for(var n=0;n<tabitems.length;n++){
								if(tabitems[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,tabitems[n].functionid.text);
							}
						}
						else if(template[j].code=="component_nav"){
							var navitems=template[j].attrs.navitems;
							for(var n=0;n<navitems.length;n++){
								if(navitems[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,navitems[n].functionid.text);
							}
						}
						else if(template[j].code=="component_search"){
							if(template[j].attrs.functionid.text!="")
								re=this.getAppOrgData(re,data,comId,template[j].attrs.functionid.text);
						}
						else if(template[j].code=='component_userprofile'){
							var items=template[j].attrs.items;
							for(var n=0;n<items.length;n++){
								if(items[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,items[n].functionid.text);
							}
						}
						else if(template[j].code=='component_userbasicinfo'){
							if(template[j].attrs.functionid!='')
									re=this.getAppOrgData(re,data,comId,template[j].attrs.functionid.text?template[j].attrs.functionid.text:template[j].attrs.functionid);
						}
						else if(template[j].code=='component_functionbar'){
							var items=template[j].attrs.items;
							for(var n=0;n<items.length;n++){
								if(items[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,items[n].functionid.text);
							}
						}
					}
				}
				else if(data.functions[i].native){
					var comId=data.functions[i].native.functionid+"_"+data.functions[i].native.index;
					re.push({
							id:comId,
							pId:interId,
							code:data.functions[i].native.code,
							index:data.functions[i].native.index,
							functionid:data.functions[i].native.functionid,
							type:"component",
							name:data.functions[i].native.name,
							icon:"/bundles/fafatimewebase/images/com.png"
						});
					if(data.functions[i].native.code=="component_matchdetail"){
						var items=data.functions[i].native.attrs.functionbar.items;
						items.push(data.functions[i].native.attrs.list);
							for(var n=0;n<items.length;n++){
								if(items[n].functionid.text!="")
									re=this.getAppOrgData(re,data,comId,items[n].functionid.text);
							}
					}
					else if(data.functions[i].native.code=="component_matchlist"){
						  re=this.getAppOrgData(re,data,comId,data.functions[i].native.attrs.functionid.text?data.functions[i].native.attrs.functionid.text:data.functions[i].native.attrs.functionid);
					}
				}
				break;
			}
		}
		return re;
	},
	getInterData:function(functionid){
		var re={};
		var InterDom=this.getInterByFunctionid(functionid);
		if(InterDom==null)return null;
		re.functionid=$(InterDom).children("functionid").text();
		re.functionname=$(InterDom).children("functionname").text();
		re.functiontype=$(InterDom).children("functiontype").text();
		if(re.functiontype=='1'){
			re.template=this.getTemplateData(functionid);
		}
		else if(re.functiontype=='2'){
			re.html5=this.getHtml5Data(functionid);
		}
		else if(re.functiontype=='3'){
			re.native=this.getNativeData(functionid);
		}
		else if(re.functiontype=='4'){
			re.webapp=this.getWebAppData(functionid);
		}
		else if(re.functiontype=='5'){
			re.mobileapp=this.getMobileAppData(functionid);
		}
		return re;
	},
	getTemplateData:function(functionid){
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("template");
		return this.getTemplateDataByDom(functionid,$dom);
	},
	getTemplateDataByDom:function(functionid,$dom){
		var re=[];
		if(typeof($dom)!='undefined' && $dom.length>0){
			var temps=$dom.children();
			for(var i=0;i<temps.length;i++){
				var nodename=temps[i].nodeName;
				if(nodename=='title'){
					re.push(this.getTempTitleData(functionid,temps[i]));
				}
				else if(nodename=='menu'){
					re.push(this.getTempMenuData(functionid,temps[i]));
				}
				else if(nodename=='list'){
					re.push(this.getTempListData(functionid,temps[i]));
				}
				else if(nodename=='switch'){
					re.push(this.getTempSwitchData(functionid,temps[i]));
				}
				else if(nodename=='nav'){
					re.push(this.getTempNavData(functionid,temps[i]));
				}
				else if(nodename=='search'){
					re.push(this.getTempSearchData(functionid,temps[i]));
				}
				else if(nodename=='tabs'){
					re.push(this.getTempTabsData(functionid,temps[i]));
				}
				else if(nodename=='applist'){
					re.push(this.getTempAppListData(functionid,temps[i]));
				}
				else if(nodename=='summary'){
					re.push(this.getTempUserProfileData(functionid,temps[i]));
				}
				else if(nodename=='userbasicinfo'){
					re.push(this.getTempUserBasicInfoData(functionid,temps[i]));
				}
				else if(nodename=='functionbar'){
					re.push(this.getTempFunctionBarData(functionid,temps[i]));
				}
			}
		}
		return re;
	},
	parseIndex:function(index){
		return index.toString().split('-');
	},
	getDomByPath:function(dom,index){
		var paths=this.parseIndex(index);
		var i=arguments[2]?arguments[2]:0;
		if(paths.length==i+1)
			return dom;
		else{
			var rooter=parseInt(paths[i]);
			var nodename=dom.nodeName;
			if(nodename=='template'){
				return this.getDomByPath($(dom).children().get(rooter-1),index,i+1);
			}
			else if(nodename=='title'){
					return dom;
			}
			else if(nodename=='menu'){
				  return dom;
			}
			else if(nodename=='list'){
				  return dom;
			}
			else if(nodename=='applist'){
				  return dom;
			}
			else if(nodename=='switch'){
					return dom;
			}
			else if(nodename=='nav'){
					return this.getDomByPath($($(dom).children("navitem").get(rooter-1)).children("template").get(0),index,i+1);
			}
			else if(nodename=='search'){
					return dom;
			}
			else if(nodename=='tabs'){
					return this.getDomByPath($($(dom).children("tabitem").get(rooter-1)).children("template").get(0),index,i+1);
			}
			else if(nodename=='summary'){
				return dom;
			}
			else if(nodename=='userbasicinfo'){
				return dom;
			}
			else if(nodename=='functionbar'){
				return dom;
			}
			return null;
		}
	},
	getInterComponent:function(functionid,index){
		var InterDom=this.getInterByFunctionid(functionid);
		if($(InterDom).children("template").length==0){
			if($(InterDom).children("native").length>0){
				return this.getNativeData(functionid);
			}
			else if($(InterDom).children("html5").length>0){
				return this.getHtml5Data(functionid);
			}
			else if($(InterDom).children("webapp").length>0){
				return this.getWebAppData(functionid);
			}
			else if($(InterDom).children("mobileapp").length>0){
				return this.getMobileAppData(functionid);
			}
		}
		var acs=$(InterDom).children("template").get(0);
		var dom=this.getDomByPath(acs,index);
		var cs=$(dom).children();
		var paths=index.toString().split('-');
		index=parseInt(paths[paths.length-1]);
		if(cs.length>=index){
			var nodename=cs[index-1].nodeName;
			if(nodename=='title'){
					return this.getTempTitleData(functionid,cs[index-1]);
			}
			else if(nodename=='menu'){
				  return this.getTempMenuData(functionid,cs[index-1]);
			}
			else if(nodename=='list'){
				  return this.getTempListData(functionid,cs[index-1]);
			}
			else if(nodename=='switch'){
					return this.getTempSwitchData(functionid,cs[index-1]);
			}
			else if(nodename=='nav'){
					return this.getTempNavData(functionid,cs[index-1]);
			}
			else if(nodename=='search'){
					return this.getTempSearchData(functionid,cs[index-1]);
			}
			else if(nodename=='tabs'){
					return this.getTempTabsData(functionid,cs[index-1]);
			}
			else if(nodename=='applist'){
					return this.getTempAppListData(functionid,cs[index-1]);
			}
			else if(nodename=='summary'){
					return this.getTempUserProfileData(functionid,cs[index-1]);
			}
			else if(nodename=='userbasicinfo'){
					return this.getTempUserBasicInfoData(functionid,cs[index-1]);
			}
			else if(nodename=='functionbar'){
					return this.getTempFunctionBarData(functionid,cs[index-1]);
			}
		}
		else
			return null;
	},
	getSourceComponent:function(functionid){
		var funcs=$(this.xmlDom).find("functionid");
		var el=null;
		for(var i=0;i<funcs.length;i++){
			if($(funcs[i]).text()!=functionid)continue;
			if($(funcs[i]).parent().get(0).nodeName=="function")continue;
			el=funcs[i];
			break;
		}
		if(el!=null){
			var com=$(el).parentsUntil("title,menu,list,switch,nav,search,tabs,applist,summary").parent().get(0);
			var pFunctionid=$(el).parentsUntil("function").parent().children("functionid").text();
			if(com!=null){
				var nodename=com.nodeName;
				if(nodename=='title'){
						return this.getTempTitleData(pFunctionid,com);
				}
				else if(nodename=='menu'){
					  return this.getTempMenuData(pFunctionid,com);
				}
				else if(nodename=='list'){
					  return this.getTempListData(pFunctionid,com);
				}
				else if(nodename=='switch'){
						return this.getTempSwitchData(pFunctionid,com);
				}
				else if(nodename=='nav'){
						var nav=this.getTempNavData(pFunctionid,com);
						for(var i=0;i<nav.attrs.navitems.length;i++){
							if(nav.attrs.navitems[i].functionid.text==functionid){
								nav.order=i;
								break;
							}
						}
						return nav;
				}
				else if(nodename=='search'){
						return this.getTempSearchData(pFunctionid,com);
				}
				else if(nodename=='tabs'){
						var tabs=this.getTempTabsData(pFunctionid,com);
						for(var i=0;i<tabs.attrs.tabitems.length;i++){
							if(tabs.attrs.tabitems[i].functionid.text==functionid){
								tabs.tabsindex=i;
								break;
							}
						}
						return tabs;
				}
				else if(nodename=='applist'){
						return this.getTempAppListData(pFunctionid,com);
				}
				else if(nodename=='summary'){
						var summary=this.getTempUserProfileData(pFunctionid,com);
						for(var i=0;i<summary.attrs.items.length;i++){
							if(summary.attrs.items[i].functionid.text==functionid){
								summary.sumindex=i;
								break;
							}
						}
						return summary;
				}
				else if(nodename=='userbasicinfo'){
					return this.getTempUserBasicInfoData(pFunctionid,com);
				}
				else if(nodename=='functionbar'){
					return this.getTempFunctionBarData(pFunctionid,com);
				}
			}
		}
		return null;
	},
	getIndexByDom:function(dom){
		var	childs=$(dom).parent().children();
		for(var i=0;i<childs.length;i++){
			if(childs[i]==dom)return (i+1).toString();
		}
		return "0";
	},
	getTempFunctionBarData:function(functionid,dom){
		var re={};
		re.name='功能按钮';
		re.code='component_functionbar';
		re.index=this.getIndexByDom(dom);
		re.attrs={'position':$(dom).attr("position"),'color':$(dom).attr("color"),'bgcolor':$(dom).attr("bgcolor")};
		re.functionid=functionid;
		re.attrs.items=[];
		var items=$(dom).children("items").children("item");
		for(var i=0;i<items.length;i++)
		{
			var $ss = $(items[i]), functionidObj = $ss.children("functionid");
			re.attrs.items.push({
				style:$ss.attr("style"),
				arrangement:$ss.attr("arrangement"),
				text:$ss.children("text").text(),
				icon:$ss.children("icon").text(),
				dataurl:$ss.children("dataurl").text(),
				para:$ss.children("para").text(),
				functionid:{
					"text":functionidObj.text(),
					"target":functionidObj.attr("target")
				}
			});
		}
		return re;
	},
	getTempUserBasicInfoData:function(functionid,dom){
		var re={};
		re.name='用户帐号';
		re.code='component_userbasicinfo';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.bgcolor=$(dom).attr("bgcolor");
		re.attrs.color=$(dom).attr("color");
		re.attrs.style=$(dom).children("style").text();
		re.attrs.functionid={
			"text":$(dom).children("functionid").text(),
			"target":$(dom).children("functionid").attr("target")
		};
		return re;
	},
	getTempUserProfileData:function(functionid,dom){
		var re={};
		re.name='用户属性';
		re.code='component_userprofile';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.bgcolor=$(dom).attr("bgcolor");
		re.attrs.color=$(dom).attr("color");
		re.attrs.bgpic=$(dom).children("bgpic").text();
		re.attrs.header=$(dom).children("header").text();
		re.attrs.items=[];
		var items=$(dom).children("items").children("item");
		for(var i=0;i<items.length;i++){
			var $ss = $(items[i]), functionidObj = $ss.children("functionid");
			re.attrs.items.push({
				itemname:$ss.children("itemname").text(),
				itemicon:$ss.children("itemicon").text(),
				dataurl:$ss.children("dataurl").text(),
				functionid:{
					"text":functionidObj.text(),
					"target":functionidObj.attr("target")
				}//template:this.getTemplateDataByDom($(items[i]).children("template")
			});
		}
		return re;
	},
	getTempAppListData:function(functionid,dom){
		var re={};
		re.name='应用列表';
		re.code='component_applist';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.style=$(dom).attr("style");
		return re;
	},
	getTempTabsData:function(functionid,dom){
		var re={};
		re.name='二级分类';
		re.code='component_tabs';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.bgcolor=$(dom).attr("bgcolor");
		re.attrs.bgcolor_active=$(dom).attr("bgcolor_active");
		re.attrs.tabitems=[];
		var items=$(dom).children("tabitem");
		for(var i=0;i<items.length;i++){
			var $ss = $(items[i]), functionidObj = $ss.children("functionid");
			re.attrs.tabitems.push({
				itemname:$ss.children("itemname").text(),
				itemicon:$ss.children("itemicon").text(),
				itemicon_active:$ss.children("itemicon_active").text(),
				functionid:{
					"text":functionidObj.text(),
					"target":functionidObj.attr("target")
				}//template:this.getTemplateDataByDom($(items[i]).children("template")
			});
		}
		return re;
	},
	getTempSearchData:function(functionid,dom){
		var re={};
		re.name='搜索';
		re.code='component_search';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		var $eleObj = $(dom),functionidObj = $eleObj.children("functionid");
		re.attrs.url=$eleObj.children("url").text().replace(/&amp;/g,'&');
		re.attrs.text=$eleObj.children("text").text();
		re.attrs.functionid={
			"text":functionidObj.text(),
			"target":functionidObj.attr("target")
		};
		return re;
	},
	getTempNavData:function(functionid,dom){
		var re={};
		re.name='底部导航';
		re.code='component_nav';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.bgcolor=$(dom).attr("bgcolor");
		re.attrs.bgcolor_active=$(dom).attr("bgcolor_active");
		re.attrs.navitems=[];
		var ss=$(dom).children("navitem");
		for(var i=0;i<ss.length;i++){
			var $ss = $(ss[i]), functionidObj = $ss.children("functionid");
			var o={
				itemname:$ss.children("itemname").text(),
				itemicon:$ss.children("itemicon").text(),
				itemicon_active:$ss.children("itemicon_active").text(),
				functionid:{
					"text":functionidObj.text(),
					"target":functionidObj.attr("target")
				}//$(ss[i]).children("functionid").text()//actiontype:$(ss[i]).children("actiontype").text(),
			}
//			var $tempDom=$(ss[i]).children("template");
//			if($tempDom.length>0){
//				o.template=this.getTemplateDataByDom($tempDom);
//			}
			re.attrs.navitems.push(o);
		}
		return re;
	},
	getTempSwitchData:function(functionid,dom){
		var re={};
		re.name='轮换图';
		re.code='component_switch';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.timer=parseInt($(dom).attr("timer"));
		if($(dom).children("listurl").length>0){
			re.attrs.listurl=$(dom).children("listurl").text().replace(/&amp;/g,'&');
		}
		re.attrs.pics=[];
		var ss=$(dom).children("pic");
		for(var i=0;i<ss.length;i++){
			re.attrs.pics.push({
				url:$(ss[i]).children("url").text(),
				text:$(ss[i]).children("text").text()
			});
		}
		return re;
	},
	getTempTitleData:function(functionid,dom){
		var re={};
		re.name='标题';
		re.code='component_title';
		re.index=this.getIndexByDom(dom);
		re.attrs={'text':$(dom).children('text').text()};
		re.functionid=functionid;
		re.attrs.color=$(dom).attr("color");
		re.attrs.pic=$(dom).children("pic").text();
		return re;
	},
	getTempMenuData:function(functionid,dom){
		var re={};
		re.name='快捷菜单';
		re.code='component_menu';
		re.index=this.getIndexByDom(dom);
		re.attrs={'position':$(dom).attr("position")};
		re.functionid=functionid;
		re.attrs.menuitems=[];
		var items=$(dom).children("menuitem");
		for(var i=0;i<items.length;i++)
		{
			var $ss = $(items[i]), functionidObj = $ss.children("functionid");
			re.attrs.menuitems.push({
				itemname:$ss.children("itemname").text(),
				itemicon:$ss.children("itemicon").text(),
				functionid:{
					"text":functionidObj.text(),
					"target":functionidObj.attr("target")
				}
			});
		}
		return re;
	},
	getTempListData:function(functionid,dom){
		var re={};
		re.name='新闻列表';
		re.code='component_list';
		re.index=this.getIndexByDom(dom);
		re.attrs={};
		re.functionid=functionid;
		re.attrs.type=$(dom).attr('type');
		re.attrs.style=$(dom).attr('style');
		if(re.attrs.type=='1'){
			re.attrs.listitems=[];
			var items=$(dom).children("listitem");
			for(var i=0;i<items.length;i++){
				var $ss = $(items[i]), functionidObj = $ss.children("functionid");
				var pp={
					itemname:$ss.children("itemname").text(),
					itemicon:$ss.children("itemicon").text(),
					functionid:{
						"text":functionidObj.text(),
						"target":functionidObj.attr("target")
					}
				}
				re.attrs.listitems.push(pp);
			}
		}
		else if(re.attrs.type=='2'){
			re.attrs.listurl=$(dom).children("listurl").text().replace(/&amp;/g,'&');
			re.attrs.functionid=$(dom).children("functionid").text();
			if($(dom).children("listurlpara").length>0)
					re.attrs.listurlpara=$(dom).children("listurlpara").text();
		}
		else if(re.attrs.type=='3'){
			re.name='应用列表';
			re.code='component_applist'
		}
		return re;
	},
	getNativeDefaultData:function(functionid){
		var re={};
		re.name='';
		re.code='';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;		
	},
	getNativeGroupNewsData:function(functionid){
		var re={};
		re.name='群组动态';
		re.code='component_groupnews';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeCircleNewsData:function(functionid){
		var re={};
		re.name='圈子动态';
		re.code='component_circlenews';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeMicroData:function(functionid){
		var re={};
		re.name='公众号';
		re.code='component_publicaccount';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeRepositoryData:function(functionid){
		var re={};
		re.name='知识库';
		re.code='component_repository';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeContactsData:function(functionid){
		var re={};
		re.name='通讯录';
		re.code='component_contacts';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeMessageData:function(functionid){
		var re={};
		re.name='消息中心';
		re.code='component_message';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeBlogData:function(functionid){
		var re={};
		re.name='企业微博';
		re.code='component_enoweibo';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getNativeMatchDetailData:function(functionid){
		var re={};
		re.name='搭配详细';
		re.code='component_matchdetail';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.url=$dom.children("url").text();
			re.attrs.para_code=$dom.children("para_code").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.comment_url=$dom.children("comment_url").text();
			re.attrs.list={
				listurl:$dom.children("list").children("listurl").text(),
				listurlpara:$dom.children("list").children("listurlpara").text(),
				functionid:{
					text:$dom.children("list").children("functionid").text(),
					target:$dom.children("list").children("functionid").attr("target")
				}
			};
			re.attrs.functionbar={items:[]};
			var items=$dom.children("functionbar").children("item");
			for(var i=0;i<items.length;i++){
				re.attrs.functionbar.items.push({
					text:$(items[i]).children("text").text(),
					icon:$(items[i]).children("icon").text(),
					para:$(items[i]).children("para").text(),
					functionid:{
						text:$(items[i]).children("functionid").text(),
						target:$(items[i]).children("functionid").attr("target")
					}
				});
			}
		}
		return re;
	},
	getNativeGoodsDetailData:function(functionid){
		var re={};
		re.name='商品详细';
		re.code='component_goodsdetail';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.url=$dom.children("url").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.price_url=$dom.children("price_url").text();
			re.attrs.spec_url=$dom.children("spec_url").text();
			re.attrs.color_url=$dom.children("color_url").text();
			re.attrs.stock_url=$dom.children("stock_url").text();
			re.attrs.buy_url=$dom.children("buy_url").text();
			re.attrs.join_url=$dom.children("join_url").text();
			re.attrs.comment=this.getNativeCommentData("",$dom.children("comment").children("native"));
			re.attrs.fav=this.getNativeFavData("",$dom.children("fav").children("native"));
		}
		return re;
	},
	getNativeCommentData:function(functionid){
		var re={};
		re.name='评论';
		re.code='component_comment';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var $dom=arguments[1]?arguments[1]:$(this.getInterByFunctionid(functionid)).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.url=$dom.children("url").text();
			re.attrs.para=$dom.children("para").text();
		}
		return re;
	},
	getNativeFavData:function(functionid){
		var re={};
		re.name='收藏';
		re.code='component_fav';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var $dom=arguments[1]?arguments[1]:$(this.getInterByFunctionid(functionid)).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.url=$dom.children("url").text();
			re.attrs.para=$dom.children("para").text();
		}
		return re;
	},
	getNativeMatchListData:function(functionid){
		var re={};
		re.name='搭配组件';
		re.code='component_matchlist';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.url=$dom.children("url").text();
			re.attrs.para_code=$dom.children("para_code").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.functionid={
				"text":$dom.children("functionid").text(),
				"target":$dom.children("functionid").attr("target")
			};
		}
		return re;
	},
	getNativeSettingData:function(functionid){
		var re={};
		re.name='设置';
		re.code='component_setting';
		re.index="1";
		re.functionid=functionid;
		re.attrs={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.attrs.type=$dom.children("type").text();
			re.attrs.title=$dom.children("title").text();
			re.attrs.actionurl=$dom.children("actionurl").text();
		}
		return re;
	},
	getHtml5Data:function(functionid){
		var re={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("html5");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.startpage=$dom.children("startpage").text();
			re.encrypt = $dom.children("encrypt").text();
			var $plugin = $(InterDom).parent().children("plugin");
			re.plugin={
				"id":$plugin.children("hplugin_id").text(),
	 			"version":$plugin.children("hplugin_ver").text(),
	 			"downurl":$plugin.children("hplugin_downurl").text()
			};
		}
		return re;
	},
	getWebAppData:function(functionid){
		var re={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("webapp");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.url=$dom.children("url").text();
			re.encrypt=$dom.children("encrypt").text();
		}
		return re;
	},
	getMobileAppData:function(functionid){
		var re={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("mobileapp");
		if(typeof($dom)!='undefined' && $dom.length>0){
			re.android_url=$dom.children("android_url").text();
			re.ios_url=$dom.children("ios_url").text();
			re.scheme=$dom.children("scheme").text();
		}
		return re;
	},
	getNativeData:function(functionid){
		var re={};
		var InterDom=this.getInterByFunctionid(functionid);
		var $dom=$(InterDom).children("native");
		var nativetype=$dom.children("type").text();
		if(nativetype=="groupnews"){
			re=this.getNativeGroupNewsData(functionid);
		}
		else if(nativetype=="CIRCLE")
		{
			re=this.getNativeCircleNewsData(functionid);
		}
		else if(nativetype=="publicaccount"){
			re=this.getNativeMicroData(functionid);
		}
		else if(nativetype=="repository"){
			re=this.getNativeRepositoryData(functionid);
		}
		else if(nativetype=="COMMUNICATE"){
			re=this.getNativeContactsData(functionid);
		}
		else if(nativetype=="MSGCENTER"){
			re=this.getNativeMessageData(functionid);
		}
		else if(nativetype=="enoweibo"){
			re=this.getNativeBlogData(functionid);
		}
		else if(nativetype=="SETTING"){
			re=this.getNativeSettingData(functionid);
		}
		else if(nativetype=="O2C_MB_MATCHLIST"){
			re=this.getNativeMatchListData(functionid);
		}
		else if(nativetype=="O2C_MB_MATCHDETAIL"){
			re=this.getNativeMatchDetailData(functionid);
		}
		else if(nativetype=="O2C_MB_GOODSDETAIL"){
			re=this.getNativeGoodsDetailData(functionid);
		}
		else if(nativetype=="COMMENT"){
			re=this.getNativeCommentData(functionid);
		}
		else if(nativetype=="FAV"){
			re=this.getNativeFavData(functionid);
		}
		else
			re = this.getNativeDefaultData(functionid);
		return re;
	},
	getRootInterData:function(){
		var functionid=$(this.xmlDom).children("mapp").children("basicinfo").children("rootfunctionid").text();
		return this.getInterData(functionid);
	}
};

var AppObj=function(){
	var _devices=["IOS","android"];
	this.device=null;
	this.appid=null;
	this.fac=null;//工厂
	this.p=null;//容器
	this.xmlBuilder=null;
	this.xmlParser=null;
	this.navorder=null;
	this.ComponentSelected=function(){};
	this.InterfaceSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.ErrorOccured=function(){};
	this.inters=[];
	this.loading=[];
	this.cFuncid=null;
	this.getLogs=function(){
		return this.fac.getLogs();
	}
}
AppObj.prototype={
	init:function(d){
		this.inters=[];
		this.cFuncid=null;
		if(d.xmlString){
			var xmlDom=typeof(d.xmlString)=="string" ? $.parseXML(d.xmlString) : d.xmlString;
			//初始化构造器
			this.xmlBuilder=new XmlBuilder();
			this.xmlBuilder.initWithXml(xmlDom);
			//初始化解析器
			this.xmlParser=new XmlParser();
			this.xmlParser.initWithXml(xmlDom);
		}
		//判断是否包含basicinfo，有随机BUG会造成basicinfo节丢失,这时需要重新生成basicinfo节
		var $xmldom = $(this.xmlBuilder.xmlDom);
		var $functions = $xmldom.find("basicinfo");
		if($functions==null || $functions.length==0)
		{
			this.xmlBuilder.bRoot(d);
		}
		if(d.appid)
			this.appid=d.appid;
		if(d.device)
			this.device=d.device;
		if(d.p)
			this.p=d.p;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.InterfaceSelected)
			this.InterfaceSelected=d.InterfaceSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.ErrorOccured)
			this.ErrorOccured=d.ErrorOccured;
		//初始化组件工厂
		this.fac=new ComponentFactory(this.device,this.xmlBuilder,this.xmlParser,this.ErrorOccured);
		this.load();
	},
	initWithAppInfo:function(d){
		this.inters=[];
		this.cFuncid=null;
		if(d.appinfo){
			this.xmlBuilder=new XmlBuilder();
			this.xmlBuilder.bRoot(d.appinfo);
			this.xmlParser=new XmlParser();
			this.xmlParser.initWithXml(this.xmlBuilder.xmlDom);
		}
		if(d.appid)
			this.appid=d.appid;
		if(d.device)
			this.device=d.device;
		if(d.p)
			this.p=d.p;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.InterfaceSelected)
			this.InterfaceSelected=d.InterfaceSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.ErrorOccured)
			this.ErrorOccured=d.ErrorOccured;
		//初始化组件工厂
		this.fac=new ComponentFactory(this.device,this.xmlBuilder,this.xmlParser,this.ErrorOccured);
		this.load();
	},
	validator:function(){
		
	},
	load:function()
	{
		//加载应用的第一个界面
		var functionid=this.xmlParser.getRootFunctionid();
		if(typeof(functionid)=='undefined' || functionid=='')return;
		this.loadInterObj(functionid);
	},
	getInterObj:function(functionid){
		for(var i=0;i<this.inters.length;i++){
			if(this.inters[i].functionid==functionid)
				return this.inters[i].inter;
		}
		return null;
	},
	setInterObj:function(functionid,inter){
		for(var i=0;i<this.inters.length;i++){
			if(this.inters[i].functionid==functionid){
				this.inters[i].inter=inter;
				return;
			}
		}
		this.inters.push({
			functionid:functionid,
			inter:inter
		});
	},
	getLoading:function(functionid){
		for(var i=0;i<this.loading.length;i++){
			if(this.loading[i]==functionid)
				return functionid;
		}
		return null;
	},
	removeLoading:function(functionid){
		for(var i=0;i<this.loading.length;i++){
			if(this.loading[i]==functionid){
				this.loading.splice(i,1);
				break;
			}
		}
	},
	loadInterObj:function(functionid){
		//是否正在加载中
		if(this.getLoading(functionid)!=null)return;
		if(this.loading.length==0){
			this.cFuncid=functionid;
		}
		this.loading.push(functionid);
		var para=arguments[1]?arguments[1]:{};
		var c=this.getInterObj(functionid);
		if(c!=null){
			c.reload(para);
			this.removeLoading(functionid);
			return;
		}
		
		var inter=new InterfaceObj();
		this.setInterObj(functionid,inter);
		this.getInterObj(functionid).init({
			functionid:functionid,
			device:this.device,
			p:para.p?para.p:this.p,
			xmlBuilder:this.xmlBuilder,
			xmlParser:this.xmlParser,
			app:this,
			sender:para.sender?para.sender:null,
			isChild:para.isChild?para.isChild:false,
			params:para.params?para.params:null,
			ComponentSelected:this.ComponentSelected,
			ErrorOccured:this.ErrorOccured,
			BeforeAddComponent:this.BeforeAddComponent,
			InterfaceSelected:this.InterfaceSelected
		});
		this.removeLoading(functionid);
	},
	updateAppInfo:function(key,val){
		this.xmlBuilder.updateAppInfo(key,val);
	},
	updateInterInfo:function(functionid,key,val){
		this.xmlBuilder.updateInterInfo(functionid,key,val);
	},
	setAppInfo:function(appinfo){
		this.xmlBuilder.setAppInfo(appinfo);
	},
	setRootInterface:function(functionid){
		this.xmlBuilder.setRootInterface(functionid);
	},
	moveInterComponent:function(functionid,index,direct,oindex){
		this.xmlBuilder.moveInterComponent(functionid,index,direct,oindex);
		this.loadInterObj(functionid);
	},
	addInterface:function(info){
		this.xmlBuilder.addInterface(info);
		this.loadInterObj(info.functionid);
	},
	addInterfaceWidthTempXml:function(functionid,functionname,tempXml){
		this.xmlBuilder.addInterfaceWidthTempXml(functionid,functionname,tempXml,this.navorder);
//		if(this.getInterObj(functionid)==null)
//			this.loadInterObj(functionid);
//		else
//			this.getInterObj(functionid).reload();
//		this.cFuncid=functionid;
	},
	addInterfaceWithHTML5Xml:function(functionid,functionname,html5Xml){
		this.xmlBuilder.addInterfaceWithHTML5Xml(functionid,functionname,html5Xml);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	addInterfaceWithWebXml:function(functionid,functionname,webXml){
		this.xmlBuilder.addInterfaceWithWebXml(functionid,functionname,webXml);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	addInterfaceWithMobileXml:function(functionid,functionname,mobileXml){
		this.xmlBuilder.addInterfaceWithMobileXml(functionid,functionname,mobileXml);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	addInterfaceWithNativeXml:function(functionid,functionname,nativeXml){
		this.xmlBuilder.addInterfaceWithNativeXml(functionid,functionname,nativeXml);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	addInterComponent:function(functionid,index,direct,code){
		var newindex=this.xmlBuilder.addInterComponent(functionid,index,direct,code);
		this.xmlBuilder.clear();
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
		return newindex;
	},
	setInterHTML5:function(functionid,info){
		this.xmlBuilder.setInterHTML5(functionid,info);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterWeb:function(functionid,info){
		this.xmlBuilder.setInterWeb(functionid,info);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterMobile:function(functionid,info){
		this.xmlBuilder.setInterMobile(functionid,info);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterNative:function(functionid,info){
		this.xmlBuilder.setInterNative(functionid,info);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterTempWithXml:function(functionid,xmlString){
		this.xmlBuilder.setInterTempWithXml(functionid,xmlString,this.navorder);
		if(this.getInterObj(functionid)==null)
			this.loadInterObj(functionid);
		else
			this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterTemplate:function(functionid,info){
		this.xmlBuilder.setInterTemplate(functionid,info);
		this.getInterObj(functionid).reload();
		this.cFuncid=functionid;
	},
	setInterComponent:function(functionid,index,info){
		this.xmlBuilder.setInterComponent(functionid,index,info);
		this.xmlBuilder.clear();
		this.getInterObj(functionid).reloadComponentAtIndex(index);
	},
	setTemplateTitle:function(functionid,index,info)
	{
		this.xmlBuilder.setTemplateTitle(functionid,index,info);
		this.getInterObj(functionid).reloadComponentAtIndex(index);
	},
	setTemplateMenu:function(functionid,index,info)
	{
		this.xmlBuilder.setTemplateMenu(functionid,index,info);
		this.xmlBuilder.clear();
		this.getInterObj(functionid).reloadComponentAtIndex(index);
	},
	setTemplateList:function(functionid,index,info)
	{
		this.xmlBuilder.setTemplateList(functionid,index,info);
		this.xmlBuilder.clear();
		this.getInterObj(functionid).reloadComponentAtIndex(index);
	},
	removeInterByFunctionid:function(functionid){
		this.xmlBuilder.removeInterByFunctionid(functionid);
		this.xmlBuilder.clear();
		if(this.getInterData(this.cFuncid)==null)
			this.load();
	},
	removeInterComponent:function(functionid,index){
		this.xmlBuilder.removeInterComponent(functionid,index);
		this.xmlBuilder.clear();
		this.getInterObj(functionid).removeComponentAtIndex(index);
	},
	getXmlDom:function(){
		return this.xmlBuilder.getXmlDom();
	},
	getRootFunctionid:function(){
		return this.xmlParser.getRootFunctionid();
	},
	getAppData:function(){
		return this.xmlParser.getAppData();
	},
	getAppOrgData:function(){
		var re=arguments[0];
		var data=arguments[1];
		var pId=arguments[2];
		var interId=arguments[3];
		return this.xmlParser.getAppOrgData(re,data,pId,interId);
	},
	getInterData:function(functionid){
		return this.xmlParser.getInterData(functionid);
	},
	getTemplateData:function(functionid){
		return this.xmlParser.getTemplateData(functionid);
	},
	getHtml5Data:function(functionid){
		return this.xmlParser.getHtml5Data(functionid);
	},
	getWebAppData:function(functionid){
		return this.xmlParser.getWebAppData(functionid);
	},
	getMobileAppData:function(functionid){
		return this.xmlParser.getMobileAppData(functionid);
	},
	getNativeData:function(functionid){
		return this.xmlParser.getNativeData(functionid);
	},
	getRootInterData:function(){
		return this.xmlParser.getRootInterData();
	},
	getXmlString:function(){
		return this.xmlBuilder.getXmlString();
	},
	getInterComponent:function(functionid,index){
		return this.xmlParser.getInterComponent(functionid,index);
	},
	getSourceComponent:function(functionid){
		return this.xmlParser.getSourceComponent(functionid);
	}
}
var InterfaceObj=function(){
	var _devices=["IOS","android"];
	this.device=null;
	this.functionid=null;
	this.sender=null;
	this.fac=null;
	this.p=null;
	this.app=null;
	this.params=null;
	this.xmlBuilder=null;
	this.xmlParser=null;
	this.components=[];
	this.sComponent=null;
	this.ComponentSelected=function(){};
	this.InterfaceSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.isChild=false;
	this.childs=[];
	this.ErrorOccured=function(){};
	this.getLogs=function(){
		return this.fac.getLogs();
	}
}

InterfaceObj.prototype={
	init:function(d){
		this.setInit(d);
		if(this.functionid=="")return;
		this.load();
		this.InterfaceSelected(this.sender,this.functionid);
	},
	setInit:function(d){
		if(d.functionid){
			if(typeof(d.functionid)=="string")
				this.functionid=d.functionid;
			else{
				this.functionid=d.functionid.text;
			}
		}
		if(d.device)
			this.device=d.device;
		if(d.p)
			this.p=d.p;
		if(d.xmlBuilder)
			this.xmlBuilder=d.xmlBuilder;
		if(d.xmlParser)
			this.xmlParser=d.xmlParser;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.InterfaceSelected)
			this.InterfaceSelected=d.InterfaceSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.ErrorOccured)
			this.ErrorOccured=d.ErrorOccured;
		if(d.app)
			this.app=d.app;
		if(d.isChild)
			this.isChild=d.isChild;
		if(d.sender)
			this.sender=d.sender;
		else
			this.sender=null;
		if(d.params)
			this.params=d.params;
		else
			this.params=null;
		if(this.fac==null){
			this.fac=new ComponentFactory(this.device,this.xmlBuilder,this.xmlParser,this.ErrorOccured);
		}
	},
	createHTML:function(){
		var html=[];
		//查找是否已创建
		var $htmlDom=$(this.p).find(".interface[functionid='"+this.functionid+"']");
		if($htmlDom.length==0){
			html.push("<div class='interface' functionid='"+this.functionid+"'><div class='interface_head'></div><div class='interface_center'></div><div class='interface_foot'><div class='interface_foot_bg'></div></div></div>");
			$div=$(html.join(''));
			$(this.p).append($div);
		}
		else{
			$htmlDom.find(".interface_head,.interface_center,.interface_foot").html(null);
		}
	},
	bindEvents:function(){
		var obj=this;
		$(".interface[functionid='"+this.functionid+"']").find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	},
	checkSource:function(){
		var theComponent=this.app.getSourceComponent(this.functionid);
		if(theComponent!=null && ( theComponent.code=="component_nav" || theComponent.code=="component_tabs"))
			this.isChild=true;
		else
			this.isChild=false;
		return theComponent;
	},
	load:function(){
		var theComponent =this.checkSource();
		this.sComponent=theComponent;
		if(this.isChild){
			if(true || this.app.getInterObj(theComponent.functionid)==null)
				this.app.loadInterObj(theComponent.functionid);
			
			var components=this.app.getInterObj(theComponent.functionid).components;
			var tt=null;
			for(var i=0;i<components.length;i++){
				if(components[i].index.toString()==this.sComponent.index.toString()){
					tt=components[i];
					break;
				}
			}
			if(tt!=null){
				if(this.sComponent.code=="component_tabs"){
					tt.loadTab(this.sComponent.tabsindex);
				}
				else if(this.sComponent.code=="component_nav"){
					tt.loadNav(this.sComponent.order);
				}
			}
		}
		
		if(this.sender!=null){
			this.app.cFuncid=this.functionid;
		}
		if(this.params!=null){
			if(this.params.sender){
				this.app.cFuncid=this.functionid;
			}
		}
		
		//获取界面的所有组件
		var interData=this.xmlParser.getInterData(this.functionid);
		if(interData==null){
			//错误
			this.fac.writeLog({
				code:INTERERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				msg:('找不到页面标识为'+this.functionid+'的页面信息')
			});
			return;
		}
		
		//if($(this.p).find(".interface[functionid='"+this.app.cFuncid+"']").find(".component[functionid='"+this.functionid+"']").length>0 && this.app.cFuncid!=this.functionid)return;
		if(this.isChild){
			var $inter=null;
			var pComponent=theComponent;
			while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
					$inter=$(".interface[functionid='"+pComponent.functionid+"']");
			 	  pComponent=this.app.getSourceComponent(pComponent.functionid);
			 }
			if(this.sComponent.code=="component_tabs"){
				$inter.find(".component[functionid='"+this.sComponent.functionid+"'][aindex='"+this.sComponent.index.toString()+"']").find(".tabs_div[tabsindex='"+this.sComponent.tabsindex.toString()+"']").html(null);
			}
			else if(this.sComponent.code=="component_nav"){
				$inter.find(".interface_head,.interface_center").html(null);
			}
		}
		else{
			this.createHTML();
		}
		
		if(this.isChild){
			var $inter=null;
			var pComponent=theComponent;
			while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
					$inter=$(".interface[functionid='"+pComponent.functionid+"']");
			 	  pComponent=this.app.getSourceComponent(pComponent.functionid);
			 }
			if(this.sComponent.code=="component_tabs"){
				this.p=$inter.find(".component[functionid='"+this.sComponent.functionid+"'][aindex='"+this.sComponent.index.toString()+"']").find(".tabs_div[tabsindex='"+this.sComponent.tabsindex.toString()+"']").get(0);
			}
			else if(this.sComponent.code=="component_nav"){
				this.p=$inter.get(0);
			}
		}
		
		var obj=this;
		
		if(this.isChild){
			if(interData.functiontype=='1'){//模板
				for(var i=0;i<interData.template.length;i++){
					var rootP=null;
					if($(this.p).attr("class").indexOf("interface")>-1){
						var code=interData.template[i].code;
						var $inter=$(this.p);
						if(code=="component_title" || code=="component_menu"){
							rootP=$inter.find(".interface_head");
						}
						else if(code=="component_nav"){
							rootP=$inter.find(".interface_foot");
							$inter.find(".interface_foot")[0].style.height="60px";
							$inter.find(".interface_center")[0].style.height="370px";
						}
						else{
							rootP=$inter.find(".interface_center");
						}
					}
					else{
						rootP=this.p;
					}
					this.components.push(
						this.fac.createObj(this.functionid,rootP,interData.template[i].code,interData.template[i].attrs,this.ComponentSelected,this.BeforeAddComponent,i+1,this.app)
					);
				}
			}
			else if(interData.functiontype=='2'){
				$(this.p).html("<div class='inter_html5'><div class='html5_title'>html5插件</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='4'){
				$(this.p).html("<div class='inter_html5'><div class='html5_title'>web应用</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='5'){
				$(this.p).html("<div class='inter_html5'><div class='html5_title'>第三方移动应用</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='3'){
				//获取原生功能类型
				var nativetype=interData.native.attrs.type;
				var $inter=$(this.p);
				var rootP=null;
					if($(this.p).attr("class").indexOf("interface")>-1){
						rootP=$inter.find(".interface_center");
					}
					else{
						rootP=this.p;
					}
				if(nativetype=="groupnews"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_groupnews",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="CIRCLE")
				{
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_circlenews",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="publicaccount"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_publicaccount",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="repository"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_repository",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="COMMUNICATE"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_contacts",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="MSGCENTER"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_message",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="enoweibo"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_enoweibo",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="SETTING"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_setting",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="O2C_MB_MATCHLIST"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_matchlist",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="O2C_MB_MATCHDETAIL"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_matchdetail",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
			  else if(nativetype=="O2C_MB_GOODSDETAIL"){
					this.components.push(
						this.fac.createObj(this.functionid,rootP,"component_goodsdetail",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else{
					//错误
					this.fac.writeLog({
						code:INTERERRORCODE,
						appid:this.app.appid,
						functionid:this.functionid,
						msg:('页面类型只能是模板、html5、web应用、第三方移动应用、或原生手机功能')
					});
					return;
				}
			}
		}
		else{
			if(interData.functiontype=='1'){//模板
			for(var i=0;i<interData.template.length;i++){
					var code=interData.template[i].code;
					var rootp=null;
					var $inter=$(this.p).find(".interface[functionid='"+this.functionid+"']");
					if(code=="component_title" || code=="component_menu"){
						rootp=$inter.find(".interface_head");
					}
					else if(code=="component_nav"){
						rootp=$inter.find(".interface_foot");
						$inter.find(".interface_foot")[0].style.height="60px";
						$inter.find(".interface_center")[0].style.height="370px";
					}
					else{
						rootp=$inter.find(".interface_center");
					}
					this.components.push(
						this.fac.createObj(this.functionid,rootp,interData.template[i].code,interData.template[i].attrs,this.ComponentSelected,this.BeforeAddComponent,i+1,this.app)
					);
				}
			}
			else if(interData.functiontype=='2'){//html5
				$(this.p).find(".interface[functionid='"+this.functionid+"']").html("<div class='inter_html5'><div class='html5_title'>html5插件</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='4'){
				$(this.p).find(".interface[functionid='"+this.functionid+"']").html("<div class='inter_html5'><div class='html5_title'>web应用</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='5'){
				$(this.p).find(".interface[functionid='"+this.functionid+"']").html("<div class='inter_html5'><div class='html5_title'>第三方移动应用</div><div class='html5_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div></div>");
			}
			else if(interData.functiontype=='3'){//native
				//获取原生功能类型
				var nativetype=interData.native.attrs.type;
				var $inter=$(this.p).find(".interface[functionid='"+this.functionid+"']");
				var rootp=$inter.find(".interface_center");
				if(nativetype=="groupnews"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_groupnews",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="CIRCLE")
				{
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_circlenews",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="publicaccount"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_publicaccount",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="repository"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_repository",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="COMMUNICATE"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_contacts",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="MSGCENTER"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_message",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="enoweibo"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_enoweibo",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="SETTING"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_setting",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="O2C_MB_MATCHLIST"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_matchlist",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="O2C_MB_MATCHDETAIL"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_matchdetail",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
				else if(nativetype=="O2C_MB_GOODSDETAIL"){
					this.components.push(
						this.fac.createObj(this.functionid,rootp,"component_goodsdetail",interData.native.attrs,this.ComponentSelected,this.BeforeAddComponent,1,this.app)
					);
				}
			}
			else{
				//错误
				this.fac.writeLog({
					code:INTERERRORCODE,
					appid:this.app.appid,
					functionid:this.functionid,
					msg:('页面类型只能是模板、html5、web应用、第三方移动应用、或原生手机功能')
				});
				return;
			}
		}
		this.bindEvents();
		if(this.isChild){
			var pComponent=theComponent;
			while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
					$(".interface").hide();
			 		$(".interface[functionid='"+pComponent.functionid+"']").show();
			 	  pComponent=this.app.getSourceComponent(pComponent.functionid);
			 }
		}
		else{
			$(this.p).find(".interface").hide();
			$(this.p).find(".interface[functionid='"+this.functionid+"']").show();
		}
	},
	reload:function(){
		var para=arguments[0]?arguments[0]:{};
		this.setInit(para);
		this.components=[];
		this.load();
		this.InterfaceSelected(this.sender,this.functionid);
	},
	show:function(){
		this.InterfaceSelected(this.sender,this.functionid);
		if(this.functionid=="")return;
		$(".interface").hide();
		$(".interface[functionid='"+this.functionid+"']").show();
	},
	getRootP:function(code){
				var rootp=null;
				var $inter=null;
				if(this.isChild){
					var $inter=null;
					var pComponent=this.sComponent;
					while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
							$inter=$(".interface[functionid='"+pComponent.functionid+"']");
					 	  pComponent=this.app.getSourceComponent(pComponent.functionid);
					 }
					if(this.sComponent.code=="component_tabs"){
						rootp=$inter.find(".component[functionid='"+this.sComponent.functionid+"'][aindex='"+this.sComponent.index.toString()+"']").find(".tabs_div[tabsindex='"+this.sComponent.tabsindex.toString()+"']");
					}
					else if(this.sComponent.code=="component_nav"){
						if(code=="component_title" || code=="component_menu"){
							rootp=$inter.find(".interface_head");
						}
						else if(code=="component_nav"){
							rootp=$inter.find(".interface_foot");
							$inter.find(".interface_foot")[0].style.height="60px";
							$inter.find(".interface_center")[0].style.height="370px";
						}
						else{
							rootp=$inter.find(".interface_center");
						}
					}
				}
				else{
					$inter=$(this.p).find(".interface[functionid='"+this.functionid+"']");
					if(code=="component_title" || code=="component_menu"){
						rootp=$inter.find(".interface_head");
					}
					else if(code=="component_nav"){
						rootp=$inter.find(".interface_foot");
						$inter.find(".interface_foot")[0].style.height="60px";
						$inter.find(".interface_center")[0].style.height="370px";
					}
					else{
						rootp=$inter.find(".interface_center");
					}
				}
				return rootp;
	},
	removeComponentAtIndex:function(index){
		var paths=index.toString().split('-');
		var comObj=this.components[parseInt(paths[0])-1];
		comObj.p=this.getRootP(comObj.code);
		comObj.remove(index);
	},
	reloadComponentAtIndex:function(index){
		var paths=index.toString().split('-');
		var cominfo=this.xmlParser.getInterComponent(this.functionid,index);
		if(cominfo==null){
			//错误
			this.fac.writeLog({
				code:INTERERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				msg:'未找到组件'
			});
			return;
		}
		if(cominfo.attrs){
			var comObj=this.components[parseInt(paths[0])-1];
			comObj.p=this.getRootP(comObj.code);
			comObj.reload(index,cominfo.attrs);
		}
		else{
			//错误
			this.fac.writeLog({
				code:INTERERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				msg:'组件数据有误'
			});
			return;
		}
	}
};

var SYSERRORCODE="9999";
var APPERRORCODE="0001";
var INTERERRORCODE="0002";
var COMPONENTERRORCODE="0003";
var ComponentFactory=function(device,xmlBuilder,xmlParser,ErrorOccured){
	var _devices=["IOS","android"];
	this.device=device;
	this.xmlBuilder=xmlBuilder;
	this.xmlParser=xmlParser;
	this.ErrorOccured=ErrorOccured;
	var _logs=[];
	this.writeLog=function(params){
		_logs.push(params);
		this.ErrorOccured(_logs[_logs.length-1]);
	};
	this.getLogs=function(){
		return _logs;
	};
}

/** 
 * 下面是一些基础函数，解决mouseover与mouserout事件不停切换的问题（问题不是由冒泡产生的） 
 */
function stopBubble(e){  
        // 如果传入了事件对象，那么就是非ie浏览器  
        if(e&&e.stopPropagation){  
            //因此它支持W3C的stopPropagation()方法  
            e.stopPropagation();  
        }else{  
            //否则我们使用ie的方法来取消事件冒泡  
            window.event.cancelBubble = true;  
        }  
    }  
var CHashTable=function(){
	this.length=0;
	this.array=new Array();
	this.get=function(key){
		for(var i=0;i<this.array.length;i++){
			if(this.array[i].key==key){
				return this.array[i].val;
			}
		}
		return null;
	};
	this.push=function(key,val){
		var obj={'key':key,'val':val};
		this.array.push(obj);
		this.length++;
	};
	this.clear=function(){
		this.array=[];
		this.length=0;
	};
	this.set=function(key,val){
		if(this.get(key)==null){
			this.push(key,val);
		}
		else{
			for(var i=0;i<this.array.length;i++){
				if(this.array[i].key==key){
					this.array[i].val=val;
				}
			}
		}
	};
	this.remove=function(key){
		var j=-1;
		for(var i=0;i<this.array.length;i++){
			
			if(this.array[i].key==key){
				j=i;
				break;
			}
		}
		if(j> -1){
			this.array.splice(j,1);
			this.length--;
		}
	}
};
var ComponentExpectAttrs=new CHashTable();
ComponentExpectAttrs.push('list',['type']);//list
ComponentExpectAttrs.push('title',[]);//title
ComponentExpectAttrs.push('menu',[]);//menu
ComponentExpectAttrs.push('switch',[]);//switch
ComponentExpectAttrs.push('search',[]);//search
ComponentExpectAttrs.push('nav',[]);//nav
ComponentExpectAttrs.push('tabs',[]);//tabs
ComponentExpectAttrs.push('applist',[]);//tabs
ComponentExpectAttrs.push('summary',[]);//用户属性
ComponentExpectAttrs.push('userbasicinfo',[]);//用户帐号
ComponentExpectAttrs.push('functionbar',[]);//功能按钮


ComponentExpectAttrs.push('component_list','<list type="1" style="GRID3"><listitem><itemname>联系人</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid></functionid></listitem><listitem><itemname>客户</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid></functionid></listitem><listitem><itemname>销售机会</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid></functionid></listitem></list>');
ComponentExpectAttrs.push('component_title','<title color="#FFFFFF"><text>移动应用门户</text></title>');
ComponentExpectAttrs.push('component_menu','<menu position="R"><menuitem><itemname>扫描</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid></functionid></menuitem><menuitem><itemname>添加联系人</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid></functionid></menuitem></menu>');
ComponentExpectAttrs.push('component_switch','<switch timer="10"><pic><text>四川人大工作会议</text><url>http://api.wefafa.com/weapp/zjxx/index/news2.jpg</url></pic><pic><text>辽宁检查院工作会议</text><url>http://api.wefafa.com/weapp/zjxx/index/news1.jpg</url></pic><pic><text>北京市长检查日常勤务</text><url>http://api.wefafa.com/weapp/zjxx/index/news3.jpg</url></pic></switch>');
ComponentExpectAttrs.push('component_search','<search><text>请输入查询关键字</text><functionid/></search>');

ComponentExpectAttrs.push('component_nav',"<nav bgcolor='#cccccc' bgcolor_active='#00aad9'><navitem><itemname>首页</itemname><itemicon>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/index.png</itemicon><itemicon_active>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/index.png</itemicon_active><functionid></functionid></navitem>"+
                               "<navitem><itemname>通讯录</itemname><itemicon>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/org.png</itemicon><itemicon_active>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/org.png</itemicon_active><functionid></functionid></navitem>"+
                               "<navitem><itemname>消息中心</itemname><itemicon>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/msgcenter.png</itemicon><itemicon_active>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/msgcenter.png</itemicon_active><functionid></functionid></navitem>"+
                               "<navitem><itemname>企业微博</itemname><itemicon>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/sns.png</itemicon><itemicon_active>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/sns.png</itemicon_active><functionid></functionid></navitem>"+
                               "<navitem><itemname>设置</itemname><itemicon>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/set.png</itemicon><itemicon_active>http://we.fafatime.com/bundles/fafatimewebase/images/icons2/set.png</itemicon_active><functionid></functionid></navitem></nav>");

ComponentExpectAttrs.push('component_tabs','<tabs bgcolor="#cccccc" bgcolor_active="#999999"><tabitem><itemname>选项卡一</itemname><functionid></functionid></tabitem><tabitem><itemname>选项卡二</itemname><functionid></functionid></tabitem></tabs>');
ComponentExpectAttrs.push('component_groupnews','<native><type>groupnews</type><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_circlenews','<native><type>CIRCLE</type><title></title><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_publicaccount','<native><type>publicaccount</type><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_repository','<native><type>repository</type><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_contacts','<native><type>COMMUNICATE</type><title></title><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_message','<native><type>MSGCENTER</type><title></title><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_enoweibo','<native><type>CIRCLE</type><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_setting','<native><type>SETTING</type><title></title><actionurl>http://xx.xx.com/xx</actionurl></native>');
ComponentExpectAttrs.push('component_applist','<list type="3" style="GRID3"></list>');
ComponentExpectAttrs.push('component_userprofile','<summary bgcolor="#CCC" color="#FFF"><items><item><itemname>关注</itemname><itemicon></itemicon><dataurl></dataurl><functionid></functionid></item><item><itemname>粉丝</itemname><itemicon></itemicon><dataurl></dataurl><functionid></functionid></item><item><itemname>订单</itemname><itemicon></itemicon><dataurl></dataurl><functionid></functionid></item><item><itemname>优惠券</itemname><itemicon></itemicon><dataurl></dataurl><functionid></functionid></item><item><itemname>购物车</itemname><itemicon></itemicon><dataurl></dataurl><functionid></functionid></item></items><bgpic></bgpic><header></header></summary>');
ComponentExpectAttrs.push('component_userbasicinfo','<userbasicinfo bgcolor="#CCC" color="#FFF"><style>default</style><functionid></functionid></userbasicinfo>');
ComponentExpectAttrs.push('component_matchlist','<native><type>O2C_MB_MATCHLIST</type><url>http://xx.xx.com/xx</url><title></title><para_code></para_code><functionid></functionid></native>');
ComponentExpectAttrs.push('component_matchdetail','<native><type>O2C_MB_MATCHDETAIL</type><title></title><url></url><comment_url></comment_url><functionbar></functionbar><para_code></para_code><list><listurl>http://xx.xx.com/xx</listurl><listurlpara>productid</listurlpara><functionid></functionid></list></native>');
ComponentExpectAttrs.push('component_goodsdetail','<native><type>O2C_MB_GOODSDETAIL</type><url></url><price_url></price_url><spec_url></spec_url><color_url></color_url><stock_url></stock_url><buy_url></buy_url><join_url></join_url><comment><native><type>COMMENT</type><url></url><para></para></native></comment><fav><native><type>FAV</type><url></url><para></para></native></fav></native>');
ComponentExpectAttrs.push('component_functionbar','<functionbar  bgcolor="#cccccc" color="#ffffff" position="bottom"><items><item style="none" arrangement="lr"><text>首页</text><icon>/bundles/fafatimewebase/images/icons2/org.png</icon><dataurl/><para/><functionid target="self">portal-1-0</functionid></item><item style="none" arrangement="lr"><text>设置</text><icon>/bundles/fafatimewebase/images/icons2/index.png</icon><dataurl/><para/><functionid target="self">portal-1-1</functionid></item></items></functionbar>');
function checkHover(e, target) {   
	e = getEvent(e);
	var fromEle=e.relatedTarget  || e.fromElement;
    if (e.type == "mouseover") {
        return !contains(target, fromEle)  && !(fromEle === target);
    } else {  
    	var toEle = e.relatedTarget  || e.toElement;
        return !contains(target, toEle)  && !(toEle === target);  
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
//判断并创建组件操作工具栏
function initComponentToolbar()
{
	if($("#runtime_component_toolbar").length==0)
	{
		$(document.body).append('<div id="runtime_component_toolbar" style="width:18px;"><span style="width:18px;" title="删除该组件" class="runtime_component_toolbar_del  icon-remove"></span><span style="width:18px;" title="编辑该组件" class="runtime_component_toolbar_edit icon-edit" ></span></div>');
		$("#runtime_component_toolbar .runtime_component_toolbar_del").bind('click',function(){
			//oneApp.removeInterComponent(oneApp.cFuncid,$(this).parent().attr('cindex'));
		});
		$("#runtime_component_toolbar .runtime_component_toolbar_edit").bind('click',function(){
		});
	}
}

ComponentFactory.prototype={
	createObj:function(functionid,p,code,attributes,componentselected,BeforeAddComponent,index,app){
		if(code=="component_list"){//列表控件
			var com=(this.device=="IOS"?new ContentComponentIOS():new ContentComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_nav"){//导航控件
			var com=(this.device=="IOS"?new NavComponentIOS():new NavComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_menu"){//菜单控件
			var com=(this.device=="IOS"?new MenuComponentIOS():new MenuComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_switch"){//轮换控件
			var com=(this.device=="IOS"?new SwitchComponentIOS():new SwitchComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_title"){//标题控件
			var com=(this.device=="IOS"?new TitleComponentIOS():new TitleComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_search"){//搜索控件
			var com=(this.device=="IOS"?new SearchComponentIOS():new SearchComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_tabs"){//搜索控件
			var com=(this.device=="IOS"?new TabsComponentIOS():new TabsComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_groupnews"){//群组动态
			var com=(this.device=="IOS"?new GroupNewsComponentIOS():new GroupNewsComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_circlenews"){//群组动态
			var com=(this.device=="IOS"?new CircleNewsComponentIOS():new CircleNewsComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_publicaccount"){//群组动态
			var com=(this.device=="IOS"?new MicroComponentIOS():new MicroComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_applist"){//群组动态
			var com=(this.device=="IOS"?new AppListComponentIOS():new AppListComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_repository"){//知识库
			var com=(this.device=="IOS"?new RepositoryComponentIOS():new RepositoryComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_contacts"){//通讯录
			var com=(this.device=="IOS"?new ContactsComponentIOS():new ContactsComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_message"){//消息中心
			var com=(this.device=="IOS"?new MessageComponentIOS():new MessageComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_enoweibo"){//通讯录
			var com=(this.device=="IOS"?new BlogComponentIOS():new BlogComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_setting"){//通讯录
			var com=(this.device=="IOS"?new SettingComponentIOS():new SettingComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_userprofile"){//通讯录
			var com=(this.device=="IOS"?new UserProfileComponentIOS():new UserProfileComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_userbasicinfo"){
			var com=(this.device=="IOS"?new UserBasicInfoComponentIOS():new UserBasicInfoComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_matchlist"){
			var com=(this.device=="IOS"?new MatchListComponentIOS():new MatchListComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_matchdetail"){
			var com=(this.device=="IOS"?new MatchDetailComponentIOS():new MatchDetailComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_goodsdetail"){
			var com=(this.device=="IOS"?new GoodsDetailComponentIOS():new GoodsDetailComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else if(code=="component_functionbar"){
			var com=(this.device=="IOS"?new FunctionBarComponentIOS():new FunctionBarComponentAndroid());
			com.init({
				"attributes":attributes,
				"p":p,
				"fac":this,
				"functionid":functionid,
				"ComponentSelected":componentselected,
				"BeforeAddComponent":BeforeAddComponent,
				"index":index,
				"app":app
			});
			return com;
		}
		else{//找不到
			//写错误日志
			
		}
	}
};

//列表控件
var ContentComponent=function(){
	this.defaultdata=[{
	}];
	this.app=null;
	this.code="component_list";
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.searchCom=null;
	this.childs=[];
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		if(typeof(this.attributes.type)=='undefined' || this.attributes.type=='' || ("1,2,3").indexOf(this.attributes.type)<=-1){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'列表组件的type属性值错误'
			});
			this.goError('列表组件的type属性值错误');
		}
		if(typeof(this.attributes.style)=='undefined' || this.attributes.style=="" || ("NORMAL/GRID3/GRID4").indexOf(this.attributes.style)<=-1){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'列表组件的style属性值错误'
			});
			this.goError('列表组件的style属性值错误');
		}
		return bool;
	};
}
ContentComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<list type="1" style="GRID3"><listitem><itemname>联系人</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem><listitem><itemname>客户</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem><listitem><itemname>销售机会</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem></list>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	load:function(){
		if(this.attributes.type=='1'){//静态列表
			this.createStaticCom();
		}
		else if(this.attributes.type=='2' || this.attributes.type=='3'){//动态列表
			this.createActiveCom();
			if(typeof(this.attributes.listurlpara)!='undefined' && this.attributes.listurlpara!=''){
				var obj=this;
				this.searchCom=this.fac.createObj('',this.root,"component_search",{
					text:'请输入文本',
					url:'',
					para:'',
					searchClick:function(tex){
						obj.search(tex);
					}
				},this.ComponentSelected,this.BeforeAddComponent,0,this.app);
			}
		}		
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		$(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	search:function(tex){
		var obj=this;
		this.asyncData(tex,function(d){
			if(typeof(d.length)=="undefined"){
				obj.fac.writeLog(BUSIERRORCODE,"数据源接口返回值格式错误!");
				obj.goError('数据源接口返回值格式错误');
				return;
			}
			$(obj.root).find(".static_normal_ul,.active_normal_ul,.static_GRID3,.static_GRID4,.active_GRID3,.active_GRID4").remove();
			if(obj.attributes.style=='NORMAL'){
				$(obj.root).append(obj.createNormalHTML(d));
			}
			else if(obj.attributes.style=='GRID3'){
				$(obj.root).append(obj.createGRID3HTML(d));
			}
			else if(obj.attributes.style=='GRID4'){
				$(obj.root).append(obj.createGRID4HTML(d));
			}
			obj.bindEvents();
		});
	},
	createActiveCom:function(){
		if(this.attributes.style=='NORMAL'){
				var div=document.createElement('div');
				div.setAttribute('class','component cp_list_active_normal');
				//设置固定长度
				div.setAttribute("style","width:"+$(".runtimescreen").css("width"));				
				this.root=div;
				this.appendRoot(div);
		}
		else if(this.attributes.style=='GRID3'){
				var div=document.createElement('div');
				div.setAttribute('class','component cp_list_active_GRID3');
		    //设置固定长度
		    div.setAttribute("style","width:"+$(".runtimescreen").css("width"));				
				this.root=div;
				this.appendRoot(div);
		}
		else if(this.attributes.style=='GRID4'){
				var div=document.createElement('div');
				div.setAttribute('class','component cp_list_active_GRID4');
		    //设置固定长度
		    div.setAttribute("style","width:"+$(".runtimescreen").css("width"));				
				this.root=div;
				this.appendRoot(div);
		}
		if(this.attributes.type=="3")  //应用列表标识
		{
			$(this.root).attr("isapplist","1");
		}
		//获取数据
		var obj=this;
		this.asyncData('',function(d){
			if(obj.attributes.type=="3" && d.returncode=="0000")//应用列表数据，需要转换成列表格式对象
			{
				var applist = [];
				for(var i=0; i<d.list.length; i++)
				{
					applist.push({
						"id" : d.list[i].appid,
						"title" : d.list[i].appname,
						"icon" : d.list[i].logo,
						"appid" : d.list[i].appid,
						"version" : d.list[i].version
					});
				}
				if(applist.length==0)
				{
					//如果没有应用时，显示创建应用的图标
					applist.push({
						"id" : "",
						"title" : "创建应用",
						"icon" : "/bundles/fafatimewebase/images/icon_addapp_normal.png",
						"appid" : "",
						"version" : "0"
					});
				}
				d.listitems= applist;
			}
			if(d.listitems==null || typeof(d.listitems.length)=="undefined"){
				obj.fac.writeLog(BUSIERRORCODE,"数据源接口返回值格式错误!");
				obj.goError('数据源接口返回值格式错误');
				return;
			}			
			if(obj.attributes.style=='NORMAL'){
				$(obj.root).append(obj.createNormalHTML(d.listitems));
			}
			else if(obj.attributes.style=='GRID3'){
				$(obj.root).append(obj.createGRID3HTML(d.listitems));
			}
			else if(obj.attributes.style=='GRID4'){
				$(obj.root).append(obj.createGRID4HTML(d.listitems));
			}
			obj.bindEvents();
		});
	},
	createStaticCom:function(){
		if(this.attributes.style=='NORMAL'){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_list_static_normal');
		  //设置固定长度
		  div.setAttribute("style","width:"+$(".runtimescreen").css("width"));			
			this.root=div;
			this.appendRoot(div);
			$(this.root).append(this.createNormalHTML(this.attributes.listitems));
		}
		else if(this.attributes.style=='GRID3'){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_list_static_GRID3');
		  //设置固定长度
		  div.setAttribute("style","width:"+$(".runtimescreen").css("width"));			
			this.root=div;
			this.appendRoot(div);
			$(this.root).append(this.createGRID3HTML(this.attributes.listitems));
		}
		else if(this.attributes.style=='GRID4'){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_list_static_GRID4');
		  //设置固定长度
		  div.setAttribute("style","width:"+$(".runtimescreen").css("width"));			
			this.root=div;
			this.appendRoot(div);
			$(this.root).append(this.createGRID4HTML(this.attributes.listitems));
		}
		this.bindEvents();
	},
	createNormalHTML:function(json){
		if(this.attributes.type=='1'){
			var html=[];
			html.push("<ul class='static_normal_ul'>");
			for(var i=0;i<json.length;i++){
				html.push("<li functionid='"+json[i].functionid.text+"' class='static_normal_i'><img src='"+json[i].itemicon+"' class='static_normal_i_icon' /><div class='static_normal_i_name'>"+json[i].itemname+"</div><div class='static_normal_i_oper'></div></li>");
			}
			html.push("</ul>");
			return html.join('');
		}
		else if(this.attributes.type=='2' || this.attributes.type=='3'){
			var html=[];
			html.push("<ul class='active_normal_ul'>");
			for(var i=0;i<json.length;i++){
				//遍历所有属性值
				var str="";
				for(var s in json[i]){
					str+=" "+s+"="+json[i][s];
				}
				html.push("<li functionid='"+this.attributes.functionid+"' "+str+" class='active_normal_i'><img src='"+json[i].icon+"' class='active_normal_i_icon' /><div class='active_normal_i_name'>"+json[i].title+"</div><div class='active_normal_i_oper'></div></li>");
			}
			html.push("</ul>");
			return html.join('');
		}
	},
	createGRID3HTML:function(json){
		if(this.attributes.type=='1'){
			var html=[];
			html.push("<div class='static_GRID3'>");
			for(var i=0;i<json.length;i++){
				html.push("<div class='static_GRID3_i' functionid='"+json[i].functionid.text+"'>");
				html.push("<div class='static_GRID3_i_img'><img src='"+json[i].itemicon+"'/></div><div class='static_GRID3_i_name'>"+json[i].itemname+"</div>");
				html.push("</div>");
			}
			html.push("</div>");
			return html.join('');
		}
		else if(this.attributes.type=='2' || this.attributes.type=='3'){
			var html=[];
			html.push("<div class='active_GRID3'>");
			for(var i=0;i<json.length;i++){
				//遍历所有属性值
				var str="";
				for(var s in json[i]){
					str+=" "+s+"="+json[i][s];
				}
				html.push("<div class='active_GRID3_i'"+str+" functionid='"+json[i].functionid+"'>");
				html.push("<div class='active_GRID3_i_img'><img src='"+json[i].icon+"'/></div><div class='active_GRID3_i_name'>"+json[i].title+"</div>");
				html.push("</div>");
			}
			html.push("</div>");
			return html.join('');
		}
	},
	createGRID4HTML:function(json){
		if(this.attributes.type=='1'){
			var html=[];
			html.push("<div class='static_GRID4'>");
			for(var i=0;i<json.length;i++){
				html.push("<div class='static_GRID4_i' functionid='"+json[i].functionid.text+"'>");
				html.push("<div class='static_GRID4_i_img'><img src='"+json[i].itemicon+"'/></div><div class='static_GRID3_i_name'>"+json[i].itemname+"</div>");
				html.push("</div>");
			}
			html.push("</div>");
			return html.join('');
		}
		else if(this.attributes.type=='2' || this.attributes.type=='3'){
			var html=[];
			html.push("<div class='active_GRID4'>");
			for(var i=0;i<json.length;i++){
				//遍历所有属性值
				var str="";
				for(var s in json[i]){
					str+=" "+s+"="+json[i][s];
				}
				html.push("<div class='active_GRID4_i'"+str+" functionid='"+json[i].functionid+"'>");
				html.push("<div class='active_GRID4_i_img'><img src='"+json[i].icon+"'/></div><div class='active_GRID4_i_name'>"+json[i].title+"</div>");
				html.push("</div>");
			}
			html.push("</div>");
			return html.join('');
		}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				//如果是门户首页，则不处理
				if(obj.attributes.type=='3')	return;
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				//如果是门户首页，则不处理
				if(obj.attributes.type=='3')	return;
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				//如果是门户首页，则不处理
				if(obj.attributes.type=='3')	return;
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").hide();
//					$("#runtime_component_toolbar").appendTo($(document.body));
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("li.static_normal_i").bind(function(){
			if($(this).attr("functionid")!=""){
				//创建新界面
				obj.app.loadInterObj($(this).attr("functionid"));
			}
		});
	},
	asyncData:function(tex,func){
			var obj=this;
			if(this.attributes.type=="3") //表示应用列表（专用于门户的应用列表，数据来源于关注应用列表），需要特殊处理
			{
				this.attributes.listurl = "/api/http/mapp/myapp/"+g_curr_openid;
			}
			if(this.attributes.listurl!=null){
				$.ajax({
					url:this.attributes.listurl+(tex==''?"":((this.attributes.listurl.indexOf("?")>-1?"&":"?")+this.attributes.listurlpara+"="+tex)),
					dataType:'json',
					success:function(d){
						//判断返回值格式是否正确
						if(obj.searchCom!=null)
							obj.searchCom.ready();
						func(d);
					},
					error:function(xmldom,msg,execption){
						obj.fac.writeLog({
							returncode:COMPONENTERRORCODE,
							appid:obj.app.appid,
							functionid:obj.functionid,
							code:obj.code,
							index:obj.index,
							msg:'无效的动态列表绑定地址'
						});
						obj.goError('无效的动态列表绑定地址');
						//重新初始化一个显示错误的组件
					}
				});
			}
	}
}
//
var ContentComponentIOS=function(){
	ContentComponent.call(this);
};
ContentComponentIOS.prototype=new ContentComponent();
ContentComponentIOS.prototype.constructor=ContentComponent;
//
var ContentComponentAndroid=function(){
	ContentComponent.call(this);
};
ContentComponentAndroid.prototype=new ContentComponent();
ContentComponentAndroid.prototype.constructor=ContentComponent;

//标题控件
var TitleComponent=function(){
	this.code="component_title";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.timeout=null;
	this.area="title";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		if((typeof(this.attributes.text)=='undefined' || this.attributes.text=='') && ( this.attributes.pic==null || this.attributes.pic=="") ){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'标题组件的text属性值错误'
			});
			this.goError('标题组件的text属性值错误');
		}
		return bool;
	};
}
TitleComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<title><text>界面标题</text></title>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
			this.load();
			//$(this.root).find(".cp_title_text").addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component cp_title');
		this.root=div;
		this.appendRoot(this.root);
		
		var html=[];
		html.push("<div class='cp_title_text' style='color:"+this.attributes.color+";'>"+this.attributes.text+"</div>");
		$(this.root).append(html.join(''));
		if(this.attributes.pic && this.attributes.pic!='')
			$(this.root).css("background-image","url("+this.attributes.pic+")");
		this.bindEvents();
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		//this.setComBnts();
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
	}
}
var TitleComponentIOS=function(){
	TitleComponent.call(this);
};
TitleComponentIOS.prototype=new TitleComponent();
TitleComponentIOS.prototype.constructor=TitleComponent;
//
var TitleComponentAndroid=function(){
	TitleComponent.call(this);
};
TitleComponentAndroid.prototype=new TitleComponent();
TitleComponentAndroid.prototype.constructor=TitleComponent;

//菜单控件
var MenuComponent=function(){
	this.code="component_menu";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="title";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		if(typeof(this.attributes.position)=='undefined' || this.attributes.position=='' || ("R/L").indexOf(this.attributes.position)<=-1){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'菜单组件的位置属性设置有误'
			});
			this.goError('菜单组件的位置属性设置有误');
		}
		if(typeof(this.attributes.menuitems)=='undefined' || this.attributes.menuitems=='' || typeof(this.attributes.menuitems.length)=='undefined' || this.attributes.menuitems.length==0){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'菜单组件的菜单列表属性设置有误'
			});
			this.goError('菜单组件的菜单列表属性设置有误');
		}
		return bool;
	};
}
MenuComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<menu position="R"><menuitem><itemname>菜单项</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>scan</functionid></menuitem><menuitem><itemname>菜单项</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>add</functionid></menuitem></menu>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
			this.load();
			//$(this.root).find("ul").addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		if(this.attributes.position=="R"){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_menu_R');
			this.root=div;
			this.appendRoot(div);
			
			var html=[];
			html.push("<div class='menu_drop_R'></div>");
			html.push("<ul class='menu_ul_R'>");
			var json=this.attributes.menuitems;
			for(var i=0;i<json.length;i++){
				html.push("<li functionid='"+json[i].functionid+"' class='menu_i'><img src='"+json[i].itemicon+"' class='menu_i_icon' /><div class='menu_i_name'>"+json[i].itemname+"</div></li>");
			}
			html.push("</ul>");
			$(this.root).append(html.join(''));  
			this.bindEvents();
		}
		else if(this.attributes.position=="L"){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_menu_L');
			this.root=div;
			this.appendRoot(div);
			
			var html=[];
			html.push("<div class='menu_drop_L'></div>");
			html.push("<ul class='menu_ul_L'>");
			var json=this.attributes.menuitems;
			for(var i=0;i<json.length;i++){
				html.push("<li functionid='"+json[i].functionid+"' class='menu_i'><img src='"+json[i].itemicon+"' class='menu_i_icon' /><div class='menu_i_name'>"+json[i].itemname+"</div></li>");
			}
			html.push("</ul>");
			$(this.root).append(html.join(''));
			this.bindEvents();
		}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind("mousedown",function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).siblings("ul").addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				$(this).siblings("ul").slideDown(150);
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find(".menu_ul_L,.menu_ul_R").bind("mouseout",function(event){
			if(checkHover(event,this)){
				$(this).slideUp(150);
			}
		});
		$(this.root).find(".menu_ul_L,.menu_ul_R").find("li").bind("click",function(){
			if($(this).attr("functionid")!=""){
				//创建新界面
				obj.app.loadInterObj($(this).attr("functionid"));
			}
		});
	}
}
//
var MenuComponentIOS=function(){
	MenuComponent.call(this);
};
MenuComponentIOS.prototype=new MenuComponent();
MenuComponentIOS.prototype.constructor=MenuComponent;
//
var MenuComponentAndroid=function(){
	MenuComponent.call(this);
};
MenuComponentAndroid.prototype=new MenuComponent();
MenuComponentAndroid.prototype.constructor=MenuComponent;

//图片轮询
var SwitchComponent=function(){
	this.code="component_switch";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.interTimer=null;
	this.area="content";
	this.ComponentSelected=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.mTatch=false;
	this.validateAttr=function(){
		return true;
	}
}
SwitchComponent.prototype={	
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<switch timer="10"><pic><text>图片标题</text><url>http://api.wefafa.com/weapp/zjxx/index/news2.jpg</url></pic><pic><text>图片标题</text><url>http://api.wefafa.com/weapp/zjxx/index/news1.jpg</url></pic><pic><text>图片标题</text><url>http://api.wefafa.com/weapp/zjxx/index/news3.jpg</url></pic></switch>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component cp_switch');
		//设置固定长度
		div.setAttribute("style","width:"+$(".runtimescreen").css("width"));		
		this.root=div;
		this.appendRoot(div);
		
		if(typeof(this.attributes.listurl)!="undefined" && this.attributes.listurl!='' && this.attributes.listurl!=null){
			var obj=this;
			this.asyncData('',function(d){
				if(d.listitems==null || typeof(d.listitems.length)=="undefined"){
					obj.fac.writeLog(BUSIERRORCODE,"数据源接口返回值格式错误!");
					obj.goError('数据源接口返回值格式错误');
					return;
				}
				obj.createActiveHTML(d.listitems);
			});
		}
		else{
			var html=[];
			html.push("<div class='switch_img_div'>");
			for(var i=0;i<this.attributes.pics.length;i++){
				if(i==0)
					html.push("<div class='switch_img_i switch_selected'><img src='"+this.attributes.pics[i].url+"'/><span>"+this.attributes.pics[i].text+"</span></div>");
				else
					html.push("<div style='display:none;' class='switch_img_i'><img src='"+this.attributes.pics[i].url+"'/><span>"+this.attributes.pics[i].text+"</span></div>");
			}
			html.push("</div>");
			html.push("<div class='switch_dot_div'>");
			for(var i=0;i<this.attributes.pics.length;i++){
				if(i==0)
					html.push("<span order='"+i.toString()+"'>●</span>");
				else
					html.push("<span order='"+i.toString()+"'>○</span>");
			}
			html.push("</div>");
			$(this.root).append(html.join(''));
			this.bindEvents();	
		}
	},
	createActiveHTML:function(d){
			var html=[];
			html.push("<div class='switch_img_div'>");
			for(var i=0;i<d.length;i++){
				if(i==0)
					html.push("<div class='switch_img_i switch_selected'><img src='"+d[i].icon+"'/><span>"+d[i].title+"</span></div>");
				else
					html.push("<div class='switch_img_i' style='display:none;'><img src='"+d[i].icon+"'/><span>"+d[i].title+"</span></div>");
			}
			html.push("</div>");
			html.push("<div class='switch_dot_div'>");
			for(var i=0;i<d.length;i++){
				if(i==0)
					html.push("<span order='"+i.toString()+"' class='dot_selected'>●</span>");
				else
					html.push("<span order='"+i.toString()+"'>○</span>");
			}
			html.push("</div>");
			$(this.root).append(html.join(''));
			this.bindEvents();	
	},
	asyncData:function(tex,func){
			var obj=this;
			if(this.attributes.listurl!=null){
				$.ajax({
					url:this.attributes.listurl,
					dataType:'json',
					success:function(d){
						//判断返回值格式是否正确
						func(d);
					},
					error:function(xmldom,msg,execption){
						obj.fac.writeLog({
							returncode:COMPONENTERRORCODE,
							appid:obj.app.appid,
							functionid:obj.functionid,
							code:obj.code,
							index:obj.index,
							msg:'无效的绑定地址'
						});
						obj.goError('无效的绑定地址');
						//重新初始化一个显示错误的组件
					}
				});
			}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind("mousedown",function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		if(parseInt(this.attributes.timer)!=0){
			var n=0;
			if(this.interTimer!=null)
				clearInterval(this.interTimer);
			this.interTimer=setInterval(function(){
				$(obj.root).find(".switch_img_div .switch_img_i").hide();
				$next=$(obj.root).find(".switch_img_div .switch_selected").removeClass("switch_selected").next();
				if($next.length==0){
					$next=$($(obj.root).find(".switch_img_div .switch_img_i").get(0));
				}
				$next.addClass("switch_selected").fadeIn(800);
				$(obj.root).find(".switch_dot_div span").text("○");
				$nextdot=$(obj.root).find(".switch_dot_div span.dot_selected").removeClass("dot_selected").next();
				if($nextdot.length==0){
					$nextdot=$($(obj.root).find(".switch_dot_div span").get(0));
				}
				$nextdot.addClass("dot_selected").text("●");
			},parseInt(obj.attributes.timer)*1000);
		}
		else{
			$(this.root).find(".switch_dot_div span").bind("click",function(){
				var order=parseInt($(this).attr("order"));
				var total=$(obj.root).find(".switch_img_div .switch_img_i").length;
				order=((order+1>total-1)?0:order+1);
				$(obj.root).find(".switch_img_div .switch_img_i").hide();
				$($(obj.root).find(".switch_img_div .switch_img_i").get(order)).fadeIn(800);
				$(obj.root).find(".switch_dot_div span").text("○");
				$(obj.root).find(".switch_dot_div span.dot_selected").removeClass("dot_selected");
				$($(obj.root).find(".switch_dot_div span.dot_selected").get(order)).addClass("dot_selected").text("●");
			});
		}
		
	}
}
//
var SwitchComponentIOS=function(){
	SwitchComponent.call(this);
};
SwitchComponentIOS.prototype=new SwitchComponent();
SwitchComponentIOS.prototype.constructor=SwitchComponent;
//
var SwitchComponentAndroid=function(){
	SwitchComponent.call(this);
};
SwitchComponentAndroid.prototype=new SwitchComponent();
SwitchComponentAndroid.prototype.constructor=SwitchComponent;

//搜索控件
var SearchComponent=function(){
	this.code="component_search";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.attach=false;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.searchClick=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}
SearchComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.attributes.searchClick){}
		else{
			this.attributes.searchClick=function(tex){
				if(this.attributes.functionid!=""){
					//创建新界面
					this.app.loadInterObj(this.attributes.functionid);
				}
			};
		}
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid){
			this.functionid=d.functionid;
		}
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<search><text>请输入查询关键字</text><functionid/></search>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component cp_search');
		this.root=div;
		this.appendRoot(div);		
		var html=[];
		html.push("<div class='search_area' functionid='"+this.attributes.functionid+"' url='"+this.attributes.url+"'><img src='/bundles/fafatimewebase/images/icon_search.png'/><input placeholder='"+this.attributes.text+"' type='text'/></div><div class='search_click'>搜索</div>");
		
		var $inter=null;
			var pComponent=this.app.getSourceComponent(this.functionid);
			while(pComponent!=null && (pComponent.code=="component_nav" || pComponent.code=="component_tabs")){
					$inter=$(".interface[functionid='"+pComponent.functionid+"']");
			 	  pComponent=this.app.getSourceComponent(pComponent.functionid);
			 }
		$(this.root).append(html.join(''));
		if ( $inter != null && $inter.find(".interface_center").children(".component").length>1){
			$inter.find(".interface_center").children(".component:first").before(this.root);
		}
		this.bindEvents();
	},
	getXML:function(info)
	{
		return "<search><text>"+(info==null?"查询关键字":info.text)+"</text></search>";
	},
	ready:function(){
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find(".search_click").bind('click',function(){
			var tex=$(this).siblings(".search_area").find("input").val();
			obj.attributes.searchClick(tex);
		});
	}
}

//
var SearchComponentIOS=function(){
	SearchComponent.call(this);
};
SearchComponentIOS.prototype=new SearchComponent();
SearchComponentIOS.prototype.constructor=SearchComponent;
//
var SearchComponentAndroid=function(){
	SearchComponent.call(this);
};
SearchComponentAndroid.prototype=new SearchComponent();
SearchComponentAndroid.prototype.constructor=SearchComponent;

//底部导航

var NavComponent=function(){
	this.code="component_nav";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="navigation";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}
NavComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
		  this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<nav><navitem><itemname>按钮一</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><itemicon_active>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon_active><actiontype>TEMPLATE</actiontype><template></template></navitem><navitem><itemname>按钮二</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><itemicon_active>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon_active><actiontype>TEMPLATE</actiontype><template></template></navitem></nav>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.app.p).find(".interface[functionid='"+this.functionid+"']").find(".interface_head,.interface_center").find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('ul');
		div.setAttribute('class','component cp_nav');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		for(var i=0;i<this.attributes.navitems.length;i++){
			if(i==0){
				html.push("<li order='"+i.toString()+"' check='1' style='background-color:"+this.attributes.bgcolor_active+"; width:"+(100/this.attributes.navitems.length).toString()+"%;' class='nav_li li_highlight'>");
				html.push("<div class='nav_li_img'><center><img class='nav_img_static' style='display:none;' src='"+this.attributes.navitems[i].itemicon+"' /><img class='nav_img_active' src='"+this.attributes.navitems[i].itemicon_active+"' /></center></div>");
				html.push("<div class='nav_li_name name_highlight'>"+this.attributes.navitems[i].itemname+"</div>");
				html.push("</li>");
			}
			else{
				html.push("<li order='"+i.toString()+"' style='width:"+(100/this.attributes.navitems.length).toString()+"%;' class='nav_li'>");
				html.push("<div class='nav_li_img'><center><img class='nav_img_static' src='"+this.attributes.navitems[i].itemicon+"' /><img class='nav_img_active' style='display:none;' src='"+this.attributes.navitems[i].itemicon_active+"' /></center></div>");
				html.push("<div class='nav_li_name'>"+this.attributes.navitems[i].itemname+"</div>");
				html.push("</li>");
			}
		}
		$(this.root).append(html.join(''));
		$(this.root).css("background-color",this.attributes.bgcolor);
		this.loadNav(0);
		this.bindEvents();
	},
	loadNav:function(order){
		var $this = $(this.root).find("li.nav_li[order='"+order.toString()+"']"),$this_p = $this.parent();
		$this_p.find('.nav_img_active').hide();
		$this_p.find('.nav_img_static').show();
		$this_p.find('.nav_li_name').removeClass('name_highlight');
		$this_p.find('li').removeClass('li_highlight');
		$this_p.find('li').attr('check','0');
		$this.siblings().css("background-color","");
		$this.addClass('li_highlight');
		$this.css("background-color",this.attributes.bgcolor_active);
		$this.attr('check','1');
		$this.find('.nav_img_static').hide();
		$this.find('.nav_img_active').show();
		$this.find('.nav_li_name').addClass('name_highlight');
		
		var issender=arguments[1]?arguments[1]:false;
		var item=this.attributes.navitems[parseInt(order)];
		var $inter=$(this.app.p).find(".interface[functionid='"+this.functionid+"']");
		$inter.children(".interface_head").html(null);
		$inter.children(".interface_center").html(null);
		
		this.app.loadInterObj(item.functionid.text,{
			functionid:item.functionid.text,
			device:this.app.device,
			p:$inter.get(0),
			xmlBuilder:this.app.xmlBuilder,
			xmlParser:this.app.xmlParser,
			app:this.app,
			params:{},
			isChild:true,
			sender:(issender?{
				functionid:this.functionid,
				index:this.index,
				order:order
			}:null),
			InterfaceSelected:this.app.InterfaceSelected,
			ComponentSelected:this.ComponentSelected,
			ErrorOccured:this.app.ErrorOccured,
			BeforeAddComponent:this.app.BeforeAddComponent
		});
		
		return;
		
		if(item.actiontype=='TEMPLATE'){
			var coms=item.template;
			for(var i=0;i<coms.length;i++){
				var code=coms[i].code;
					var rootp=null;
					if(code=="component_title" || code=="component_menu"){
						rootp=$inter.find(".interface_head");
					}
					else if(code=="component_nav"){
						rootp=$inter.find(".interface_foot");
						$inter.find(".interface_foot")[0].style.height="60px";
						$inter.find(".interface_center")[0].style.height="370px";
					}
					else{
						rootp=$inter.find(".interface_center");
					}
					this.childs.push(this.fac.createObj(this.functionid,rootp,coms[i].code,coms[i].attrs,this.ComponentSelected,this.BeforeAddComponent,this.getPath((parseInt(order)+1).toString()+"-"+(i+1).toString()),this.app));
			}
		}
		else{
			$inter.children(".interface_center").html("<div class='nav_phone'>手机原生功能</div>");
		}
		this.app.navorder=parseInt(order);
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("li.nav_li").bind('click',function(){
			obj.loadNav($(this).attr("order"),true);
		});
	}
}

//
var NavComponentIOS=function(){
	NavComponent.call(this);
};
NavComponentIOS.prototype=new NavComponent();
NavComponentIOS.prototype.constructor=NavComponent;
//
var NavComponentAndroid=function(){
	NavComponent.call(this);
};
NavComponentAndroid.prototype=new NavComponent();
NavComponentAndroid.prototype.constructor=NavComponent;

//tabs控件

var TabsComponent=function(){
	this.code="component_tabs";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
};

TabsComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<tabs><tabitem><itemname>选项卡一</itemname><template><switch timer="10"><pic><text>四川人大工作会议</text><url>http://api.wefafa.com/weapp/zjxx/index/news2.jpg</url></pic><pic><text>辽宁检查院工作会议</text><url>http://api.wefafa.com/weapp/zjxx/index/news1.jpg</url></pic><pic><text>北京市长检查日常勤务</text><url>http://api.wefafa.com/weapp/zjxx/index/news3.jpg</url></pic></switch><list type="2" style="NORMAL"><listurl>http://we.fafatime.com/api/http/test/ listdata</listurl><listurlpara/><functionid>2</functionid></list></template></tabitem><tabitem><itemname>选项卡二</itemname><template><list type="2" style="NORMAL"><listurl>http://xx.xx.com/xx</listurl><listurlpara>x</listurlpara><functionid>2</functionid></list></template></tabitem></tabs>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component component_tabs');
		//设置固定长度
		div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<ul class='tabs_ul' style='background-color:"+this.attributes.bgcolor+";'>");
		for(var i=0;i<this.attributes.tabitems.length;i++){
			if(i==0)
				html.push("<li class='tabs_li tab_selected' style='background-color:"+this.attributes.bgcolor_active+";width:"+(100/this.attributes.tabitems.length).toString()+"%;' tabsindex='"+i.toString()+"'>"+((typeof(this.attributes.tabitems[i].itemicon)=='undefined' || this.attributes.tabitems[i].itemicon=='' || typeof(this.attributes.tabitems[i].itemicon_active)=='undefined' || this.attributes.tabitems[i].itemicon_active=='')?"":("<img style='display:none;' class='tab_static_img' src='"+this.attributes.tabitems[i].itemicon+"' /><img class='tab_active_img' src='"+this.attributes.tabitems[i].itemicon_active+"' />"))+this.attributes.tabitems[i].itemname+"</li>");
			else
				html.push("<li class='tabs_li' style='width:"+(100/this.attributes.tabitems.length).toString()+"%;' tabsindex='"+i.toString()+"'>"+((typeof(this.attributes.tabitems[i].itemicon)=='undefined' || this.attributes.tabitems[i].itemicon=='' || typeof(this.attributes.tabitems[i].itemicon_active)=='undefined' || this.attributes.tabitems[i].itemicon_active=='')?"":("<img style='display:none;' class='tab_static_img' src='"+this.attributes.tabitems[i].itemicon+"' /><img class='tab_active_img' src='"+this.attributes.tabitems[i].itemicon_active+"' />"))+this.attributes.tabitems[i].itemname+"</li>");
		}
		html.push("</ul>");
		
		for(var i=0;i<this.attributes.tabitems.length;i++){
			if(i==0)
				html.push("<div tabsindex='"+i.toString()+"' class='tabs_div'></div>");
			else
				html.push("<div style='display:none;' tabsindex='"+i.toString()+"' class='tabs_div'></div>");
		}
		$(this.root).append(html.join(''));
		this.bindEvents();
		this.loadTab(0);
	},
	loadTab:function(tabsindex){
		var $this=$(this.root).find(".tabs_li[tabsindex='"+tabsindex.toString()+"']");
		$this.siblings().removeClass("tab_selected");
		$this.siblings().css("background-color","");
		$this.addClass("tab_selected");
		$this.parent().find(".tab_active_img").hide();
		$this.parent().find(".tab_static_img").show();
		$this.find(".tab_static_img").hide();
		$this.find(".tab_active_img").show();
		$this.css("background-color",this.attributes.bgcolor_active);
		$this.parent().siblings(".tabs_div").hide();
		$this.parent().siblings(".tabs_div[tabsindex='"+$this.attr("tabsindex")+"']").show();
		
		var issender=arguments[1]?arguments[1]:false;
		var rootp=null;
		rootp=$(this.root).find(".tabs_div[tabsindex='"+tabsindex+"']")[0];
		
		var item=this.attributes.tabitems[parseInt(tabsindex)];
		var $inter=$(this.app.p).find(".interface[functionid='"+this.functionid+"']");
		this.app.loadInterObj(item.functionid.text,{
			functionid:item.functionid.text,
			device:this.app.device,
			p:rootp,
			xmlBuilder:this.app.xmlBuilder,
			xmlParser:this.app.xmlParser,
			app:this.app,
			params:{},
			isChild:true,
			sender:(issender?{
				functionid:this.functionid,
				index:this.index,
				tabsindex:tabsindex
			}:null),
			InterfaceSelected:this.app.InterfaceSelected,
			ComponentSelected:this.ComponentSelected,
			ErrorOccured:this.app.ErrorOccured,
			BeforeAddComponent:this.app.BeforeAddComponent
		});
		
		return;
		
		if(true){
			var coms=item.template;
			for(var i=0;i<coms.length;i++){
				var code=coms[i].code;
					var rootp=null;
					rootp=$(this.root).find(".tabs_div[tabsindex='"+tabsindex+"']")[0];
					this.childs.push(this.fac.createObj(this.functionid,rootp,coms[i].code,coms[i].attrs,this.ComponentSelected,this.BeforeAddComponent,this.getPath((parseInt(tabsindex)+1).toString()+"-"+(i+1).toString()),this.app));
			}
		}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		
		$(this.root).find(".tabs_li").bind('click',function(){
			var $this=$(this);
			obj.loadTab($this.attr("tabsindex"),true);
		});
	}
};

//
var TabsComponentIOS=function(){
	TabsComponent.call(this);
};
TabsComponentIOS.prototype=new TabsComponent();
TabsComponentIOS.prototype.constructor=TabsComponent;
//
var TabsComponentAndroid=function(){
	TabsComponent.call(this);
};
TabsComponentAndroid.prototype=new TabsComponent();
TabsComponentAndroid.prototype.constructor=TabsComponent;

GroupNewsComponent=function(){
	this.code="component_groupnews";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

GroupNewsComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var GroupNewsComponentIOS=function(){
	GroupNewsComponent.call(this);
};
GroupNewsComponentIOS.prototype=new GroupNewsComponent();
GroupNewsComponentIOS.prototype.constructor=GroupNewsComponent;
//
var GroupNewsComponentAndroid=function(){
	GroupNewsComponent.call(this);
};
GroupNewsComponentAndroid.prototype=new GroupNewsComponent();
GroupNewsComponentAndroid.prototype.constructor=GroupNewsComponent;

CircleNewsComponent=function(){
	this.code="component_circlenews";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

CircleNewsComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var CircleNewsComponentIOS=function(){
	CircleNewsComponent.call(this);
};
CircleNewsComponentIOS.prototype=new CircleNewsComponent();
CircleNewsComponentIOS.prototype.constructor=CircleNewsComponent;
//
var CircleNewsComponentAndroid=function(){
	CircleNewsComponent.call(this);
};
CircleNewsComponentAndroid.prototype=new CircleNewsComponent();
CircleNewsComponentAndroid.prototype.constructor=CircleNewsComponent;

MicroComponent=function(){
	this.code="component_publicaccount";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

MicroComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var MicroComponentIOS=function(){
	MicroComponent.call(this);
};
MicroComponentIOS.prototype=new MicroComponent();
MicroComponentIOS.prototype.constructor=MicroComponent;
//
var MicroComponentAndroid=function(){
	MicroComponent.call(this);
};
MicroComponentAndroid.prototype=new MicroComponent();
MicroComponentAndroid.prototype.constructor=MicroComponent;



//列表控件
var AppListComponent=function(){
	this.defaultdata=[{
	}];
	this.app=null;
	this.code="component_applist";
	this.functionid=null;
	this.listurl="";
	this.index=0;
	this.root=null;
	this.fac=null;
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.searchCom=null;
	this.childs=[];
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}
AppListComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
		
	},
	getDefaultXml:function(){
		return '<list type="1" style="GRID3"><listitem><itemname>联系人</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem><listitem><itemname>客户</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem><listitem><itemname>销售机会</itemname><itemicon>http://we.fafatime.com/getfile/534ba34c7c274a1445000000</itemicon><functionid>list</functionid></listitem></list>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	load:function(){
		this.createActiveCom();
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	search:function(tex){
		var obj=this;
		this.asyncData(tex,function(d){
			if(typeof(d.length)=="undefined"){
				obj.fac.writeLog(BUSIERRORCODE,"数据源接口返回值格式错误!");
				obj.goError('数据源接口返回值格式错误');
				return;
			}
			$(obj.root).find(".static_normal_ul,.active_normal_ul,.static_GRID3,.static_GRID4,.active_GRID3,.active_GRID4").remove();
			if(obj.attributes.style=='NORMAL'){
				$(obj.root).append(obj.createNormalHTML(d));
			}
			else if(obj.attributes.style=='GRID3'){
				$(obj.root).append(obj.createGRID3HTML(d));
			}
			else if(obj.attributes.style=='GRID4'){
				$(obj.root).append(obj.createGRID4HTML(d));
			}
			obj.bindEvents();
		});
	},
	createActiveCom:function(){
		if(this.attributes.style=='GRID3'){
				var div=document.createElement('div');
				div.setAttribute('class','component cp_applist cp_list_active_GRID3');
				//设置固定长度
		    div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
				this.root=div;
				this.appendRoot(div);
		}
		else if(this.attributes.style=='GRID4'){
				var div=document.createElement('div');
				div.setAttribute('class','component cp_applist cp_list_active_GRID4');
				//设置固定长度
		    div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
				this.root=div;
				this.appendRoot(div);
		}
		$(this.root).attr("isapplist","1");
		//获取数据
		var obj=this;
		this.asyncData('',function(d){
			if(d.returncode=="0000")//应用列表数据，需要转换成列表格式对象
			{
				var applist = [];
				for(var i=0; i<d.list.length; i++)
				{
					applist.push({
						"id" : d.list[i].appid,
						"title" : d.list[i].appname,
						"icon" : d.list[i].logo,
						"appid" : d.list[i].appid,
						"version" : d.list[i].version
					});
				}
				if(applist.length==0)
				{
					//如果没有应用时，显示创建应用的图标
					$(obj.root).append(obj.createAppHTML());
					$(".component_createApp_area").parent().css("padding-bottom","0px");
					return;
				}
				d.listitems= applist;
			}
			if(d.listitems==null || typeof(d.listitems.length)=="undefined"){
				obj.fac.writeLog(BUSIERRORCODE,"数据源接口返回值格式错误!");
				obj.goError('数据源接口返回值格式错误');
				return;
			}				
			else if(obj.attributes.style=='GRID3'){
					$(obj.root).append(obj.createGRID3HTML(d.listitems));
			}
			else if(obj.attributes.style=='GRID4'){
				$(obj.root).append(obj.createGRID4HTML(d.listitems));
			}
			obj.bindEvents();
		});
	},
	createAppHTML:function(){
		 var html = new Array();
		 html.push("<div class='component_createApp_area'>");
		 html.push("<div class='component_createicon'><span><span></div>");
		 html.push("<div class='component_createtitle'><span>您的企业暂时没有任何应用</span></div>");
		 html.push("<div class='component_createtitle' style='margin-top:-5px;'><span>请创建新应用</span></div>");
		 html.push("<div style='width:100%;margin-top:35px;'><span class='component_createApp' onclick='ApplicationMgr.component_createApp();'>创建应用</span></div>");
		 html.push("</div>");
		 return html.join('');			 
	},
	createGRID3HTML:function(json){
		var html=[];
		html.push("<div class='active_GRID3'>");
		for(var i=0;i<json.length;i++){
			//遍历所有属性值
			var str="";
			for(var s in json[i]){
				str+=" "+s+"='"+json[i][s]+"'";
			}
			html.push("<div class='active_GRID3_i'"+str+">");
			html.push("<div class='active_GRID3_i_img'><img src='"+json[i].icon+"'/></div><div class='active_GRID3_i_name'>"+json[i].title+"</div>");
			html.push("</div>");
		}
		html.push("</div>");
		return html.join('');
	},
	
	createGRID4HTML:function(json){
		var html=[];
		html.push("<div class='active_GRID4'>");
		for(var i=0;i<json.length;i++){
			//遍历所有属性值
			var str="";
			for(var s in json[i]){
				str+=" "+s+"="+json[i][s];
			}
			html.push("<div class='active_GRID4_i'"+str+" functionid='"+json[i].functionid+"'>");
			html.push("<div class='active_GRID4_i_img'><img src='"+json[i].icon+"'/></div><div class='active_GRID4_i_name'>"+json[i].title+"</div>");
			html.push("</div>");
		}
		html.push("</div>");
		return html.join('');
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").hide();
//					$("#runtime_component_toolbar").appendTo($(document.body));
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("li.static_normal_i").bind(function(){
			if($(this).attr("functionid")!=""){
				//创建新界面
				obj.app.loadInterObj($(this).attr("functionid"));
			}
		});
	},
	asyncData:function(tex,func){
			var obj=this;
			$.ajax({
					url:("/api/http/mapp/myapp/"+g_curr_openid),
					dataType:'json',
					success:function(d){
						//判断返回值格式是否正确
						if(obj.searchCom!=null)
							obj.searchCom.ready();
						func(d);
					},
					error:function(xmldom,msg,execption){
						obj.fac.writeLog({
							returncode:COMPONENTERRORCODE,
							appid:obj.app.appid,
							functionid:obj.functionid,
							code:obj.code,
							index:obj.index,
							msg:'无效的动态列表绑定地址'
						});
						obj.goError('无效的动态列表绑定地址');
						//重新初始化一个显示错误的组件
					}
				});
	}
}
//
var AppListComponentIOS=function(){
	AppListComponent.call(this);
};
AppListComponentIOS.prototype=new AppListComponent();
AppListComponentIOS.prototype.constructor=AppListComponent;
//
var AppListComponentAndroid=function(){
	AppListComponent.call(this);
};
AppListComponentAndroid.prototype=new AppListComponent();
AppListComponentAndroid.prototype.constructor=AppListComponent;

//知识库
RepositoryComponent=function(){
	this.code="component_repository";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

RepositoryComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var RepositoryComponentIOS=function(){
	RepositoryComponent.call(this);
};
RepositoryComponentIOS.prototype=new RepositoryComponent();
RepositoryComponentIOS.prototype.constructor=RepositoryComponent;
//
var RepositoryComponentAndroid=function(){
	RepositoryComponent.call(this);
};
RepositoryComponentAndroid.prototype=new RepositoryComponent();
RepositoryComponentAndroid.prototype.constructor=RepositoryComponent;

//通讯录
ContactsComponent=function(){
	this.code="component_contacts";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

ContactsComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var ContactsComponentIOS=function(){
	ContactsComponent.call(this);
};
ContactsComponentIOS.prototype=new ContactsComponent();
ContactsComponentIOS.prototype.constructor=ContactsComponent;
//
var ContactsComponentAndroid=function(){
	ContactsComponent.call(this);
};
ContactsComponentAndroid.prototype=new ContactsComponent();
ContactsComponentAndroid.prototype.constructor=ContactsComponent;

//消息中心
MessageComponent=function(){
	this.code="component_message";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

MessageComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var MessageComponentIOS=function(){
	MessageComponent.call(this);
};
MessageComponentIOS.prototype=new MessageComponent();
MessageComponentIOS.prototype.constructor=MessageComponent;
//
var MessageComponentAndroid=function(){
	MessageComponent.call(this);
};
MessageComponentAndroid.prototype=new MessageComponent();
MessageComponentAndroid.prototype.constructor=MessageComponent;

//企业微博
BlogComponent=function(){
	this.code="component_enoweibo";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

BlogComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var BlogComponentIOS=function(){
	BlogComponent.call(this);
};
BlogComponentIOS.prototype=new BlogComponent();
BlogComponentIOS.prototype.constructor=BlogComponent;
//
var BlogComponentAndroid=function(){
	BlogComponent.call(this);
};
BlogComponentAndroid.prototype=new BlogComponent();
BlogComponentAndroid.prototype.constructor=BlogComponent;

//设置
SettingComponent=function(){
	this.code="component_setting";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

SettingComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var SettingComponentIOS=function(){
	SettingComponent.call(this);
};
SettingComponentIOS.prototype=new SettingComponent();
SettingComponentIOS.prototype.constructor=SettingComponent;
//
var SettingComponentAndroid=function(){
	SettingComponent.call(this);
};
SettingComponentAndroid.prototype=new SettingComponent();
SettingComponentAndroid.prototype.constructor=SettingComponent;

//用户属性控件
var UserProfileComponent=function(){
	this.code="component_userprofile";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.timeout=null;
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		return bool;
	};
}
UserProfileComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
			this.load();
			//$(this.root).find(".cp_title_text").addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component cp_summary');
		//设置固定长度
		div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='summary_header'>");
		html.push("<img onerror=\"this.src='http://we.fafatime.com/bundles/fafatimewebase/images/no_photo.png'\" src='"+(this.attributes.header!='' && this.attributes.header!=null?this.attributes.header:"http://we.fafatime.com/bundles/fafatimewebase/images/no_photo.png")+"'/>");
		html.push("</div>");
		html.push("<ul class='summary_items'>");
		for(var i=0;i< this.attributes.items.length;i++){
			html.push("<li sumindex='"+i.toString()+"' style='width:"+(100/this.attributes.items.length).toString()+"%;"+((this.attributes.items[i].itemicon=="" || this.attributes.items[i].itemicon==null)?'':('background-image:url('+this.attributes.items[i].itemicon+')'))+"' functionid='"+this.attributes.items[i].functionid.text+"'><div class='summary_item_count'>0</div><div class='summary_item_name'>"+this.attributes.items[i].itemname+"</div>");
			if(i!=this.attributes.items.length-1)
				html.push('<div class="summary_hr"></div>');
			html.push("</li>");
		}
		html.push("</ul>");
		
		$(this.root).append(html.join(''));
		$(this.root).css({"background-color":this.attributes.bgcolor,"color":this.attributes.color});
		if(this.attributes.bgpic!='' && this.attributes.bgpic!=null){
			$(this.root).css({"background-image":"url("+this.attributes.bgpic+")"});
		}
		this.bindEvents();
		var obj=this;
		for(var i=0;i<this.attributes.items.length;i++){
			this.asyncData(this.attributes.items[i].dataurl,i,function(d,sumindex){
				if(d.succeed=='1'){
					$(obj.root).find("li[sumindex='"+sumindex.toString()+"'] .summary_item_count").text(d.data);
				}
				else{
					obj.goError('url地址数据获取失败');
				}
			});
		}
	},
	asyncData:function(url,sumindex,func){
			var obj=this;
			if(url!=null && url!=''){
				$.ajax({
					url:url,
					dataType:'json',
					success:function(d){
						//判断返回值格式是否正确
						func(d,sumindex);
					},
					error:function(xmldom,msg,execption){
						obj.fac.writeLog({
							returncode:COMPONENTERRORCODE,
							appid:obj.app.appid,
							functionid:obj.functionid,
							code:obj.code,
							index:obj.index,
							msg:'无效的url地址'
						});
						obj.goError('无效的url地址');
						//重新初始化一个显示错误的组件
					}
				});
			}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		//this.setComBnts();
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("li[sumindex]").bind('click',function(){
			obj.app.loadInterObj($(this).attr("functionid"));
		});
	}
}
var UserProfileComponentIOS=function(){
	UserProfileComponent.call(this);
};
UserProfileComponentIOS.prototype=new UserProfileComponent();
UserProfileComponentIOS.prototype.constructor=UserProfileComponent;
//
var UserProfileComponentAndroid=function(){
	UserProfileComponent.call(this);
};
UserProfileComponentAndroid.prototype=new UserProfileComponent();
UserProfileComponentAndroid.prototype.constructor=UserProfileComponent;

//用户帐号控件
var UserBasicInfoComponent=function(){
	this.code="component_userbasicinfo";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.timeout=null;
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		return bool;
	};
}
UserBasicInfoComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
			this.load();
			//$(this.root).find(".cp_title_text").addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component cp_userbaseinfo');
		//设置固定长度
		div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='base_header'><img onerror=\"this.src='http://we.fafatime.com/bundles/fafatimewebase/images/no_photo.png'\" style='width:45px;height:45px;' src='"+g_photo_path+"'/></div>");
		html.push("<div class='base_info'><div class='base_name'>"+g_nick_name+"</div><div class='base_account'>"+g_login_account+"</div></div>");
		html.push("<div class='base_detail_open'>></div>");
		
		$(this.root).append(html.join(''));
		$(this.root).css({"background-color":this.attributes.bgcolor,"color":this.attributes.color});
		$(this.root).attr("thefunctionid",this.attributes.functionid.text);
		this.bindEvents();
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		//this.setComBnts();
		this.bindEvents();
	},
	asyncData:function(url,func){
			var obj=this;
			if(url!=null && url!=''){
				$.ajax({
					url:url,
					dataType:'json',
					success:function(d){
						//判断返回值格式是否正确
						func(d);
					},
					error:function(xmldom,msg,execption){
						obj.fac.writeLog({
							returncode:COMPONENTERRORCODE,
							appid:obj.app.appid,
							functionid:obj.functionid,
							code:obj.code,
							index:obj.index,
							msg:'当前用户获取失败'
						});
						obj.goError('当前用户获取失败');
						//重新初始化一个显示错误的组件
					}
				});
			}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).bind("click",function(){
			obj.app.loadInterObj($(this).attr("thefunctionid"));
		});
	}
}
var UserBasicInfoComponentIOS=function(){
	UserBasicInfoComponent.call(this);
};
UserBasicInfoComponentIOS.prototype=new UserBasicInfoComponent();
UserBasicInfoComponentIOS.prototype.constructor=UserBasicInfoComponent;
//
var UserBasicInfoComponentAndroid=function(){
	UserBasicInfoComponent.call(this);
};
UserBasicInfoComponentAndroid.prototype=new UserBasicInfoComponent();
UserBasicInfoComponentAndroid.prototype.constructor=UserBasicInfoComponent;

//搭配组件
MatchListComponent=function(){
	this.code="component_matchlist";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

MatchListComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		//设置固定长度
		div.setAttribute("style","width:"+$(".runtimescreen").css("width"));
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var MatchListComponentIOS=function(){
	MatchListComponent.call(this);
};
MatchListComponentIOS.prototype=new MatchListComponent();
MatchListComponentIOS.prototype.constructor=MatchListComponent;
//
var MatchListComponentAndroid=function(){
	MatchListComponent.call(this);
};
MatchListComponentAndroid.prototype=new MatchListComponent();
MatchListComponentAndroid.prototype.constructor=MatchListComponent;

//搭配详细组件
MatchDetailComponent=function(){
	this.code="component_matchdetail";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

MatchDetailComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var MatchDetailComponentIOS=function(){
	MatchDetailComponent.call(this);
};
MatchDetailComponentIOS.prototype=new MatchDetailComponent();
MatchDetailComponentIOS.prototype.constructor=MatchDetailComponent;
//
var MatchDetailComponentAndroid=function(){
	MatchDetailComponent.call(this);
};
MatchDetailComponentAndroid.prototype=new MatchDetailComponent();
MatchDetailComponentAndroid.prototype.constructor=MatchDetailComponent;

//搭配详细组件
GoodsDetailComponent=function(){
	this.code="component_goodsdetail";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="content";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		return true;
	}
}

GoodsDetailComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").remove();
			this.load();
			//$(this.root).addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		var div=document.createElement('div');
		div.setAttribute('class','component inter_native');
		this.root=div;
		this.appendRoot(div);
		
		var html=[];
		html.push("<div class='native_title'>原生手机功能</div><div class='native_bnt'><a functionid='"+this.app.cFuncid+"' class='inter_goback' href='javascript:void(0);'>返回</a></div>");
		$(this.root).append(html.join(''));
		this.bindEvents();
	},
	bindEvents:function(){
		var obj=this;
				if(this.functionid!=''){
			$(this.root).bind('mousedown',function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).addClass("selectedComponent");
				$(obj.root).addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				var $this = $(this), t = $this.offset();
//				initComponentToolbar();
//				$("#runtime_component_toolbar").appendTo($this);
//				$("#runtime_component_toolbar").attr("cindex",obj.index).css("margin-top",(0-$this.height())+"px").show();
				
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("a.inter_goback").bind('click',function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}

//
var GoodsDetailComponentIOS=function(){
	GoodsDetailComponent.call(this);
};
GoodsDetailComponentIOS.prototype=new GoodsDetailComponent();
GoodsDetailComponentIOS.prototype.constructor=GoodsDetailComponent;
//
var GoodsDetailComponentAndroid=function(){
	GoodsDetailComponent.call(this);
};
GoodsDetailComponentAndroid.prototype=new GoodsDetailComponent();
GoodsDetailComponentAndroid.prototype.constructor=GoodsDetailComponent;

//菜单控件
var FunctionBarComponent=function(){
	this.code="component_functionbar";
	this.defaultdata=[{
	}];
	this.app=null;
	this.functionid=null;
	this.index=0;
	this.root=null;
	this.fac=null;
	this.childs=[];
	this.area="title";
	this.ComponentSelected=function(){};
	this.BeforeAddComponent=function(){};
	this.attributes={};//属性列表
	this.p=null;//父容器
	this.validateAttr=function(){
		var bool=true;
		if(typeof(this.attributes.position)=='undefined' || this.attributes.position=='' || ("bottom").indexOf(this.attributes.position)<=-1){
			bool=false;
			this.fac.writeLog({
				returncode:COMPONENTERRORCODE,
				appid:this.app.appid,
				functionid:this.functionid,
				code:this.code,
				index:this.index,
				msg:'菜单组件的位置属性设置有误'
			});
			this.goError('菜单组件的位置属性设置有误');
		}
//		if(typeof(this.attributes.menuitems)=='undefined' || this.attributes.menuitems=='' || typeof(this.attributes.menuitems.length)=='undefined' || this.attributes.menuitems.length==0){
//			bool=false;
//			this.fac.writeLog({
//				returncode:COMPONENTERRORCODE,
//				appid:this.app.appid,
//				functionid:this.functionid,
//				code:this.code,
//				index:this.index,
//				msg:'菜单组件的菜单列表属性设置有误'
//			});
//			this.goError('菜单组件的菜单列表属性设置有误');
//		}
		return bool;
	};
}
FunctionBarComponent.prototype={
	init:function(d){
		if(d.attributes)
			this.attributes=d.attributes;
		if(d.p)
			this.p=d.p;
		if(d.fac)
			this.fac=d.fac;
		if(d.functionid)
			this.functionid=d.functionid;
		if(d.ComponentSelected)
			this.ComponentSelected=d.ComponentSelected;
		if(d.BeforeAddComponent)
			this.BeforeAddComponent=d.BeforeAddComponent;
		if(d.index)
			this.index=d.index;
		if(d.app)
			this.app=d.app;
		if(!this.validateAttr()){
			return;
		}
		this.load();
	},
	getDefaultXml:function(){
		return '<functionbar bgcolor="#CCC" color="#FFF" position="bottom"><items></items></functionbar>';
	},
	getPath:function(cindex){
		return this.index.toString()+"-"+cindex.toString();
	},
	parsePath:function(){
		var paths=this.index.toString().split('-');
		return parseInt(paths[paths.length-1]);
	},
	appendRoot:function(el){
		$(el).attr("cindex",this.parsePath());
		$(el).attr("aindex",this.index);
		$(el).attr("functionid",this.functionid);
		if(this.index.toString().indexOf("-")>-1)
			$(el).addClass("component_child");
		if(this.parsePath()>1){
			for(var i=1;i<this.parsePath();i++){
				var $before=$(this.p).children(".component[cindex='"+(this.parsePath()-i)+"']");
				if($before.length>0){
					$before.after(el);
					return;
				}
			}
			$(this.p).prepend(el);
		}
		else
			$(this.p).prepend(el);
	},
	reload:function(index,attributes){
		if(this.index==index){
			this.attributes=attributes;
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
			this.load();
			//$(this.root).find("ul").addClass("selectedComponent");
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].p=$(this.p).find(".component[aindex='"+this.childs[i].index+"']").parent()[0];
					this.childs[i].reload(index,attributes);
					break;
				}
			}
		}
	},
	remove:function(index){
		if(this.index==index){
			$(this.root).remove();
			$(".selectedComponent").parent().remove();
		}
		else{
			for(var i=0;i<this.childs.length;i++){
				if(index.toString().indexOf(this.childs[i].index)>-1){
					this.childs[i].remove(index);
					break;
				}
			}
		}
	},
	goError:function(msg){
		if($(".component[aindex='"+this.index.toString()+"'][functionid='"+this.functionid+"']").length==0)return;
		if(this.root)
			$(this.root).remove();
		var div=document.createElement('div');
		div.setAttribute('class','component component_error');
		this.root=div;
		this.appendRoot(div);
		$(this.root).append("<p>"+msg+"</p>");
		this.bindEvents();
	},
	load:function(){
		if(this.attributes.position==null || this.attributes.position=="" || this.attributes.position=="bottom"){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_functionbar_bottom');
			this.root=div;
			this.appendRoot(div);			
			
			var json=this.attributes.items;
			var color = this.attributes.color,ul_style = "";
			var _width = $(".runtimescreen").css("width");
			ul_style += "width:" + _width+";";
			if ( color !=null && color!="")
			   ul_style += "color:"+color+";";
			color = this.attributes.bgcolor;
			if ( color !=null && color!="")
			   ul_style += "background-color:"+color+";";
			ul_style = ul_style!="" ? " style='"+ul_style+"'" :"";
			var html=[];
			html.push("<ul class='functionbar_ul'"+ul_style+">");
			var widthstyle = "",widthbyname = "";
			if ( json.length>0){
				_width = _width.replace("px","");
				var len = Math.floor(_width/json.length);
			  widthstyle = " style='width:"+ len +"px;'";
			  widthbyname=" style='width:"+ (len-28)+"px;'";
			}
			for(var i=0;i<json.length;i++){
				html.push("<li functionid='"+json[i].functionid.text+"' class='bar_i_uu'" + widthstyle + ">");
				if(json[i].arrangement=="uu"){					
					if ( json[i].icon!=null && json[i].icon!=""){
				    html.push("<div class='bar_i_icon_area'><img src='"+json[i].icon+"' class='bar_i_icon_uu'/></div>" +
						          "<span class='bar_i_name_uu'>"+json[i].text+"</span></li>");
					}
					else
					  html.push("<span class='bar_i_name_lr'"+widthbyname+">"+json[i].text+"</span></li>");
				}
				else if(json[i].arrangement=="lr"){
					if (json[i].icon!=null && json[i].icon!="")
						 html.push("<img src='"+json[i].icon+"' class='bar_i_icon_lr'/>" +
						           "<span class='bar_i_name_lr'"+widthbyname+">"+json[i].text+"</span></li>");
					else
						html.push("<span class='bar_i_name_lr'"+widthbyname+">"+json[i].text+"</span></li>");
				}
			}
			html.push("</ul>");			
			$(this.root).append(html.join(''));
			this.bindEvents();
		}
		else if(this.attributes.position=="relative"){
			var div=document.createElement('div');
			div.setAttribute('class','component cp_functionbar_relative');
			this.root=div;
			this.appendRoot(div);
			
			var html=[];
			html.push("<ul class='functionbar_ul'>");
			var json=this.attributes.items;
			for(var i=0;i<json.length;i++){
				if(json[i].arrangement=="uu"){
					html.push("<li functionid='"+json[i].functionid.text+"' class='bar_i_uu'>"+((json[i].icon=="" || json[i].icon==null)?"":("<img src='"+json[i].icon+"' class='bar_i_icon_uu'/>"))+"<div class='bar_i_name_uu'>"+json[i].text+"</div></li>");
				}
				else if(json[i].arrangement=="lr"){
					html.push("<li functionid='"+json[i].functionid.text+"' class='bar_i_lr'>"+((json[i].icon=="" || json[i].icon==null)?"":("<img src='"+json[i].icon+"' class='bar_i_icon_lr'/>"))+"<div class='bar_i_name_lr'>"+json[i].text+"</div></li>");
				}
			}
			html.push("</ul>");
			$(this.root).append(html.join(''));
			this.bindEvents();
		}
	},
	bindEvents:function(){
		var obj=this;
		if(this.functionid!=''){
			$(this.root).bind("mousedown",function(event){
				$(".interface .selectedComponent").removeClass("selectedComponent");
				$(this).siblings("ul").addClass("selectedComponent");
				obj.ComponentSelected({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					attrs:obj.attributes,
					event:event,
					obj:obj
				});
				$(this).siblings("ul").slideDown(150);
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseover',function(event){
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseout',function(event){
				if(!checkHover(event,this)) return;
//				if(event.button!=1){
//					$("#runtime_component_toolbar").appendTo($(document.body));
//					$("#runtime_component_toolbar").hide();
//				}
				stopBubble(event||window.event);
			});
			$(this.root).bind('mouseup',function(event){
				obj.BeforeAddComponent({
					functionid:obj.functionid,
					index:obj.index,
					code:obj.code,
					name:obj.name,
					area:obj.area
				});
			});
		}
		$(this.root).find("li").bind("click",function(){
			obj.app.loadInterObj($(this).attr('functionid'));
		});
	}
}
//
var FunctionBarComponentIOS=function(){
	FunctionBarComponent.call(this);
};
FunctionBarComponentIOS.prototype=new FunctionBarComponent();
FunctionBarComponentIOS.prototype.constructor=FunctionBarComponent;
//
var FunctionBarComponentAndroid=function(){
	FunctionBarComponent.call(this);
};
FunctionBarComponentAndroid.prototype=new FunctionBarComponent();
FunctionBarComponentAndroid.prototype.constructor=FunctionBarComponent;