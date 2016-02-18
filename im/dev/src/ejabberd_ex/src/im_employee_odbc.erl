-module(im_employee_odbc).
-author('liling@lli2').


-include("../include/mod_ejabberdex_init.hrl").
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").

-export([
         getuserbyid/1,
         getuserbyaccount/1,
         getuserbydeptid/1,
         getuserbydeptid/2,
         getuserbydeptid2/2,
         executebatch/1,
         executebatch/2,
         add/6,add/5,
         delete/2,
         update/5,
         getdept/1,
         getdeptid/1,
         getdept/2,
%         getfriend/1,
         getRole/1,
         getRoleByAccount/1,
         saveRole/2,
         resetpass/1,
         resetrandompass/1,
         resetrandompass_ac/1,
         changepass/2,
         changepassbyaccount/2,
         changemanagerpass/3,
         selectFriendGroup/1,
         addFriendGroup/2,
         deleteFriendGroup/2,
         renameFriendGroup/3,
         getPublicAccount/2,
         isPublicAccount/1]).

%%获取企业的指定类别公共帐号
getPublicAccount(Eno,Type)->
  DeptId="v"++Eno++"999",
  Rs = getuserbydeptid(DeptId),
  case Type of
     [] -> Rs;
     _-> lists:filter(fun(Item)-> string:str(Item#employee.employeeid,Type)>0 end,Rs)
  end
.

isPublicAccount(Acc)->
   Rs = ejabberdex_odbc_query:get_emp_by_account(Acc),
   case Rs of
     []-> [];
     _->
       lists:filter(fun(Item)->Eid =Item#employee.employeeid, (string:str(Eid,"service")>0)orelse(string:str(Eid,"sale")>0)orelse(string:str(Eid,"front")>0)orelse(string:str(Eid,"admin")>0)  end,Rs)
   end
.


%%查询好友组         
selectFriendGroup(EmpAccount)->
   ejabberdex_odbc_query:get_friend_group(EmpAccount)
.
%%添加好友分组
addFriendGroup(EmpAccount,GroupName)->
    case GroupName of []->[];
    _->
		        {Bear,Regserver}=func_utils:jid(EmpAccount),
		        Var=true,%ejabberd_auth_odbc:is_user_exists(Bear,Regserver),
		        case Var of
		           true->
		               Acc = Bear++"@"++Regserver,
		               %%F=#friendgroups{empg={Acc,request:convertUTF8(GroupName)},loginname=Acc,groupname=request:convertUTF8(GroupName)},
                   ejabberdex_odbc_query:ins_friend_group(Acc,GroupName),
                   if Bear=:="admin" ->                     
                      Emps=ejabberdex_odbc_query:get_emp_by_server(Regserver),
                      [ejabberdex_odbc_query:ins_friend_group(Us#employee.loginname,GroupName)||Us<-Emps ],
                      ok;
                   true->
                      ok
                   end;
		           _->[]              
		        end
    end
.
%%删除好友分组。删除 时不处理该组中的好友，在确认删除组之前，应用程序应已处理好该的内的所有好友
deleteFriendGroup(EmpAccount,GroupName)->
    case GroupName of []->[];
    _->
		        {Bear,Regserver}=func_utils:jid(EmpAccount),
		        Var=true,%%ejabberd_auth_odbc:is_user_exists(Bear,Regserver),
		        case Var of
		           true->
		               ejabberdex_odbc_query:del_friend_group(EmpAccount,GroupName),
		               if Bear=:="admin" ->                    
                      Emps=ejabberdex_odbc_query:get_emp_by_server(Regserver),
                      [ejabberdex_odbc_query:del_friend_group(Us#employee.loginname,GroupName)||Us<-Emps ],
                      ok;
		               true->
		                  skip
		               end;
		           _->[]
		        end
    end
.
%%重命名分组名称
renameFriendGroup(EmpAccount,OldGroupName,NewGroupName)->    
        ejabberdex_odbc_query:update_friend_group(EmpAccount,OldGroupName,NewGroupName)
.

%%重置人员密码为默认密码         
resetpass(Empid)->
      changepass(Empid,?DEFAULT_PASSWORD)
.
%%重置密码为随机密码
resetrandompass(Empid)->
      Random=integer_to_list(func_utils:getNumber(10000000,99999999)),
      changepass(Empid,Random)
.
%%根据帐号重置密码为随机密码
resetrandompass_ac(Account)->
      resetrandompass(Account)
.
%%修改密码
changepass(Empid,Pass)->
      EmpRs = ejabberdex_odbc_query:get_emp_by_id(Empid),
      if EmpRs==[] -> {error,"employee not found"};
      true->
		      LoginName = (hd(EmpRs))#employee.loginname,
		      {Bare,Regserver}=func_utils:jid(LoginName),
		      ejabberd_auth_odbc:set_password(Bare,Regserver,Pass),
		      ejabberdex_odbc_query:update_emp_pass(Empid,Pass),
		      {ok,Pass}
      end       
.
%%修改密码
changepassbyaccount(Account,Pass)->
          changepass(Account,Pass)
.
changemanagerpass(Uid,Old,New)->
     if (Uid==[])or(Old==[])or(new==[])->
         Reply={error,"isnull"};
      true->
         %%判断Uid是否有效
		    {User,Server}=func_utils:jid(Uid),
		    LocalServ = func_utils:getlocalserver(),    
		    Rs=empFilter(case Server of LocalServ->  
		                            TempRs=ejabberdex_odbc_query:get_emp_by_account(User),
		                            case TempRs of []-> ejabberdex_odbc_query:get_emp_by_account( User++"@"++Server);
		                                _-> TempRs
		                            end;
		                      _->  ejabberdex_odbc_query:get_emp_by_account( Uid)
		     end
		    ),
    		 if Rs =:= [] ->
				       %%尝试使用企业注册号
				        Hsh = case func_utils:is_jid(Uid) of 
				                               true->[];
				                               _->
				                                  EnterRs =  ejabberdex_odbc_query:get_enterprise_by_eno(Uid),
				                                  case EnterRs of
				                                       []->[];
				                                       _->getuserbyaccount((hd(EnterRs))#enterprise_reg.admin)
				                                  end
				       end;
			   true      ->
			                  Hsh = Rs
			   end,
         case Hsh of  []-> Reply={error,"notfound"};
         _->
             EmpRec = hd(Hsh),
             Pass = EmpRec#employee.password,             
             Reply=case Pass of Old->
                        changepass(EmpRec#employee.employeeid,New);
                   _-> {error,"password"}
             end
         end
    end,
    Reply
.
%%%获取指定人员的好友列表
%getfriend(_Empid)->
%   readall()
%.
getRoleByAccount(Account)->
    %%判断帐号是否有服务器信息
    ejabberdex_odbc_query:get_role(Account)
.
getRole(Empid)->
    getRoleByAccount(Empid).
saveRole(Empid,Roleid)->
    EmpRs = getuserbyaccount(Empid),
    if EmpRs==[] -> {error,"not found"};
    true->
      LoginName = (hd(EmpRs))#employee.loginname,
	    Rs= ejabberdex_odbc_query:ins_role(LoginName,Roleid),
	    Rs
    end.    
%%批量添加多个的新的组织。添加新数据前会删除Pid对应的记录，所以该方法也可用于修改数据
%%参数说明：
%%   Lst：再一次性添加的组织机构数据列表［］。列表的数据项顺序及内容如下：
%%  Empid:新增的人员的编号。可以为null标识,表示为空,没有传值,系统会自动产生一个数字编号
%%  Employeename:新增的员的名称。可理解为真实名称
%%  DeptID:人员所属的机构部门编号 
%%  LoginName:人员的登录帐号
%%  Password:人员的登录密码
%%返回说明：
%%  失败时，返回{error,Pid_R}结构
%%  成功时，返回{atomic,recordCounter,[..]}结构，
%%              recordCounter为成功的记录总数
%%              []为未成功的数据
executebatch(Lst) ->
   {Flag,Result} = mnesia:transaction(fun()-> mnesia:clear_table(employee), executebatch(Lst,0,[]) end),
   case Flag of 
     atomic-> Result;
          _-> {Flag,Result}
   end. 
   
executebatch(Lst,noclear) ->
   {Flag,Result} = mnesia:transaction(fun()-> executebatch(Lst,0,[]) end),
   case Flag of 
     atomic-> Result;
          _-> {Flag,Result}
   end. 
executebatch(Lst,Recordcounter,FailLst)->
   if Lst==[] -> {atomic,Recordcounter,FailLst};
   true       ->
      try
            [C_empid,C_deptid,C_empname,C_loginname,C_pwd]= hd(Lst),      
		        %%新增记录
		        ejabberdex_odbc_query:ins_emp(C_empid,C_loginname,C_empname,C_deptid,C_loginname,C_pwd),
		        stat_dept(C_deptid,1),%%更新部门的员工总数统计表
		        %%判断是否设置 有密码
		        {Bear,Regserver}=func_utils:jid(C_loginname),
		        Var=ejabberd_auth_odbc:is_user_exists(Bear,Regserver),
		        case Var of
		           true->ejabberd_auth_odbc:set_password(Bear,Regserver,C_pwd);
		           _->ejabberd_auth_odbc:try_register(Bear,Regserver,C_pwd)	              
		        end,
		        executebatch(tl(Lst),Recordcounter+1,FailLst)
		    catch
		       Ec:Ex->?ERROR_MSG("~p:~p", [Ec, Ex]),
		       executebatch(tl(Lst),Recordcounter,[hd(Lst)|FailLst])
		    end		        
   end
.        

stat_dept(Did,Cnt)->
  Rs = ejabberdex_odbc_query:get_dept_empstat_count(Did),
  case Rs of []->
    if Cnt>0->
		    ejabberdex_odbc_query:save_dept_emp_count(Did,Cnt);
    true->
      skip
    end;
  _->
	    ejabberdex_odbc_query:save_dept_emp_count(Did,(hd(Rs))#dept_stat.empcount+Cnt)
  end,
		    Dept = im_organ_odbc:getdeptbyid(Did),
		    case Dept of []->
		      ok;
		    _->
		        Pid = (hd(Dept))#base_dept.pid,
		        case Pid of "-1" -> ok;
		            "-10000" -> ok;
		            _->
		               stat_dept(Pid,Cnt)
		        end
		    end  
.
 
%%=====================================================
%%根据人员编号获取对应的部门信息
%%参数说明：
%%    null:固定标识符。表示只获取人员所在机构部门的信息
%%    all: 固定标识符。表示获取人员所在机构部门的信息以及所有的下级机构信息
%%    Empid:人员编号
%%返回说明：
%%    ->[]|[{#base_dept}]
%%=====================================================
getdept(Empid)->
     getdept(null,Empid).
getdeptid(Empid)->
     Dc=getdept(null,Empid),
     
     case Dc of []->[];
          _->
             (hd(Dc))#base_dept.deptid
     end.     
getdept(null,Empid)->
    %%判断帐号是否有服务器信息
    {User,Server}=func_utils:jid(Empid),
    LocalServ = func_utils:getlocalserver(),    
    Rs=empFilter(case Server of LocalServ->  
                            TempRs=ejabberdex_odbc_query:get_emp_by_account( User),
                            case TempRs of []-> ejabberdex_odbc_query:get_emp_by_account( User++"@"++Server);
                                _-> TempRs
                            end;
                      _-> ejabberdex_odbc_query:get_emp_by_account( Empid)
       end),
    if Rs =:= [] ->
	       %%尝试使用企业注册号
	        case func_utils:is_jid(Empid) of 
	                               true->[];
	                               _->
	                                  EnterRs =  ejabberdex_odbc_query:get_enterprise_by_eno(Empid),
	                                  case EnterRs of
	                                       []->[];
	                                       _->getdept((hd(EnterRs))#enterprise_reg.admin)
	                                  end
	       end;
   true      ->
                   Deptid=(hd(Rs))#employee.deptid,
                   im_organ_odbc:getdeptbyid(Deptid)
   end;
getdept(all,Empid)->
    Rs=empFilter(ejabberdex_odbc_query:get_emp_by_account( Empid)),
    if Rs =:= [] ->[];
       true      ->
                   Deptid=(hd(Rs))#employee.deptid,
                   im_organ_odbc:getdeptbypid(all,Deptid)
    end.             
         

%%=====================================================
%%获取指定编号的人员信息。
%%参数说明：
%%   Uid:人员编号
%%返回结果：
%%  ->[{#employee}]|[]
%%=====================================================  
getuserbyid(Uid)->
        ejabberdex_odbc_query:get_emp_by_id(Uid). 
getuserbyaccount(Account)->
    case Account of []->[];
    _->
	    {User,Server}=func_utils:jid(Account),
	    LocalServ = func_utils:getlocalserver(),    
	    Rs=case Server of LocalServ->  
	                            TempRs=ejabberdex_odbc_query:get_emp_by_account( User),
	                            case TempRs of []-> ejabberdex_odbc_query:get_emp_by_account( User++"@"++Server);
	                                _-> TempRs
	                            end;
	                      _->  ejabberdex_odbc_query:get_emp_by_account( Account)
	    end,
	    empFilter(Rs)
    end
.
%%过滤公共帐号 
empFilter(Rs)->
    F=fun(Rec)->
        case regexp:first_match(element(2,Rec),"v[0-9][0-9][0-9][0-9][0-9][0-9]-") of
         nomatch->true;
         _-> false
         end
    end,
    lists:filter(F,Rs)
.
%%=====================================================
%%获取指定机构的直接所属人员。
%%参数说明：
%%   Deptid:机构编号
%%返回结果：
%%  ->[{#employee},{...}]|[]
%%=====================================================
getuserbydeptid(Deptid)->
   ejabberdex_odbc_query:get_emp_by_dept(Deptid). 
getuserbydeptid(all,Deptid)->
   Childs = [DRec#base_dept.deptid||DRec<-im_organ_odbc:getdeptbypid(all,Deptid)],
   Depts = Childs,
   Emps=[ejabberdex_odbc_query:get_emp_by_dept(Did)||Did<-Depts],
   VcradCols =[atom_to_list(C)||C<-mnesia:table_info(vcard_search,attributes)],
   Ind_sex = func_utils:indexOf("xsex",VcradCols)+1,
   Ind_role = func_utils:indexOf("role",VcradCols)+1,
   Ind_email=func_utils:indexOf("email",VcradCols)+1,
   Ind_mobile=func_utils:indexOf("mobile",VcradCols)+1,
   Ind_phone=func_utils:indexOf("phone",VcradCols)+1,
   [get_emp_vcards(Ind_sex,Ind_role,Ind_email,Ind_mobile,Ind_phone,E)||E<-lists:flatten(Emps)].
   
get_emp_vcards(Ind_sex,Ind_role,Ind_email,Ind_mobile,Ind_phone,Emp)->
    Rs=mnesia:dirty_read(vcard_search,func_utils:jid(Emp#employee.loginname)),
    case Rs of []-> erlang:append_element(Emp,[]);
    _->
        Rec=hd(Rs),
        Vcrad = [element(Ind_sex,Rec),element(Ind_role,Rec),element(Ind_email,Rec),element(Ind_mobile,Rec),element(Ind_phone,Rec)],
        erlang:append_element(Emp,Vcrad)
    end
.
getuserbydeptid2(all,Deptid)->
   Childs = [DRec#base_dept.deptid||DRec<-im_organ_odbc:getdeptbypid(all,Deptid)],
   Emps=[ejabberdex_odbc_query:get_emp_by_dept(Did)||Did<-Childs],
   lists:flatten(Emps).
%%==============================================================
%%添加新的人员。 
%%参数说明：
%%  Empid:新增的人员的编号。可以为null标识,表示为空,没有传值,系统会自动产生一个数字编号
%%  Employeename:新增的员的名称。可理解为真实名称
%%  DeptID:人员所属的机构部门编号 
%%  LoginName:人员的登录帐号
%%  Password:人员的登录密码
%%  Regserver:当前人员注册的服务器名
%%==============================================================
add(Empid,Employeename,DeptID,LoginName,Password,Regserver)-> 
    Pass = case Password of []-> ?DEFAULT_PASSWORD; _-> Password end,
    ?ERROR_MSG("new add employee:========~p,~p,~p,~p,~p~n", [Empid,Employeename,DeptID,LoginName,Pass]),
    Jid=func_utils:jid(LoginName,Regserver),
		if (Empid==null) or (Empid=="") ->
			        Uid = ejabberdex_odbc_query:get_seq_nextvalue("im_employee","employeeid");
			       true       ->Uid =Empid
		end,			    
		F = fun() ->   
			        Female = #employee{employeeid=Uid,
			                           employeename=request:convertUTF8(Employeename),
			                           deptid=DeptID,
			                           loginname=LoginName,
			                           password = Pass
			                           },	        
			        case Jid of [] ->
			             mnesia:write(Female),
			             mnesia:write(#employee_version{us = LoginName, version = sha:sha(term_to_binary(now()))}),
			             {atomic,ok};
			        _->
			            {Bare,Server} = Jid,
			            Has =ejabberd_auth_odbc:is_user_exists(Bare,Server),
			            case Has of true-> mnesia:abort("帐号已存在");
			            _->
			                ejabberd_auth_odbc:try_register(Bare,Server,Pass),
			                mnesia:write(Female),
			                mnesia:write(#employee_version{us = LoginName, version = sha:sha(term_to_binary(now()))})
			            end
			        end   
		end,   
		case mnesia:transaction(F) of {atomic,ok}->
			      stat_dept(DeptID,1);%%更新部门的员工总数统计表
			   {aborted,R}->
			       ?ERROR_MSG("new add employee error:========~p~n", [R]),
			      {error,R}
	  end
  .    
  
add(Empid,Employeename,DeptID,LoginName,Password)-> 
  Jid=func_utils:jid(LoginName),
	Pass = case Password of []-> ?DEFAULT_PASSWORD; _-> Password end,
	?ERROR_MSG("new add employee:========~p,~p,~p,~p~n", [Employeename,DeptID,LoginName,Pass]),
	F = fun() ->	        
			        case Jid of [] ->
			            ejabberdex_odbc_query:ins_emp(Empid,Employeename,DeptID,LoginName,Password),
			            {atomic,ok};
			        _->
			            {Bare,Server} = Jid,
			            Has =ejabberd_auth_odbc:is_user_exists(Bare,Server),
			            case Has of true-> mnesia:abort("帐号已存在");
			            _->
			                ejabberd_auth_odbc:try_register(Bare,Server,Pass),
			                ejabberdex_odbc_query:ins_emp(LoginName,Employeename,DeptID,LoginName,Password)
			            end
			        end
	end,   
	case mnesia:transaction(F) of 
			 {aborted,R}->
			      ?ERROR_MSG("new add employee error:========~p~n", [R]),
			      {error,R};
			 _->
			      stat_dept(DeptID,1) %%更新部门的员工总数统计表
  end
  .   
  
%%=====================================================================
%%删除指定deptID的所有人员数据
%%参数说明
%%   deptid:固定标识。表示当前删除的是指定部门的所有人员
%%   Para_id:部门编号。
%%返回结果为以下结构之一：
%%   ->{ok,0}  操作成功，但未删除任何数据 
%%   ->{ok,Counter} 操作成功，并返回删除的人员总数Counter
%%   ->{error,_R} 操作失败及原因
%%=====================================================================
delete(deptid,Para_id)-> 
    F = fun() ->
        %%获取部门下的员工
        Rowset = getuserbydeptid(Para_id),
        if Rowset==[] -> {ok,0};%%返回操作成功标识，删除结果数为空
           true       ->
                 deleteemplist(Rowset,0) 
        end
    end,   
  mnesia:transaction(F);
  
%%=====================================================================
%%删除指定的人员数据
%%参数说明
%%   employeeid:固定标识。表示当前删除的是特定的人员
%%   Para_id:人员编号。
%%=====================================================================  
delete(employeeid,Para_id)->
        Rs = getuserbyid(Para_id),
        case Rs of []->{ok};
        _-> 	        
          ?ERROR_MSG("delete employee:========~p~n", [Para_id]),
	        ejabberdex_odbc_query:del_emp(Para_id),
	        DeptID =(hd(Rs))#employee.deptid,
	        stat_dept(DeptID,-1),%%更新部门的员工总数统计表
	        Jid = func_utils:jid((hd(Rs))#employee.loginname),
	        case Jid of []->
	            {ok};              
	        _->
	            ?ERROR_MSG("delete employee's vcard:========~p~n", [Jid]),
	            {Bare,Server} = Jid, 	            
	            %%移除vcard信息
	            mod_vcard:remove_user(Bare,Server),
	            Has =ejabberd_auth_odbc:is_user_exists(Bare,Server),
			        case Has of true->
			               ?ERROR_MSG("delete employee's from ejabberd user table:========~p~n", [Jid]),
			               ejabberd_auth_odbc:remove_user(Bare,Server);
			        _->
			           {ok}
			        end
	        end
        end.
  
%%删除指定的人员列表中的所有人员  
deleteemplist(Emps,Counter)->
   case Emps of []-> {ok,Counter};
        _->
            Empid = hd(Emps),
            delete(employeeid,Empid#employee.employeeid),
            deleteemplist(tl(Emps),Counter+1)
   end
.  
%%修改指定用户的数据 .其中employeeid是条件。 
update(Employeeid,Employeename,DeptID,LoginName,Password)-> 
    ?ERROR_MSG("edit employee:========~p,~p,~p,~p,~p~n", [Employeeid,Employeename,DeptID,LoginName,Password]),
    Jid=func_utils:jid(LoginName),
    Pass = case Password of []-> ?DEFAULT_PASSWORD; _-> Password end,
		F = fun() ->   
		              %%判断是否是更新部门
		              case getuserbyid(Employeeid) of 
		                 [Rec]->
		                    OldDeptId = Rec#employee.deptid,
		                    case OldDeptId of  %%判断是否是同一部门
		                      DeptID -> skip;
		                      _->
		                        stat_dept(OldDeptId,-1), %%更新原部门的员工总数统计表
		                        stat_dept(DeptID,1)      %%更新部门的员工总数统计表
		                    end;
		                 _-> skip
		              end,
							    ejabberdex_odbc_query:update_emp(Employeename,DeptID,LoginName,Password),							    
					        %%判断是否设置 有密码					        
					        case Jid of []-> ok;
					        _->
					            {Bare,Server} = Jid,
							        Var=ejabberd_auth_odbc:is_user_exists(Bare,Server),
							        case Var of
							           true->ejabberd_auth_odbc:set_password(Bare,Server,Pass);
							           _->ejabberd_auth_odbc:try_register(Bare,Server,Pass)	              
							        end,
							        ok
					        end         
		end,   
  	case mnesia:transaction(F) of
  			{aborted,R}->
  				    {error,R};
  		  _->{ok}
  	end
  .      
    
   