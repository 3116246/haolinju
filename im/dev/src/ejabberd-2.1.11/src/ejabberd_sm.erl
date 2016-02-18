%%%----------------------------------------------------------------------
%%% File    : ejabberd_sm.erl
%%% Author  : Alexey Shchepin <alexey@process-one.net>
%%% Purpose : Session manager
%%% Created : 24 Nov 2002 by Alexey Shchepin <alexey@process-one.net>
%%%
%%%
%%% ejabberd, Copyright (C) 2002-2012   ProcessOne
%%%
%%% This program is free software; you can redistribute it and/or
%%% modify it under the terms of the GNU General Public License as
%%% published by the Free Software Foundation; either version 2 of the
%%% License, or (at your option) any later version.
%%%
%%% This program is distributed in the hope that it will be useful,
%%% but WITHOUT ANY WARRANTY; without even the implied warranty of
%%% MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
%%% General Public License for more details.
%%%
%%% You should have received a copy of the GNU General Public License
%%% along with this program; if not, write to the Free Software
%%% Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
%%% 02111-1307 USA
%%%
%%%----------------------------------------------------------------------

-module(ejabberd_sm).
-author('alexey@process-one.net').

-behaviour(gen_server).

%% API
-export([start_link/0,
   route/3,
   open_session/5, close_session/4,
   check_in_subscription/6,
   bounce_offline_message/3,
   disconnect_removed_user/2,
   get_user_resources/2,
   set_presence/7,
   unset_presence/6,
   close_session_unset_presence/5,
   dirty_get_sessions_list/0,
   dirty_get_my_sessions_list/0,
   get_vh_session_list/1,
   get_vh_session_number/1,
   register_iq_handler/4,
   register_iq_handler/5,
   unregister_iq_handler/2,
   force_update_presence/1,
   connected_users/0,
   connected_users_number/0,
   user_resources/2,
   get_session_pid/3,
   get_user_info/3,
   get_user_ip/3,
   is_existing_resource/3,
   get_resource_sessions/3,
   check_existing_resources/3,
   get_resource_global_sessions/2,
   get_resource_global_session_maxpriority/3,
   get_resource_global_sessions/3,
   set_global_session/4
  ]).

%% gen_server callbacks
-export([init/1, handle_call/3, handle_cast/2, handle_info/2,
   terminate/2, code_change/3]).

-include("../include/ejabberd.hrl").
-include("../include/jlib.hrl").
-include("../include/ejabberd_commands.hrl").
-include("../include/mod_privacy.hrl").

-record(session, {sid, usr, us, priority, info}).
-record(session_counter, {vhost, count}).
-record(state, {}).

%% default value for the maximum number of user connections
-define(MAX_USER_SESSIONS, infinity).

%%====================================================================
%% API
%%====================================================================
%%--------------------------------------------------------------------
%% Function: start_link() -> {ok,Pid} | ignore | {error,Error}
%% Description: Starts the server
%%--------------------------------------------------------------------
start_link() ->
    gen_server:start_link({local, ?MODULE}, ?MODULE, [], []).

route(From, To, Packet) ->
    case catch do_route(From, To, Packet) of
  {'EXIT', Reason} ->
      ?ERROR_MSG("~p~nwhen processing: ~p",
           [Reason, {From, To, Packet}]);
  _ ->
      ok
    end.

open_session(SID, User, Server, Resource, Info) ->
    set_session(SID, User, Server, Resource, undefined, Info),
    mnesia:dirty_update_counter(session_counter,
        jlib:nameprep(Server), 1),
    check_for_sessions_to_replace(User, Server, Resource),
    JID = jlib:make_jid(User, Server, Resource),
    ejabberd_hooks:run(sm_register_connection_hook, JID#jid.lserver,
           [SID, JID, Info]).

close_session(SID, User, Server, Resource) ->
    Info = case mnesia:dirty_read({session, SID}) of
      [] -> [];
      [#session{info=I}] -> I
    end,
    F = fun() -> 
      mnesia:delete({session, SID}),
      mnesia:dirty_update_counter(session_counter,
              jlib:nameprep(Server), -1)    
    end,
    mnesia:sync_dirty(F),
    %%catch del_global_session(User, Server, Resource),
    JID = jlib:make_jid(User, Server, Resource),
    ejabberd_hooks:run(sm_remove_connection_hook, JID#jid.lserver,
           [SID, JID, Info]).

check_in_subscription(Acc, User, Server, _JID, _Type, _Reason) ->
    case ejabberd_auth:is_user_exists(User, Server) of
  true ->
      Acc;
  false ->
      {stop, false}
    end.

bounce_offline_message(From, To, Packet) ->
    Err = jlib:make_error_reply(Packet, ?ERR_SERVICE_UNAVAILABLE),
    ejabberd_router:route(To, From, Err),
    stop.

disconnect_removed_user(User, Server) ->
    ejabberd_sm:route(jlib:make_jid("", "", ""),
          jlib:make_jid(User, Server, ""),
          {xmlelement, "broadcast", [],
           [{exit, "User removed"}]}).

get_user_resources(User, Server) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
%    US = {LUser, LServer},
%    case catch mnesia:dirty_index_read(session, US, #session.us) of
% {'EXIT', _Reason} ->
%     [];
% Ss ->
%     [element(3, S#session.usr) || S <- clean_session_list(Ss)]
%    end.
    case catch get_resource_global_sessions(LUser, LServer) of
      {'EXIT', _Reason} ->
        [];
      Ss ->
        [element(2, S) || S <- Ss]
    end.

get_user_ip(User, Server, Resource) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
    LResource = jlib:resourceprep(Resource),
    USR = {LUser, LServer, LResource},
    case mnesia:dirty_index_read(session, USR, #session.usr) of
  [] ->
      undefined;
  Ss ->
      Session = lists:max(Ss),
      proplists:get_value(ip, Session#session.info)
    end.

get_user_info(User, Server, Resource) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
    LResource = jlib:resourceprep(Resource),
    USR = {LUser, LServer, LResource},
    case mnesia:dirty_index_read(session, USR, #session.usr) of
  [] ->
      offline;
  Ss ->
      Session = lists:max(Ss),
      Node = node(element(2, Session#session.sid)),
      Conn = proplists:get_value(conn, Session#session.info),
      IP = proplists:get_value(ip, Session#session.info),
      [{node, Node}, {conn, Conn}, {ip, IP}]
    end.

set_presence(SID, User, Server, Resource, Priority, Presence, Info) ->
    set_session(SID, User, Server, Resource, Priority, Info),
    ejabberd_hooks:run(set_presence_hook, jlib:nameprep(Server),
           [User, Server, Resource, Presence]).

unset_presence(SID, User, Server, Resource, Status, Info) ->
    set_session(SID, User, Server, Resource, undefined, Info),
    ejabberd_hooks:run(unset_presence_hook, jlib:nameprep(Server),
           [User, Server, Resource, Status]).

close_session_unset_presence(SID, User, Server, Resource, Status) ->
    close_session(SID, User, Server, Resource),
    ejabberd_hooks:run(unset_presence_hook, jlib:nameprep(Server),
           [User, Server, Resource, Status]).

get_session_pid(User, Server, Resource) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
    LResource = jlib:resourceprep(Resource),
    USR = {LUser, LServer, LResource},
    case catch mnesia:dirty_index_read(session, USR, #session.usr) of
  [#session{sid = {_, Pid}}] -> Pid;
  _ -> none
    end.

dirty_get_sessions_list() ->
    mnesia:dirty_select(
      session,
      [{#session{usr = '$1', _ = '_'},
  [],
  ['$1']}]).

dirty_get_my_sessions_list() ->
    mnesia:dirty_select(
      session,
      [{#session{sid = {'_', '$1'}, _ = '_'},
  [{'==', {node, '$1'}, node()}],
  ['$_']}]).

get_vh_session_list(Server) ->
    LServer = jlib:nameprep(Server),
    mnesia:dirty_select(
      session,
      [{#session{usr = '$1', _ = '_'},
  [{'==', {element, 2, '$1'}, LServer}],
  ['$1']}]).

get_vh_session_number(Server) ->
    LServer = jlib:nameprep(Server),
    Query = mnesia:dirty_select(
    session_counter,
    [{#session_counter{vhost = LServer, count = '$1'},
      [],
      ['$1']}]),
    case Query of
  [Count] ->
      Count;
  _ -> 0
    end.
    
register_iq_handler(Host, XMLNS, Module, Fun) ->
    ejabberd_sm ! {register_iq_handler, Host, XMLNS, Module, Fun}.

register_iq_handler(Host, XMLNS, Module, Fun, Opts) ->
    ejabberd_sm ! {register_iq_handler, Host, XMLNS, Module, Fun, Opts}.

unregister_iq_handler(Host, XMLNS) ->
    ejabberd_sm ! {unregister_iq_handler, Host, XMLNS}.


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
init([]) ->
    update_tables(),
    mnesia:create_table(session,
      [{ram_copies, [node()]},
       {attributes, record_info(fields, session)}]),
    mnesia:create_table(session_counter,
      [{ram_copies, [node()]},
       {attributes, record_info(fields, session_counter)}]),
    mnesia:add_table_index(session, usr),
    mnesia:add_table_index(session, us),
    mnesia:add_table_copy(session, node(), ram_copies),
    mnesia:add_table_copy(session_counter, node(), ram_copies),
    mnesia:subscribe(system),
    ets:new(sm_iqtable, [named_table]),
    lists:foreach(
      fun(Host) ->
        ejabberd_hooks:add(roster_in_subscription, Host,
         ejabberd_sm, check_in_subscription, 20),
        ejabberd_hooks:add(offline_message_hook, Host,
         ejabberd_sm, bounce_offline_message, 100),
        ejabberd_hooks:add(remove_user, Host,
         ejabberd_sm, disconnect_removed_user, 100)
      end, ?MYHOSTS),
    ejabberd_commands:register_commands(commands()),

    {ok, #state{}}.

%%--------------------------------------------------------------------
%% Function: %% handle_call(Request, From, State) -> {reply, Reply, State} |
%%                                      {reply, Reply, State, Timeout} |
%%                                      {noreply, State} |
%%                                      {noreply, State, Timeout} |
%%                                      {stop, Reason, Reply, State} |
%%                                      {stop, Reason, State}
%% Description: Handling call messages
%%--------------------------------------------------------------------
handle_call(_Request, _From, State) ->
    Reply = ok,
    {reply, Reply, State}.

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
handle_info({route, From, To, Packet}, State) ->
    case catch do_route(From, To, Packet) of
  {'EXIT', Reason} ->
      ?ERROR_MSG("~p~nwhen processing: ~p",
           [Reason, {From, To, Packet}]);
  _ ->
      ok
    end,
    {noreply, State};
handle_info({mnesia_system_event, {mnesia_down, Node}}, State) ->
    recount_session_table(Node),
    {noreply, State};
handle_info({register_iq_handler, Host, XMLNS, Module, Function}, State) ->
    ets:insert(sm_iqtable, {{XMLNS, Host}, Module, Function}),
    {noreply, State};
handle_info({register_iq_handler, Host, XMLNS, Module, Function, Opts}, State) ->
    ets:insert(sm_iqtable, {{XMLNS, Host}, Module, Function, Opts}),
    {noreply, State};
handle_info({unregister_iq_handler, Host, XMLNS}, State) ->
    case ets:lookup(sm_iqtable, {XMLNS, Host}) of
  [{_, Module, Function, Opts}] ->
      gen_iq_handler:stop_iq_handler(Module, Function, Opts);
  _ ->
      ok
    end,
    ets:delete(sm_iqtable, {XMLNS, Host}),
    {noreply, State};
handle_info(_Info, State) ->
    {noreply, State}.

%%--------------------------------------------------------------------
%% Function: terminate(Reason, State) -> void()
%% Description: This function is called by a gen_server when it is about to
%% terminate. It should be the opposite of Module:init/1 and do any necessary
%% cleaning up. When it returns, the gen_server terminates with Reason.
%% The return value is ignored.
%%--------------------------------------------------------------------
terminate(_Reason, _State) ->
    ejabberd_commands:unregister_commands(commands()),
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
set_global_session(LUser, LServer, LResource, Priority) ->
  Node = atom_to_list(node()),
  User = LUser++"@"++LServer,
  Key = User++"/"++LResource,
  Key2 = User++"/"++LResource++Node,
  Value = binary_to_list(erlmc:get(Key2)),%%get data from memcache
  case Value of []->
      PriorityStr = if is_integer(Priority) -> integer_to_list(Priority); true -> "0" end,
      {{Yy,Mm,Dd},{HH,Mi,Ss}} = calendar:now_to_local_time(os:timestamp()),
      CacheData = LResource++"#"++PriorityStr++"#"++Node++"#"++integer_to_list(Yy)++"-"++integer_to_list(Mm)++"-"++integer_to_list(Dd)++" "++integer_to_list(HH)++":"++integer_to_list(Mi)++":"++integer_to_list(Ss),
      C_Res = case binary_to_list(erlmc:get(User)) of []->
          CacheData;
        Tmp->
          Tmp++","++CacheData
      end,
      erlmc:set(User,list_to_binary(C_Res)),
      erlmc:set(Key,list_to_binary(CacheData)),
      erlmc:set(Key2,list_to_binary("1")),
      Vals = [User, LResource, PriorityStr, Node],
    ejabberd_odbc:sql_query(
          LServer,
          ["delete from global_session where us='", User, "' and res='", LResource, "' and node='", Node, "';"]),
    ejabberd_odbc:sql_query(
          LServer,
          ["insert into global_session(us, res, priority, node, login_date) values ('", odbc_queries:join(Vals, "', '"), "', now());"]);
  _->
      ejabberd_odbc:sql_query(
          LServer,
          ["update global_session set login_date=now() where us='", User, "' and res='", LResource, "' and node='", Node, "';"])
  end
  .

del_global_session(User, Server, Resource) ->
  LUser = jlib:nodeprep(User),
  LServer = jlib:nameprep(Server),
  LResource = jlib:resourceprep(Resource),
  UserKey = LUser++"@"++LServer,
  Key = UserKey++"/"++LResource,
  Key2 = UserKey++"/"++LResource++atom_to_list(node()),
  erlmc:delete(Key),
  erlmc:delete(Key2),
  C_Res = binary_to_list(erlmc:get(UserKey)),
  if C_Res=/=[] ->
     Result = re:split(C_Res,",",[{return,list}]),
     NewList=lists:map(fun(X)-> case lists:prefix(Resource,X) of true->[];_->X  end end, Result) ,
     case lists:delete([],NewList) of []
      -> erlmc:delete(UserKey);
      Has->
        Cache=lists:foldl(fun(Rec,CacheData)->case CacheData of []->Rec;_-> CacheData++","++Rec end end,[],Has),
          
        erlmc:set(UserKey,list_to_binary(Cache))
    end
  end,
  ejabberd_odbc:sql_query(
        LServer,
        ["delete from global_session where us='", LUser++"@"++LServer, "' and res='", LResource, "'and node='", atom_to_list(node()), "';"])
  .

set_session(SID, User, Server, Resource, Priority, Info) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
    LResource = jlib:resourceprep(Resource),
    US = {LUser, LServer},
    USR = {LUser, LServer, LResource},
    F = fun() ->
    set_global_session(LUser, LServer, LResource, Priority),
    mnesia:write(#session{sid = SID,
              usr = USR,
              us = US,
              priority = Priority,
              info = Info})
  end,
    mnesia:sync_dirty(F).

%% Recalculates alive sessions when Node goes down 
%% and updates session and session_counter tables 
recount_session_table(Node) ->
    F = fun() ->
    Es = mnesia:select(
           session,
           [{#session{sid = {'_', '$1'}, _ = '_'},
       [{'==', {node, '$1'}, Node}],
       ['$_']}]),
    lists:foreach(fun(E) ->
              mnesia:delete({session, E#session.sid})
            end, Es),
    %% reset session_counter table with active sessions
    mnesia:clear_table(session_counter),
    lists:foreach(fun(Server) ->
        LServer = jlib:nameprep(Server),
        Hs = mnesia:select(session,
            [{#session{usr = '$1', _ = '_'},
            [{'==', {element, 2, '$1'}, LServer}],
            ['$1']}]),
        mnesia:write(
            #session_counter{vhost = LServer, 
                 count = length(Hs)})
            end, ?MYHOSTS)
  end,
    mnesia:async_dirty(F).

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

do_route(From, To, Packet) ->
    #jid{user = User, server = Server,
   luser = LUser, lserver = LServer, lresource = LResource} = To,
    {xmlelement, Name, Attrs, _Els} = Packet,
    case LResource of
  "" ->
      case Name of
    "presence" ->
        {Pass, _Subsc} =
      case xml:get_attr_s("type", Attrs) of
          "subscribe" ->
        Reason = xml:get_path_s(
             Packet,
             [{elem, "status"}, cdata]),
        {is_privacy_allow(From, To, Packet) andalso
         ejabberd_hooks:run_fold(
           roster_in_subscription,
           LServer,
           false,
           [User, Server, From, subscribe, Reason]),
         true};
          "subscribed" ->
        {is_privacy_allow(From, To, Packet) andalso
         ejabberd_hooks:run_fold(
           roster_in_subscription,
           LServer,
           false,
           [User, Server, From, subscribed, ""]),
         true};
          "unsubscribe" ->
        {is_privacy_allow(From, To, Packet) andalso
         ejabberd_hooks:run_fold(
           roster_in_subscription,
           LServer,
           false,
           [User, Server, From, unsubscribe, ""]),
         true};
          "unsubscribed" ->
        {is_privacy_allow(From, To, Packet) andalso
         ejabberd_hooks:run_fold(
           roster_in_subscription,
           LServer,
           false,
           [User, Server, From, unsubscribed, ""]),
         true};
          _ ->
        {true, false}
      end,
        if Pass ->
          PResources = get_user_present_resources(
             LUser, LServer),
          lists:foreach(
            fun({_, R}) ->
              do_route(
          From,
          jlib:jid_replace_resource(To, R),
          Packet)
            end, PResources);
           true ->
          ok
        end;
    "message" ->
        route_message(From, To, Packet);
    "iq" ->
        process_iq(From, To, Packet);
    "broadcast" ->
        lists:foreach(
          fun(R) ->
            do_route(From,
               jlib:jid_replace_resource(To, R),
               Packet)
          end, get_user_resources(User, Server));
    _ ->
        ok
      end;
  _ ->
      USR = {LUser, LServer, LResource},
      case mnesia:dirty_index_read(session, USR, #session.usr) of
    [] ->
        case Name of
      "message" ->
          route_message(From, To, Packet);
      "iq" ->
          %%可能用户不在本结点登录
          catch case get_resource_global_sessions(LUser, LServer, LResource) of
            [Aglobal_session|_] ->
              ejabberdex_cluster_router:route(element(4, Aglobal_session), element(1, Aglobal_session), element(2, Aglobal_session), {From, To, Packet});
            _ ->
              case xml:get_attr_s("type", Attrs) of
                "error" -> ok;
                "result" -> ok;
                _ ->
                    Err =
                  jlib:make_error_reply(
                    Packet, ?ERR_SERVICE_UNAVAILABLE),
                    ejabberd_router:route(To, From, Err)
              end
          end;
      "presence" ->
          %%可能用户不在本结点登录
          catch case get_resource_global_sessions(LUser, LServer, LResource) of
            [Aglobal_session|_] ->
              ejabberdex_cluster_router:route(element(4, Aglobal_session), element(1, Aglobal_session), element(2, Aglobal_session), {From, To, Packet});
            _ ->
              drop
          end;
      _ ->
          ?DEBUG("packet droped~n", [])
        end;
    Ss ->
        Session = lists:max(Ss),
        Pid = element(2, Session#session.sid),
        ?DEBUG("sending to process ~p~n", [Pid]),
        Pid ! {route, From, To, Packet}
      end
    end.

%% The default list applies to the user as a whole,
%% and is processed if there is no active list set
%% for the target session/resource to which a stanza is addressed,
%% or if there are no current sessions for the user.
is_privacy_allow(From, To, Packet) ->
    User = To#jid.user,
    Server = To#jid.server,
    PrivacyList = ejabberd_hooks:run_fold(privacy_get_user_list, Server,
            #userlist{}, [User, Server]),
    is_privacy_allow(From, To, Packet, PrivacyList).

%% Check if privacy rules allow this delivery
%% Function copied from ejabberd_c2s.erl
is_privacy_allow(From, To, Packet, PrivacyList) ->
    User = To#jid.user,
    Server = To#jid.server,
    allow == ejabberd_hooks:run_fold(
         privacy_check_packet, Server,
         allow,
         [User,
    Server,
    PrivacyList,
    {From, To, Packet},
    in]).

route_message(From, To, Packet) ->
    LUser = To#jid.luser,
    LServer = To#jid.lserver,
    LResource = To#jid.lresource,
    
%    PrioRes = get_user_present_resources(LUser, LServer),
%    case catch lists:max(PrioRes) of
% {Priority, _R} when is_integer(Priority), Priority >= 0 ->
%     lists:foreach(
%       %% Route messages to all priority that equals the max, if
%       %% positive
%       fun({P, R}) when P == Priority ->
%         LResource = jlib:resourceprep(R),
%         USR = {LUser, LServer, LResource},
%         case mnesia:dirty_index_read(session, USR, #session.usr) of
%       [] ->
%           ok; % Race condition
%       Ss ->
%           Session = lists:max(Ss),
%           Pid = element(2, Session#session.sid),
%           ?DEBUG("sending to process ~p~n", [Pid]),
%           Pid ! {route, From, To, Packet}
%         end;
%    %% Ignore other priority:
%    ({_Prio, _Res}) ->
%         ok
%       end,
%       PrioRes);
% _ ->
%     case xml:get_tag_attr_s("type", Packet) of
%   "error" ->
%       ok;
%   "groupchat" ->
%       bounce_offline_message(From, To, Packet);
%   "headline" ->
%       bounce_offline_message(From, To, Packet);
%   _ ->
%       case ejabberd_auth:is_user_exists(LUser, LServer) of
%     true ->
%         case is_privacy_allow(From, To, Packet) of
%       true ->
%           ejabberd_hooks:run(offline_message_hook,
%                  LServer,
%                  [From, To, Packet]);
%       false ->
%           ok
%         end;
%     _ ->
%         Err = jlib:make_error_reply(
%           Packet, ?ERR_SERVICE_UNAVAILABLE),
%         ejabberd_router:route(To, From, Err)
%       end
%     end
%    end.

    case get_resource_global_session_maxpriority(LUser, LServer, LResource) of
      [{Aus, Ares, _Apriority, Anode1, _Alogin_date}|_] ->
          Anode = ejabberdex_cluster_router:list_to_atom_ex(Anode1),
          if 
            Anode /= node() ->
              ejabberdex_cluster_router:route(Anode, Aus, Ares, {From, To, Packet});
            true ->
              USR = {LUser, LServer, Ares},
              case mnesia:dirty_index_read(session, USR, #session.usr) of
                [] ->                   
                    ok; % Race condition
                Ss ->
                    Session = lists:max(Ss),
                    Pid = element(2, Session#session.sid),
                    ?DEBUG("sending to process ~p~n", [Pid]),
                    Pid ! {route, From, To, Packet}
              end
          end;
      _ ->
        case xml:get_tag_attr_s("type", Packet) of
          "error" ->
              ok;
          "groupchat" ->
              bounce_offline_message(From, To, Packet);
          "headline" ->
              bounce_offline_message(From, To, Packet);
          _ ->
              case ejabberd_auth:is_user_exists(LUser, LServer) of
            true ->
                case is_privacy_allow(From, To, Packet) of
              true ->
                  ejabberd_hooks:run(offline_message_hook,
                         LServer,
                         [From, To, Packet]);
              false ->
                  ok
                end;
            _ ->
                Err = jlib:make_error_reply(
                  Packet, ?ERR_SERVICE_UNAVAILABLE),
                ejabberd_router:route(To, From, Err)
          end
        end
    end.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%clean_session_list(Ss) ->
%    clean_session_list(lists:keysort(#session.usr, Ss), []).
%
%clean_session_list([], Res) ->
%    Res;
%clean_session_list([S], Res) ->
%    [S | Res];
%clean_session_list([S1, S2 | Rest], Res) ->
%    if
% S1#session.usr == S2#session.usr ->
%     if
%   S1#session.sid > S2#session.sid ->
%       clean_session_list([S1 | Rest], Res);
%   true ->
%       clean_session_list([S2 | Rest], Res)
%     end;
% true ->
%     clean_session_list([S2 | Rest], [S1 | Res])
%    end.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

get_user_present_resources(LUser, LServer) ->
%    US = {LUser, LServer},
%    case catch mnesia:dirty_index_read(session, US, #session.us) of
% {'EXIT', _Reason} ->
%     [];
% Ss ->
%     [{S#session.priority, element(3, S#session.usr)} ||
%   S <- clean_session_list(Ss), is_integer(S#session.priority)]
%    end.
    case catch get_resource_global_sessions(LUser, LServer) of
      {'EXIT', _Reason} ->
        [];
      Ss ->
        [{list_to_integer(element(3, S)), element(2, S)} || S <- Ss, is_integer(catch list_to_integer(element(3, S)))]
    end.

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%% On new session, check if some existing connections need to be replace
check_for_sessions_to_replace(User, Server, Resource) ->
    LUser = jlib:nodeprep(User),
    LServer = jlib:nameprep(Server),
    LResource = jlib:resourceprep(Resource),

    %% TODO: Depending on how this is executed, there could be an unneeded
    %% replacement for max_sessions. We need to check this at some point.
    check_existing_resources(LUser, LServer, LResource),
    check_max_sessions(LUser, LServer).

check_existing_resources(LUser, LServer, LResource) ->
    %%检查global_session
    ALglobal_session = get_resource_global_sessions(LUser, LServer, LResource),
    lists:foreach(fun(Aglobal_session) -> 
        Node = ejabberdex_cluster_router:list_to_atom_ex(element(4, Aglobal_session)),
        if  
          node() /= Node ->
            %%通知另外的节点
            ejabberdex_cluster_router:route(Node, LUser++"@"++LServer, LResource, replaced);
          true ->
            ok
        end
      end, 
      ALglobal_session),

    SIDs = get_resource_sessions(LUser, LServer, LResource),
    if
  SIDs == [] -> ok;
  true ->
      %% A connection exist with the same resource. We replace it:
      MaxSID = lists:max(SIDs),
      lists:foreach(
        fun({_, Pid} = S) when S /= MaxSID ->
          Pid ! replaced;
     (_) -> ok
        end, SIDs)
    end.

is_existing_resource(LUser, LServer, LResource) ->
    [] /= get_resource_sessions(LUser, LServer, LResource).

get_resource_sessions(User, Server, Resource) ->
    USR = {jlib:nodeprep(User), jlib:nameprep(Server), jlib:resourceprep(Resource)},
    mnesia:dirty_select(
       session,
       [{#session{sid = '$1', usr = USR, _ = '_'}, [], ['$1']}]).

get_resource_global_sessions(User, Server) ->
    Key = User++"@"++Server, 
    Value = binary_to_list(erlmc:get(Key)),%%get data from memcache
    case Value of []-> 
      Rs = ejabberd_odbc:sql_query(
        Server,
        ["select us, res, priority, node, login_date from global_session "
         "where  us='", User++"@"++Server, "' order by login_date"]),
      Result =case Rs of
        {selected, _, Items} ->
          Items;
         _->
            []
      end,
      case Result of 
        []->
            %%判断session是否存在。处理由于客户端自动重连把之前进程T退时，清空了global_session和缓存的情况 
            case mnesia:dirty_index_read(session,{User,Server}, #session.us) of
                    [] ->
                        []; % Race condition
                    Ss ->
                        lists:map(fun(Session)-> 
                Res = element(3, Session#session.usr),
                          Priority = 0,
                          {{Y,M,D},{Hh,Mi,Si}} = calendar:now_to_local_time(element(1, Session#session.sid)),
                          Login_date =lists:append([integer_to_list(Y),"-",integer_to_list(M),"-",integer_to_list(D)," ",integer_to_list(Hh),":",integer_to_list(Mi),":",integer_to_list(Si)]),
                          set_global_session(User, Server, Res, Priority),
                          {Key,Res,Priority,atom_to_list(node()), Login_date}
                        end,Ss)
            end;
          _->
            Result
        end;
  _->
      Result = re:split(Value,",",[{return,list}]),
      lists:filter(fun(A)->A=/=[] end,
        lists:map(fun(X)->
          Info = re:split(X,"#",[{return,list}]),
          {Res,Priority,Node, Login_date}=list_to_tuple(Info),
              case binary_to_list(erlmc:get(Node)) of 
              []->[];
              _->
            {Key,Res,Priority,Node, Login_date}
              end
        end, 
      Result))
  end
    .
get_resource_global_session_maxpriority(User, Server, Resource) ->
  Key = User++"@"++Server, 
    Value = binary_to_list(erlmc:get(Key)),%%get data from memcache
    case Value of []
    ->
      Rs = ejabberd_odbc:sql_query(
        Server,
        ["select us, res, priority, node, login_date from global_session "
         "where  us='", User++"@"++Server, "' order by case res when '", Resource, "' then 1 else 0 end desc, priority desc limit 0, 1"]),
      Result=case Rs of
        {selected, _, Items} ->
          Items;
        _->
          []
      end,
      case Result of 
        []->
            %%判断session是否存在。处理由于客户端自动重连把之前进程T退时，清空了global_session和缓存的情况 
            case mnesia:dirty_index_read(session,{User,Server}, #session.us) of
                    [] ->
                        []; % Race condition
                    Ss ->
                        Session = lists:max(Ss),
                        Res = element(3, Session#session.usr),
                        Priority = 0,
                        {{Y,M,D},{Hh,Mi,Si}} = calendar:now_to_local_time(element(1, Session#session.sid)),
                        Login_date =lists:append([integer_to_list(Y),"-",integer_to_list(M),"-",integer_to_list(D)," ",integer_to_list(Hh),":",integer_to_list(Mi),":",integer_to_list(Si)]),
                        set_global_session(User, Server, Res, Priority),
                        [{Key,Res,Priority,atom_to_list(node()), Login_date}]
            end;
          _-> 
            Result
        end;
    _->
      Result = re:split(Value,",",[{return,list}]),
        SelfNode = atom_to_list(node()),
      R1=lists:map(fun(X)->
          Info = re:split(X,"#",[{return,list}]),
          {Res,Priority,Node, Login_date}=list_to_tuple(Info),
          case SelfNode of Node->{Key,Res,Priority,Node, Login_date};
          _->
            case net_adm:ping(list_to_atom(Node)) of pang->{[],[],"-10000",[],[]};
              _->
                {Key,Res,Priority,Node, Login_date}
            end
          end
      end, 
      Result),
      R2=lists:sort(fun(A,B)-> list_to_integer(element(3,A))>list_to_integer(element(3,B)) end,R1),
      FirstRes = hd(R2),
      case FirstRes of 
        {[],[],"-10000",[],[]}->[];
        _->[FirstRes]
      end
  end
.
get_resource_global_sessions(User, Server, Resource) ->
  Key = User++"@"++Server++"/"++Resource, 
    Value = binary_to_list(erlmc:get(Key)),%%get data from memcache
    case Value of []
    ->
    Rs = ejabberd_odbc:sql_query(
          Server,
          ["select us, res, priority, node, login_date from global_session "
           "where  us='", User++"@"++Server, "' and res='", Resource, "'"]),
    Result = case Rs of
          {selected, _, Items} ->           
            Items;
          _ ->
              []
    end,
      case Result of 
        []->
            %%判断session是否存在。处理由于客户端自动重连把之前进程T退时，清空了global_session和缓存的情况 
            case mnesia:dirty_index_read(session,{User,Server,Resource}, #session.usr) of
                    [] ->
                        []; % Race condition
                    Ss ->
                        Session = lists:max(Ss),
                        Res = element(3, Session#session.usr),
                        Priority = 0,
                        {{Y,M,D},{Hh,Mi,Si}} = calendar:now_to_local_time(element(1, Session#session.sid)),
                        Login_date =lists:append([integer_to_list(Y),"-",integer_to_list(M),"-",integer_to_list(D)," ",integer_to_list(Hh),":",integer_to_list(Mi),":",integer_to_list(Si)]),
                        set_global_session(User, Server, Res, Priority),
                        [{Key,Res,Priority,atom_to_list(node()), Login_date}]
            end;
          _-> 
            Result
        end;        
    _->
      Info = re:split(Value,"#",[{return,list}]),
      {Res,Priority,Node, Login_date}=list_to_tuple(Info),
      case binary_to_list(erlmc:get(Node)) of 
        []->[];
        _->[{User++"@"++Server,Res,Priority,Node, Login_date}]
      end
  end
    .

check_max_sessions(LUser, LServer) ->
    %% If the max number of sessions for a given is reached, we replace the
    %% first one
%    SIDs = mnesia:dirty_select(
%      session,
%      [{#session{sid = '$1', us = {LUser, LServer}, _ = '_'}, [],
%        ['$1']}]),
    SIDs = get_resource_global_sessions(LUser, LServer),
    MaxSessions = get_max_user_sessions(LUser, LServer),
    if
  length(SIDs) =< MaxSessions ->
      ok;
  true ->
%     {_, Pid} = lists:min(SIDs),
%     Pid ! replaced
      Aglobal_session = hd(SIDs),
      ejabberdex_cluster_router:route(ejabberdex_cluster_router:list_to_atom_ex(element(4, Aglobal_session)), element(1, Aglobal_session), element(2, Aglobal_session), replaced)
    end.


%% Get the user_max_session setting
%% This option defines the max number of time a given users are allowed to
%% log in
%% Defaults to infinity
get_max_user_sessions(LUser, Host) ->
    case acl:match_rule(
     Host, max_user_sessions, jlib:make_jid(LUser, Host, "")) of
  Max when is_integer(Max) -> Max;
  infinity -> infinity;
  _ -> ?MAX_USER_SESSIONS
    end.

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

process_iq(From, To, Packet) ->
    IQ = jlib:iq_query_info(Packet),
    case IQ of
  #iq{xmlns = XMLNS} ->
      Host = To#jid.lserver,
      case ets:lookup(sm_iqtable, {XMLNS, Host}) of
    [{_, Module, Function}] ->
        ResIQ = Module:Function(From, To, IQ),
        if
      ResIQ /= ignore ->
          ejabberd_router:route(To, From,
              jlib:iq_to_xml(ResIQ));
      true ->
          ok
        end;
    [{_, Module, Function, Opts}] ->
        gen_iq_handler:handle(Host, Module, Function, Opts,
            From, To, IQ);
    [] ->
        Err = jlib:make_error_reply(
          Packet, ?ERR_SERVICE_UNAVAILABLE),
        ejabberd_router:route(To, From, Err)
      end;
  reply ->
      ok;
  _ ->
      Err = jlib:make_error_reply(Packet, ?ERR_BAD_REQUEST),
      ejabberd_router:route(To, From, Err),
      ok
    end.

force_update_presence({LUser, _LServer} = US) ->
    case catch mnesia:dirty_index_read(session, US, #session.us) of
        {'EXIT', _Reason} ->
            ok;
        Ss ->
            lists:foreach(fun(#session{sid = {_, Pid}}) ->
                                  Pid ! {force_update_presence, LUser}
                          end, Ss)
    end.


%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% ejabberd commands

commands() ->
  [
     #ejabberd_commands{name = connected_users,
           tags = [session],
           desc = "List all established sessions",
           module = ?MODULE, function = connected_users,
           args = [],
           result = {connected_users, {list, {sessions, string}}}},
     #ejabberd_commands{name = connected_users_number,
           tags = [session, stats],
           desc = "Get the number of established sessions",
           module = ?MODULE, function = connected_users_number,
           args = [],
           result = {num_sessions, integer}},
     #ejabberd_commands{name = user_resources,
           tags = [session],
           desc = "List user's connected resources",
           module = ?MODULE, function = user_resources,
           args = [{user, string}, {host, string}],
           result = {resources, {list, {resource, string}}}}
  ].

connected_users() ->
    USRs = dirty_get_sessions_list(),
    SUSRs = lists:sort(USRs),
    lists:map(fun({U, S, R}) -> [U, $@, S, $/, R] end, SUSRs).

connected_users_number() ->
    length(dirty_get_sessions_list()).

user_resources(User, Server) ->
    Resources =  get_user_resources(User, Server),
    lists:sort(Resources).


%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%% Update Mnesia tables

update_tables() ->
    case catch mnesia:table_info(session, attributes) of
  [ur, user, node] ->
      mnesia:delete_table(session);
  [ur, user, pid] ->
      mnesia:delete_table(session);
  [usr, us, pid] ->
      mnesia:delete_table(session);
  [sid, usr, us, priority] ->
      mnesia:delete_table(session);
  [sid, usr, us, priority, info] ->
      ok;
  {'EXIT', _} ->
      ok
    end,
    case lists:member(presence, mnesia:system_info(tables)) of
  true ->
      mnesia:delete_table(presence);
  false ->
      ok
    end,
    case lists:member(local_session, mnesia:system_info(tables)) of
  true ->
      mnesia:delete_table(local_session);
  false ->
      ok
    end.
