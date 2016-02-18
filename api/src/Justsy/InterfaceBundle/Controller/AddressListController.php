<?php
namespace Justsy\InterfaceBundle\Controller;

use Doctrine\ODM\MongoDB\Mapping\Annotations\String;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;

class AddressListController extends Controller
{
    public function getAddrTypeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $sql = "select typeid,typename,mid_time from we_addrlist_type WHERE owner =? and status<>'delete'";
            $params = array();
            $params[] = (string) $account;
            $ds = $da->GetData("we_addrlist_type", $sql, $params);
            $re['addrlist_types'] = $this->getAddrType($ds['we_addrlist_type']['rows']);
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function insertAddrTypeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $addrTypeName = $request->get("addrtype_name");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($addrTypeName)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $addrTypeId = $this->genAddrTypeId();
            $sql = "select typeid,typename,mid_time from we_addrlist_type where typename=? and owner=?";
            $das = $da->GetData("we_addrlist_type", $sql,array((string) $addrTypeName, (string) $account));
            $count = $das['we_addrlist_type']['recordcount'];
            if (!empty($count) && $count != 0) {
                $row = $das['we_addrlist_type']['rows'][0];
                $re['addrlist_type'] = array('addrtype_id' => $row['typeid'],'addrtype_name' => $row['typename'],'mid_time' => $row['mid_time']);
            } else {
                $mid_time = $this->getMillisecond();
                $sql = "insert into we_addrlist_type  values (?,?,?,?,?)";
                $params = array();
                $params[] = (string) $addrTypeId;
                $params[] = (string) $addrTypeName;
                $params[] = (string) $account;
                $params[] = (string) $mid_time;
                $params[] = (string) 'add';
                $ds = $da->ExecSQL($sql, $params);
                if (!empty($ds) && $ds != -1) {
                    $re['addrlist_type'] = array('addrtype_id' => $addrTypeId,'addrtype_name' => $addrTypeName,'mid_time' => $mid_time);
                }
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function updateAddrTypeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $addrTypeJson = $request->get("addrlist_type");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($addrTypeJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $json = json_decode($addrTypeJson);
            $addrType = $this->jsonToAddrtypeList($json);
            if (empty($addrType['addrtype_id'])) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $sql = "select typeid,typename,status,mid_time from we_addrlist_type where typeid=? and owner=?";
            $das = $da->GetData("we_addrlist_type", $sql,array((string) $addrType['addrtype_id'],(string) $account));
            $count = $das['we_addrlist_type']['recordcount'];
            if (!empty($count) && $count != 0) {
                $row = $das['we_addrlist_type']['rows'][0];
                if ($row['status'] != 'delete'&& (float) $row['mid_time']< (float) $addrType['mid_time']) {
                    $sql = "update we_addrlist_type set typename=? ,mid_time=?,status=? where typeid=? and owner=?";
                    $params = array();
                    $params[] = (string) $addrType['addrtype_name'];
                    $params[] = (string) $addrType['mid_time'];
                    $params[] = (string) 'update';
                    $params[] = (string) $addrType['addrtype_id'];
                    $params[] = (string) $account;
                    $ds = $da->ExecSQL($sql, $params);
                    $re['addrlist_type'] = array('addrtype_id' => $addrType['addrtype_id'],'addrtype_name' => $addrType['addrtype_name'],'mid_time' => $addrType['mid_time']);
                } else if ($row['status'] == 'delete') {
                    $re["returncode"] = ReturnCode::$OTHERERROR;
                } else {
                    $re['addrlist_type'] = array('addrtype_id' => $row['typeid'],'addrtype_name' => $row['typename'],'mid_time' => $row['mid_time']);
                }
            } else {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function delAddrTypeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $addrTypeJson = $request->get("addrlist_type");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($addrTypeJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $json = json_decode($addrTypeJson);
            $addrType = $this->jsonToAddrtypeList($json);
            $sql = "select typeid,typename,status,mid_time from we_addrlist_type where typeid=? and owner=?";
            $params = array();
            $params[] = (string) $addrType['addrtype_id'];
            $params[] = (string) $account;
            $das = $da->GetData('we_addrlist_type', $sql, $params);
            $count = $das['we_addrlist_type']['recordcount'];
            if (!empty($count) && $count != 0) {
                $row = $das['we_addrlist_type']['rows'][0];
                if ($row['status'] == 'delete') {
                    $re["returncode"] = ReturnCode::$SUCCESS;
                } else {
                    $sql = "update  we_addrlist_type set status=? ,mid_time=?  where typeid=? and owner=?";
                    $params = array();
                    $params[] = (string) 'delete';
                    $params[] = (string) $addrType['mid_time'];
                    $params[] = (string) $addrType['addrtype_id'];
                    $params[] = (string) $account;
                    $ds = $da->ExecSQL($sql, $params);
                    if (!empty($ds) && $ds != -1) {
                        $re["returncode"] = ReturnCode::$SUCCESS;
                    } else {
                        $re["returncode"] = ReturnCode::$OTHERERROR;
                        return $this->getResponse($request, $re);
                    }
                }
            } else {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function getOrgVersionAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $sql = "select a.login_account from we_staff a LEFT JOIN we_staff b on (a.eno=b.eno) WHERE b.login_account=? ORDER BY a.login_account";
            $ds = $da->GetData('we_staff', $sql, array($account));
            if ($ds && $ds["we_staff"]["recordcount"] == 0) {
                $re["returncode"] = ReturnCode::$SYSERROR;
            } else {
                $s = '';
                foreach ($ds['we_staff']['rows'] as &$row) {
                    $s .= $row['login_account'];
                }
                $re["version"] = md5($s);
            }

        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function getOrgContactListAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $sql = "select c.nick_name nick_name,d.dept_name organ,c.mobile mobile_phone,c.work_phone phone,
                        c.login_account e_mail,c.login_account login_account,c.fafa_jid fafa_jid,null server_id,null mid_time from (
                        select a.dept_id,a.eno,a.nick_name,a.mobile,a.work_phone,a.login_account,a.fafa_jid 
                        from we_staff a LEFT JOIN we_staff b on (a.eno=b.eno) WHERE b.login_account=?
                        ) c LEFT JOIN we_department  d on (c.dept_id=d.dept_id and c.eno=d.eno)";
            $params = array();
            $params[] = (string) $account;
            $ds = $da->GetData('we_contacts', $sql, $params);
            $re['contacts'] = $this->getContacts($ds['we_contacts']['rows'],array(array('addrtype_id' => 'M002','addrtype_name' => '组织机构','status' => '', 'mid_time' => '')),'', $account);

        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function getContactListAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $sql = "select * from(
                    select addr_name nick_name,addr_unit organ,addr_mobile mobile_phone,addr_phone phone,addr_mail e_mail,'' login_account,'' fafa_jid,
                     b.id server_id,b.mid_time mid_time from we_addrlist_addition b  
                    LEFT JOIN we_addrlist_main a on(a.id=b.id) WHERE  a.OWNER=?
                    union 
                    SELECT e.nick_name nick_name,f.dept_name organ,e.mobile mobile_phone,e.work_phone phone,
                    e.login_account e_mail,e.login_account login_account,e.fafa_jid,'' server_id,max(e.mid_time) mid_time from( 
                    select d.nick_name,d.dept_id,d.eno,d.mobile,d.work_phone,d.login_account,d.fafa_jid,c.mid_time from we_addrlist_main c 
                    left JOIN we_staff d ON(c.addr_account=d.login_account) WHERE   c.owner=? and c.addr_account<>''
                    ) e LEFT JOIN we_department f on(e.dept_id=f.dept_id and e.eno=f.eno) WHERE e.nick_name is not null
                    ) g WHERE g.nick_name is not null";
            $params = array();
            $params[] = (string) $account;
            $params[] = (string) $account;
            $ds = $da->GetData('we_contacts', $sql, $params);
            $re['contacts'] = $this->getContacts($ds['we_contacts']['rows'], array(), '',$account);
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function storeContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactJson = $request->get("contact");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $json = json_decode($contactJson);
            $contact = $this->jsonToContact($json);

            $querySql = "select id,addr_account,typeid,status,mid_time from we_addrlist_main where typeid=? and owner=? ";
            $queryParams = array();
            $queryParams[] = (string) 'M001';
            $queryParams[] = (string) $account;
            $insertSql = "insert into we_addrlist_main (id,owner,addr_account,typeid,status,mid_time) values (?,?,?,?,?,?)";
            $params = array();
            if (!empty($contact['login_account'])) {
                $querySql .= " and addr_account=?";
                $queryParams[] = (string) $contact['login_account'];
                $ds = $da->GetData("we_addrlist_main", $querySql, $queryParams);
                $count = $ds['we_addrlist_main']['recordcount'];
                if ($count && !empty($count) && $count != -1) {
                    $status = $ds['we_addrlist_main']['rows'][0]['status'];
                    if ($status == 'delete') {
                        $insertSql = "update we_addrlist_main set status=?,mid_time=? where typeid=? and addr_account=? and owner=?";
                        $params[] = (string) 'add';
                        $params[] = (string) $contact['mid_time'];
                        $params[] = (string) 'M001';
                        $params[] = (string) $contact['login_account'];
                        $params[] = (string) $account;
                        $ds = $da->ExecSQL($insertSql, $params);
                        if (!empty($ds) && $ds != -1) {
                            $re["returncode"] = ReturnCode::$SUCCESS;
                        } else {
                            $re["returncode"] = ReturnCode::$OTHERERROR;
                        }
                    }
                } else {
                    $params[] = (string) '';
                    $params[] = (string) $account;
                    $params[] = (string) $contact['login_account'];
                    $params[] = (string) 'M001';
                    $params[] = (string) 'add';
                    $params[] = (string) $contact['mid_time'];
                    $ds = $da->ExecSQL($insertSql, $params);
                    if (!empty($ds) && $ds != -1) {
                        $re["returncode"] = ReturnCode::$SUCCESS;
                    } else {
                        $re["returncode"] = ReturnCode::$OTHERERROR;
                    }
                }
            } else {
                $querySql .= " and id=?";
                $queryParams[] = (string) $contact['server_id'];
                $ds = $da->GetData("we_addrlist_main", $querySql, $queryParams);
                $count = $ds['we_addrlist_main']['recordcount'];
                if ($count && !empty($count) && $count != -1) {
                    $status = $ds['we_addrlist_main']['rows'][0]['status'];
                    if ($status == 'delete') {
                        $insertSql = "update we_addrlist_main set status=?,mid_time=? where typeid=? and id=? and owner=?";
                        $params[] = (string) 'add';
                        $params[] = (string) $contact['mid_time'];
                        $params[] = (string) 'M001';
                        $params[] = (string) $contact['server_id'];
                        $params[] = (string) $account;
                        $ds = $da->ExecSQL($insertSql, $params);
                        if (!empty($ds) && $ds != -1) {
                            $re["returncode"] = ReturnCode::$SUCCESS;
                        } else {
                            $re["returncode"] = ReturnCode::$OTHERERROR;
                        }
                    }
                } else {
                    $params[] = (string) $contact['server_id'];
                    $params[] = (string) $account;
                    $params[] = (string) '';
                    $params[] = (string) 'M001';
                    $params[] = (string) 'add';
                    $params[] = (string) $contact['mid_time'];
                    $ds = $da->ExecSQL($insertSql, $params);
                    if (!empty($ds) && $ds != -1) {
                        $re["returncode"] = ReturnCode::$SUCCESS;
                    } else {
                        $re["returncode"] = ReturnCode::$OTHERERROR;
                    }
                }
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function unstoreContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactJson = $request->get("contact");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $json = json_decode($contactJson);
            $contact = $this->jsonToContact($json);
            $querySql = "select id,addr_account,typeid,status,mid_time from we_addrlist_main where typeid=? and owner=? ";
            $insertSql = "update we_addrlist_main set status=?,mid_time=? where typeid=? and owner=?";
            $queryParams = array();
            $queryParams[] = (string) 'M001';
            $queryParams[] = (string) $account;
            $params = array();
            if (!empty($contact['login_account'])) {
                $querySql .= " and addr_account=?";
                $queryParams[] = (string) $contact['login_account'];
                $ds = $da->GetData("we_addrlist_main", $querySql, $queryParams);
                $count = $ds['we_addrlist_main']['recordcount'];
                if ($count && !empty($count) && $count != -1) {
                    $status = $ds['we_addrlist_main']['rows'][0]['status'];
                    if ($status != 'delete') {
                        $insertSql .= " and addr_account=?";
                        $params[] = (string) 'delete';
                        $params[] = (string) $contact['mid_time'];
                        $params[] = (string) 'M001';
                        $params[] = (string) $account;
                        $params[] = (string) $contact['login_account'];
                        $ds = $da->ExecSQL($insertSql, $params);
                        if (!empty($ds) && $ds != -1) {
                            $re["returncode"] = ReturnCode::$SUCCESS;
                        } else {
                            $re["returncode"] = ReturnCode::$OTHERERROR;
                        }
                    } else {
                        $re["returncode"] = ReturnCode::$SUCCESS;
                    }
                } else {
                    $re["returncode"] = ReturnCode::$OTHERERROR;
                }
            } else {
                $querySql .= " and id=?";
                $queryParams[] = (string) $contact['server_id'];
                $ds = $da->GetData("we_addrlist_main", $querySql, $queryParams);
                $count = $ds['we_addrlist_main']['recordcount'];
                if ($count && !empty($count) && $count != 0) {
                    $status = $ds['we_addrlist_main']['rows'][0]['status'];
                    if ($status != 'delete') {
                        $insertSql .= " and id=?";
                        $params[] = (string) 'delete';
                        $params[] = (string) $contact['mid_time'];
                        $params[] = (string) 'M001';
                        $params[] = (string) $account;
                        $params[] = (string) $contact['server_id'];
                        $ds = $da->ExecSQL($insertSql, $params);
                        if (!empty($ds) && $ds != -1) {
                            $re["returncode"] = ReturnCode::$SUCCESS;
                        } else {
                            $re["returncode"] = ReturnCode::$OTHERERROR;
                        }
                    } else {
                        $re["returncode"] = ReturnCode::$SUCCESS;
                    }
                } else {
                    $re["returncode"] = ReturnCode::$OTHERERROR;
                }
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function insertContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactJson = $request->get("contact");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            
            $json = json_decode($contactJson);
            $contact = $this->jsonToContact($json);
            $types=$contact['addrtypes'];
            $wefafaRes=$this->checkWefafaRecord($contact, "wefafa");
            $additionRes=$this->checkAdditionRecord($contact, $account, "addition");
            if($wefafaRes&&!empty($wefafaRes)&&$additionRes&&!empty($additionRes)){
                $countWefafa=$wefafaRes['wefafa']['recordcount'];
                $countAddition=$additionRes['addition']['recordcount'];
                //addition有记录，不是wefafa用户
                if($countAddition!=0&&$countWefafa==0){
                    if((float)$contact['mid_time']<(float)$additionRes['addition']['rows'][0]['mid_time']){
                        $re['contact']=$this->getContact($additionRes['addition']['rows'][0],array(), $contact['local_id'], $account);
                    }else{
                        $id=$additionRes['addition']['rows'][0]['server_id'];
                        $this->updateAddition('add', $contact['mid_time'], $id, $account);
                        foreach ($types as $type){
                            $typeid=$type['addrtype_id'];
                            $ds=$this->getRelation($id, null, $typeid, $account, "relation");
                            if($ds!=null&&$ds['relation']['recordcount']!=0){
                                $this->updateAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
                            }else{
                                $this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
                            }
                        }
                        $contact['server_id']=$id;
                        $re['contact']=$this->getContact($contact,array(), $contact['local_id'], $account);
                    }
                }
                //addition有记录，是wefafa用户
                else if($countAddition!=0&&$countWefafa!=0){
                    $loginAccount=$wefafaRes['we_staff']['rows'][0]['login_account'];
                    $id=$additionRes['addition']['rows'][0]['server_id'];
                    $this->updateAddition('delete', $contact['mid_time'], $id, $account);
                    $sql="update we_addrlist_main set status=?,mid_time=? where id=? and owner=?";
                    $params=array();
                    $params[]=(string)'delete';
                    $params[]=(string)$contact['mid_time'];
                    $params[]=(string)$id;
                    $params[]=(string)$account;
                    $da->ExecSQL($sql, $params);
                     foreach ($types as $type){
                            $typeid=$type['addrtype_id'];
                            $ds=$this->getRelation(null, $loginAccount, $typeid, $account, "relation");
                            if($ds!=null&&$ds['relation']['recordcount']!=0){
                                $this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
                            }else{
                                $this->insertAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
                            }
                      }
                    $re['contact']=$this->getContact($wefafaRes['we_staff']['rows'][0],array(), $contact['local_id'], $account);
                }
                //addition无记录，不是wefafa用户
                else if($countAddition==0&&$countWefafa==0){
                    $id=SysSeq::GetSeqNextValue($da,"we_addrlist_addition", "id");
                    $this->insertAddition($id, $account, $contact['nick_name'], $contact['organ'], $contact['phone'], $contact['mobile_phone'], $contact['e_mail'], $contact['mid_time'], 'add');
                    foreach ($types as $type){
                        $typeid=$type['addrtype_id'];
                        $this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
                    }
                    $contact['server_id']=$id;
                    $re['contact']=$this->getContact($contact,array(), $contact['local_id'], $account);
                }
                //addition无记录，是wefafa用户
                else if($countAddition==0&&$countWefafa!=0){
                    foreach ($types as $type){
                        $typeid=$type['addrtype_id'];
                        $ds=$this->getRelation(null, $loginAccount, $typeid, $account, "relation");
                        if($ds!=null&&$ds['relation']['recordcount']!=0){
                            $this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
                        }else{
                            $this->insertAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
                        }
                    }
                    $re['contact']=$this->getContact($wefafaRes['we_staff']['rows'][0],array(), $contact['local_id'], $account);
                }
            }else{
                $id=SysSeq::GetSeqNextValue($da,"we_addrlist_addition", "id");
                $this->insertAddition($id, $account, $contact['nick_name'], $contact['organ'], $contact['phone'], $contact['mobile_phone'], $contact['e_mail'], $contact['mid_time'], $contact['status']);
                foreach ($types as $type){
                    $typeid=$type['addrtype_id'];
                    $this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
                }
                $contact['server_id']=$id;
                $re['contact']=$this->getContact($contact,array(), $contact['local_id'], $account);
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function updateContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactJson = $request->get("contact");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $json = json_decode($contactJson);
            $contact = $this->jsonToContact($json);
            $addrtypes =$contact['addrtypes'];
            if (!empty($contact['server_id'])) {
                $ds=$this->checkAdditionIdRecord( $contact['server_id'], $account, "addition");
                if ((float) $ds['addition']['rows'][0]['mid_time']< (float)$contact['mid_time']) {
                    $this->updateAdditionAll($contact['server_id'], $account, $contact['nick_name'], $contact['organ'], $contact['phone'], $contact['mobile_phone'], $contact['e_mail'], $contact['mid_time'], 'update');
                    $sql="update we_addrlist_main set status=?,mid_time=? where id=? and owner=?";
                    $params=array();
                    $params[]=(string)'delete';
                    $params[]=(string)$contact['mid_time'];
                    $params[]=(string)$id;
                    $params[]=(string)$account;
                    $da->ExecSQL($sql, $params);
                    foreach ($addrtypes as $addrtype) {
                     $typeid=$addrtype['addrtype_id'];
                        $ds=$this->getRelation($contact['server_id'], null, $typeid, $account, "relation");
                        if($ds!=null&&$ds['relation']['recordcount']!=0){
                            $this->updateAddrlistMain($contact['server_id'], $account, null, $typeid,'add', $contact['mid_time']);
                        }else{
                            $this->insertAddrlistMain($contact['server_id'], $account, null, $typeid,'add', $contact['mid_time']);
                        }
                    }
                    $re['contact'] = $this->getContact($contact, array(), $contact['local_id'],$account);
                } else {
                    $re['contact'] = $this->getContact($ds['addition']['rows'][0], array(),$contact['local_id'], $account);
                }
            } else if (!empty($contact['login_account'])) {
                    $sql="update we_addrlist_main set status=?,mid_time=? where addr_account=? and owner=?";
                    $params=array();
                    $params[]=(string)'delete';
                    $params[]=(string)$contact['mid_time'];
                    $params[]=(string)$contact['login_account'];
                    $params[]=(string)$account;
                    $da->ExecSQL($sql, $params);
                    foreach ($addrtypes as $addrtype) {
                        $typeid=$addrtype['addrtype_id'];
                        $ds=$this->getRelation(null, $contact['login_account'], $typeid, $account, "relation");
                        if($ds!=null&&$ds['relation']['recordcount']!=0){
                            $this->updateAddrlistMain(null, $account, $contact['login_account'], $typeid,'add', $contact['mid_time']);
                        }else{
                            $this->insertAddrlistMain(null, $account, $contact['login_account'], $typeid,'add', $contact['mid_time']);
                        }
                    }
                $re['contact'] = $this->getContact($contact, array(), $contact['local_id'],$account);
            } else {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }
        return $this->getResponse($request, $re);
    }

    public function delContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactJson = $request->get("contact");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $json = json_decode($contactJson);
            $contact = $this->jsonToContact($json);
            $addrtypes = $contact['addrtypes'];
            if (!empty($contact['server_id'])) {
            	$id=$contact['server_id'];
                $sql = "select id,typeid,owner,status from we_addrlist_main where id=? and owner=?";
                $pars = array();
                $pars[] = (string)$id;
                $pars[] =(string)$account;
                $ds = $da->GetData("we_addrlist_main", $sql, $pars);
                $arrayTypes=array();
                $arrayMains=array();
                $arrayConver=array();
                foreach ($ds['we_addrlist_main']['rows'] as &$row) {
                	$arrayMains[]=$row['typeid'];
                }
                foreach ($addrtypes as $type){
                	$arrayTypes[]=$type['addrtype_id'];
                }
                $arrayConver=array_diff($arrayMains,$arrayTypes);
                foreach ($arrayConver as $typeid){
                	$ds=$this->getRelation($id,null, $typeid, $account, "relation");
                	if($ds!=null&&$ds['relation']['recordcount']!=0){
                		if($ds['relation']['rows'][0]['status']!='delete'){
                			$this->updateAddrlistMain($id, $account, null, $typeid,'delete', $contact['mid_time']);
                		}
                	}else{
                		$this->updateAddrlistMain($id, $account,null, $typeid,'delete', $contact['mid_time']);
                	}
                }
                $querySql = "select id,typeid,owner,status from we_addrlist_main where id=? and owner=? and status<>'delete'";
                $params = array();
                $params[] = (string)$id;
                $params[] = (string)$account;
                $ds = $da->GetData("main", $querySql, $params);
                $count=$ds['main']['recordcount'];
                if($count==0){
                	$this->updateAddition('delete', $contact['mid_time'], $id, $account);
                }
                else if($count==1){
                	if($ds['main']['rows'][0]['typeid']=='M001'){
                		$this->updateAddrlistMain($id, $account,null, 'M001','delete', $contact['mid_time']);
                		$this->updateAddition('delete', $contact['mid_time'], $id, $account);
                	}
                }
                $re['contact'] = $this->getContact($contact, array(), $contact['local_id'],$account);
            } else if (!empty($contact['login_account'])) {
            	$loginAccount=$contact['login_account'];
             	$sql = "select id,typeid,owner,status from we_addrlist_main where addr_account=? and owner=?";
                $pars = array();
                $pars[] = (string)$loginAccount;
                $pars[] = (string)$account;
                $ds = $da->GetData("we_addrlist_main", $sql, $pars);
                $arrayTypes=array();
                $arrayMains=array();
                $arrayConver=array();
                foreach ($ds['we_addrlist_main']['rows'] as &$row) {
                	$arrayMains[]=$row['typeid'];
                }
                foreach ($addrtypes as $type){
                	$arrayTypes[]=$type['addrtype_id'];
                }
                $arrayConver=array_diff($arrayMains,$arrayTypes);
                foreach ($arrayConver as $typeid){
                	$ds=$this->getRelation(null,$loginAccount, $typeid, $account, "relation");
                	if($ds!=null&&$ds['relation']['recordcount']!=0){
                		if($ds['relation']['rows'][0]['status']!='delete'){
                			$this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'delete', $contact['mid_time']);
                		}
                	}else{
                		$this->updateAddrlistMain(null, $account,$loginAccount, $typeid,'delete', $contact['mid_time']);
                	}
                }
                $querySql = "select id,typeid,owner,status from we_addrlist_main where addr_account=? and owner=? and status<>'delete'";
                $params = array();
                $params[] = (string)$loginAccount;
                $params[] = $account;
                $ds = $da->GetData("main", $querySql, $params);
                $count=$ds['main']['recordcount'];
               if($count==1){
                	if($ds['main']['rows'][0]['typeid']=='M001'){
                		$this->updateAddrlistMain(null, $account,$loginAccount, 'M001','delete', $contact['mid_time']);
                	}
                }
                $re['contact'] = $this->getContact($contact, array(), $contact['local_id'],$account);
            } else {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function uploadContactAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        $contactsJson = $request->get("contacts");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            if (empty($contactJson)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            
            $json = json_decode($contactsJson);
            $contacts = array();
            if (!empty($json)) $contacts=$this->jsonToContacts($json);
            foreach ($contacts as $contact){
            	$types=$contact['addrtypes'];
            	if(!empty($contact['server_id'])){
            		$wefafaRes=$this->checkWefafaRecord($contact, "wefafa");
            		$additionRes=$this->checkAdditionRecord($contact, $account, "addition");
            		if($wefafaRes&&!empty($wefafaRes)&&$additionRes&&!empty($additionRes)){
            			$countWefafa=$wefafaRes['wefafa']['recordcount'];
            			$countAddition=$additionRes['addition']['recordcount'];
            			//addition有记录，不是wefafa用户
            			if($countAddition!=0&&$countWefafa==0){
            				if((float)$contact['mid_time']>(float)$additionRes['addition']['rows'][0]['mid_time']){
            					$id=$additionRes['addition']['rows'][0]['server_id'];
            					$this->updateAddition('update', $contact['mid_time'], $id, $account);
            					foreach ($types as $type){
            						$typeid=$type['addrtype_id'];
            						$ds=$this->getRelation($id, null, $typeid, $account, "relation");
            						if($ds!=null&&$ds['relation']['recordcount']!=0){
            							$this->updateAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
            						}else{
            							$this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
            						}
            					}
            				}
            			}
            			//addition有记录，是wefafa用户
            			else if($countAddition!=0&&$countWefafa!=0){
            				$loginAccount=$wefafaRes['we_staff']['rows'][0]['login_account'];
            				$id=$additionRes['addition']['rows'][0]['server_id'];
            				$this->updateAddition('delete', $contact['mid_time'], $id, $account);
            				$sql="update we_addrlist_main set status=?,mid_time=? where id=? and owner=?";
            				$params=array();
            				$params[]=(string)'delete';
            				$params[]=(string)$contact['mid_time'];
            				$params[]=(string)$id;
            				$params[]=(string)$account;
            				$da->ExecSQL($sql, $params);
            				foreach ($types as $type){
            					$typeid=$type['addrtype_id'];
            					$ds=$this->getRelation(null, $loginAccount, $typeid, $account, "relation");
            					if($ds!=null&&$ds['relation']['recordcount']!=0){
            						$this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            					}else{
            						$this->insertAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            					}
            				}
            			}
            			//addition无记录，不是wefafa用户
            			else if($countAddition==0&&$countWefafa==0){
            				$id=SysSeq::GetSeqNextValue($da,"we_addrlist_addition", "id");
            				$this->insertAddition($id, $account, $contact['nick_name'], $contact['organ'], $contact['phone'], $contact['mobile_phone'], $contact['e_mail'], $contact['mid_time'], 'add');
            				foreach ($types as $type){
            					$typeid=$type['addrtype_id'];
            					$this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
            				}
            			}
            			//addition无记录，是wefafa用户
            			else if($countAddition==0&&$countWefafa!=0){
            				foreach ($types as $type){
            					$typeid=$type['addrtype_id'];
            					$ds=$this->getRelation(null, $loginAccount, $typeid, $account, "relation");
            					if($ds!=null&&$ds['relation']['recordcount']!=0){
            						$this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            					}else{
            						$this->insertAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            					}
            				}
            			}
            		}else{
            			$id=SysSeq::GetSeqNextValue($da,"we_addrlist_addition", "id");
            			$this->insertAddition($id, $account, $contact['nick_name'], $contact['organ'], $contact['phone'], $contact['mobile_phone'], $contact['e_mail'], $contact['mid_time'], 'add');
            			foreach ($types as $type){
            				$typeid=$type['addrtype_id'];
            				$this->insertAddrlistMain($id, $account, null, $typeid,'add', $contact['mid_time']);
            			}
            		}
            	}else if(!empty($contact['login_account'])){
            		foreach ($types as $type){
            			$typeid=$type['addrtype_id'];
            			$ds=$this->getRelation(null, $loginAccount, $typeid, $account, "relation");
            			if($ds!=null&&$ds['relation']['recordcount']!=0){
            				$this->updateAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            			}else{
            				$this->insertAddrlistMain(null, $account, $loginAccount, $typeid,'add', $contact['mid_time']);
            			}
            		}
            	}
            }
            $re['returncode']=ReturnCode::$SUCCESS;
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function getAddrlistTypeMaxTimeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }
            $sql = "SELECT MAX(mid_time) mid_time from we_addrlist_type WHERE OWNER=?";
            $params = array($account);
            $ds = $da->GetData("we_addrlist_type", $sql, $params);
            $count = $ds['we_addrlist_type']['recordcount'];
            if ($count && $count != -1) {
                $re['maxtime'] = $ds['we_addrlist_type']['rows'][0]['mid_time'];
            } else {
                $re["returncode"] = ReturnCode::$SYSERROR;
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    public function getContactsMaxTimeAction()
    {
        $da = $this->get('we_data_access');
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $account = $request->get("account");
        try {
            if (empty($account)) {
                $re["returncode"] = ReturnCode::$OTHERERROR;
                return $this->getResponse($request, $re);
            }

            $sql = "select MAX(c.mid_time) mid_time from (
                    select MAX(a.mid_time) mid_time from we_addrlist_addition a LEFT JOIN
                    we_addrlist_main b  on(a.id=b.id) where  b.OWNER=?
                    UNION ALL
                    select MAX(mid_time) mid_time from we_addrlist_main WHERE  OWNER=?
                    ) c";
            $params = array();
            $params[] = $account;
            $params[] = $account;
            $ds = $da->GetData("we_addrlist_main", $sql, $params);
            $count = $ds['we_addrlist_main']['recordcount'];
            if ($count && $count != -1) {
                $re['maxtime'] = $ds['we_addrlist_main']['rows'][0]['mid_time'];
            } else {
                $re["returncode"] = ReturnCode::$SYSERROR;
            }
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e);
        }

        return $this->getResponse($request, $re);
    }

    private function getAddrType(&$rows)
    {
        $addrTypes = array();
        foreach ($rows as &$row) {
            $addrType = array();
            $addrType['addrtype_id'] = $row['typeid'];
            $addrType['addrtype_name'] = $row['typename'];
            $addrType['mid_time'] = $row['mid_time'];
            $addrTypes[] = $addrType;
        }
        return $addrTypes;
    }

    private function genAddrTypeId()
    {
        $da = $this->get('we_data_access');
        $re = 'A1';
        try {
            $id=SysSeq::GetSeqNextValue($da,"we_addrlist_type", "typeid");
            $re = 'A'.$id;
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $re;
    }

    private function getContacts(&$rows, array $addtypes = array(), $localId,$account)
    {
        $contacts = array();
        foreach ($rows as &$row) {
            $contacts[] = $this->getContact($row, $addtypes, $localId, $account);
        }
        return $contacts;
    }

    private function getContact($row, array $addtypes = array(), $localId,$account)
    {
        $contact = array();
        if ($addtypes && !empty($addtypes)) {
            $contact['addrtypes'] = $addtypes;
        } else {
            $da = $this->get('we_data_access');
            $addtypes = array();
            $server_id = $row['server_id'];
            $loginAccount = $row['login_account'];
            $sql = "select a.typeid,a.status,a.mid_time,b.typename from we_addrlist_main a left join we_addrlist_type b on(a.typeid=b.typeid) where a.owner=? ";
            $params = array();
            $params[] = (string) $account;
            if ($server_id && !empty($server_id)) {
                $sql .= " and a.id=?";
                $params[] = (string) $server_id;
            } else {
                $sql .= " and a.addr_account=? ";
                $params[] = (string) $loginAccount;
            }
            $sql.=" order by mid_time desc";
            try {
                $ds = $da->GetData("we_addrlist_main", $sql, $params);
                $count = $ds['we_addrlist_main']['recordcount'];
                if ($ds && $count != -1) {
                    foreach ($ds['we_addrlist_main']['rows'] as &$rowtype) {
                        if($rowtype['status']!='delete'){
                            $addtype = array();
                            $addtype['addrtype_id'] = $rowtype['typeid'];
                            $addtype['addrtype_name'] = $rowtype['typename'];
                            $addtype['status'] = $rowtype['status'];
                            $addtype['mid_time'] = $rowtype['mid_time'];
                            $addtypes[] = $addtype;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->get('logger')->err($e);
            }
            $contact['addrtypes'] = $addtypes;
        }
        $contact['nick_name'] = $row['nick_name'];
        $contact['organ'] = $row['organ'];
        $contact['mobile_phone'] = $row['mobile_phone'];
        $contact['phone'] = $row['phone'];
        $contact['e_mail'] = $row['e_mail'];
        $contact['login_account'] = $row['login_account'];
        $contact['fafa_jid'] = $row['fafa_jid'];
        $contact['local_id'] = $localId;
        $contact['server_id'] = $row['server_id'];
        $contact['mid_time'] = $row['mid_time'];
        return $contact;
    }

    private function jsonToContacts($json){
    	$contacts=array();
    	foreach ($json as $contact){
    		$contacts[]=$this->jsonToContact($json);
    	}
    	return $contacts;
    }
    
    private function jsonToContact($json)
    {
        $contact = array();
        if ($json->addrtypes && !empty($json->addrtypes))$contact['addrtypes'] = $this->jsonToAddrtypes($json);
        else $contact['addrtypes'] = array();
        if (!empty($json->nick_name))$contact['nick_name'] = $json->nick_name;
        else $contact['nick_name'] = '';
        if (!empty($json->organ))$contact['organ'] = $json->organ;
        else $contact['organ'] = '';
        if (!empty($json->mobile_phone))$contact['mobile_phone'] = $json->mobile_phone;
        else $contact['mobile_phone'] = '';
        if (!empty($json->phone))$contact['phone'] = $json->phone;
        else $contact['phone'] = '';
        if (!empty($json->e_mail))$contact['e_mail'] = $json->e_mail;
        else $contact['e_mail'] = '';
        if (!empty($json->login_account))$contact['login_account'] = $json->login_account;
        else $contact['login_account'] = '';
        if (!empty($json->fafa_jid))$contact['fafa_jid'] = $json->fafa_jid;
        else $contact['fafa_jid'] = '';
        if (!empty($json->local_id))$contact['local_id'] = $json->local_id;
        else $contact['local_id'] = '';
        if (!empty($json->server_id))$contact['server_id'] = $json->server_id;
        else $contact['server_id'] = '';
        if (!empty($json->mid_time))$contact['mid_time'] = $json->mid_time;
        else $contact['mid_time'] = '';
        return $contact;
    }

    private function jsonToAddrtypes($json)
    {
        $addrtypes = array();
        if ($json->addrtypes[0] && !empty($json->addrtypes[0])) {
            foreach ($json->addrtypes as &$addrtype) {
                $addrtypes[] = $this->jsonToAddrtype($addrtype);
            }
        }
        return $addrtypes;
    }

    private function jsonToAddrtype($json)
    {
        $addrtype = array();
        if (!empty($json->addrtype_id))$addrtype['addrtype_id'] = $json->addrtype_id;
        else $addrtype['addrtype_id'] = '';
        if (!empty($json->addrtype_name))$addrtype['addrtype_name'] = $json->addrtype_name;
        else $addrtype['addrtype_name'] = '';
        if (!empty($json->status))$addrtype['status'] = $json->status;
        else $addrtype['status'] = '';
        if (!empty($json->mid_time))$addrtype['mid_time'] = $json->mid_time;
        else $addrtype['mid_time'] = '';
        return $addrtype;
    }

    private function jsonToAddrtypeList($json)
    {
        $addrtype = array();
        if (!empty($json->addrtype_id))$addrtype['addrtype_id'] = $json->addrtype_id;
        if (!empty($json->addrtype_name))$addrtype['addrtype_name'] = $json->addrtype_name;
        if (!empty($json->mid_time))$addrtype['mid_time'] = $json->mid_time;
        return $addrtype;
    }
    
    /**
     * 查询有无wefafa帐号
     * @param unknown $contact 联系人json对象
     * @param unknown $resultCode 查询数据返回字符
     * @return 查询数据集合
     */
    private function checkWefafaRecord($contact,$resultCode){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $query="select * from(
                    select c.nick_name nick_name,d.dept_name organ,c.mobile mobile_phone,c.work_phone phone,
                            c.login_account e_mail,c.login_account login_account,c.fafa_jid fafa_jid,null server_id,null mid_time from (
                            select a.dept_id,a.eno,a.nick_name,a.mobile,a.work_phone,a.login_account,a.fafa_jid 
                            from we_staff a LEFT JOIN we_staff b on (a.eno=b.eno) WHERE b.login_account=?
                            ) c LEFT JOIN we_department  d on (c.dept_id=d.dept_id and c.eno=d.eno)
                        UNION
                    select c.nick_name nick_name,d.dept_name organ,c.mobile mobile_phone,c.work_phone phone,
                            c.login_account e_mail,c.login_account login_account,c.fafa_jid fafa_jid,null server_id,null mid_time from (
                            select a.dept_id,a.eno,a.nick_name,a.mobile,a.work_phone,a.login_account,a.fafa_jid 
                            from we_staff a LEFT JOIN we_staff b on (a.eno=b.eno) WHERE b.mobile_bind=?
                            ) c LEFT JOIN we_department  d on (c.dept_id=d.dept_id and c.eno=d.eno)
                     UNION
                    select c.nick_name nick_name,d.dept_name organ,c.mobile mobile_phone,c.work_phone phone,
                            c.login_account e_mail,c.login_account login_account,c.fafa_jid fafa_jid,null server_id,null mid_time from (
                            select a.dept_id,a.eno,a.nick_name,a.mobile,a.work_phone,a.login_account,a.fafa_jid 
                            from we_staff a LEFT JOIN we_staff b on (a.eno=b.eno) WHERE b.mobile_bind=?
                            ) c LEFT JOIN we_department  d on (c.dept_id=d.dept_id and c.eno=d.eno)
                    ) e where e.nick_name is not null";
            $params=array();
            $params[]=$contact['e_mail'];
            $params[]=$contact['mobile_phone'];
            $params[]=$contact['phone'];
            $ds=$da->GetData($resultCode, $query, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;        
    }

    /**
     * 查询addition表有无记录
     * @param unknown $contact 联系人json对象
     * @param unknown $account owner
     * * @param unknown $resultCode 查询数据返回字符
     * @return unknown 查询数据集合
     */
    private function checkAdditionRecord($contact, $account,$resultCode){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $query="select id server_id,addr_name nick_name,addr_unit organ,addr_phone phone,addr_mobile mobile_phone,addr_mail e_mail ,null fafa_jid,null login_account,mid_time mid_time ,status status
                   from we_addrlist_addition where addr_name=? and addr_unit=? and addr_phone=? and addr_mobile=? and addr_mail=? and owner=?";
            $params = array();
            $params[] = (string) $contact['nick_name'];
            $params[] = (string) $contact['organ'];
            $params[] = (string) $contact['phone'];
            $params[] = (string) $contact['mobile_phone'];
            $params[] = (string) $contact['e_mail'];
            $params[] = (string) $account;
            $ds=$da->GetData($resultCode, $query, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    
    /**
     * 查询Addition表有无记录
     * @param unknown $id 该数据id
     * @param unknown $account owner
     * * @param unknown $resultCode 查询数据返回字符
     * @return unknown 查询数据集合
     */
    private function checkAdditionIdRecord($id, $account, $resultCode){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $query="select id server_id,addr_name nick_name,addr_unit organ,addr_phone phone,addr_mobile mobile_phone,addr_mail e_mail ,null fafa_jid,null login_account,mid_time mid_time ,status status
                   from we_addrlist_addition where id=? and owner=?";
            $params = array();
            $params[] = (string) $id;
            $params[] = (string) $account;
            $ds=$da->GetData($resultCode, $query, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    
    /**
     * 查询关系是否存在
     * @param unknown $id additionid
     * @param unknown $loginAccount wefafa帐号
     * @param unknown $typeid 类型id
     * @param unknown $account owner
     * @param unknown $resultCode 查询数据返回字符
     * @return 查询数据集合
     */
    private function getRelation($id,$loginAccount,$typeid,$account,$resultCode){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            if($id&&!empty($id)){
                $query="select id,owner,addr_account,typeid,status,mid_time from we_addrlist_main where id=? and typeid=? and owner=?";
                $params = array();
                $params[] = (string) $id;
                $params[] = (string) $typeid;
                $params[] = (string) $account;
                $ds=$da->GetData($resultCode, $query, $params);
            }else {
                $query="select id,owner,addr_account,typeid,status,mid_time from we_addrlist_main where addr_account=? and typeid=? and owner=?";
                $params = array();
                $params[] = (string) $loginAccount;
                $params[] = (string) $typeid;
                $params[] = (string) $account;
                $ds=$da->GetData($resultCode, $query, $params);
            }
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    /**
     * 新增关系 
     * @param unknown $id 
     * @param unknown $owner
     * @param unknown $addr_account
     * @param unknown $typeid
     * @param unknown $status
     * @param unknown $mid_time
     * @return unknown 新增成功条数
     */
    private function insertAddrlistMain($id,$owner,$addr_account,$typeid,$status,$mid_time){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $sql="insert into we_addrlist_main (id,owner,addr_account,typeid,status,mid_time) values (?,?,?,?,?,?)";
            $params=array();
            $params[]=(string)$id;
            $params[]=(string)$owner;
            $params[]=(string)$addr_account;
            $params[]=(string)$typeid;
            $params[]=(string)$status;
            $params[]=(string)$mid_time;
            $ds=$da->ExecSQL($sql, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    /**
     * 新增addition数据
     * @param unknown $id
     * @param unknown $owner
     * @param unknown $addr_name
     * @param unknown $addr_unit
     * @param unknown $addr_phone
     * @param unknown $addr_mobile
     * @param unknown $addr_mail
     * @param unknown $mid_time
     * @param unknown $status
     * @return unknown
     */
    private function insertAddition($id,$owner,$addr_name,$addr_unit,$addr_phone,$addr_mobile,$addr_mail,$mid_time,$status){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $sql="insert into we_addrlist_addition (id,owner,addr_name,addr_unit,addr_phone,addr_mobile,addr_mail,mid_time,status) values (?,?,?,?,?,?,?,?,?)";
            $params=array();
            $params[]=(string)$id;
            $params[]=(string)$owner;
            $params[]=(string)$addr_name;
            $params[]=(string)$addr_unit;
            $params[]=(string)$addr_phone;
            $params[]=(string)$addr_mobile;
            $params[]=(string)$addr_mail;
            $params[]=(string)$mid_time;
            $params[]=(string)$status;
            $ds=$da->ExecSQL($sql, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    /**
     * 修改关系
     * @param unknown $id
     * @param unknown $owner
     * @param unknown $addr_account
     * @param unknown $typeid
     * @param unknown $status
     * @param unknown $mid_time
     * @return unknown 修改成功条数
     */
    private function updateAddrlistMain($id,$owner,$addr_account,$typeid,$status,$mid_time){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            if($id&&!empty($id)){
                $sql="update we_addrlist_main set status=?,mid_time=? where id=? and owner=? and typeid=?";
                $params=array();
                $params[]=(string)$status;
                $params[]=(string)$mid_time;
                $params[]=(string)$id;
                $params[]=(string)$owner;
                $params[]=(string)$typeid;
                $ds=$da->ExecSQL($sql, $params);
            }else{
                $sql="update we_addrlist_main set status=?,mid_time=? where addr_account=? and owner=? and typeid=?";
                $params=array();
                $params[]=(string)$status;
                $params[]=(string)$mid_time;
                $params[]=(string)$addr_account;
                $params[]=(string)$owner;
                $params[]=(string)$typeid;
                $ds=$da->ExecSQL($sql, $params);
            }
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    
   /**
    * 修改addition数据
    * @param unknown $status
    * @param unknown $mid_time
    * @param unknown $id
    * @param unknown $owner
    * @return unknown
    */
    private function updateAddition($status,$mid_time,$id,$owner){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
                $sql="update we_addrlist_addition set status=?,mid_time=? where id=? and owner=? ";
                $params=array();
                $params[]=(string)$status;
                $params[]=(string)$mid_time;
                $params[]=(string)$id;
                $params[]=(string)$owner;
                $ds=$da->ExecSQL($sql, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    
    /**
     * 修改addition数据
     * @param unknown $id
     * @param unknown $owner
     * @param unknown $addr_name
     * @param unknown $addr_unit
     * @param unknown $addr_phone
     * @param unknown $addr_mobile
     * @param unknown $addr_mail
     * @param unknown $mid_time
     * @param unknown $status
     * @return unknown
     */
    private function updateAdditionAll($id,$owner,$addr_name,$addr_unit,$addr_phone,$addr_mobile,$addr_mail,$mid_time,$status){
        $ds=null;
        $da = $this->get('we_data_access');
        try {
            $sql="update we_addrlist_addition set addr_name=?,addr_unit=?,addr_phone=?,addr_mobile=?,addr_mail=?, status=?,mid_time=? where id=? and owner=? ";
            $params=array();
            $params[]=(string)$addr_name;
            $params[]=(string)$addr_unit;
            $params[]=(string)$addr_phone;
            $params[]=(string)$addr_mobile;
            $params[]=(string)$addr_mail;
            $params[]=(string)$status;
            $params[]=(string)$mid_time;
            $params[]=(string)$id;
            $params[]=(string)$owner;
            $ds=$da->ExecSQL($sql, $params);
        } catch (Exception $e) {
            $this->get('logger')->err($e);
        }
        return $ds;
    }
    private function getResponse($request, $re)
    {
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback'). "(" . json_encode($re) . ");": json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    
    public function getMillisecond()
    {
        $time = explode (" ", microtime () );
        $time = ($time [1]+$time [0])* 1000;
        $time = round($time,0);
        return $time;
    }
}
