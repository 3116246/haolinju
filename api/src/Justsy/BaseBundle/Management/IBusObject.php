<?php

namespace Justsy\BaseBundle\Management;

////所有业务对象必须实现该接口
interface IBusObject
{
	//实现方法中应该调用对象本身的构造函数，返回对象本身
    public function getInstance($container);
}
