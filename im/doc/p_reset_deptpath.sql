-- --------------------------------------------------------------------------------
-- Routine DDL
-- Note: comments before and after the routine body will not be stored by the server
-- --------------------------------------------------------------------------------
DELIMITER //

drop procedure if exists p_reset_deptpath //

CREATE PROCEDURE p_reset_deptpath (v_eno varchar(20), v_deptid varchar(50) )
BEGIN
	declare _deptid varchar(20);
	declare _pid varchar(200);  
	declare _path varchar(200);  
	declare done int;  
	declare rs_cursor cursor for SELECT deptid FROM im_base_dept where pid =case v_deptid when '' then concat('v',v_eno) else v_deptid end;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1; 
	
	set max_sp_recursion_depth=32;
    -- select v_deptid from dual;
	select  case v_deptid when '' then concat('v',v_eno) else v_deptid end into _pid;
	if v_deptid='' then
		update im_base_dept set path=concat('/-10000/v',v_eno,'/') where deptid =concat('v',v_eno);
	else
	    select concat(b2.path,v_deptid,'/') into _path from im_base_dept b1,im_base_dept b2 where b2.deptid=b1.pid and b1.deptid=v_deptid;
	    update im_base_dept set path=_path where deptid =v_deptid;
	    -- select * from im_base_dept where deptid = v_deptid;
    end if;
    select path into _path from im_base_dept where deptid= _pid;
	update im_base_dept set path=concat(_path,deptid,'/') where pid = _pid;
	open rs_cursor;
    cursor_loop:loop
		FETCH rs_cursor into _deptid; 
		if done=1 then
			leave cursor_loop;
		end if;
		call p_reset_deptpath(v_eno,_deptid);
	end loop cursor_loop;
	close rs_cursor;
	-- delete from im_dept_stat where deptid in (SELECT deptid FROM im_base_dept where path like concat('/-10000/v',eno,'/%'));
	
END
//
DELIMITER ;
