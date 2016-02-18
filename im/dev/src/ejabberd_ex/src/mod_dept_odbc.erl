%%%----------------------------------------------------------------------
%%% ��֯��������
%%%----------------------------------------------------------------------

%%% @doc ��֯�������� (�洢��odbc���ݿ���).
%%%
%%% ��֯��������ʹ��IQ�ڣ�������Ԫ��<query/>����ָ�����ռ�Ϊ'jabber:iq:dept'�� 
%%% ͨ������verָ����ǰ�汾����ver=''����δָ���汾��ָ���汾�뵱ǰ�������汾��һ��ʱ����������������֯������
%%% <query/>���԰���һ��������Ԫ�أ�ÿ����Ԫ������ÿ����֯�����������Ϣ��
%%%
%%% mod������{versioning, true|false} �Ƿ�����ʹ�ð汾��Ĭ��Ϊtrue
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
%%% ����IQ������
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

%% ������������ŷ�������
%%     - mod���� versioning ������Ϊfalse ��
%%     - �ͻ��˷�����������δ����ver���� ��
%%     - ��������û�д洢��ǰ�汾 ��
%%     - ��ǰ�汾��ͻ�����İ汾��һ��
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
		      %% �����ݿ���ȡ����ǰ�汾
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
%%% ȡ���Ƿ�����ʹ�ð汾��mod����versioning��true|false
dept_versioning_enabled(Host) ->
    gen_mod:get_module_opt(Host, ?MODULE, versioning, true).

%%%----------------------------------------------------------------------
%%% ����XML
item_to_xml(Item) ->
    Attrs = [
              {"deptid", Item#base_dept.deptid},
              {"deptname", Item#base_dept.deptname},
              {"pid", Item#base_dept.pid},
              {"noorder", Item#base_dept.noorder},
              {"manager", Item#base_dept.manager},
              {"empcount",Item#base_dept.remark}   %%��ǰ���ŵ�����
            ],
    {xmlelement, "item", Attrs, []}.

%%%----------------------------------------------------------------------
%%% ������֯����
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
