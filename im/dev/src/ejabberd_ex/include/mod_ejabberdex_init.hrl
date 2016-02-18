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
          grouprole,      %%成员角色
          employeenick,   %%成员昵称
          employeenote,    %%群名片
          photo="",
          spell
          }).    
          
-define(GROUP_ROLE_OWNER, "owner").  %%成员角色：群主
-define(GROUP_ROLE_MANAGER, "manager").  %%成员角色：群管理者
-define(GROUP_ROLE_NORMAL, "normal").       %%成员角色：普通成员 

-define(GROUP_AUTH_ALL, "0").            %%验证方法：允许任何人
-define(GROUP_AUTH_CHECK, "1").        %%验证方法：需要身份验证      
-define(GROUP_AUTH_REJECT, "2").      %%验证方法：不允许任何人 

-define(GROUP_ACCESS_ANY,"any").          %%访问权限：任何人
-define(GROUP_ACCESS_MEMBER,"none").      %%访问权限：群成员  

-define(DEFAULT_PASSWORD,"888888").    %%员工默认密码

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
    groupid,  %群号  string
    rawtype,  %流类型 audio, video, audiovideo string
    jid,      %jid string 若为""，则说明该记录为群指定流类型用，其它字段无意义
    ip,       %ip  string() | atom() | ip_address()
    port,     %port 0..65535
    other,    %other string() 存储其它信息
    lasttime, %最后一次发送信息时间，由now()取得 {MegaSecs, Secs, MicroSecs}
    sockmod,  %tcp/udp
    sendpid   %记录TCP连接进来时的发送process的pid
  }).

-record(lib_files,
  {
    fileid,       %文件编号，一般为文件FileHashValue string
    filepath,     %存放路径，带文件名 string，例：<PathPre1>ff.doc，其中PathPre1在lib_filepath.pathid中定义，若为DEFAULT，则为$EJABBERD_HOME/fileupload/目录
    filedesc="",     %文件描述，可为空 string
    addstaff,     %上传人员，一般为JID string
    savelevel,    %保存级别 string，0－上传中，保存1天；1－离线文件，保存7天；2－永久文件，一直保存  
    lastdate      %最后修改日期，由date()取得{yyyy, mm, dd}
  }).
%打开的文件
-record(lib_files_opened,
  {
    fileid,       %文件编号，一般为文件FileHashValue string
    io_device,    %打开的文件描述符，io_device()
    lasttime      %最后一次访问时间，由now()取得 {MegaSecs, Secs, MicroSecs}
  }).  
%离线文件
-record(offline_file,
  {
    fileid,       %文件编号，一般为文件FileHashValue string
    filename,     %文件名 string
    from,         %发送者jid, string
    sendto,       %发送至JID，string
    lastdate      %最后修改日期，由date()取得{yyyy, mm, dd}
  }).
%%消息广播  
-record(broadcast,
  {
     ctime,    %%消息创建时期
     sendemp,  %%消息发送人
     sendtype, %%消息发送类型.一般分为立即发送和定时发送
     receiveemp,  %%消息接收人员列表
     mssage,   %%消息体
     state,     %%消息状态。一般分为未发送，已发送，正在发送（当所有接收人员都发送后更改为已发送）
     url,
     buttons
  }). 
   
%群共享文件
-record(groupshare_file,{groupid,fileid,filename,addstaff,adddate, filesize}).

-record(meeting_run,{meetingemp,meetingid,emprole,in_date,quit_date,state}).
 
-record(syscode,{code,desc,codetype}).%%代码类别

-define(MONGOPOOL, 'MONGOPOOL').    %%mongodb连接池标识
-define(MONGOPOOLSIZE, 10).    %%mongodb连接池中连接数量
-define(MONGOCONN_OPT_D, {"localhost", 27017, we, "", ""}).
-define(MONGODBNAME, element(3, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).    %%mongodb数据库名
-define(MONGOCOLLECTION, 'WeDocument').  %%文档collection名字
-define(MONGODBUSER, element(4, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).
-define(MONGODBPASS, element(5, gen_mod:get_module_opt(?MYNAME, mod_ejabberdex_init, mongo, ?MONGOCONN_OPT_D))).

