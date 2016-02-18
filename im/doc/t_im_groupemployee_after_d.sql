DELIMITER //

drop trigger if exists t_im_groupemployee_after_d ;

create trigger t_im_groupemployee_after_d after delete
on im_groupemployee for each row
begin
  declare v_sql_text text;
  declare v_fafa_jid varchar(200);
    
  -- 同步至we
  if substring_index(user(),'@',1) <> 'we_rep' then
    select concat('delete from we_group_staff ',
                  'where group_id in (select group_id from we_groups where fafa_groupid=\'', old.groupid, '\' ) ',
                  '  and login_account in (select login_account from we_staff where fafa_jid=\'', old.employeeid, '\' )')
    into v_sql_text
    from dual;    
    insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
    
  end if;
  
end;

//
DELIMITER ;