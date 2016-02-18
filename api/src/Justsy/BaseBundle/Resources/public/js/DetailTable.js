var classObject=new Array();//返回已创建的对象引用
var objectRef = null;
var searchClassObject;//返回已创建的对象引用
var decimal = 3;//小数位数
var Unit = new UnitTools();

document.onkeypress = onkeypress;
function onkeypress(e)
{
    var event = e || window.event;
	if(event.keyCode==13)
	{
		var txtobj=document.selection.createRange();
		txtobj.text==""?txtobj.text="\n":(document.selection.clear())&(txtobj.text="\n"); //三目复合表达式,解决有被选文字时回车的光标定位问题
		document.selection.createRange().select();
		return false;
	}   
}

function removeNode(node)
{
   if(isIE)
      node.removeNode(true);
   else
      node.parentNode.removeChild(node);
}
document.onmousedown=function(e){
   		var obj = (new searchPanel()).control;
   		var event = e || window.event;
   		var eventsrcElement = event.target||event.srcElement;
	   	if(
	       obj.style.display==''
	       &&eventsrcElement.id!='_SEARCH_PANEL'
	       &&(eventsrcElement.parentNode!=null
	       &&eventsrcElement.parentNode.id!='_SEARCH_PANEL')
	   	)   	
   		obj.style.display="none";
};
document.onkeydown=function(event){movepos(event);};
function movepos(v)
{
   var event = getDetailTableEvent();
   //去掉特殊的
   if(event.keyCode==13 || objectRef==null)
   {//回车
      return;
   }
   if(event.ctrlKey && event.keyCode==86)
   {
		   	var rowno=null;
   	    if(objectRef.copyrow) //判断是否可以进行行复制
   	    {
   	        rowno = objectRef.currentSelectedRows();
   	        if(rowno==null || rowno.length<1) return;
	   	    document.body.focus();
	   	    for(var i=0;i<rowno.length;i++)
	   	    {
	   	        if(rowno[i]<=objectRef.cloneRowIndex) continue; //如果当前行为标题行，直接退出
            	objectRef.copyRows(rowno[i]);
            }
	   	   	return;
   	    }
   	    rowno = nowClickRow(v);
   	    var cellno = nowClickCol(v);
   	    if(cellno==null || cellno<1) return;
   	   	if(objectRef.copyouterdata)//判断是否可以复制外部数据到表格
   	   	{
   	   		var pasteData = $.trim(getClipboard());//引用 Util.js
   	   		if(pasteData==null || pasteData.length==0) return;  	   		
            objectRef.copyData(rowno,cellno,pasteData);
   	   	}
   	   	return;
   }
   var inputtable = objectRef.tabelobj;
   var nowobj = event.srcElement || event.target;
   
   if(nowobj==null )
   {
      return;
   }
   var nowcell = nowobj.parentNode;
   if(nowcell==null || nowcell.parentNode==null)
   {
      return;
   }
   var rowindex = nowcell.parentNode.rowIndex;
   if(rowindex==null)
   {
      return;
   }
   if(nowcell.tagName=="TD" && nowcell.cellIndex>0)
   {
      //继续搜索
      if(event.keyCode==40 && event.ctrlLeft)
      {
         document.getElementById("_SEARCH_FAST_FIND").onclick();
         return;
      }         
      //向上
      if(event.keyCode==38 && rowindex>objectRef.cloneRowIndex+1)
      {
      	 thisonblur(nowobj);
         resetInput(nowobj);
         createInput(objectRef,inputtable.rows[rowindex-1].cells[nowcell.cellIndex]);         
         return;
      }
      //向下
      if(event.keyCode==40 && rowindex+1<inputtable.rows.length)
      {
      	 thisonblur(nowobj);     	
         resetInput(nowobj);
         createInput(objectRef,inputtable.rows[rowindex+1].cells[nowcell.cellIndex]);         
         return;
      }
      //向右
      if(event.keyCode==39 || event.keyCode==9)
      {
         //获取下一可用单元 格
         var nextCell =  	nowcell.nextSibling;
         while(nextCell!=null && !Type.toDefaultBoolean(nextCell.getAttribute("edit"),true))
         {
         	  nextCell =  	nextCell.nextSibling;
         }
			      	if(event.keyCode==9) //阻止tab键的默认动作，以及阻止事件向上传播
			      	{
			      		if(event.stopPropagation==null)event.cancelBubble=true;
			      		else event.stopPropagation();	      		
			      		if(event.preventDefault!=null)event.preventDefault();
			      		else event.returnValue =false;
			      	 }         
      	 if(nextCell!=null && nowobj.type!="text")
      	 {
       	    thisonblur(nowobj);     	 	
            resetInput(nowobj);
            createInput(objectRef,nextCell);       	 	
      	 	  return;
      	 }
      	 var goNextCtlFalg =false;
      	 try{
      	     if(Util.isIE){
	           var r = document.selection.createRange();
	           var r2 = nowobj.createTextRange();
	           r2.setEndPoint("starttostart", r);
	           goNextCtlFalg = r2.text.length==0 && nextCell!=null;
	         }
	         else{
	            var r2 = window.getSelection().getRangeAt(0);
	            goNextCtlFalg = r2.startContainer.children[0].selectionStart>=r2.startContainer.innerText.length;
	         }
	         if(goNextCtlFalg || event.keyCode==9)
	         {
	            if(nextCell!=null)
	            {
	      	        thisonblur(nowobj);
	            	  resetInput(nowobj);
	            	  createInput(objectRef,nextCell);	            	  
	            }	            
	         }
         }
         catch(e){}
         return;
      }
      //向左
      if(event.keyCode==37)
      {
         var goNextCtlFalg = false;
         //获取下一可用单元 格
         var nextCell =  	nowcell.previousSibling;
         while(nextCell!=null && !Type.toDefaultBoolean(nextCell.getAttribute("edit"),true))
         {
         	  nextCell =  	nextCell.previousSibling;
         }         
      	 if(nextCell!=null && nowobj.type!="text")
      	 {
			      thisonblur(nowobj);      	 	
            resetInput(nowobj);
            createInput(objectRef,nextCell);       	 	
      	 	  return;
      	 }
      	 try{
      	     if(Util.isIE){
	         var r = document.selection.createRange();
	         var r2 = nowobj.createTextRange();
	         r2.setEndPoint("endtoend", r);
	         goNextCtlFalg = r2.text.length==0 && nextCell!=null;
	         }
	         else{
	            var r2 = window.getSelection().getRangeAt(0);
	            goNextCtlFalg = r2.startContainer.children[0].selectionStart==0;
	         }
	         if(goNextCtlFalg)
	         {
	            if(nextCell!=null)
	            {
	            	thisonblur(nowobj);
	            	resetInput(nowobj);
	            	createInput(objectRef,nextCell);            	
	            }
	         }
         }
         catch(e){}
         return;
      }
   }
}
/**
 * 设置一行或多行的选中状态
 */
function checkedRow(v,inds)
{
	//if(v.clickedRowIndex!=null&&v.clickedRowIndex!=-1&&v.rows[v.clickedRowIndex]!=null)
	//	v.rows(v.clickedRowIndex).style.backgroundColor=v.clickedRowBg; 
	v.clickedRowIndex = inds;	 
	var indAry = new Array();
	if(inds=="ALL")
	{
		for(var i=v.cloneRowIndex+1;i<v.tabelobj.rows.length;i++)
			indAry[indAry.length] = i;
	}
	else
		indAry = (""+inds).split(",");
	for(var i=0; i<indAry.length; i++)
	{
	    var r = v.tabelobj.rows[indAry[i]];
		if(r==null) continue;
		r.setAttribute("clickedRowBg",r.style.backgroundColor);
		//r.style.backgroundColor="#8589E0";
		r.style.color="#fff";	
		for(var a=1;a<r.cells.length; a++)
		   r.cells[a].style.backgroundColor="#00AAD8";
		r.setAttribute("checked",true);
		var _r = r.children[0].children[0];
		if(_r!=null)
			 window.setTimeout("document.getElementById('"+v.tabelobj.id+"').rows["+r.rowIndex+"].cells[0].children[0].checked=true",0);
	}
}
/**
 * 取消一行或多行的选中状态
 */
function cancelCheckedRow(v,inds)
{
	var indAry = new Array();
	if(inds=="ALL")
	{
		v.clickedRowIndex = -1;
		for(var i=v.cloneRowIndex+1;i<v.tabelobj.rows.length;i++)
			indAry[indAry.length] = i;
	}
	else
		indAry = (""+inds).split(",");
	for(var i=0; i<indAry.length; i++)
	{
	    var r = v.tabelobj.rows[indAry[i]];
		if(r!=null&&Type.toBoolean(r.getAttribute("checked")))
		{
			//r.style.backgroundColor=r.getAttribute("clickedRowBg");	
			r.setAttribute("checked",false);
			r.style.color="#000";
			var _r = r.children[0].children[0];
			if(_r!=null)
			  window.setTimeout("document.getElementById('"+v.tabelobj.id+"').rows["+r.rowIndex+"].cells[0].children[0].checked=false",0);
		    for(var a=1;a<r.cells.length; a++)
		        r.cells[a].style.backgroundColor=r.getAttribute("clickedRowBg")||"#FFF";			
		}
	}	
}

function getObjectRef(id)
{
	var vid= "";
	if(typeof(id)=="string")
	   vid = id;
	else
	{
	   var t=id;
	   while(t.tagName!="TABLE")
	   {
	       t = t.parentNode;
	   }
	   if(t.tagName!="TABLE") return null;
	   vid = t.id;
	}
	if(vid.substring(vid.length-3)=="Tar")
	{
	    vid = vid.substring(0,vid.length-3);
	}	
	if(objectRef!=null && objectRef.tabelobj.id==vid)
		return objectRef;
    for(var i=0; i<classObject.length; i++)
    {
    	if(classObject[i].tabelobj.id==vid)
    	{ 
    		return classObject[i];
    	}
    }	
    return null;
}

function editCell(v)
{	 
   var event = getDetailTableEvent();
   var obj=oNode= event.srcElement||event.target;
   if(obj.getAttribute("name")!="___row_selectControl") //判断是否是行选择控件
   {
	   if(obj.type!=null || Type.toDefaultBoolean(obj.getAttribute("edit"),true)==false)return;
	   if(oNode.tagName=="INPUT") return;
	   if(oNode.tagName=="DIV")
	      oNode = oNode.parentNode;
   }
   else
   {
       oNode = oNode.parentNode;      
   }
   //判断行是否可编辑
	while (obj.tagName != "TR")
	{
		obj = obj.parentNode;
	}
	//判断当前单击事件是否由冻结的行或列触发的，是则将事件转移到源表格对象上
	var tableId = obj.parentNode.parentNode.id;
	if(tableId.substring(tableId.length-3)=="Tar")
	{
	    tableId = obj.parentNode.parentNode.srcTable;
	    obj = document.getElementById(tableId).rows[obj.rowIndex];
	}
	if(objectRef==null || objectRef.tabelobj.id!=tableId)
		objectRef = getObjectRef(tableId);
	if(objectRef==null || objectRef.form!=null) return;
	if(oNode.id!=null && oNode.id=="_FLAG_COLMUN") //当点击行头时，切换选择状态
	{
		if(objectRef.selectMode!="multiple"&&obj.rowIndex!=objectRef.clickedRowIndex) //如果单选模式下，去除上次选择的行的状态
			cancelCheckedRow(objectRef,objectRef.clickedRowIndex);
	    //对于操作的同一行，进行状态切换
		if(objectRef.clickedRowIndex!=null){
		   if((objectRef.selectMode!="multiple"&&
		       obj.rowIndex==objectRef.clickedRowIndex&&
		       Type.toBoolean(objectRef.tabelobj.rows[objectRef.clickedRowIndex].getAttribute("checked")) 
		      )||
		      (objectRef.selectMode=="multiple" && Type.toBoolean(obj.getAttribute("checked")))
		    )
		    {
			    cancelCheckedRow(objectRef,obj.rowIndex);
          if(objectRef.onRowSelected!=null) //判断是否设置了选中事件
			        objectRef.onRowSelected(obj);			    
			    return;
			}
	    }		
		checkedRow(objectRef,obj.rowIndex);
		if(objectRef.onRowSelected!=null) //判断是否设置了选中事件
			   objectRef.onRowSelected(obj);
		
		return ;
	}
	var isedit = obj.getAttribute("edit");
	if(!Type.toDefaultBoolean(isedit,true)) return;
	if (oNode.childNodes[0]!=null && oNode.childNodes[0].type=="select-one")	return;
    createInput(objectRef,oNode);   
}
function createInput(objectRef,oNode)
{
   var isedit = oNode.getAttribute("edit");
   var hiddenV = oNode.getAttribute("hiddenvalue");
   if(Type.toString(isedit)=="" || Type.toDefaultBoolean(isedit,true) )
   {       
       var oNodevalue=oNode.childNodes[0];
       if(oNodevalue==null )oNodevalue=oNode; 
       //alert(oNode.clientHeight);
       if(oNodevalue.tagName!='OPTION' && oNodevalue.tagName!='INPUT' && oNodevalue.tagName!='TEXTAREA')
       {
       	  //获取当前列对象
       	  var colObj = objectRef.ColsName[objectRef.tabelobj.rows[objectRef.cloneRowIndex].cells[oNode.cellIndex].id];//getCOlObject(classObject.Cols,classObject.tabelobj.rows[0].cells[oNode.cellIndex].id);
          //根据列属性生成相应的控件
          var oInput=createInputControl(colObj,"98%");
		  if(oInput==null) return;
		  oInput.setAttribute("className","edit_input");
		  if(isIE)
              oInput.id= oInput.uniqueID;
          else
              oInput.id= "ctl"+(new random()).rand(10000);
          if(oInput.type=="text")
          	selectedValue(oInput,oNode.title,null); 
          else if(oInput.type=="textarea") 
          	selectedValue(oInput,oNode.title,null); 
          else          
          	selectedValue(oInput,oNode.title+","+hiddenV,null); 
          if(hiddenV!=null && hiddenV!="null")
             oInput.setAttribute("hiddenvalue",hiddenV);
          if(oNode.children[0]!=null)
          	  removeNode(oNode.children[0]); 
          if(oNode.childNodes[0]!=null) 
              removeNode(oNode.childNodes[0]); 
          oNode.appendChild(oInput); 
          window.setTimeout("try{var x=document.getElementById('"+oInput.id+"');x.focus();x.select();}catch(e){}",50);             
      }else if(oNodevalue.tagName!='OPTION')
      {
          oNodevalue.focus();
          oNodevalue.select();           
      }               
    }   
}
//根据列对象生成相应的控件
function createInputControl(colSet,widthpx)
{
	if(colSet==null) return;		
	if(Type.toBoolean(colSet.isOpenWindow)==true)
	{
			 if(Type.toString(colSet.OpenWindowURL)=="")
			 {
				alert("没有设置列["+colSet.id+"]可供调用的弹出窗口函数的URL地址");
			 }	 
			 var oInput=document.createElement("input");
			 oInput.style.textAlign = colSet.alignStyle;
			 oInput.style.width = widthpx==null?colSet.width:widthpx;
			 oInput.setAttribute("maxLength",colSet.maxLength==null?20:colSet.maxLength);
			 oInput.setAttribute("className",colSet.CSS);
			 //oInput. readonly=true;
			 oInput.ondragenter=function(){return false;};
			 var onclick = "";
			 if (colSet.OpenWindowURL.indexOf("javascript:")>-1)
			 {
			 	var eventCode = colSet.OpenWindowURL.substring(11);
			 	oInput.onfocus=function(event){
			 		eval(replaceall(eventCode,"\"","'"));//resetInput(event.srcElement)
			     }
			 }
			 else if (colSet.OpenWindowURL.indexOf("callback:")>-1) //回调函数。函数实现时只允许一个参数，参数只接收程序回传的列对象
			 {
			 	var eventCode = colSet.OpenWindowURL.substring(9);
			 	oInput.onfocus=function(event){eval(eventCode+"("+colSet+")");};			 	
			 }
			 else
			 {
				 oInput.onfocus=function(){alert('未实现')};			 				 	
			 }
			 oInput.onblur=function(event){
				  if(colSet.onblur!=null)
				  {
				  	 var e = event||window.event;
				     colSet.onblur(e.target||e.srcElement);
				  }
				  thisonblur(this);
				  fnEndEdit();
			 };
			 return oInput;
   }
   else if(Type.toBoolean(colSet.isAreaText))
   {
			    var oInput=document.createElement("textarea"); 
				oInput.style.width = widthpx==null?colSet.width:widthpx;
				oInput.style.height="100px";
				oInput.setAttribute("className",colSet.CSS);
				oInput.onblur=function(event){
				  if(colSet.onblur!=null)
				     colSet.onblur();
				  thisonblur(this);
				  fnEndEdit();
				};
				oInput.ondragenter=function(){return false};		
			    return oInput;	
   }
   else if(Type.toBoolean(colSet.isDownList)==true)
   {
			 //下拉框 控件
			 if(colSet.listData==null)
			 {
				alert("没有设置列["+colSet.id+"]可供填充的数据源!");
			 }
			 else
			 {
			 	var oControl=document.createElement("select");
				oControl.style.width = widthpx==null?colSet.width:widthpx;
			 	oControl.setAttribute("className",colSet.CSS);			 	
				var strData = "";//首先生成列表数据串
				var strtemp = "";				
				for(var j=0;j<colSet.listData.length;j++)
				{
				   var opt = document.createElement("option");
				   oControl.options.add(opt);
				   strtemp = colSet.listData[j].split(",");
				   opt.value = strtemp[1];
				   opt.text = strtemp[0];
				}
				oControl.onblur=function(event){
				  if(colSet.onblur!=null)
				     colSet.onblur();
				  thisonblur(this);
				  fnEndEdit();
				};
				return oControl;
			 }
			 return null;			 
   }
   else if(Type.toBoolean(colSet.isRadio)==true)
   {
			 if(colSet.listData==null)
				alert("没有设置列["+colSet.id+"]可供填充的数据源!");
			else{
				var oDIV=document.createElement("DIV");
				oDIV.style.width = widthpx==null?colSet.width:widthpx;
			 	oDIV.setAttribute("className",colSet.CSS);
				var strData = "";//首先生成列表数据串
				var strtemp = "";
				for(var j=0;j<colSet.listData.length;j++)
				{
				   strtemp = colSet.listData[j].split(",");
				   var oControl=document.createElement("radio");
				   oControl.id=colSet.id;
				   oControl.setAttribute("hiddenvalue",strtemp[1]);
				   oControl.value=strtemp[1];
				   oDIV.appendChild(oControl);
				   oControl=document.createElement("span");
				   oControl.innerText = [0];
				   oDIV.appendChild(oControl);
				}
				return oDIV;
			 }
			 return null;			 
   }
   else if(Type.toBoolean(colSet.isCheckbox)==true)
   {
			 if(colSet.listData==null)
				alert("没有设置列["+colSet.id+"]可供填充的数据源!");
			else
			{
				var oControl=document.createElement("DIV");
				oControl.style.width = widthpx==null?colSet.width:widthpx;
			 	oControl.setAttribute("className",colSet.CSS);
				var strData = "";//首先生成列表数据串
				var strtemp = "";
				for(var j=0;j<colSet.listData.length;j++)
				{
				   strtemp = colSet.listData[j].split(",");
				   var oControl=document.createElement("checkbox");
				   oControl.id=colSet.id;
				   oControl.setAttribute("hiddenvalue",strtemp[1]);
				   oControl.value=strtemp[1];
				   oDIV.appedChild(oControl);
				   oControl=document.createElement("span");
				   oControl.innerText = strtemp[0];
				   oDIV.appedChild(oControl);
				}
				return oDIV;
			 }
			 return null;
   }
   else
   {		  	
	          var oInput=document.createElement("INPUT");
	          oInput.type="text";
	          oInput.setAttribute("maxLength",colSet.maxLength==null?20:colSet.maxLength*1);  	
			 //加入设置的回车调用事件
			 if(colSet.enterCallFunction!=null && colSet.enterCallFunction!="")
			 {
				oInput.onkeydown=function(event){
				    var event = event||window.event;
					if(event.keyCode==13){eval(colSet.enterCallFunction);}else{if(this.innerHTML.length>16){this.style.height='20';}else{this.style.height='20';}};
				}
			 }
			 //判断是否允许换行
			 if(colSet.isEnter==true)
			 {
				oInput.onkeydown=function(event){
				    var event = event||window.event;
					if(event.keyCode==13){}else{if(this.innerHTML.length>parseInt(replace(colSet.width,"px","")/7)){this.style.height='20';}else{this.style.height='20';}};
				}
			 }
			 if(colSet.isEnter==false && colSet.enterCallFunction==null)
			 {
				oInput.onkeydown=function(event){
				    var event = event||window.event;
					if(event.keyCode==13){return false;}else{if(this.innerHTML.length>parseInt(replace(colSet.width,"px","")/7)){this.style.height='20';}else{this.style.height='20';}};
				}
			 } 
			 if(Type.toString(colSet.inputType)!="")
			 {
				oInput.onkeypress=function(event){
				    var ts = event!=null?"event":"this";
					return eval(colSet.inputType+"("+ts+");");
				}; 
				oInput.style.imeMode="disabled";
			 }
			 oInput.style.textAlign = colSet.alignStyle;
			 oInput.style.width = widthpx==null?colSet.width:widthpx;
				 oInput.onblur=function(event){
				 	  var r = true;
					  if(colSet.onblur!=null)
					  {
					  	 var e = event||window.event;
					     r=colSet.onblur(e.target||e.srcElement);
					  }
					  //if(r!=null && !r){return;}
					  thisonblur(this);
					  fnEndEdit();
				 };
			 oInput.ondragenter=function(){return false};
			return oInput;
	}		
}
function resetInput(oNode)
{	
   if(oNode==null)return;
   var oldValue = oNode.parentNode.title;
   var editRow = oNode.parentNode.parentNode;
   var newValue = "";
   var div  = document.createElement("div");
   div.setAttribute("className","textNoBR");
   div.innerHTML = oNode.value;
   div.innerHTML=div.innerHTML==""?"&nbsp;":div.innerHTML;
   var owenParentNode = oNode.parentNode;
   if(oNode.type=="text")
   {
	   newValue = oNode.value;
	   owenParentNode.title =    oNode.value;   
	   owenParentNode.appendChild(div);
	   var x = oNode.getAttribute("hiddenvalue");
       if(x!=null){
	   		div.setAttribute("hiddenvalue",x);
	   		owenParentNode.setAttribute("hiddenvalue", x);
	   }	   
	   removeNode(oNode);
   }
   else if(oNode.type=="textarea")
   {
	   newValue = oNode.value;
	   owenParentNode.title =    oNode.value;
	   owenParentNode.appendChild(div);
	   var x = oNode.getAttribute("hiddenvalue");
       if(x!=null){
	   		div.setAttribute("hiddenvalue",x);
	   		owenParentNode.setAttribute("hiddenvalue", x);
	   }	   
	   removeNode(oNode);   	
   }
   else if(oNode.type.indexOf("select")>-1)
   {
   		div.innerHTML = oNode.options[oNode.selectedIndex].text;
   		div.innerHTML=div.innerHTML==""?"&nbsp;":div.innerHTML;
   		newValue = oNode.options[oNode.selectedIndex].text;
   		newValue = newValue+"," + oNode.value;
	   	owenParentNode.appendChild(div);
	   	div.setAttribute("hiddenvalue", oNode.value);
	   	owenParentNode.setAttribute("hiddenvalue",oNode.value);
	   	removeNode(oNode);   		
   }
   else
   {
   			newValue = oNode.childNodes[0].value;
	   		owenParentNode.appendChild(div);
	 	   var x = oNode.getAttribute("hiddenvalue");
	       if(x!=null){
		   		div.setAttribute("hiddenvalue",x);
		   		owenParentNode.setAttribute("hiddenvalue", x);
		   }	   	
	   	removeNode(oNode);   	
   }
   if(owenParentNode.childNodes.length>1)
      removeNode(owenParentNode.childNodes[0]);
   if(oldValue!=null &&newValue!=oldValue)
   {
       var ac = editRow.getAttribute("action");
       editRow.setAttribute("action",(ac!=null && ac!="insert")?"edit":"insert");    //编辑标识
   }
}
function fnEndEdit(){
   var event = getDetailTableEvent();
   var oNode=event.srcElement||event.target;
   resetInput(oNode);
}
function UnitTools(){	
	this.setCalendar = function()
	{
	    var o=getDetailTableEvent();
		var control = o.srcElement||o.target;
        setCalendar	(o,control.parentNode);
        resetInput(control);	
	}
	this.SELECT_SYSCODE = function (marojcode,minorcode,isMulSelect)
	{
	   var control = event.srcElement;
	   var winWidth=700,winHeight= 450,newToday = new Date();
	   if (isMulSelect==null ||isMulSelect=="") isMulSelect=false;
	   var myWinUrl = conText + "/agdev/syscode/syscode-tag-main.jsp";
	   myWinUrl = myWinUrl + "?code=" + marojcode;
	   myWinUrl = myWinUrl + "&codeId=";
	   myWinUrl = myWinUrl + "&multiFlag=" + isMulSelect;   
	   myWinUrl = myWinUrl + "&selectIDFlag=";
	   myWinUrl = myWinUrl + "&selectedValue=;";
	   myWinUrl = myWinUrl + "&diffUrl=" + newToday.getTime();
	   if(minorcode!=null && minorcode!="")
	   {
	   	    var objectRef = getObjectRef(control);
	        //获取指定的次码值
	        if(objectRef!=null && minorcode.indexOf("index=")>-1)
	        {
	        	minorcode = minorcode.replace("index=","");
	        	minorcode = objectRef.tabelobj.rows[nowClickRow()].cells[minorcode].getAttribute("hiddenvalue");
	        }
			myWinUrl = conText + "/managersys/syscode/syscode-tag-main.jsp";
		   	myWinUrl = myWinUrl + "?code=" + marojcode;
		   	myWinUrl = myWinUrl + "&codeId=";
		   	myWinUrl = myWinUrl + "&multiFlag=" + isMulSelect;   
		   	myWinUrl = myWinUrl + "&selectIDFlag=";
		   	myWinUrl = myWinUrl + "&selectedValue=;";
		   	myWinUrl = myWinUrl + "&diffUrl=" + newToday.getTime();	   	
	   		myWinUrl = myWinUrl + "&minorcode="+minorcode;
	   }
	   var myWinValue = popup_window(conText,"代码选择",myWinUrl,winWidth,winHeight,false);   
	   if ( myWinValue != null )
	   {
			var nameValue="",idValue="";
			var valueArray = myWinValue.split(";");           
			for ( var i = 0; i <  valueArray.length; i++)
			{
				idValue += valueArray[i].split(",")[0]+",";
				nameValue += valueArray[i].split(",")[1]+",";
			}
	      	control.value = nameValue.substring(0,nameValue.lastIndexOf(","));
	      	control.parentNode.setAttribute("hiddenvalue",idValue.substring(0,idValue.lastIndexOf(",")));	
	      	control.setAttribute("hiddenvalue",control.parentNode.getAttribute("hiddenvalue"));			
	   }
	   resetInput(control);
	}
	
	this.SELECT_ORGANIZE=function (orgid,valueCtl,isMulSelect)
	{
		var control = event.srcElement;
		var winWidth=700,winHeight= 450,newToday = new Date();
		if (isMulSelect==null ||isMulSelect=="") isMulSelect=false;
		var myWinUrl = conText + "/agdev/hrmsub/org/org-tag-main.jsp";
		myWinUrl = myWinUrl + "?orgRootID=" + orgid;
		myWinUrl = myWinUrl + "&multiFlag=" + isMulSelect;
		myWinUrl = myWinUrl + "&selectedValue=;";
		myWinUrl = myWinUrl + "&diffUrl=" + newToday.getTime();   
		var myWinValue = popup_window(conText,"组织机构选择",myWinUrl,winWidth,winHeight,false);   
		if ( myWinValue != null )
		{
			var nameValue="",idValue="";
			var valueArray = myWinValue.split(";");           
			for ( var i = 0; i <  valueArray.length; i++)
			{
				idValue += valueArray[i].split(",")[0]+",";
				nameValue += valueArray[i].split(",")[1]+",";
			}
	      	control.value = nameValue.substring(0,nameValue.lastIndexOf(","));
	      	document.getElementById(valueCtl).value=idValue.substring(0,idValue.lastIndexOf(",")); 
		}
		//resetInput(control);
	}
	this.SELECT_EMPLOYEE=function (orgid,isMulSelect)
	{
		var control = event.srcElement;
	   var winWidth=700,winHeight= 450,newToday = new Date();
	   if (isMulSelect==null ||isMulSelect=="") isMulSelect=false;
	   var myWinUrl = conText + "/agdev/hrmsub/emp/emp-tag-main.jsp";
	   myWinUrl = myWinUrl + "?orgRootID=" + orgid;
	   myWinUrl = myWinUrl + "&multiFlag=" + isMulSelect;
	   myWinUrl = myWinUrl + "&userFlag=" ;
	   myWinUrl = myWinUrl + "&selectedValue=;" ;
	   myWinUrl = myWinUrl + "&diffUrl=" + newToday.getTime();
	   
	   var myWinValue = popup_window(conText,"人员信息选择",myWinUrl,winWidth,winHeight,false);  
	   if ( myWinValue != null )
	   {
			var nameValue="",idValue="";
			var valueArray = myWinValue.split(";");           
			for ( var i = 0; i <  valueArray.length; i++)
			{
				idValue += valueArray[i].split(",")[0]+",";
				nameValue += valueArray[i].split(",")[1]+",";
			}
	      	control.value = nameValue.substring(0,nameValue.lastIndexOf(","));
	      	control.parentNode.setAttribute("hiddenvalue",idValue.substring(0,idValue.lastIndexOf(",")));	
	      	control.setAttribute("hiddenvalue",control.parentNode.getAttribute("hiddenvalue"));		      		
	   }
	   resetInput(control);
	}
    this.SELECT_WINDOW=function (url,title,parameters)
	{
	    var event = getDetailTableEvent();
		var control = event.srcElement||event.parentNode||event.currentTarget;
		if(!Util.isIE)
		   control = control.parentNode;
	   var winWidth=700,winHeight= 450,newToday = new Date(); 
	   var myWinUrl = conText + url;	   
	   myWinUrl = myWinUrl + "?diffUrl=" + newToday.getTime();
	   if(parameters!=null)
	      myWinUrl += "&"+parameters;
	   var myWinValue = popup_window(conText,title,myWinUrl,winWidth,winHeight,false);  
	   if ( myWinValue != null )
	   {
			var nameValue="",idValue="";
			var valueArray = myWinValue.split(";");           
			for ( var i = 0; i <  valueArray.length; i++)
			{
				idValue += valueArray[i].split(",")[0]+",";
				nameValue += valueArray[i].split(",")[1]+",";
			}
			if(Util.isIE)
			{
	      	   control.value = nameValue.substring(0,nameValue.lastIndexOf(","));
	      	   control.parentNode.setAttribute("hiddenvalue",idValue.substring(0,idValue.lastIndexOf(",")));	
	      	   control.setAttribute("hiddenvalue",control.parentNode.getAttribute("hiddenvalue"));
	      	}
	      	else
	      	{
	      	   control.innerHTML = nameValue.substring(0,nameValue.lastIndexOf(","));
	      	   control.setAttribute("hiddenvalue",idValue.substring(0,idValue.lastIndexOf(",")));
	      	}		      		
	   }
	   resetInput(control);
	}
}
//列对象
function colObject()
{
   this.id = null;
   this.name = "";
   this.width = "100%";
   this.alignStyle = "left";
   this.index = null;//列索引
   this.isSave = true;//是否要保存该列
   this.addRow = false;  //当前列操作后是否新加一行
   this.isAreaText = false;//是否多行输入框
   this.isButton = false;//列类型是否是按钮。未实现该属性的处理.后续保留
   this.isDownList = false;//列类型是否是下拉列表框
   this.isRadio = false;//列类型是否是单选框
   this.isCheckbox = false;//列类型是否是多选框框
   this.isOpenWindow = false;//是否弹出窗口。是则要生成列时要多生成一个按钮和隐藏框以保存选择的值。
   //如果是弹出窗口时的调用函数名称(系统函数与回调函数)。当isOpenWindow为真时有效
   //列类型为打开新窗口操作类型时，如果触发的是一个系统函数脚本事件，请使用javascript:eventname形式
   //此处提供了以下常用事件：
   //1、代码选择。如果你确定列触发代码选择，请使用javascript:Unit.SELECT_SYSCODE(主码,是否多选择)标明
   //2、单位选择。如果你确定列触发单位选择，请使用javascript:Unit.SELECT_ORGANIZE(单位ID,是否多选择)标明
   //3、人员选择。如果你确定列触发人员选择，请使用javascript:Unit.SELECT_EMPLOYEE(单位ID,是否多选择)标明
   //4、日期选择。如果你确定列触发日期选择，请使用javascript:setCalendar(this[,control])标明
   //如果触发的是一个回调函数脚本事件，请使用callback:eventname(arg)形式,参数arg用于接收回传的列对象。函数本身需要自行实现
   this.OpenWindowURL = "";
   this.listData = null;//如果类型是下拉列表时的数据源。该源是一个Array对象，该对象的元素是逗号间隔的文本/值字符串。。当isDownList为真时有效
   this.href=null;
   this.CSS = "input_text";//列的样式表.默认为input_text
   this.Edit = true;//是否可编辑
   this.enterCallFunction = null;//回车时响应的事件
   this.isEnter = false;//是否允许换行
   this.permitColType = null;
   this.inputType = "";//列的输入限制。默认为无限制。有以下几种：整数inputInteger、正整数inputPlusInteger、小数inputDecimal、正小数inputPlusDecimal、日期、时间
   this.expression = null;//访问设置的表达式。默认为无。
   this.hiddenColName = "";//如果要保存该列的隐藏值时的列名。不指定时代表不保存该列的隐藏值。
   //设置列的默认值。有以下几种情况：1、无默认值。2、自定义值。3、上一行的值。
   //                            4、指定控件的值。5、指定列的值。6、事件处理
   //1、不设置或者设置为空
   //2、非空值但排除后两种情况。就是说先判断后两种情况后为非空的值做为自定义的值处理。
   //3、判断值串是否是全字匹配字符串COPY，是则取上一行该列的值。
   //4、通过判断值串从起始位置开始是否包含全小写的document.getElementById字样来确定是否指定了控件名称
   //5、通过判断值串从起始位置开始是否包含全小写的index=字样来确定是否指定了当前行的列索引号
   //6、通过指定的事件生成默认值
   this.text = ""; 
   this.showtext = true;//是否停留显示全部的内容.
   this.search = false; //是否可以在该列上进行快速查找   
   this.format = "";//输出格式.对于日期型：格式由yyyy、mm、dd、hh、mi、ss组成
   this.sort = "";//是否允许排序.默认为不允许。可以为下列值：string、datetime、number
   this.sortType = "desc";//当前列的排序方式。分为desc和asc方式
   this.deleteRow = null;//删除数据行的事件接口。接受一个回调函数，当前列据作为回调参数返回
   this.callback = null;//当前列填充值后的回调函数
   this.datacheck = null;//当前列的数据校验规则。接受一个回调函数，当前列的数据作为回调参数返回
   this.maxLength=20;
   this.setAttribute=function(attObj)
   {
       for(var key in attObj)
          eval("this."+key+"=attObj."+key);
   }
}

var InputState=0;//表格可输入状态
//表格对象
function detailTable(name,attributes)
{
   this.context = conText;
   var obj = null;
   this.isShowLoadProcess=false;//是否显示数据加载进度   
   this.cloneRowIndex = 0;//要进行列复制的行索引号
   this.selectMode = ""; //默认为单选模式;设置为multiple时为多选模式
   this.selectByControl = false; //是否通过控件行选择，为false表示通过行头选择。为true时，多选采用checkbox类型控件，单选采用radio类型控件。
   this.createRowFlagColumn = false;
   this.edit=false;   
   this.form = null;//默认为表格，非表单
   this.name = name;
   this.key = "ID";//主键列.默认为ID
   this.currentAddRowCount = 0; //当前已经新增的总行数
   this.showLine = true; //是否显示网格线
   this.showHeader = true;//是否显示表头区域
   this.createDeleteColumn = true;//是否添加删除列 
   this.count = 21;//可以编辑的记录行数.默认20行
   this.isaddrows = true;//添加行标志位.标志是否还可以添加新行.主要根据判断count属性来更改其值
   this.copyrow = false;//是否允许在表格内复制选择的行并追加到表格最后。默认为true，允许复制   
   this.copyouterdata=true;//是否允许复制外部数据到表格中，并从当前单元格起进行顺序粘贴。默认为false，不允许   
   this.notnull = "";//保存数据时要检查的不为空的列名，多个列用逗号间隔.
   this.saveCols = null;//要保存的列
   this.dataSource = null;//绑定数据源。二维数组对象
   this.dataKeyMap = [];  //数据源的主键索引缓存,如果没指定主銉时将序列化记录进行索引。根据主键值可快速返回对应记录的位置
   this.setdecimal=setDecimal;//小数位数;//小数位数
   this.isTatolRow = false;//是否加合计行
   this.addTotalRow = AddTatol;//添加合计行
   this.DataBind=databind;
   this.DataBindAfter=null;//数据加载完成后的事件
   this.Init = table_init;//表格初始化
   this.addCol = AddCol;
   this.addRow = AddNewRow;
   this.addRowFragment = AddNewRowFragment;
   this.delRow = DelRow;
   this.getSaveValues = getValues;//返回要保存的列的值数组对象。元素返回一个字符串。列名1,值1,列名2,值2,......
   this.getSaveValuesToString = getValuesToString;//返回要保存的列的值数组对象。元素返回一个字符串。列名1,值1,列名2,值2,......
   this.dbOnClick = null;//双击行事件
   this.updateRowData = updaterowdata;//更新指定行的数据
   this.getRowData = getrowdata;//取得指定编辑行的数据并填充到相应的控件
   this.saveRowData = saverowdata;//保存指定行的数据
   this.noShowCols = noshowcols;
   this.isAddRow = false;//是否添加了新的行到表格中.主要影响到编辑表格时的记录条数读取
   this.event = "";//用户事件名称   
   this.Cols = new Array();//列对象
   this.ColsName = new Array();//列对象.以列名主主键存储
   this.cacheCells = new Array();//缓存已经产生的单元格对象。同一列的单元格只缓存一个单元格。
   this.expression = new expressionList(name);//实例表达式对象  
   /*
    *实例：headerCompose:[["序号","序号"],["主属性","物理名称"],["主属性","逻辑名称(中文描述)"]
                         ,["附加属性","小数位数"]
                         ,["附加属性","精度"]
                         ,["附加属性","是否为空"]
                         ,["附加属性","长度"]
                         ,["是否主键","是否主键"]]   
   */ 
   this.headerCompose = null;//多行表头对象
   this.corssColor = {doubleColor:"#ffffff",singularColor:"#EFEFEF"};//交替行的背景设置
   this.autoFreeze = null;//是否应用行列冻结
   if(name.constructor==String)      
       obj = document.getElementById(name);
   else 
       obj = name;
   if(obj == null)
   {
     alert("无效的对象!");
	 return null;
   }
   
   if(obj.tagName==null || obj.tagName!="TABLE")
   {
   	        if(attributes==null)
   	        {
   	        	alert("创建GRID对象参数不足！");
   	        	return;
   	        }
   	        obj.innerHTML = "";
   	        this.createDeleteColumn=false;
            //自动创建表格对象
            var newTable = document.createElement("TABLE");
            newTable.id = "table_" + (new Date()).getTime();
            ClassStyle.setClass(newTable,"Etable");
            ClassStyle.setClass(obj,"Ediv");
            obj.appendChild(newTable);
            if(isIE ||!isIE)  //如果是非IE，如FF下，需要明细DIV的绝对高度，否则不会自动出现滚动条
            {
               var exp=[],widthExp = [];
               var tempObj = obj;
               //计算高度
               while(tempObj!=null)
	           {
		               if(tempObj.parentNode==null)
		               {
		                  exp[exp.length] =document.compatMode=="CSS1Compat"?document.documentElement.clientHeight: document.body.clientHeight;
		                  break;
		               }	           
	                   if(tempObj.tagName=="HTML"||tempObj.tagName=="TR"||tempObj.tagName=="TBODY"||tempObj.tagName=="BODY")
	                   {
	                        tempObj=tempObj.parentNode;
	                        continue;
	                   }   
		               var h=tempObj.style.height||tempObj.height;
		               h=(h==null||h=="")?tempObj.clientHeight:h;	
		               h=(h==null||h=="")?"100%":h;	           
	                   if(h.constructor!=Number && h.indexOf("%")>-1)
	                   {
	                      exp[exp.length] = h.replace("%","")*1/100;
	                   }
	                   else if(h.constructor==String && h.indexOf("px")>-1)
	                   {
	                       exp[exp.length] = h.replace("px","")*1;
	                       break;
	                   }
	                   else
	                   {
	                      exp[exp.length] = h*1;
	                      break;
	                   }
	                   tempObj=tempObj.parentNode;
	           }
	           //计算宽度
               tempObj = obj;
               while(tempObj!=null)
	           {
		               if(tempObj.parentNode==null)
		               {
		                  widthExp[widthExp.length] = document.compatMode=="CSS1Compat"?document.documentElement.clientWidth:document.body.clientWidth;
		                  break;
		               }	           
	                   if(tempObj.tagName=="HTML"||tempObj.tagName=="TR"||tempObj.tagName=="TBODY"||tempObj.tagName=="BODY")
	                   {
	                        tempObj=tempObj.parentNode;
	                        continue;
	                   }   
		               var h=tempObj.style.width||tempObj.width;
		               h=(h==null||h=="")?tempObj.clientWidth:h;	
		               h=(h==null||h=="")?"100%":h;	           
	                   if(h.constructor!=Number && h.indexOf("%")>-1)
	                   {
	                      widthExp[widthExp.length] = h.replace("%","")*1/100;
	                   }
	                   else if(h.constructor==String && h.indexOf("px")>-1)
	                   {
	                       widthExp[widthExp.length] = h.replace("px","")*1;
	                       break;
	                   }
	                   else
	                   {
	                      widthExp[widthExp.length] = h*1;
	                      break;
	                   }
	                   tempObj=tempObj.parentNode;
	           }	           
	           if(exp.length>0)
	              obj.style.height =eval( exp.join("*")) +"px";
               if(widthExp.length>0)
	              obj.style.width =eval( widthExp.join("*")) +"px";	              
            }
            obj.style.overFlow="auto";
            obj = newTable;
            //设置行列冻结属性
            var lastElement = attributes[attributes.length-1];
            this.showHeader = lastElement.showHeader==null?true:lastElement.showHeader;
            var freeze = lastElement.freeZe;
            if(freeze!=null && (freeze.row!=null ||freeze.col!=null||freeze.foot!=null ))
               this.autoFreeze = freeze;
            //设置背景
            var bc = lastElement.crossColor;
            if(bc!=null){
                for(var attr in bc)
                {
                	var attrValue = eval("bc."+attr);
                	eval("obj."+attr+"=\""+attrValue+"\"");
                }
            } 
            //判断是否是多行表头.如果最后一个元素的headerCompose设置了属性则表示是多行表头
            //多行表头设置规则为：...
            var lastEle = lastElement.headerCompose;            
            if(lastEle!=null && this.showHeader==true)
            {
            	//生成行头单元格矩阵
            	var space = [];
            	for(var i=0;i<lastEle[i].length; i++){space[i]="&nbsp;";}
            	//space[space.length] = {className:"Etd_rowheader"};
            	this.headerCompose = [space];   
            	//生成主体表头单元格知阵         	
            	for(var i=0;i<lastEle.length; i++) 
            		this.headerCompose[this.headerCompose.length] = lastEle[i]; 
              //对元素矩阵进行重复元素过滤
			   var r1,r2,base=0;
			   for(var r=0;r<this.headerCompose[0].length;r++)
			   {
			       var ele = this.headerCompose[r];
			       var compValue = "";
			       base = 0;
			       for(var c=0; c<this.headerCompose.length; c++)
			       {
			       	  r1 = this.headerCompose[c][r];
			       	  if(c==0) compValue = r1;
			       	  r2 =c<this.headerCompose.length-1?this.headerCompose[c+1][r]:null;
			          this.headerCompose[c][r] = {value:this.headerCompose[c][r],colspan:1,rowspan:1};
			          if(compValue==r2)
			          {
			             this.headerCompose[base][r].colspan = this.headerCompose[base][r].colspan+1;
			             this.headerCompose[c+1][r]=null; 
			          }
			          else
			          {
			          	base = c+1;
			          	compValue = r2;            
			          }
			       }
			   }
			   for(var c=0;c<this.headerCompose.length;c++)
			   {
			       var ele = this.headerCompose[c];
			       var compValue = "";
			       base = 0;
			       for(var r=0;r<ele.length; r++)
			       {
			       	  r1 = ele[r].value;
			       	  if(r==0) 
			       	  {
			       	  	compValue = r1; 
			       	  }
			       	  r2 =r<ele.length-1?ele[r+1].value:null;
			          if((compValue==r2) && r<ele.length-1)
			          {
			             this.headerCompose[c][base].rowspan = this.headerCompose[c][base].rowspan+1;
			             this.headerCompose[c][r+1].value=null; 
			          }
			          else
			          {
			          	base = compValue==null?r+1:r;
			          	compValue = r2;            
			          }
			       }
			   }            	
	            //产生表头
	            var rowL = obj.rows.length;
		       for(var p=0;p<rowL;p++)
		          obj.childNodes[0].removeChild(obj.rows[0]);  
		       for(var p=0; p<this.headerCompose[0].length; p++)
		       {
		           var r = obj.insertRow(obj.rows.length);
		           r.setAttribute("edit","0");
		           for(var i=0;i<this.headerCompose.length; i++)
		           {          
		           	   var cellobj = this.headerCompose[i][p];	
		           	   if(cellobj.value==null) continue;
		           	   var cell = r.insertCell(-1);
		           	   cell.colSpan = cellobj.colspan;
		           	   cell.rowSpan = cellobj.rowspan;
		           	   cell.innerHTML = cellobj.value==""&&i==0?"&nbsp;":cellobj.value;
		           	   cell.align="center";
		           	   if(i==0) 
		           	   	  ClassStyle.setClass(cell, "Etd_rowheader");
		           }	
		       }
            }
            var firstRow = newTable.insertRow(obj.rows.length);
            firstRow.setAttribute("edit","0");         
            if(this.headerCompose!=null || !this.showHeader) firstRow.height=1;
            var posLen =    this.headerCompose==null && bc==null?attributes.length:attributes.length-1;
            var _tw = 0;
            for(var i=0;i<posLen; i++)
            {                
                var td =   document.createElement("TD");
                firstRow.appendChild(td);
                td.id = attributes[i].id;//"list_details_col_"+i;
                ClassStyle.setClass(td,attributes[i].CSS==null?"":attributes[i].CSS);
                td.align = attributes[i].alignStyle==null?"center":attributes[i].alignStyle;  
                td.setAttribute("inputType", attributes[i].inputType==null?"":attributes[i].inputType);       
                td.innerHTML = this.headerCompose==null&&this.showHeader==true?attributes[i].title:"";
                td.width = attributes[i].width==null?"":attributes[i].width;
                if(this.headerCompose!=null || !this.showHeader) td.height=1;
                for(var attr in attributes[i])
                {
                	var attrValue = eval("attributes[i]."+attr); 
                	var k = td.getAttribute(attr);               	
                	if(attr=="title" ||( k!=null &&k!="undefined" && k!="")) continue;   
                	td.setAttribute(attr,attrValue);
                }
                if((td.width==null||td.width=="undefined" || td.width=="")&&i<posLen-1)
                {
                   td.width = "60px";
                   _tw += 60;
                }
                else
                	_tw += (td.clientWidth||td.offsetWidth||td.width.replace("px",""))*1;
                if((td.width==null||td.width=="undefined" ||td.width=="0px" || td.width=="")&&i==posLen-1)
                {                   
                    var lastCellWidth = newTable.parentNode.style.width.replace("px","")*1-_tw;
                    lastCellWidth=lastCellWidth<=0?"60":lastCellWidth;
                    _tw += lastCellWidth*1;
                    newTable.style.width = _tw+"px";
                    td.style.width = lastCellWidth+"px";
                    td.width = lastCellWidth+"px";
                }
            }
            newTable.width =   _tw+"px";
            newTable.style.width =   _tw+"px";     
   }
   if(obj.id==null || obj.id=="")
      obj.id = "table_" + (new Date()).getTime();
      /*
   obj.onmouseout = function()
   {
   	   var r = event.srcElement.parentNode; 
   	   while(r!=null && r.tagName!="TR")
   	      r = r.parentNode;
   	   if(r==null) return;
   	   var t = r.parentNode.parentNode;
       //r.className=(r.rowIndex%2==0)?t.doubleColorCSS:t.singularColorCSS;
       
   }
   obj.onmouseover = function(e)
   {
       var event = e || window.event;
   	   var r = (event.srcElement || event.target).parentNode;   	     	   
   	   while(r!=null && r.tagName!="TR")
   	      r = r.parentNode;
   	   if(r==null) return; 
   	   var t = r.parentNode.parentNode;   
   	   //r.className=(r.rowIndex%2==0)?t.doubleColorCSS:t.singularColorCSS;

   }   */
   //根据指定的属性名及属性值，选中符合条件的行
   this.selectRow=function(att,value){
      var cellIndex = getIndex(this.tabelobj,att);
      if(cellIndex==-1) return;
      for(var i=this.cloneRowIndex;i<this.tabelobj.rows.length;i++)
      {
         var vs = getColValue(this.tabelobj,i,cellIndex,null);
         if(vs==value)
            checkedRow(this,i);
      }
   }
   this.setIsForm=function(){
   	   this.tabelobj.style.tableLayout = "auto";
   	   //判断是否引入了form表单JS文件
   	   var isRef = false;
   	   var scripts = document.getElementsByTagName("SCRIPT");
   	   if(scripts!=null)
   	   {
	   	   for(var i=0;i<scripts.length; i++)
	   	   {
	   	       if(scripts[i].src.indexOf("DetailForm.js")>-1)
	   	       {
	   	       	  isRef = true;
	   	          break;
	   	       }
	   	   }
   	   }
   	   if(!isRef)
   	   {
		    var oHead = document.getElementsByTagName('HEAD').item(0);		
		    var oScript= document.createElement("script"); 		
		    oScript.type = "text/javascript"; 		
		    oScript.src="DetailForm.js"; 		
		    oHead.appendChild( oScript);  
		    
            if (!/*@cc_on!@*/0) { //if not IE        //Firefox2、Firefox3、Safari3.1+、Opera9.6+ support js.onload        
                 oScript.onload = function () {                 	
                 }    
            } else {        //IE6、IE7 support js.onreadystatechange        
              oScript.onreadystatechange = function () {           
              	 if (oScript.readyState == 'loaded' || oScript.readyState == 'complete') { 
              	 	                }        
              	 	             }    
            }
                  	      
   	   }
	   	   this.form = new FormObject();
	   	   this.form.setParentObject(this);
   }
   obj.onmousedown=function(event){   	
   		editCell(event);
   	};
   this.tabelobj = obj;
   this.tabelobj.style.tableLayout = "fixed";

   if(obj.rows.length<1)
   {
      alert("指定对象的行不能为空!");
      return null;
   }
   this.CreateLink=function(file){
   	    var isLoaded = false;
   	    var links = document.getElementsByTagName("LINK");
   	    if(links!=null){
	   	    for(var i=0; i<links.length; i++)
	   	    {
	   	    	var linkName = links[i].href;
	   	    	if(linkName.indexOf(file)>-1)
	   	    	{
	   	    		isLoaded = true;
	   	    		break
	   	    	}
	   	    }
   	    }
   	    if(!isLoaded){
			var new_element;
			new_element=document.createElement("link");
			new_element.setAttribute("type","text/css");
			new_element.setAttribute("rel","stylesheet");
			new_element.setAttribute("href",file);
			void(document.getElementsByTagName("head")[0].appendChild(new_element));
   	    }
		ClassStyle.setClass(this.tabelobj,"Etable"); 
		for(var tds =0;tds< this.tabelobj.rows.length; tds++)
		{
			var tdrow = this.tabelobj.rows[tds];
			if(tdrow.height!=null &&tdrow.offsetHeight*1<10)
			{
				continue;
			}
			tdrow.className="";
			var tdcss = "Etd_input";
			if(!Type.toDefaultBoolean(tdrow.getAttribute("edit"),true)) 
				tdcss = "Etd_titleheader"; 
			else
			   tdcss = "Etd_input"; 
			for(var t=0;t<tdrow.cells.length; t++)
			{
				if(ClassStyle.getClass(tdrow.cells[t])=="Etd_rowheader" ) continue; 
				ClassStyle.setClass(tdrow.cells[t],tdcss); 
			}
		}
   }
   this.CreateLink("../css/edit-table-default-style.css");
   this.setCloneRowIndex = function(_rowindex)
   {
   	   rowindex = this.headerCompose!=null?this.tabelobj.rows.length-1:_rowindex;
   	   this.createRowFlagColumn = true;//是否自动创建行标识列
       this.cloneRowIndex = rowindex;    
       this.tabelobj.cloneRowIndex = rowindex;   
	       var newtd = this.tabelobj.rows[rowindex].insertCell(0); 
	       ClassStyle.setClass(newtd,"Etd_rowheader");//this.tabelobj.rows[rowindex].cells[1].className;
	       newtd.align="center";
	       newtd.width="20px"; 
	       newtd.innerHTML ="";
	       newtd.onclick=function() //选择/取消全部选中
	       {       	   
	       	   //如果当前表格对象的全选标志为选中，则更改为全部取消，否则设置为全部选择状态
	       	   var currentTableObject = getObjectRef(this);
	       	   if(currentTableObject.selectMode!="multiple") return;
	       	   if(currentTableObject.clickedRowIndex==null || currentTableObject.clickedRowIndex==-1) 
	       	       checkedRow(currentTableObject,"ALL");
	       	   else 
	       	       cancelCheckedRow(currentTableObject,"ALL");       	   
	       }      
       
       var startCellIndex = 1;
       //是否自动创建删除列
       if(this.createDeleteColumn)
       {
           var newtd=null;
           if(this.headerCompose!=null){
	           newtd = this.tabelobj.rows[0].insertCell(1);
	           ClassStyle.setClass(newtd,"Etd_rowheader");
	           newtd.style.width="30px"; 
		       newtd.innerHTML ="";  
		       newtd.setAttribute("edit","0");
		       newtd.setAttribute("isSave","0");
		       newtd.rowSpan =  this.headerCompose[0].length;
	       }
           newtd = this.tabelobj.rows[rowindex].insertCell(1);
           ClassStyle.setClass(newtd,"Etd_rowheader");
           newtd.style.width="30px"; 
           newtd.id="_DEL";
           newtd.setAttribute("edit","0");
           newtd.setAttribute("isSave","0");
	       newtd.innerHTML ="";	       
       }
          
       //判断当前列是否设置了排序功能
       for(var i=startCellIndex; i<this.tabelobj.rows[rowindex].cells.length;i++)
       {
       	   var cell = this.tabelobj.rows[rowindex].cells[i];
           if(cell.sort!=null && cell.sort!="")
           {
               var sortdiv = document.createElement("div");
               cell.appendChild(sortdiv);
               cell.style.position = "relative";
           }
       }
	   for(var i=startCellIndex;i<this.tabelobj.rows[rowindex].cells.length;i++)
       {
       	   var colid = this.tabelobj.rows[rowindex].cells[i].id;
       	   if(colid==null) continue;
           var colObj = new colObject();
           
           colObj.id=colid;
		       colObj.CSS=(!this.edit)?"Etd_readonly":"Etd_input";
		       colObj.Edit = this.edit;
           this.addCol(colObj);
          
       }      
   }    
   this.objCols = obj.rows[this.cloneRowIndex].cells.length;
   if(this.objCols<1)
   {
      alert("指定对象的列不能为空!");
      return null;
   }
   //内存分页
   this.memoryPage=function(pageno)
   {
           this.Init(null); 
		   var pageObj = this.pageObject.getPageBean();		
		   this.DataBind(pageObj.startrecord,pageObj.endrecord>this.dataSource.length?this.dataSource.length:pageObj.endrecord);　　　//将数据源加载到表格       
       
   }
   
   /*
    * 初始化分页组件
    * 初始化属性：
    *     parentNode：显示数据时包含分页栏的元素ID，未指定时默认采用GRID的父元素
    *     callfunc：指定一个开发人员已实现的分页函数，供对象回调。回调时将分页对象为参数返回.没指定时将采用自动内存分页,这时需将所有数据全部获取到页面
    *     startColor：分页栏渐变起始色。默认为FFFFFF
    *     endColor：分页栏渐变结束色。默认为98B2E6
    * */
   this.pageObject = {
   	parentObject:this,
    pageinfo:null,   	
    fixedid:this.tabelobj.id,
   	init:function(args)
   	{
   		//如果args.callfunc==null时，则自动采用内存分页
   		var parentNode = args.parentNode,startColor=args.startColor,endColor=args.endColor;   		
   		//if(args.callfunc==null) return;   		
   		var firstPage = document.createElement("span");
   		firstPage.id=this.fixedid+"_first";
   		ClassStyle.setClass(firstPage,"grid_pageinfo_button");  
   		firstPage.innerHTML="＜＜";
   		var prevPage = document.createElement("span");
   		prevPage.id=this.fixedid+"_last";
   		ClassStyle.setClass(prevPage,"grid_pageinfo_button");  
   		prevPage.innerHTML="＜";   
   		var nextPage = document.createElement("span");
   		nextPage.id= this.fixedid+"_next";
   		ClassStyle.setClass(nextPage,"grid_pageinfo_button");  
   		nextPage.innerHTML="＞";   
   		var lastestPage = document.createElement("span");
   		lastestPage.id= this.fixedid+"_lastest";
   		ClassStyle.setClass(lastestPage,"grid_pageinfo_button");    		
   		lastestPage.innerHTML="＞＞"; 
   		var pagehint = document.createElement("span");
   		pagehint.id= this.fixedid+"_pagehint";
   		ClassStyle.setClass(pagehint,"grid_pageinfo_hint"); 		  		 				
   		var pe = parentNode==null?this.parentObject.tabelobj.parentNode:(parentNode.constructor==String?document.getElementById(parentNode):parentNode);
   	    this.pageinfo = document.createElement("div");
   	    this.pageinfo.id = this.fixedid+"__grid_pageinfo";
   	    this.pageinfo.style.width = this.parentObject.tabelobj.width;
   	    if(startColor!=null && endColor!=null)
   	    this.pageinfo.style.filter="progid:DXImageTransform.Microsoft.Gradient(startColorStr='#"+(startColor==null?"FFFFFF":startColor)+"', endColorStr='#"+(endColor=null?"98B2E6":endColor)+"', gradientType='0')";
        pe.appendChild(this.pageinfo);
        this.pageinfo.appendChild(firstPage);
        this.pageinfo.appendChild(prevPage);
        this.pageinfo.appendChild(nextPage);
        this.pageinfo.appendChild(lastestPage);
        this.pageinfo.appendChild(pagehint);
        this.pageinfo.setAttribute("pagecount",1);
        this.pageinfo.setAttribute("pagesize",20);
        this.pageinfo.setAttribute("recordcount",0);
        this.pageinfo.setAttribute("currentpage",1);
	    args.parent = this;        	
        args.updatePage=function(p)
        {
        	this.parent.pageinfo.setAttribute("currentpage",p);
        	this.parent.refreshPageList();
        }
        args.updatePage(1);
        firstPage.onclick=prevPage.onclick=nextPage.onclick=lastestPage.onclick=function(e)
        {
            var event = e || window.event;
            var eventsrcElement = event.target || event.srcElement;
        	var src = (eventsrcElement.id); 
        	var page = eventsrcElement.parentNode;
            var currentPageNo = parseInt(page.getAttribute("currentpage"));
            var pageCount = parseInt(page.getAttribute("pagecount"));
            if (src.indexOf("_next")>0){
               if (currentPageNo + 1 > pageCount*1){alert("已经是最后一页！");return;}
               else{
                    args.updatePage(currentPageNo + 1);}}
            else if(src.indexOf("_lastest")>=0){if (currentPageNo == pageCount){alert("已经是最后页");return;}
                args.updatePage(pageCount);}                    
            else if(src.indexOf("_last")>0){
                if (currentPageNo > 1){
                    args.updatePage(currentPageNo - 1);}
                else{alert("已经是第一页！");return;}}
            else if(src.indexOf("_first")>0){
                 if (currentPageNo == 1){alert("已经是第一页！");return;}
                 args.updatePage(1);}
            else if (src.indexOf("_jump")>0){
                 if (currentPageNo >= 1 && currentPageNo <= pageCount){
                 	 args.updatePage(currentPageNo);}
                 else{alert("跳转页数无效！");return;}
                 }       
           if(args.callfunc!=null) args.callfunc(); 
           else
           {
               var o = getObjectRef(page.id.replace("__grid_pageinfo",""));
               o.memoryPage(currentPageNo);
           }
        };
   	},
   	setCurrentPage:function(_page)
   	{
   	  this.pageinfo.setAttribute("currentpage",_page)
   	},   	
   	setPageSize:function(cnt)
   	{
   		if(this.pageinfo==null){
   			 setControlValue(this.fixedid+"pagesize",cnt);
   			 return;
   		}
   		this.pageinfo.setAttribute("pagesize",cnt);
   		this.parentObject.count = cnt;
   		this.refreshPageList();
   	},
   	getPageSize:function()
   	{
   		if(this.pageinfo==null) return getControlValue(this.fixedid+"pagesize");
   		return this.pageinfo.getAttribute("pagesize");
   	},
   	refreshPageList:function()
   	{
   		document.getElementById(this.fixedid+"_pagehint").innerHTML = this.pageinfo.getAttribute("currentpage")+"/"+this.pageinfo.getAttribute("pagecount")+"&nbsp;&nbsp;"+this.pageinfo.getAttribute("pagesize")+"条/页 &nbsp;&nbsp;共"+this.pageinfo.getAttribute("recordcount")+"条";
   	},
   	setRecordCount:function(cnt)
   	{   		
   		if(cnt!=null){
   			if(this.pageinfo!=null){
   		    this.pageinfo.setAttribute("recordcount",cnt);
   		    var pageCount = 1;
	   		if(cnt>0)
	   		{
	   			pageCount =cnt/(this.pageinfo.getAttribute("pagesize")*1);
	   		    if(pageCount!=parseInt(pageCount))
	   		       pageCount = parseInt(pageCount)+1;
	   		}
	   		this.pageinfo.setAttribute("pagecount",pageCount);
	   		this.refreshPageList();
   		   }
   		   else
   		   {   		   	
	   		    setControlValue(this.fixedid+"recordcount",cnt);
	   		    var pageCount = 1;
		   		if(cnt>0)
		   		{
		   			pageCount =cnt/(getControlValue(this.fixedid+"pagesize")*1);
		   		    if(pageCount!=parseInt(pageCount))
		   		       pageCount = parseInt(pageCount)+1;
		   		}
		   		setControlValue(this.fixedid+"pagecount",pageCount);   		   	
   		   }
   		}
   	},
   	getRecordCount:function()
   	{
   		var cnt =this.pageinfo!=null?this.pageinfo.getAttribute("recordcount"):getControlValue(this.fixedid+"recordcount");
   		return cnt==null||cnt==""?-1:cnt;
   	},
   	getPageBean:function(_curPage)
   	{
   		var curPage = _curPage==null?this.pageinfo.getAttribute("currentpage"):_curPage;
   		var ps = this.getPageSize();
   		return {recordcount:this.getRecordCount(),
   		        pagesize:ps,
   		        currentpage:this.pageinfo!=null?this.pageinfo.getAttribute("currentpage"):getControlValue(this.fixedid+"paginate_currentPage"),
   		        startrecord:(curPage*1-1)*ps,
   		        endrecord:(curPage*1)*ps,
   		        toUrlParameter:function(){return "recordcount="+this.recordcount+
   		                       "&pagesize="+this.pagesize+
   		                       "&currentpage="+this.currentpage+
   		                       "&startrecord="+this.startrecord+
   		                       "&endrecord="+this.endrecord}
   		        };
   	}
   };  
   //设置当前表格为打印样式
   this.setPrintStyle=function()
   {
   	   var r = this.tabelobj.rows;
   	   var rl = r.length;
   	   for(var i=0;i<rl; i++)
   	   {
   	   	   var l = r[i].cells.length;
   	   	   for(var c=0;c<l;c++)
   	   	   {
   	   	   	   if(i==rl-1 && c<l-1)
   	   	   	      ClassStyle.setClass(r[i].cells[c],"tdinput_print_bottom");
   	   	   	   else if(i==rl-1 && c==l-1)
   	   	   	      ClassStyle.setClass(r[i].cells[c],"tdinput_print_end");
   	   	       else if(i<rl-1 && c==l-1) //每行的最后一单元格(最后行除外)
   	   	          ClassStyle.setClass(r[i].cells[c],"tdinput_print_top");
   	   	       else
   	   	          ClassStyle.setClass(r[i].cells[c],"tdinput_print_none");
   	   	   }   	   	
   	   }
   }
   //打印前准备工作。主要是针对页面上的输入元素进行清除.需手动调用该方法
   this.printReady = function()
   {
      //清除input元素.不包括hidden
      var tags = new Array("INPUT","TEXTAREA");
      for(var e=0;e<tags.length;e++)
      {
	      var clearEles = this.tabelobj.getElementsByTagName(tags[e]); 
	      for(var i=0,len= clearEles.length;i<len;i++)
	      {
	      	  if(clearEles[0].type=="hidden")  
	      	  {
	      	  	clearEles[0].parentNode.removeChild(clearEles[0]);
	      	  	continue;
	      	  }
	      	  var contain = clearEles[0].parentNode;
	      	  var hv = contain.getAttribute("hiddenvalue");
	      	  var text = (hv!=null&&hv!=""&&hv!="null")?hv:clearEles[0].value;          
	          contain.innerHTML =text==""?"&nbsp":text; 
	      }
      }
   }
   this.print=function()
   {
       var printFrm = document.getElementById("_lli2_print_form");
       if(printFrm==null){
           printFrm = document.createElement("FORM");
           printFrm.id = "_lli2_print_form";
           printFrm.name = "_lli2_print_form";
           document.body.appendChild(printFrm);           
       }
       var printContent =document.getElementById("_lli2_print_content");
       if(printContent==null){
           printContent = document.createElement("input");
           printContent.type="hidden";
           printContent.id="_lli2_print_content";
           printContent.name="_lli2_print_content";
           printFrm.appendChild(printContent);
       }       
	   printContent.value = this.tabelobj.outerHTML;   
       var printWin = window.open("",
                   "printWin",
                   "width=20px,height=20px,top=3000px,left=3000px"); 
	   printFrm.target="printWin";    	
       printFrm.method="post";
       printFrm.action= this.context+"/includes/printTable.jsp?Encoding=UTF-8";	   	
       printFrm.submit();                    
                        
   }
   
   this.importExcel = function()
   {
			alert("");
   }   
   this.expExcel=function(title)
   {
   	   this.printReady();
   	   var iframe = document.getElementById("_lli2_exp_iframe");
   	   if(iframe==null)
   	   {
           try{   
               var iframe = document.createElement('<iframe name="_lli2_exp_iframe"></iframe>');
           }catch(e){
               var iframe = document.createElement('iframe');
               iframe.name = '_lli2_exp_iframe';
           }
           iframe.id = "_lli2_exp_iframe";
           iframe.width="0";
           iframe.height = "0";
           document.body.appendChild(iframe);   	       
   	   }
       var printFrm = document.getElementById("_lli2_print_form");
       if(printFrm==null){
           printFrm = document.createElement("FORM");
           printFrm.id = "_lli2_print_form";
           printFrm.name = "_lli2_print_form";
           document.body.appendChild(printFrm);           
       }       
       var printContent =document.getElementById("_lli2_exp_content");
       if(printContent==null){
           printContent = document.createElement("input");
           printContent.type="hidden";
           printContent.id="_lli2_exp_content"; 
           printContent.name="_lli2_exp_content";          
           printFrm.appendChild(printContent);
       }	   
       var printTable = this.tabelobj.cloneNode(true);
       printTable.border="1";
       for(var i=0;i<printTable.rows.length; i++)
        for(var c=0;c<printTable.rows[i].cells.length; c++)
        {
        	var cell = printTable.rows[i].cells[c];
            if(cell.title!=null && cell.title!="")
               cell.innerHTML = cell.title;
        }
	   printContent.value = printTable.outerHTML; 
	   printTable =null; 
       var expTitle =document.getElementById("_lli2_exp_title");
       if(expTitle==null){
           expTitle = document.createElement("input");
           expTitle.type="hidden";
           expTitle.id="_lli2_exp_title"; 
           expTitle.name="_lli2_exp_title";          
           printFrm.appendChild(expTitle);
       }	   
	   expTitle.value = title;	 
	   printFrm.target="_lli2_exp_iframe";  
       printFrm.method="post";
       printFrm.action=this.context+ "/includes/expTableExcel.jsp?type=xls&Encoding=UTF-8";	        		
       printFrm.submit();       
   }
   this.expWord=function(title)
   {
   	   this.printReady();
   	   var iframe = document.getElementById("_lli2_exp_iframe");
   	   if(iframe==null)
   	   {
           try{   
               var iframe = document.createElement('<iframe name="_lli2_exp_iframe"></iframe>');
           }catch(e){
               var iframe = document.createElement('iframe');
               iframe.name = '_lli2_exp_iframe';
           }
           iframe.id = "_lli2_exp_iframe";
           iframe.width="0";
           iframe.height = "0";
           document.body.appendChild(iframe);   	       
   	   }
       var printFrm = document.getElementById("_lli2_print_form");
       if(printFrm==null){
           printFrm = document.createElement("FORM");
           printFrm.id = "_lli2_print_form";
           printFrm.name = "_lli2_print_form";
           document.body.appendChild(printFrm);           
       }       
       var printContent =document.getElementById("_lli2_exp_content");
       if(printContent==null){
           printContent = document.createElement("input");
           printContent.type="hidden";
           printContent.id="_lli2_exp_content";   
           printContent.name="_lli2_exp_content";         
           printFrm.appendChild(printContent);
       }	   
       var printTable = this.tabelobj.cloneNode(true);
       printTable.border="1";
       for(var i=0;i<printTable.rows.length; i++)
        for(var c=0;c<printTable.rows[i].cells.length; c++)
        {
        	var cell = printTable.rows[i].cells[c];
            if(cell.title!=null && cell.title!="")
               cell.innerHTML = cell.title; 
        }
	   printContent.value = printTable.outerHTML; 
	   printTable =null;   
       var expTitle =document.getElementById("_lli2_exp_title");
       if(expTitle==null){
           expTitle = document.createElement("input");
           expTitle.type="hidden";
           expTitle.id="_lli2_exp_title";    
           expTitle.name="_lli2_exp_title";         
           printFrm.appendChild(expTitle);
       }	   
	   expTitle.value = title;	 
	   printFrm.target="_lli2_exp_iframe";  
       printFrm.method="post";
       printFrm.action=this.context+ "/includes/expTableExcel.jsp?type=doc&Encoding=UTF-8";	        		
       printFrm.submit();       
   }   
   /*
    * 行列冻结.表格的父容器格式中不能设置padding属性，否则滚动时padding区域会出现表格内容
    * */
this.cellFreeZe = function (iFrozenRowHead, iFrozenRowFoot, iFrozenColLeft)
{
	    var oFrozenTable = this.tabelobj;
        oFrozenTable.HeadRow = iFrozenRowHead;
        var oDivMaster = oFrozenTable.parentNode;
        if(oDivMaster.tagName != 'DIV') return;
        if(oDivMaster.parentNode.tagName=="DIV")
            if(oDivMaster.parentNode.style.position==null || oDivMaster.parentNode.style.position=="")
               oDivMaster.parentNode.style.position="relative";

        if(oDivMaster.style.position==null || oDivMaster.style.position=="")
               oDivMaster.style.position="relative";               
        //为主DIV附加onscroll事件
        if(oDivMaster.onscroll==null){
	        oDivMaster.onscroll=function(e)
	        {
	            var event = e||window.event;
			    var oDivMaster = event.srcElement||event.target;
			    if(document.getElementById(oFrozenTable.id+'HeadTar') != null)
			    {
			        document.getElementById(oFrozenTable.id+'HeadTar').style.left = - oDivMaster.scrollLeft+"px";
			    }
			    if(document.getElementById(oFrozenTable.id+'FootTar') != null)    
			    {
			        document.getElementById(oFrozenTable.id+'FootTar').style.left = - oDivMaster.scrollLeft+"px";
			    }
			    if(document.getElementById(oFrozenTable.id+'LeftTar') != null)    
			    {
			        document.getElementById(oFrozenTable.id+'LeftTar').style.top = - oDivMaster.scrollTop+"px";
			    }        	
	        }     
        }   
        var divBorderLeftWidth = "",divBorderTopWidth="";
        if(isIE)
        {
            divBorderLeftWidth=oDivMaster.currentStyle.borderLeftWidth.replace("px","");
            divBorderLeftWidth = divBorderLeftWidth==""?0:divBorderLeftWidth;
            divBorderTopWidth=oDivMaster.currentStyle.borderTopWidth.replace("px","");
            divBorderTopWidth = divBorderTopWidth==""?0:divBorderTopWidth;
        }
        else
        {
            divBorderLeftWidth=window.getComputedStyle(oDivMaster,null).getPropertyValue('border-left-width').replace("px","");
            divBorderLeftWidth = divBorderLeftWidth==""?0:divBorderLeftWidth;
            divBorderTopWidth=window.getComputedStyle(oDivMaster,null).getPropertyValue('border-top-width').replace("px","");
            divBorderTopWidth = divBorderTopWidth==""?0:divBorderTopWidth;
        }
        var element = null;//document.getElementById(oFrozenTable.id+"oTableLH");
        if((oFrozenTable.offsetHeight > oDivMaster.offsetHeight) && (oFrozenTable.offsetWidth > oDivMaster.offsetWidth))
        {
                //创建并克隆LeftHead表格
		        element = document.getElementById(oFrozenTable.id+"oTableLHTar");
		        if(element!=null)
		        {
		           if(isIE) element.removeNode(true);
		           else
		           {
		               element.innerHTML="";
		               element.parentNode.removeChild(element);
		           }
		        }                
                if((iFrozenColLeft > 0) && (iFrozenRowHead > 0 && this.showHeader==true))
                {
                        var oTableLH = document.createElement("TABLE");
                        var newTBody = document.createElement("TBODY");
                        oTableLH.insertBefore(newTBody,null);
                        oTableLH.id = oFrozenTable.id+"oTableLHTar";
                        oDivMaster.parentNode.insertBefore(oTableLH,null);
                        CloneTable(oFrozenTable, oTableLH, 0, iFrozenRowHead, iFrozenColLeft)
                        oTableLH.srcTable = oFrozenTable.id;
                        with(oTableLH.style)
                        {
                                zIndex = 804;
                                position = 'absolute'
                                left = (oDivMaster.offsetLeft+divBorderLeftWidth*1)+"px";
                                top = (oDivMaster.offsetTop)+"px";
                        }
                }

                //创建并克隆LeftFoot表格
		        element = document.getElementById(oFrozenTable.id+"oTableLFTar");
		        if(element!=null)
		        {
		           if(isIE) element.removeNode(true);
		           else
		           {
		               element.innerHTML="";
		               element.parentNode.removeChild(element);
		           }
		        }
                if((iFrozenColLeft > 0) && (iFrozenRowFoot > 0))
                {
                        var oTableLF = document.createElement("TABLE");
                        var newTBody = document.createElement("TBODY");
                        oTableLF.insertBefore(newTBody,null);
                        oTableLF.id = oFrozenTable.id+"oTableLFTar";
                        oDivMaster.parentNode.insertBefore(oTableLF,null);
                        CloneTable(oFrozenTable, oTableLF,oFrozenTable.rows.length - iFrozenRowFoot, oFrozenTable.rows.length, iFrozenColLeft)
                        oTableLF.srcTable = oFrozenTable.id;
                        with(oTableLF.style)
                        {
                                zIndex = 803;
                                position = 'absolute'
                                left = (oDivMaster.offsetLeft+divBorderLeftWidth*1)+"px";
                                top = (oDivMaster.offsetTop + oDivMaster.offsetHeight - oTableLF.offsetHeight - 16)+"px";
                        }
                }
        }
        element = document.getElementById(oFrozenTable.id+"HeadTar");
        //创建DivHead、创建并克隆HeadTar表格
        if(element!=null)
        {
           if(isIE) element.parentNode.removeNode(true);
           else
           {
               var pn = element.parentNode;
               pn.innerHTML="";
               pn.parentNode.removeChild(pn);
           }
        }        
        if((iFrozenRowHead > 0) && (oFrozenTable.offsetHeight > oDivMaster.offsetHeight)  && this.showHeader==true)
        {
                var DivHead = document.createElement("DIV");
                oDivMaster.parentNode.insertBefore(DivHead,null);
                var oTableHead = document.createElement("TABLE");
                var newTBody = document.createElement("TBODY");
                oTableHead.id = oFrozenTable.id+"HeadTar";                
                oTableHead.insertBefore(newTBody,null);
                DivHead.insertBefore(oTableHead,null);
                CloneTable(oFrozenTable, oTableHead, 0, iFrozenRowHead, -1)
                oTableHead.srcTable = oFrozenTable.id;
                oTableHead.style.position = "relative";
                with(DivHead.style)
                {
                        overflow = "hidden";
                        zIndex = 802;
                        width = (oDivMaster.offsetWidth - 17)+"px";
                        height = (oTableHead.offsetHeight)+"px";
                        position = 'absolute';
                        left = (oDivMaster.offsetLeft+divBorderLeftWidth*1)+"px";
                        top = oDivMaster.offsetTop+"px";
                }
                with(oTableHead.style)
                {
                        left = oFrozenTable.offsetLeft+"px";
                        top = oFrozenTable.offsetTop+"px";
                        width =    oFrozenTable.offsetWidth+"px";            	
                }
        }
        else if(element!=null && oFrozenTable.offsetHeight <= oDivMaster.offsetHeight) 
        	element.parentNode.style.display = 'none'; 
        	
        element = document.getElementById(oFrozenTable.id+"FootTar");
        //创建DivFoot、创建并克隆FootTar表格
        if(element!=null)
        {
           if(isIE) element.parentNode.removeNode(true);
           else
           {
               var pn = element.parentNode;
               pn.innerHTML="";
               pn.parentNode.removeChild(pn);
           }
        }        
        if((iFrozenRowFoot > 0) && (oFrozenTable.offsetHeight > oDivMaster.offsetHeight))
        { 
                var DivFoot = document.createElement("DIV");
                oDivMaster.parentNode.insertBefore(DivFoot,null);
                var oTableFoot = document.createElement("TABLE");
                var newTBody = document.createElement("TBODY");
                oTableFoot.insertBefore(newTBody,null);
                oTableFoot.id = oFrozenTable.id+"FootTar";                
                DivFoot.insertBefore(oTableFoot,null);
                CloneTable(oFrozenTable, oTableFoot, oFrozenTable.rows.length - iFrozenRowFoot, oFrozenTable.rows.length, -1)
                oTableFoot.srcTable = oFrozenTable.id;
                oTableFoot.style.position = "relative";
                with(DivFoot.style)
                {
                        overflow = "hidden";
                        zIndex = 801;
                        width = oDivMaster.offsetWidth - 17;
                        position =  'absolute'
                        left = oDivMaster.offsetLeft+oDivMaster.clientLeft;
                        top = oDivMaster.offsetTop + oDivMaster.offsetHeight - DivFoot.offsetHeight + oDivMaster.clientTop;
                        //borderWidth="0px";
                        //padding="0px";
                }
                with(oTableFoot.style)
                {
                        left = oFrozenTable.offsetLeft;
                        width =    oFrozenTable.offsetWidth;                	
                }        	            
        }
        else if(element!=null && oFrozenTable.offsetHeight <= oDivMaster.offsetHeight)         
            element.parentNode.style.display="none";

        //创建DivLeft、创建并克隆LeftTar表格
        element = document.getElementById(oFrozenTable.id+"LeftTar");
        if(element!=null)
        {
           if(isIE) element.parentNode.removeNode(true);
           else
           {
               var pn = element.parentNode;
               pn.innerHTML="";
               pn.parentNode.removeChild(pn);
           }
        }
        if((iFrozenColLeft > 0) && (oFrozenTable.offsetWidth > oDivMaster.offsetWidth))
        {
                var DivLeft = document.createElement("DIV");
                oDivMaster.parentNode.insertBefore(DivLeft,null);
                var oTableLeft = document.createElement("TABLE");
                var newTBody = document.createElement("TBODY");
                oTableLeft.insertBefore(newTBody,null);
                oTableLeft.id = oFrozenTable.id+"LeftTar";                
                DivLeft.insertBefore(oTableLeft,null);
                CloneTable(oFrozenTable, oTableLeft, 0, oFrozenTable.rows.length, iFrozenColLeft)
                oTableLeft.srcTable = oFrozenTable.id;
                oTableLeft.style.position = "relative";
                with(DivLeft.style)
                {
                        overflow = "hidden";
                        zIndex = 800;
                        width = oTableLeft.offsetWidth;
                        height = oDivMaster.offsetHeight - 17;
                        position =  'absolute';
                        left = (oDivMaster.offsetLeft+divBorderLeftWidth*1)+"px";
                        top = oDivMaster.offsetTop+"px";
                }
                oTableLeft.style.height =  oFrozenTable.clientHeight+"px";
        }
}   
   //返回当前选中的行索引数组
   this.currentSelectedRows = function(){
	   	var rows = new Array();
	   	for(var i=0; i<this.tabelobj.rows.length; i++)
	   	{
	   		if(Type.toBoolean(this.tabelobj.rows[i].getAttribute("checked")))
	   			rows[rows.length]=i;
	   	}
	   	return rows;
   	}    
   //返回当前选中的行索引串
   this.currentRowIndex = function(){
	   	var rows = this.currentSelectedRows();
	   	return rows.join(',');
   	} 
   	//返回当前选中行的指定列值
   	this.getSelectedValue = function(colID)
   	{
	   	var rows = new Array();
	   	for(var i=this.cloneRowIndex+1; i<this.tabelobj.rows.length; i++)
	   	{
	   		if(Type.toBoolean(this.tabelobj.rows[i].getAttribute("checked")))
	   		    if(colID==this.key)
	   		    rows[rows.length]=this.tabelobj.rows[i].getAttribute("keyvalue");
	   		    else
	   			rows[rows.length]=colID==null?i:this.getCellValue(i,colID);
	   	}
	   	return rows.join(',');   		
   	}
   	//更新数据知的状态为保存成功状态，一般在采用ajax方式保存数据后必须调用此方法
   	this.updateCommited =function()
   	{
	   	for(var i=this.cloneRowIndex; i<this.tabelobj.rows.length; i++)
	   	{
	   		this.tabelobj.rows[i].setAttribute("action","none");
	   	}
   	   if(this.dataSource==null || this.dataSource.length==null)return;  
	   for(var i=0; i<this.dataSource.length; i++)
	   {
	   	   if(this.dataSource[i]!=null) this.dataSource[i].action="none";
	   }	
	   document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES").value = "";   	
   	}
   //判断是否有保存行值的隐藏域
   var zerocell ;
   //创建用于保存详细数据的控件对象，以及存储原始数据的控件对象
   if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES")==null){
       zerocell = document.createElement("input");
       zerocell.type="hidden";
       zerocell.id=this.tabelobj.id+"_LLI2_DEF_DETAILVALUES";
       obj.rows[0].cells[0].appendChild(zerocell);
       var _LLI2_DEF_OLDVALUES = document.createElement("input");
       _LLI2_DEF_OLDVALUES.type="hidden";
       _LLI2_DEF_OLDVALUES.id=this.tabelobj.id+"_LLI2_DEF_OLDVALUES";
       obj.rows[0].cells[0].appendChild(_LLI2_DEF_OLDVALUES);       
   }
   //创建用于导出数据时存储列ID名与列名对应的控件对象
   if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_EXPORT_COLUMNMAPPING")==null)
   {
       var _LLI2_DEF_EXPORT_COLUMNMAPPING = document.createElement("input");
       _LLI2_DEF_EXPORT_COLUMNMAPPING.type="hidden";
       _LLI2_DEF_EXPORT_COLUMNMAPPING.id=this.tabelobj.id+"_LLI2_DEF_EXPORT_COLUMNMAPPING";
       obj.rows[0].cells[0].appendChild(_LLI2_DEF_EXPORT_COLUMNMAPPING);   	
   }
   this.exportColumnMappingCtrl = document.getElementById(this.tabelobj.id+"_LLI2_DEF_EXPORT_COLUMNMAPPING");

   this.copyRows = function(sourceRowIndex,targetIndex)
   { 
	   var copyrow = this.tabelobj.rows[sourceRowIndex];
	   var newrow = this.addRow(targetIndex);
	   for(var i=0; i<copyrow.cells.length; i++)
	   {
	   	   		var newcell = newrow.cells[i]; 
	   	   		if(newcell.getAttribute("edit")!='0')
	   	   			newcell.innerHTML = copyrow.cells[i].innerHTML;
	   	   		ClassStyle.setClass(newcell,ClassStyle.getClass(copyrow.cells[i]));
	    }   	  
   }
   //直接通过ctrl+v粘贴excel数据到表格中
   this.copyData = function(startRow,startCell,outerdata)
   { 
   		var rowno = startRow,cellno=startCell;
   	   		//判断行数
   	   		var trs = outerdata.split("\r\n"); 
   	   		for(var pos=0; pos<trs.length-1;pos++)
   	   		{
   	   			var tds = trs[pos].split("\t");
   	   			var insertCell = cellno;
   	   			for(var posi=0; posi<tds.length;posi++)
   	   			{
   	   				this.tabelobj.rows[rowno].cells[insertCell].innerHTML =  tds[posi]==""?"&nbsp;":tds[posi];
	   	   			insertCell++;
	   	   			if(this.tabelobj.rows[rowno].cells.length-1<insertCell) break;
   	   			}
	   	   		rowno++;
	   	   		if(this.tabelobj.rows.length-1<rowno) break;
   	   		}   	
   }
   //显示数据加载提示过程
   this.showLoading=function()
   {
   	   this.isShowLoadProcess=true;
   	   //判断当前表格的上层元素，非DIV元素则不处理
   	   var parentDiv = this.tabelobj.parentNode;
   	   if(parentDiv.tagName!="DIV") return;
   	   if(document.getElementById("_LLI2_LP_HINT_"+this.tabelobj.id)!=null)
   	   {
   	   	 document.getElementById("_LLI2_LP_HINT_"+this.tabelobj.id).style.display="";
   	   	 return;
   	   }
   	   var loadingDdiv = document.createElement("div");
   	   loadingDdiv.id="_LLI2_LP_HINT_"+this.tabelobj.id;
   	   loadingDdiv.style.position="absolute";
   	   loadingDdiv.style.color = "#C0C0C0";
   	   loadingDdiv.style.border="1px";
   	   loadingDdiv.style.top=parseInt(parentDiv.offsetHeight/2);
   	   loadingDdiv.style.left=parseInt(parentDiv.offsetWidth/2);
   	   loadingDdiv.style.height = parseInt(parentDiv.offsetHeight);
   	   loadingDdiv.style.width = parseInt(parentDiv.offsetWidth); 
   	   loadingDdiv.style.display="";
   	   //width:100px;border-style: solid;;border-width: 1px; padding-left: 1px; padding-right: 1px; padding-top: 1px; padding-bottom: 1px;">
	   var hint = document.createElement("span");
	   hint.style.fontSize="10pt";
	   hint.innerHTML = "正在获取数据中，请等待......";
	   loadingDdiv.appendChild(hint); 
	   parentDiv.appendChild(loadingDdiv);
   }
   //隐藏加载过程提示
   this.loadStop = function()
   {
   	  if(document.getElementById("_LLI2_LP_HINT_"+this.tabelobj.id)!=null)
   	  {
   	      var obj =document.getElementById("_LLI2_LP_HINT_"+this.tabelobj.id);
   	      obj.parentNode.removeChild(obj);
   	  }
   }
   
   /**
    * 数据导出时，自动生成列与标题之间的影射关系串
    */
   this.exportColMapping = function(excludeCols) {   	
   	    var columnname = "";
   	    var caption = "";
   		for(var i=0; i<this.Cols.length; i++)
   		{
   			if(excludeCols!=null && (","+excludeCols+",").indexOf(","+this.Cols[i].id+",")>-1)
   			   continue;
   			columnname += ","+this.Cols[i].id;
   			caption    += ","+this.Cols[i].name;
   		}
   		document.getElementById(this.tabelobj.id+"_LLI2_DEF_EXPORT_COLUMNMAPPING").value = columnname.substring(1)+"|"+caption.substring(1);
        return document.getElementById(this.tabelobj.id+"_LLI2_DEF_EXPORT_COLUMNMAPPING").value;
   }   
   this.getTotalValue = function(cellInfo)
   {
       var cellIndex =-1;
   	   cellIndex = isNaN(cellInfo*1)?this.ColsName[cellInfo].index:cellInfo;
   	   if(!this.isTatolRow) return;
   	   return getColValue(this.tabelobj,this.tabelobj.rows.length-1,cellIndex,null);
   }
   //初始化大量的行.将不受最大行数限制
 this.BigDataInit= function(rowcount,commit){	
 	this.Init(null);
	var container = document.createDocumentFragment(); 
	for(var c=0;c<rowcount;c++){
        var newrow = this.addRowFragment();
		container.appendChild(newrow);
	}
	//是否立即应用数据行
	if(commit)
	{
	        var i = 0;
	        t=this.tabelobj;   
	        while(i>=0)
	        {
	            if(t.childNodes[i].tagName=="TBODY")
	            {
	                t.childNodes[i].appendChild(container);
	                break;
	            }
	        }
	}
	return 	container;
   }
   //绑定大量的数据
   this.BigDataBind=function(commit){	
	   var container = document.createDocumentFragment(); 
	   var ds = this.dataSource;
	   var recordCount = ds.length;
	   var dsCol = new Array();
	   for(var i=0;i<recordCount;i++)
	   {
	      this.dataSource[i].action="none";
	   	  dsCol = ds[i];
          for(var col=0;col<this.Cols.length;col++)
		  {
		     var colObject = this.Cols[col];		  
		     index = colObject.index;//返回当前列在表格中的索引号
			 var colid = colObject.id;//返回当前列的列名 
			 var permitType = colObject.permitColType;//取出当前列的操作属性 readonly notsee等..
			 if(index>-1)
			 {
				 //返回指定列的当前行值
				 var valuetemp =dsCol[colid]||dsCol[colid.toUpperCase()];
				 if(valuetemp==null) continue;
				 if(valuetemp=="null"||valuetemp=="") valuetemp="&nbsp;";
				 //格式化值
				 if(colObject.format!="")
				 {
				 	if(colObject.format=="datetime") valuetemp=valuetemp.replace(/(\.0)/g,"");
				 	else if(colObject.format=="date") valuetemp=valuetemp.split(" ")[0];
				 	else if(colObject.format=="time") valuetemp=valuetemp.split(" ")[1];
				 	else if(colObject.format=="int") valuetemp=round(valuetemp*1,0);			 	
				 	else if(colObject.format=="number") valuetemp=round(valuetemp*1,decimal);			 	
				 }
				 dsCol[colid] = valuetemp;
				 if(colObject.isDownList && valuetemp.indexOf(",")==-1)
				 {
				     for(var k=0;k<colObject.listData.length; k++)
				     {
				         if(colObject.listData[k].split(",")[1]==valuetemp){
				             valuetemp = colObject.listData[k];
				             dsCol[colid] = valuetemp;
				             break;
				         }
				     }
				 }				 
			 }
		  }	   	  
	      var rs = this.addRowFragment(dsCol);//添加一个新行.默认为无操作。操作分为：insert(新增行)、eidt(编辑行)、delete(删除行)	  
	      //为当前对象设置操作状态为none
      rs.setAttribute("action","none");
      rs.setAttribute("oldValue",dsCol);
	  if(this.key!=null && this.key!="")
	  {
	  	var keyV = getDataSourceValue(dsCol,this.key);
	  	this.dataKeyMap[keyV]=i;
	  	rs.setAttribute("oldValue",dsCol);
	  	rs.setAttribute("keyvalue",keyV);
	  }
	  else
	  {
	     var oldvalue = [];
	     for(var v in dsCol)
	        	oldvalue.push(dsCol[v]);
	     var keyV = oldvalue.join(",");
       this.dataKeyMap[keyV]=i;
	     rs.setAttribute("oldValue",keyV);
	  }
		  
		  container.appendChild(rs);
	   }
	   this.isAddRow = false;//数据绑定时不设置新行标志	
		//是否立即应用数据行
		if(commit)
		{
		        var i = 0;
		        t=this.tabelobj;   
		        while(i>=0)
		        {
		            if(t.childNodes[i].tagName=="TBODY")
		            {
		                t.childNodes[i].appendChild(container);
		                break;
		            }
		        }
		}
		if(this.isShowLoadProcess)
			this.loadStop();
		return 	container;
   }   
   this.getDeleteRowsKey=function()
   {
   	   if(this.dataSource==null || this.dataSource.length==null)return null;
	   var deleteRows = new Array();
	   //获取删除的行
	   for(var i=0; i<this.dataSource.length; i++)
	   {
	   	   if(this.dataSource[i]!=null && this.dataSource[i].action=="delete")
	   	   {
	   	      if(this.key!=null && this.key!="")
	   	          deleteRows[deleteRows.length] = this.dataSource[i][this.key];
	   	      else
	   	      {
	   	          var rowinfo=[];
	   	          for(var col in this.dataSource[i])
	   	          {
	   	              rowinfo[rowinfo.length]= col+":\""+this.dataSource[i][col]+"\"";
	   	          }
	   	          deleteRows[deleteRows.length] ="{"+ rowinfo.join(",")+"}";
	   	      }
	   	   }
	   }
	   return deleteRows;
   }   
   this.getCellValue = function(rowIndex,cellInfo)  //获取指定ID的单元格的值
   {
       var cellIndex =-1;
   	   cellIndex = isNaN(cellInfo*1)?this.ColsName[cellInfo].index:cellInfo;
   	   var resulr = getColValue(this.tabelobj,rowIndex,cellIndex,null);
   	   return resulr==""?"&nbsp;":resulr;
   }
   this.setCellValue = function(rowIndex,cellInfo,value)  //设置指定ID的单元格的值
   {
       var cellIndex =-1;
   	   cellIndex = isNaN(cellInfo*1)?this.ColsName[cellInfo].index:cellInfo;
   	   if(this.tabelobj.rows[rowIndex].cells[cellIndex].children.length==0)
   	   		selectedValue(this.tabelobj.rows[rowIndex].cells[cellIndex],value,null);
   	   else
   	       selectedValue(this.tabelobj.rows[rowIndex].cells[cellIndex].children[0],value,null);
   }         
   this.pageObject.setPageSize(this.count-1);
   this.setAttribute=function(attObj)
   {
       for(var key in attObj)
          eval("this."+key+"=attObj."+key);
       if(attObj.cloneRowIndex!=null)
          this.setCloneRowIndex(attObj.cloneRowIndex);
   }    
   classObject[classObject.length] = this;//创建对象的外部引用
}


function setDecimal(v)
{
	if(v==null||v=="")
	{
	   v=3;
	}
    decimal = v;
	
}

function table_init(fixedRows)
{
	if(fixedRows!=null)
	{
		if(!confirm("警告：当前页的数据会被清空，你确认已成功保存当前数据了吗？"))
		{
			return;
		}
	}
   var saveRowCount = 0;
   if(this.isTatolRow==true)
   {
      saveRowCount  = this.tabelobj.rows.length-1;
   }
   else
   {
      saveRowCount  = this.tabelobj.rows.length;
   }
   //如果添加了新行则要读取的总行数还要在扣除合计行的基础上再减１
   if(this.isAddRow==true)
   {
      //saveRowCount--;
   }
   this.tabelobj.clickedRowIndex=-1;
   var tableobj = this.tabelobj;
   var row = this.cloneRowIndex+1;
   for(var i=row;i<saveRowCount;i++)
   {	   
	   tableobj.deleteRow(row);
   }
   if(fixedRows!=null)
   {
   		for(var i=0; i<this.count; i++)
   		 	this.addRow(null);
   } 
   if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES")!=null)
   {
	   document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES").value = "";
	   document.getElementById(this.tabelobj.id+"_LLI2_DEF_OLDVALUES").value = "";  
   }
   this.currentAddRowCount=0;
   this.dataKeyMap = [];
}


//只供对象内部调用，对外方法名称为getSaveValues()。
//根据设置的保存列返回列名及值的数组对象。元素返回一个字符串。列名1,值1,列名2,值2,......
function getValues()
{	
	if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES")==null)
	{
       var zerocell = document.createElement("<input type='hidden' id='"+this.tabelobj.id+"_LLI2_DEF_DETAILVALUES' name='"+this.tabelobj.id+"_LLI2_DEF_DETAILVALUES'>");
       this.tabelobj.rows[0].cells[0].appendChild(zerocell);	
	}
   if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES").value=="")
      this.getSaveValuesToString();
   return document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES").value;
}

function getDataSourceValue(data,id)
{
	return data[id]||data[id.toUpperCase()];
	for(var colSize=0;colSize<data.length;colSize++)
	{		
		  var dataCol = data[colSize];
          if(dataCol[0]==id)
	      {
	        return dataCol[1];
	      }
	}
    return "";
}

//只供对象内部调用，对外方法名称为DataBind()。
//将指定的数据源绑定到表格
//列对象的新建顺序一定要和数据源中的列顺序相同
function databind(begin,end)
{
   if(this.form!=null)
   {
   	   this.form.setDataSource(this.dataSource);
   	   return this.form.dataBind();
   }   
   if(this.dataSource==null)
   {
   	  this.loadStop();
      return;
   } 
   var tableobj = this.tabelobj;//返回表格对象
   var ds = this.dataSource;
   var recordCount = ds.length;
   var dsCol = new Array();
   if(this.pageObject!=null)this.pageObject.setRecordCount( this.dataSource.length);
   var startLoad = 0,endLoad = recordCount;
   if(arguments.length==2)
   {
      startLoad = begin;
      endLoad = end;
   }
   else if(this.pageObject!=null&&this.pageObject.pageinfo!=null)
      endLoad = this.pageObject.getPageSize();
   this.currentAddRowCount=startLoad;
   endLoad = ds.length<endLoad?ds.length:endLoad;
   for(var i=startLoad;i<endLoad;i++)
   {  
      dsCol = ds[i];
      var isSpaceObject=false,t;
      for(t in dsCol)
      {
        if(t==undefined || t==null)
           break;
      }
      if(t==undefined || t==null)
         isSpaceObject=true;
      if(isSpaceObject || dsCol==null || !(dsCol instanceof Object) || ((dsCol instanceof Object) && (dsCol instanceof Array))) continue;
      //为当前对象设置操作状态为none
      ds[i].action="none";   	
      var rs = this.addRow(null);//添加一个新行.默认为无操作。操作分为：insert(新增行)、eidt(编辑行)、delete(删除行)
      if (rs == undefined){
      	  break;
      }
      rs.setAttribute("action","none");
      rs.setAttribute("oldValue",dsCol);
	  var index;
	  if(this.key!=null && this.key!="")
	  {
	  	var keyV = getDataSourceValue(dsCol,this.key);
	  	this.dataKeyMap[keyV]=i;
	  	rs.setAttribute("oldValue",dsCol);
	  	rs.setAttribute("keyvalue",keyV);
	  }
	  else
	  {
	     var oldvalue = [];
	     for(var v in dsCol)
	        	oldvalue.push(dsCol[v]);
	     var keyV = oldvalue.join(",");
       this.dataKeyMap[keyV]=i;
	     rs.setAttribute("oldValue",keyV);
	  }
	  for(var col=0;col<this.Cols.length;col++)
	  {
	     var colObject = this.Cols[col];
	     var hasCallback = (colObject.callback!=null && colObject.callback!="");
	     index = colObject.index;//返回当前列在表格中的索引号
		 var colid = colObject.id;//返回当前列的列名 
		 var permitType = colObject.permitColType;//取出当前列的操作属性 readonly notsee等..
		 if(index>-1)
		 {
		 	var curcell = rs.cells[index];
			 //返回指定列的当前行值
			 var valuetemp =replaceDefault(getDataSourceValue(dsCol,colid));			 
			 if(valuetemp==null) {
			     if(hasCallback)
			     {
			     	colObject.callback(curcell,dsCol);
			     }			
				 if(colObject.href!=null)
				 {
				      var hrefInnerHtml = "";
				      if(typeof(colObject.href) =="object" )
				      {
				         var words = (colObject.href.url||colObject.href.click).match(/ROWDATA\.[A-Za-z0-9_]*/g);
				         hrefInnerHtml = "<a ";
				         if(colObject.href.url!=null&&colObject.href.url!="")
				         {
				            hrefInnerHtml += " href= \""+colObject.href.url + "\"";
				         }
				         if(colObject.href.click!=null&&colObject.href.click!="")
				         {
				            hrefInnerHtml += " style='cursor:pointer' onclick=\"" +colObject.href.click +"\"";
				         }
				         if(words!=null && words.length>0)
                         {
				                for(var ip=0;ip<words.length; ip++)
				                {
				                   hrefInnerHtml = hrefInnerHtml.replace(words[ip],eval(words[ip].replace("ROWDATA","dsCol")));
				                }
				         }
				         if(colObject.href.target!=null&&colObject.href.target!="")
				            hrefInnerHtml += " target="+colObject.href.target;
				         if(colObject.href.className!=null&&colObject.href.className!="")
				            hrefInnerHtml += " class=\""+colObject.href.className+"\"";
				         if(colObject.href.cssText!=null && colObject.href.cssText!="")
				            hrefInnerHtml += " style=\""+colObject.href.css+"\"";				            
				         hrefInnerHtml += ">";   
				      }
				      else
				         hrefInnerHtml = "<a href=\""+eval("\""+colObject.href.replace(/ROWDATA/g,"\"+dsCol")+"+\""+"\"")+"\">";
				      curcell.innerHTML = hrefInnerHtml+curcell.innerHTML+"</a>";
				 }			      	
			 	 continue;
			 }
			 if(valuetemp=="null"||valuetemp=="") valuetemp="&nbsp;";
			 //格式化值
			 if(colObject.format!="")
			 {
			 	if(colObject.format=="datetime") valuetemp=valuetemp.replace(/\.0/g,"");
			 	else if(colObject.format=="date") valuetemp=valuetemp.split(" ")[0];
			 	else if(colObject.format=="time") valuetemp=valuetemp.split(" ")[1];
			 	else if(colObject.format=="int") valuetemp=round(valuetemp*1,0);			 	
			 	else if(colObject.format=="number") valuetemp=round(valuetemp*1,decimal);			 	
			 }
			 var titleValeu = valuetemp;  
			 curcell.innerHTML="";
			 if(curcell.children.length==0)
			 {
			 	var div = document.createElement("div");
			 	div.style.width =colObject.width;
			 	ClassStyle.setClass(div,"textNoBR");
			 	curcell.appendChild(div);
			 }
			 if(colObject.isDownList && valuetemp.indexOf(",")==-1)
			 {
			     for(var k=0;k<colObject.listData.length; k++)
			     {
			         if(colObject.listData[k].split(",")[1]==valuetemp){
			             valuetemp = colObject.listData[k];
			             break;
			         }
			     }
			 }
			 if(rs.cells[index]!=null)
			    selectedValue(curcell.children.length>0?curcell.children[0]:curcell,valuetemp,permitType);
			 else				 
		         selectedValue(curcell.children.length>0?curcell.children[0]:curcell,valuetemp,permitType);
		     curcell.title = valuetemp=="&nbsp;"?"":replaceDefault(titleValeu);
		     curcell.setAttribute("hiddenvalue",curcell.children.length>0?curcell.children[0].getAttribute("hiddenvalue"):curcell.getAttribute("hiddenvalue"));
			 if(hasCallback)
			 {
			     colObject.callback(curcell,dsCol);
			 }
			 if(colObject.href!=null)
			 {
				      var hrefInnerHtml = "";
				      if(typeof(colObject.href) =="object" )
				      {
				         var words = (colObject.href.url||colObject.href.click).match(/ROWDATA\.[A-Za-z0-9_]*/g);
				         hrefInnerHtml = "<a ";
				         if(colObject.href.url!=null&&colObject.href.url!="")
				         {
				            hrefInnerHtml += " href= \""+colObject.href.url + "\"";
				         }
				         if(colObject.href.click!=null&&colObject.href.click!="")
				         {
				            hrefInnerHtml += " style='cursor:pointer' onclick=\"" +colObject.href.click +"\"";
				         }
				         if(words!=null && words.length>0)
                         {
				                for(var ip=0;ip<words.length; ip++)
				                {
				                   hrefInnerHtml = hrefInnerHtml.replace(words[ip],eval(words[ip].replace("ROWDATA","dsCol")));
				                }
				         }
				         if(colObject.href.target!=null&&colObject.href.target!="")
				            hrefInnerHtml += " target="+colObject.href.target;
				         if(colObject.href.className!=null&&colObject.href.className!="")
				            hrefInnerHtml += " class=\""+colObject.href.className+"\"";
				         if(colObject.href.cssText!=null && colObject.href.cssText!="")
				            hrefInnerHtml += " style=\""+colObject.href.css+"\"";				            
				         hrefInnerHtml += ">";   
				      }
				      else
				         hrefInnerHtml = "<a href=\""+eval("\""+colObject.href.replace(/ROWDATA/g,"\"+dsCol")+"+\""+"\"")+"\">";
				      curcell.children[0].innerHTML = hrefInnerHtml+curcell.children[0].innerHTML+"</a>";
				 }			 		 
		 }
	  }
   }
   this.isAddRow = false;//数据绑定时不设置新行标志
   //数据绑定后，立即收集一原始数据，做为数据保存进的对比依据
   //document.getElementById("_LLI2_DEF_OLDVALUES").value= this.getSaveValuesToString();   
   if(this.autoFreeze!=null)
   {
       this.cellFreeZe(this.autoFreeze.row!=null?this.autoFreeze.row:0,
                       this.autoFreeze.foot!=null?this.autoFreeze.foot:0,
                       this.autoFreeze.col!=null?this.autoFreeze.col:0);
   }
   if(this.isShowLoadProcess)
		this.loadStop();  
	 if(this.DataBindAfter!=null) 
	    this.DataBindAfter();
}
//更新指定的行的数据
//vIndex:要更新的行索引
//dataObj：数据对象。和列对象顺序相同的一维数组。
function updaterowdata(vIndex,dataObj)
{
	var tableobj = this.tabelobj;//返回从表表格对象
    var dsCol = dataObj;
	var index;
	for(var col=0;col<this.Cols.length;col++)
	{
       //删除列不进行数据填充
	   if(tableobj.rows[vIndex].cells[col].children[0].getAttribute("hiddenvalue")=="none"||tableobj.rows[vIndex].cells[col].children[0].innerText=="删除") continue;
	   index = this.Cols[col].index;//返回当前列在表格中的索引号
	   if(index>-1) selectedValue(tableobj.rows[vIndex].cells[index].children[0],dsCol[col],null);
	}
}

//取得指定编辑行的数据并填充到相应的控件
//vIndex:编辑的行索引
//editTableName：编辑的表格名称
function getrowdata(vIndex,editTableName)
{
   
}

//保存指定编辑行的数据
//vIndex:编辑的行索引
//editTableName：编辑的表格名称
function saverowdata(vIndex,editTableName)
{
}

//根据指定的列名在指定的表格对象中查找列。返回列索引，未找到返回-1。只供对象内部调用，无对外方法实现。
//tableobj:表格对象
//colname:　列名
function getIndex(tableobj,colname)
{
	var tableObj = getObjectRef(tableobj.id);
   for(var i=0;i<tableobj.rows[tableObj.cloneRowIndex].cells.length;i++)
   {
      if(tableobj.rows[tableObj.cloneRowIndex].cells[i].id==colname) return i;
   }
   return -1;
}

//根据指定的列名在指定的表格对象中查找列。返回列名称，未找到返回空串。只供对象内部调用，无对外方法实现。
//tableobj:表格对象
//index:　 列的索引
function getCode(tableobj,index)
{
   var tableObj = getObjectRef(tableobj.id);
   var fieldCode = "";
   for(var i=0;i<tableobj.rows[tableObj.cloneRowIndex].cells.length;i++)
   {
      if(index==i) fieldCode = tableobj.rows[tableObj.cloneRowIndex].cells[i].id;
   }
   return fieldCode;
}

//指定数据列表选中指定的值或向控件输出文本。只供对象内部调用，无对外方法实现。
//listObj:控件对象
//selValue:值
function selectedValue(listObj,selValue,permitType)
{
   //selValue = selValue.replace(/&cma;/,",").replace(/&cma;/,",");
   var values = selValue.split(",");
   var valuetemp = values[0];
   if(valuetemp=="null"||valuetemp=="") valuetemp="&nbsp;";
   var opobj = listObj;
   if(opobj.type==null||opobj.type.indexOf("select")==-1)
   {
      if(listObj.children[0]!=null) opobj = listObj.children[0];
   }
   if(opobj.type!=null)
   {
		//循环读取数组中的元素,如果与页面上表单元素同名,判断权限
		if (permitType==null || permitType=="")
 	    {
		   if(opobj.type.indexOf("select")>-1)
		   {
		   	  selValue = (selValue);
			  var valueXX = selValue;
			  if(selValue.lastIndexOf(",")>-1)  valueXX = (valueXX.substring(selValue.lastIndexOf(",")+1)).replace(/&cma;/g,",");
			  for(var i=0;i<opobj.length;i++)
			  {	
				 if(opobj.options[i].value==valueXX)
				 {
					opobj.options[i].selected = true;
					break;
				 }
			  }
		   }else{
			   if(opobj.type.indexOf("radio")>-1 || opobj.type.indexOf("checkbox")>-1)
			   {
			   	selValue = (selValue);
				  for(var i=0;i<listObj.children.length;i++)
				  {
					 if(listObj.children[i].value==selValue)
					 {
						listObj.children[i].checked = true;
						break;
					 }
				  }
			   }else{
			   	   if(opobj.type=="textarea")
			   	   {
			   	   	        var realValue = (selValue);
							opobj.value =realValue;
			   	   }
				   else if(opobj.type=="text")
				   {
						if(values.length==2)
						{					  
							opobj.value = valuetemp;
							opobj.setAttribute("hiddenvalue",values[1]);
						}
						else 
						{
							var realValue = (selValue);
							opobj.value =realValue; 
						}
				   }
				   else
				   {
						if(values.length==2)
						{					  								  
							opobj.innerHTML = valuetemp;
							opobj.setAttribute("hiddenvalue", values[1]);
						}
						else 
							opobj.innerHTML = (selValue);
				   }
			   }
		   }
			
	    }
		if (permitType=="readonly")
		{
		   if(opobj.type.indexOf("select")>-1)
		   {
		   	  selValue = (selValue);
			  for(var i=0;i<opobj.length;i++)
			  {		
				 if(opobj.options[i].value==selValue)
				 {
					listObj.innerHTML = opobj.options[i].text;
					break;
				 }
			  }
		   }else{
			   if(opobj.type.indexOf("radio")>-1 || opobj.type.indexOf("checkbox")>-1)
			   {
			   	  selValue = (selValue);
				  for(var i=0;i<listObj.children.length;i++)
				  {		
					 if(listObj.children[i].value==selValue)
					 {
						listObj.innerHTML = listObj.children[i].getAttribute("hiddenvalue");
						break;
					 }
				  }
			   }else{
			   	   if(opobj.type=="textarea")
			   	   {
			   	   	        var realValue = (selValue);
							opobj.value =realValue;
			   	   }
				   else  if(opobj.type=="text")
				   {
						if(values.length==2)
						{					  
							opobj.value = valuetemp;
							opobj.setAttribute("hiddenvalue",values[1]);
						}
						else 
							opobj.value = (selValue);
				   }
				   else
				   {
						if(values.length==2)
						{												  
							opobj.innerHTML = valuetemp;
							opobj.setAttribute("hiddenvalue", values[1]);
						}
						else 
							opobj.innerHTML = (selValue); 
				   }
			   }
		   }
		}
   }
   else
   {
      if(values.length==2)
	  {
	  	 var values = selValue.split(",");
	  	 var valuetemp = values[0];
	  	 if(valuetemp=="null"||valuetemp=="") valuetemp="&nbsp;";
	     opobj.innerHTML = valuetemp;
		 opobj.setAttribute("hiddenvalue",values[1]);
      }
	  else 
	  {
          //opobj.innerHTML = selValue;
	      if(isIE)
	          opobj.innerHTML = selValue;
	      else
	      {
	          var w = opobj.clientWidth;
	          w = w==0 ? opobj.style.width.replace("px",""):w;
	          w=parseInt(w/12);
	          var text = [],t=w*2-2,j=0,l=selValue.length;
	          for(var i=1;i<=t;i++)
	          {
	             if(i>l) break;
	             text[j] = selValue.substring(i-1,i);	             
	             if(selValue.charCodeAt(i)>255)
	                t--;
	             j++;
	          }
	          opobj.innerHTML = text.join("")+(text.length<l?"..":"");
	      }      
      }
   }
}

function AddTatol(str,str1,css,caption)
{
   if(!isIE) return;
   var tableobj = this.tabelobj;
   this.isTatolRow = true;
   var newrow = tableobj.insertRow(tableobj.rows.length);
   newrow.setAttribute("edit","0");
   var firstRow = tableobj.rows[this.cloneRowIndex];
   var cellobj;
   var L = 0;
	if(this.createRowFlagColumn)
	{
        	cellobj = document.createElement("TD");
			newrow.appendChild(cellobj);
			ClassStyle.setClass(cellobj,"Etd_rowheader");
			cellobj.id="_FLAG_COLMUN";
			cellobj.style.cursor="pointer"; 
			cellobj.innerHTML="&nbsp;";
			L = 1;
	}   
   for(var i=L;i<firstRow.cells.length;i++)
   {
      cellobj = newrow.insertCell(i);
      cellobj.setAttribute("edit","0");
	  cellobj.setAttribute("align","center");
	  if(css!="")  
	      ClassStyle.setClass(cellobj,css);
	  else
	      ClassStyle.setClass(cellobj,"Etd_tatol");
	  if(i==1) cellobj.innerHTML="<div id='div"+i+"'  contentEditable='false' ondragenter='return false;'>"+(caption==null||caption==""?"合  计":caption)+"</div>";
 	   else if(str.indexOf(firstRow.cells[i].id)==-1) 
	      cellobj.innerHTML="<div id='div"+i+"'  contentEditable='false' ondragenter='return false;'>"+str1+"</div>";
	   else
	   {
	      cellobj.innerHTML="<div id='div"+i+"'  contentEditable='false' ondragenter='return false;'></div>";
		  cellobj.children[0].setExpression("innerHTML","getTatol("+i+",'"+tableobj.id+"')");
	   }
   }
}
//只供对象内部调用，对外方法名称为getSaveValuesToString()。
//根据设置的保存列返回列名及值的一个字符串。列名1,值1,列名2,值2,......;列名1,值1,列名2,值2,......;....
function getValuesToString()
{
   if(this.form!=null)
   {
   	   return this.form.getSaveElementValue();
   }
   var result = "";
   var resultValue;
   var colname = "";
   var saveRowCount = 0;
   if(this.isTatolRow==true) 
      saveRowCount  = this.tabelobj.rows.length-1; 
   else 
      saveRowCount  = this.tabelobj.rows.length; 
   var saveColsCount = 0; //所有需要保存的列数.用于判断当前行是否进行过编辑
   var defaultColsCount = 0;//设置了默认值的列数。用于判断当前行是否进行过编辑
   var insertRows = new Array();
   var editRows = new Array();
   for(var i=this.cloneRowIndex+1;i<saveRowCount;i++)
   {
   	  var hint = {};
   	  saveColsCount = 0;
   	  defaultColsCount = 0;
      resultValue = new Array();
      var keyValue = this.tabelobj.rows[i].getAttribute("keyvalue");
      //判断并取得主键列的值
      if(this.key!=null && this.key!="" && keyValue!=null)
         resultValue[resultValue.length] =  this.key+":\"" + keyValue +"\"";
      var action = this.tabelobj.rows[i].getAttribute("action");//获取操作标识(insert\edit\delete\none)
      if(action=="none") //未作更改时不保存
         continue;
      var cellCount = this.tabelobj.rows[i].cells.length;
      for(var pos=0;pos<cellCount;pos++)
	  {
	  	  var cellId = this.tabelobj.rows[this.cloneRowIndex].cells[pos].id;
		  if(cellId==null||cellId=="")
		     continue;
		  if(this.saveCols!=null&&this.saveCols.indexOf(cellId)==-1)
             continue;
		  var colObj = this.ColsName[cellId];//取得列对象的定义
		  if(colObj==null)   continue; 
		  if(!colObj.isSave)  continue; 
		  if(colObj.text!="")  defaultColsCount++;
		  var v = "";
		  if(colObj.isAreaText==true)
		  {
		      v = replace(this.tabelobj.rows[i].cells[pos].title);
		  }
		  else
		  {
			  //判断有没有属性hiddenColName.有则要保存其他隐藏值
			  var hiddenCol = colObj.hiddenColName;
			  if(hiddenCol!=null&&hiddenCol!="")
					v = getColValue(this.tabelobj,i,pos,hiddenCol);
			  else
					v = getColValue(this.tabelobj,i,pos,null);
		  }
		  //检查当前字段是否设置了数据校验
		  if(colObj.datacheck!=null && colObj.datacheck!="")
		  {
		  	  var checkObjData = new Object();
		  	  checkObjData.value = v;
		  	  checkObjData.rowIndex = i;
		  	  checkObjData.cellIndex = pos;
		  	  checkObjData.id = cellId;
		  	  var checkresult = colObj.datacheck(checkObjData);
		  	  if(!checkresult) return "";
		  }
		  //检查当前字段的值是否为空且是否设置了不能为空检验.
		  var nullcol = ","+this.notnull+","; 
		  v = v.replace(/(\u00a0)/g," ");
		  if(v.length==1 && v.charCodeAt(0)==160)
		     v = "";
	      if((v==""||v==" ") && nullcol.indexOf(","+cellId+",")>-1)
	      {
		      hint = {row:i-this.cloneRowIndex,col:pos,attrname:cellId,msg:colObj.name+" 不能为空",toString:function(){return this.msg+"(第"+this.row+"行 "+this.col+"列)"}};
	      }
	      resultValue[resultValue.length] = cellId+":\"" + $.trim(v) +"\""; 
	      saveColsCount++;     
	  }	  
	  result = "{"+resultValue.join(",") + "}";
	  var regx=/""/g;
	  //判断当前行是否输入了数据。如果都没输入数据，则当前行不保存
	  if(result.match(regx)!=null&&result.match(regx).length==(saveColsCount-defaultColsCount))
	  {
	  	continue;
	  }
	  if(hint.msg!=null)
	  {
	  	if(this.ErrorHint==null) alert(hint);
	  	else this.ErrorHint(hint.row,hint.attrname,hint.msg);
	  	return "";
	  }
	  if(this.key!=null&& this.key!="")
	  {
	  	//如果指定主键列，则根据当前行是否拥有主键值判断操作类型
	  	if(keyValue==null || keyValue=="")
	  	   insertRows[insertRows.length] = result;  //没主键值则认定为新增行
	  	else
	  	   editRows[editRows.length] = result;
	  }
	  else
	  {
	  	  //如果没有指定主键列，则根据行状态判断操作类型
		  if(action=="insert")
		     insertRows[insertRows.length] = result;
		  else if(action=="edit")
	         editRows[editRows.length] = result;
	  }
   }
   var saveResult = "primarykey:\""+this.key+"\"";
   var deleteRows = this.getDeleteRowsKey();
   if(deleteRows!=null && deleteRows.length>0)
   {
       if(this.key!=null && this.key!="")
           saveResult += ",deleterows:\""+deleteRows.join(",")+"\"";
       else
           saveResult += ",deleterows:["+deleteRows.join(",")+"]";
   }
   if(insertRows.length>0)
   {
       saveResult += ",insertrows:["+insertRows.join(",")+"]";
   }
   if(editRows.length>0)
   {
       saveResult += ",editrows:["+editRows.join(",")+"]";
   }
	if(document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES")==null)
	{
       var zerocell = document.createElement("input");
       zerocell.type="hidden";
       zerocell.id=this.tabelobj.id+"_LLI2_DEF_DETAILVALUES";
       this.tabelobj.rows[0].cells[0].appendChild(zerocell);	
	}   
   document.getElementById(this.tabelobj.id+"_LLI2_DEF_DETAILVALUES").value = saveResult;
   return eval("({"+saveResult+"})");
}

function getColValue(tabelobj,rowindex,colindex,hiddenColName)
{
	var resultValue = "";
	//判断各种情况。
	//通过读取控件的hiddenvalue属性来判断是否是隐藏域。是则直接采用其hiddenvalue属性值	
	//取得单元格中的第一个层中的第一个控件
	var control = tabelobj.rows[rowindex].cells[colindex].children[0];
	//如果单元格中只包含一个层时，则获取当前层
	if(control==null) 
		control = tabelobj.rows[rowindex].cells[colindex]; 
	if(control==null)   return ""; 
	//判断控件类型
	var isType = null;
	if(control!=null)
	{
       isType = control.type;
	   if(isType==null)  isType=""; 
	}
	var hv = null;
	try{
	    hv = control.getAttribute("hiddenvalue");
	}
	catch(e){}
	if(control!=null&&isType.indexOf("select")==-1&&hv!=null&&hv!="")
	{
	   //有隐藏域的情况且要保存隐藏值和显示值时
	   //为了只保存隐藏值的情况所以在这时是交叉保存。用指定的列名来保存其隐藏值，而用指定的隐藏值保存列名来保存其显示值.
       if(hiddenColName!=null&&hiddenColName!=""&&hiddenColName!="[out]")
	      resultValue = replace(getControlValue(control))+"\","+hiddenColName+":\""+replace(hv);
	   //如果明确指定隐藏值做为直接输出处理时，则文本与隐藏值同时返回并以逗号间隔
	   else if(hiddenColName=="[out]")
	      resultValue = replace(getControlValue(control))+","+replace(hv);
	   else
		  //有隐藏域的情况只保存隐藏值时
          resultValue = replace(hv);				 
	}
	else
        resultValue=replace(getControlValue(control)); 
	return resultValue;
}

//向列集合添加一个列对象。只供对象内部调用，对外方法名称为addCol()。
//col:一个由new colObject()创建的列对象
function AddCol(col)
{
   if(this.ColsName[col.id]!=null) return;
    var curCell = null;
   if(document.all!=null)   
     curCell = this.tabelobj.rows[this.cloneRowIndex].all(col.id);
   else
   {
       var tds=this.tabelobj.rows[this.cloneRowIndex].getElementsByTagName("TD");
       for(var o =0;o<tds.length; o++)
       {
           if(tds[o].id==col.id)
           {
               curCell = tds[o];
               break;
           }
       }
   }
   if(curCell==null) return;
   if(curCell.width!="")
       col.width = curCell.width;
   else if(curCell.offsetWidth>0)
       col.width=(curCell.offsetWidth)+"px"; //默认宽度
   else
       col.width = "";
   col.index = curCell.cellIndex;
   var temp = curCell.getAttribute("inputType");
   if(temp!=null && temp!="")
       col.inputType=temp;
   var temp = curCell.getAttribute("maxLength");
   if(temp!=null && temp!="")
       col.maxLength=temp;        
   temp = curCell.getAttribute("sort");
   if(temp!=null && temp!="")
       col.sort=temp;
   temp = curCell.getAttribute("edit");
   if(temp!=null && temp+""!="")
       col.Edit=temp;
   temp = curCell.getAttribute("text");
   if(temp!=null && temp+""!="")
       col.text=temp;   
   temp = curCell.getAttribute("href");    
   if(temp!=null && temp+""!="")
       col.href=temp;   
   temp = curCell.getAttribute("isSave");        
   if(temp!=null && (temp==false || temp==0 || temp=="false" || temp+""=="0"))
       col.isSave=false; 
   temp = curCell.getAttribute("isAreaText");        
   if(temp!=null && (temp==true || temp==1 || temp+""=="true" || temp+""=="1"))
       col.isAreaText=true; 
   temp = curCell.getAttribute("search");    
   if(temp!=null && (temp==true || temp==1 || temp+""=="true" || temp+""=="1"))
       col.search=true; 
   temp = curCell.getAttribute("openWindowURL");
   if(temp!=null && temp!="")
   {
   	   col.isOpenWindow = true;
       col.OpenWindowURL = temp;                                   
   }
   temp = curCell.getAttribute("listData");
   if(temp!=null && temp!="")
   {
   	   col.isDownList = true;
       col.listData = eval("new Array("+temp+")");                                   
   }   
   this.Cols[this.Cols.length]=col;
   this.ColsName[col.id]=col;
   this.objCols = this.Cols.length;

   //排序功能
   this.tabelobj.rows[this.cloneRowIndex].cells[col.index].onclick=function()
   {
   	  function comp(cellIndex,type)
   	  {
			    if(type=="number")
			  	  return function(x,y){
			  	  	var x1 = x.cells[cellIndex].innerText;
   	  	            var y1 = y.cells[cellIndex].innerText;
			  	  	return parseFloat(x1)-parseFloat(y1);}//数字排序
			    else if(type=="string")
			      return function(x,y){
			  	  	var x1 = x.cells[cellIndex].innerText;
   	  	            var y1 = y.cells[cellIndex].innerText;			      	
			      	return (x1>y1)?1:(x1==y1?0:-1);}//数字排序			       
			    else if(type=="datetime")
			      return function(x,y){
			  	  	var x1 = x.cells[cellIndex].innerText;
   	  	            var y1 = y.cells[cellIndex].innerText;			      	
			      	return new Date(Date.parse(x1.replace(/-/g,   "/")))-new Date(Date.parse(y1.replace(/-/g,   "/")));};//数字排序
   	  }   	
   	  var tab = this.parentNode;
   	  while(tab.tagName!="TABLE")
   	  {
   	     tab = tab.parentNode;
   	  }
   	  var tableObj = getObjectRef(tab.id);	
   	  var thisSort = tableObj.ColsName[this.id];
   	  if(thisSort.sort!=null && thisSort.sort!="")
   	  {
   	      this.sort = thisSort.sort;
   	  	  //把当前列及所属行存入数组
   	  	  var trs = new Array(); 
   	  	  for(var i=this.parentNode.rowIndex+1; i<tab.rows.length-(tableObj.isTatolRow?1:0); i++)
   	  	  {
   	  	  	  trs[trs.length]=tab.rows[i];
   	  	  }
		  if(tab.getAttribute("sortType")!=null && tab.getAttribute("sortType")==this.cellIndex)
		  { 
		    trs.reverse();
		  }
		  else
		  {   	  	  
		  	  trs.sort(new comp(this.cellIndex,this.getAttribute("sort")));//数字排序
			  tab.setAttribute("sortType",this.cellIndex); 
		  }
          var oFragment = document.createDocumentFragment();
          for (var i=0; i < trs.length; i++) {
                    oFragment.appendChild(trs[i]);
          } 
          tab.children[0].appendChild(oFragment); 
   	  }
   	  //触发搜索事件
   	  if(thisSort.search)
   	  {
   	      searchEvent(thisSort);
   	  }
   }
   var title = this.tabelobj.rows[this.cloneRowIndex].cells[col.index].getAttribute("caption");
   if(title!=null && title!="")
   {
		col.name= title; 
		return;  	
   }   
   col.name=this.tabelobj.rows[this.cloneRowIndex].cells[col.index].innerText;
   if(!Type.toDefaultBoolean(col.Edit,true)) return;
   //this.tabelobj.rows[this.cloneRowIndex].cells[col.index].style.cursor="pointer";
}
//根据列名称在指定的列数据对象集合中找指定的列对象.只供对象内部调用，无对外方法实现。
//ary:列对象集合
//colId:列名称
//返回　数据列对象
function getCOlObject(ary,colId)
{
   var result = null;
   var ary = ary;
   for(var i=0;i<ary.length;i++)
   {
      if(ary[i].id==colId)
	  {
	     result = ary[i];
	     break;
	  }
   }
   return result;
}

//添加一新行。只供对象内部调用，对外方法名称为addNewRow()。
function AddNewRow(index)
{
	var tableobj = this.tabelobj;
	var insertindex = index;
	var newrow = null;
	//判断是否有合计行。
	if(this.isTatolRow==true&&tableobj.rows.length>1)
	{
	   if(index==null||index<0||index>tableobj.rows.length)
	   {
	      if(this.count!=null&&this.count>0)
	      {
	      	
		     if(this.count<tableobj.rows.length-1)
		     {
                alert("超出最大可编辑行数");
			    this.isaddrows = false;
			    this.isAddRow = false;
      	        return;
		     }
	      }
	      insertindex = tableobj.rows.length-1;
	   }
	   else
	      insertindex ++;
	   newrow = tableobj.insertRow(insertindex);
	}
	else
	{
	   if(index==null||index<0||index>tableobj.rows.length)
	   {
	      if(this.count!=null&&this.count>0)
	      {
		     if(this.count<tableobj.rows.length)
		     {
                alert("超出最大可编辑行数");
			    this.isaddrows = false;
			    this.isAddRow = false;
      	        return;
		     }
	      }
	      insertindex = tableobj.rows.length;
	   }
	   else
	      insertindex ++;	 
	   newrow = tableobj.insertRow(insertindex);
	}
	
	newrow.setAttribute("action","insert");
	ClassStyle.setClass(newrow,(newrow.rowIndex%2==0 && this.corssColor!=null)?this.corssColor.doubleColorCSS:this.corssColor.singularColorCSS);			
    this.isAddRow = true;
	var cellobj;
	var firstRow = tableobj.rows[this.cloneRowIndex];
	var colSet;
	var l=0,len=this.objCols;
	if(this.createRowFlagColumn)
	{
      cellobj = document.createElement("TD");
			newrow.appendChild(cellobj);
			ClassStyle.setClass(cellobj,"Etd_rowheader");
			cellobj.id="_FLAG_COLMUN";
			if(!this.selectByControl)
			   cellobj.innerHTML=this.currentAddRowCount+1;
			else
			{
			    var ctl = document.createElement("INPUT");
			    ctl.setAttribute("name","___row_selectControl");
			    if(this.selectMode=="multiple")  //多选
			    {
			        ctl.type="checkbox";
			    }
			    else
			    {
			        ctl.type="radio";
			    }
			    cellobj.appendChild(ctl);
			    ctl.onclick=function(event)
			    {
			        var e = window.event||event;
			        if(!Util.isIE){
			            e.preventDefault(); 
			    	    e.stopPropagation(); 
			    	}
			    	else
			    	{
			    	    e.cancelBubble = true;
		                e.returnValue = false;
			    	}
			    }
			}
			l++;
			len++;
	}
		
  for(var i=l;i<len;i++)
	{
			var curColID = firstRow.cells[i].id;;
			colSet = this.ColsName[curColID];
			var cache = this.cacheCells[curColID];
			//获取该列是否设置了默认值
			var defaultText = "";
			var _t = colSet.text;	
		  if(colSet!=null&&_t!=null&&_t!="")
			{
					   //默认的按自定义的方式处理
					   defaultText = _t;
					   if(defaultText=="COPY")
					   {
					      //复制上一行当前列的值
						  if(this.cloneRowIndex!=insertindex-1)
						     defaultText = getColValue(tableobj,insertindex-1,colSet.index,"[out]");
					   }
					   else if(defaultText.indexOf("index=")==0)
					      //复制当前行指定列的值
						  defaultText = getColValue(tableobj,insertindex,defaultText.replace("index=",""),"[out]");
					   else if(defaultText.indexOf("document.")==0)
					      //取指定控件的值
						  defaultText = eval(defaultText);
					   else if(defaultText.indexOf("javascript:")==0)
					      //触发指定的事件返回默认值
						  defaultText = eval(defaultText.substring(11));
			}			
			if(cache!=null)
			{
				cellobj = cache.cell.cloneNode(true);
			}	    
      else
      	 cellobj = document.createElement("TD");
    cellobj.style.backgroundColor="";  	 
    newrow.appendChild(cellobj); 	       	 
 		if(!Type.toDefaultBoolean(colSet.Edit,true))//不可以编辑
		{
		  	cellobj.setAttribute("edit","0");
		  	ClassStyle.setClass(cellobj,"Etd_readonly");
		}else
		{
		  	cellobj.setAttribute("edit","1");
		  	ClassStyle.setClass(cellobj,"Etd_input");		  	
		}
		cellobj.ondragenter=function (){return false;};
		cellobj.setAttribute("hiddenvalue","");
		cellobj.setAttribute("title","");
		var astyle  =(colSet==null||colSet.alignStyle==null)?"left": colSet.alignStyle;
		cellobj.setAttribute("align",astyle);
		cellobj.innerHTML = "&nbsp;";
		cellobj.style.width=colSet.width;
		cellobj.style.overflow="hidden";
		cellobj.style.textOverflow="ellipsis";
		cellobj.style.whiteSpace = "nowrap";
		if(defaultText!=null	&&defaultText!=""	&&"COPY,index=,document.".indexOf(defaultText)==-1)
			selectedValue(cellobj,defaultText,null);		
		if(cache!=null) continue;
		
		//判断是否应该在第一列加行删除按钮
			if(this.createDeleteColumn==true && i==l)
			{
				cellobj.onclick=function(event) {
					var colInd = this.cellIndex;
					var tobj = getObjectRef(this);	
					var coldataset	= 	tobj.ColsName[getCode(tobj.tabelobj,colInd)];
					var ind = this.parentNode.rowIndex-(tobj.cloneRowIndex+1);
					var rowData = tobj.dataSource[ind];
					var delFlag = tobj.delRow(event);
					if(delFlag && coldataset!=null && coldataset.deleteRow!=null)	
					{	
					    coldataset.deleteRow(rowData);
						for(var i=0;i<tobj.dataSource.length; i++)
						{
							if(tobj.dataSource[i]!=null && tobj.dataSource[i].getAttribute("action")=="delete")
							{
								for(var j=i;j<tobj.dataSource.length;j++)
							       tobj.dataSource[j] = tobj.dataSource[j+1];
							    i--;
							}
						}
					}
				};
				cellobj.style.width="30px";
				cellobj.style.color="blue";
				cellobj.style.fontSize="12px";
				cellobj.style.cursor="pointer";				
				cellobj.innerHTML="删除";
				cellobj.setAttribute("edit","0");
				cellobj.setAttribute("className",this.ColsName[firstRow.cells[i+1].id].CSS);
				continue;
			}
			
		if(colSet==null)//该列没有在列设置数组是找到则采用默认的处理方式
		{
		   cellobj.onkeydown= function(){if(window.event.keyCode==13){return false;}};
		   //cellobj.setExpression("title","this.innerText");
		}
		else
		{
		   if(colSet.width!="")
	           cellobj.style.width=colSet.width;
	       else
	           cellobj.width = colSet.width;
		   cellobj.style.textAlign=colSet.alignStyle;			
		}		
		//缓存当前单元格
		var cacheCell = new Object();
		cacheCell.cellIndex = colSet.index;
		cacheCell.cellID = colSet.id;
		cacheCell.cell = cellobj;
		this.cacheCells[colSet.id]=cacheCell;
	}
	this.currentAddRowCount++;
	return newrow;
}


//添加一新行碎片。只供对象内部调用
function AddNewRowFragment(data)
{
	var  newrow = document.createElement("TR");
	ClassStyle.setClass(newrow,(this.currentAddRowCount%2==0 && this.corssColor!=null)?this.corssColor.doubleColorCSS:this.corssColor.singularColorCSS);			
	newrow.setAttribute("action","insert");
    this.isAddRow = true;
	var cellobj;
	var firstRow = this.tabelobj.rows[this.cloneRowIndex];
	var colSet;
	var l=0,len=this.objCols;
	if(this.createRowFlagColumn)
	{
      cellobj = document.createElement("TD");
			newrow.appendChild(cellobj);
			ClassStyle.setClass(cellobj,"Etd_rowheader");
			cellobj.id="_FLAG_COLMUN";
      if(!this.selectByControl)
			   cellobj.innerHTML=this.currentAddRowCount+1;
			else
			{
			    var ctl = document.createElement("INPUT");
			    ctl.setAttribute("name","___row_selectControl");
			    if(this.selectMode=="multiple")  //多选
			    {
			        ctl.type="checkbox";
			    }
			    else
			    {
			        ctl.type="radio";
			    }
			    cellobj.appendChild(ctl);
			    ctl.onclick=function(event)
			    {
			        var e = window.event||event;
			        if(!Util.isIE){
			            e.preventDefault(); 
			    	    e.stopPropagation(); 
			    	}
			    	else
			    	{
			    	    e.cancelBubble = true;
		            e.returnValue = false;
			    	}
			    }
			}			
			l++;
			len++;
	}
  for(var i=l;i<len;i++)
	{
		var curColID = firstRow.cells[i].id;;
		colSet = this.ColsName[curColID];
		var cache = this.cacheCells[curColID];
		var fullValue = "",text = "",hiddenV="";
		if(data!=null)
		{
			var tx = data[curColID];
			tx = tx==null?"":tx;
			fullValue = tx;
			var dataValues = tx.split(",");
			hiddenV = dataValues.length>1?dataValues[1].replace(/&cma;/g,","):"";
			text = dataValues[0].replace(/&cma;/g,",");
	  }
	  else
	  {
      //获取该列是否设置了默认值
			var defaultText = "";
			if(colSet!=null&&colSet.text!=null&&colSet.text!="")
			{
			   //默认的按自定义的方式处理
			   defaultText = colSet.text;
			   if(defaultText=="COPY")
			   {
			      //复制上一行当前列的值
				  if(this.cloneRowIndex!=insertindex-1)
				     defaultText = getColValue(tableobj,insertindex-1,colSet.index,"[out]");
			   }
			   else if(defaultText.indexOf("index=")==0)
			      //复制当前行指定列的值
				  defaultText = getColValue(tableobj,insertindex,defaultText.replace("index=",""),"[out]");
			   else if(defaultText.indexOf("document.")==0)
			      //取指定控件的值
				  defaultText = eval(defaultText);
			   else if(defaultText.indexOf("javascript:")==0)
			      //触发指定的事件返回默认值
				  defaultText = eval(defaultText.substring(11));
				if(defaultText!=null	&&defaultText!=""	&&"COPY,index=,document.".indexOf(defaultText)==-1)
						fullValue = defaultText;
			}			  	
	  }
		if(cache!=null)
		{			
			cellobj = cache.cell.cloneNode(true);
			cellobj.innerHTML = "";
		}	 
		else   
       cellobj = document.createElement("TD");
    newrow.appendChild(cellobj);
		cellobj.ondragenter=function (){return false;};
		cellobj.setAttribute("hiddenvalue",hiddenV);
		cellobj.setAttribute("title",text);
		if(!Type.toDefaultBoolean(colSet.Edit,true))//不可以编辑
		  {
		  	cellobj.setAttribute("edit","0");
		  	ClassStyle.setClass(cellobj,"Etd_readonly");
		  }else
		  {
		  	cellobj.setAttribute("edit","1");
		  	ClassStyle.setClass(cellobj,"Etd_input");		  	
		  }
		var astyle =   (colSet==null||colSet.alignStyle==null)?"left":colSet.alignStyle;
		cellobj.setAttribute("align",astyle);
		var div = document.createElement("div");
		div.style.width = colSet.width;
		ClassStyle.setClass(div,"textNoBR");
		cellobj.appendChild(div);
		selectedValue(div,fullValue,null);
		if(cache!=null) continue; //从已有单元格克隆时，不处理下面的
		//判断是否应该在第一列加行删除按钮
			if(this.createDeleteColumn==true && i==l)
			{
          cellobj.onclick=function(event) {
					var colInd = this.cellIndex;
					var tobj = getObjectRef(this);	
					var coldataset	= 	tobj.ColsName[getCode(tobj.tabelobj,colInd)];
					var ind = this.parentNode.rowIndex-(tobj.cloneRowIndex+1);					
					var delFlag = tobj.delRow(event);
					if(tobj.dataSource==null) return;
					var rowData = tobj.dataSource[ind];
					if(delFlag && coldataset!=null && coldataset.deleteRow!=null)	
					{	
					    coldataset.deleteRow(rowData);
						for(var i=0;i<tobj.dataSource.length; i++)
						{
							if(tobj.dataSource[i]!=null && tobj.dataSource[i].getAttribute("action")=="delete")
							{
								for(var j=i;j<tobj.dataSource.length;j++)
							       tobj.dataSource[j] = tobj.dataSource[j+1];
							    i--;
							}
						}
					}
				};
				cellobj.style.width="30px";
				cellobj.style.color="blue";
				cellobj.style.fontSize="12px";
				cellobj.style.cursor="pointer";				
				cellobj.innerHTML="删除";
				cellobj.setAttribute("edit","0");
				ClassStyle.setClass(cellobj, this.ColsName[firstRow.cells[i+1].id].CSS);
				continue;
			}
			
		if(colSet==null)//该列没有在列设置数组是找到则采用默认的处理方式
		{
		   cellobj.onkeydown= function(){if(window.event.keyCode==13){return false;}};
		}
		else
		{
		  cellobj.style.width=colSet.width;
		}		
		//缓存当前单元格
		var cacheCell = new Object();
		cacheCell.cellIndex = colSet.index;
		cacheCell.cellID = colSet.id;
		cacheCell.cell = cellobj;
		this.cacheCells[colSet.id]=cacheCell;
	}
	this.currentAddRowCount++;
	return newrow;
}

function getTatol(cellIndex,tableid)
{
   var tableObj = getObjectRef(tableid);
   if(tableObj.isTatolRow==true)
	{
	   sourceCol = "";
	   var va = "0";
	   var tavbleobj = tableObj.tabelobj;
	   var initPos= tableObj.cloneRowIndex+1;
	   var endPos = tavbleobj.rows.length-1;
	   for(var i=initPos;i<endPos;i++)
	   {
		   va = getControlValue(tavbleobj.rows[i].cells[cellIndex]);
		   va= va.replace("&nbsp;","");
		   va= va.replace(" ","");
		   if(va==""||va=="&nbsp;")
		   {
		      va="0";
		   }
	      sourceCol += "+"+va;
	   }
	   if(sourceCol.length>1)
	   {
	      sourceCol = sourceCol.substring(1);
	   }
	   if(sourceCol==null || sourceCol=="") return;
	   var result=(eval(sourceCol)).toFixed(decimal);//设置小数位数
	   if(result=="NaN"||result=="Infinity") 
		{
		   result = "0";
		}
	   return result;
	}
}
//当前编辑单元格失去焦点的处理。主要针对有计算表达式的情况
function thisonblur(v)
{
	objectRef = getObjectRef(v);
	//取得表达式对象
	var isAccount = false;
	var nowCol = nowClickCol(v);
	var nowRow = nowClickRow(v);
	var oRow;
	if(nowRow>-1)
	{
	   oRow = objectRef.tabelobj.rows[nowRow];
	}
	else
	{
	   return;
	}
	var tempCol;
	var exObj = objectRef.expression;

    //判断是否设置的了条件表达式
    for(var posi=0;posi<exObj.condtionlist.length;posi++)
	{
		var colname=getCode(objectRef.tabelobj,nowCol);
		if(colname=="")
		{
		   return;
		}
	   var result = false;
	   var sourceCol = exObj.condtionlist[posi][1];
	   var objectCol = ","+exObj.condtionlist[posi][0]+",";
	   //如果当前列包含在不判断的列中时不进行判断
	   if(objectCol.indexOf(","+colname+",")>-1)
	   {
	      break;
	   }
  	   if(sourceCol.indexOf(colname)>-1)
	   {
		   var objectCol = exObj.condtionlist[posi];
		   //替换所有的列的值
		   var pos = 0;
		   for(var j=0;j<objectRef.Cols.length;j++)
		   {
			  pos = sourceCol.indexOf(objectRef.Cols[j].id);
		      while(pos>-1)
			  {
                  tempCol = objectRef.Cols[j].index;
				  var va = objectRef.tabelobj.rows[nowRow].cells[tempCol].children[0].innerHTML;
				  va= va.replace("&nbsp;","");
				  va= va.replace(" ","");
				  if(va==""||va=="&nbsp;")
				  {
				     va="0";
				  }
				  //负数的计算时做为单独的表达式
				  if(va.substring(0,1)=="-")
				  {
				     va = "("+va+")";
				  }
			      sourceCol = sourceCol.substring(0,pos)+va+sourceCol.substring(pos+objectRef.Cols[j].id.length);
				  pos = sourceCol.indexOf(objectRef.Cols[j].id);
			  }
		   }
	       eval(sourceCol);
		   tempCol = nowCol;	
		   if(result==false)
		   {
              oRow.cells[tempCol].children[0].innerText = "";
              oRow.cells[tempCol].children[0].focus();
		      return;
		   }
	   }
	}
	//判断当前行是否设置了计算表达式
	for(var i=0;i<exObj.list.length;i++)
	{
	   var sourceCol = exObj.list[i][1];
       var objectCol = exObj.list[i][0];	   
	   if(sourceCol.indexOf("["+nowCol+"]")>-1)
	   {
		   if(objectCol==null || objectCol==-1)
		   {
		      continue;
		   }
		   //替换所有的列的值
		   var pos = sourceCol.indexOf("[");
		      while(pos>-1)
			  {
                  tempCol = sourceCol.substring(pos+1,sourceCol.indexOf("]"));
				  var va =objectRef.getCellValue(oRow.rowIndex,tempCol);			  
				  va= va.replace(/&nbsp;/g,"");
				  va= va.replace(/ /g,""); 
				  if(va==""||va=="&nbsp;")
				  {
				     return;
				  }
				  //负数的计算时做为单独的表达式
				  if(va.substring(0,1)=="-")
				  {
				     va = "("+va+")";
				  }
			     sourceCol = sourceCol.replace("["+tempCol+"]",va);
				 pos = sourceCol.indexOf("[");
			  }
		   var result=(eval(eval(sourceCol).toFixed(6))).toFixed(decimal);//设置小数位数
           if(result=="NaN"||result=="Infinity") 
		   {
		      result = "";
		   }
           objectRef.setCellValue(oRow.rowIndex,objectCol,result);
	   }
	}	
}

//在表达式对象中添加一个表达式。对外访问方法实现addExpression(expression);
function AddExpression(colobject,prar)
{
   objectRef = getObjectRef(this.tableID);
   var aryExp  = new Array();
   var pos =objectRef.ColsName[colobject].index;// getIndex(objectRef.tabelobj,colobject);
   if(pos!=-1)
   {
      aryExp[0] = pos;
      aryExp[1] = this.exptoindexexp(objectRef.tabelobj,prar);
      this.list[this.list.length] = aryExp;
   }
}
//把列名表示的表达式解析成表格索引表示的表达式
function parse(tname,exp)
{
   //在每个表达式的最后加一个非字母字符以保证能解析到每一个单词
   var palcestr =exp+"+";
   var word = new Array();
   //把表达式解析成单词
   var temp = "";
   for(var pos=0;pos<palcestr.length;pos++)
   {
	  var chars = palcestr.charAt(pos);
      if("+-*/()[]".indexOf(chars)==-1) temp += chars;
	  else
	  {
		  if(temp!="") word[word.length] = temp;
	      temp = "";
	  }
   }
   var len = word.length;
   var newExp = exp;
   var index = 0;
   //把每个列名替换成对应的索引号
   for(var i=0;i<len;i++)
   {
		if(word[i]=="" || !isNaN(word[i]*1)) continue;
	  	index = objectRef.ColsName[word[i]].index;//getIndex(tname,word[i]);
	  	if(index>-1) newExp = newExp.replace(word[i],"["+index+"]");	  	
   }   
   return newExp;
}

function AddConExpression(nochangecol,ex)
{
   var aryExp  = new Array();
   aryExp[0] = nochangecol;
   aryExp[1] = ex;
   this.condtionlist[this.condtionlist.length] = aryExp;   
}

//表达式列表对象。不对外提供访问
function expressionList(tableObjId)
{
   this.tableID = tableObjId;
   this.list = new Array();
   this.condtionlist = new Array();
   this.addExpression  = AddExpression;//计算表达式
   this.addConExpression  = AddConExpression;//条件表达式
   this.exptoindexexp = parse;
}

//返回当前操作的列.只供对象内部调用，无对外方法实现。
function nowClickCol(e)
{
    var event = getDetailTableEvent();
	var o = event.srcElement||event.target;
	while (o.tagName != "TD")
	{
		o = o.parentNode;
		if(o==null || o.tagName=="FORM")  return null;
	}
    if("SPAN,DIV".indexOf(o.tagName)>-1)  return null;
	nowTdIndex = o.cellIndex;					 //就是点击的列索引
	return nowTdIndex;			
}

//返回当前操作的行.只供对象内部调用，无对外方法实现。
function nowClickRow(e)
{
    var event = getDetailTableEvent();
	var o = event.srcElement||event.target; 			
	while (o.tagName != "TR")
	{
		o = o.parentNode;
		if(o==null || o.tagName=="FORM")   return null;
	}
	nowTrIndex = o.rowIndex;			//就是点击的行索引
	if(nowTrIndex==0) return -1;
	return nowTrIndex;
}

//删除当前行。只供对象内部调用，对外方法名称为delRow()。
function DelRow(e)
{
   var tableobj = this.tabelobj;
   //最后一条记录不能删除
   /*
   var max =tableobj.rows.length-1;
   if(this.isTatolRow==true) max--;
   var cr =null;
   if(nowClickCol(e)==null) cr = nowTrIndex;
   else cr = nowClickRow(e);*/
   if(confirm("确定要删除当前选择的行吗？")) 	
   {
   	  var l =  -1;
   	  var attrName = (this.key!=null && this.key!="")?"keyvalue":"oldValue";
	   	var rss = this.currentSelectedRows();	
	   	//单选模式时，删除当前操作的行
	   	if(rss.length==0) 
	   	{	   		
		   var cr =null;
		   if(nowClickCol(e)==null) cr = nowTrIndex;
		   else cr = nowClickRow(e);	
		   if(cr==0) return;
		   if(tableobj.rows[cr].getAttribute("action")!="insert"){
		       l = this.dataKeyMap[tableobj.rows[cr].getAttribute(attrName)]	;
		       if(l!=null)
		          this.dataSource[l].action="delete";
		   }
		   tableobj.deleteRow(cr);
		   this.currentAddRowCount--;
		   return true;		     		
	   	}  	
	   	//多行选择模式时，删除所有被选择的行
	   	for(var i=rss.length-1;i>=0;i--)
	   	{
	   		 if(tableobj.rows[i].getAttribute("action")!="insert"){
			       l = this.dataKeyMap[tableobj.rows[i].getAttribute(attrName)]	;
			       if(l!=null)
			           this.dataSource[l].action="delete";  
			   } 		 
		     tableobj.deleteRow(rss[i]);
		     this.currentAddRowCount--;
	   	}
	   	return true;
   }
   return false;
}

function noshowcols(colgroupname,cols)
{
   var groupObj = document.getElementById(colgroupname);
   if(groupObj==null)
   {
      alert("无效的列分组对象");
	  return;
   }
   if(cols==null||cols=="")
   {
      alert("无效的列名");
	  return;
   }
   var i=cols.indexOf(",");
   var colindex = 0;   
   if(i==-1)
   {
	  colindex = getIndex(this.tabelobj,cols);
	  if(colindex==-1)
	  {
		 alert("无效的列名");
	     return;
	  }
      groupObj.childNodes[colindex].style.display='none';
      return;
   }
   else
   {
      var tempcols = cols+",";
	  var colsid = "";
	  while(i>-1)
	  {
	     colsid = tempcols.substring(0,i);
		 colindex = getIndex(this.tabelobj,colsid);
		 if(colindex>-1)  groupObj.childNodes[colindex].style.display='none';
		 tempcols = tempcols.substring(i+1);
		 i = tempcols.indexOf(",");
	  }
   }
}


/**
 * 搜索事件。
 */
function searchEvent(e,obj)
{	
    var evetn = e || window.event;
	var o = event.srcElement||event.target; 			
	while (o.tagName != "TABLE")
	{
		o = o.parentNode;
		if(o==null || o.tagName=="FORM")
		{
		   alert("请确认当前事件的触发源对象为TABLE!");
		   return null;
		}		
	}
	getObjectRef(o.id);
	var search = new searchObject();
	search.setSearchTarget(o);
	search.setSearchStartCellIndex(obj.index);
	search.setSearchStartRowIndex(objectRef.cloneRowIndex+1);
	var panel = search.panel;
	panel.control.style.display='';
	panel.control.style.top = event.y+document.body.scrollTop;
	panel.control.style.left = event.x+document.body.scrollLeft;
    if(obj.index>=o.rows[objectRef.cloneRowIndex].cells.length-3)
       panel.control.style.left = event.x+document.body.scrollLeft-panel.control.style.width;
    else
       panel.control.style.left = event.x+document.body.scrollLeft;
	document.getElementById("_SEARCH_VALUE").focus();
	document.getElementById("_SEARCH_CURRENT_COLNAME").innerText = " ："+obj.name;
	document.getElementById("_SEARCH_COMPS_CLOS").value = obj.index;
	
}


//+----------------------------------------------------------------------------
//
//功能描述：克隆表格
//
//输入参数：oSrcTable        源表格
//            oNewTable        新表格
//            iRowStart        克隆开始行
//            iRowEnd            克隆结束行
//            iColumnEnd        克隆结束列
//
//-----------------------------------------------------------------------------
function CloneTable(oSrcTable, oNewTable, iRowStart, iRowEnd, iColumnEnd)
{
        //循环控制参数
        var i, j, k = 0;
        
        //新增行、列            
        var newTR, newTD;
        
        //新表格宽度、高度            
        var iWidth = 0, iHeight = 0;
        
        //拷贝Attributes、events and styles
        if(isIE)
            oNewTable.mergeAttributes(oSrcTable);
        else
            mergeAttributes(oSrcTable,oNewTable);  
        oNewTable.width=oSrcTable.offsetWidth;         
        //循环克隆指定行
        var rowspanAry = [];
        for (i = iRowStart; i < iRowEnd; i++)
        {
                //if(isIE)
                //{
        	     //   oNewTable.childNodes[0].appendChild(oSrcTable.rows[i].cloneNode(true));
        	    //    continue;
        	   // }
                newTR = oNewTable.insertRow(k);
                
                //拷贝Attributes、events and styles
                if(isIE)
                   newTR.mergeAttributes(oSrcTable.rows[i]);
                else
                   mergeAttributes(oSrcTable.rows[i],newTR);   
                
                iWidth = 0;
                iHeight +=isIE?oSrcTable.rows[i].cells[0].offsetHeight:oSrcTable.rows[i].cells[0].clientHeight;
                //循环克隆指定列
                for(j = 0; j < (iColumnEnd == -1 ? oSrcTable.rows[i].cells.length: iColumnEnd); j++)
                {
                	    var ktd = oSrcTable.rows[i].cells[j]; 
                        newTD = ktd.cloneNode(true);
                        if(newTD.rowSpan>1 || newTD.colSpan>1)
                           rowspanAry[rowspanAry.length] = {row:i,cell:j,rowspan:newTD.rowSpan,colspan:newTD.colSpan};
                        iWidth += (isIE?ktd.offsetWidth:ktd.clientWidth);                        
                        newTR.insertBefore(newTD,null);
                        newTD.innerHTML = ktd.innerHTML==""?"&nbsp;":ktd.innerHTML;
                        newTD.style.width = (isIE?ktd.offsetWidth:ktd.clientWidth)+"px";                        
                }  
                //if(newTR.childNodes.length==0) newTR.heigth=1;              
                k++;
        }
        for(var i=0; i<rowspanAry.length; i++)
        {
        	if(rowspanAry[i].rowspan>1)
        	{
        		for(var k=0;k<rowspanAry[i].rowspan-1;k++)
        		{
        		   var cell = oNewTable.rows[rowspanAry[i].row+1].cells[rowspanAry[i].cell-i];
        		   if(!isIE)cell.parentNode.removeChild(cell);
        		   else cell.removeNode(true);        		   
        		}
        	}
        }
        var firstCellWidth =oNewTable.rows[0].cells[0].style.width.replace("px","");
        if(firstCellWidth>20)
        {
        	iWidth = iWidth-firstCellWidth+20;
        	for(var r=0;r<oNewTable.rows;r++)
        	{
        		oNewTable.rows[r].cells[0].style.width="20px";
        	}
        }
        oNewTable.style.width = iWidth+"px";
        oNewTable.style.height = iHeight+"px";
        oNewTable.onmousedown = function(){oSrcTable.onmousedown()};
}

function mergeAttributes(src,target)
{
        var attrs = src.attributes;
		var i = attrs.length - 1;
		for(;i>=0;i--){
			var name = attrs[i].name;
			if(name.toLowerCase() === 'id')
				continue;
			target.setAttribute(name, attrs[i].value);
        }
        target.setAttribute("style", src.getAttribute("style"));        
}


/**
 * 搜索对象
 */
function searchObject()
{	
	//if(searchClassObject!=null)
	//	return searchClassObject;
    this.panel = new searchPanel();
    //添加列信息
    var colsAry = objectRef.Cols;
    var compCols = document.getElementById("_SEARCH_COMPS_CLOS");
    if(compCols.options.length==0)
    for(var i=0 ;i<colsAry.length; i++)
    {
		var opt = document.createElement("option");
		compCols.options.add(opt);
		opt.value = colsAry[i].index;
		opt.text = colsAry[i].name;    	
    }
    this.setSearchTarget = function (v){  	
    	this.panel.searchTarget = v; }
    this.setSearchStartRowIndex = function (v){
    	this.panel.sarchrow = v; }      
    this.setSearchStartCellIndex = function (v){
    	this.panel.sarchcol = v; }    
    searchClassObject = this;  
}
/*
 * 搜索面板控件
 * */
function searchPanel()
{
	//如果当前页面未创建搜索面板对象，则自动创建。
	this. control = document.getElementById("_SEARCH_PANEL");
    this.sarchtext = "";
    this.searchTarget = null; //搜索的目标。一般为表格对象
    this.sarchrow = 0;        //搜索的起始行
    this.sarchcol = 0;	      //搜索的列.一般为当前列
	if(this.control==null)
	{
		var panel = document.createElement("div");
		panel.setAttribute("cssText" , "display:'none';BORDER-RIGHT: dimgray 1px solid; TOP: 30px;BORDER-TOP: whitesmoke 1px solid; DISPLAY: none; BORDER-LEFT: whitesmoke 1px solid; WIDTH: 150px;BORDER-BOTTOM: dimgray 1px solid; POSITION: absolute; BACKGROUND-COLOR: activeborder;");
		panel.id="_SEARCH_PANEL";
		var oValue=document.createTextNode("列名");
		//oValue.style.fontSize=12;
		panel.appendChild(oValue);
		var a=document.createElement("a");
		a.id="_SEARCH_CURRENT_COLNAME";
		panel.appendChild(a);
		panel.appendChild(document.createElement("hr"));
		var obj=document.createElement("input");
		obj.type="text";
		obj.id="_SEARCH_VALUE";
		obj.style.width="150px";
		obj.onkeypress=function(){if(event.keyCode==13){kkkk();};}
		panel.appendChild(obj);		
		panel.appendChild(document.createElement("hr"));
		panel.appendChild(document.createTextNode("条件范围"));
		obj = document.createElement("select");
		obj.id = "_SEARCH_COMPS_CLOS";
		obj.style.width="150px";
		panel.appendChild(obj);
		obj = document.createElement("select");
		obj.id = "_SEARCH_COMPS_CHAR";
		obj.style.width="60px";
		obj.onchange = function(){document.getElementById('_SEARCH_COMPS_VALUE').focus()};
		var opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "";
		opt.text = "全部";
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "=";
		opt.text = "等于";	
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = ">";
		opt.text = "大于";	
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = ">=";
		opt.text = "大于等于";	
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "<";
		opt.text = "小于";
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "<=";
		opt.text = "小于等于";	
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "<>";
		opt.text = "不等于";								
		panel.appendChild(obj);	
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "bettwen";
		opt.text = "两者之间";
		opt = document.createElement("option");
		obj.options.add(opt);
		opt.value = "like";
		opt.text = "包含";										
		panel.appendChild(obj);		
		var ab = document.createElement("input");
		ab.id="_SEARCH_COMPS_VALUE";
		ab.type="text";
		ab.style.width="80px";
		panel.appendChild(ab);	
		obj = document.createElement("input");
		obj.id="_SEARCH_FAST_FIND";
		obj.type="button";
		obj.value="快速查找";
		obj.onclick=function(){kkkk()} ;
		panel.appendChild(obj);
		obj = document.createElement("input");
		obj.id="_SEARCH_COPY_FILL";
		obj.type="button";
		obj.value="快速查找复制填充";
		obj.onclick=function(){copydata()} ;
		panel.appendChild(obj);					
		this.control = panel;
		document.body.appendChild(panel);
	}		
		
	function kkkk()
	{   
	   var queryvalue = $.trim(document.getElementById("_SEARCH_VALUE").value);
	   if(queryvalue=="" && searchClassObject.panel.sarchtext=="")    return;
	   if(queryvalue!="") searchClassObject.panel.sarchtext = queryvalue;//保存当前搜索的内容，以便可以进行继续搜索
	   var isfind = false;
	   sarchFlag = true;
	   var inputtable = searchClassObject.panel.searchTarget;
	   var startRowIndex = searchClassObject.panel.sarchrow;
	   var currentCellIndex = searchClassObject.panel.sarchcol;
	   for(var i=startRowIndex; i<inputtable.rows.length-(objectRef.isTatolRow?1:0); i++)
	   {
	      if(inputtable.rows[i].cells[currentCellIndex].innerText.indexOf(searchClassObject.panel.sarchtext)>-1)
	      {
	         createInput(objectRef,inputtable.rows[i].cells[currentCellIndex]);
	         startRowIndex=i;
	         searchClassObject.panel.sarchrow = startRowIndex;
	         isfind = true;
	         break;
	      }
	   }
	   sarchFlag=false;
	   if(!isfind)
	   {
	      alert("没找到你要搜索的内容或者搜索已结束！");
	   }
	   document.getElementById('_SEARCH_PANEL').style.display='none';
	   //document.getElementById("_SEARCH_VALUE").value = "";
	}	
	
function copydata()
{
   var queryvalue = $.trim(document.getElementById("_SEARCH_VALUE").value);
   if(queryvalue=="")
   {
      if(!confirm("满足范围的所有值都将被置为空，继续吗？"))
      {
         document.getElementById('_SEARCH_PANEL').style.display='none';
         return;
      }
      queryvalue = " ";
   }
   var inputtable = searchClassObject.panel.searchTarget;
   var cellIndex = searchClassObject.panel.sarchcol;//当前列的索引   
   var advItem = document.getElementById("_SEARCH_COMPS_CHAR").value;
   var advvalue = document.getElementById("_SEARCH_COMPS_VALUE").value;
   var beginvalue,endvalue;
   if(advItem=="bettwen")
   {
      advvalue = advvalue.replace("，",",");
      advvalue = advvalue.replace("-",",");
      if(advvalue.indexOf(",")==-1)
      {
         alert("请输入起始和结束值，中间请用逗号间隔！");
         document.getElementById("_SEARCH_COMPS_VALUE").focus();
         return;
      }
      beginvalue = advvalue.substring(0,advvalue.indexOf(","));
      if($.trim(beginvalue)=="")
      {
         alert("请输入起始值！");
         document.getElementById("_SEARCH_COMPS_VALUE").focus();
         return;         
      }
      endvalue = advvalue.substring(advvalue.indexOf(",")+1);
      if($.trim(endvalue)=="")
      {
         alert("请输入结束值！");
         document.getElementById("_SEARCH_COMPS_VALUE").focus();
         return;
      }
   }
   
   for(var i=searchClassObject.panel.sarchrow; i<inputtable.rows.length-(objectRef.isTatolRow?1:0); i++)
   {
      var placeCtrl = inputtable.rows[i].cells[cellIndex];
      var csCtrl =inputtable.rows[i].cells[document.getElementById("_SEARCH_COMPS_CLOS").value].innerText;
      if(advItem=="")
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="bettwen")
      {
         if(csCtrl>=beginvalue && csCtrl<=endvalue)
             placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="like" && csCtrl.indexOf(advvalue)>-1)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="==" && csCtrl==advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem==">=" && csCtrl>=advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="<=" && csCtrl<=advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="<>" && csCtrl!=advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem==">" && csCtrl>advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
      if(advItem=="<" && csCtrl<advvalue)
      {
         placeCtrl.innerText=queryvalue;
         continue;
      }
   }
   document.getElementById('_SEARCH_PANEL').style.display='none';
   document.getElementById("_SEARCH_VALUE").value = "";
}	
}