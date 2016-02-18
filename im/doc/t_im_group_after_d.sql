DELIMITER //

drop trigger if exists t_im_group_after_d ;

create trigger t_im_group_after_d after delete
on im_group for each row
begin
  declare v_sql_text text;
  -- 同步至we
  if substring_index(user(),'@',1) <> 'we_rep' then
    select concat('delete from we_groups ',
                  'where fafa_groupid =\'', old.groupid, '\'')
    into v_sql_text
    from dual;    
    insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
    
  end if;
  
end;

//
DELIMITER ;