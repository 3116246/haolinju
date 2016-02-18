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
   
/*
 * Copyright (c) 2005, 
 * All rights reserved.
 *　
 * 文 件 名: Util.js
 * 版    本: V1.0  2007-7-20 下午01:56:07
 * 描    述: 相关WEB页面控制的公共JS方法
 * 备    注: 
 * 修改记录:
 * 修 改 人 - 修改日期 - 修改说明11
 * lli2      2008-7-22 从原人力资源系统框架中移到本系统中
 * lli2      2008-7-31 增加新功能函数selectedAll:根据指定列进行当前列的选择框全选
 ****************************************************************************/
//函数作用：检测文本框字符长度
//变量声明：widgetName：控件名；widgetLimitLength：控件限定长度；widgetSpec:控件说明文字
function checkStrLength(widgetName,widgetLimitLength,widgetSpec)
{
   var widgetValue=document.all(widgetName).value;
   //正则表达式说明：g参数代表全局匹配，中文编码位于x00至xff，正则表达作用在于替换掉文本框中的中文字符
   var widgetStrTrueLeng = widgetValue.replace(/[^\x00-\xff]/g,"~~").length;
   var overLength=widgetStrTrueLeng-widgetLimitLength;
   if(widgetStrTrueLeng>widgetLimitLength)
   {
      alert("【 "+widgetSpec+" 】长度不得超过"+widgetLimitLength+"个字符或者"+widgetLimitLength/2+"个汉字\n\n现已超出"+overLength+"个字符");
      document.all(widgetName).focus();
      return false;
   }
   else
   {
      return true;
   }
}



//日期比较
function compareDate(strDate1,steDate2)
{
   try
   {
      var tmp =strDate1.split("-");
   strDate1=new Date(tmp[1]+"-"+tmp[2]+"-"+tmp[0])
   var tmp =steDate2.split("-");
   steDate2=new Date(tmp[1]+"-"+tmp[2]+"-"+tmp[0])
      var ret = strDate1- steDate2
   return ret ;
   }
   catch(e)
   {
      return 0;
   }
}
//限制输入
//ctrl：要限制输入的控制对象

function inputCharInteger(ctrl)
{
   var v = ctrl.value;
   if(v!="")
   {
     var val = v.substring(0,1);
     if((val>='a'&&val<='z') || (val>='A'&&val<='Z'))
     {}
     else
     {
    alert("第一个字符必须是字母!");
        ctrl.value="";
    window.event.keyCode=0;
    return;
     }
        if((window.event.keyCode>=48 && window.event.keyCode<=57)||(window.event.keyCode>=65 && window.event.keyCode<=90)||(window.event.keyCode>=97
&& window.event.keyCode<=122))
     {
     }
     else
     {
        window.event.keyCode=0;
        return;
     }
   }
   else
   {
      inputChar(ctrl);
   }
   return;
}

function inputChar(ctrl)
{
      if((window.event.keyCode>=65 && window.event.keyCode<=90)||(window.event.keyCode>=97 && window.event.keyCode<=122))
   {
   }
   else
   {
      window.event.keyCode=0;
      return;
   }
   return true;
}

function inputInteger(ctrl)
{
   if(window.event.keyCode<48 || window.event.keyCode>57)
   {
   //排除负号
   if(window.event.keyCode!=45)
   {
      window.event.keyCode=0;
           return;
      }
      if(window.event.keyCode==45)
      {
     var ctrlValue = "";
     if(typeof(ctrl) == "string")
     {
        ctrlValue = ctrl;
     }
     else
     {
        if(ctrl.type==null)
        {
           ctrlValue = ctrl.innerText;
        }
        else
        {
           ctrlValue = ctrl.value;
        }
     }
         //判断是否已经输入了负号。只允许出现一个负号.且负号只能出现在第一个字符(未实现判断)
         if(ctrlValue.indexOf("-")>-1)
         {
             window.event.keyCode=0;
         return;
         }
     else if(ctrlValue.length>1)
     {
             window.event.keyCode=0;
         return;
     }
      }
   }
   return true;
}


function inputPlusInteger(ctrl)
{
   var event = getEvent();
   if(document.all!=null)
   {
	   if(event.keyCode<48 || event.keyCode>57)
	   {
	      event.keyCode=0;
	      return;
	   }
   }
   else
   {
	   if(event.charCode<48 || event.charCode>57)
	   {
	      return false;
	   }   
   }
   return true;
}

function inputDecimal (ctrl)
{
   if(window.event.keyCode<48 || window.event.keyCode>57)
   {
   //排除负号和小数点
   if(window.event.keyCode!=45&&window.event.keyCode!=46)
   {
      window.event.keyCode=0;
      return;
   }
   var ctrlValue = "";
   if(ctrl.type==null)
   {
      ctrlValue = ctrl.innerText;
   }
   else
   {
      ctrlValue = ctrl.value;
   }
   if(window.event.keyCode==46)
       {
          //判断是否已经输入了小数点。只允许出现一个小数点
          if(ctrlValue.indexOf(".")>-1)
          {
              window.event.keyCode=0;
          return;
          }
       }
       if(window.event.keyCode==45)
       {
          //判断是否已经输入了负号。只允许出现一个负号.且负号只级出现在第一个字符(未实现判断)
         if(ctrlValue.indexOf("-")>-1)
         {
            window.event.keyCode=0;
        return;
         }
     else if(ctrlValue.length>1)
     {
             window.event.keyCode=0;
         return;
     }
       }
   }
   return true;
}

// 允许输入负数
function inputPlusDecimalMinus (ctrl)
{
   if(window.event.keyCode<48 || window.event.keyCode>57)
   {

   //排除小数点, -号
   if(window.event.keyCode!=46 && window.event.keyCode!=45)
   {
      window.event.keyCode=0;
      return;
   }
   var ctrlValue = "";
   if(ctrl.type==null)
   {
      ctrlValue = ctrl.innerText;
   }
   else
   {
      ctrlValue = ctrl.value;
   }
        if(window.event.keyCode==46)
       {
          //判断是否已经输入了小数点。只允许出现一个小数点
          if(ctrlValue.indexOf(".")>-1)
          {
              window.event.keyCode=0;
          return;
          }
       }
   }
   return true;
}

// 不能输入负数
function inputPlusDecimal (ctrl)
{
   if(window.event.keyCode<48 || window.event.keyCode>57)
   {

   //排除小数点
   if(window.event.keyCode!=46 )
   {
      window.event.keyCode=0;
      return;
   }
   var ctrlValue = "";
   if(ctrl.type==null)
   {
      ctrlValue = ctrl.innerText;
   }
   else
   {
      ctrlValue = ctrl.value;
   }
        if(window.event.keyCode==46)
       {
          //判断是否已经输入了小数点。只允许出现一个小数点
          if(ctrlValue.indexOf(".")>-1)
          {
              window.event.keyCode=0;
          return;
          }
       }
   }
   return true;
}

//去除字符的前后空格

function trim(v)
{

   var s1 = v+"";
   if(s1!=null||s1.length>0)
   {

      while(s1.charAt(0)==" ")
      {
         s1=s1.substring(1,s1.length);
      }
      while(s1.charAt(s1.length-1)==" ")
      {
         s1=s1.substring(0,s1.length-1);
      }
   }
   return s1;
}

function replaceall(s,oldstring,newstring)
{

   var strTemp = s;

   if(strTemp==null||trim(strTemp)=="")
   {
      return "";
   }
   else
   {
      var newY=s.split(oldstring);
      if(newY.length<=1)
      {
         return s;
      }
     strTemp = "";
      for(var i=0; i<newY.length; i++)
      {
         strTemp += newY[i]+newstring;
         if(i==newY.length-2)
         {
            strTemp += newY[i+1];
            return strTemp;
         }
      }
      return strTemp;
   }
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

  
//将数据表示的小数点后,转换为中文显示.例如:0 整数 ;0.00 小数点后两位
function dotConvertCHS(numFieldName,chFieldName)
{
   //例如:0 整形  0.00 小数点后两位
   var strNumField = document.all(numFieldName);//数字字段
   var strChField = document.all(chFieldName);//中文描述
   if (strNumField && strChField)
   {
   strChField.value = "";
   var strFieldValue = strNumField.value;
   if (strFieldValue!="")
   {
      if (strFieldValue.indexOf(".")>=0 && strFieldValue.indexOf(".")<strFieldValue.length)
      {
      strFieldValue = strFieldValue.substring(strFieldValue.indexOf(".")+1,strFieldValue.length);
      var dotCount = strFieldValue.length;
      strChField.value = "小数点后" + numConvertCHS(dotCount) + "位";
      }else{
      strChField.value = "整数";
      }
   }
   }

}


//将阿拉伯数字转换为中文数字
function numConvertCHS (num)
{
   //适合100以下的数值转换，将阿拉伯数字转换为中文数字。
   var str = "";
   var num = Math.round(num);
   var upperNum = new Array("十","一","二","三","四","五","六","七","八","九");
   var value = num/10; //值
   var residual = num%10;//余数
   if ( value<1 )
   {
   //一位数字
   if (residual==2)
   {
      str = "两";
   }else{
      str = upperNum[residual];
   }
   }else{
   //两位数字
   if ( value>1 )
   {
      //小于20的数字
      if ( residual==0 )
      {
      str = upperNum[residual];
      }else{
      str = upperNum[0] + upperNum[residual];
      }
   }else{
      //大于20的数字
      if ( residual==0 )
      {
      str = upperNum[value] + upperNum[residual];
      }else{
      str = upperNum[value] + upperNum[0] + upperNum[residual];
      }
   }
   }
   return str;
}

function isEmail(str){
       var reg = /^([\.|a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
       return reg.test(str);
}

function isMobile(str){
       var reg = /^1\d{10}$/;
       return reg.test(str);	
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
   setEnable:function(id)
   {
       var ctl = document.getElementById(id),ctl_icon = document.getElementById(id+"_icon");
   	   if(ctl==null) return;
   	   ctl.disabled=false; 
   	   var k=this.buttonEventPool.containsKey(id);
   	   if(k!=null)
   	       ctl.onclick=k;
       if(ctl.type!="button")
       {   	       
   	       var oc = this.getOldColor(ctl);
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
	   	   this.setCtl(ctl,"#c0c0c0","none");
   	   }
   	   if(ctl_icon==null) return;
       ctl_icon.disabled=true;
       if(icon!=null)
       {
           //如果指定了无效时的图标，则使用指定图标替换原有图标
	       var img = this.getOldImgPath(ctl_icon);
	       ctl_icon.setAttribute("oldImgPath",img);
	       this.setCtlIcon(ctl_icon,icon,"none");
       }
       else
       {
           this.setCtl(ctl_icon,"#c0c0c0","none");
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

var Login={
    login:function(){},	
    out:function(){},
    sessionReg:function(){},
    cookie:{
        	set:function(name,value)//两个参数，一个是cookie的名子，一个是值
					{
						  this.del(name);
					    var Days = 30; //此 cookie 将被保存 30 天
					    var exp  = new Date();    //new Date("December 31, 9998");
					    exp.setTime(exp.getTime() + Days*24*60*60*1000);
					    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
					},
					get:function(name)//读取cookies函数        
					{
					    var arr = document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));
					     if(arr != null) return unescape(arr[2]); return null;					
					},
					del:function(name)//删除cookie
					{
					    var exp = new Date();
					    exp.setTime(exp.getTime() - 1);
					    var cval=this.get(name);
					    if(cval!=null) document.cookie= name + "="+cval+";expires="+exp.toGMTString();
					}
    }    
}
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


function getEvent()
{
 if(document.all)    return window.event;//如果是ie
 var func=getEvent.caller;
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
 function ajaxSubmit(urladd,parameters,callfunction)
   {        
	    var xmlr = null;
	    try{
	        xmlr=new ActiveXObject("Microsoft.XMLHTTP");
	    }
	    catch(e)
	    {
	        xmlr=new XMLHttpRequest();
	    }
		xmlr.open("post","../controller.yaws?method="+urladd,true);
		if(parameters!="")
		   parameters = parameters+"&Encoding=utf-8";
		else
		   parameters="Encoding=utf-8";
		var sid = (top.CUR_SID|| (typeof(CUR_SID)=="undefined"?null : CUR_SID));
		parameters = encodeURI(parameters+"&sid="+(sid==null?"":sid));//如果jsp页面用的非utf-8编码，则需要通过这句进行编码，否则中文会乱码
		//alert(parameters);
		xmlr.setRequestHeader("content-length",parameters.length); 
		xmlr.setRequestHeader("Content-type","application/x-www-form-urlencoded");//默认采用utf-8发送
		//xmlr.setRequestHeader("Content-type","text/html;charset=GBK");//get方式用这句设请求头
		xmlr.send(parameters);
		xmlr.onreadystatechange = function(){
			if(xmlr.readyState==4){
				if(xmlr.status==200){
					var result = (xmlr.responseText); 
					  var data = eval("("+result.replace(/<br>/g,"\\r\\n")+")");
					  if(data.succeed==false && data.msg=="ERR9999")
					  {
					      var p=Login.cookie.get("fafa.yaws.u.p");
								if(p!=null && p!="null")
								{
									    CUR_SID=null;
									    Login.cookie.del("fafa.yaws");
										  //自动登录
									    ajaxSubmit("login:login","password="+p+"&account="+CUR_UID,
											    function(data){
													    if(!data.succeed)
													    {
													        top.window.location="/login.yaws";
													        return;
													    }
													    CUR_SID=data.data.sid;
				                      Login.cookie.set("fafa.yaws.u",data.data.uid);
				                      Login.cookie.set("fafa.yaws",data.data.sid);
				                      top.window.location="/enInfo/index.yaws?CUR_SID="+CUR_SID+"&CUR_UID="+CUR_UID;
									    });
									    return;
								}
					  	  top.window.location="/login.yaws";
					  		return;
					  }
					  callfunction(data);
			     }
			}
		}   
}

	String.prototype.nullTo=function()
	{
	  return (this==null||this=="null"||this=="undefined")?"":this;
	}