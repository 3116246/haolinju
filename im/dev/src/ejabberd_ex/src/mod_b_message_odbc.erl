%%向全体推送的离线业务消息处理模块
-module(mod_b_message_odbc).
-author('krisli').

-behaviour(gen_mod).

-export([start/2, stop/1,user_online/3,reg/1,update_unread_flag/1]).

%-include_lib("stdlib/include/qlc.hrl").
%-include("../../../includes/stdlib/include/qlc.hrl").
%-define(NS_MACC,       "http://im.en.com/namespace/microaccount").

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, _Opts) ->
    %%ejabberd_hooks:add(sm_remove_connection_hook, Host,?MODULE, user_offline, 50),
    ejabberd_hooks:add(sm_register_connection_hook, Host,
                               ?MODULE, user_online, 70).

%%%----------------------------------------------------------------------
stop(Host) ->
    %%ejabberd_hooks:delete(sm_remove_connection_hook, Host,?MODULE, user_offline, 50),
    ejabberd_hooks:delete(sm_register_connection_hook, Host,
                               ?MODULE, user_online, 70).
reg(CodeList)->
  io:format("~p~n",[CodeList]),
  [ets:insert(push_business_mods, {C,?MODULE})||C<-CodeList]
  %%ets:insert(push_business_mods, {"removeStaff",?MODULE}),
  %%ets:insert(push_business_mods, {"newstaff",?MODULE}),
  %%ets:insert(push_business_mods, {"createDept",?MODULE}),
  %%ets:insert(push_business_mods, {"removeDept",?MODULE}),
  %%ets:insert(push_business_mods, {"editDept",?MODULE})
.

update_unread_flag([_From1,Jids])->
  set_lastreadid(Jids),
  ok
.

user_online(_SID, JID, _Info) ->
	Operator = JID#jid.luser ++ "@" ++ JID#jid.lserver,
	Msgs = get_message(Operator),
	case Msgs of []-> [];
	_->
		lists:foreach(fun(Item)->
		      		{xmlelement,Name,Attrs,Text}=xml_stream:parse_element(element(1,Item)),
		      		%%NewAtts=lists:keyreplace("sendtime",1,Attrs,{"sendtime",element(2,Item)}),
		      		[Y,M,D,Hh,Mi,Ss]=string:tokens(element(2,Item),"- :"),
		      		NowTime=hd(calendar:local_time_to_universal_time_dst({{list_to_integer(Y),list_to_integer(M),list_to_integer(D)},{list_to_integer(Hh),list_to_integer(Mi),list_to_integer(Ss)}})),
              OfflineAttr = [
        		    	jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", JID#jid.server, ""),"Offline Storage"),
						  jlib:timestamp_to_xml(NowTime)],
		      		Ms={xmlelement,Name,Attrs,Text++OfflineAttr},
		      		ejabberd_sm:route(jlib:string_to_jid(element(2,lists:keyfind("from",1,Attrs))), JID, Ms)
    end,Msgs),
    set_lastreadid([Operator])
	end,
  []
.

set_lastreadid(Qusr)->    
    A = ejabberd_odbc:sql_query("",
           ["select max(id) from im_b_msg"]
        ),      
    ARows = ejabberdex_odbc_query:extract_resultrows(A), 
    Maxid = case ARows of []-> "0";
    _->
        element(1,hd(ARows))
    end,
    InsValue = lists:map(fun(Jid2)->
      A2 = ejabberd_odbc:sql_query("",
             ["select 1 from im_b_msg_read where employeeid='",Jid2,"'"]
      ),

      Rs = ejabberdex_odbc_query:extract_resultrows(A2),
      case Rs of []-> 
        lists:append([",('",Jid2,"',",(Maxid),",now())"]);        
      _->
        [Jid2]
      end
    end,Qusr),
    %%获取新加入的jid列表
    InsValue2 = lists:filter(fun(Item)-> case Item of [$,|_]-> true;_-> false end end,InsValue),
    case InsValue2 of []->
        skip;
      _->
        [F|H]=InsValue2,
        [F1|H1]=F,
        Tmp=lists:append([[H1],H]),
        InsSql=[["insert into im_b_msg_read(employeeid,lastid,readdatetime)values"++ lists:append(Tmp)]],
        ejabberdex_odbc_query:sql_transaction(InsSql)
    end,
    %%获取更新的jid列表
    InsValue3 = lists:filter(fun(Item)-> case Item of [$,|_]-> false;_-> true end end,InsValue),
    Where = "''"++lists:append([",'"++Jid++"'"||Jid<-InsValue3]), %%拼接In条件值
    Asqls = [
                 ["update im_b_msg_read set lastid=",(Maxid)," where employeeid in (",Where,")"]
               ],
    ejabberdex_odbc_query:sql_transaction(Asqls)  
.


get_message(Qusr)->
    A = ejabberd_odbc:sql_query("",
           ["select lastid from im_b_msg_read where employeeid='",Qusr,"'"]
        ),      
    ARows = ejabberdex_odbc_query:extract_resultrows(A),
    Readid = case ARows of []-> "0";
    _->
        element(1,hd(ARows))
    end,
    A1 = ejabberd_odbc:sql_query("",
           ["select b.msg,b.created from im_b_msg b where b.id>",(Readid)]
        ),
  	ARows1 = ejabberdex_odbc_query:extract_resultrows(A1),
  	ARows1
.