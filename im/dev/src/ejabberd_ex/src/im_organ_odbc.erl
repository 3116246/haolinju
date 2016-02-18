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
%%����ģ���ʼ������     
inittemplate(A)->
   LOrg = request:getparameter(A,"orgid","utf-8"),
   Template = request:getparameter(A,"template","utf-8"),
   case LOrg of 
       []-> request:returnerror("1");
       "null"-> request:returnerror("1");
       _->
           Org =case LOrg of [$v|_] -> LOrg;_-> "v"++LOrg end,
           VirOrg=Org++"999",
           
           %%����н������ⲿ������Աȫ���Ƶ�������
           Emps= im_employee_odbc:getuserbydeptid2(all,Org),
           if Emps=/=[]->
               [im_employee_odbc:update(Re#employee.employeeid,
                                Re#employee.employeename,Org,
                                Re#employee.loginname,
                                Re#employee.password)||Re<-lists:filter(fun(Rs)->(Rs#employee.deptid=/=VirOrg)and(Rs#employee.deptid=/=Org) end,Emps)];
           true->
               skip
           end,
           
           %%ɾ�����в���
           Depts=getdeptbypid(all,Org),
           if Depts=/=[]->
             %%���˳����ⲿ�ż��Լ�
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


%%��ȡָ����Ա������ҵ�����Ĳ���
readall_stat(UserAccount)->
    %%��ȡ��Ա�Ĳ���
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
   
%%��ȡָ���Ļ�������Ϣ  
%%����˵����
%%  Deptid:����ID
%%����˵����
%%  []|[{#base_dept}]
getdeptbyid(Deptid)->  ejabberdex_odbc_query:get_dept_by_id(Deptid). 
getdeptbyid_stat(Deptid)->   ejabberdex_odbc_query:get_deptandstat_by_id(Deptid).   

%%��ȡָ���Ļ�����ֱ���¼�������Ϣ  
%%����˵����
%%  Pid:����ID
%%����˵����
%%  []|[{#base_dept},{...}]
getdeptbypid(Pid)->  ejabberdex_odbc_query:get_dept_by_parentid(Pid).
getdeptbypid_stat(Pid)-> ejabberdex_odbc_query:get_deptandstat_by_parentid(Pid).

%%ѭ����ȡָ����ŵ������¼�����  
%%����˵����
%%  all:�̶���ʶ��.��ʾ��ȡָ�������µ������ӻ���
%%  Pid:����ID
%%����˵����
%%  []|[{#base_dept},{...}]
getdeptbypid(all,Pid)-> ejabberdex_odbc_query:get_all_dept_by_parentid(Pid).
getdeptbypid_stat(all,Pid)->
    ejabberdex_odbc_query:get_all_deptandstat_by_parentid(Pid).
  
%%����µ���֯ .����deptID���û����ȷָ�����Զ����ɡ�
%%����˵����
%%   Deptid:���������ı�š�������Ϊ""ʱ��ϵͳ���Զ�����һ�����������
%%   Deptname:�������������ơ�
%%   Pid:�����������ϼ�������š�
%%   OrderNo:����������˳��š�Ĭ��Ϊ999.
%%����˵����
%%  ʧ��ʱ������{error,Pid_R}�ṹ
%%  �ɹ�ʱ������{atomic,ok}�ṹ
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
    %%��ȡָ����pid�ڵ����Ϣ��������Ϣ���ɵ�ǰ�ڵ��path����       
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


%%������Ӷ�����µ���֯�����������ǰ��ɾ��Pid��Ӧ�ļ�¼�����Ը÷���Ҳ�������޸�����
%%����˵����
%%   Lst����һ������ӵ���֯���������б�ۣݡ��б��������˳���������£�
%%        Deptid:���������ı�š�������Ϊ""ʱ��ϵͳ���Զ�����һ�����������
%%        Deptname:�������������ơ�
%%        Pid:�����������ϼ�������š�
%%        OrderNo:����������˳��š�Ĭ��Ϊ"999".
%%����˵����
%%  ʧ��ʱ������{error,Pid_R}�ṹ
%%  �ɹ�ʱ������{atomic,recordCounter,[..]}�ṹ��
%%              recordCounterΪ�ɹ��ļ�¼����
%%              []Ϊδ�ɹ�������
executebatch(Lst) ->
   {Flag,Result} = mnesia:transaction(fun()->  executebatch(Lst,0,[]) end),
   case Flag of 
     atomic-> Result;
          _-> {Flag,Result}
   end. 
   
%%���������ǰ�����ԭ�����ݡ�executebatch/1 ���Զ����ԭ������
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

%%ɾ��ָ��deptID����֯����
%%����˵����
%%    Deptid:��ɾ���Ļ����ı��
%%����˵����
%%  ʧ��ʱ������{error,Pid_R}�ṹ
%%  �ɹ�ʱ������{atomic,ok}�ṹ
delete(Deptid)-> 
        %%�ж��Ƿ����¼�������Ա����������ɾ��
        case {getdeptbypid(Deptid),im_employee_odbc:getuserbydeptid(Deptid)}
         of {[],[]}->
		        ejabberdex_odbc_query:del_dept(Deptid);
        _->
        		iconv:convert("gbk","utf-8","�����¼����Ż�Ա��,����ɾ��")
        end.  
  
%%�޸�ָ����������Ϣ ��
%%����˵����
%%   Deptid:���µĻ�����š�
%%   Deptname:���»��������ơ�
%%   Pid:���»������ϼ�������š�
%%   OrderNo:���»�����˳��š�
%%����˵����
%%  ʧ��ʱ������{error,Pid_R}�ṹ
%%  �ɹ�ʱ������{atomic,ok}�ṹ 
update(Deptid,Deptname,Pid,OrderNo)-> 
    update(Deptid,Deptname,Pid,OrderNo,"","")
.    
update(Deptid,Deptname,Pid,OrderNo,Manager,Remark)-> 
    ejabberdex_odbc_query:del_dept(Deptid),
    add(Deptid,Deptname,Pid,OrderNo,Manager,Remark). 
     