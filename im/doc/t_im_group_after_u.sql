DELIMITER //

drop trigger if exists t_im_group_after_u ;

create trigger t_im_group_after_u after update
on im_group for each row
begin
  declare v_sql_text text;
  declare v_fafa_jid varchar(200);
    
  -- 同步至we
  if substring_index(user(),'@',1)  <> 'we_rep' then
    select concat('update we_groups set ',
                  '  group_name=\'', new.groupname, '\', ',
                  '  group_desc=\'', substr(ifnull(new.groupdesc, ''), 1, 200), '\', ',
                  '  join_method=\'', case when new.add_member_method='0' then '0' else '1' end, '\' ', 
                  'where fafa_groupid=\'', new.groupid, '\'')
    into v_sql_text
    from dual;    
    insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
    
  end if;
  
end;

//
DELIMITER ;