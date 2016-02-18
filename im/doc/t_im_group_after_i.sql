DELIMITER //

drop trigger if exists t_im_group_after_i ;

create trigger t_im_group_after_i after insert
on im_group for each row
begin
  declare v_sql_text text;
  declare v_create_account varchar(50);
  select concat('[',new.creator,']') into v_create_account;
  -- 同步至we
  if substring_index(user(),'@',1)  <> 'we_rep' then

    select concat('insert into we_groups (group_id,group_name,group_desc,fafa_groupid,join_method,group_class,create_staff,create_date,circle_id)values(\'@groupid\',\''
    	          ,new.groupname,'\',\''
    	          ,substr(ifnull(new.groupdesc, ''), 1, 200),'\',\'fafa_groupid='
    	          ,new.groupid,'\',\''
    	          ,new.add_member_method,'\',\''
    	          ,'discussgroup\',\''
    	          ,v_create_account,'\',now(),\'@eno\')') 
    into v_sql_text
    from dual;    
    insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
    
  end if;
  
end;

//
DELIMITER ;