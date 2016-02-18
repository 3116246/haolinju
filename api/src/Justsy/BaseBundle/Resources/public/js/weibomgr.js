var WeiboMgr={
	sina_auth_url:'',
	weixin_auth_url:'',
	tencent_auth_url:'',
	init:function(params){
		WeiboMgr.sina_auth_url=params.sina_auth_url;
		WeiboMgr.weixin_auth_url=params.weixin_auth_url;
		WeiboMgr.tencent_auth_url=params.tencent_auth_url;
	},
	addSinaAccount:function(){
		$("#add_sina").modal("show");
		$("#add_sina").find("iframe").attr('src',WeiboMgr.sina_auth_url);
	},
	addWeixinAccount:function(){
		
	},
	addTencentAccount:function(){
		$("#add_tencent").modal("show");
		$("#add_tencent").find("iframe").attr('src',WeiboMgr.tencent_auth_url);
	}
}