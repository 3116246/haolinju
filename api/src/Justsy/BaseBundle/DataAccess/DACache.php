<?php

namespace Justsy\BaseBundle\DataAccess;
use \Memcached;

class DACache
{
    protected $container;
    protected $logger;
    protected $memcachedServers = null; //[[host, port(, weight)], ...], 以JSON格式配置在parameters.ini的memcached_servers参数中
    protected $mc;

    public function __construct($container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');

        try {
            $memcached_servers = $this->container->getParameter("memcached_servers");
            if (!empty($memcached_servers))
                $this->memcachedServers = json_decode($memcached_servers, true);
        } catch (\InvalidArgumentException $e) {            
        }

        if ($this->hasMemcached())
        {
            $this->mc = new Memcached('WeSnsMemcachedPool');
            $this->mc->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            $this->mc->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            if (!count($this->mc->getServerList())) 
            {
                $this->mc->addServers($this->memcachedServers);
            }
        }
    }

    private function hasMemcached()
    {
        if ($this->memcachedServers == null || empty($this->memcachedServers) || 0 == count($this->memcachedServers)) return false;
        if (!class_exists("Memcached")) return false;

        return true;
    }

    // 根据一级缓存的KEY，取出对应缓存值，KEY由【F_功能名_关键字】构成
    public function get($keyModule, $keyItem)
    {
        if (!$this->hasMemcached()) return null;

        $key = "F_${keyModule}_$keyItem";

        $re = $this->mc->get($key);
        if ($this->mc->getResultCode() == Memcached::RES_SUCCESS)
            return $re;
        else
            return null;
    }

    // 根据功能名、关键字、依赖的表名、主键值设置缓存
    // 功能名、关键字构成一级缓存的KEY【F_功能名_关键字】
    // 依赖的表名、主键值构成二级缓存的KEY【T_表名_主键值】
    // $arrDependcyTableKeyValue: [{tablename=>[主键值, ...]}, ...]
    public function set($keyModule, $keyItem, $value, $arrDependcyTableKeyValue)
    {
        if (!$this->hasMemcached()) return false;

        $onekey = "F_${keyModule}_$keyItem";
        $arrCache = array();
        $arrCache[$onekey] = $value;

        foreach ($arrDependcyTableKeyValue as $tablename => &$keyvalues) 
        {
            foreach ($keyvalues as &$valueitem) 
            {
                $twokey = "T_${tablename}_$valueitem";
                $twokeyvalue = $this->mc->get($twokey);
                if ($twokeyvalue)
                {
                    if (!array_key_exists($onekey, $twokeyvalue)) $twokeyvalue[] = $onekey;
                    $arrCache[$twokey] = $twokeyvalue;
                }
                else
                {
                    $arrCache[$twokey] = array($onekey);
                }
            }
        }

        return $this->mc->setMulti($arrCache, 30*60);
    }

    // 根据表名、主键值删除其缓存
    public function del($dependcyTable, $dependcyKeyValue)
    {
        if (!$this->hasMemcached()) return false;

        $twokey = "T_${dependcyTable}_$dependcyKeyValue";
        $twokeyvalue = $this->mc->get($twokey);
        if ($twokeyvalue)
        {
            foreach ($twokeyvalue as &$value) {
                $this->mc->delete($value);
            }
            $this->mc->delete($twokey);
        }

        return true;
    }
}
