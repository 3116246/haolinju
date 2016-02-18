%%====================================================================
%% im服务端服务发布模块
%% 这些service由C/S通信实现的S(服务器)端调用
%%====================================================================

-module(im_service).
-author('liling@lli2').

-behaviour(gen_server).

%% API
-export([start/0, stop/0,start_link/0]).
%%一些扩展服务
-export([getdeptbyid/1,getempdept/1,getempdept/2,getempbyid/1,getempbydeptid/1,getallempbydeptid/1,getdeptbypid/2]).
%% gen_server的回调方法，同时作为基础服务进行发布
-export([init/1,handle_info/2, handle_call/3,handle_cast/2,terminate/2,code_change/3]).
-export([add_dept/4,
         batch_add_dept/1,
         add_emp/5,
         batch_add_emp/1,
         selectFriendGroup/1,
         addFriendGroup/1,
         deleteFriendGroup/1,  
         renameFriendGroup/1,       
         delete_dept/1,
         delete_emp/1,
         delete_emps/1,
         delete_empbydept/1,
         update_dept/4,
         update_emp/5,
         getempbydeptidA/1,
         getallempbydeptidA/1,
         getEmpRoleA/1,
         foundPass/1,
         changeManagerPass/1,
         changeemployeePass/1]).
%%导出所有方法
%%compile(export_all).

%%====================================================================
%% API
%%====================================================================
start() ->
    start_link().

stop() ->
    Pid = whereis(im),
    if Pid ==undefined ->gen_server:cast(?MODULE, stop);
    true->
       unregister(im),gen_server:cast(?MODULE, stop)
    end.
    

start_link() ->
    gen_server:start_link({local, ?MODULE}, ?MODULE, [], []).
    %%start_service().
    
%start_service()->  register(im,spawn(fun()->wait() end)).  
%
%wait()->
%       receive
%       {From,get}->
%         Re = readallemp(),
%         From!{Re},
%         wait();
%       {From,_}->
%        From!{"not!"}
%    end
%.
%
%rpc(N)->
%   im!{self(),N},
%        receive
%       {R}->
%         R
%    end
%   .
%%====================================================================
%% 1、gen_server callbacks
%% 2、对组织机构和人员进行初始化
%%====================================================================
init([]) ->
     io:format("init org service...~n"),
     im_organ_odbc:init(),
     io:format("org service init finished~n"),     
     {ok,[]}
.


%%====================================================================
%% 1、gen_server callbacks
%% 2、实现对组织机构的add\delete\update
%%====================================================================

%%添加新的组织 .其中deptID如果没有明确指定则自动生成。
%%参数说明：
%%   add_dept:固定标识符。表示当前操作为新增机构
%%   Deptid:新增机构的编号。当设置为""时，系统会自动生成一个长整数编号
%%   Deptname:新增机构的名称。
%%   Pid:新增机构的上级机构编号。
%%   OrderNo:新增机构的顺序号。默认为999.
%%   From:调用节点。一般为node().
%%   State:循环数据。一般设置为[].
%%返回说明：
%%  失败时，返回{reply,{error,Pid_R},[])结构
%%  成功时，返回{reply,{atomic,ok},[]}结构
%%=======================================================  

%%接口定义及调用
add_dept(Deptid, Deptname,Pid,No_order)-> gen_server:call(?MODULE,{add_dept,Deptid, Deptname,Pid,No_order}).
batch_add_dept(Deptlst)->gen_server:call(?MODULE,{batch_add_dept,Deptlst}).
add_emp(Empid,Empname,Deptid,Loginac,Pwd)->gen_server:call(?MODULE,{add_emp,Empid,Empname,Deptid,Loginac,Pwd}).    
batch_add_emp(Emplst)->gen_server:call(?MODULE,{batch_add_emp,Emplst}).
 
delete_dept(Deptid)->gen_server:call(?MODULE,{delete_dept, Deptid}).  
delete_emp(Empid)->gen_server:call(?MODULE,{delete_emp, Empid}). 
delete_empbydept(Deptid)->gen_server:call(?MODULE,{delete_empbydept, Deptid}). 

update_dept(Deptid,Deptname,Pid,OrderNo)->gen_server:call(?MODULE,{update_dept, Deptid,Deptname,Pid,OrderNo}).    
    
update_emp(Employeeid,Employeename,DeptID,LoginName,Password)->
    gen_server:call(?MODULE,{update_emp, Employeeid,Employeename,DeptID,LoginName,Password}).     
    
 
getdeptbyid(Pd)->
   gen_server:call(?MODULE,{getdeptbyid,Pd}).

getdeptbypid(child,Pid)-> gen_server:call(?MODULE,{getdeptbypid,child,Pid});   
getdeptbypid(all,Pid)-> gen_server:call(?MODULE,{getdeptbypid,all,Pid}). 
getempbydeptid(Pd)-> gen_server:call(?MODULE,{getuserbydeptid,Pd}). 
getallempbydeptid(Deptid)->gen_server:call(?MODULE,{getuserbydeptid,all,Deptid}).
getempbyid(Pd)->gen_server:call(?MODULE,{getuserbyid,Pd}). 
getempdept(Empid)->gen_server:call(?MODULE,{getdept,Empid}).
getempdept(all,Empid)->gen_server:call(?MODULE,{getdept,all,Empid}).       

selectFriendGroup(A)->
    Account = request:getparameter(A,"uid"), 
    F=gen_server:call(?MODULE,{sFG,Account}),
    request:returndata(F).  
deleteFriendGroup(A)->
    Account = request:getparameter(A,"uid"),
    GroupName = request:getparameter(A,"g","utf-8"),
    F=gen_server:call(?MODULE,{dFG,Account,GroupName}),
    request:returndata([F]).  
addFriendGroup(A)->
   Account = request:getparameter(A,"uid"),
   GroupName = request:getparameter(A,"g","utf-8"),
   F=gen_server:call(?MODULE,{aFG,Account,GroupName}),
   request:returndata([F]). 
renameFriendGroup(A)->
   Account = request:getparameter(A,"uid"),
   OldGroupName = request:getparameter(A,"og","utf-8"),
   NewGroupName = request:getparameter(A,"ng","utf-8"),
   F=gen_server:call(?MODULE,{rnFG,Account,OldGroupName,NewGroupName}),
   request:returndata([F]).    
foundPass(A)->
    %%获取加密码后的帐号
    Account = request:des_dec(request:getparameter(A,"p")),
    case Account of []->request:returndata([{error,"RE001"}]);
    _->
        F=gen_server:call(?MODULE,{foundyoupass,Account}),
        request:returndata([F])
    end
.
%%修改企业管理密码
changeManagerPass(A)->
    Uid = request:getparameter(A,"uid"),
    Old = request:getparameter(A,"old"),
    New = request:getparameter(A,"new"),
    F=gen_server:call(?MODULE,{changemanagerpass,Uid,Old,New}),
    request:returndata([F])    
.
changeemployeePass(A)->
    Uid = request:getparameter(A,"jid"),
    New = request:getparameter(A,"new"),
    F=gen_server:call(?MODULE,{changeemployeePass,Uid,New}),
    request:returndata([F])    
.
getempbydeptidA(A)->
   Pd = request:getparameter(A,"deptid"),
   F=gen_server:call(?MODULE,{getuserbydeptid,Pd}),
   request:returndata(F)
.  
getallempbydeptidA(A)->
   Pd = request:getparameter(A,"deptid"),
   F=gen_server:call(?MODULE,{getuserbydeptid,all,Pd}),
   request:returndata(F,["data_flag","employeeid","deptid","account","pwd","name","vcard"])
.
delete_emps(A)->Empids=request:getparameter(A,"ids"),
     gen_server:call(?MODULE,{delete_emps, Empids}),
     request:return("ok").
     
getEmpRoleA(A)->
 Empid = request:getparameter(A,"empid"),
 F = im_employee_odbc:getRole(Empid),
 case F of {error,R} -> request:returnerror(R);
 _->
     request:returndata(F)
 end
.     
%%接口实现
handle_call({sFG,Account}, _From, State) ->
    {reply,im_employee_odbc:selectFriendGroup(Account), State};
handle_call({aFG,Account,Gname}, _From, State) ->
    {reply,im_employee_odbc:addFriendGroup(Account,Gname), State};
handle_call({dFG,Account,Gname}, _From, State) ->
    {reply,im_employee_odbc:deleteFriendGroup(Account,Gname), State};
handle_call({rnFG,Account,OldGroupName,NewGroupName}, _From, State) ->
    {reply,im_employee_odbc:renameFriendGroup(Account,OldGroupName,NewGroupName), State};
    
handle_call({changemanagerpass,Uid,Old,New}, _From, State) ->
    {reply,im_employee_odbc:changemanagerpass(Uid,Old,New), State};
handle_call({changemployeepass,Uid,New}, _From, State) ->
    {reply,im_employee_odbc:changepassbyaccount(Uid,New), State};
%%重置帐号的密码
handle_call({foundyoupass,Acc}, _From, State) ->  
     Rs = im_employee_odbc:getuserbyaccount(Acc),
     case Rs of []-> Reply = {error,"RC002"}; %%notfound
     _->
        Reply=im_employee_odbc:resetrandompass(element(2,hd(Rs)))
     end,
    {reply,Reply, State};
handle_call({add_dept,Deptid, Deptname,Pid,No_order}, _From, State) ->
    case Deptid of  %%判断是否指定了机构ID属性
      ""->
         Reply=im_organ_odbc:add("",Deptname,Pid,No_order);
      _->
         Reply=im_organ_odbc:add(Deptid,Deptname,Pid,No_order)
    end,
    {reply,Reply, State};
    
%%-----------------------------------
%%批量添加组织机构   
%%DeptLst为列表，列表项的内容及顺序为：Deptid，Deptname，Pid，OrderNo 

handle_call({batch_add_dept,Deptlst}, _From, State) ->
    if is_list(Deptlst)== false ->{reply,{error,"参数类型不正确:不是list类型"}, State};
    true->
      %%检查是否是字符串类型的list
      if is_list(hd(Deptlst))== false -> {reply,{error,"参数类型不正确:不是list类型"}, State};
      true ->
		    Reply=im_organ_odbc:executebatch(Deptlst),
		    {reply,Reply, State}
	    end
    end;       

handle_call({add_emp,Empid,Empname,Deptid,Loginac,Pwd}, _From, State) ->
    case Empid of  %%判断是否指定了Empid属性
      ""->
         Reply=im_employee_odbc:add(null,Empname,Deptid,Loginac,Pwd);
      _->
         Reply=im_employee_odbc:add(Empid,Empname,Deptid,Loginac,Pwd)
    end,
    {reply,Reply, State}; 
    
%%-----------------------------------
%%批量添加员工信息   
%%EmpLst为列表，列表项的内容及顺序为：Empid,Employeename,DeptID,LoginName,Password 

handle_call({batch_add_emp,Emplst}, _From, State) ->
    if is_list(Emplst)== false ->{reply,{error,"参数类型不正确:不是list类型"}, State};
    true->
      %%检查是否是字符串类型的list
      if is_list(hd(Emplst))==false ->{reply,{error,"参数类型不正确:不是list类型"}, State};
      true->
		    Reply=im_employee_odbc:executebatch(Emplst),
		    {reply,Reply, State}
	    end
    end;  

  
%%=======================================================      
%%删除指定deptID的组织数据
%%参数说明：
%%    delete_dept:固定标识符。表示当前操作为删除指定的机构
%%    Deptid:将删除的机构的编号
%%    _From:调用节点。一般为node().
%%    State:循环数据。一般设置为[].
%%返回说明：
%%  失败时，返回{reply,{error,Pid_R},[]}结构
%%  成功时，返回{reply,{atomic,ok},[]}结构    
%%=======================================================
 
handle_call({delete_dept, Deptid}, _From, State) ->
    Reply=im_organ_odbc:delete(Deptid),
    {reply,Reply, State}; 
   
handle_call({delete_emp, Empid}, _From, State) ->
    Reply=im_employee_odbc:delete(employeeid,Empid),
    {reply,Reply, State};
    
handle_call({delete_emps, Empids}, _From, State) ->
    {_,Ids} = re:split(Empids,","),
    Reply=[im_employee_odbc:delete(employeeid,Empid)||Empid<-Ids],
    {reply,Reply, State};    
   
handle_call({delete_empbydept, Deptid}, _From, State) ->
    Reply=im_employee_odbc:delete(deptid,Deptid),
    {reply,Reply, State};
               
%%=======================================================    
%%修改指定机构的信息 。
%%参数说明：
%%   update_dept:固定标识符。表示当前操作为更新指定的机构
%%   Deptid:更新的机构编号。
%%   Deptname:更新机构的名称。
%%   Pid:更新机构的上级机构编号。
%%   OrderNo:更新机构的顺序号。
%%   _From:调用节点。一般为node().
%%   State:循环数据。一般设置为[].
%%返回说明：
%%  失败时，返回{reply,{error,Pid_R},State}结构
%%  成功时，返回{reply,{atomic,ok},State)结构。State为循环数据
%%============================================================   

handle_call({update_dept,Deptid,Deptname,Pid,OrderNo}, _From, State) ->
    Reply=im_organ_odbc:update(Deptid,Deptname,Pid,OrderNo),
    {reply,Reply, State};
     
handle_call({update_emp,Employeeid,Employeename,DeptID,LoginName,Password}, _From, State) ->
    Reply=im_employee_odbc:update(Employeeid,Employeename,DeptID,LoginName,Password),
    {reply,Reply, State};      
    
  
%%===================================================================
%%  获取指定的机构信息
%%  参数说明：
%%     Pd:机构ID编号
%%  返回结果说明：
%%       []|[{#base_dept}]
%%===================================================================   
 
handle_call({getdeptbyid,Pd}, _From, State) ->
    Reply=im_organ_odbc:getdeptbyid(Pd),
    {reply,Reply, State};   
%%===================================================================   
%%  根据获取指定的上级机构编号获取下级子机构
%%  child：只获取直接子机构
%%  all  : 获取所有下级机构


%%===================================================================
%%  获取指定的机构的下级机构信息
%%  参数说明：
%%     child:标识符。表示当前只获取指定机构的直接下级机构
%%     Pid:机构ID编号
%%  返回结果说明：
%%       []|[{#base_dept},{...}] 
%%=================================================================== 

handle_call({getdeptbypid,child,Pid}, _From, State) ->
    Reply=im_organ_odbc:getdeptbypid(Pid),
    {reply,Reply, State};
%%===================================================================
%%  获取指定的机构的下级机构信息
%%  参数说明：
%%     all:标识符。表示当前获取指定机构的所有下级机构
%%     Pid:机构ID编号
%%  返回结果说明：
%%       []|[{#base_dept},{...}]
%%===================================================================   

handle_call({getdeptbypid,all,Pid}, _From, State) ->
    Reply=im_organ_odbc:getdeptbypid(all,Pid),
    {reply,Reply, State};
%%===================================================================
%%  获取指定部门的所有人员信息
%%  参数说明：
%%     Empid:人员编号
%%  返回结果说明：
%%       []|[{#employee},{...}]
%%===================================================================   
 
handle_call({getuserbydeptid,Pd}, _From, State) ->
    Reply=im_employee_odbc:getuserbydeptid(Pd),
    {reply,Reply, State};
    
handle_call({getuserbydeptid,all,Pd}, _From, State) ->
    Reply=im_employee_odbc:getuserbydeptid(all,Pd),
    {reply,Reply, State};    

%%===================================================================
%%  获取指定人员的信息
%%  参数说明：
%%     Empid:人员编号
%%  返回结果说明：
%%       []|[{#employee}]
%%===================================================================   
 
handle_call({getuserbyid,Pd}, _From, State) ->
    Reply=im_employee_odbc:getuserbyid(Pd),
    {reply,Reply, State};   
%%===================================================================
%%  获取指定人员的当前机构信息
%%  参数说明：
%%     Empid:人员编号
%%  返回结果说明：
%%       []|[{#base_dept}]
%%===================================================================    
  
handle_call({getdept,Empid}, _From, State) ->
    Reply=im_employee_odbc:getdept(Empid),
    {reply,Reply, State};    
   
%%===================================================================
%%  获取指定人员的当前机构部门及所有下级机构部门信息
%%  参数说明：
%%     all  :固定标识符。表示当前获取所有机构部门信息
%%     Empid:人员编号
%%  返回结果说明：
%%       []|[{#base_dept},{...}]
%%===================================================================   
  
handle_call({getdept,all,Empid}, _From, State) ->
    Reply=im_employee_odbc:getdept(all,Empid),
    {reply,Reply, State}. 
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%55
handle_cast(stop, Data) ->
    Pid = whereis(im),
    if Pid==undefined ->{stop,normal,Data};
    true->unregister(im),{stop,normal,Data}
    end.  
    
terminate(_Reason, _LoopData) ->    
    ok.      
    
handle_info(_Msg,_ParaData) ->
   {noreply,null}.   
    
code_change(_OldVsn, State, _Extra) ->
    {ok, State}. 
    