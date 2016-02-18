<?php

namespace Justsy\InterfaceBundle\Common;

class ReturnCode
{
  public static $SUCCESS = '0000';
  public static $NOTLOGIN = '0001';
  public static $ERROFUSERORPWD = '0002';
  public static $NOTAUTHORIZED = '0003';
  public static $OTHERERROR = '0004';
  public static $NOTACCESS = '0005';  //无权限
  public static $SYSERROR = '9999';
  public static $OUTOFRANGE= '0006';//超出限制
}
