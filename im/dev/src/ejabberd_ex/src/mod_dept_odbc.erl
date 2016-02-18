%%%----------------------------------------------------------------------
%%% 组织机构管理
%%%----------------------------------------------------------------------

%%% @doc 组织机构管理 (存储于odbc数据库中).
%%%
%%% 组织机构管理使用IQ节，并且子元素<query/>必须指定名空间为'jabber:iq:dept'， 
%%% 通过属性ver指定当前版本，如ver=''，当未指定版本或指定版本与当前服务器版本不一致时，服务器返回其组织机构；
%%% <query/>可以包含一个或多个子元素，每个子元素描述每个组织机构具体的信息。
%%%
%%% mod参数：{versioning, true|false} 是否允许使用版本，默认为true
%%%----------------------------------------------------------------------
-module(mod_dept_odbc).
-author('feihu929@sina.com').

-behaviour(gen_mod).

-export([start/2, stop/1,
         process_iq/3,
         process_local_iq/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../include/mod_dept.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, Opts) ->
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),    
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_DEPT,
                                  ?MODULE, process_iq, IQDisc).

%%%----------------------------------------------------------------------
stop(Host) ->
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_DEPT).

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

%% 仅当下列情况才返回数据
%%     - mod参数 versioning 被设置为false 或
%%     - 客户端发来的请求中未设置ver属性 或
%%     - 服务器上没有存储当前版本 或
%%     - 当前版本与客户请求的版本不一致
process_iq_get(From, _To, #iq{sub_el = SubEl} = IQ) ->
    LUser = From#jid.luser,
    LServer = From#jid.lserver,
    US = LUser ++ "@" ++ LServer,
    try
      	Ascope = xml:get_tag_attr_s("scope", SubEl),
     	APid = xml:get_tag_attr_s("pid", SubEl),
      	{ItemsToSend, VersionToSend} = case APid of "-10000"->
  			RootID=func_utils:getOrgRootID(US),
  			DeptInfo=ejabberdex_odbc_query:get_deptandstat_by_id(RootID),
  			{lists:map(fun item_to_xml/1, DeptInfo), false};
	  	_->
	    	case {xml:get_tag_attr("ver", SubEl), dept_versioning_enabled(LServer)} of
	      	{{value, RequestedVersion}, true} ->
		      %% 从数据库中取出当前版本
    			case ejabberdex_odbc_query:get_dept_version(US) of
    				[#dept_version{version = RequestedVersion}] ->
    					{false, false};
    				[#dept_version{version = NewVersion}] ->
    				        Adepts = if Ascope == "1" -> im_organ_odbc:getdeptbypid_stat(case APid of ""-> func_utils:getOrgRootID(US);_-> APid end); true -> im_organ_odbc:readall_stat(US) end,
    				        {lists:map(fun item_to_xml/1, Adepts), NewVersion};
    				[] ->
    					DeptVersion = sha:sha(term_to_binary(now())),
    					ejabberdex_odbc_query:set_dept_version(US, DeptVersion),
    					Adepts = if Ascope == "1" -> im_organ_odbc:getdeptbypid_stat(case APid of ""-> func_utils:getOrgRootID(US);_-> APid end); true -> im_organ_odbc:readall_stat(US) end,
    					{lists:map(fun item_to_xml/1, Adepts), DeptVersion}
    			end;
	      	_ ->
	        	Adepts = if Ascope == "1" -> im_organ_odbc:getdeptbypid_stat(case APid of ""-> func_utils:getOrgRootID(US);_-> APid end); true -> im_organ_odbc:readall_stat(US) end,
	        	{lists:map(fun item_to_xml/1, Adepts), false}
		  	end
		end,
		IQ#iq{type = result, sub_el = case {ItemsToSend, VersionToSend} of
			{false, false} ->  [];
			{Items, false} -> [{xmlelement, "query", [{"xmlns", ?NS_DEPT}], Items}];
			{Items, Version} -> [{xmlelement, "query", [{"xmlns", ?NS_DEPT}, {"ver", Version}], Items}]
		end}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		  IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% 取得是否允许使用版本的mod参数versioning，true|false
dept_versioning_enabled(Host) ->
    gen_mod:get_module_opt(Host, ?MODULE, versioning, true).

%%%----------------------------------------------------------------------
%%% 生成XML
item_to_xml(Item) ->
    Attrs = [
              {"deptid", Item#base_dept.deptid},
              {"deptname", Item#base_dept.deptname},
              {"pid", Item#base_dept.pid},
              {"noorder", Item#base_dept.noorder},
              {"manager", Item#base_dept.manager},
              {"empcount",Item#base_dept.remark}   %%当前部门的人数
            ],
    {xmlelement, "item", Attrs, []}.

%%%----------------------------------------------------------------------
%%% 设置组织机构
process_iq_set(_From, _To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, _Name, _Attrs, Items} = SubEl,
        FConverToArray = fun(Item) ->
          DeptID    = xml:get_tag_attr_s("deptid", Item),
          DeptName  = xml:get_tag_attr_s("deptname", Item),
          ParentID  = xml:get_tag_attr_s("pid", Item),
          NoOrder   = xml:get_tag_attr_s("noorder", Item),
          [DeptID, DeptName, ParentID, NoOrder]
        end,
        FFilterErrData = fun([DeptID, _DeptName, ParentID, _NoOrder]) ->
          DeptID /= "" andalso ParentID /= ""
        end,
        im_organ_odbc:executebatch(lists:filter(FFilterErrData, lists:map(FConverToArray, Items))),
        IQ#iq{type = result, sub_el=[]}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.
  
%%%----------------------------------------------------------------------
