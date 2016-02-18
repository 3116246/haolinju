<?php

namespace Justsy\InterfaceBundle\SsoAuth;

interface ISsoAuth
{
	//返回操作的returncode代码，当代码为0000时表示认证成功，并返回用户在wefafa中的openid，login_account以及jid属性
    public static function userAuthAction($container,$request, $con,$con_im,$user,$pass,$comeform);
}
