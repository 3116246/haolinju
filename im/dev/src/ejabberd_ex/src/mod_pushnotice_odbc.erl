-module(mod_pushnotice_odbc).
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
                               ?MODULE, user_online, 60).

%%%----------------------------------------------------------------------
stop(Host) ->
    %%ejabberd_hooks:delete(sm_remove_connection_hook, Host,?MODULE, user_offline, 50),
    ejabberd_hooks:delete(sm_register_connection_hook, Host,
                               ?MODULE, user_online, 60).
reg(CodeList)->
	io:format("~p~n",[CodeList]),
	[ets:insert(push_business_mods, {C,?MODULE})||C<-CodeList]
 	%%ets:insert(push_business_mods, {"push-notice-reply",?MODULE})
.

update_unread_flag([_From1,_Jids])->
  %%set_lastreadid(From1,Jids)
  ok
.

user_online(_SID, JID, _Info) ->
	Operator = JID#jid.luser ++ "@" ++ JID#jid.lserver,
	Msgs = get_message(Operator),
	case Msgs of []-> [];
	_->
		lists:map(fun(Item)->
		      		{xmlelement,Name,Attrs,Text}=xml_stream:parse_element(element(1,Item)),
		      		%%NewAtts=lists:keyreplace("sendtime",1,Attrs,{"sendtime",element(2,Item)}),
		      		[Y,M,D,Hh,Mi,Ss]=string:tokens(element(2,Item),"- :"),
		      		NowTime=hd(calendar:local_time_to_universal_time_dst({{list_to_integer(Y),list_to_integer(M),list_to_integer(D)},{list_to_integer(Hh),list_to_integer(Mi),list_to_integer(Ss)}})),
        			OfflineAttr = [
        		    	jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", JID#jid.server, ""),"Offline Storage"),
						jlib:timestamp_to_xml(NowTime)],
		      		Ms={xmlelement,Name,Attrs,Text++OfflineAttr},
		      		ejabberd_sm:route(jlib:string_to_jid(element(2,lists:keyfind("from",1,Attrs))), JID, Ms)
		end,Msgs)
	end,
	[]
.


get_message(Qusr)->
    A = ejabberd_odbc:sql_query("",
           ["select b.msg,b.created from im_pushnotice_memebr a,im_pushnotice_msg b where a.noticeid=b.id and a.receive_time is null and a.employeeid='"++Qusr++"'"]
        ),  		
  	ARows = ejabberdex_odbc_query:extract_resultrows(A),
  	ARows
.