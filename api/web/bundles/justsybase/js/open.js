//window.onload =function()
//{
//    document.body.onclick=function(){
//	    if(showing!=null )
//		   document.getElementById(showing.id+"_children").style.display ="none";
//	}
//	eventBind("circlemenu","circlemenu_children");
//	eventBind("account","account_children");
//}
//var showing=null;
function eventBind(ctl1,ctl2)
{
	var oSelect = document.getElementById(ctl1);
	var oSub = document.getElementById(ctl2);
	var aLi = oSub.children;
	var i = 0;
	
	oSelect.onclick = function (event)
	{
//		if(showing!=null && this.id!=showing.id)
//		   document.getElementById(showing.id+"_children").style.display ="none";
		var style = oSub.style;
		style.display = style.display == "block" ? "none" : "block";
//		showing = this;
		//阻止事件冒泡
		(event || window.event).cancelBubble = true
	};
	
	for (i = 0; i < aLi.length; i++)
	{
		//鼠标划过
		aLi[i].onmouseover = function ()
		{
			this.className = "hover"
		};
		//鼠标离开
		aLi[i].onmouseout = function ()
		{
			this.className = "";
		};
		//鼠标点击
		aLi[i].onclick = function ()
		{
			<!-- alert("fff"); -->
		}
	}
	
//	document.onclick = function ()
//	{
//		oSub.style.display = "none";	
//	};

        oSelect.onblur = function () 
        {
          var style = oSub.style;
	  style.display = "none";
        }
}

// 首页滑动门js
		function setTab(name,cursel,n){
		for(i=1;i<=n;i++){
		var menu=document.getElementById(name+i);
		var con=document.getElementById("con_"+name+"_"+i);
		menu.className=i==cursel?"hover":"";
		con.style.display=i==cursel?"block":"none";
		}
	}
//
