<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Enterprise;
use Justsy\BaseBundle\Management\Staff;

class SsoModules{
	public static $modules=array(
		array(
			'module_name'=>'携程',
			'bind_type'=>'ctrip',
			'module_code'=>'XiechengController'
		),
		array(
			'module_name'=>'优选',
			'bind_type'=>'ecstore',
			'module_code'=>'EstoreController'
		)
	);
}
?>