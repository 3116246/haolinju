DELIMITER //

drop procedure if exists emp_change_name //
CREATE  PROCEDURE `emp_change_name`(c_jid varchar(32),nick_name varchar(50))
BEGIN
	declare done int;
  	declare oldName varchar(50);
	declare rs_cursor cursor for select employeename from im_employee where loginname=c_jid ;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1; 	
	open rs_cursor;
	cursor_loop:loop
		FETCH rs_cursor into oldName; 
		if done=1 then
			leave cursor_loop;
		end if;
		if oldName != nick_name and nick_name!='' then 
			start transaction;
				update im_employee set employeename=nick_name where loginname=c_jid;
				update im_groupemployee set employeenick=nick_name where employeeid=c_jid;
				update rosterusers set nick=nick_name where jid=c_jid;
			commit;
		end if;
	end loop cursor_loop;
	close rs_cursor;
END //
 
DELIMITER ;