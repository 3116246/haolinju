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

%%��ȡ��ҵ��ָ����𹫹��ʺ�
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


%%��ѯ������         
selectFriendGroup(EmpAccount)->
   ejabberdex_odbc_query:get_friend_group(EmpAccount)
.
%%��Ӻ��ѷ���
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
%%ɾ�����ѷ��顣ɾ�� ʱ����������еĺ��ѣ���ȷ��ɾ����֮ǰ��Ӧ�ó���Ӧ�Ѵ���øõ��ڵ����к���
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
%%��������������
renameFriendGroup(EmpAccount,OldGroupName,NewGroupName)->    
        ejabberdex_odbc_query:update_friend_group(EmpAccount,OldGroupName,NewGroupName)
.

%%������Ա����ΪĬ������         
resetpass(Empid)->
      changepass(Empid,?DEFAULT_PASSWORD)
.
%%��������Ϊ�������
resetrandompass(Empid)->
      Random=integer_to_list(func_utils:getNumber(10000000,99999999)),
      changepass(Empid,Random)
.
%%�����ʺ���������Ϊ�������
resetrandompass_ac(Account)->
      resetrandompass(Account)
.
%%�޸�����
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
%%�޸�����
changepassbyaccount(Account,Pass)->
          changepass(Account,Pass)
.
changemanagerpass(Uid,Old,New)->
     if (Uid==[])or(Old==[])or(new==[])->
         Reply={error,"isnull"};
      true->
         %%�ж�Uid�Ƿ���Ч
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
				       %%����ʹ����ҵע���
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
%%%��ȡָ����Ա�ĺ����б�
%getfriend(_Empid)->
%   readall()
%.
getRoleByAccount(Account)->
    %%�ж��ʺ��Ƿ��з�������Ϣ
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
%%������Ӷ�����µ���֯�����������ǰ��ɾ��Pid��Ӧ�ļ�¼�����Ը÷���Ҳ�������޸�����
%%����˵����
%%   Lst����һ������ӵ���֯���������б�ۣݡ��б��������˳���������£�
%%  Empid:��������Ա�ı�š�����Ϊnull��ʶ,��ʾΪ��,û�д�ֵ,ϵͳ���Զ�����һ�����ֱ��
%%  Employeename:������Ա�����ơ������Ϊ��ʵ����
%%  DeptID:��Ա�����Ļ������ű�� 
%%  LoginName:��Ա�ĵ�¼�ʺ�
%%  Password:��Ա�ĵ�¼����
%%����˵����
%%  ʧ��ʱ������{error,Pid_R}�ṹ
%%  �ɹ�ʱ������{atomic,recordCounter,[..]}�ṹ��
%%              recordCounterΪ�ɹ��ļ�¼����
%%              []Ϊδ�ɹ�������
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
		        %%������¼
		        ejabberdex_odbc_query:ins_emp(C_empid,C_loginname,C_empname,C_deptid,C_loginname,C_pwd),
		        stat_dept(C_deptid,1),%%���²��ŵ�Ա������ͳ�Ʊ�
		        %%�ж��Ƿ����� ������
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
%%������Ա��Ż�ȡ��Ӧ�Ĳ�����Ϣ
%%����˵����
%%    null:�̶���ʶ������ʾֻ��ȡ��Ա���ڻ������ŵ���Ϣ
%%    all: �̶���ʶ������ʾ��ȡ��Ա���ڻ������ŵ���Ϣ�Լ����е��¼�������Ϣ
%%    Empid:��Ա���
%%����˵����
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
    %%�ж��ʺ��Ƿ��з�������Ϣ
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
	       %%����ʹ����ҵע���
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
%%��ȡָ����ŵ���Ա��Ϣ��
%%����˵����
%%   Uid:��Ա���
%%���ؽ����
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
%%���˹����ʺ� 
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
%%��ȡָ��������ֱ��������Ա��
%%����˵����
%%   Deptid:�������
%%���ؽ����
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
%%����µ���Ա�� 
%%����˵����
%%  Empid:��������Ա�ı�š�����Ϊnull��ʶ,��ʾΪ��,û�д�ֵ,ϵͳ���Զ�����һ�����ֱ��
%%  Employeename:������Ա�����ơ������Ϊ��ʵ����
%%  DeptID:��Ա�����Ļ������ű�� 
%%  LoginName:��Ա�ĵ�¼�ʺ�
%%  Password:��Ա�ĵ�¼����
%%  Regserver:��ǰ��Աע��ķ�������
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
			            case Has of true-> mnesia:abort("�ʺ��Ѵ���");
			            _->
			                ejabberd_auth_odbc:try_register(Bare,Server,Pass),
			                mnesia:write(Female),
			                mnesia:write(#employee_version{us = LoginName, version = sha:sha(term_to_binary(now()))})
			            end
			        end   
		end,   
		case mnesia:transaction(F) of {atomic,ok}->
			      stat_dept(DeptID,1);%%���²��ŵ�Ա������ͳ�Ʊ�
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
			            case Has of true-> mnesia:abort("�ʺ��Ѵ���");
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
			      stat_dept(DeptID,1) %%���²��ŵ�Ա������ͳ�Ʊ�
  end
  .   
  
%%=====================================================================
%%ɾ��ָ��deptID��������Ա����
%%����˵��
%%   deptid:�̶���ʶ����ʾ��ǰɾ������ָ�����ŵ�������Ա
%%   Para_id:���ű�š�
%%���ؽ��Ϊ���½ṹ֮һ��
%%   ->{ok,0}  �����ɹ�����δɾ���κ����� 
%%   ->{ok,Counter} �����ɹ���������ɾ������Ա����Counter
%%   ->{error,_R} ����ʧ�ܼ�ԭ��
%%=====================================================================
delete(deptid,Para_id)-> 
    F = fun() ->
        %%��ȡ�����µ�Ա��
        Rowset = getuserbydeptid(Para_id),
        if Rowset==[] -> {ok,0};%%���ز����ɹ���ʶ��ɾ�������Ϊ��
           true       ->
                 deleteemplist(Rowset,0) 
        end
    end,   
  mnesia:transaction(F);
  
%%=====================================================================
%%ɾ��ָ������Ա����
%%����˵��
%%   employeeid:�̶���ʶ����ʾ��ǰɾ�������ض�����Ա
%%   Para_id:��Ա��š�
%%=====================================================================  
delete(employeeid,Para_id)->
        Rs = getuserbyid(Para_id),
        case Rs of []->{ok};
        _-> 	        
          ?ERROR_MSG("delete employee:========~p~n", [Para_id]),
	        ejabberdex_odbc_query:del_emp(Para_id),
	        DeptID =(hd(Rs))#employee.deptid,
	        stat_dept(DeptID,-1),%%���²��ŵ�Ա������ͳ�Ʊ�
	        Jid = func_utils:jid((hd(Rs))#employee.loginname),
	        case Jid of []->
	            {ok};              
	        _->
	            ?ERROR_MSG("delete employee's vcard:========~p~n", [Jid]),
	            {Bare,Server} = Jid, 	            
	            %%�Ƴ�vcard��Ϣ
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
  
%%ɾ��ָ������Ա�б��е�������Ա  
deleteemplist(Emps,Counter)->
   case Emps of []-> {ok,Counter};
        _->
            Empid = hd(Emps),
            delete(employeeid,Empid#employee.employeeid),
            deleteemplist(tl(Emps),Counter+1)
   end
.  
%%�޸�ָ���û������� .����employeeid�������� 
update(Employeeid,Employeename,DeptID,LoginName,Password)-> 
    ?ERROR_MSG("edit employee:========~p,~p,~p,~p,~p~n", [Employeeid,Employeename,DeptID,LoginName,Password]),
    Jid=func_utils:jid(LoginName),
    Pass = case Password of []-> ?DEFAULT_PASSWORD; _-> Password end,
		F = fun() ->   
		              %%�ж��Ƿ��Ǹ��²���
		              case getuserbyid(Employeeid) of 
		                 [Rec]->
		                    OldDeptId = Rec#employee.deptid,
		                    case OldDeptId of  %%�ж��Ƿ���ͬһ����
		                      DeptID -> skip;
		                      _->
		                        stat_dept(OldDeptId,-1), %%����ԭ���ŵ�Ա������ͳ�Ʊ�
		                        stat_dept(DeptID,1)      %%���²��ŵ�Ա������ͳ�Ʊ�
		                    end;
		                 _-> skip
		              end,
							    ejabberdex_odbc_query:update_emp(Employeename,DeptID,LoginName,Password),							    
					        %%�ж��Ƿ����� ������					        
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
    
   