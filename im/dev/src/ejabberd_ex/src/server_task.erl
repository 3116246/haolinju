%%% -------------------------------------------------------------------
%%% Author  : zhys9
%%% Description : erlang版crontab
%%%
%%% Created : 2010-12-8
%%% -------------------------------------------------------------------
-module(server_task).
-author('http://t.sina.com.cn/zhys9').
-email('zhys99@gmail.com').

-behaviour(gen_server).
%% --------------------------------------------------------------------
%% Include files
%% --------------------------------------------------------------------

%% crontab配置文件
-define(CRONTAB_CONFIG,	"../conf/task.yrl").

%% --------------------------------------------------------------------
%% External exports
-export([list/0, add/1, del/1, reload/0]).

%% gen_server callbacks
-export([start_link/0, init/1, handle_call/3, handle_cast/2, handle_info/2, terminate/2, code_change/3,stop/0]).

%% 一个任务
-record(task, {
			   min = [],
			   hour = [],
			   day = [],
			   month = [],
			   week = [],
			   exec :: tuple()
			   }).
%% crond 状态
-record(state, {
				task_list = [] :: [#task{}]
				}).

%% ====================================================================
%% External functions
%% ====================================================================
start_link() ->
	gen_server:start_link({local,?MODULE}, ?MODULE, [], []).

%% 列出当前任务
list() ->
	gen_server:call(?MODULE, list).
%% 添加一个任务
add(Task) ->
	gen_server:cast(?MODULE, {add, Task}).
%% 删除一个任务(参数要与添加任务时提供的参数相同)
del(Task) ->
	gen_server:cast(?MODULE, {del, Task}).
%% 重载配置文件
reload() ->
	init([]).

%% ====================================================================
%% Server functions
%% ====================================================================


%% --------------------------------------------------------------------
%% Function: init/1
%% Description: Initiates the server
%% Returns: {ok, State}          |
%%          {ok, State, Timeout} |
%%          ignore               |
%%          {stop, Reason}
%% --------------------------------------------------------------------
init([]) ->
	process_flag(trap_exit, true),
	Data1 = init(),
	Data=loadTask(Data1), %%加载广播消息任务
	crond_timer(),	%% 开启timer
	io:format("server task manager is started!~n"),
    {ok, #state{task_list=Data}}.

init() ->
	%% 从yrl加载初始配置
	case file:consult(?CRONTAB_CONFIG) of
		{ok, Data} ->
			io:format("crond init..ok~n"),
			[ #task{min=Min,hour=Hour,day=Day,month=Month,week=Week,exec={M,F,A}} || {Min,Hour,Day,Month,Week, {M,F,A}} <- Data];
		{_,R}->
			io:format("Crontab init error.~n~p~n",[R]),
			[]
	end.
	
loadTask(List)->
    Lst = message_action_odbc:queryNoSendMsg(),
    loadTask(Lst,List)
.
loadTask(Task,List) when Task==[]->
    List
;
loadTask([H|Task],List)->
  {Id,Month,Day,Hour,Min,Week} = H,
  Rec = #task{min=Min,hour=Hour,day=Day,month=Month,week=Week,exec={message_action,send_msg,[Id]}},
  loadTask(Task,[Rec|List])
.

%% --------------------------------------------------------------------
%% Function: handle_call/3
%% Description: Handling call messages
%% Returns: {reply, Reply, State}          |
%%          {reply, Reply, State, Timeout} |
%%          {noreply, State}               |
%%          {noreply, State, Timeout}      |
%%          {stop, Reason, Reply, State}   | (terminate/2 is called)
%%          {stop, Reason, State}            (terminate/2 is called)
%% --------------------------------------------------------------------
handle_call(list, _From, State) ->
    Reply = State#state.task_list,
    {reply, Reply, State};
handle_call(_Request, _From, State) ->
    Reply = ok,
    {reply, Reply, State}.

%% --------------------------------------------------------------------
%% Function: handle_cast/2
%% Description: Handling cast messages
%% Returns: {noreply, State}          |
%%          {noreply, State, Timeout} |
%%          {stop, Reason, State}            (terminate/2 is called)
%% --------------------------------------------------------------------
handle_cast({add, Task}, State = #state{task_list=List}) ->
	case Task of
		{Min,Hour,Day,Month,Week, {M,F,A}} ->
		  Rec = #task{min=case Min of []->"1";_-> Min end,hour=Hour,day=Day,month=Month,week=Week,exec={M,F,A}},
			State2 = State#state{task_list=[Rec|List]};
		_ ->
			io:format("add crontab error: ~p", [Task]),
			State2 = State
	end,
	{noreply, State2};
handle_cast({del, Task}, State = #state{task_list=List}) ->
	case Task of
		{Min,Hour,Day,Month,Week, MFA} ->
			List2 = lists:foldl(fun(T, L)->
									if
										Min=:=T#task.min andalso Hour=:=T#task.hour
										  andalso Day=:=T#task.day andalso Month=:=T#task.month
										  andalso Week=:=T#task.week andalso MFA=:=T#task.exec ->
											lists:delete(T, L);
										true ->
											L
									end
								end, List, List),
			State2 = State#state{task_list=List2};
		_ ->
			io:format("add crontab error: ~p", [Task]),
			State2 = State
	end,
	{noreply, State2};
handle_cast(_Msg, State) ->
    {noreply, State}.    

%% --------------------------------------------------------------------
%% Function: handle_info/2
%% Description: Handling all non call/cast messages
%% Returns: {noreply, State}          |
%%          {noreply, State, Timeout} |
%%          {stop, Reason, State}            (terminate/2 is called)
%% --------------------------------------------------------------------
handle_info({timeout, _Ref, crontab}, State) ->
%% 	?MSG_DEBUG("crontab start: ok",[]),
	State2 = crond_run(State),
	crond_timer(),	%% 开启timer
	{noreply, State2};
handle_info(_Info, State) ->
    {noreply, State}.

%% --------------------------------------------------------------------
%% Function: terminate/2
%% Description: Shutdown the server
%% Returns: any (ignored by gen_server)
%% --------------------------------------------------------------------
terminate(_Reason, _State) ->
    ok.
    
stop() ->ok.

 
%% --------------------------------------------------------------------
%% Func: code_change/3
%% Purpose: Convert process state when code is changed
%% Returns: {ok, NewState}
%% --------------------------------------------------------------------
code_change(_OldVsn, State, _Extra) ->
    {ok, State}.

%% --------------------------------------------------------------------
%%% Internal functions
%% --------------------------------------------------------------------


%% 计时器，即，crontab最小计时单位
crond_timer() ->
    erlang:start_timer(60000, self(), crontab).

%% 扫描任务列表，执行可执行的任务
crond_run(State = #state{task_list=TaskList}) ->
	{Date, Time} = erlang:localtime(),
    Week = calendar:day_of_the_week(Date),
	TaskList2 = lists:foldl(fun(Task=#task{exec={M,F,A}}, NewTaskList) ->
								case check_time(Task, Date, Time, Week) of
									true ->
										spawn(fun()->apply(M, F, A)end),
										%% 可以在这里扩展，实现执行多少次之后就不执行了
										Task;
									false ->
										Task
								end,
								[Task|NewTaskList]
							end, [], TaskList),
	State#state{task_list = TaskList2}.

%% 时间检查
check_time(Task, {_Y,M,D}, {H,I,_S}, Week) ->
	CheckList = [{Task#task.min, I}, {Task#task.hour, H}, {Task#task.day, D}, 
				 {Task#task.month, M},{Task#task.week, Week}],
	check_time(CheckList).

check_time([]) ->
	true;
check_time([H|T]) ->
	case check_time2(H) of
		true ->
			check_time(T);
		false ->
			false
	end.

check_time2({[], _NowTime}) ->
	true;
check_time2({TaskTime, NowTime}) ->
  Src  =case is_integer(TaskTime) of true-> integer_to_list(TaskTime); _-> TaskTime end,
  {_,Tr1} = regexp:split(Src,"|"),
  lists:member(integer_to_list(NowTime), Tr1).
