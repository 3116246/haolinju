-module(im_group_odbc).
-author('liling@lli2').

-export([
         init/0,          %%��ʼ��
         gettypelist/0,
         newGroup/7,
         adduser/2]).      
         
         

%%---------------------------------
%% ��ʼ��������ݱ�
%%---------------------------------           
init()->
  ok.         

gettypelist()->ejabberdex_odbc_query:get_grouptype().

%%==============================================================
%%������Ⱥ�� 
%%    ���У�
%%         Gname:Ⱥ����
%%         Gclass:Ⱥ����
%%         Gdesc:Ⱥ����
%%         Gcreator:Ⱥ������
%%         Gauth:��֤����.�ο�im_group_const.hrl��ض���
%%         Gaccess:���ʿ���.�ο�im_group_const.hrl��ض���
%%==============================================================
newGroup(Gname,Gclass,Gdesc,Gpost,Gcreator,Gauth,Gaccess)-> 
    Uid = mod_group_odbc:new_group_id(),
    ejabberdex_odbc_query:create_new_group(Uid,Gname,Gclass,Gdesc,Gpost,Gcreator,Gauth,Gaccess)
  . 
  
%%------------------------------------------------
%%  ����³�Ա���ǳƺͱ�ע��Ϊ�գ���Ҫ��Ա�Լ��޸�
%%              ��ɫĬ��Ϊ��ͨ��Ա��
%% �����Ƕ����Ա�����ɵĳ�Ա�б�
%%------------------------------------------------
adduser(Gid,Empid)->
  if is_list(Empid)==true ->
     if Empid==[] -> ok;
     true ->
	      mod_group_odbc:add_groupmember_notify(Gid,hd(Empid)),
        adduser(Gid,tl(Empid))
     end;
  true->
     throw({error,"����Ĳ�������(ֻ�����ַ�����list)!"})
  end.  