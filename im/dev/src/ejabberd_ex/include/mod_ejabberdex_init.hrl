-record(grouptype,
{
         typeid, 
         typename,
         pid,
         remark=""
}).

-record(group,
        {
         groupid, 
         groupname,
         groupclass,
         groupdesc,
         grouppost,
         creator,
         add_member_method,
         accessright,
         logo,
         number,
         max_number
         }).
               
-record (groupemployee,
         {employeeid, 
          groupid,
          grouprole,      %%��Ա��ɫ
          employeenick,   %%��Ա�ǳ�
          employeenote,    %%Ⱥ��Ƭ
          photo="",
          spell
          }).    
          
-define(GROUP_ROLE_OWNER, "owner").  %%��Ա��ɫ��Ⱥ��
-define(GROUP_ROLE_MANAGER, "manager").  %%��Ա��ɫ��Ⱥ������
-define(GROUP_ROLE_NORMAL, "normal").       %%��Ա��ɫ����ͨ��Ա 

-define(GROUP_AUTH_ALL, "0").            %%��֤�����������κ���
-define(GROUP_AUTH_CHECK, "1").        %%��֤��������Ҫ�����֤      
-define(GROUP_AUTH_REJECT, "2").      %%��֤�������������κ��� 

-define(GROUP_ACCESS_ANY,"any").          %%����Ȩ�ޣ��κ���
-define(GROUP_ACCESS_MEMBER,"none").      %%����Ȩ�ޣ�Ⱥ��Ա  

-define(DEFAULT_PASSWORD,"888888").    %%Ա��Ĭ������

-record(employeerole,
        {
          employeeid,
          roleid
        }).


-record(group_version, {us, version}).

-record(groupemployee_version, {us, groupid, version}).

-record(enterprise_reg, {eno,mail,subdomain,admin,fullname,name}).

-record(subscribe_ex,{jid,rid,rtype }).
-record(base_dept,{deptid,deptname,pid,path,noorder,manager="",remark=""}).     
-record(dept_stat,{deptid,empcount,online=0,childdept=0}).       
-record(employee,{employeeid,deptid,loginname,password,employeename,p_desc,photo,spell}). 
       
-record(friendgroups,{empg,loginname,groupname
}).
-record(dept_version, {us,version}).
-record(employee_version, {us,version}).

-record(group_raw_swap,
  {
    groupid,  %Ⱥ��  string
    rawtype,  %������ audio, video, audiovideo string
    jid,      %jid string ��Ϊ""����˵���ü�¼ΪȺָ���������ã������ֶ�������
    ip,       %ip  string() | atom() | ip_address()
    port,     %port 0..65535
    other,    %other string() �洢������Ϣ
    lasttime, %���һ�η�����Ϣʱ�䣬��now()ȡ�� {MegaSecs, Secs, MicroSecs}
    sockmod,  %tcp/udp
    sendpid   %��¼TCP���ӽ���ʱ�ķ���process��pid
  }).

-record(lib_files,
  {
    fileid,       %�ļ���ţ�һ��Ϊ�ļ�FileHashValue string
    filepath,     %���·�������ļ��� string������<PathPre1>ff.doc������PathPre1��lib_filepath.pathid�ж��壬��ΪDEFAULT����Ϊ$EJABBERD_HOME/fileupload/Ŀ¼
    filedesc="",     %�ļ���������Ϊ�� string
    addstaff,     %�ϴ���Ա��һ��ΪJID string
    savelevel,    %���漶�� string��0���ϴ��У�����1�죻1�������ļ�������7�죻2�������ļ���һֱ����  
    lastdate      %����޸����ڣ���date()ȡ��{yyyy, mm, dd}
  }).
%�򿪵��ļ�
-record(lib_files_opened,
  {
    fileid,       %�ļ���ţ�һ��Ϊ�ļ�FileHashValue string
    io_device,    %�򿪵��ļ���������io_device()
    lasttime      %���һ�η���ʱ�䣬��now()ȡ�� {MegaSecs, Secs, MicroSecs}
  }).  
%�����ļ�
-record(offline_file,
  {
    fileid,       %�ļ���ţ�һ��Ϊ�ļ�FileHashValue string
    filename,     %�ļ��� string
    from,         %������jid, string
    sendto,       %������JID��string
    lastdate      %����޸����ڣ���date()ȡ��{yyyy, mm, dd}
  }).
%%��Ϣ�㲥  
-record(broadcast,
  {
     ctime,    %%��Ϣ����ʱ��
     sendemp,  %%��Ϣ������
     sendtype, %%��Ϣ��������.һ���Ϊ�������ͺͶ�ʱ����
     receiveemp,  %%��Ϣ������Ա�б�
     mssage,   %%��Ϣ��
     state,     %%��Ϣ״̬��һ���Ϊδ���ͣ��ѷ��ͣ����ڷ��ͣ������н�����Ա�����ͺ����Ϊ�ѷ��ͣ�
     url,
     buttons
  }). 
   
%Ⱥ�����ļ�
-record(groupshare_file,{groupid,fileid,filename,addstaff,adddate, filesize}).

-record(meeting_run,{meetingemp,meetingid,emprole,in_date,quit_date,state}).
 
-record(syscode,{code,desc,codetype}).%%�������

-define(MONGOPOOL, 'MONGOPOOL').    %%mongodb���ӳر�ʶ
-define(MONGOPOOLSIZE, 10).    %%mongodb���ӳ�����������
-define(MONGOCONN_OPT_D, {"localhost", 27017, we, "", ""}).
-define(MONGODBNAME, element(3, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).    %%mongodb���ݿ���
-define(MONGOCOLLECTION, 'WeDocument').  %%�ĵ�collection����
-define(MONGODBUSER, element(4, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).
-define(MONGODBPASS, element(5, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).

