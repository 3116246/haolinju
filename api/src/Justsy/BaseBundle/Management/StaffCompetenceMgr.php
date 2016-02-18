<?php

namespace Justsy\BaseBundle\Management;

class StaffCompetenceMgr {

    private $conn = null;
    private $conn_im = null;

    public function __construct($_db, $_db_im) {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
    }

    //获取指定企业的指定权限人员信息 $eno企业号  $roleid权限
    public function Get($eno, $roleid) {
        $sql = "select group_concat(employeeid) as employeeid from im_employeerole where employeeid like concat('%-',?,'@fafacn.com') and roleid=?";
        $ds = $this->conn_im->GetData("result", $sql, array((string) $eno, (string) $roleid));
        $jids = $ds["result"]["rows"][0]["employeeid"];
        $listsql = "select login_account,nick_name from we_staff where fafa_jid in ";
        $jidin = "";
        $jidparas = array();
        foreach (explode(",", $jids) as $val) {
            if ($jidin != "")
                $jidin = $jidin . ",?";
            else
                $jidin = $jidin . "?";
            array_push($jidparas, (string) $val);
        }
        $listsql = $listsql . "(" . $jidin . ") and eno=?";
        array_push($jidparas, (string) $eno);
        $userlist = $this->conn->GetData("result", $listsql, $jidparas);
        return $userlist["result"]["rows"];
    }

    //设置指定企业的指定权限人员信息 $eno企业号  $roleid权限  $managrlist人员帐号集合
    public function Set($eno, $roleid, $managrlist) {
        $jidsql = "SELECT group_concat(T1.fafa_jid) AS fafa_jid FROM we_staff T1  WHERE T1.login_account IN ";
        $jidparas = array();
        $jidin = "";
        $jidlist=array();
        $jid='';
        if(empty($managrlist)){
        	
        }
        else{
	        foreach (explode(";", $managrlist) as $val) {
	            if ($jidin != "")
	                $jidin = $jidin . ",?";
	            else
	                $jidin = $jidin . "?";
	            array_push($jidparas, (string) $val);
	        }
	        $jidsql = $jidsql . "(" . $jidin . ") AND T1.eno=?";
	        array_push($jidparas, (string) $eno);
	        $jidlist = $this->conn->GetData("result", $jidsql, $jidparas);
	        $jid = $jidlist["result"]["rows"][0]["fafa_jid"];
        }
        $sqls = array();
        $paras = array();
        $sqls[] = "delete from im_employeerole where roleid=? and employeeid like concat('%-',?,'@fafacn.com') ";
        $paras[] = array((string) $roleid, (string) $eno);
        foreach (explode(",", $jid) as $val) {
        	  if(empty($val)) continue;
            $sqls[] = "insert into im_employeerole(employeeid,roleid) values(?,?)";
            $paras[] = array((string) $val, (string) $roleid);
        }
        return $this->conn_im->ExecSQLs($sqls, $paras);
    }

}
