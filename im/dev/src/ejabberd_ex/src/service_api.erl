-module(service_api).

-behaviour(gen_server).
-behaviour(gen_mod).

-export([start_link/2,start/2,stop/1]).
-export([init/1,handle_info/2, handle_call/3,handle_cast/2,terminate/2,code_change/3,receivemsg/1,deal/2]).

-export([send/1,sendMsg/1,sendMicroMsg/1,sendPresence/1,sendBroadcast/1,sendRemind/1,removeBroadcast/1,removeRemind/1,getFaFaAccount/3,send/6,getWebOfflineFile/1]).
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../../ejabberd_ex/include/mod_ejabberdex_init.hrl").

-define(PROCNAME, push_messages_service).

start_link(Host, Opts) ->    
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    gen_server:start_link({local, Proc}, ?MODULE, [Host, Opts], []).
    
start(Host, Opts) ->
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    ChildSpec =
        {Proc,
         {?MODULE, start_link, [Host, Opts]},
         permanent,
         100,
         worker,
         [?MODULE]},
    supervisor:start_child(ejabberd_sup, ChildSpec).  

stop(Host) ->
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    %%gen_server:call(Proc, stop),
    receive_push_msg!{stop},
    supervisor:delete_child(ejabberd_sup, Proc).

init([Host, _Opts]) ->
    ets:new(push_msg_list, [set, public, named_table]), 
    checkDealProccess(Host),   
    register(receive_push_msg,spawn(?MODULE, receivemsg, [Host])),  
    busmod_reg(),
	io:format("push service init finished~n"),     
  	{ok,[]}
.

busmod_reg()->
    %%推送业务模块配置：
    %%{service_api,[{once_messages_count,200},{b_mods,[MODSList]}]}
    %%MODSList元素格式:[MOD_NAME|atom,MOD_CODE|list]。例：[[a,["a1","a2","a3"]],[b,["b1"]]]
    %%MOD_CODE格式：["code1","code2",..]
	Mods = gen_mod:get_module_opt(?MYNAME, service_api, b_mods, []),
	ets:new(push_business_mods, [set, public, named_table]),
	[spawn(Modname, reg, [CodesList])||[Modname,CodesList]<-Mods]
.

checkDealProccess(Host)->
	case whereis(deal_push_msg) of
	undefined->
		SendMessageCount = gen_mod:get_module_opt(?MYNAME, service_api, once_messages_count, 100),
		register(deal_push_msg,spawn(?MODULE, deal, [Host,SendMessageCount]));
	_->
		ok
	end
.

receivemsg(Host)->
	receive
		{stop}->
			exit("stop");
		{MsgType,From,Jids,MsgEle,OfflineAttr}->
			T=os:timestamp(),
			ets:insert(push_msg_list, {T,MsgType,From,Jids,MsgEle,OfflineAttr}),
			checkDealProccess(Host)
	end,
	receivemsg(Host)
.

deal(Host, SendMessageCount)->
	Data=ets:match(push_msg_list, '$1'),
	case Data of []->		
		timer:sleep(100);
	_->
		erlang:garbage_collect(self()),
		Send=fun(Tup)->
			Obj=hd(Tup),
			{T,MsgType,From,Jids,MsgEle,OfflineAttr}=Obj,
			ets:delete(push_msg_list,T),
			send(MsgType,From,Jids,MsgEle,OfflineAttr,SendMessageCount)
		end,
		lists:foreach(fun(A)-> Send(A) end,Data)
	end,
  	deal(Host, SendMessageCount)
.




%%--------------------------------------------------------------------
%% Function: %% handle_call(Request, From, State) -> {reply, Reply, State} |
%%                                      {reply, Reply, State, Timeout} |
%%                                      {noreply, State} |
%%                                      {noreply, State, Timeout} |
%%                                      {stop, Reason, Reply, State} |
%%                                      {stop, Reason, State}
%% Description: Handling call messages
%%--------------------------------------------------------------------

handle_call(stop, _From, State) ->
    {stop, normal, ok, State}.    

%%--------------------------------------------------------------------
%% Function: handle_cast(Msg, State) -> {noreply, State} |
%%                                      {noreply, State, Timeout} |
%%                                      {stop, Reason, State}
%% Description: Handling cast messages
%%--------------------------------------------------------------------
handle_cast(_Msg, State) ->
    {noreply, State}.
    
    
handle_info(_Info, State) ->
    {noreply, State}.
    
terminate(_Reason, _State) ->

    ok.

%%--------------------------------------------------------------------
%% Func: code_change(OldVsn, State, Extra) -> {ok, NewState}
%% Description: Convert process state when code is changed
%%--------------------------------------------------------------------
code_change(_OldVsn, State, _Extra) ->
    {ok, State}. 
    
%%获取离线文件。返回可访问的http地址
getWebOfflineFile(A)->
    FileID = request:getparameter(A,"fileid","utf-8"),
    Fn = request:getparameter(A,"fn","utf-8"),
    Dt =  binary_to_list(func_utils:date(func_utils:time(),4)),
    Dir = "../lib/Yaws-1.89/im/offlinefile/"++Dt++"/",
    filelib:ensure_dir(Dir),
    ALlib_files1 = ejabberdex_odbc_query:get_lib_file(FileID),
    EItemX = hd(ALlib_files1),
    Path=ejabberdex_c2c_odbc:get_lib_files_mongo2local(FileID, EItemX#lib_files.filepath),
    NewFile = Dir++"/"++Fn,
    file:rename(Path,NewFile),
    HttpPath = "http://"++func_utils:getServerAddress()++"/offlinefile/"++Dt++"/"++Fn,
    case img:image_type(NewFile) of
      {_,W,H}->
        "var result="++request:returndata([[HttpPath],W,H]);
      _->
        "var result="++request:return([[HttpPath]])
    end        
.
%%send runtime message
send(A)->
	To = request:getparameter(A,"to","utf-8"),
	if (To==[]) or(To==null) or(To=="null") ->
       request:returnerror("400");
   	true->
       	From1 = request:getparameter(A,"from","utf-8"),
       	From =case From1 of 
          "null"->[];
          null->[];
          F-> jlib:string_to_jid(F) 
       	end ,
		MsgBody = request:getparameter(A,"msg","utf-8"),
       	%%发送消息。返回成功发送的接收人帐号列表
		Jids = re:split(To,",",[{return,list}]),
        Result = case xml_stream:parse_element(MsgBody) of {error,Error}->
        		?ERROR_MSG("send message error(string_to_element):~p,~p~n", [MsgBody,Error]),
        		error;
        	MsgEle->
        		?ERROR_MSG("send message:~p~n", [MsgEle]),
				NowTime=calendar:now_to_universal_time(os:timestamp()),
        		OfflineAttr = [
        		    jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", From#jid.server, ""),"Offline Storage"),
					jlib:timestamp_to_xml(NowTime)], 
				receive_push_msg!{"message",From,Jids,MsgEle,OfflineAttr},
				ok      		
        		%%send("message",From,Jids,MsgEle,OfflineAttr)
        end,

        request:returndata([Result])
   	end
.
%%通过presence向指定的人发送通知。
%%1、当自己的签名和头像发生更改时通知客户端同步获取数据.caption=changeinfo
sendPresence(A)->
   To = request:getparameter(A,"to","utf-8"),
   if (To==[]) or(To==null) or(To=="null") ->
       request:returnerror("400");
   true->
   		Type = request:getparameter(A,"type","utf-8"),
       	From1 = request:getparameter(A,"from","utf-8"),
      	From =case From1 of 
          "null"->[];
          null->[];
          F-> jlib:string_to_jid(F) 
       	end ,
		Body = request:getparameter(A,"presence","utf-8"),
		%%?ERROR_MSG("~p sendPresence:~p~n",[self(),func_utils:date(),Body]),
       	%%发送消息。返回成功发送的接收人帐号列表
		Jids = re:split(To,",",[{return,list}]),
        BusID = request:getparameter(A,"busdata","utf-8"),
        MsgID = case BusID of 
                  []->
                    [From#jid.user,"-",func_utils:getNextID()];
                  "null"->
                    [From#jid.user,"-",func_utils:getNextID()];
                  null->
                    [From#jid.user,"-",func_utils:getNextID()];
                  _->
                    BusID
        end,
       	Msg =  {xmlelement, "presence", [{"id",MsgID},{"from",From}], 
                    [{xmlelement, "business", 
                    	[{"xmlns","http://im.private-en.com/namespace/business"}],
                        [{xmlcdata,Body}]
                    }]
       	},
		NowTime=calendar:now_to_universal_time(os:timestamp()),
       	OfflineAttr = [
        	jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", From#jid.server, ""),"Offline Storage"),
			jlib:timestamp_to_xml(NowTime)],
		receive_push_msg!{"presence",From,Jids,Msg,OfflineAttr},
		%%Result=send("presence",From,Jids,Msg,OfflineAttr),
		MRec=ets:select(push_business_mods, [{{Type, '_'},[],['$_']}]),
		case MRec of []-> ok;
		_->
			Mod = element(2,hd(MRec)),
			spawn(fun()->Mod:update_unread_flag([From1,Jids]) end)
		end,
		request:returndata([])
   end
.

%%消息发送接口
%%参数内容必需经过应用KEY加密
%%参数内容包括：from :发送人JID，To:接收人JID列表,title:消息标题，msg：消息体
sendMsg(A)->
   case whereis(receive_push_msg) of undefined->
   	  	request:returnerror("500");
   	_->
   		skip
   end,
   To = request:getparameter(A,"to","utf-8"),
   if (To==[]) or(To==null) or(To=="null") ->
       request:returnerror("400");
   true->
   		Type = request:getparameter(A,"type","utf-8"),
       	From1 = request:getparameter(A,"from","utf-8"),
       	From =case From1 of 
          "null"->[];
          null->[];
          F-> jlib:string_to_jid(F) 
       	end ,
			 Body = request:getparameter(A,"msg","utf-8"),%%case Appid of "00441" ->request:getparameter(A,"sendMsg","utf-8"); _-> request:des_dec( request:getparameter(A,"sendMsg","utf-8")) end,
       		%%发送消息。返回成功发送的接收人帐号列表
			if (Body=="null") -> request:returnerror("501");
		    true->
				Jids = re:split(To,",",[{return,list}]),    
                BusID = request:getparameter(A,"busdata","utf-8"),
                MsgID = case BusID of 
                  []->
                    [From#jid.user,"-",func_utils:getNextID()];
                  "null"->
                    [From#jid.user,"-",func_utils:getNextID()];
                  null->
                    [From#jid.user,"-",func_utils:getNextID()];
                  _->
                    BusID
                end,
                Msg =  {xmlelement, "message", [{"id",MsgID},{"from",From}], 
                        [{xmlelement, "business", 
                        	[{"xmlns","http://im.private-en.com/namespace/business"}],
                            [{xmlcdata,Body}]
                        }]
                },                
				NowTime=calendar:now_to_universal_time(os:timestamp()),
        		OfflineAttr = [
        		    jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", From#jid.server, ""),"Offline Storage"),
					jlib:timestamp_to_xml(NowTime)],
				receive_push_msg!{"message",From,Jids,Msg,OfflineAttr},
				%%send("message",From,Jids,Msg,OfflineAttr),
				MRec=ets:select(push_business_mods, [{{Type, '_'},[],['$_']}]),
				case MRec of []-> ok;
				_->
					Mod = element(2,hd(MRec)),
					spawn(fun()->Mod:update_unread_flag([From1,Jids]) end)
				end,
				request:returndata([])                
			end
   end
.

%%微信消息发送接口
%%参数内容包括：from :发送人JID，To:接收人JID列表,title:消息标题，msg：消息体
sendMicroMsg(A)->
   To = request:getparameter(A,"to","utf-8"),
   if (To==[]) or(To==null) or(To=="null") ->
       request:returnerror("400");
   true->
   		Type = request:getparameter(A,"type","utf-8"),
       	From1 = request:getparameter(A,"from","utf-8"),
       	From =case From1 of 
          "null"->[];
          null->[];
          F-> jlib:string_to_jid(F) 
       	end ,
       	Body = case request:getparameter(A,"msg","utf-8") of "null"->[];T1-> T1 end,
       	%%发送消息。返回成功发送的接收人帐号列表
		if (Body=="null") -> request:returnerror("501");
		  true->
				Jids = re:split(To,",",[{return,list}]),
                
                BusID = request:getparameter(A,"busdata","utf-8"),
                MsgID = case BusID of 
                  []->
                    [From#jid.user,"-",func_utils:getNextID()];
                  "null"->
                    [From#jid.user,"-",func_utils:getNextID()];
                  null->
                    [From#jid.user,"-",func_utils:getNextID()];
                  _->
                    BusID
                end,
                Msg =  {xmlelement, "message", [{"id",MsgID},{"from",From}], 
                            [{xmlelement, "serviceaccount", 
                        		[{"xmlns","http://im.private-en.com/namespace/serviceaccount"}],
                            	[{xmlcdata,Body}]
                            }]
                },
                NowTime=calendar:now_to_universal_time(os:timestamp()),
        		OfflineAttr = [
        		    jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", From#jid.server, ""),"Offline Storage"),
					jlib:timestamp_to_xml(NowTime)],
				receive_push_msg!{"message",From,Jids,Msg,OfflineAttr},
				Rec=ets:select(push_business_mods, [{{Type, '_'},[],['$_']}]),
				case Rec of []-> ok;
				_->
					Mod = element(2,hd(Rec)),
					spawn(fun()->Mod:update_unread_flag([From1,Jids]) end)
				end,
				%%Result=send("message",From,Jids,Msg,OfflineAttr),
				request:returndata([])
			end
   end
.


%%提醒发送接口
%%参数内容必需经过应用KEY加密
%%参数内容包括：from :发送人JID，To:接收人JID列表,title:消息标题，msg：消息体
sendRemind(A)->   
       message_action_odbc:broadcast(A)
.
removeRemind(A)->   
       message_action_odbc:remove(A)
.
%%广播发送接口
%%参数内容必需经过应用KEY加密
%%参数内容包括：from :发送人JID，To:接收人JID列表,title:消息标题，msg：消息体
sendBroadcast(A)->   
       message_action_odbc:broadcast(A)
.
removeBroadcast(A)->   
       message_action_odbc:remove(A)
.
%%根据应用帐号获取对应的fafa帐号
getFaFaAccount(_,_,AppAccount)->
               case AppAccount of []->[];
               _->	               
		               AppAccount
               end
.

splitTos(Jids,Cnt,Result)->
	case Jids of 
		[]->Result;
		_->
		    Len = length(Result),
			Re =case catch lists:sublist(Jids,Len*Cnt+1,Cnt) of
				{'EXIT', _}->[];
				Lre->Lre
				end,
			case Re of 
				[]-> Result;
				_-> splitTos(Jids,Cnt,Result++[Re])
			end
	end
.

lookup(Pids) ->
  case lists:any(fun(Pid)-> erlang:is_process_alive(Pid) end,Pids) of
  true-> timer:sleep(5), lookup(Pids);
  _-> ok
  end
.

send(Type,From,Tos,Msg,OfflineAttr,SendMessageCount)->
	Len = length(Tos),
    case Len<(SendMessageCount+1) of 
    true->    	
    	Sqls=[send(Type,From,To,Msg,OfflineAttr,SendMessageCount,[])||To<-Tos],
    	Sqls2=lists:filter(fun(Ss)-> Ss=/=[] end,Sqls),
    	case Sqls2 of 
    		[]->ok;
    		_->
    			%%insert into spool(username, xml) values 
    			[F|H]=Sqls2,
    			[F1|H1]=F,
    			Tmp=lists:append([[H1],H]),
    			Joinsql = [["insert into spool(username, xml) values"++ lists:append(Tmp)]],
    			%%?ERROR_MSG("~p begin:~p~n",[self(),func_utils:date()]),
		    	%%ejabberd_odbc:sql_transaction([], ["SET AUTOCOMMIT = 0;"]++Sqls2++["COMMIT;set autocommit = 1;"]),
		    	ejabberd_odbc:sql_transaction([], Joinsql)
    			%%?ERROR_MSG("~p end:~p~n",[self(),func_utils:date()])
    	end;
    _->
    	Lst = splitTos(Tos,SendMessageCount,[]),
    	Spids=[spawn(fun()-> send(Type,From,C,Msg,OfflineAttr,SendMessageCount) end)||C<-Lst],    	
    	lookup(Spids)
    	%%?ERROR_MSG("send Spids:~p ~p~n", [length(Spids),Spids])
	end,
    ok
.


send(_Type,_From,Jid,_Msg,_OfflineAttr,_SendMessageCount,_Result) when Jid==[]->
    []
;
send(Type,From,Jid,TMsg,OfflineAttr,_SendMessageCount,_Result)->    
    To = jlib:string_to_jid(Jid), 
    User = To#jid.user,
    Serv1 = To#jid.server,
    Operator =case From of []->jlib:string_to_jid("admin@"+Serv1);_-> From end,
    try
        case To#jid.user of []->
            [];
        _->
            IsOnline=case binary_to_list(erlmc:get(User++"@"++Serv1)) of 
            	[]->            	    
            	    Server = Serv1,
            		case mnesia:dirty_index_read(session,{User,Server}, us) of
		                [] ->
		                        []; % Race condition
		                Ss ->
		                    	Session = lists:max(Ss),
		                        Res = element(3, element(3,Session)),
		                        Priority = 0,
		                        {{Y,M,D},{Hh,Mi,Si}} = calendar:now_to_local_time(element(1,element(2, Session))),
		                        Login_date =lists:append([integer_to_list(Y),"-",integer_to_list(M),"-",integer_to_list(D)," ",integer_to_list(Hh),":",integer_to_list(Mi),":",integer_to_list(Si)]),
		                        ejabberd_sm:set_global_session(User, Server, Res, Priority),
		                        ok
		            end;
            	_->
            	  ok
            end,
        	case IsOnline of 
        		%%[]->mod_offline_odbc:store_packet(Operator, To, TMsg);
        		[]->
        		    case Type of "message"->
        		    	{xmlelement, Name, Attrs, Els}=TMsg,
        		    	Attrs2 = jlib:replace_from_to_attrs(jlib:jid_to_string(Operator),jlib:jid_to_string(To),Attrs),
        		    	Packet = {xmlelement, Name, Attrs2,Els++OfflineAttr},
	        			SpoolSql=w_spool(User++"@"++Serv1,Packet),%%
	        			SpoolSql;
	        			_->[]
        			end;
        		_-> 
        		    %%?ERROR_MSG("ejabberd_sm:route Online message :~p~n ", [To]),
        			spawn(fun()->try ejabberd_sm:route(Operator, To, TMsg) catch Ec:Ex ->?ERROR_MSG("send business message error:~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]) end end),
        			[]
			end
        end
    catch 
    	Ec:Ex ->?ERROR_MSG("send business message error:~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
    	[]
    end   
.

w_spool(User,Xml)->
		Queries = [",('",User,"', '",ejabberd_odbc:escape(xml:element_to_binary(Xml)),"')"],
		lists:append(Queries)
		%%ejabberd_odbc:sql_transaction([], [lists:append(Queries)])
.