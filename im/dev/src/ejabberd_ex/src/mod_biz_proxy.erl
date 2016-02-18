
-module(mod_biz_proxy).
-author('liling@fafatime.com').

-behaviour(gen_server).
-behaviour(gen_mod).

-export([start_link/2, start/2, stop/1, conn/0,      
         process_iq/3]).

%% gen_server callbacks
-export([init/1, handle_call/3, handle_cast/2, handle_info/2,
         terminate/2, code_change/3]).
-compile(export_all).
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../../ejabberd-2.1.11/lib/xmerl/include/xmerl.hrl").

-define(PROCNAME, ejabberd_mod_biz_proxy).
-define(NS_MESSAGE,"http://im.fafacn.com/namespace/message").
-define(INFINITY, calendar:datetime_to_gregorian_seconds({{2038,1,19},{0,0,0}})).
-record(state, {host}).
%% Should be OK for most of modern DBs, I hope ...
-define(MAX_QUERY_LENGTH, 32768).

-define(MYDEBUG(Format, Args),
        io:format("D(~p:~p:~p) : " ++ Format ++ "~n",
                  [calendar:local_time(), ?MODULE, ?LINE] ++ Args)).
-define(SERVERURL, gen_mod:get_module_opt(?MYNAME, mod_biz_proxy, serverurl, [])).

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
    %%gen_server:call(Proc, stop),
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
    mod_msg_seq_server:start(),
    %%Dict = dict:new(),
    %%Serverurl = dict:store(serverurl,gen_mod:get_opt(serverurl, Opts, []),Dict),

    %%AutologinList = gen_mod:get_opt(autologin, Opts, []),

    %%Info = [{ip, [127,0,0,1]}, {conn, unknown},{auth_module, ["md5"]}],    

    %%[ejabberd_sm:open_session({now(), self()},User,Host,"wefafaproxy",Info)||User<-AutologinList],
    %%IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),
    %%gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_MESSAGE,?MODULE, process_iq, IQDisc),
    
    %%ejabberd_hooks:add(user_send_packet, Host, ?MODULE, send_packet, 90),
    %%ejabberd_hooks:add(user_receive_packet, Host, ?MODULE, rev_packet, 90),
    %%ejabberd_hooks:add(user_available_hook, Host, ?MODULE, user_available, 50),    
    conn(),
    %%spawn_link(?MODULE,startlogin,[Socket]),
    %%gen_tcp:close(Socket),
    {ok,[]}.

conn()->
    Server = "182.92.11.9",
    Port = 5222,
    Jid = "10001-100001@fafacn.com",
    Pass = "Fa2010",
    {ok, Socket} = gen_tcp:connect(Server, Port, [binary, {packet, raw}, {active, true}, {reuseaddr, true}]),
    ?MYDEBUG("connect to ~p,~p",[Server,Socket]),
    startlogin(Socket)
.

recv(Socket)->
    receive  
        {tcp, Socket, Bin} ->              
            parseXml(Socket,binary_to_list(Bin)),
            recv(Socket);  
        {tcp_closed, Socket} ->  
            io:format("remote server closed!~n");
        _->
            io:format("error................!~n")
    after 3000 ->
            timeout                       
    end,
    io:format("no data...............!~n"),
    gen_tcp:close(Socket)
.

parseXml(Socket,Bin)->
    io:format("recv ~p~n", [Bin]),
    case Bin of 
        [$<,$?,$x,$m,$l|_]->
            skip;
        _->
            case xmerl_scan:string(Bin) of
                {Doc,_}->                
                    matchNode(Socket,Doc);
                _->
                    skip
            end
    end
.


matchNode(Socket,{xmlElement,'stream:features',_,_,_,_,_,Attributes,Content,_,_,_})->
    io:format("match  stream:features................!~n"),
    %%判断starttls
    case  xmerl_xpath:string("//starttls",hd(Content)) of
        []-> getchallenge(Socket);
        _->
            starttls(Socket)
    end
;
matchNode(Socket,{xmlElement,'challenge',_,_,_,_,_,Attributes,Content,_,_,_})->
    io:format("match  challenge................!~n"),
    response(Socket,Content)
;
matchNode(Socket,{xmlElement,success,_,_,_,_,_,Attributes,Content,_,_,_})->
    resource_bind(Socket)
;
matchNode(Socket,{xmlElement,proceed,_,_,_,_,_,Attributes,Content,_,_,_})->
    ssl:start(),
    {ok, SSL}=ssl:connect(Socket,[]),
    sendMsg(ssl, SSL, "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='fafacn.com'  version='1.0'>")
;
matchNode(Socket,_Msg)->
  skip
.


startlogin(Socket)->
    sendMsg(gen_tcp,Socket, "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='fafacn.com'  version='1.0'>"),
    recv(Socket)
.

starttls(Socket)->
    sendMsg(gen_tcp,Socket,"<starttls xmlns=\"urn:ietf:params:xml:ns:xmpp-tls\"/>"),
    recv(Socket).

getchallenge(Socket)->
    Auth = auth_sasl("10001-100001@fafacn.com","Fa2010","PLAIN"),
    sendMsg(ssl,Socket, Auth)
.

resource_bind(Socket)->
    Resource = "webizproxy",
    Id = mod_msg_seq_server:get_id(list),
    Msg="<iq type='set' id='bind_"++Id++"'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>"++Resource++"</resource></bind></iq>",
    sendMsg(ssl,Socket, Msg)
.

response(Socket,[{xmlText,_,1,[],RepStr,text}])->
    EncodeStr = base64:decode_to_string(RepStr),
    DecondeStr = "",
    sendMsg(gen_tcp,Socket, "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>"++DecondeStr++"</response>")
.
sendMsg(Moduel,Socket,Msg)->
    Moduel:send(Socket, Msg),
    io:format("send message: ~p~n", [Msg])
.
auth_sasl(Username, Passwd, Mechanism) ->
    S = <<0>>,
    N = list_to_binary(Username),
    P = list_to_binary(Passwd),
    list_to_binary(["<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='",Mechanism,"' >",
                    base64:encode(<<S/binary,N/binary,S/binary,P/binary>>) ,"</auth>"]).
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
    %%ejabberd_hooks:delete(user_receive_packet, Host, ?MODULE, rev_packet, 90),
    %%ejabberd_hooks:delete(user_available_hook, Host, ?MODULE, user_available, 50),
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_MESSAGE),
    inets:stop(),
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
        #iq{sub_el = SubEl} = IQ,
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]}
end.

process_local_iq(From, To, #iq{type = Type} = IQ) ->
    case Type of
    get ->
        process_iq_get(From, To, IQ);
    _->
        #iq{sub_el = SubEl} = IQ,
        IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]} 
end.

process_iq_get(From, To, #iq{sub_el = SubEl} = IQ) ->    
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of 
          "dataaccess" -> dataaccess(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
        Ec:Ex ->  
                ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

dataaccess(From, _To, #iq{sub_el = SubEl} = IQ)->
    DsId = xml:get_tag_attr("dsid", SubEl),
    Params = xml:get_tag_attr("params", SubEl),
    Url =  ?SERVERURL ++ "/api/http/dataaccess",
    User = From#jid.luser++"@"++From#jid.lserver,
    ParamsList = case Params of 
        []-> "params=&dsid="++DsId++"&openid="++User;
        _->
            "params="++request:convertUTF8(Params)++"&dsid="++DsId++"&openid="++User
    end,
    inets:start(),
    case httpc:request(post,{Url,[{"connection", "close"},{"Referer","localhost"}],"application/x-www-form-urlencoded",ParamsList},[{timeout,3000},{connect_timeout,3000}],[],[]) of
        {ok, {{_Version, 200, _ReasonPhrase}, _Headers, Body}}-> 
            inets:stop(),
            IQ#iq{type = result, sub_el = [Body,[]]}
            ;
        {error,Reason}->
            inets:stop(),
            IQ#iq{type = error, sub_el = [Reason,[]]}
    end    
.



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

