DELIMITER //

drop procedure if exists p_deluser_im //
CREATE PROCEDURE `p_deluser_im`(in v_account varchar(50))
BEGIN
   start transaction;
-- 删除人员表 
  delete  from rosterusers where username=v_account;
  delete  from rosterusers where jid=v_account;
  delete  from users where username=v_account;
  
  delete  from rostergroups where username=v_account;
  delete  from rostergroups where jid=v_account;
  
  delete  from im_subscribe_ex where jid=v_account;
  
  delete  from im_offline_file where sendfrom=v_account;
  
  delete  from im_groupemployee where employeeid=v_account;
  delete  from im_friendgroups where loginname=v_account;
  delete  from im_employeerole where employeeid=v_account;
  
  delete  from im_employee where loginname=v_account;
  delete  from im_employee_version where us=v_account;
  delete  from global_session where us=v_account;
  delete  from im_employeerole where employeeid=v_account;

  delete from spool where username=v_account;
  delete from im_runtime_message where jid=v_account;
  
  commit;
END //
 
DELIMITER ;