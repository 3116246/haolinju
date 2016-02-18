//fafa分享组件及API
var FaFaShare={_element:null},LoadCSS={},srcs = [],currentScriptSrc="";
if(document.currentScript==null)
{
        var scripts = document.getElementsByTagName("script");
        var  reg = /faapi([.-]\d)*\.js(\W|$)/i
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
LoadCSS.load("fashare_css.css");
FaFaShare.prototype={
	  init:function(){
	  	  var s_id = "fafa_share_4294967295";
	  	  this._element = $("#"+s_id);
	  	  if(this._element.length>0)
	  	  {
	  	  	  this._element.remove();
	  	  }
	  	  this._element=$("<DIV id='"+s_id+"' class='fafa_share_modal'><div class='fafa_share_window_title'></div><div></div></DIV>");
	  	  document.body.appendChild(this._element[0]);
	  }
};
