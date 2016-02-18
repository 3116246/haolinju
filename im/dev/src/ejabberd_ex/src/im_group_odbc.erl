-module(im_group_odbc).
-author('liling@lli2').

-export([
         init/0,          %%初始化
         gettypelist/0,
         newGroup/7,
         adduser/2]).      
         
         

%%---------------------------------
%% 初始化相关数据表
%%---------------------------------           
init()->
  ok.         

gettypelist()->ejabberdex_odbc_query:get_grouptype().

%%==============================================================
%%创建新群。 
%%    其中：
%%         Gname:群名称
%%         Gclass:群分类
%%         Gdesc:群描述
%%         Gcreator:群创建者
%%         Gauth:验证方法.参考im_group_const.hrl相关定义
%%         Gaccess:访问控制.参考im_group_const.hrl相关定义
%%==============================================================
newGroup(Gname,Gclass,Gdesc,Gpost,Gcreator,Gauth,Gaccess)-> 
    Uid = mod_group_odbc:new_group_id(),
    ejabberdex_odbc_query:create_new_group(Uid,Gname,Gclass,Gdesc,Gpost,Gcreator,Gauth,Gaccess)
  . 
  
%%------------------------------------------------
%%  添加新成员。昵称和备注都为空，需要成员自己修改
%%              角色默认为普通成员。
%% 可以是多个人员编号组成的成员列表
%%------------------------------------------------
adduser(Gid,Empid)->
  if is_list(Empid)==true ->
     if Empid==[] -> ok;
     true ->
	      mod_group_odbc:add_groupmember_notify(Gid,hd(Empid)),
        adduser(Gid,tl(Empid))
     end;
  true->
     throw({error,"错误的参数类型(只允许字符串和list)!"})
  end.  