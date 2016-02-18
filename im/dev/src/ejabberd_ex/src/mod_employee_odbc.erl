%%%----------------------------------------------------------------------
%%% 用户管理
%%%----------------------------------------------------------------------

%%% @doc 用户管理 (存储于odbc数据库中).
%%%
%%% 用户管理使用IQ节，并且子元素<query/>必须指定名空间为'jabber:iq:employee'， 
%%% 通过属性ver指定当前版本，如ver=''，当未指定版本或指定版本与当前服务器版本不一致时，服务器返回其用户；
%%% 通过属性deptid指定要返回用户所属部门，否则返回空列表；
%%% <query/>可以包含一个或多个子元素，每个子元素描述每个用户具体的信息。
%%%
%%% mod参数：{versioning, true|false} 是否允许使用版本，默认为true
%%%----------------------------------------------------------------------
-module(mod_employee_odbc).
-author('feihu929@sina.com').

-behaviour(gen_mod).

-export([start/2, stop/1,
         process_iq/3,
         process_local_iq/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../include/mod_employee.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, Opts) ->
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),    
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_EMPLOYEE,
                                  ?MODULE, process_iq, IQDisc).

%%%----------------------------------------------------------------------
stop(Host) ->
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_EMPLOYEE).

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

process_iq_get(From, To, #iq{sub_el = SubEl} = IQ) ->    
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of 
          "query" -> query1(From, To, IQ);
          "queryfriendgroup" -> queryfriendgroup(From, To, IQ);
          "queryemployeerole" -> queryemployeerole(From, To, IQ);
          "querypublicaccount"-> querypublicaccount(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%查询当前帐号是否公共帐号并返回数据    
querypublicaccount(From, _To, #iq{sub_el = SubEl} = IQ)->
    LUser = From#jid.luser,
    LServer = From#jid.lserver,
    US = LUser ++ "@" ++ LServer,
    Items=case xml:get_tag_attr("eno", SubEl) of 
          {value, XDeptID}->
              Stype=xml:get_tag_attr("stype", SubEl),
              im_employee_odbc:getPublicAccount(XDeptID,case Stype of {value,T}->T;_-> [] end);
           _-> im_employee_odbc:isPublicAccount(US)
    end,
    IQ#iq{type = result, sub_el = [{xmlelement, "query", [{"xmlns", ?NS_EMPLOYEE}], lists:map(fun item_to_xml/1,Items)}]}
.

%%%----------------------------------------------------------------------
%% 仅当下列情况才返回数据
%%     - mod参数 versioning 被设置为false 或
%%     - 客户端发来的请求中未设置ver属性 或
%%     - 服务器上没有存储当前版本 或
%%     - 当前版本与客户请求的版本不一致
%%     - 正确设置了deptid属性
query1(From, _To, #iq{sub_el = SubEl} = IQ) ->
    LUser = From#jid.luser,
    LServer = From#jid.lserver,
    US = LUser ++ "@" ++ LServer,
    try
            RequestedDeptID = case xml:get_tag_attr("deptid", SubEl) of  
                                {value, XDeptID} ->
                                  XDeptID;
                                _ ->
                                  "" 
                              end,
            RequestedScope = case xml:get_tag_attr("scope", SubEl) of  
                                {value, XScope} ->
                                  XScope;
                                _ ->
                                  "child" 
                              end,
	    {ItemsToSend, VersionToSend} = 
		case {xml:get_tag_attr("ver", SubEl),
		      employee_versioning_enabled(LServer)} of
		{{value, RequestedVersion}, true} ->
			%% 从数据库中取出当前版本
			case ejabberdex_odbc_query:get_employee_version(US) of
				[#employee_version{version = RequestedVersion}] ->
					{false, false};
				[#employee_version{version = NewVersion}] ->
				        {lists:map(fun item_to_xml/1,
				        	case RequestedScope of 
				        	   "all"->im_employee_odbc:getuserbydeptid2(all,RequestedDeptID);
				        	   _-> im_service:getempbydeptid(RequestedDeptID) 
				        	end),
				         NewVersion};
				[] ->
					EmployeeVersion = sha:sha(term_to_binary(now())),
					ejabberdex_odbc_query:set_employee_version(US, EmployeeVersion),
					{lists:map(fun item_to_xml/1, 
						     case RequestedScope of
						        "all"->im_employee_odbc:getuserbydeptid2(all,RequestedDeptID);
						        _-> im_service:getempbydeptid(RequestedDeptID)
						     end
						        ),EmployeeVersion}
			end;
		_ ->
		        {lists:map(fun item_to_xml/1,
		        	  case RequestedScope of
		        	     "all"->im_employee_odbc:getuserbydeptid2(all,RequestedDeptID);
		        	     _-> im_service:getempbydeptid(RequestedDeptID)
		        	     end), false}
		end,
		
		IQ#iq{type = result, sub_el = case {ItemsToSend, VersionToSend} of
					 	 {false, false} ->  [];
						 {Items, false} -> [{xmlelement, "query", [{"xmlns", ?NS_EMPLOYEE}], Items}];
						 {Items, Version} -> [{xmlelement, "query", [{"xmlns", ?NS_EMPLOYEE}, {"ver", Version}], Items}]
					      end}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% 取得是否允许使用版本的mod参数versioning，true|false
employee_versioning_enabled(Host) ->
    gen_mod:get_module_opt(Host, ?MODULE, versioning, true).

%%%----------------------------------------------------------------------
%%% 生成XML
item_to_xml(Item) ->
    Attrs = [
              {"employeeid", Item#employee.employeeid},
              {"deptid", Item#employee.deptid},
              {"loginname", Item#employee.loginname},
              {"employeename", Item#employee.employeename},
              {"photo",Item#employee.photo},
              {"spell",Item#employee.spell},
              {"p_desc",Item#employee.p_desc}
            ],
    {xmlelement, "item", Attrs, []}.

%%%----------------------------------------------------------------------
%%% 11.1	取得好友分组
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='employee_1'>
%%%   <queryfriendgroup xmlns='http://im.fafacn.com/namespace/employee'/>
%%% </iq>
%%% 返回值 IQ节
queryfriendgroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
    LUser = From#jid.luser,
    LServer = From#jid.lserver,
    US = LUser ++ "@" ++ LServer,
    try
      ALfriendgroups = im_employee_odbc:selectFriendGroup(US),
      Items = lists:map(fun friendgroupsitem_to_xml/1, ALfriendgroups),
      IQ#iq{type = result, sub_el = [{xmlelement, "queryfriendgroup", [{"xmlns", ?NS_EMPLOYEE}], Items}]}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.
%%%----------------------------------------------------------------------
%%% 生成好友分组XML
friendgroupsitem_to_xml(Item) ->
    Attrs = [
              {"loginname", Item#friendgroups.loginname},
              {"groupname", Item#friendgroups.groupname}
            ],
    {xmlelement, "friendgroupitem", Attrs, []}.

%%%----------------------------------------------------------------------
%%% 2.4	取得用户权限
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='employee_1'>
%%%   <queryemployeerole xmlns='http://im.fafacn.com/namespace/employee'/>
%%% </iq>
%%% 返回值 IQ节
queryemployeerole(From, _To, #iq{sub_el = SubEl} = IQ) ->
    LUser = From#jid.luser,
    LServer = From#jid.lserver,
    US = LUser ++ "@" ++ LServer,
    try
      ALemployeerole = im_employee_odbc:getRoleByAccount(US),
      Items = lists:map(fun employeeroleitem_to_xml/1, ALemployeerole),
      IQ#iq{type = result, sub_el = [{xmlelement, "queryemployeerole", [{"xmlns", ?NS_EMPLOYEE}], Items}]}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.
%%%----------------------------------------------------------------------
%%% 生成分组XML
employeeroleitem_to_xml(Item) ->
    Attrs = [
              {"employeeid", Item#employeerole.employeeid},
              {"roleid", Item#employeerole.roleid}
            ],
    {xmlelement, "employeeroleitem", Attrs, []}.

%%%----------------------------------------------------------------------
process_iq_set(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "query" -> query_set(From, To, IQ);
          "feedbackemployapply"->feedbackemployapply(From, To, IQ);
          "addfriendgroup" -> addfriendgroup(From, To, IQ);
          "delfriendgroup" -> delfriendgroup(From, To, IQ);
          "renamefriendgroup" -> renamefriendgroup(From, To, IQ);
          "headline" -> headline(From, To, IQ);          
          "fafawebfile" -> fafawebfile(From, To, IQ);
          "chatshift" -> chatshift(From, To, IQ);
          "chatshiftresult" -> chatshiftresult(From, To, IQ);
          "atten" -> atten(From, To, IQ); %关注联系人
          "unatten" -> unatten(From, To, IQ); %取消关注联系人
          "deleteatten" -> delatten(From, To, IQ); %删除联系人的关注
          "requestfriend" -> requestfriend(From, To, IQ); %请求添加好友
          "agreefriend" -> agreefriend(From, To, IQ);   %同意添加好友
          "rejectfriend" -> rejectfriend(From, To, IQ);  %拒绝好友请求
          "deletefriend" -> delfriend(From, To, IQ);  %删除好友
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

requestfriend(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      Desc=xml:get_tag_attr_s("auth_desc", SubEl),  %获取认证描述
      Groupname=xml:get_tag_attr_s("groupname", SubEl),  %获取认证描述
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      Empinfo = ejabberdex_odbc_query:get_emp_by_account(FromJid),
      ejabberdex_odbc_query:add_roster_request(FromJid,ToJid,Groupname),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},
              							{"action", "request"},
              							{"desc",Desc},
              							{"nickname",Empinfo#employee.employeename},
              							{"photo",Empinfo#employee.photo}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

agreefriend(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      Groupname=xml:get_tag_attr_s("groupname", SubEl),  %获取认证描述
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      Rs_from = ejabberdex_odbc_query:get_roster_relation(ToJid,FromJid), %查看对方的好友请求记录是否还存在
      case Rs_from of
        []->
          IQ#iq{type =error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]};
        _->
        	Empinfo = ejabberdex_odbc_query:get_emp_by_account(FromJid),
          	ejabberdex_odbc_query:add_roster_agreen(FromJid,ToJid,Groupname),
          	Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
                  [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "agree"},
                  						{"nickname",Empinfo#employee.employeename},
              							{"photo",Empinfo#employee.photo}], 
                    []}]},
          	ejabberd_sm:route(From, To, Pres),
         	ejabberd_sm:route(To,From, Pres),
          	%%互相发送出席状态
          	%%判断To是否在线
          	case ejabberd_sm:get_user_resources(To#jid.luser, To#jid.lserver) of
          	[]-> skip;
          	Resources->
              ejabberd_sm:route(From, To,{xmlelement, "presence", [{"type", "available"}], []}),
              [ejabberd_sm:route(jlib:make_jid(To#jid.luser,To#jid.lserver,S), From,{xmlelement, "presence", [{"type", "available"}], []})||S<-Resources]
          	end,
          	IQ#iq{type = result, sub_el = []}
      end
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

rejectfriend(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      Empinfo = ejabberdex_odbc_query:get_emp_by_account(FromJid),
      ejabberdex_odbc_query:add_roster_delete(FromJid,ToJid),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "reject"},
              							{"nickname",Empinfo#employee.employeename},
              							{"photo",Empinfo#employee.photo}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      %PresTome = {xmlelement, "presence", [], 
      %        [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "delete"}], 
      %          []}]},
      %ejabberd_sm:route(To,From, PresTome),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

delfriend(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      ejabberdex_odbc_query:add_roster_delete(FromJid,ToJid),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "delete"}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.
%From attention To,client rev 'atten' action ,maybe do nothing
atten(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      ejabberdex_odbc_query:atten(FromJid,ToJid),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "atten"}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

unatten(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      ejabberdex_odbc_query:delatten(FromJid,ToJid),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "unatten"}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.
%delete To vs From attention
delatten(From, To, #iq{sub_el = SubEl} = IQ) ->
try
      FromJid = From#jid.luser ++ "@" ++ From#jid.lserver, 
      ToJid = To#jid.luser ++ "@" ++ To#jid.lserver, 
      ejabberdex_odbc_query:delatten(ToJid,FromJid),
      Pres = {xmlelement, "message", [{"id",func_utils:getNextID()}], 
              [{xmlelement, "friend", [{"xmlns", ?NS_EMPLOYEE},{"action", "deleteatten"}], 
                []}]},
      ejabberd_sm:route(From, To, Pres),
      IQ#iq{type = result, sub_el = []}
catch 
      Ec:Ex ->  
              ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
      IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.

chatshift(From, To, #iq{sub_el = SubEl} = IQ) ->
  Pres = {xmlelement, "presence", [], 
          [{xmlelement, "chatshift", [{"xmlns", ?NS_EMPLOYEE}], 
            [{xmlelement, "item", 
              [{"action", xml:get_tag_attr_s("action", SubEl)},
               {"chatto",xml:get_tag_attr_s("chatto", SubEl)}, 
               {"nickname",xml:get_tag_attr_s("nickname", SubEl)}],
              []}]}]},
  ejabberd_sm:route(From, To, Pres),
  IQ#iq{type = result, sub_el = []}
.
chatshiftresult(From, To, #iq{sub_el = SubEl} = IQ) ->
  Pres = {xmlelement, "presence", [], 
          [{xmlelement, "chatshiftresult", [{"xmlns", ?NS_EMPLOYEE}], 
            [{xmlelement, "item", 
              [{"result", xml:get_tag_attr_s("result", SubEl)},
               {"chatto",xml:get_tag_attr_s("chatto", SubEl)}, 
               {"nickname",xml:get_tag_attr_s("nickname", SubEl)}],
              []}]}]},
  ejabberd_sm:route(From, To, Pres),
  IQ#iq{type = result, sub_el = []}
.
    
fafawebfile(_From, _To, #iq{sub_el = _SubEl} = IQ) ->
  IQ#iq{type = result, sub_el = []}
.     
feedbackemployapply(_From, _To, #iq{sub_el = SubEl} = IQ) ->
   Accept    = xml:get_tag_attr_s("accept", SubEl),
   Eno    = xml:get_tag_attr_s("eno", SubEl),
   Email    = xml:get_tag_attr_s("email", SubEl),
   case Accept of
    "1"->					   
					   Domain = Eno++".fafacn.com",
					   Deptid    = xml:get_tag_attr_s("deptid", SubEl),
					   Loginname    = xml:get_tag_attr_s("loginname", SubEl),
					   Employeename    = xml:get_tag_attr_s("employeename", SubEl),					   
%					   Phone    = xml:get_tag_attr_s("phone", SubEl),
					   Mobile    = xml:get_tag_attr_s("mobile", SubEl),
						 Account=case func_utils:is_jid(Loginname) of
						            true->
						              case func_utils:jid(Loginname) of 
						                 {U,Eno}->U++"@"++Domain;
						                 {U,Domain}->U++"@"++Domain;
						                 _-> error
						              end;
						            _-> Loginname++"@"++Domain
						 end,
						 case Account of
						  error-> IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]};
						  _->
					       im_vcard:set("",Account,Deptid,Employeename,request:convertUTF8("男"),request:convertUTF8("员工"),Mobile,Email,Domain),
					       IQ#iq{type = result, sub_el=[]}
					   end ;
   _->
				      %%拒绝：发送邮件通知
	            Msg = "&nbsp;&nbsp;&nbsp;&nbsp;企业号为"++Eno++"的企业管理员拒绝了您的FaFa帐号申请，请完善您的申请信息或者直接联系该企业！"
	                      ++"<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(该邮件由系统自动发送，请不要回复，谢谢合作！)"
	                      ++"<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(提示：为了更好的显示本邮件内容，请将邮件阅读方式切换为HTML显示。)",
				      sendmail:fafa(Email,"FaFa帐户申请被拒绝",Msg),
				      IQ#iq{type = result, sub_el=[]}   
   end  
.
%%%----------------------------------------------------------------------
%%% 设置用户
query_set(_From, _To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, _Name, _Attrs, Items} = SubEl,
        FConverToArray = fun(Item) ->
          Empid    = xml:get_tag_attr_s("employeeid", Item),
          DeptID  = xml:get_tag_attr_s("deptid", Item),
          LoginName  = xml:get_tag_attr_s("loginname", Item),
          Password   = xml:get_tag_attr_s("password", Item),
          Employeename   = xml:get_tag_attr_s("employeename", Item),
          [Empid, Employeename, DeptID, LoginName, Password]
        end,
        FFilterErrData = fun([Empid, _Employeename, DeptID, LoginName, _Password]) ->
          Empid /= "" andalso DeptID /= "" andalso LoginName /= ""
        end,
        im_employee_odbc:executebatch(lists:filter(FFilterErrData, lists:map(FConverToArray, Items))),
        IQ#iq{type = result, sub_el=[]}
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% 11.2	增加好友分组
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='employee_1'>
%%%   <addfriendgroup xmlns='http://im.fafacn.com/namespace/employee' groupname='一组'/>
%%% </iq>
%%% 返回值 IQ节
addfriendgroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver, 
    Qgroupname = xml:get_tag_attr_s("groupname", SubEl),  
    im_employee_odbc:addFriendGroup(Operator, Qgroupname),
    IQ#iq{type = result, sub_el=[]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 11.3	删除好友分组
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='employee_1'>
%%%   <delfriendgroup xmlns='http://im.fafacn.com/namespace/employee' groupname='一组'/>
%%% </iq>
%%% 返回值 IQ节
delfriendgroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver, 
    Qgroupname = xml:get_tag_attr_s("groupname", SubEl),  
    im_employee_odbc:deleteFriendGroup(Operator, Qgroupname),
    IQ#iq{type = result, sub_el=[]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 11.4	重命名好友分组
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='employee_1'>
%%%   <renamefriendgroup xmlns='http://im.fafacn.com/namespace/employee' oldgroupname='一组' groupname='一组'/>
%%% </iq>
%%% 返回值 IQ节
renamefriendgroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver, 
    Qoldgroupname = xml:get_tag_attr_s("oldgroupname", SubEl), 
    Qgroupname = xml:get_tag_attr_s("groupname", SubEl),  
    im_employee_odbc:renameFriendGroup(Operator, Qoldgroupname, Qgroupname),
    IQ#iq{type = result, sub_el=[]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 10.2	广播通知
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <headline xmlns='http://im.fafacn.com/namespace/employee' sendfrom='abc@xx.xx'
%%% sendtoroster='11@xx.xx;12@xx.xx;13@xx.xx' sendtodept='111111;222222;333333' >
%%%     <text>this is a test.</text>
%%%   </headline>
%%% </iq>
%%% 返回值 IQ节
headline(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    %Operator = From#jid.luser ++ "@" ++ From#jid.lserver, 
    Qsendfrom = xml:get_tag_attr_s("sendfrom", SubEl), 
    Qsendtoroster = xml:get_tag_attr_s("sendtoroster", SubEl),
    Qsendtodept = xml:get_tag_attr_s("sendtodept", SubEl),  
    {xmlelement, _, _, QSendMsg} = SubEl,
    message_action_odbc:broadcast_once(Qsendfrom, QSendMsg, Qsendtoroster, Qsendtodept),
    
    IQ#iq{type = result, sub_el=[]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  				
%%%----------------------------------------------------------------------
    