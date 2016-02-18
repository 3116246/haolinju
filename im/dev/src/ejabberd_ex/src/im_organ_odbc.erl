-module(im_organ_odbc).
-author('liling@lli2').

-include("../include/mod_ejabberdex_init.hrl").
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").

-export([init/0,
         inittemplate/1,
         readall_stat/1,
         getdeptbyid/1,
         getdeptbyid_stat/1,
         getdeptbypid/1,
         getdeptbypid_stat/1,
         getdeptbypid/2,
         getdeptbypid_stat/2,
         executebatch/1,
         executebatch/2,
         add/4,
         add/6,
         delete/1,
         update/4,
         update/6,
         deleteEno/2,
         loadTemplate/2]).

   
init() ->
     io:format("---->org init end!~n")
     .
%%根据模板初始化机构     
inittemplate(A)->
   LOrg = request:getparameter(A,"orgid","utf-8"),
   Template = request:getparameter(A,"template","utf-8"),
   case LOrg of 
       []-> request:returnerror("1");
       "null"-> request:returnerror("1");
       _->
           Org =case LOrg of [$v|_] -> LOrg;_-> "v"++LOrg end,
           VirOrg=Org++"999",
           
           %%如果有将非虚拟部门下人员全部移到根下面
           Emps= im_employee_odbc:getuserbydeptid2(all,Org),
           if Emps=/=[]->
               [im_employee_odbc:update(Re#employee.employeeid,
                                Re#employee.employeename,Org,
                                Re#employee.loginname,
                                Re#employee.password)||Re<-lists:filter(fun(Rs)->(Rs#employee.deptid=/=VirOrg)and(Rs#employee.deptid=/=Org) end,Emps)];
           true->
               skip
           end,
           
           %%删除已有部门
           Depts=getdeptbypid(all,Org),
           if Depts=/=[]->
             %%过滤出虚拟部门及自己
             Depts2 = lists:filter(fun(D)-> (D#base_dept.deptid=/=Org)and(D#base_dept.deptid=/= VirOrg) end,Depts),
             
             [ejabberdex_odbc_query:del_dept(Ds#base_dept.deptid)||Ds<-Depts2];
           true->
               skip
           end,
           
           case Template of "none"-> request:return();
           "null"-> request:return();
           _->
	           loadTemplate(Org,Template),
	           request:return()
           end
    end
.     
loadTemplate(Org,Template)->
   Lst=lists:filter(fun(E)->case E#syscode.code of {Template,_}-> true; _-> false end end,ejabberdex_odbc_query:get_syscode_by_type("template_org")),
	 [add("",R#syscode.desc,Org,1)||R<-Lst]
.

deleteEno(Eno,Eno2)when Eno==Eno2 ->
  {ok}
;
deleteEno(Eno1,Eno2)when Eno1=/=Eno2 ->
    Eno =integer_to_list(Eno1),
   Rs =  ejabberdex_odbc_query:get_enterprise_by_eno(Eno),
   case Rs of []->io:format("=====jump eno:~p~n",[Eno]), deleteEno(Eno1+1,Eno2);
   _->
      ejabberdex_odbc_query:del_enterprise_reg(Eno),
      Depts = getdeptbypid(all,"v"++Eno),
      [im_employee_odbc:delete(deptid,D#base_dept.deptid)||D<-Depts],
      im_employee_odbc:delete(deptid,"v"++Eno),
      [delete(D1#base_dept.deptid)||D1<-Depts],
      delete("v"++Eno),
      deleteEno(Eno1+1,Eno2)  
   end
.


%%获取指定人员所在企业的所的部门
readall_stat(UserAccount)->
    %%获取人员的部门
    DeptId = im_employee_odbc:getdeptid(UserAccount), 
    if DeptId=:= []->[];
    true->
        case func_utils:jid(UserAccount) of
             []-> [];
             {_,Server}->
                    %%io:format("==========Server:~p~n",[Server]),
                    Lst=string:tokens(Server,".")--string:tokens(func_utils:getlocalserver(),"."),
                    LocalServ = func_utils:getlocalserver(),
                    Pid=if Lst=:=[];Server==LocalServ -> func_utils:getOrgRootID(UserAccount);
                           true->      "v"++hd(Lst)
                    end,
                    getdeptbypid_stat(all,Pid)
        end
    end
.        
   
%%获取指定的机构的信息  
%%参数说明：
%%  Deptid:机构ID
%%返回说明：
%%  []|[{#base_dept}]
getdeptbyid(Deptid)->  ejabberdex_odbc_query:get_dept_by_id(Deptid). 
getdeptbyid_stat(Deptid)->   ejabberdex_odbc_query:get_deptandstat_by_id(Deptid).   

%%获取指定的机构的直接下级机构信息  
%%参数说明：
%%  Pid:机构ID
%%返回说明：
%%  []|[{#base_dept},{...}]
getdeptbypid(Pid)->  ejabberdex_odbc_query:get_dept_by_parentid(Pid).
getdeptbypid_stat(Pid)-> ejabberdex_odbc_query:get_deptandstat_by_parentid(Pid).

%%循环获取指定编号的所有下级机构  
%%参数说明：
%%  all:固定标识符.表示获取指定机构下的所有子机构
%%  Pid:机构ID
%%返回说明：
%%  []|[{#base_dept},{...}]
getdeptbypid(all,Pid)-> ejabberdex_odbc_query:get_all_dept_by_parentid(Pid).
getdeptbypid_stat(all,Pid)->
    ejabberdex_odbc_query:get_all_deptandstat_by_parentid(Pid).
  
%%添加新的组织 .其中deptID如果没有明确指定则自动生成。
%%参数说明：
%%   Deptid:新增机构的编号。当设置为""时，系统会自动生成一个长整数编号
%%   Deptname:新增机构的名称。
%%   Pid:新增机构的上级机构编号。
%%   OrderNo:新增机构的顺序号。默认为999.
%%返回说明：
%%  失败时，返回{error,Pid_R}结构
%%  成功时，返回{atomic,ok}结构
add(Deptid,Deptname,Pid,OrderNo)-> 
  add(Deptid,Deptname,Pid,OrderNo,"","")
.
add(Deptid,Deptname,Pid,_OrderNo1,Manager,Remark)->
    Did = case Deptid of
            []->
              ejabberdex_odbc_query:get_seq_nextvalue("im_base_dept","deptid");
            _->
              Deptid
           end,
    %%获取指定的pid节点的信息，根据信息生成当前节点的path属性       
    R=ejabberdex_odbc_query:get_dept_by_id(Pid),
    if R==[] -> Path=    Deptid;
          true ->  
               Basedeptvar = hd(R),
               Parentnodepath = Basedeptvar#base_dept.path,
               case Parentnodepath of
                  undefined->
                       Path=    Basedeptvar#base_dept.deptid;
                  _->  Path=    Basedeptvar#base_dept.path++"/"++Basedeptvar#base_dept.deptid
               end                  
    end,
    Childs = getdeptbypid_stat(Pid),
    OrderNo = case Childs of []-> 1; _-> length(Childs)+1  end,        
    ejabberdex_odbc_query:ins_dept(Did,Deptname,Pid,Path,integer_to_list(OrderNo),Manager,Remark)
.    


%%批量添加多个的新的组织。添加新数据前会删除Pid对应的记录，所以该方法也可用于修改数据
%%参数说明：
%%   Lst：再一次性添加的组织机构数据列表［］。列表的数据项顺序及内容如下：
%%        Deptid:新增机构的编号。当设置为""时，系统会自动生成一个长整数编号
%%        Deptname:新增机构的名称。
%%        Pid:新增机构的上级机构编号。
%%        OrderNo:新增机构的顺序号。默认为"999".
%%返回说明：
%%  失败时，返回{error,Pid_R}结构
%%  成功时，返回{atomic,recordCounter,[..]}结构，
%%              recordCounter为成功的记录总数
%%              []为未成功的数据
executebatch(Lst) ->
   {Flag,Result} = mnesia:transaction(fun()->  executebatch(Lst,0,[]) end),
   case Flag of 
     atomic-> Result;
          _-> {Flag,Result}
   end. 
   
%%在批量添加前不清空原有数据。executebatch/1 会自动清除原有数据
executebatch(Lst,noclear) ->
   {Flag,Result} = mnesia:transaction(fun()-> executebatch(Lst,0,[]) end),
   case Flag of 
     atomic-> 
           Result;
          _-> {Flag,Result}
   end. 
   
executebatch(Lst,Recordcounter,OkLst)->
   if Lst==[] -> {atomic,OkLst};
   true       ->
        try
           [Aname,Apid,AOrderno] = hd(Lst),
           add("",Aname,Apid,AOrderno),	    
		       executebatch(tl(Lst),Recordcounter+1,["1"|OkLst])
		    catch
		       Ec:Ex->?ERROR_MSG("~p:~p", [Ec, Ex]),
		       executebatch(tl(Lst),Recordcounter,[[]|OkLst])
		    end
   end
.

%%删除指定deptID的组织数据
%%参数说明：
%%    Deptid:将删除的机构的编号
%%返回说明：
%%  失败时，返回{error,Pid_R}结构
%%  成功时，返回{atomic,ok}结构
delete(Deptid)-> 
        %%判断是否还有下级机构或员工，有则不能删除
        case {getdeptbypid(Deptid),im_employee_odbc:getuserbydeptid(Deptid)}
         of {[],[]}->
		        ejabberdex_odbc_query:del_dept(Deptid);
        _->
        		iconv:convert("gbk","utf-8","还有下级部门或员工,不能删除")
        end.  
  
%%修改指定机构的信息 。
%%参数说明：
%%   Deptid:更新的机构编号。
%%   Deptname:更新机构的名称。
%%   Pid:更新机构的上级机构编号。
%%   OrderNo:更新机构的顺序号。
%%返回说明：
%%  失败时，返回{error,Pid_R}结构
%%  成功时，返回{atomic,ok}结构 
update(Deptid,Deptname,Pid,OrderNo)-> 
    update(Deptid,Deptname,Pid,OrderNo,"","")
.    
update(Deptid,Deptname,Pid,OrderNo,Manager,Remark)-> 
    ejabberdex_odbc_query:del_dept(Deptid),
    add(Deptid,Deptname,Pid,OrderNo,Manager,Remark). 
     