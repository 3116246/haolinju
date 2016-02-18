<?php

namespace Justsy\OpenAPIBundle\Controller;


interface ISso
{
    public static function ssoAction($controller,$con,$appid,$openid,$token,$encrypt);
    //返回一个数组
    //数据必须包含属性access_token或者token
    //如果token获取失败时，把失败描述信息存放到error属性
    public static function tokenAction($controller,$con,$appid,$openid,$encrypt);
    public static function bindTitleAction($controller,$con,$appid,$openid,$encrypt);
    public static function directUrlAction($container);
    public static function bindAction($controller,$con,$appid,$openid,$params);
    public static function bindBatAction($controller,$con,$appid,$eno,$encrypt,$params);
    public static function rest($controller,$user,$re,$parameters,$need_params);
}