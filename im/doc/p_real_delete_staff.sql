DELIMITER //

drop procedure if exists p_real_delete_staff;


create procedure p_real_delete_staff (
  in v_login_account varchar(200),
  in v_isquiet numeric(1)
)
-- 物理上删除员工的全部数据
begin
  
  delete from global_session    where us=v_login_account;  
  delete from global_session_his where us=v_login_account;
  delete from im_employee where loginname=v_login_account;
  delete from im_employee_version where us=v_login_account;
  delete from im_employeerole where employeeid=v_login_account;
  delete from im_friendgroups where loginname=v_login_account;
  delete from im_groupemployee where employeeid=v_login_account;
  delete from im_offline_file where sendto=v_login_account;
  delete from im_subscribe_ex where jid=v_login_account;
  delete from rostergroups where username=v_login_account;
  delete from rosterusers where username=v_login_account;
  delete from users where username=v_login_account;  
  if v_isquiet = 0 then
    select '1' recode, '' remsg from dual;
  end if;
end;

//
DELIMITER ;