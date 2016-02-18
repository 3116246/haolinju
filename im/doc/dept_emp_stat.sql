DELIMITER //

drop procedure if exists dept_emp_stat //

CREATE  PROCEDURE `dept_emp_stat`(eno varchar(50))
BEGIN
   declare eno2 varchar(200);
   declare done int;
   declare _deptid varchar(20);
   declare _path varchar(200);  
   declare cnt int;
   declare has int;
   declare splitCharCount int;
   declare isRootID int;

   declare rs_cursor cursor for SELECT deptid,path FROM im_base_dept where path like concat('/-10000/v',eno,'/%');

   DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;   
   -- eno or jid
   if INSTR(eno,'@')=0 then
       	SELECT count(*) into isRootID FROM im_base_dept where path = concat('/-10000/v',eno,'/');
       	if isRootID=0 then
	      	SELECT b.path into _path FROM im_base_dept b where b.deptid = eno;
	      	if _path!='' then         
	         	-- start  transaction;         
	         	while _path is not null do
	            if _path='/-10000/' or _path='' or _path='/' then
	               set _path=null;
	            else
	               select length(_path)-length(replace(_path,"/","")) into splitCharCount;
	               select replace( replace(_path,  SUBSTRING_INDEX(_path,"/", splitCharCount-1), ''),'/','') into _deptid;
	               SELECT count(0) into cnt FROM im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like concat(_path,'%') ;
	               
	               select count(1) into has from im_dept_stat where deptid=_deptid;
	               if has>0 then
	                  update im_dept_stat set empcount=cnt where deptid=_deptid;
	               else
	                  insert into im_dept_stat select _deptid,cnt,0,0;
	               end if;  
	               select concat( SUBSTRING_INDEX(_path,"/", splitCharCount-1),"/") into _path;
	             end if;
	         	end while;
	        	-- commit;
	      	end if;     		
       	else
	      	-- is eno
	      	start  transaction;
	      	open rs_cursor;
	      	cursor_loop:loop
	   		FETCH rs_cursor into _deptid, _path; 
	   		if done=1 then
	   			leave cursor_loop;
	   		end if;
	   		SELECT count(0) into cnt FROM im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like concat(_path,'%') ;
	        select count(1) into has from im_dept_stat where deptid=_deptid;
	        if has>0 then
	            update im_dept_stat set empcount=cnt where deptid=_deptid;
	        else
	            insert into im_dept_stat select _deptid,cnt,0,0;
	        end if;
	   		end loop cursor_loop;
	   		close rs_cursor;
	   		commit;
   		end if;
   else
      -- is jid
      SELECT b.path into _path FROM im_employee a, im_base_dept b where a.deptid=b.deptid and a.loginname=eno;
      if _path!='' then         
         -- start  transaction;         
         while _path is not null do
            if _path='/-10000/' or _path='' or _path='/' then
               set _path=null;
            else
               select length(_path)-length(replace(_path,"/","")) into splitCharCount;
               -- set log=concat(log,'->B:',now());
               select replace( replace(_path,  SUBSTRING_INDEX(_path,"/", splitCharCount-1), ''),'/','') into _deptid;
               SELECT count(0) into cnt FROM im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like concat(_path,'%') ;
               -- set log=concat(log,'->L:',now(),'[',_path,']');
               select count(1) into has from im_dept_stat where deptid=_deptid;
               if has>0 then
                  update im_dept_stat set empcount=cnt where deptid=_deptid;
               else
                  insert into im_dept_stat select _deptid,cnt,0,0;
               end if;  
               select concat( SUBSTRING_INDEX(_path,"/", splitCharCount-1),"/") into _path;
             end if;
         end while;
         -- commit;
      end if;
   end if;
   delete from im_dept_version where us like concat('%-',eno,'@fafacn.com');
   -- select log;
END //
 
DELIMITER ;