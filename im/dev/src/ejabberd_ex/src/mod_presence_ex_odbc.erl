%%%----------------------------------------------------------------------
%%% 4	出席状态管理
%%%----------------------------------------------------------------------

%%% @doc 出席状态管理.
%%%
%%%----------------------------------------------------------------------
-module(mod_presence_ex_odbc).
-author('feihu929@sina.com').

-behaviour(gen_mod).

-export([start/2, stop/1,
         process_iq/3,
         process_local_iq/3, process_iq_get/3, process_iq_set/3,user_lose_connection/3,
         subscribe/3, on_set_presence/4, on_unset_presence/4, broadcast_msg/2, on_offline_push/3
         ]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../../ejabberd-2.1.11/include/mod_roster.hrl").
-include("../include/mod_presence_ex.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, Opts) ->
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),    
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_USERSTATE,
                                  ?MODULE, process_iq, IQDisc),
    ejabberd_hooks:add(set_presence_hook, Host,
		       ?MODULE, on_set_presence, 50),
    ejabberd_hooks:add(unset_presence_hook, Host,
		       ?MODULE, on_unset_presence, 50),
    ejabberd_hooks:add(offline_message_hook, Host,
        ?MODULE, on_offline_push, 49).

%%%----------------------------------------------------------------------
stop(Host) ->
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_USERSTATE),
    ejabberd_hooks:delete(set_presence_hook, Host,
			  ?MODULE, on_set_presence, 50),
    ejabberd_hooks:delete(unset_presence_hook, Host,
        ?MODULE, on_unset_presence, 50),
    ejabberd_hooks:delete(offline_message_hook, Host,
        ?MODULE, on_offline_push, 49).

%%%----------------------------------------------------------------------
%%% 处理IQ节内容
process_iq(From, To, IQ) ->
    #iq{sub_el = SubEl} = IQ,
    #jid{lserver = LServer} = From,
    case lists:member(LServer, ?MYHOSTS) of
	true ->
	    process_local_iq(From, To, IQ);
	_ ->
	    IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]}
    end.

process_local_iq(From, To, #iq{type = Type} = IQ) ->
    case Type of
	set ->
	    process_iq_set(From, To, IQ);
	get ->
	    process_iq_get(From, To, IQ)
    end.

%%%----------------------------------------------------------------------
%% GET相关功能
process_iq_get(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "online"-> notifyOnlineRoster(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% SET相关功能
process_iq_set(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "subscribe" -> subscribe(From, To, IQ);
          "kill" -> kill(From, To, IQ);
          "applypush" -> applypush(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%用户离线，主要处理客户端通过非正常退出方式，服务端自动判断为离线的情况
%%主要是mod_ping模块
user_lose_connection(User, Server, Resource)->
  %%?ERROR_MSG("~p unavailable~n  StackTrace:~p", [JID]),
  Packet = {xmlelement, "presence",
              [{"type", "unavailable"}], []},
  Operator = User ++ "@" ++ Server,
  JID = jlib:make_jid(User, Server, Resource),
  Rosters = ejabberdex_odbc_query:get_online_roster(Operator),
  %%?ERROR_MSG("~p Rosters ~p~n", [Rosters]),
  F = fun(Item)-> 
      ToJid = jlib:string_to_jid(element(3, Item)),
      ejabberd_sm:route(JID, ToJid, Packet)
  end,
  lists:foreach(F, Rosters)
.

%%获取在线的好友，并发出席，用于WEBIM刷新页面attach时
notifyOnlineRoster(From, _To, #iq{sub_el = SubEl} = IQ)->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,   
    
    Pres = {xmlelement, "presence", [{"type", "available"}], []},
    Rosters = ejabberdex_odbc_query:get_online_roster(Operator),
    F = fun(Item)-> 
      FromJid = jlib:string_to_jid(element(3, Item)),
      ejabberd_sm:route(FromJid, From, Pres)
    end,  
    lists:foreach(F, Rosters),
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	  IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end
.

%%%----------------------------------------------------------------------
%%% 4.2.1	订阅/取消订阅单位
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <subscribe xmlns='http://im.fafacn.com/namespace/userstate'>
%%%     <item rid='deptid1' rtype='0' action='subscribe'/>
%%%     <item rid='deptid2' rtype='0' action='unsubscribe'/>
%%%     <item rid='groupid_10001' rtype='1' action='subscribe'/>
%%%     <item rid='groupid_10002' rtype='1' action='unsubscribe'/>
%%%     <item rid='XXX@fafacn.com' rtype='2' action='subscribe'/>
%%%     <item rid='XXX@fafacn.com' rtype='2' action='unsubscribe'/>
%%%     <item rid='eno' rtype='3' action='subscribe'/>
%%%     <item rid='eno' rtype='3' action='unsubscribe'/>
%%%   </subscribe>
%%% </iq>
%%% 返回值 IQ节
subscribe(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    %% 取出Item
    {xmlelement, _Name, _Attrs, Items} = SubEl,
        
    %% 保存
    FunA = fun(EItem) ->
      case EItem of
        {xmlelement, "item", _, _} ->          
          AArtype = xml:get_tag_attr_s("rtype", EItem),
          Artype = (case AArtype of "1" -> AArtype; "2" -> AArtype; "3" -> AArtype;  _-> "0" end),  %% rtype默认为0 部门
          %%rtype为3表示是订阅的当前企业，rid未指定时默认为当前用户的企业号
          Arid =case Artype of 
          		"3"->
          				TmpRid=xml:get_tag_attr_s("rid", EItem) ,
          				TmpRid2=case TmpRid  of
          				 	[]->
          						lists:nth(2,[binary_to_list(Ele)||Ele<-re:split( From#jid.luser,"-")]) ; %%取企业号
          					_->
          					  %%订阅指定的外单位。暂时不支持此订阅
          					 TmpRid
          				end,
          				TmpRid2;
          		_-> xml:get_tag_attr_s("rid", EItem) 
          end,
          Aaction = xml:get_tag_attr_s("action", EItem),
         
          %% action默认为订阅subscribe
          if
            Aaction == "unsubscribe" ->
              ejabberdex_odbc_query:del_subscribe_ex(From, Arid, Artype);
            true ->
              Asubscribe_ex = #subscribe_ex{jid = From, rid = Arid, rtype = Artype},
              ejabberdex_odbc_query:set_subscribe_ex(Asubscribe_ex),
             
              %发送该部门/群中非好友在线成员的出席状态给当前用户
              ALjid = case Artype of 
                "1" ->
                  %%订阅群组
                  ejabberdex_odbc_query:get_online_jid_bygroupfrom(Operator, Arid);
                "2"->
                    %%指定的人员
                    AJid=jlib:string_to_jid(Arid),
                    case ejabberd_sm:get_user_resources(AJid#jid.luser,AJid#jid.lserver)
                     of []->[];
                     Res-> [jlib:string_to_jid(AJid#jid.luser++"@"++AJid#jid.lserver++"/"++R)||R<-Res]
                    end;
                "3"->
                    %%订阅的自己所在的企业。一般由需要状态实时感知功能的应用进行订阅                    
                    ejabberdex_odbc_query:get_online_jid_byenterprisefrom(Operator, Arid);
                _ ->
                  %%订阅企业部门
                  ejabberdex_odbc_query:get_online_jid_bydeptfrom(Operator, Arid)
              end,
              
              %发送出席
              Pres = {xmlelement, "presence", [], []},
              FSend = fun(EItemX, AccIn) ->
                ejabberd_sm:route(EItemX, From, AccIn),
                AccIn      
              end,
              lists:foldl(FSend, Pres, ALjid)
          end; 
        _ -> continue
      end  
    end,
    lists:foreach(FunA, Items),
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 4.4 踢掉自己的已登录帐号
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <kill xmlns='http://im.fafacn.com/namespace/userstate' res='FaFaWin'>
%%%   </kill>
%%% </iq>
%%% 返回值 IQ节
kill(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    % Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    Ares = case xml:get_tag_attr_s("res", SubEl) of
              []-> "asdfzxcv";
              Nick-> Nick
           end,
    ejabberd_sm:check_existing_resources(From#jid.luser, From#jid.lserver, Ares),
    case ejabberd_sm:get_resource_sessions(From#jid.luser, From#jid.lserver, Ares) of
      [{_, Pid}] ->
        Pid ! replaced;
      _ ->
        ok
    end,
  
    IQ#iq{type = result, sub_el = []}
  catch 
    Ec:Ex ->  
            ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
  IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 4.5 申请PUSH消息
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <applypush xmlns='http://im.fafacn.com/namespace/userstate' 
%%% devtoken='4E493BDC0D0B8EC25C57AFA2AA9759A16B67E20DC992BB7C4C4CAB73516A136A'>
%%%   </applypush>
%%% </iq>
%%% 返回值 IQ节
applypush(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Res = From#jid.lresource,
    Adevtoken = case xml:get_tag_attr_s("devtoken", SubEl) of
              []-> "";
              X-> X
           end,

    ejabberdex_odbc_query:set_pushtoken(Operator, Res, Adevtoken),
  
    IQ#iq{type = result, sub_el = []}
  catch 
    Ec:Ex ->  
            ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
  IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 服务器监测每个客户端发送的上线及普通出席信息，即presence的type属性未设置
on_set_presence(User, Server, Resource, Presence) ->
  try
    %%每个用户上线时，默认订阅自己所在企业的公共帐号部门
    Eno = func_utils:getOrgRootID(User++"@"++Server),
    MakeJid = jlib:make_jid(User, Server, Resource),
    Asubscribe_ex = #subscribe_ex{jid = MakeJid, rid = Eno++"999", rtype = "0"},
    ejabberdex_odbc_query:set_subscribe_ex(Asubscribe_ex),
    broadcast_msg(MakeJid, Presence)
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%%----------------------------------------------------------------------
%%% 检测到presence的type属性为unavailable
on_unset_presence(User, Server, Resource, _Status) ->
  try
    From = jlib:make_jid(User, Server, Resource),
    %删除订阅
    ejabberdex_odbc_query:del_subscribe_ex(From),
    %发送离线pres
    Pres = {xmlelement, "presence", [{"type", "unavailable"}], []},
    broadcast_msg(From, Pres)   
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%%----------------------------------------------------------------------
%%% 转发pres
%%% From jid
%%% Pres 待发送xml
broadcast_msg(From, Pres) ->
  Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
  %查找订阅了当前人员所属部门、群，并且不是该人好友的人员JID
  ASjid = ejabberdex_odbc_query:get_subscribe_ex_jid_byfrom(Operator),
  %发送
  FSend = fun(EItem, AccIn) -> 
    ejabberd_sm:route(From, EItem, AccIn),
    AccIn      
  end,
  lists:foldl(FSend, Pres, ASjid).

%%%----------------------------------------------------------------------
%%% 检测到有离线消息时，推送
on_offline_push(From, To, Packet) ->
    ANeedPush = check_need_push(Packet),
    Type = xml:get_tag_attr_s("type", Packet),
    if
      (Type /= "error") and (Type /= "groupchat") and
      (Type /= "headline") and ANeedPush ->
        Operator = To#jid.luser ++ "@" ++ To#jid.lserver,
        % Res = To#jid.lresource,
        case ejabberdex_odbc_query:get_pushtoken(Operator, "IPhone") of
          [Row] ->
            Adevtoken = element(3, Row),
            Abadgenum = 1+list_to_integer(element(5, Row)),
            Aalert = list_to_binary(genAlert(From, Packet)),
            apns:send_message(apns_conn, Adevtoken, Aalert, Abadgenum, "default"),
            ok;
          _ ->
            ok
        end;
      true ->
          ok
    end.

%%%检查该包是否需要PUSH
check_need_push(Packet) ->
  case Packet of
    {xmlelement, _, _, []} ->
      false;
    {xmlelement, _, _, [{xmlelement, "active", _, _}]} ->
      false;
    {xmlelement, _, _, [{xmlelement, "inactive", _, _}]} ->
      false;
    {xmlelement, _, _, [{xmlelement, "composing", _, _}]} ->
      false;
    {xmlelement, _, _, [{xmlelement, "gone", _, _}]} ->
      false;
    {xmlelement, _, _, [{xmlelement, "paused", _, _}]} ->
      false;
    _ ->
      true
  end.

%%% 生成push时的消息
genAlert(From, Packet)->
  Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
  Aemps = ejabberdex_odbc_query:get_emp_by_account(Operator),
  case Aemps of
    [#employee{employeename=Aemployeename}] ->
      Amsgstr = case xml:get_subtag(Packet, "body") of
        false ->
          case xml:get_subtag(Packet, "groupchat") of
            false ->
              "[业务消息]";
            AEl ->
              case xml:get_subtag(AEl, "text") of
                false ->
                  "[群消息]";
                TEl ->
                  Atextstr = xml:get_tag_cdata(TEl),
                  encodePushMsg(Atextstr)
              end
          end; 
        BEl ->
          Abodystr = xml:get_tag_cdata(BEl),
          encodePushMsg(Abodystr)
      end,
      [Aemployeename, "：", Amsgstr];
    _ ->
      "新消息"
  end.

encodePushMsg(Abodystr)->
  case re:run(Abodystr, "\\{\\(.{32}\\..{3,5}\\)\\}") of
    {match, _} ->
      "[语音消息]";
    _ ->
      case re:run(Abodystr, "\\{..{32}\\..{3,5}.\\}") of
        {match, _} ->
          "[图片消息]";
        _ ->
          string:left(Abodystr, 100)
      end
  end.

%%%----------------------------------------------------------------------
%%% 取得订阅了出席的好友列表
%%% User jid
%%% 返回[#roster]|[]
%get_roster_subscribe(User) ->
%  ALroster1 = mnesia:dirty_index_match_object(#roster{us={User#jid.luser, User#jid.lserver}, _ = '_'}, us),
%  ALroster = lists:filter(fun(EItem) -> EItem#roster.subscription == both orelse EItem#roster.subscription == to end, ALroster1),
%  ALroster.
%%%----------------------------------------------------------------------
