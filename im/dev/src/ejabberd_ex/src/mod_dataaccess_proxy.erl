
-module(mod_dataaccess_proxy).
-author('liling@fafatime.com').

-behaviour(gen_server).
-behaviour(gen_mod).

-export([start_link/2, start/2, stop/1,         
         process_iq/3]).

%% gen_server callbacks
-export([init/1, handle_call/3, handle_cast/2, handle_info/2,
         terminate/2, code_change/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").

-define(PROCNAME, ejabberd_mod_dataaccess_proxy).
-define(NS_MESSAGE,"http://im.fafacn.com/namespace/message").
-define(INFINITY, calendar:datetime_to_gregorian_seconds({{2038,1,19},{0,0,0}})).
-record(state, {host}).
%% Should be OK for most of modern DBs, I hope ...
-define(MAX_QUERY_LENGTH, 32768).

-define(MYDEBUG(Format, Args),
        io:format("D(~p:~p:~p) : " ++ Format ++ "~n",
                  [calendar:local_time(), ?MODULE, ?LINE] ++ Args)).
-define(SERVERURL, gen_mod:get_module_opt(?MYNAME, mod_dataaccess_proxy, serverurl, [])).

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
   
    %%Dict = dict:new(),
    %%Serverurl = dict:store(serverurl,gen_mod:get_opt(serverurl, Opts, []),Dict),

    %%AutologinList = gen_mod:get_opt(autologin, Opts, []),

    %%Info = [{ip, [127,0,0,1]}, {conn, unknown},{auth_module, ["md5"]}],    

    %%[ejabberd_sm:open_session({now(), self()},User,Host,"wefafaproxy",Info)||User<-AutologinList],
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_MESSAGE,
                                  ?MODULE, process_iq, IQDisc),
    
    %%ejabberd_hooks:add(user_send_packet, Host, ?MODULE, send_packet, 90),
    %%ejabberd_hooks:add(user_receive_packet, Host, ?MODULE, rev_packet, 90),
    %%ejabberd_hooks:add(user_available_hook, Host, ?MODULE, user_available, 50),    
    {ok,#state{host=Host}}.

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

