<?php

namespace Justsy\BaseBundle\Login;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;

class UserProvider implements UserProviderInterface
{
  protected $container;
 
  
  public function __construct($container)
  {
    $this->container = $container;
  }

  public function loadUserByUsernameWithMobile($username)
  {
	$DataAccess = $this->container->get('we_data_access');
  	$staff  = new \Justsy\BaseBundle\Management\Staff($DataAccess,$this->container->get('we_data_access_im'),$username,$this->container->get("logger"),$this->container);
  	$dataset = $staff->getInfoByMobileLogin();
  	if(!empty($dataset))
  	{
		return $staff->getSessionUser($dataset);
  	}
  	else
  	{
  		throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.',$username));
  	}
  }

  //$comeform ：登录来源
  public function loadUserByUsername($username,$comeform="")
  {
  	if(Utils::validateMobile($username))
  	{
  		//手机登录
  		return $this->loadUserByUsernameWithMobile($username);
  	}
    $DataAccess = $this->container->get('we_data_access');
    $staff  = new \Justsy\BaseBundle\Management\Staff($DataAccess,$this->container->get('we_data_access_im'),$username,$this->container->get("logger"),$this->container);
  	$us = $staff->getInfo();
  	if(!empty($us))
  	{
  		$us = $staff->getSessionUser($us);
    	return $us;
    }
    else
    {
      throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.',$username));
    }
  }

  public function refreshUser(UserInterface $user)
  {
    if (!$user instanceof UserSession)
    {
      throw new UnsupportedUserException(sprintf('Instances of "%s" are notsupported.', get_class($user)));
    }
    return $this->loadUserByUsername($user->getUsername());
  }

  public function supportsClass($class)
  {
    return $class === 'Justsy\BaseBundle\Login\UserSession';
  }    
}