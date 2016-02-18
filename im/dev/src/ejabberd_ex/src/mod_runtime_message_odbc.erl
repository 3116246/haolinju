
-module(mod_runtime_message_odbc).
-author('liling@fafatime.com').

-behaviour(gen_server).
-behaviour(gen_mod).

-export([start_link/2, start/2, stop/1,
         %send_packet/3,
         rev_packet/3,
         rev_packet/4,
         user_available/1,
         process_iq/3]).

%% gen_server callbacks
-export([init/1, handle_call/3, handle_cast/2, handle_info/2,
         terminate/2, code_change/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").

-record(state, {host,delete_duration}).

-define(PROCNAME, ejabberd_mod_runtime_message_odbc).
-define(NS_ARCHIVE,
        "http://www.xmpp.org/extensions/xep-0136.html#ns").
-define(NS_ARCHIVE_AUTO,
        "http://www.xmpp.org/extensions/xep-0136.html#ns-auto").
-define(NS_ARCHIVE_MANAGE,
        "http://www.xmpp.org/extensions/xep-0136.html#ns-manage").
-define(NS_ARCHIVE_PREF,
        "http://www.xmpp.org/extensions/xep-0136.html#ns-pref").
-define(NS_ARCHIVE_MANUAL,
        "http://www.xmpp.org/extensions/xep-0136.html#ns-manual").
-define(NS_MESSAGE,"http://im.private-en.com/namespace/message").
-define(INFINITY, calendar:datetime_to_gregorian_seconds({{2038,1,19},{0,0,0}})).

%% Should be OK for most of modern DBs, I hope ...
-define(MAX_QUERY_LENGTH, 32768).

-define(MYDEBUG(Format, Args),
        io:format("D(~p:~p:~p) : " ++ Format ++ "~n",
                  [calendar:local_time(), ?MODULE, ?LINE] ++ Args)).

-record(runtime_message,
        {id,
         jid,
         xml,
         created_at}).

%%====================================================================
%% API
%%====================================================================
%%--------------------------------------------------------------------
%% Function: start_link() -> {ok,Pid} | ignore | {error,Error}
%% Description: Starts the server
%%--------------------------------------------------------------------
start_link(Host, Opts) ->
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    gen_server:start_link({local, Proc}, ?MODULE, [Host, Opts], []).

start(Host, Opts) ->
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    ChildSpec =
        {Proc,
         {?MODULE, start_link, [Host, Opts]},
         permanent,
         1000,
         worker,
         [?MODULE]},
    supervisor:start_child(ejabberd_sup, ChildSpec).
%% ejabberd-1.x compatibility code
%% NOTE: keepalive is not supported in ejabberd 1.x, so
%% you'll either need to turn off connections timeout in DB
%% configuration or invent smth else ...
%%    ChildSpecODBC =
%%         {gen_mod:get_module_proc(Host, ejabberd_odbc_sup),
%%         {ejabberd_odbc_sup, start_link, [Host]},
%%         permanent,
%%         infinity,
%%         supervisor,
%%         [ejabberd_odbc_sup]},
%%    supervisor:start_child(ejabberd_sup, ChildSpecODBC).
%% EOF ejabberd-1.x compatibility code

stop(Host) ->
    Proc = gen_mod:get_module_proc(Host, ?PROCNAME),
    gen_server:call(Proc, stop),
    supervisor:delete_child(ejabberd_sup, Proc).
%% ejabberd-1.x compatibility code
%%    ProcODBC = gen_mod:get_module_proc(Host, ejabberd_odbc_sup),
%%    gen_server:call(ProcODBC, stop),
%%    supervisor:delete_child(ejabberd_sup, ProcODBC).
%% EOF ejabberd-1.x compatibility code

%%====================================================================
%% gen_server callbacks
%%====================================================================

%%--------------------------------------------------------------------
%% Function: init(Args) -> {ok, State} |
%%                         {ok, State, Timeout} |
%%                         ignore               |
%%                         {stop, Reason}
%% Description: Initiates the server
%%--------------------------------------------------------------------
init([Host, Opts]) ->
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),
    SessionDuration = gen_mod:get_opt(delete_duration, Opts, 120),
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_ARCHIVE,
                                  ?MODULE, process_iq, IQDisc),
    %%ejabberd_hooks:add(user_send_packet, Host, ?MODULE, send_packet, 90),
    ejabberd_hooks:add(user_receive_packet, Host, ?MODULE, rev_packet, 90),
    ejabberd_hooks:add(user_available_hook, Host, ?MODULE, user_available, 50),
    
    {ok, #state{host=Host,delete_duration = SessionDuration}}.

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
handle_cast({addlog, Id , From , JID , Elem}, State) ->
    do_log( Id , From , JID , Elem, State#state.delete_duration),
    {noreply, State};
handle_cast(_Msg, State) ->
    {noreply, State}.

%%--------------------------------------------------------------------
%% Function: handle_info(Info, State) -> {noreply, State} |
%%                                       {noreply, State, Timeout} |
%%                                       {stop, Reason, State}
%% Description: Handling all non call/cast messages
%%--------------------------------------------------------------------

handle_info(_Info, State) ->
    {noreply, State}.

%%--------------------------------------------------------------------
%% Function: terminate(Reason, State) -> void()
%% Description: This function is called by a gen_server when it is about to
%% terminate. It should be the opposite of Module:init/1 and do any necessary
%% cleaning up. When it returns, the gen_server terminates with Reason.
%% The return value is ignored.
%%--------------------------------------------------------------------
terminate(_Reason, State) ->
    Host = State#state.host,    
    %%ejabberd_hooks:delete(user_send_packet, Host, ?MODULE, send_packet, 90),
    ejabberd_hooks:delete(user_receive_packet, Host, ?MODULE, rev_packet, 90),
    ejabberd_hooks:delete(user_available_hook, Host, ?MODULE, user_available, 50),
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_ARCHIVE),
    ok.

%%--------------------------------------------------------------------
%% Func: code_change(OldVsn, State, Extra) -> {ok, NewState}
%% Description: Convert process state when code is changed
%%--------------------------------------------------------------------
code_change(_OldVsn, State, _Extra) ->
    {ok, State}.

%%--------------------------------------------------------------------
%%% Internal functions
%%--------------------------------------------------------------------

%% Workaround the fact that if the client send <iq type='get'>
%% it end up like <iq type='get' from='u@h' to = 'u@h'>

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

process_iq_get(From, To, #iq{sub_el = SubEl} = IQ) ->    
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of 
          "queryunreadmessage" -> queryunreadmessage(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
        Ec:Ex ->  
                ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

process_iq_set(_From, _To, #iq{sub_el = SubEl} = IQ)->
    IQ#iq{type = result, sub_el = SubEl}
.

%%%----------------------------------------------------------------------
%%% 服务器监测每个客户端上线
user_available(Jid) ->
  %%?ERROR_MSG("INFO ~p~n  Jid:~p", ["user_available", Jid]),
  try
    send_runtime_message(Jid,[])
  catch 
    Ec:Ex ->  
            ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%  3 Automated archiving
%%

queryunreadmessage(From, _To, #iq{sub_el = SubEl} = IQ)->
    Id = xml:get_tag_attr("lastid", SubEl),
    case send_runtime_message(From,Id) of
        ok->
            IQ#iq{type = result, sub_el = SubEl};
        _->
            IQ#iq{type = error, sub_el = SubEl}
    end
.

send_runtime_message(From,LastID)->
    UserJid = [From#jid.luser,"@", From#jid.lserver],
    Sql =case LastID of 
        []-> ["select id,jid,`from`,xml from im_runtime_message where jid='",UserJid,"'"];
        _->  ["select id,jid,`from`,xml from im_runtime_message a,(select create_at from im_runtime_message where id='",LastID,"') b where jid='",UserJid,"' where a.create_at>b.create_at"]
    end,
    %%?ERROR_MSG("INFO ~p~n  Sql:~p", ["get resend data:", Sql]),
    %ejabberd_odbc:sql_query("",["delete from im_runtime_message where jid=",UserJid," and created_at<(now()-",DeleteDuration,")"]),
                            
    case ejabberd_odbc:sql_query("",Sql) of
        {selected, ["id","jid", "from","xml"], Rs} ->
            lists:flatmap(
            fun({Id, _, Sender , XML}) ->
                case xml_stream:parse_element(XML) of
                {error, _Reason} ->
                    ejabberd_odbc:sql_query("",["delete from im_runtime_message where id='",Id,"'"]),
                    [];
                El ->
                    To = jlib:string_to_jid(
                       xml:get_tag_attr_s("to", El)),
                    SenderJid = jlib:string_to_jid(Sender),
                    if
                    (To /= error) and
                    (SenderJid /= error) ->
                        %[{route, From, To, El}];
                        Msgid = xml:get_tag_attr_s("id",El),
                        NewEle = xml:replace_tag_attr("id","sas-"++Msgid,El),
                        ejabberd_sm:route(SenderJid, To, NewEle),       
                        ejabberd_odbc:sql_query("",["delete from im_runtime_message where id='",Id,"'"]),
                        [];
                    true ->
                        ejabberd_odbc:sql_query("",["delete from im_runtime_message where id='",Id,"'"]),
                        []
                    end
                end
            end, Rs),
            ok;
        {error, Err} ->
            ?ERROR_MSG("error ~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()]),
            error;
        _ ->
            ?ERROR_MSG("INFO ~p~n  Jid:~p", ["no need send data!", From]),
            ok
    end
.


%send_packet(From, To, Packet) ->    
%    add_log(to, From, To, Packet)
%.

rev_packet(From, To, Packet)->
   add_log(to, From, To, Packet)
.

rev_packet(_Jid,From, To, Packet)->
   rev_packet(From, To, Packet)  
.



add_log(Direction, From, JID, Packet) ->    
    case parse_message(Packet) of
    {Id, ElePacket}  ->
            Proc = gen_mod:get_module_proc(JID#jid.lserver, ?PROCNAME),
            gen_server:cast(
                     Proc, {addlog, Id , From ,JID, ElePacket});
	_ ->
            ok
    end.

%% Parse the message and return {Thread, Subject, Body} strings if successful
parse_message({xmlelement, "message", _, _} = Packet) ->
    case xml:get_tag_attr_s("id", Packet) of
        []->"";
        Id ->
            case xml:get_subtag(Packet, "delay") of %% Offline Message
            false-> 
                ?DEBUG("INFO ~p~n  Packet:~p", ["add_log", Packet]),
                {Id,Packet};
            _->
                ""
            end
    end
;
%%parse_message({xmlelement, "presence", _, SubEl} = Packet) ->    
%%    case xml:get_tag_attr_s("id", Packet) of
%%        []->"";
%%        Id ->
%%            case SubEl of
%%            [{xmlelement,"priority",_,_}|_]->
%%                "";
%%            _->
%%                ?ERROR_MSG("INFO ~p~n  Packet:~p", ["add_log", Packet]),
%%                {Id,Packet}
%%            end
%%    end;    
parse_message(_) ->
   ""
.

%% 记录发送的消息，并删除2分钟之前的消息
do_log( Id ,From, JID , Elem, DeleteDuration) ->
?DEBUG("do_log : ~p ~p~n~p~n", [From,JID,Elem]),
%%判断To是否离线，离线状态时不存储消息 ，由离线消息 模块负责
case ejabberd_sm:get_user_resources(JID#jid.luser, JID#jid.lserver) of
[] ->
        ok;
_ ->
    Id = xml:get_tag_attr_s("id", Elem),
    ?DEBUG("do_log Id: ~p~n", [Id]),
    case Id of
    []-> ok;
    [$s,$a,$s,$-|_]-> ok; %%服务器自动发送的消息 
    _->
                F = fun() ->
                            TimeStamp = os:timestamp(),
                            UserJid = [JID#jid.luser,"@", JID#jid.lserver],
                            FromJid = [From#jid.luser,"@", From#jid.lserver],
                            %M = #runtime_message{id = Id,
                            %                     jid = LowJID,
                            %                     xml = Elem,
                            %                     created_at =TimeStamp},
                            %%store_message(LServer, M),
                            DelSQL = ["delete from im_runtime_message where jid='",UserJid,"' and create_at<(now()-",integer_to_list( DeleteDuration),")"],
                               
                            ejabberd_odbc:sql_query("",DelSQL),
                            XML = ejabberd_odbc:escape(xml:element_to_binary(Elem)),
                            Sql = ["insert into im_runtime_message("
                                       "id, jid,`from`, xml, create_at)values( '",                           
                                       Id, "','",
                                       UserJid, "', '",
                                       FromJid, "', '",
                                       XML,  "',now())"],
                            
                            ejabberd_odbc:sql_query("",Sql)
                end,
                case run_sql_transaction("", F) of
                    {error, Err} ->
                        ?ERROR_MSG("error when performing automated archiving: ~p", [Err]);            
                    R -> %?MYDEBUG("successfull automated archiving: ~p", [R]),
            	       R
                end       
    end
end.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%
%% Utility functions to make database interaction easier.
%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%% Noone seems to follow standards these days :-(
%% We have to perform DB-specific escaping,as f.e. SQLite does not understand
%% '\' as escaping character (which is exactly in accordance with the standard,
%% by the way), while most other DBs do.

%% Generic, DB-independent escaping for integers and simple strings.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%
%% End of copy-paste-modified
%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%

get_timestamp() ->
    calendar:datetime_to_gregorian_seconds(calendar:universal_time()).

%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%
%% Wrapper functions to perform queries and transactions.
%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%

run_sql_query(Query) ->
    %%?MYDEBUG("running query: ~p", [lists:flatten(Query)]),
    case catch ejabberd_odbc:sql_query("",Query) of
        {'EXIT', Err} ->
            ?ERROR_MSG("unhandled exception during query: ~p", [Err]),
            exit(Err);
        {error, Err} ->
            ?ERROR_MSG("error during query: ~p", [Err]),
            throw({error, Err});
        aborted ->
            ?ERROR_MSG("query aborted", []),
            throw(aborted);
        R -> %?MYDEBUG("query result: ~p", [R]),
	    R
    end.

run_sql_transaction(LServer, F) ->
    DBHost = gen_mod:get_module_opt(LServer, ?MODULE, db_host, LServer),
    case ejabberd_odbc:sql_transaction(DBHost, F) of
        {atomic, R} ->
	    %%?MYDEBUG("succeeded transaction: ~p", [R]),
	    R;
        {error, Err} -> {error, Err};
        E ->
            ?ERROR_MSG("failed transaction: ~p, stack: ~p", [E, process_info(self(),backtrace)]),
            {error, ?ERR_INTERNAL_SERVER_ERROR}
    end.

