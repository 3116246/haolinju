var screenWidth = window.screen.width;
var screenHeight = window.screen.height;
var isIE = true;
if(document.all==null)
   isIE=false;
var Util={ch_res : /[^\x00-\xff]/g,isIE:isIE};
var documentWidth = 0;
var documentHeight = 0;
var loadFunc = null;
//获取文档的真实宽度和高度
function _document_init_()
{
   documentWidth = !isIE?document.documentElement.clientWidth:document.documentElement.offsetWidth ;
   documentHeight = !isIE?document.documentElement.clientHeight:document.documentElement.offsetHeight ;
}
if(isIE){
    window.attachEvent("onload",_document_init_);
}else{
    window.addEventListener("load",_document_init_(), false);
}
if(!isIE){ //firefox innerText define	
  HTMLElement.prototype.__defineGetter__( "innerText",
  function(){
	  var anyString = "";
	  var childS = this.childNodes;
		  for(var i=0; i <childS.length; i++) {
		  if(childS[i].nodeType==1)
		  anyString += childS[i].tagName=="BR" ? '\n' : childS[i].innerText;
		  else if(childS[i].nodeType==3)
		  anyString += childS[i].nodeValue;
		  }
	  return anyString;
	  }
  );
  HTMLElement.prototype.__defineSetter__( "innerText",
	  function(sText){
	  this.textContent=sText;
	  }
  ); 
  HTMLElement.prototype.__defineGetter__("outerHTML",function() 
    { 
        var a=this.attributes, str="<"+this.tagName, i=0;
        for(;i<a.length;i++) 
        if(a[i].specified) 
            str+=" "+a[i].name+'="'+a[i].value+'"'; 
        return str+">"+this.innerHTML+"</"+this.tagName+">"; 
    }); 
  HTMLElement.prototype.__defineSetter__("outerHTML",function(s) 
    { 
        var r = this.ownerDocument.createRange(); 
        r.setStartBefore(this); 
        var df = r.createContextualFragment(s); 
        this.parentNode.replaceChild(df, this); 
        return s; 
    }); 
}
   
function isEmail(str){
       var reg = /^([\.|a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
       return reg.test(str);
}

function isMobile(str){
       var reg = /^1\d{10}$/;
       return reg.test(str);	
}

//替换特殊字符串
function replace(v)
{
	if(v==null||v=="") return v;
	var str =v.replace(/&nbsp;/g," ")
	            .replace(/&/g,"&amp;")
	            .replace(/</g,"&lt;")
	            .replace(/>/g,"&gt;")
	            .replace(/=/g,"&eql;")
	            .replace(/\r\n/g,"<BR>")
	            .replace(/,/g,"&cma;");  
	return str;
}

//替换特殊字符串
function replaceDefault(v)
{
	if(v==null||v=="") return v;
	var str =v.replace(/&nbsp;/g," ")
	            .replace(/&amp;/g,"&")
	            .replace(/&lt;/g,"<")
	            .replace(/&gt;/g,">")
	            .replace(/&eql;/g,"=")
	            .replace(/&cma;/g,",")
	            .replace(/<BR>/g,"\r\n")
	            ;  
	return str;
}

//为空字段的校验。
//cols:要校验的列列表。接受一维数组对象
//captions:提示信息列表。接受一维数组对象
//返回布尔型。通过或者未通过.
//使用字符串做参数实例:
/*
   checkNull("billCode,dfgf","字段billCode不能为空!,''");
*/
//使用数组做参数的实例：
/*
   checkNull(new Array('billCode'),new Array('billCode not null!'));
*/

function checkNull(cols,captions)
{
   var tempCols = new Array();
   var tempCaptions = new Array();

   if(typeof(cols)=="string")
   {
      tempCols = cols.split(",");
   }
   else
   {
       tempCols = cols;
   }
   if(typeof(captions)=="string")
   {
      tempCaptions = captions.split(",");
   }
   else
   {
      tempCaptions = captions;
   }
   if(tempCols.length!=tempCaptions.length)
   {
     alert("校验列数和提示项数不匹配!");
      return false;
   }
   var i=0;j=0;
   var tempValue;
   var control;
   for(i=0;i<tempCols.length;i++)
   {
      if(typeof(tempCols[i])=="string")
      {
         control = document.getElementById(tempCols[i]);
      }
      else
      {
         control = tempCols[i];
      }
      if(control == null)
      {
         alert("没找到控件" + tempCols[i]);
         return false;
      }
      tempValue = getControlValue(control);
      if(trim(tempValue)=="")
      {
         if(tempCaptions[i]!="")
         {
            if(document.getElementById("LbMsg")==null) alert("请输入["+tempCaptions[i]+"]的值，必填项不能为空!");
            else setControlValue("LbMsg","请输入:"+tempCaptions[i]);
            control.value = "";
            if(control.type!="hidden" && control.disabled==false && control.style.display!="none")
            {
               control.focus();
            }
         }
         return false;
      }
   }
   return true;
}

function setControlValue(cotrl,tempValue)
{
   var control=cotrl;
   if(typeof(cotrl)=="string")
   {
   	   control = document.getElementById(cotrl);
   }	
   if(control==null) return;
   if(control.type!=""&& "text,password,hidden,textarea".indexOf(control.type)>-1)
   {
       control.value  = tempValue;
   }
   else if(control.type!=""&& "select,select-one".indexOf(control.type)>-1)
   {
      for(var i=0 ;i<control.length; i++)
      {
         if(control.options[i].value==tempValue)
         {
            control.selectedIndex = i;
            break;
         }
      }
   }
   else if(control.type!=""&& "radio,checkbox".indexOf(control.type)>-1)
   {
      var vs = tempValue.split(",");
      var k =0;
      for(var j=0; j<vs.length; j++)
      {
      	 if((control.length==null || control.length<1) && control.value==vs[j])
      	 {
      	    control.checked = true;
      	    return;
      	 }
         for(var i=k ;i<control.length; i++)
         {
            if(control[i].value==vs[j])
            {
               control.checked = true;
               k = i;
               break;
            }
         }
      }
   }
   else if(control.length>1)
   {         
       var vs = tempValue.split(",");
       for(var i=0; i<control.length; i++)
       {
       	  control[i].checked = false; 
          if("radio,checkbox".indexOf(control[i].type)>-1 )
          {
               for(var j=0; j<vs.length; j++)
               {
                     if(control[i].value==vs[j]){
                        control[i].checked = true;
                        break;
                     } 
               }
          }
          else if("select,select-one,text,password,hidden,textarea".indexOf(control[i].type)>-1) 
             control[i].value = tempValue; 
       }
   }
   else 
   {
          control.innerHTML = tempValue;
   }
}

function getControlValue(cotrl)
{  
   var tempValue="";
   var control = cotrl;
   if(typeof(cotrl)=="string")
   {
   	   control = document.getElementById(cotrl);
   }
   if(control==null) return "";   
   if("text,password,hidden,textarea,file".indexOf(control.type)>-1)
   {
      tempValue = control.value;
   }
   else if("select,select-one".indexOf(control.type)>-1)
   {
      if(control.selectedIndex==-1)
      {
         tempValue = "";
      }
      else
      {
         tempValue = control.options[control.selectedIndex].value;
      }
   }
   else if("radio,checkbox".indexOf(control.type)>-1 && control.checked)
   {
   	     return control.value;
   }
   else if(control.length>1)
   {
   	   var vs = [];
       for(var i=0; i<control.length; i++)
       {
          if(control[i].type=="radio" && control[i].checked)
         {
               vs.push( control[i].value);
               break;
         }
         else if(control[i].type=="checkbox" && control[i].checked)
         {
            vs.push(control[i].value);
         }
         else if("select,select-one,text,password,hidden,textarea".indexOf(control[i].type)>-1)
         {
            vs.push(control[i].value);
         }
       }
       tempValue = vs.join(",");
   }
   else
   {
        tempValue = control.innerText;
	    if(!isIE)
	    {
	    	if(tempValue.indexOf("..")>-1) return control.title||control.parentNode.title;
	    }      
   }   
   return tempValue;
}


/**************
验证身份证号码
**************/
function checkEmpID( idcard )
{
   if (idcard == "")
   {
      return true;
   }
   var resultStr;
   resultStr = checkIdcard( idcard );
   if ( resultStr !="验证通过!")
   {
      alert(resultStr);
      return false;
   }
   return true;
}
function checkIdcard(idcard){
var Errors=new Array(
"验证通过!",
"身份证号码位数不对!",
"身份证号码出生日期超出范围或含有非法字符!",
"身份证号码校验错误!",
"身份证地区非法!"
);
var area={11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",21:"辽宁",22:"吉林",23:"黑龙江",31:"上海",32:"江苏",33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",42:"湖北",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",51:"四川",52:"贵州",53:"云南",54:"西藏",61:"陕西",62:"甘肃",63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外"}
var idcard,Y,JYM;
var S,M;
var idcard_array = new Array();
idcard_array = idcard.split("");
//地区检验
if(area[parseInt(idcard.substr(0,2))]==null) return Errors[4];
//身份号码位数及格式检验
switch(idcard.length){
case 15:
if ( (parseInt(idcard.substr(6,2))+1900) % 4 == 0 || ((parseInt(idcard.substr(6,2))+1900) % 100 == 0 && (parseInt(idcard.substr(6,2))+1900) % 4 == 0 )){
ereg=/^[1-9][0-9]{5}[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))[0-9]{3}$/;//测试出生日期的合法性
} else {
ereg=/^[1-9][0-9]{5}[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|1[0-9]|2[0-8]))[0-9]{3}$/;//测试出生日期的合法性
}
if(ereg.test(idcard)) return Errors[0];
else return Errors[2];
break;
case 18:
//18位身份号码检测
//出生日期的合法性检查
//闰年月日:((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))
//平年月日:((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|1[0-9]|2[0-8]))
if ( parseInt(idcard.substr(6,4)) % 4 == 0 || (parseInt(idcard.substr(6,4)) % 100 == 0 && parseInt(idcard.substr(6,4))%4 == 0 )){
ereg=/^[1-9][0-9]{5}19[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))[0-9]{3}[0-9Xx]$/;//闰年出生日期的合法性正则表达式
} else {
ereg=/^[1-9][0-9]{5}19[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|1[0-9]|2[0-8]))[0-9]{3}[0-9Xx]$/;//平年出生日期的合法性正则表达式
}
if(ereg.test(idcard)){//测试出生日期的合法性
//计算校验位
S = (parseInt(idcard_array[0]) + parseInt(idcard_array[10])) * 7
+ (parseInt(idcard_array[1]) + parseInt(idcard_array[11])) * 9
+ (parseInt(idcard_array[2]) + parseInt(idcard_array[12])) * 10
+ (parseInt(idcard_array[3]) + parseInt(idcard_array[13])) * 5
+ (parseInt(idcard_array[4]) + parseInt(idcard_array[14])) * 8
+ (parseInt(idcard_array[5]) + parseInt(idcard_array[15])) * 4
+ (parseInt(idcard_array[6]) + parseInt(idcard_array[16])) * 2
+ parseInt(idcard_array[7]) * 1
+ parseInt(idcard_array[8]) * 6
+ parseInt(idcard_array[9]) * 3 ;
Y = S % 11;
M = "F";
JYM = "10X98765432";
M = JYM.substr(Y,1);//判断校验位
if(M == idcard_array[17]) return Errors[0]; //检测ID的校验位
else return Errors[3];
}
else return Errors[2];
break;
default:
return Errors[1];
break;
}
}

function insertChar( textObj , insertText )
{
   textObj.value =textObj.value+insertText;
}

function getClipboard() { 
   if (window.clipboardData) { 
      return(window.clipboardData.getData('text')); 
   } 
   else if (window.netscape) { 
      netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect'); 
      var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard); 
      if (!clip) return; 
      var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable); 
      if (!trans) return; 
      trans.addDataFlavor('text/unicode'); 
      clip.getData(trans,clip.kGlobalClipboard); 
      var str = new Object(); 
      var len = new Object(); 
      try { 
         trans.getTransferData('text/unicode',str,len); 
      } 
      catch(error) { 
         return null; 
      } 
      if (str) { 
         if (Components.interfaces.nsISupportsWString) str=str.value.QueryInterface(Components.interfaces.nsISupportsWString); 
         else if (Components.interfaces.nsISupportsString) str=str.value.QueryInterface(Components.interfaces.nsISupportsString); 
         else str = null; 
      } 
      if (str) { 
         return(str.data.substring(0,len.value / 2)); 
      } 
   } 
   return null; 
} 


function random()
{
	var today=new Date();
	seed=today.getTime();
	this.rnd=function() 
	{
	　　　　seed = (seed*9301+49297) % 233280;
	　　　　return seed/(233280.0);
	};
	this.rand=function(number) {
	　　　　return Math.ceil(this.rnd()*number);
	};
}
  
function HashMap(){    
	this.hashTable=new Array();    
	this.put = function (k,v)    
	{        
		if(this.hashTable==null)
           this.hashTable = new  Array(); 
        var vv =typeof(v)=="string"?v.replace(/\"/g,"&cemh"):v;
        //if(typeof(k)=="string" && isNaN(k))
        //   eval("this.hashTable."+k.replace(/[`|@|#|%|(|)|\[|\]|\\|:|;|\.]/g,"")+"=''");        
        this.hashTable[k] = vv;
    };
    this.get = function (k)
    {
        var resutl = this.hashTable[k];
        return typeof(resutl)=="string"?resutl.replace(/&cemh/g,"\""):resutl;
    };
    this.containsKey=function(k)
    {
        return this.hashTable[k];
    }
    this.keyString=function()
    {
    	var str = "";
        for(var i in this.hashTable)        
        {
           if(i=="toJSON" || this.hashTable[i]==null || this.hashTable==undefined) continue;
           str+= ","+i;
        }   
        return str.substring(1);     
    }
    this.valueString=function()
    {
    	var str = "";
        for(var i in this.hashTable)        
        {
           if(i=="toJSON" ||this.hashTable[i]==null || this.hashTable==undefined) continue;
           str+= ","+this.hashTable[i];
        }   
        return str.substring(1);     
    }    
    this.toString= function()
    {
    	var str = "";
        for(var i in this.hashTable)        
        {
           str+= ";{";
           for(var j in this.hashTable[i])
           {
           	str+= j+":\""+this.hashTable[i][j]+"\",";
           } 
           str+= "object:self";
           str+= "}";
        }   
        return str; 	
    }    
    this.count=function()
    {
        var cnt = 0;
        for(var i in this.hashTable)        
        {
           cnt++;
        }
        return cnt;
    }
    this.keySet=function()
    {
       return this.hashTable;
    }
}   
//对功能按钮的可编辑/可使用状态控制
var ToolButton={
   buttonEventPool:new HashMap(),
   setDisplay:function(id,status)
   {
   	   if(document.getElementById(id)==null) return;
   	   document.getElementById(id).style.display=status;
   	   if(document.getElementById(id+"_icon")==null) return;
       document.getElementById(id+"_icon").style.display=status;   	   	
   },
   setEnable:function(id,fontColor)
   {
       var ctl = document.getElementById(id),ctl_icon = document.getElementById(id+"_icon");
   	   if(ctl==null) return;
   	   ctl.disabled=false; 
   	   var k=this.buttonEventPool.containsKey(id);
   	   if(k!=null)
   	       ctl.onclick=k;
       if(ctl.type!="button")
       {   	       
   	       var oc = fontColor||this.getOldColor(ctl);
   	       this.setCtl(ctl,oc,"pointer");
   	   }
   	   if(ctl_icon==null) return;
       ctl_icon.disabled=false;
       //获取存储的原始图标路径
       var ip = this.getOldImgPath(ctl_icon);
       //判断原始图标路径是否与当前图标路径相同
       this.setCtl(ctl_icon,(ip==ctl_icon.src?oc:ip),"pointer");
   },
   setDisable:function(id,icon)
   {
   	   var ctl = document.getElementById(id),ctl_icon = document.getElementById(id+"_icon");
   	   if(ctl==null) return;
   	   ctl.disabled=true; 
   	   if(ctl.onclick!=null)
   	      this.buttonEventPool.put(id,ctl.onclick);
   	   ctl.onclick=null;
   	   if(ctl.type!="button")
   	   {   	   
	   	   var oc = ctl.style!=null?ctl.style.color:"#000000";
	   	   oc = oc==""?"#000000":oc;
	   	   ctl.setAttribute("oldColor",oc);
	   	   this.setCtl(ctl,"#c0c0c0","default");
   	   }
   	   if(ctl_icon==null) return;
       ctl_icon.disabled=true;
       if(icon!=null)
       {
           //如果指定了无效时的图标，则使用指定图标替换原有图标
	       var img = this.getOldImgPath(ctl_icon);
	       ctl_icon.setAttribute("oldImgPath",img);
	       this.setCtlIcon(ctl_icon,icon,"default");
       }
       else
       {
           this.setCtl(ctl_icon,"#c0c0c0","default");
       }
   },
   getOldColor:function(c)
   {
   	   var oldColor = "#000000";
   	   if(c.getAttribute("oldColor")!=null && c.getAttribute("oldColor")!="")
   	      oldColor = c.getAttribute("oldColor");
   	   return oldColor; 
   },
   getOldImgPath:function(c)
   {
   	   var oldPath = c.src;
   	   if(c.getAttribute("oldImgPath")!=null && c.getAttribute("oldImgPath")!="")
   	      oldPath = c.getAttribute("oldImgPath");
   	   return oldPath; 
   },   
   setCtl:function(c,c1,p)
   {
   	   c.style.color = c1;
   	   c.style.cursor=p;      
   },
   setCtlIcon:function(c,c1,p)
   {
       var _src = c.src;
       _src = _src.substring(0,_src.lastIndexOf("/")+1)+c1;
   	   c.src = _src;
   	   c.style.cursor=p;      
   }
}
//兼容IE的FF的className设置
var ClassStyle={
    isIE:(document.all!=null)?true:false,
    setClass:function(ctrol,className)
    {
        if(this.isIE)
           ctrol.className=className;
        else
           ctrol.setAttribute("class",className);
    },
    getClass:function(ctrol)
    {
        if(this.isIE)
           return ctrol.className;
        else
           return ctrol.getAttribute("class");       
    }
};
//类型自动适配。将字符串表示类型转换成基本类型，如"true",1,"1"等转换成true
var Type={
   toBoolean:function(boolStr)
   {
      if(boolStr==null) return false;
      var constr = boolStr.constructor;
      if(constr==Boolean) return boolStr;
      if(constr==Number && boolStr==0) 
          return false;
      if(constr==String && (boolStr.toLowerCase()=="false"||boolStr=="0"))
         return false;
      else 
         return true;
   },
   toDefaultBoolean:function(boolStr,defaultvalue)
   {
      if(boolStr==null) return defaultvalue;
      return Type.toBoolean(boolStr);
   },   
   toString:function(para)
   {
       if(para==null) return "";
       var constr = para.constructor;
       return (constr!=String)?para+"":para;
   },
   toString:function(para,defaultvalue)
   {
       if(para==null) return defaultvalue;
       var constr = para.constructor;
       return (constr!=String)?para+"":para;
   },   
   toNumber:function(para)
   {
       if(para==null) return 0;
       var constr = para.constructor;
       if(constr==Boolean)
       {
           return para?1:0;
       }
       if(constr==String)
           return para*1;
       return para;
   },
   toDefaultNumber:function(para,defaultvalue)
   {
       if(para==null) return defaultvalue;
       return Type.toNumber(para);
   }
};

var City={
    	province:{"11":"北京市", "12":"天津市", "13":"河北省", "14":"山西省", "15":"内蒙古自治区", "21":"辽宁省", "22":"吉林省", "23":"黑龙江省", "31":"上海市", "32":"江苏省", "33":"浙江省", "34":"安徽省", "35":"福建省", "36":"江西省", "37":"山东省", "41":"河南省", "42":"湖北省", "43":"湖南省", "44":"广东省", "45":"广西壮族自治区", "46":"海南省", "50":"重庆市", "51":"四川省", "52":"贵州省", "53":"云南省", "54":"西藏自治区", "61":"陕西省", "62":"甘肃省", "63":"青海省", "64":"宁夏回族自治区", "65":"新疆维吾尔自治区", "71":"台湾省", "81":"香港特别行政区", "82":"澳门特别行政区"},
      citys:{"11":{"110100":"北京"}
						 ,"12":{"120100":"天津"}
						 ,"13":{"130101":"石家庄","130201":"唐山","130301":"秦皇岛","130701":"张家口","130801":"承德","131001":"廊坊","130401":"邯郸","130501":"邢台","130601":"保定","130901":"沧州","133001":"衡水"}
						 ,"14":{"140101":"太原","140201":"大同","140301":"阳泉","140501":"晋城","140601":"朔州","142201":"忻州","142331":"离石","142401":"榆次","142601":"临汾","142701":"运城","140401":"长治"}
						 ,"15":{"150101":"呼和浩特","150201":"包头","150301":"乌海","152601":"集宁","152701":"东胜","152801":"临河","152921":"阿拉善左旗","150401":"赤峰","152301":"通辽","152502":"锡林浩特","152101":"海拉尔","152201":"乌兰浩特"}
						 ,"21":{"210101":"沈阳","210201":"大连","210301":"鞍山","211001":"辽阳","210401":"抚顺","210501":"本溪","210701":"锦州","210801":"营口","210901":"阜新","211101":"盘锦","211201":"铁岭","211301":"朝阳","211401":"锦西","210601":"丹东"}
						 ,"22":{"220101":"长春","220201":"吉林","220301":"四平","220401":"辽源","220601":"浑江","222301":"白城","222401":"延吉","220501":"通化"}
						 ,"23":{"230101":"哈尔滨","230301":"鸡西","230401":"鹤岗","230501":"双鸭山","230701":"伊春","230801":"佳木斯","230901":"七台河","231001":"牡丹江","232301":"绥化","230201":"齐齐哈尔","230601":"大庆","232601":"黑河","232700":"加格达奇"}
						 ,"31":{"310100":"上海"}
						 ,"32":{"320101":"南京","320201":"无锡","320301":"徐州","320401":"常州","320501":"苏州","320600":"南通","320701":"连云港","320801":"淮阴","320901":"盐城","321001":"扬州","321101":"镇江"}
						 ,"33":{"330101":"杭州","330201":"宁波","330301":"温州","330401":"嘉兴","330501":"湖州","330601":"绍兴","330701":"金华","330801":"衢州","330901":"舟山","332501":"丽水","332602":"临海"}
						 ,"34":{"340101":"合肥","340201":"芜湖","340301":"蚌埠","340401":"淮南","340501":"马鞍山","340601":"淮北","340701":"铜陵","340801":"安庆","341001":"黄山","342101":"阜阳","342201":"宿州","342301":"滁州","342401":"六安","342501":"宣州","342601":"巢湖","342901":"贵池"}
						 ,"35":{"350101":"福州","350201":"厦门","350301":"莆田","350401":"三明","350501":"泉州","350601":"漳州","352101":"南平","352201":"宁德","352601":"龙岩"}
						 ,"36":{"360101":"南昌","360201":"景德镇","362101":"赣州","360301":"萍乡","360401":"九江","360501":"新余","360601":"鹰潭","362201":"宜春","362301":"上饶","362401":"吉安","362502":"临川"}
						 ,"37":{"370101":"济南","370201":"青岛","370301":"淄博","370401":"枣庄","370501":"东营","370601":"烟台","370701":"潍坊","370801":"济宁","370901":"泰安","371001":"威海","371100":"日照","372301":"滨州","372401":"德州","372501":"聊城","372801":"临沂","372901":"菏泽"}
						 ,"41":{"410101":"郑州","410201":"开封","410301":"洛阳","410401":"平顶山","410501":"安阳","410601":"鹤壁","410701":"新乡","410801":"焦作","410901":"濮阳","411001":"许昌","411101":"漯河","411201":"三门峡","412301":"商丘","412701":"周口","412801":"驻马店","412901":"南阳","413001":"信阳"}
						 ,"42":{"420101":"武汉","420201":"黄石","420301":"十堰","420400":"沙市","420501":"宜昌","420601":"襄樊","420701":"鄂州","420801":"荆门","422103":"黄州","422201":"孝感","422301":"咸宁","422421":"江陵","422801":"恩施"}
						 ,"43":{"430101":"长沙","430401":"衡阳","430501":"邵阳","432801":"郴州","432901":"永州","430801":"大庸","433001":"怀化","433101":"吉首","430201":"株洲","430301":"湘潭","430601":"岳阳","430701":"常德","432301":"益阳","432501":"娄底"}
						 ,"44":{"440101":"广州","440301":"深圳","441501":"汕尾","441301":"惠州","441601":"河源","440601":"佛山","441801":"清远","441901":"东莞","440401":"珠海","440701":"江门","441201":"肇庆","442001":"中山","440801":"湛江","440901":"茂名","440201":"韶关","440501":"汕头","441401":"梅州","441701":"阳江"}
						 ,"45":{"450101":"南宁","450401":"梧州","452501":"玉林","450301":"桂林","452601":"百色","452701":"河池","452802":"钦州","450201":"柳州","450501":"北海"}
						 ,"46":{"460100":"海口","460200":"三亚"}
						 ,"50":{"500100":"重庆","500239":"黔江土家族苗族自治县"}
						 ,"51":{"510101":"成都","513321":"康定","513101":"雅安","513229":"马尔康","510301":"自贡","512901":"南充","510501":"泸州","510601":"德阳","510701":"绵阳","510901":"遂宁","511001":"内江","511101":"乐山","512501":"宜宾","510801":"广元","513021":"达县","513401":"西昌","510401":"攀枝花"}
						 ,"52":{"520101":"贵阳","520200":"六盘水","522201":"铜仁","522501":"安顺","522601":"凯里","522701":"都匀","522301":"兴义","522421":"毕节","522101":"遵义"}
						 ,"53":{"530101":"昆明","530201":"东川","532201":"曲靖","532301":"楚雄","532401":"玉溪","532501":"个旧","532621":"文山","532721":"思茅","532101":"昭通","532821":"景洪","532901":"大理","533001":"保山","533121":"潞西","533221":"丽江纳西族自治县","533321":"泸水","533421":"中甸","533521":"临沧"}
						 ,"54":{"540101":"拉萨","542121":"昌都","542221":"乃东","542301":"日喀则","542200":"泽当镇","542600":"八一镇","542421":"那曲","542523":"噶尔","542621":"林芝"}
						 ,"61":{"610101":"西安","610201":"铜川","610301":"宝鸡","610401":"咸阳","612101":"渭南","612301":"汉中","612401":"安康","612501":"商州","612601":"延安","612701":"榆林"}
						 ,"62":{"620101":"兰州","620401":"白银","620301":"金昌","620501":"天水","622201":"张掖","622301":"武威","622421":"定西","622624":"成县","622701":"平凉","622801":"西峰","622901":"临夏","623027":"夏河","620201":"嘉峪关","622102":"酒泉"}
						 ,"63":{"630100":"西宁","632121":"平安","632221":"门源回族自治县","632321":"同仁","632521":"共和","632621":"玛沁","632721":"玉树","632802":"德令哈","640101":"银川","640201":"石嘴山","642101":"吴忠","642221":"固原"}
						 ,"65":{"650101":"乌鲁木齐","650201":"克拉玛依","652101":"吐鲁番","652201":"哈密","653201":"和田","652301":"昌吉","652701":"博乐","652801":"库尔勒","652901":"阿克苏","653001":"阿图什","653101":"喀什","654101":"伊宁"}
						 ,"71":{"710001":"台北","710002":"基隆","710020":"台南","710019":"高雄","710008":"台中"}
						 ,"82":{"820000":"澳门"}
						 ,"81":{"810000":"香港"}},
      BindProvince:function(ctl)
      {
      	  for (var key in this.province) {
      	  	   var name = this.province[key];
      	  	   var opt = document.createElement("option");
      	  	   ctl.options.add(opt);
      	  	   opt.text = name;
      	  	   opt.value= key;
      	  }
      	  ctl.onchange=function()
      	  {
      	      	if(City.city==null) return;     	      	
      	      	var key = this.value;
     	      	  City.FillCity(key);
      	  }
      },
      city:null,
      BindCity:function(ctl)
      {
      	  this.city=ctl;
      },
      FillCity:function(provinceCode)
      {
      	      	City.city.innerHTML="";
                var opt = document.createElement("option");
      	  	    City.city.options.add(opt);
      	  	    opt.text = "-市-";
      	  	    opt.value= "";       	
      	      	var cs = City.citys[provinceCode];
			      	  for (key in cs) {
			      	  	   var name = cs[key];
			      	  	   var opt = document.createElement("option");
			      	  	   City.city.options.add(opt);
			      	  	   opt.text = name;
			      	  	   opt.value= key;
			      	  }           	
      }   
};

function enterNext(obj,nextCtl)
{
    document.getElementById(obj).onkeypress= function(event)
		{
		      if((event||window.event).keyCode==13)
		      {
		            document.getElementById(nextCtl).focus();
				  }
		}	
}


function getDetailTableEvent()
{
 if(document.all)    return window.event;//如果是ie
 var func=getDetailTableEvent.caller;
        while(func!=null){
            var arg0=func.arguments[0];
            if(arg0){
            	if((arg0.constructor==Event || arg0.constructor ==MouseEvent) || (typeof(arg0)=="object" && arg0.preventDefault && arg0.stopPropagation))
            	{
            		return arg0;
            	}
            }
            func=func.caller;
        }
       return null;
}
function xmlescape(text) {
    text = text.replace(/\&/g, "&amp;");
    text = text.replace(/</g, "&lt;");
    text = text.replace(/>/g, "&gt;");
    text = text.replace(/'/g, "&apos;");
    text = text.replace(/"/g, "&quot;");
    return text;
}
function unxmlescape(text) {
    text = text.replace(/\&amp;/g, "&");
    text = text.replace(/\&lt;/g, "<");
    text = text.replace(/\&gt;/g, ">");
    text = text.replace(/\&apos;/g, "'");
    text = text.replace(/\&quot;/g, "\"");
    return text;
}

	String.prototype.nullTo=function()
	{
	  return (this==null||this=="null"||this=="undefined")?"":this;
	}
	
/**
* DES加密/解密
* @Copyright Copyright (c) 2006
* @author Guapo
* @see DESCore
*/

/*
* encrypt the string to string made up of hex
* return the encrypted string
*/
function strEnc(data,firstKey,secondKey,thirdKey){

 var leng = data.length;
 var encData = "";
 var firstKeyBt,secondKeyBt,thirdKeyBt,firstLength,secondLength,thirdLength;
 if(firstKey != null && firstKey != ""){    
   firstKeyBt = getKeyBytes(firstKey);
   firstLength = firstKeyBt.length;
 }
 if(secondKey != null && secondKey != ""){
   secondKeyBt = getKeyBytes(secondKey);
   secondLength = secondKeyBt.length;
 }
 if(thirdKey != null && thirdKey != ""){
   thirdKeyBt = getKeyBytes(thirdKey);
   thirdLength = thirdKeyBt.length;
 }  
 
 if(leng > 0){
   if(leng < 4){
     var bt = strToBt(data);      
     var encByte ;
     if(firstKey != null && firstKey !="" && secondKey != null && secondKey != "" && thirdKey != null && thirdKey != ""){
       var tempBt;
       var x,y,z;
       tempBt = bt;        
       for(x = 0;x < firstLength ;x ++){
         tempBt = enc(tempBt,firstKeyBt[x]);
       }
       for(y = 0;y < secondLength ;y ++){
         tempBt = enc(tempBt,secondKeyBt[y]);
       }
       for(z = 0;z < thirdLength ;z ++){
         tempBt = enc(tempBt,thirdKeyBt[z]);
       }        
       encByte = tempBt;        
     }else{
       if(firstKey != null && firstKey !="" && secondKey != null && secondKey != ""){
         var tempBt;
         var x,y;
         tempBt = bt;
         for(x = 0;x < firstLength ;x ++){
           tempBt = enc(tempBt,firstKeyBt[x]);
         }
         for(y = 0;y < secondLength ;y ++){
           tempBt = enc(tempBt,secondKeyBt[y]);
         }
         encByte = tempBt;
       }else{
         if(firstKey != null && firstKey !=""){            
           var tempBt;
           var x = 0;
           tempBt = bt;            
           for(x = 0;x < firstLength ;x ++){
             tempBt = enc(tempBt,firstKeyBt[x]);
           }
           encByte = tempBt;
         }
       }        
     }
     encData = bt64ToHex(encByte);
   }else{
     var iterator = parseInt(leng/4);
     var remainder = leng%4;
     var i=0;      
     for(i = 0;i < iterator;i++){
       var tempData = data.substring(i*4+0,i*4+4);
       var tempByte = strToBt(tempData);
       var encByte ;
       if(firstKey != null && firstKey !="" && secondKey != null && secondKey != "" && thirdKey != null && thirdKey != ""){
         var tempBt;
         var x,y,z;
         tempBt = tempByte;
         for(x = 0;x < firstLength ;x ++){
           tempBt = enc(tempBt,firstKeyBt[x]);
         }
         for(y = 0;y < secondLength ;y ++){
           tempBt = enc(tempBt,secondKeyBt[y]);
         }
         for(z = 0;z < thirdLength ;z ++){
           tempBt = enc(tempBt,thirdKeyBt[z]);
         }
         encByte = tempBt;
       }else{
         if(firstKey != null && firstKey !="" && secondKey != null && secondKey != ""){
           var tempBt;
           var x,y;
           tempBt = tempByte;
           for(x = 0;x < firstLength ;x ++){
             tempBt = enc(tempBt,firstKeyBt[x]);
           }
           for(y = 0;y < secondLength ;y ++){
             tempBt = enc(tempBt,secondKeyBt[y]);
           }
           encByte = tempBt;
         }else{
           if(firstKey != null && firstKey !=""){                      
             var tempBt;
             var x;
             tempBt = tempByte;
             for(x = 0;x < firstLength ;x ++){                
               tempBt = enc(tempBt,firstKeyBt[x]);
             }
             encByte = tempBt;              
           }
         }
       }
       encData += bt64ToHex(encByte);
     }      
     if(remainder > 0){
       var remainderData = data.substring(iterator*4+0,leng);
       var tempByte = strToBt(remainderData);
       var encByte ;
       if(firstKey != null && firstKey !="" && secondKey != null && secondKey != "" && thirdKey != null && thirdKey != ""){
         var tempBt;
         var x,y,z;
         tempBt = tempByte;
         for(x = 0;x < firstLength ;x ++){
           tempBt = enc(tempBt,firstKeyBt[x]);
         }
         for(y = 0;y < secondLength ;y ++){
           tempBt = enc(tempBt,secondKeyBt[y]);
         }
         for(z = 0;z < thirdLength ;z ++){
           tempBt = enc(tempBt,thirdKeyBt[z]);
         }
         encByte = tempBt;
       }else{
         if(firstKey != null && firstKey !="" && secondKey != null && secondKey != ""){
           var tempBt;
           var x,y;
           tempBt = tempByte;
           for(x = 0;x < firstLength ;x ++){
             tempBt = enc(tempBt,firstKeyBt[x]);
           }
           for(y = 0;y < secondLength ;y ++){
             tempBt = enc(tempBt,secondKeyBt[y]);
           }
           encByte = tempBt;
         }else{
           if(firstKey != null && firstKey !=""){            
             var tempBt;
             var x;
             tempBt = tempByte;
             for(x = 0;x < firstLength ;x ++){
               tempBt = enc(tempBt,firstKeyBt[x]);
             }
             encByte = tempBt;
           }
         }
       }
       encData += bt64ToHex(encByte);
     }                
   }
 }
 return encData;
}

/*
* decrypt the encrypted string to the original string 
*
* return  the original string  
*/
function strDec(data,firstKey,secondKey,thirdKey){
 var leng = data.length;
 var decStr = "";
 var firstKeyBt,secondKeyBt,thirdKeyBt,firstLength,secondLength,thirdLength;
 if(firstKey != null && firstKey != ""){    
   firstKeyBt = getKeyBytes(firstKey);
   firstLength = firstKeyBt.length;
 }
 if(secondKey != null && secondKey != ""){
   secondKeyBt = getKeyBytes(secondKey);
   secondLength = secondKeyBt.length;
 }
 if(thirdKey != null && thirdKey != ""){
   thirdKeyBt = getKeyBytes(thirdKey);
   thirdLength = thirdKeyBt.length;
 }
 
 var iterator = parseInt(leng/16);
 var i=0;  
 for(i = 0;i < iterator;i++){
   var tempData = data.substring(i*16+0,i*16+16);    
   var strByte = hexToBt64(tempData);    
   var intByte = new Array(64);
   var j = 0;
   for(j = 0;j < 64; j++){
     intByte[j] = parseInt(strByte.substring(j,j+1));
   }    
   var decByte;
   if(firstKey != null && firstKey !="" && secondKey != null && secondKey != "" && thirdKey != null && thirdKey != ""){
     var tempBt;
     var x,y,z;
     tempBt = intByte;
     for(x = thirdLength - 1;x >= 0;x --){
       tempBt = dec(tempBt,thirdKeyBt[x]);
     }
     for(y = secondLength - 1;y >= 0;y --){
       tempBt = dec(tempBt,secondKeyBt[y]);
     }
     for(z = firstLength - 1;z >= 0 ;z --){
       tempBt = dec(tempBt,firstKeyBt[z]);
     }
     decByte = tempBt;
   }else{
     if(firstKey != null && firstKey !="" && secondKey != null && secondKey != ""){
       var tempBt;
       var x,y,z;
       tempBt = intByte;
       for(x = secondLength - 1;x >= 0 ;x --){
         tempBt = dec(tempBt,secondKeyBt[x]);
       }
       for(y = firstLength - 1;y >= 0 ;y --){
         tempBt = dec(tempBt,firstKeyBt[y]);
       }
       decByte = tempBt;
     }else{
       if(firstKey != null && firstKey !=""){
         var tempBt;
         var x,y,z;
         tempBt = intByte;
         for(x = firstLength - 1;x >= 0 ;x --){
           tempBt = dec(tempBt,firstKeyBt[x]);
         }
         decByte = tempBt;
       }
     }
   }
   decStr += byteToString(decByte);
 }      
 return decStr;
}
/*
* chang the string into the bit array
* 
* return bit array(it's length % 64 = 0)
*/
function getKeyBytes(key){
 var keyBytes = new Array();
 var leng = key.length;
 var iterator = parseInt(leng/4);
 var remainder = leng%4;
 var i = 0;
 for(i = 0;i < iterator; i ++){
   keyBytes[i] = strToBt(key.substring(i*4+0,i*4+4));
 }
 if(remainder > 0){
   keyBytes[i] = strToBt(key.substring(i*4+0,leng));
 }    
 return keyBytes;
}

/*
* chang the string(it's length <= 4) into the bit array
* 
* return bit array(it's length = 64)
*/
function strToBt(str){  
 var leng = str.length;
 var bt = new Array(64);
 if(leng < 4){
   var i=0,j=0,p=0,q=0;
   for(i = 0;i<leng;i++){
     var k = str.charCodeAt(i);
     for(j=0;j<16;j++){      
       var pow=1,m=0;
       for(m=15;m>j;m--){
         pow *= 2;
       }        
       bt[16*i+j]=parseInt(k/pow)%2;
     }
   }
   for(p = leng;p<4;p++){
     var k = 0;
     for(q=0;q<16;q++){      
       var pow=1,m=0;
       for(m=15;m>q;m--){
         pow *= 2;
       }        
       bt[16*p+q]=parseInt(k/pow)%2;
     }
   }  
 }else{
   for(i = 0;i<4;i++){
     var k = str.charCodeAt(i);
     for(j=0;j<16;j++){      
       var pow=1;
       for(m=15;m>j;m--){
         pow *= 2;
       }        
       bt[16*i+j]=parseInt(k/pow)%2;
     }
   }  
 }
 return bt;
}

/*
* chang the bit(it's length = 4) into the hex
* 
* return hex
*/
function bt4ToHex(binary) {
 var hex;
 switch (binary) {
   case "0000" : hex = "0"; break;
   case "0001" : hex = "1"; break;
   case "0010" : hex = "2"; break;
   case "0011" : hex = "3"; break;
   case "0100" : hex = "4"; break;
   case "0101" : hex = "5"; break;
   case "0110" : hex = "6"; break;
   case "0111" : hex = "7"; break;
   case "1000" : hex = "8"; break;
   case "1001" : hex = "9"; break;
   case "1010" : hex = "A"; break;
   case "1011" : hex = "B"; break;
   case "1100" : hex = "C"; break;
   case "1101" : hex = "D"; break;
   case "1110" : hex = "E"; break;
   case "1111" : hex = "F"; break;
 }
 return hex;
}

/*
* chang the hex into the bit(it's length = 4)
* 
* return the bit(it's length = 4)
*/
function hexToBt4(hex) {
 var binary;
 switch (hex) {
   case "0" : binary = "0000"; break;
   case "1" : binary = "0001"; break;
   case "2" : binary = "0010"; break;
   case "3" : binary = "0011"; break;
   case "4" : binary = "0100"; break;
   case "5" : binary = "0101"; break;
   case "6" : binary = "0110"; break;
   case "7" : binary = "0111"; break;
   case "8" : binary = "1000"; break;
   case "9" : binary = "1001"; break;
   case "A" : binary = "1010"; break;
   case "B" : binary = "1011"; break;
   case "C" : binary = "1100"; break;
   case "D" : binary = "1101"; break;
   case "E" : binary = "1110"; break;
   case "F" : binary = "1111"; break;
 }
 return binary;
}

/*
* chang the bit(it's length = 64) into the string
* 
* return string
*/
function byteToString(byteData){
 var str="";
 for(i = 0;i<4;i++){
   var count=0;
   for(j=0;j<16;j++){        
     var pow=1;
     for(m=15;m>j;m--){
       pow*=2;
     }              
     count+=byteData[16*i+j]*pow;
   }        
   if(count != 0){
     str+=String.fromCharCode(count);
   }
 }
 return str;
}

function bt64ToHex(byteData){
 var hex = "";
 for(i = 0;i<16;i++){
   var bt = "";
   for(j=0;j<4;j++){    
     bt += byteData[i*4+j];
   }    
   hex+=bt4ToHex(bt);
 }
 return hex;
}

function hexToBt64(hex){
 var binary = "";
 for(i = 0;i<16;i++){
   binary+=hexToBt4(hex.substring(i,i+1));
 }
 return binary;
}

/*
* the 64 bit des core arithmetic
*/

function enc(dataByte,keyByte){  
 var keys = generateKeys(keyByte);    
 var ipByte   = initPermute(dataByte);  
 var ipLeft   = new Array(32);
 var ipRight  = new Array(32);
 var tempLeft = new Array(32);
 var i = 0,j = 0,k = 0,m = 0, n = 0;
 for(k = 0;k < 32;k ++){
   ipLeft[k] = ipByte[k];
   ipRight[k] = ipByte[32+k];
 }    
 for(i = 0;i < 16;i ++){
   for(j = 0;j < 32;j ++){
     tempLeft[j] = ipLeft[j];
     ipLeft[j] = ipRight[j];      
   }  
   var key = new Array(48);
   for(m = 0;m < 48;m ++){
     key[m] = keys[i][m];
   }
   var  tempRight = xor(pPermute(sBoxPermute(xor(expandPermute(ipRight),key))), tempLeft);      
   for(n = 0;n < 32;n ++){
     ipRight[n] = tempRight[n];
   }  
   
 }  
 
 
 var finalData =new Array(64);
 for(i = 0;i < 32;i ++){
   finalData[i] = ipRight[i];
   finalData[32+i] = ipLeft[i];
 }
 return finallyPermute(finalData);  
}

function dec(dataByte,keyByte){  
 var keys = generateKeys(keyByte);    
 var ipByte   = initPermute(dataByte);  
 var ipLeft   = new Array(32);
 var ipRight  = new Array(32);
 var tempLeft = new Array(32);
 var i = 0,j = 0,k = 0,m = 0, n = 0;
 for(k = 0;k < 32;k ++){
   ipLeft[k] = ipByte[k];
   ipRight[k] = ipByte[32+k];
 }  
 for(i = 15;i >= 0;i --){
   for(j = 0;j < 32;j ++){
     tempLeft[j] = ipLeft[j];
     ipLeft[j] = ipRight[j];      
   }  
   var key = new Array(48);
   for(m = 0;m < 48;m ++){
     key[m] = keys[i][m];
   }
   
   var  tempRight = xor(pPermute(sBoxPermute(xor(expandPermute(ipRight),key))), tempLeft);      
   for(n = 0;n < 32;n ++){
     ipRight[n] = tempRight[n];
   }  
 }  
 
 
 var finalData =new Array(64);
 for(i = 0;i < 32;i ++){
   finalData[i] = ipRight[i];
   finalData[32+i] = ipLeft[i];
 }
 return finallyPermute(finalData);  
}

function initPermute(originalData){
 var ipByte = new Array(64);
 for (i = 0, m = 1, n = 0; i < 4; i++, m += 2, n += 2) {
   for (j = 7, k = 0; j >= 0; j--, k++) {
     ipByte[i * 8 + k] = originalData[j * 8 + m];
     ipByte[i * 8 + k + 32] = originalData[j * 8 + n];
   }
 }    
 return ipByte;
}

function expandPermute(rightData){  
 var epByte = new Array(48);
 for (i = 0; i < 8; i++) {
   if (i == 0) {
     epByte[i * 6 + 0] = rightData[31];
   } else {
     epByte[i * 6 + 0] = rightData[i * 4 - 1];
   }
   epByte[i * 6 + 1] = rightData[i * 4 + 0];
   epByte[i * 6 + 2] = rightData[i * 4 + 1];
   epByte[i * 6 + 3] = rightData[i * 4 + 2];
   epByte[i * 6 + 4] = rightData[i * 4 + 3];
   if (i == 7) {
     epByte[i * 6 + 5] = rightData[0];
   } else {
     epByte[i * 6 + 5] = rightData[i * 4 + 4];
   }
 }      
 return epByte;
}

function xor(byteOne,byteTwo){  
 var xorByte = new Array(byteOne.length);
 for(i = 0;i < byteOne.length; i ++){      
   xorByte[i] = byteOne[i] ^ byteTwo[i];
 }  
 return xorByte;
}

function sBoxPermute(expandByte){
 
   var sBoxByte = new Array(32);
   var binary = "";
   var s1 = [
       [14, 4, 13, 1, 2, 15, 11, 8, 3, 10, 6, 12, 5, 9, 0, 7],
       [0, 15, 7, 4, 14, 2, 13, 1, 10, 6, 12, 11, 9, 5, 3, 8],
       [4, 1, 14, 8, 13, 6, 2, 11, 15, 12, 9, 7, 3, 10, 5, 0],
       [15, 12, 8, 2, 4, 9, 1, 7, 5, 11, 3, 14, 10, 0, 6, 13 ]];

       /* Table - s2 */
   var s2 = [
       [15, 1, 8, 14, 6, 11, 3, 4, 9, 7, 2, 13, 12, 0, 5, 10],
       [3, 13, 4, 7, 15, 2, 8, 14, 12, 0, 1, 10, 6, 9, 11, 5],
       [0, 14, 7, 11, 10, 4, 13, 1, 5, 8, 12, 6, 9, 3, 2, 15],
       [13, 8, 10, 1, 3, 15, 4, 2, 11, 6, 7, 12, 0, 5, 14, 9 ]];

       /* Table - s3 */
   var s3= [
       [10, 0, 9, 14, 6, 3, 15, 5, 1, 13, 12, 7, 11, 4, 2, 8],
       [13, 7, 0, 9, 3, 4, 6, 10, 2, 8, 5, 14, 12, 11, 15, 1],
       [13, 6, 4, 9, 8, 15, 3, 0, 11, 1, 2, 12, 5, 10, 14, 7],
       [1, 10, 13, 0, 6, 9, 8, 7, 4, 15, 14, 3, 11, 5, 2, 12 ]];
       /* Table - s4 */
   var s4 = [
       [7, 13, 14, 3, 0, 6, 9, 10, 1, 2, 8, 5, 11, 12, 4, 15],
       [13, 8, 11, 5, 6, 15, 0, 3, 4, 7, 2, 12, 1, 10, 14, 9],
       [10, 6, 9, 0, 12, 11, 7, 13, 15, 1, 3, 14, 5, 2, 8, 4],
       [3, 15, 0, 6, 10, 1, 13, 8, 9, 4, 5, 11, 12, 7, 2, 14 ]];

       /* Table - s5 */
   var s5 = [
       [2, 12, 4, 1, 7, 10, 11, 6, 8, 5, 3, 15, 13, 0, 14, 9],
       [14, 11, 2, 12, 4, 7, 13, 1, 5, 0, 15, 10, 3, 9, 8, 6],
       [4, 2, 1, 11, 10, 13, 7, 8, 15, 9, 12, 5, 6, 3, 0, 14],
       [11, 8, 12, 7, 1, 14, 2, 13, 6, 15, 0, 9, 10, 4, 5, 3 ]];

       /* Table - s6 */
   var s6 = [
       [12, 1, 10, 15, 9, 2, 6, 8, 0, 13, 3, 4, 14, 7, 5, 11],
       [10, 15, 4, 2, 7, 12, 9, 5, 6, 1, 13, 14, 0, 11, 3, 8],
       [9, 14, 15, 5, 2, 8, 12, 3, 7, 0, 4, 10, 1, 13, 11, 6],
       [4, 3, 2, 12, 9, 5, 15, 10, 11, 14, 1, 7, 6, 0, 8, 13 ]];

       /* Table - s7 */
   var s7 = [
       [4, 11, 2, 14, 15, 0, 8, 13, 3, 12, 9, 7, 5, 10, 6, 1],
       [13, 0, 11, 7, 4, 9, 1, 10, 14, 3, 5, 12, 2, 15, 8, 6],
       [1, 4, 11, 13, 12, 3, 7, 14, 10, 15, 6, 8, 0, 5, 9, 2],
       [6, 11, 13, 8, 1, 4, 10, 7, 9, 5, 0, 15, 14, 2, 3, 12]];

       /* Table - s8 */
   var s8 = [
       [13, 2, 8, 4, 6, 15, 11, 1, 10, 9, 3, 14, 5, 0, 12, 7],
       [1, 15, 13, 8, 10, 3, 7, 4, 12, 5, 6, 11, 0, 14, 9, 2],
       [7, 11, 4, 1, 9, 12, 14, 2, 0, 6, 10, 13, 15, 3, 5, 8],
       [2, 1, 14, 7, 4, 10, 8, 13, 15, 12, 9, 0, 3, 5, 6, 11]];
   
   for(m=0;m<8;m++){
   var i=0,j=0;
   i = expandByte[m*6+0]*2+expandByte[m*6+5];
   j = expandByte[m * 6 + 1] * 2 * 2 * 2 
     + expandByte[m * 6 + 2] * 2* 2 
     + expandByte[m * 6 + 3] * 2 
     + expandByte[m * 6 + 4];
   switch (m) {
     case 0 :
       binary = getBoxBinary(s1[i][j]);
       break;
     case 1 :
       binary = getBoxBinary(s2[i][j]);
       break;
     case 2 :
       binary = getBoxBinary(s3[i][j]);
       break;
     case 3 :
       binary = getBoxBinary(s4[i][j]);
       break;
     case 4 :
       binary = getBoxBinary(s5[i][j]);
       break;
     case 5 :
       binary = getBoxBinary(s6[i][j]);
       break;
     case 6 :
       binary = getBoxBinary(s7[i][j]);
       break;
     case 7 :
       binary = getBoxBinary(s8[i][j]);
       break;
   }      
   sBoxByte[m*4+0] = parseInt(binary.substring(0,1));
   sBoxByte[m*4+1] = parseInt(binary.substring(1,2));
   sBoxByte[m*4+2] = parseInt(binary.substring(2,3));
   sBoxByte[m*4+3] = parseInt(binary.substring(3,4));
 }
 return sBoxByte;
}

function pPermute(sBoxByte){
 var pBoxPermute = new Array(32);
 pBoxPermute[ 0] = sBoxByte[15]; 
 pBoxPermute[ 1] = sBoxByte[ 6]; 
 pBoxPermute[ 2] = sBoxByte[19]; 
 pBoxPermute[ 3] = sBoxByte[20]; 
 pBoxPermute[ 4] = sBoxByte[28]; 
 pBoxPermute[ 5] = sBoxByte[11]; 
 pBoxPermute[ 6] = sBoxByte[27]; 
 pBoxPermute[ 7] = sBoxByte[16]; 
 pBoxPermute[ 8] = sBoxByte[ 0]; 
 pBoxPermute[ 9] = sBoxByte[14]; 
 pBoxPermute[10] = sBoxByte[22]; 
 pBoxPermute[11] = sBoxByte[25]; 
 pBoxPermute[12] = sBoxByte[ 4]; 
 pBoxPermute[13] = sBoxByte[17]; 
 pBoxPermute[14] = sBoxByte[30]; 
 pBoxPermute[15] = sBoxByte[ 9]; 
 pBoxPermute[16] = sBoxByte[ 1]; 
 pBoxPermute[17] = sBoxByte[ 7]; 
 pBoxPermute[18] = sBoxByte[23]; 
 pBoxPermute[19] = sBoxByte[13]; 
 pBoxPermute[20] = sBoxByte[31]; 
 pBoxPermute[21] = sBoxByte[26]; 
 pBoxPermute[22] = sBoxByte[ 2]; 
 pBoxPermute[23] = sBoxByte[ 8]; 
 pBoxPermute[24] = sBoxByte[18]; 
 pBoxPermute[25] = sBoxByte[12]; 
 pBoxPermute[26] = sBoxByte[29]; 
 pBoxPermute[27] = sBoxByte[ 5]; 
 pBoxPermute[28] = sBoxByte[21]; 
 pBoxPermute[29] = sBoxByte[10]; 
 pBoxPermute[30] = sBoxByte[ 3]; 
 pBoxPermute[31] = sBoxByte[24];    
 return pBoxPermute;
}

function finallyPermute(endByte){    
 var fpByte = new Array(64);  
 fpByte[ 0] = endByte[39]; 
 fpByte[ 1] = endByte[ 7]; 
 fpByte[ 2] = endByte[47]; 
 fpByte[ 3] = endByte[15]; 
 fpByte[ 4] = endByte[55]; 
 fpByte[ 5] = endByte[23]; 
 fpByte[ 6] = endByte[63]; 
 fpByte[ 7] = endByte[31]; 
 fpByte[ 8] = endByte[38]; 
 fpByte[ 9] = endByte[ 6]; 
 fpByte[10] = endByte[46]; 
 fpByte[11] = endByte[14]; 
 fpByte[12] = endByte[54]; 
 fpByte[13] = endByte[22]; 
 fpByte[14] = endByte[62]; 
 fpByte[15] = endByte[30]; 
 fpByte[16] = endByte[37]; 
 fpByte[17] = endByte[ 5]; 
 fpByte[18] = endByte[45]; 
 fpByte[19] = endByte[13]; 
 fpByte[20] = endByte[53]; 
 fpByte[21] = endByte[21]; 
 fpByte[22] = endByte[61]; 
 fpByte[23] = endByte[29]; 
 fpByte[24] = endByte[36]; 
 fpByte[25] = endByte[ 4]; 
 fpByte[26] = endByte[44]; 
 fpByte[27] = endByte[12]; 
 fpByte[28] = endByte[52]; 
 fpByte[29] = endByte[20]; 
 fpByte[30] = endByte[60]; 
 fpByte[31] = endByte[28]; 
 fpByte[32] = endByte[35]; 
 fpByte[33] = endByte[ 3]; 
 fpByte[34] = endByte[43]; 
 fpByte[35] = endByte[11]; 
 fpByte[36] = endByte[51]; 
 fpByte[37] = endByte[19]; 
 fpByte[38] = endByte[59]; 
 fpByte[39] = endByte[27]; 
 fpByte[40] = endByte[34]; 
 fpByte[41] = endByte[ 2]; 
 fpByte[42] = endByte[42]; 
 fpByte[43] = endByte[10]; 
 fpByte[44] = endByte[50]; 
 fpByte[45] = endByte[18]; 
 fpByte[46] = endByte[58]; 
 fpByte[47] = endByte[26]; 
 fpByte[48] = endByte[33]; 
 fpByte[49] = endByte[ 1]; 
 fpByte[50] = endByte[41]; 
 fpByte[51] = endByte[ 9]; 
 fpByte[52] = endByte[49]; 
 fpByte[53] = endByte[17]; 
 fpByte[54] = endByte[57]; 
 fpByte[55] = endByte[25]; 
 fpByte[56] = endByte[32]; 
 fpByte[57] = endByte[ 0]; 
 fpByte[58] = endByte[40]; 
 fpByte[59] = endByte[ 8]; 
 fpByte[60] = endByte[48]; 
 fpByte[61] = endByte[16]; 
 fpByte[62] = endByte[56]; 
 fpByte[63] = endByte[24];
 return fpByte;
}

function getBoxBinary(i) {
 var binary = "";
 switch (i) {
   case 0 :binary = "0000";break;
   case 1 :binary = "0001";break;
   case 2 :binary = "0010";break;
   case 3 :binary = "0011";break;
   case 4 :binary = "0100";break;
   case 5 :binary = "0101";break;
   case 6 :binary = "0110";break;
   case 7 :binary = "0111";break;
   case 8 :binary = "1000";break;
   case 9 :binary = "1001";break;
   case 10 :binary = "1010";break;
   case 11 :binary = "1011";break;
   case 12 :binary = "1100";break;
   case 13 :binary = "1101";break;
   case 14 :binary = "1110";break;
   case 15 :binary = "1111";break;
 }
 return binary;
}
/*
* generate 16 keys for xor
*
*/
function generateKeys(keyByte){    
 var key   = new Array(56);
 var keys = new Array();  
 
 keys[ 0] = new Array();
 keys[ 1] = new Array();
 keys[ 2] = new Array();
 keys[ 3] = new Array();
 keys[ 4] = new Array();
 keys[ 5] = new Array();
 keys[ 6] = new Array();
 keys[ 7] = new Array();
 keys[ 8] = new Array();
 keys[ 9] = new Array();
 keys[10] = new Array();
 keys[11] = new Array();
 keys[12] = new Array();
 keys[13] = new Array();
 keys[14] = new Array();
 keys[15] = new Array();  
 var loop = [1,1,2,2,2,2,2,2,1,2,2,2,2,2,2,1];

 for(i=0;i<7;i++){
   for(j=0,k=7;j<8;j++,k--){
     key[i*8+j]=keyByte[8*k+i];
   }
 }    
 
 var i = 0;
 for(i = 0;i < 16;i ++){
   var tempLeft=0;
   var tempRight=0;
   for(j = 0; j < loop[i];j ++){          
     tempLeft = key[0];
     tempRight = key[28];
     for(k = 0;k < 27 ;k ++){
       key[k] = key[k+1];
       key[28+k] = key[29+k];
     }  
     key[27]=tempLeft;
     key[55]=tempRight;
   }
   var tempKey = new Array(48);
   tempKey[ 0] = key[13];
   tempKey[ 1] = key[16];
   tempKey[ 2] = key[10];
   tempKey[ 3] = key[23];
   tempKey[ 4] = key[ 0];
   tempKey[ 5] = key[ 4];
   tempKey[ 6] = key[ 2];
   tempKey[ 7] = key[27];
   tempKey[ 8] = key[14];
   tempKey[ 9] = key[ 5];
   tempKey[10] = key[20];
   tempKey[11] = key[ 9];
   tempKey[12] = key[22];
   tempKey[13] = key[18];
   tempKey[14] = key[11];
   tempKey[15] = key[ 3];
   tempKey[16] = key[25];
   tempKey[17] = key[ 7];
   tempKey[18] = key[15];
   tempKey[19] = key[ 6];
   tempKey[20] = key[26];
   tempKey[21] = key[19];
   tempKey[22] = key[12];
   tempKey[23] = key[ 1];
   tempKey[24] = key[40];
   tempKey[25] = key[51];
   tempKey[26] = key[30];
   tempKey[27] = key[36];
   tempKey[28] = key[46];
   tempKey[29] = key[54];
   tempKey[30] = key[29];
   tempKey[31] = key[39];
   tempKey[32] = key[50];
   tempKey[33] = key[44];
   tempKey[34] = key[32];
   tempKey[35] = key[47];
   tempKey[36] = key[43];
   tempKey[37] = key[48];
   tempKey[38] = key[38];
   tempKey[39] = key[55];
   tempKey[40] = key[33];
   tempKey[41] = key[52];
   tempKey[42] = key[45];
   tempKey[43] = key[41];
   tempKey[44] = key[49];
   tempKey[45] = key[35];
   tempKey[46] = key[28];
   tempKey[47] = key[31];
   switch(i){
     case 0: for(m=0;m < 48 ;m++){ keys[ 0][m] = tempKey[m]; } break;
     case 1: for(m=0;m < 48 ;m++){ keys[ 1][m] = tempKey[m]; } break;
     case 2: for(m=0;m < 48 ;m++){ keys[ 2][m] = tempKey[m]; } break;
     case 3: for(m=0;m < 48 ;m++){ keys[ 3][m] = tempKey[m]; } break;
     case 4: for(m=0;m < 48 ;m++){ keys[ 4][m] = tempKey[m]; } break;
     case 5: for(m=0;m < 48 ;m++){ keys[ 5][m] = tempKey[m]; } break;
     case 6: for(m=0;m < 48 ;m++){ keys[ 6][m] = tempKey[m]; } break;
     case 7: for(m=0;m < 48 ;m++){ keys[ 7][m] = tempKey[m]; } break;
     case 8: for(m=0;m < 48 ;m++){ keys[ 8][m] = tempKey[m]; } break;
     case 9: for(m=0;m < 48 ;m++){ keys[ 9][m] = tempKey[m]; } break;
     case 10: for(m=0;m < 48 ;m++){ keys[10][m] = tempKey[m]; } break;
     case 11: for(m=0;m < 48 ;m++){ keys[11][m] = tempKey[m]; } break;
     case 12: for(m=0;m < 48 ;m++){ keys[12][m] = tempKey[m]; } break;
     case 13: for(m=0;m < 48 ;m++){ keys[13][m] = tempKey[m]; } break;
     case 14: for(m=0;m < 48 ;m++){ keys[14][m] = tempKey[m]; } break;
     case 15: for(m=0;m < 48 ;m++){ keys[15][m] = tempKey[m]; } break;
   }
 }
 return keys;  
}