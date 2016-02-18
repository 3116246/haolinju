//基础函数
var HashTable=function(){
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
}
var PictureViewer=function(){
	//private
	var _status=0;//0|1|2|3 关闭|嵌入|窗口|全屏
	var Images=function(c,t,b,f,convid){
		this.c=c;//中图
		this.t=t;//缩略图
		this.b=b;//大图
		this.f=f;
		this.convId=convid;//所属动太id
	};
	var getPrimaryId=function(obj){
		var s="Pic_Viewer_Img_obj_";
		return s+(obj.oImages.length+1).toString();
	};
	this.oImages=new HashTable();
	this.cPic=null;
	this.sPic=null;
	this.head=null;
	this.insetBefore=null;
	this.PcViewer=null;
	this.pcBoxContainer=null;
	this.thumcardContainer=null;
	this.timer1=null;
	this.timer2=null;
	this.convBox=null;
	this.child=null;
	this.sender=null;
	this.addImages=function(para){//[{smallImage:'',midImage:'',bigImage:''},{smallImage:'',midImage:'',bigImage:''}]
		if(para instanceof Array){
			for(var i=0;i<para.length;i++){
				var one=new Images(para[i].midImage,para[i].smallImage,para[i].bigImage,para[i].orginImage,para[i].convid);
				var pid=getPrimaryId(this);
				this.oImages.push(pid,one);
				if(para[i].isSender)
					this.sPic=pid;
			}
		}
	};
	this.getStatus=function(){
		if(_status==0)
			return 'close';
		else if(_status==1)
			return 'inset';
		else if(_status==2)
			return 'win';
		else if(_status==3)
			return 'screen';
	};
	this.onClose=[];
	this.onStatusChange=[];//窗口状态改变时触发
	this.setStatus=function(s){
		if(s=='inset')
			_status=1;
		else if(s=='win')
			_status=2;
		else if(s=='screen')
			_status=3;
		for(var i=0;i<this.onStatusChange.length;i++){
			this.onStatusChange[i].apply();
		}
	};
};
PictureViewer.prototype={
	addStatusChangeEvt:function(func){
		if(func instanceof Function)
			this.onStatusChange.push(func);
	},
	addCloseEvt:function(func){
		if(func instanceof Function)
			this.onClose.push(func);
	},
	close:function(){
		if(this.PcViewer==null)return;
		this.PcViewer.parentElement.removeChild(this.PcViewer);
		this.PcViewer=null;
		for(var i=0;i<this.onClose.length;i++){
			this.onClose[i].apply();
		}
		if(this.getStatus()=='win'){
			$(".pcview_conver_div").remove();
		}
	},
	closeAll:function(){
		for(var i=0;i<picviewer.length;i++)
		{
			picviewer[i].close();
		}
	},
	getBoxWid:function(){
		var winwid=$(window).width();
		return winwid-140;
	},
	getPicBoxWid:function(){
		return this.getBoxWid()-400;
	},
	toScreen:function(){
		var imgObj=this.oImages.get(this.cPic);
		window.open(imgObj.b);
	},
	toInset:function(){
		this.closeAll();
		this.setStatus('inset');
		$(this.sender).parent().parent().children().hide();
		this.ThumbCard.clientCount=7;
		this.child.loadCImage();
		//html
		var box=document.createElement('div');
		box.setAttribute('class','pcview_inset');
		
		this.PcViewer=box;
		
		var head=document.createElement('div');
		head.setAttribute('class','inset_head');
		
		this.head=head;
		
		var center=document.createElement('div');
		center.setAttribute('class','inset_center');
		
		this.pcBoxContainer=center;
		
		var foot=document.createElement('div');
		foot.setAttribute('class','inset_bottom');
		
		this.thumcardContainer=foot;
		
		head.appendChild(this.createBnts());
		
		box.appendChild(head);
		box.appendChild(center);
		box.appendChild(foot);
		
		this.insetBefore.appendChild(box);
		this.createPicBox();
		this.createThCard();
	},
	toWin:function(){
		this.close();
		var obj=this;
		this.setStatus('win');
		this.ThumbCard.clientCount=16;
		this.child.loadAllImage();
		
		//html
		this.createconver();
		var box=document.createElement('div');
		
		box.setAttribute('class','pcview_win');
		box.style.width=this.getBoxWid().toString()+"px";
		this.PcViewer=box;
		
		var head=document.createElement('div');
		head.setAttribute('class','win_head');
		
		this.head=head;
		
		head.onmouseover=function(event){
			if(checkHover(event,this)){
				if(obj.timer1!=null){
					clearTimeout(obj.timer1);
				}
			}
		}
		head.onmouseout=function(event){
			if(checkHover(event,this)){
				var o=obj;
				obj.timer1=setTimeout(function(){
					$(o.head).hide();
				},200);
			}
		}
		var center=document.createElement('div');
		center.setAttribute('class','win_center');
		
		var conv=document.createElement('div');
		conv.setAttribute('class','win_conv');
		
		var closewin=document.createElement('img');
		closewin.setAttribute('src',"/bundles/fafatimewebase/images/closewin.png");
		closewin.setAttribute('class','close_win_img');
		closewin.onclick=function(){
			obj.close();
		}
		
		
		var normal=document.createElement("div");
		normal.setAttribute('class','topicbox');
		
		
		var ul=document.createElement('ul');
		ul.setAttribute('class','win_conv_ul conv_box');
		normal.appendChild(ul);
		conv.appendChild(normal);
		conv.appendChild(closewin);
		
		this.convBox=conv;
		
		center.appendChild(conv);
		
		this.pcBoxContainer=center;
		
		var foot=document.createElement('div');
		foot.setAttribute('class','win_bottom');
		foot.style.width=this.getPicBoxWid().toString()+"px";
		foot.onmouseout=function(event){
			if(checkHover(event,this)){
				var o=this;
				obj.timer2=setTimeout(function(){
					var p=o;
					var q=obj;
					$(o).slideUp('slow',function(){
						$(p).css({'opacity': '0','filter':'Alpha(Opacity=0)'}).show();
						q.timer2=null;
					});
					//$(o).css({'opacity': '0','filter':'Alpha(Opacity=0)'}).show();
				},3000);
			}
		}
		foot.onmouseover=function(event){
			if(checkHover(event,this)){
				var o=this;
				if(obj.timer2!=null){
					clearTimeout(obj.timer2);
					obj.timer2=null;
					return;
				}
				$(o).hide().css({'opacity': '1','filter':'Alpha(Opacity=100)'});
				$(o).slideDown(200);
			}
		}
		this.thumcardContainer=foot;
		
		head.appendChild(this.createBnts());
		
		box.appendChild(head);
		box.appendChild(center);
		box.appendChild(foot);
		
		document.body.appendChild(box);
		this.setWinPosi();
		this.createPicBox();
		this.createThCard();
		this.child.loadConv();
	},
	setWinPosi:function(){
		$(this.PcViewer).css({
			left:(($(window).width()-$(this.PcViewer).width())/2).toString()+"px",
			top:(40+$(document).scrollTop()).toString()+'px'
		});
	},
	createconver:function(){
		var conver=document.createElement('div');
		conver.setAttribute('class','pcview_conver_div');
		document.body.appendChild(conver);
	},
	createBnts:function(){
		var obj=this;
		if(this.getStatus()=='inset'){
			var div=document.createElement('div');
			div.setAttribute('class','inset_buttons');
			//收起按钮
			var button1=document.createElement('a');
			button1.setAttribute('href','javascript:void(0);');
			button1.setAttribute('class','inset_button_close');
			button1.appendChild(document.createTextNode('收起'));
			button1.onclick=function(){
				obj.close();
			};
			//查看大图按钮
			var button2=document.createElement('a');
			button2.setAttribute('href','javascript:void(0);');
			button2.setAttribute('class','inset_button_win');
			//button2.setAttribute('style','di');
			button2.appendChild(document.createTextNode('查看大图'));
			button2.onclick=function(){
				obj.toWin();
			}
			//查看原图
			var button3=document.createElement('a');
			button3.setAttribute('href','javascript:void(0);');
			button3.setAttribute('class','inset_button_screen');
			button3.appendChild(document.createTextNode('查看原图'));
			button3.onclick=function(){
				obj.toScreen();
			}
			//添加节点
			div.appendChild(button1);
			div.appendChild(button2);
			div.appendChild(button3);
			return div;
		}
		else if(this.getStatus()=='screen'){
			
		}
		else if(this.getStatus()=='win'){
			var div=document.createElement('div');
			div.setAttribute('class','win_buttons');
			//收起按钮
			var button1=document.createElement('a');
			button1.setAttribute('href','javascript:void(0);');
			button1.setAttribute('class','win_button_close');
			button1.appendChild(document.createTextNode('收起'));
			button1.onclick=function(){
				obj.close();
			};
			//查看中图按钮
			var button2=document.createElement('a');
			button2.setAttribute('href','javascript:void(0);');
			button2.setAttribute('class','win_button_inset');
			button2.appendChild(document.createTextNode('查看中图'));
			button2.onclick=function(){
				obj.toInset();
			}
			//查看原图
			var button3=document.createElement('a');
			button3.setAttribute('href','javascript:void(0);');
			button3.setAttribute('class','win_button_screen');
			button3.appendChild(document.createTextNode('查看原图'));
			button3.onclick=function(){
				obj.toScreen();
			}
			//添加节点
			div.appendChild(button1);
			div.appendChild(button2);
			div.appendChild(button3);
			return div;
		}
	},
	createPicBox:function(){
		this.PictureBox.init(this);
		if(this.getStatus()=='inset'){
				
		}
		else if(this.getStatus()=='win'){
		}
		else if(this.getStatus()=='screen'){
		}
	},
	createThCard:function(){
		this.ThumbCard.init(this);
	},
	showImg:function(e){
		var pic=e.getAttribute('Pic');
		this.cPic=pic;
		var Imgobj=this.oImages.get(pic);
		
		var curr_img='';
		if(this.getStatus()=='inset')
			curr_img=Imgobj.c;
		else if(this.getStatus()=='win')
			curr_img=Imgobj.f;
		var obj=this;
		this.PictureBox.imgLoad.style.display='block';
		obj.PictureBox.imgEl.setAttribute('Pic',pic);
		var ttt=this.readimg(curr_img);
		ttt();				
		if(this.getStatus()=='win'){
			this.child.loadConv();
		}
	},
	readimg:function(img){
		var o=this;
		return function(){
			var obj=o;
			var curr_img=img;
			imgReady(curr_img, function () {
							var doc_open_img=$(obj.PictureBox.imgbox), _w= doc_open_img.width() ,_h= doc_open_img.height(),w= this.width ,h= this.height;
							var s_w = (_w/w).toFixed(2)*1,s_h=(_h/h).toFixed(2)*1;
							if(s_w<1 || s_h<1) 
							{
								  s = s_w<s_h ? s_w :s_h;			//当前缩放比例	
								  var l = ((_w-(w*s))/2).toFixed(2)*1,t=Math.max(2,(_h-(h*s)).toFixed(2)*1)/2;
								  $(obj.PictureBox.imgEl).attr("src",curr_img).attr("style","width:"+(w*s).toFixed(2)+"px;height:"+(h*s).toFixed(2)+"px;position:relative;top:"+t+"px");
							}
							else
							{
								 var l = ((_w-w)/2).toFixed(2)*1,t=Math.max(2,((_h-h)/2).toFixed(2)*1);
							   $(obj.PictureBox.imgEl).attr("src",curr_img).attr("style","position:relative;top:"+t+"px");
							}
							$(obj.PictureBox.imgLoad).hide();
						}, function () {
							
						}, function () {
							obj.PictureBox.imgLoad.style.display='none';
				      obj.PictureBox.imgEl.src=obj.PictureBox.img_default_url;			      
			});	
		}
	},
	setPositionCenter:function(e){
		$(this.PictureBox.imgbox).css('height','inherit');
		var hei =e.height;//this.PictureBox.imgEl.height;
		var wid=e.width;//this.PictureBox.imgEl.width;
		this.PictureBox.imgEl.height=hei;
		this.PictureBox.imgEl.width=wid;
		var maxhei=this.PictureBox.imgbox.clientHeight;
		var maxwid=this.PictureBox.imgbox.clientWidth;
		this.PictureBox.imgEl.style="";
		if(wid < maxwid){
			$(this.PictureBox.imgEl).css({"left":"50%","margin-left":("-"+(wid/2).toString()+"px")});
		}
		else{
			$(this.PictureBox.imgEl).css({'max-width':maxwid.toString()+"px",'left':'0','margin-left':'0'});
		}
		if(hei <maxhei){
			$(this.PictureBox.imgEl).css({"top":"50%","margin-top":("-"+(hei/2).toString()+"px")});
		}
		else{
			$(this.PictureBox.imgEl).css({'max-height':maxhei.toString()+"px",'top':'0','margin-top':'0'});
			//$(this.PictureBox.imgbox).css({'height':hei.toString()+"px"});
		}
	},
	ThumbCard:{
		p:null,
		pre_bnt_url:null,
		clientCount:7,
		preBnt:null,
		nexBnt:null,
		nex_bnt_url:null,
		img_default_url:"/bundles/fafatimewebase/images/no_photo.png",//图片浏览时默认图片
		sheetbox:null,
		oImagesClick:function(e){
			var childs=e.parentElement.parentElement.children;
			for(var i=0;i<childs.length;i++){
				if(childs[i].children[0]==e)
					e.parentElement.style.border="2px solid #FFA306";
				else
					childs[i].style.border="1px solid #CCCCCC";
			}
			this.p.showImg(e);
		},
		preClick:function(){
			var pic=this.p.cPic;
			var lis=this.sheetbox.children;
			for(var i=0;i<lis.length;i++){
				if(lis[i].children[0].getAttribute('Pic')==pic && i!=0){
					if($(lis[i-1]).css('display').indexOf('none')>-1){
						if(i-1+this.clientCount<lis.length)
							$(lis[i-1+this.clientCount]).hide();
						$(lis[i-1]).fadeIn();
					}
					$(lis[i-1]).fadeIn();
					this.oImagesClick(lis[i-1].children[0]);
					return;
				}
			}
		},
		nexClick:function(){
			var pic=this.p.cPic;
			var lis=this.sheetbox.children;
			for(var i=0;i<lis.length;i++){
				if(lis[i].children[0].getAttribute('Pic')==pic && i!=lis.length-1){
					if($(lis[i+1]).css('display').indexOf('none')>-1){
						if(i+1-this.clientCount >=0)
							$(lis[i+1-this.clientCount]).hide();
						$(lis[i+1]).fadeIn();
					}
					this.oImagesClick(lis[i+1].children[0]);
					return;
				}
			}
		},
		slide:function(direct){
			if(direct=='left'){
				
			}
			else if(direct=='right'){
				
			}
		},
		load:function(){
			var obj=this.p;
			var obj_p=this;
			var t=this.p.oImages.array;
			if(t.length<this.clientCount){
				this.preBnt.style.display=this.nexBnt.style.display='none';
			}
			for(var i=0;i<t.length;i++){
				var li=document.createElement('li');
				if(i > this.clientCount-1){
					li.setAttribute('style','display:none;');
				}
				var img=document.createElement('img');
				img.setAttribute('src',t[i].val.t);
				img.setAttribute('Pic',t[i].key);
				img.onerror=function(){
					this.src=obj_p.img_default_url;
					obj_p.p.setPositionCenter(this);
				}
				li.appendChild(img);
				li.onclick=function(){
					obj_p.oImagesClick(this.children[0]);
				};
				this.sheetbox.appendChild(li);
			}
			this.oImagesClick($(this.sheetbox).find("img[pic='"+obj.sPic+"']")[0]);
		},
		cPreBnt:function(){
			if(this.p.getStatus()=='inset'){
				var obj=this;
				var span=document.createElement('span');
				span.setAttribute('class','inset_thumb_pre');
				span.onclick=function(){obj.preClick();};
				this.preBnt=span;
				return span;
			}
			else if(this.p.getStatus()=='win'){
				var obj=this;
				var span=document.createElement('span');
				span.setAttribute('class','win_thumb_pre');
				span.onclick=function(){obj.preClick();};
				this.preBnt=span;
				return span;
			}
			else if(this.p.getStatus()=='screen'){
				
			}
		},
		cNexBnt:function(){
			if(this.p.getStatus()=='inset'){
				var obj=this;
				var span=document.createElement('span');
				span.setAttribute('class','inset_thumb_nex');
				span.onclick=function(){obj.nexClick();};
				this.nexBnt=span;
				return span;
			}
			else if(this.p.getStatus()=='win'){
				var obj=this;
				var span=document.createElement('span');
				span.setAttribute('class','win_thumb_nex');
				span.onclick=function(){obj.nexClick();};
				this.nexBnt=span;
				return span;
			}
			else if(this.p.getStatus()=='screen'){
				
			}
		},
		cImgSheet:function(){
			if(this.p.getStatus()=='inset'){
				var ul=document.createElement('ul');
				ul.setAttribute('class','inset_sheet_ul');
				this.sheetbox=ul;
				return ul;
			}
			else if(this.p.getStatus()=='win'){
				var ul=document.createElement('ul');
				ul.setAttribute('class','win_sheet_ul');
				this.sheetbox=ul;
				return ul;
			}
			else if(this.p.getStatus()=='screen'){
				
			}
		},
		init:function(p){
			this.p=p;
			this.p.thumcardContainer.appendChild(this.cPreBnt());
			this.p.thumcardContainer.appendChild(this.cImgSheet());
			this.p.thumcardContainer.appendChild(this.cNexBnt());
			this.load();
		}
	},
	PictureBox:{
		CurrPic:null,
		p:null,
		pre_bnt_url:null,
		nex_bnt_url:null,
		img_default_url:"/bundles/fafatimewebase/images/defaultimg.png",//图片浏览时默认图片
		img_load_url:"/bundles/fafatimewebase/images/loadingsmall.gif",//等待图标
		to_left_cursor_url:"/bundles/fafatimewebase/images/leftcursor.png",
		to_right_cursor_url:"/bundles/fafatimewebase/images/rightcursor.png",
		to_close_cursor_url:"/bundles/fafatimewebase/images/small.cur",
		preClick:function(){},
		nexClick:function(){},
		setCursor:function(){},
		loadImg:function(){},
		imgbox:null,
		imgEl:null,
		imgLoad:null,
		cPreBnt:function(){
			if(this.p.getStatus()=='inset'){
				var div=document.createElement('div');
				div.setAttribute('class','inset_pc_preBnt');
				div.setAttribute('title','上一个');
				
				var bnt=document.createElement('img');
				bnt.setAttribute('src',this.pre_bnt_url);
				
				//div.appendChild(bnt);
				return div;
			}
			else if(this.p.getStatus()=='win'){
				var div=document.createElement('div');
				div.setAttribute('class','win_pc_preBnt');
				div.setAttribute('title','上一个');
				
				var bnt=document.createElement('img');
				bnt.setAttribute('src',this.pre_bnt_url);
				
				//div.appendChild(bnt);
				return div;
			}
			else if(this.p.getStatus()=='screen'){
			}
		},
		cNexBnt:function(){
			if(this.p.getStatus()=='inset'){
				var div=document.createElement('div');
				div.setAttribute('class','inset_pc_nexBnt');
				div.setAttribute('title','下一个');
				var bnt=document.createElement('img');
				bnt.setAttribute('src',this.nex_bnt_url);
				
				//div.appendChild(bnt);
				return div;
			}
			else if(this.p.getStatus()=='win'){
				var div=document.createElement('div');
				div.setAttribute('class','win_pc_nexBnt');
				div.setAttribute('title','下一个');
				var bnt=document.createElement('img');
				bnt.setAttribute('src',this.nex_bnt_url);
				
				//div.appendChild(bnt);
				return div;
			}
			else if(this.p.getStatus()=='screen'){
			}
		},
		cPcbox:function(){
			var obj_p=this;
			if(this.p.getStatus()=='inset'){
				var div=document.createElement('div');
				div.setAttribute('class','inset_pic_box');
				div.onclick=function(event){
					var ev=event||window.event;
					obj_p.conBntshow(ev);
				}
				div.onmousemove=function(event){
					var ev=event||window.event;
					obj_p.conCursorShow(ev);
				}
				this.imgbox=div;
				var img_load=document.createElement('img');
				img_load.setAttribute('class','inset_pic_load');
				img_load.setAttribute('src',this.img_load_url);
				img_load.setAttribute('style','display:none');
				
				var img=document.createElement('img');
				img.setAttribute('class','inset_pic_img');
				img.setAttribute('src',this.img_default_url);
				img.setAttribute('Pic','');
				img.onerror=function(){
					this.src=obj_p.img_default_url;
				}
				img.onmouseover=function(){
					obj_p.imgEl.setAttribute('over','1');
				}
				img.onmouseout=function(){
					obj_p.imgEl.setAttribute('over','0');
				}
				this.imgEl=img;
				
				this.imgLoad=img_load;
				
				div.appendChild(img_load);
				div.appendChild(img);
				return div;
			}
			else if(this.p.getStatus()=='win'){
				var div=document.createElement('div');
				div.setAttribute('class','win_pic_box');
				div.style.width=this.p.getPicBoxWid().toString()+"px";
				$(div).css('min-height',($(window).height()-80).toString()+"px");
				div.onclick=function(event){
					var ev=event||window.event;
					obj_p.conBntshow(ev);
				}
				div.onmousemove=function(event){
					var ev=event||window.event;
					obj_p.conCursorShow(ev);
				}
				div.onmouseover=function(event){
					if(checkHover(event,this)){
						if(obj_p.p.timer1!=null)
							clearTimeout(obj_p.p.timer1);
						$(obj_p.p.head).show();
					}
				}
				div.onmouseout=function(event){
					if(checkHover(event,this)){
						var head=obj_p.p.head;
						obj_p.p.timer1=setTimeout(function(){
							$(head).hide();
						},200);
					}
				}
				this.imgbox=div;
				var img_load=document.createElement('img');
				img_load.setAttribute('class','win_pic_load');
				img_load.setAttribute('src',this.img_load_url);
				img_load.setAttribute('style','display:none');
				
				var img=document.createElement('img');
				img.setAttribute('class','win_pic_img');
				img.setAttribute('src',this.img_default_url);
				img.setAttribute('Pic','');
				img.onerror=function(){
					this.src=obj_p.img_default_url;
				}
				img.onmouseover=function(){
					obj_p.imgEl.setAttribute('over','1');
				}
				img.onmouseout=function(){
					obj_p.imgEl.setAttribute('over','0');
				}
				this.imgEl=img;
				
				this.imgLoad=img_load;
				
				div.appendChild(img_load);
				div.appendChild(img);
				return div;
			}
			else if(this.p.getStatus()=='screen'){
				
			}
		},
		conCursorShow:function(ev){
			if((ev.clientX-$(this.imgbox).offset().left)<this.imgbox.clientWidth/4){
				if(this.p.cPic==this.p.oImages.array[0].key)
					this.imgbox.style.cursor='default';
				else
					this.imgbox.style.cursor="url("+this.to_left_cursor_url+"),pointer";
			}
			else if((ev.clientX-$(this.imgbox).offset().left)<(this.imgbox.clientWidth*3)/4){
				if(this.imgEl.getAttribute('over')=='1')
					this.imgbox.style.cursor="url("+this.to_close_cursor_url+"),pointer";
				else
					this.imgbox.style.cursor="default";
			}
			else{
				if(this.p.cPic==this.p.oImages.array[this.p.oImages.length-1].key)
					this.imgbox.style.cursor='default';
				else
					this.imgbox.style.cursor="url("+this.to_right_cursor_url+"),pointer";
			}
			
			//win
			if(this.p.getStatus()=='win'){
				//alert(ev.clientY.toString()+"|"+$(document).scrollTop().toString()+"|"+$(this.imgbox).offset().top.toString()+","+this.imgbox.clientHeight.toString());
				if((ev.clientY+$(document).scrollTop()-$(this.imgbox).offset().top)>(this.imgbox.clientHeight*4)/5){
					/*
					if(this.p.timer2!=null){
						clearTimeout(this.p.timer2);
					}
					$(this.p.thumcardContainer).show();
					*/
				}
				else{
					/*
					var obj=this.p;
					this.p.timer2=setTimeout(function(){$(obj.thumcardContainer).hide();},200);
					*/
				}
			}
		},
		conBntshow:function(ev){
			if((ev.clientX-$(this.imgbox).offset().left)<this.imgbox.clientWidth/4){
				this.p.ThumbCard.preClick();
			}
			else if((ev.clientX-$(this.imgbox).offset().left)<(this.imgbox.clientWidth*3)/4){
				if(this.imgEl.getAttribute('over')=='1')
					this.p.close();
			}
			else
				this.p.ThumbCard.nexClick();
		},
		init:function(p){
			this.p=p;
			var Pcbox=this.cPcbox();
			var NexBnt=this.cNexBnt();
			var PreBnt=this.cPreBnt();
			Pcbox.appendChild(NexBnt);
			Pcbox.appendChild(PreBnt);
			
			this.p.pcBoxContainer.appendChild(Pcbox);
		}
	},
	init:function(params){
		this.child=params.child;
		this.toInset();
	},
	reload:function(){
	}
};
var PictureSNSViewer=function(){
	PictureViewer.call(this);
	var pathImage='/viewimage/';
	var pathBImage='/getfile/image/original/';
	var getImagesUrl=function(imglist){
		var url=imglist.url;
		var s=url.split('/');
		var mogoId=s[s.length-1];
		return {
			smallImage:url,
			midImage:url.replace('/small/','/middle/'),
			bigImage:pathImage+mogoId,
			orginImage:pathBImage+mogoId,
			convid:imglist.convid,
			isSender:imglist.isSender
		};
	};
	this.Cid=null;
	this.CloseCallBack=null;
	this.load_conv_url=null;
	this.boxshow=function(para){
		this.insetBefore=para.res;
		this.sender=para.sender;
		this.load_conv_url=para.load_conv_url;
		this.Cid=para.ImgList[0].convid;
		this.CloseCallBack=para.onClose;
		this.addCloseEvt(this.CloseCallBack);
		this.init({
			child:this
		});
	};
	this.loadCImage=function(){
		if(this.oImages.length==1 && this.oImages.array[0].val.convid==this.Cid)return;
		this.oImages.clear();
		var imgurls=[];
		var convid=this.Cid;
		var $images=$("div.contentbox").length >0?$("div.contentbox").children(".hover").find("div.convbox[conv_id='"+convid+"']").find(".disp_attachs_imgs").find("span.attach_item").find('img'):$("div.convbox[conv_id='"+convid+"']").find(".disp_attachs_imgs").find("span.attach_item").find('img');
		for(var j=0;j<$images.length;j++){
			if($images[j]==this.sender)
				imgurls.push({'convid':convid,'isSender':true,'url':$($images[j]).attr('src')});
			else
				imgurls.push({'convid':convid,'isSender':false,'url':$($images[j]).attr('src')});
		}
		var params=[];
		for(var i=0;i<imgurls.length;i++){
			params.push(getImagesUrl(imgurls[i]));
		}
		this.addImages(params);
	};
	this.loadAllImage=function(){
		this.oImages.clear();
		var convs=$("div.contentbox").length >0?$("div.contentbox").children(".hover").find("div.convbox"):$("div.convbox");
		var imgurls=[];
		for(var i=0;i<convs.length;i++){
			var convid=$(convs[i]).attr('conv_id');
			var $images=$(convs[i]).find(".disp_attachs_imgs").find("span.attach_item").find('img');
			for(var j=0;j<$images.length;j++){
				if($images[j]==this.sender)
					imgurls.push({'convid':convid,'isSender':true,'url':$($images[j]).attr('src')});
				else
					imgurls.push({'convid':convid,'isSender':false,'url':$($images[j]).attr('src')});
			}
		}
		var params=[];
		for(var i=0;i<imgurls.length;i++){
			params.push(getImagesUrl(imgurls[i]));
		}
		this.addImages(params);
	};
};
PictureSNSViewer.prototype=new PictureViewer();
PictureSNSViewer.prototype.constructor=PictureSNSViewer;
PictureSNSViewer.prototype.LoadComponent=function(data){
	$(this.convBox).find('li').hide();
	if($(this.convBox).find("li[convId='"+data.convId+"']").length>0){
		$(this.convBox).find("li[convId='"+data.convId+"']").show();
		return;
	}
	$(this.convBox).find("ul").append("<li class='clearfix' convId='"+data.convId+"'></li>");
	$(this.convBox).find("li:last").append("<div class='urlloading'><div /></div>");
	var e=$(this.convBox).find("li:last");
	var obj=this;
  $(e).load(this.load_conv_url, $.extend({t: new Date().getTime()}, data),
    function () 
    {
    	var o=obj;
      $(e).find(".urlloading").remove();
      $(e).find("div.disp_attachs_imgs").find('img').attr('onclick','');
      $(e).find("div.disp_attachs_imgs").find('img').unbind('click').bind('click',function(){
      	o.ThumbCard.oImagesClick($(o.ThumbCard.sheetbox).find("img[pic='"+obj.cPic+"']")[0]);
      });
    });
}
PictureSNSViewer.prototype.loadConv=function(){
	var OImage=this.oImages.get(this.cPic);
	var curr_conv_id=OImage.convId;
	this.LoadComponent({'convId':curr_conv_id});
}