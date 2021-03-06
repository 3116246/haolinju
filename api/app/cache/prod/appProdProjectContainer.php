<?php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
class appProdProjectContainer extends Container
{
    public function __construct()
    {
        $this->parameters = $this->getDefaultParameters();
        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();
        $this->set('service_container', $this);
        $this->scopes = array('request' => 'container');
        $this->scopeChildren = array('request' => array());
    }
    protected function getAnnotationReaderService()
    {
        return $this->services['annotation_reader'] = new \Doctrine\Common\Annotations\FileCacheReader(new \Doctrine\Common\Annotations\AnnotationReader(), 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/annotations', false);
    }
    protected function getAssetic_AssetManagerService()
    {
        $a = $this->get('templating.loader');
        $this->services['assetic.asset_manager'] = $instance = new \Assetic\Factory\LazyAssetManager($this->get('assetic.asset_factory'), array('twig' => new \Assetic\Factory\Loader\CachedFormulaLoader(new \Assetic\Extension\Twig\TwigFormulaLoader($this->get('twig')), new \Assetic\Cache\ConfigCache('E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/assetic/config'), false)));
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'FrameworkBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/FrameworkBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'FrameworkBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SecurityBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/SecurityBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SecurityBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\SecurityBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'TwigBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/TwigBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'TwigBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\TwigBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'MonologBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/MonologBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'MonologBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\MonologBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SwiftmailerBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/SwiftmailerBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SwiftmailerBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\SwiftmailerBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'DoctrineBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/DoctrineBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'DoctrineBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\DoctrineBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'DoctrineMongoDBBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/DoctrineMongoDBBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'DoctrineMongoDBBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\bundles\\Symfony\\Bundle\\DoctrineMongoDBBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'AsseticBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/AsseticBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'AsseticBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\bundles\\Symfony\\Bundle\\AsseticBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SensioFrameworkExtraBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/SensioFrameworkExtraBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'SensioFrameworkExtraBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\bundles\\Sensio\\Bundle\\FrameworkExtraBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JMSSecurityExtraBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JMSSecurityExtraBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JMSSecurityExtraBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\bundles\\JMS\\SecurityExtraBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyBaseBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JustsyBaseBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyBaseBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\BaseBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyMongoDocBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JustsyMongoDocBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyMongoDocBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\MongoDocBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyInterfaceBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JustsyInterfaceBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyInterfaceBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\InterfaceBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyOpenAPIBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JustsyOpenAPIBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyOpenAPIBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\OpenAPIBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyAdminAppBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/JustsyAdminAppBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'JustsyAdminAppBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\AdminAppBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'WebIMImChatBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/WebIMImChatBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'WebIMImChatBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\WebIM\\ImChatBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\CoalescingDirectoryResource(array(0 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'WebIMImMainBundle', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/WebIMImMainBundle/views', '/\\.[^.]+\\.twig$/'), 1 => new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, 'WebIMImMainBundle', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\WebIM\\ImMainBundle/Resources/views', '/\\.[^.]+\\.twig$/'))), 'twig');
        $instance->addResource(new \Symfony\Bundle\AsseticBundle\Factory\Resource\DirectoryResource($a, '', 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources/views', '/\\.[^.]+\\.twig$/'), 'twig');
        return $instance;
    }
    protected function getAssetic_Filter_CssrewriteService()
    {
        return $this->services['assetic.filter.cssrewrite'] = new \Assetic\Filter\CssRewriteFilter();
    }
    protected function getAssetic_FilterManagerService()
    {
        return $this->services['assetic.filter_manager'] = new \Symfony\Bundle\AsseticBundle\FilterManager($this, array('cssrewrite' => 'assetic.filter.cssrewrite'));
    }
    protected function getCacheWarmerService()
    {
        $a = $this->get('kernel');
        $b = $this->get('templating.name_parser');
        $c = new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder($a, $b, 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources');
        return $this->services['cache_warmer'] = new \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate(array(0 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplatePathsCacheWarmer($c, $this->get('templating.locator')), 1 => new \Symfony\Bundle\AsseticBundle\CacheWarmer\AssetManagerCacheWarmer($this), 2 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\RouterCacheWarmer($this->get('router')), 3 => new \Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer($this, $c), 4 => new \Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer($this->get('doctrine')), 5 => new \Symfony\Bundle\DoctrineMongoDBBundle\CacheWarmer\ProxyCacheWarmer($this), 6 => new \Symfony\Bundle\DoctrineMongoDBBundle\CacheWarmer\HydratorCacheWarmer($this), 7 => new \Symfony\Bundle\AsseticBundle\CacheWarmer\AssetWriterCacheWarmer($this, new \Assetic\AssetWriter('E:/work/Code/Justsy Push-RESTService/Trunk/app/../web'))));
    }
    protected function getDoctrineService()
    {
        return $this->services['doctrine'] = new \Symfony\Bundle\DoctrineBundle\Registry($this, array('default' => 'doctrine.dbal.default_connection', 'im' => 'doctrine.dbal.im_connection'), array('default' => 'doctrine.orm.default_entity_manager'), 'default', 'default');
    }
    protected function getDoctrine_Dbal_ConnectionFactoryService()
    {
        return $this->services['doctrine.dbal.connection_factory'] = new \Symfony\Bundle\DoctrineBundle\ConnectionFactory(array());
    }
    protected function getDoctrine_Dbal_DefaultConnectionService()
    {
        $a = new \Doctrine\Common\EventManager();
        $a->addEventSubscriber(new \Doctrine\DBAL\Event\Listeners\MysqlSessionInit('UTF8'));
        return $this->services['doctrine.dbal.default_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('dbname' => 'justsy_sns', 'host' => '127.0.0.1', 'port' => '3306', 'user' => 'justsy_sns', 'password' => 'justsy_sns', 'driver' => 'pdo_mysql', 'logging' => false, 'driverOptions' => array()), new \Doctrine\DBAL\Configuration(), $a, array());
    }
    protected function getDoctrine_Dbal_ImConnectionService()
    {
        $a = new \Doctrine\Common\EventManager();
        $a->addEventSubscriber(new \Doctrine\DBAL\Event\Listeners\MysqlSessionInit('UTF8'));
        return $this->services['doctrine.dbal.im_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('dbname' => 'justsy_im', 'host' => '127.0.0.1', 'port' => '3306', 'user' => 'justsy_im', 'password' => 'justsy_im', 'driver' => 'pdo_mysql', 'logging' => false, 'driverOptions' => array()), new \Doctrine\DBAL\Configuration(), $a, array());
    }
    protected function getDoctrine_Odm_Mongodb_Cache_ArrayService()
    {
        return $this->services['doctrine.odm.mongodb.cache.array'] = new \Doctrine\Common\Cache\ArrayCache();
    }
    protected function getDoctrine_Odm_Mongodb_DefaultConfigurationService()
    {
        $a = $this->get('annotation_reader');
        $b = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($a, array(0 => 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\MongoDocBundle\\Document', 1 => 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\WebIM\\ImChatBundle\\Document'));
        $c = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $c->addDriver($b, 'Justsy\\MongoDocBundle\\Document');
        $c->addDriver($b, 'WebIM\\ImChatBundle\\Document');
        $this->services['doctrine.odm.mongodb.default_configuration'] = $instance = new \Doctrine\ODM\MongoDB\Configuration();
        $instance->setDocumentNamespaces(array('JustsyMongoDocBundle' => 'Justsy\\MongoDocBundle\\Document', 'WebIMImChatBundle' => 'WebIM\\ImChatBundle\\Document'));
        $instance->setMetadataCacheImpl($this->get('doctrine.odm.mongodb.default_metadata_cache'));
        $instance->setMetadataDriverImpl($c);
        $instance->setProxyDir('E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/odm/mongodb/Proxies');
        $instance->setProxyNamespace('Proxies');
        $instance->setAutoGenerateProxyClasses(false);
        $instance->setHydratorDir('E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/odm/mongodb/Hydrators');
        $instance->setHydratorNamespace('Hydrators');
        $instance->setAutoGenerateHydratorClasses(false);
        $instance->setDefaultDB('we');
        $instance->setRetryConnect(0);
        $instance->setRetryQuery(0);
        return $instance;
    }
    protected function getDoctrine_Odm_Mongodb_DefaultConnectionService()
    {
        return $this->services['doctrine.odm.mongodb.default_connection'] = new \Doctrine\MongoDB\Connection('mongodb://127.0.0.1:27017', array('connect' => true, 'username' => 'we', 'password' => 'we'), $this->get('doctrine.odm.mongodb.default_configuration'));
    }
    protected function getDoctrine_Odm_Mongodb_DefaultDocumentManagerService()
    {
        return $this->services['doctrine.odm.mongodb.default_document_manager'] = call_user_func(array('Doctrine\\ODM\\MongoDB\\DocumentManager', 'create'), $this->get('doctrine.odm.mongodb.default_connection'), $this->get('doctrine.odm.mongodb.default_configuration'), $this->get('doctrine.odm.mongodb.event_manager'));
    }
    protected function getDoctrine_Odm_Mongodb_DefaultMetadataCacheService()
    {
        return $this->services['doctrine.odm.mongodb.default_metadata_cache'] = new \Doctrine\Common\Cache\ArrayCache();
    }
    protected function getDoctrine_Odm_Mongodb_EventManagerService()
    {
        return $this->services['doctrine.odm.mongodb.event_manager'] = new \Doctrine\Common\EventManager();
    }
    protected function getDoctrine_Odm_Mongodb_LoggerService()
    {
        return $this->services['doctrine.odm.mongodb.logger'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Logger\DoctrineMongoDBLogger($this->get('monolog.logger.doctrine'));
    }
    protected function getDoctrine_Odm_Mongodb_Metadata_AnnotationService()
    {
        return $this->services['doctrine.odm.mongodb.metadata.annotation'] = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array());
    }
    protected function getDoctrine_Odm_Mongodb_Metadata_ChainService()
    {
        return $this->services['doctrine.odm.mongodb.metadata.chain'] = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
    }
    protected function getDoctrine_Odm_Mongodb_Metadata_XmlService()
    {
        return $this->services['doctrine.odm.mongodb.metadata.xml'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Mapping\Driver\XmlDriver(array());
    }
    protected function getDoctrine_Odm_Mongodb_Metadata_YmlService()
    {
        return $this->services['doctrine.odm.mongodb.metadata.yml'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Mapping\Driver\YamlDriver(array());
    }
    protected function getDoctrine_Orm_DefaultEntityManagerService()
    {
        $a = new \Doctrine\Common\Cache\ArrayCache();
        $a->setNamespace('sf2orm_default_26631329feb5821c41d812022f4bbefc');
        $b = new \Doctrine\Common\Cache\ArrayCache();
        $b->setNamespace('sf2orm_default_26631329feb5821c41d812022f4bbefc');
        $c = new \Doctrine\Common\Cache\ArrayCache();
        $c->setNamespace('sf2orm_default_26631329feb5821c41d812022f4bbefc');
        $d = new \Doctrine\ORM\Configuration();
        $d->setEntityNamespaces(array());
        $d->setMetadataCacheImpl($a);
        $d->setQueryCacheImpl($b);
        $d->setResultCacheImpl($c);
        $d->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\DriverChain());
        $d->setProxyDir('E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/orm/Proxies');
        $d->setProxyNamespace('Proxies');
        $d->setAutoGenerateProxyClasses(false);
        $d->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        return $this->services['doctrine.orm.default_entity_manager'] = call_user_func(array('Doctrine\\ORM\\EntityManager', 'create'), $this->get('doctrine.dbal.default_connection'), $d);
    }
    protected function getDoctrine_Orm_Validator_UniqueService()
    {
        return $this->services['doctrine.orm.validator.unique'] = new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator($this->get('doctrine'));
    }
    protected function getDoctrine_Orm_ValidatorInitializerService()
    {
        return $this->services['doctrine.orm.validator_initializer'] = new \Symfony\Bridge\Doctrine\Validator\EntityInitializer($this->get('doctrine'));
    }
    protected function getDoctrineOdm_Mongodb_Validator_UniqueService()
    {
        return $this->services['doctrine_odm.mongodb.validator.unique'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\UniqueValidator($this);
    }
    protected function getEventDispatcherService()
    {
        $this->services['event_dispatcher'] = $instance = new \Symfony\Bundle\FrameworkBundle\ContainerAwareEventDispatcher($this);
        $instance->addListenerService('kernel.request', array(0 => 'router_listener', 1 => 'onEarlyKernelRequest'), 255);
        $instance->addListenerService('kernel.request', array(0 => 'router_listener', 1 => 'onKernelRequest'), 0);
        $instance->addListenerService('kernel.response', array(0 => 'response_listener', 1 => 'onKernelResponse'), 0);
        $instance->addListenerService('kernel.request', array(0 => 'session_listener', 1 => 'onKernelRequest'), 128);
        $instance->addListenerService('kernel.request', array(0 => 'security.firewall', 1 => 'onKernelRequest'), 64);
        $instance->addListenerService('kernel.response', array(0 => 'security.rememberme.response_listener', 1 => 'onKernelResponse'), 0);
        $instance->addListenerService('kernel.exception', array(0 => 'twig.exception_listener', 1 => 'onKernelException'), -128);
        $instance->addListenerService('kernel.controller', array(0 => 'sensio_framework_extra.controller.listener', 1 => 'onKernelController'), 0);
        $instance->addListenerService('kernel.controller', array(0 => 'sensio_framework_extra.converter.listener', 1 => 'onKernelController'), 0);
        $instance->addListenerService('kernel.controller', array(0 => 'sensio_framework_extra.view.listener', 1 => 'onKernelController'), 0);
        $instance->addListenerService('kernel.view', array(0 => 'sensio_framework_extra.view.listener', 1 => 'onKernelView'), 0);
        $instance->addListenerService('kernel.response', array(0 => 'sensio_framework_extra.cache.listener', 1 => 'onKernelResponse'), 0);
        $instance->addListenerService('kernel.controller', array(0 => 'security.extra.controller_listener', 1 => 'onCoreController'), -255);
        return $instance;
    }
    protected function getFileLocatorService()
    {
        return $this->services['file_locator'] = new \Symfony\Component\HttpKernel\Config\FileLocator($this->get('kernel'), 'E:/work/Code/Justsy Push-RESTService/Trunk/app/Resources');
    }
    protected function getFilesystemService()
    {
        return $this->services['filesystem'] = new \Symfony\Component\Filesystem\Filesystem();
    }
    protected function getForm_CsrfProviderService()
    {
        return $this->services['form.csrf_provider'] = new \Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider($this->get('session'), '335a052790228abbc6ea61b49ie9280adfaow808');
    }
    protected function getForm_FactoryService()
    {
        return $this->services['form.factory'] = new \Symfony\Component\Form\FormFactory(array(0 => new \Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension($this, array('field' => 'form.type.field', 'form' => 'form.type.form', 'birthday' => 'form.type.birthday', 'checkbox' => 'form.type.checkbox', 'choice' => 'form.type.choice', 'collection' => 'form.type.collection', 'country' => 'form.type.country', 'date' => 'form.type.date', 'datetime' => 'form.type.datetime', 'email' => 'form.type.email', 'file' => 'form.type.file', 'hidden' => 'form.type.hidden', 'integer' => 'form.type.integer', 'language' => 'form.type.language', 'locale' => 'form.type.locale', 'money' => 'form.type.money', 'number' => 'form.type.number', 'password' => 'form.type.password', 'percent' => 'form.type.percent', 'radio' => 'form.type.radio', 'repeated' => 'form.type.repeated', 'search' => 'form.type.search', 'textarea' => 'form.type.textarea', 'text' => 'form.type.text', 'time' => 'form.type.time', 'timezone' => 'form.type.timezone', 'url' => 'form.type.url', 'csrf' => 'form.type.csrf', 'entity' => 'form.type.entity', 'document' => 'form.type.mongodb_document'), array('field' => array(0 => 'form.type_extension.field'), 'form' => array(0 => 'form.type_extension.csrf')), array(0 => 'form.type_guesser.validator', 1 => 'form.type_guesser.doctrine', 2 => 'form.type_guesser.doctrine.mongodb'))));
    }
    protected function getForm_Type_BirthdayService()
    {
        return $this->services['form.type.birthday'] = new \Symfony\Component\Form\Extension\Core\Type\BirthdayType();
    }
    protected function getForm_Type_CheckboxService()
    {
        return $this->services['form.type.checkbox'] = new \Symfony\Component\Form\Extension\Core\Type\CheckboxType();
    }
    protected function getForm_Type_ChoiceService()
    {
        return $this->services['form.type.choice'] = new \Symfony\Component\Form\Extension\Core\Type\ChoiceType();
    }
    protected function getForm_Type_CollectionService()
    {
        return $this->services['form.type.collection'] = new \Symfony\Component\Form\Extension\Core\Type\CollectionType();
    }
    protected function getForm_Type_CountryService()
    {
        return $this->services['form.type.country'] = new \Symfony\Component\Form\Extension\Core\Type\CountryType();
    }
    protected function getForm_Type_CsrfService()
    {
        return $this->services['form.type.csrf'] = new \Symfony\Component\Form\Extension\Csrf\Type\CsrfType($this->get('form.csrf_provider'));
    }
    protected function getForm_Type_DateService()
    {
        return $this->services['form.type.date'] = new \Symfony\Component\Form\Extension\Core\Type\DateType();
    }
    protected function getForm_Type_DatetimeService()
    {
        return $this->services['form.type.datetime'] = new \Symfony\Component\Form\Extension\Core\Type\DateTimeType();
    }
    protected function getForm_Type_EmailService()
    {
        return $this->services['form.type.email'] = new \Symfony\Component\Form\Extension\Core\Type\EmailType();
    }
    protected function getForm_Type_EntityService()
    {
        return $this->services['form.type.entity'] = new \Symfony\Bridge\Doctrine\Form\Type\EntityType($this->get('doctrine'));
    }
    protected function getForm_Type_FieldService()
    {
        return $this->services['form.type.field'] = new \Symfony\Component\Form\Extension\Core\Type\FieldType($this->get('validator'));
    }
    protected function getForm_Type_FileService()
    {
        return $this->services['form.type.file'] = new \Symfony\Component\Form\Extension\Core\Type\FileType();
    }
    protected function getForm_Type_FormService()
    {
        return $this->services['form.type.form'] = new \Symfony\Component\Form\Extension\Core\Type\FormType();
    }
    protected function getForm_Type_HiddenService()
    {
        return $this->services['form.type.hidden'] = new \Symfony\Component\Form\Extension\Core\Type\HiddenType();
    }
    protected function getForm_Type_IntegerService()
    {
        return $this->services['form.type.integer'] = new \Symfony\Component\Form\Extension\Core\Type\IntegerType();
    }
    protected function getForm_Type_LanguageService()
    {
        return $this->services['form.type.language'] = new \Symfony\Component\Form\Extension\Core\Type\LanguageType();
    }
    protected function getForm_Type_LocaleService()
    {
        return $this->services['form.type.locale'] = new \Symfony\Component\Form\Extension\Core\Type\LocaleType();
    }
    protected function getForm_Type_MoneyService()
    {
        return $this->services['form.type.money'] = new \Symfony\Component\Form\Extension\Core\Type\MoneyType();
    }
    protected function getForm_Type_MongodbDocumentService()
    {
        return $this->services['form.type.mongodb_document'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Form\Type\DocumentType($this->get('doctrine.odm.mongodb.default_document_manager'));
    }
    protected function getForm_Type_NumberService()
    {
        return $this->services['form.type.number'] = new \Symfony\Component\Form\Extension\Core\Type\NumberType();
    }
    protected function getForm_Type_PasswordService()
    {
        return $this->services['form.type.password'] = new \Symfony\Component\Form\Extension\Core\Type\PasswordType();
    }
    protected function getForm_Type_PercentService()
    {
        return $this->services['form.type.percent'] = new \Symfony\Component\Form\Extension\Core\Type\PercentType();
    }
    protected function getForm_Type_RadioService()
    {
        return $this->services['form.type.radio'] = new \Symfony\Component\Form\Extension\Core\Type\RadioType();
    }
    protected function getForm_Type_RepeatedService()
    {
        return $this->services['form.type.repeated'] = new \Symfony\Component\Form\Extension\Core\Type\RepeatedType();
    }
    protected function getForm_Type_SearchService()
    {
        return $this->services['form.type.search'] = new \Symfony\Component\Form\Extension\Core\Type\SearchType();
    }
    protected function getForm_Type_TextService()
    {
        return $this->services['form.type.text'] = new \Symfony\Component\Form\Extension\Core\Type\TextType();
    }
    protected function getForm_Type_TextareaService()
    {
        return $this->services['form.type.textarea'] = new \Symfony\Component\Form\Extension\Core\Type\TextareaType();
    }
    protected function getForm_Type_TimeService()
    {
        return $this->services['form.type.time'] = new \Symfony\Component\Form\Extension\Core\Type\TimeType();
    }
    protected function getForm_Type_TimezoneService()
    {
        return $this->services['form.type.timezone'] = new \Symfony\Component\Form\Extension\Core\Type\TimezoneType();
    }
    protected function getForm_Type_UrlService()
    {
        return $this->services['form.type.url'] = new \Symfony\Component\Form\Extension\Core\Type\UrlType();
    }
    protected function getForm_TypeExtension_CsrfService()
    {
        return $this->services['form.type_extension.csrf'] = new \Symfony\Component\Form\Extension\Csrf\Type\FormTypeCsrfExtension(true, '_token');
    }
    protected function getForm_TypeExtension_FieldService()
    {
        return $this->services['form.type_extension.field'] = new \Symfony\Component\Form\Extension\Validator\Type\FieldTypeValidatorExtension($this->get('validator'));
    }
    protected function getForm_TypeGuesser_DoctrineService()
    {
        return $this->services['form.type_guesser.doctrine'] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser($this->get('doctrine'));
    }
    protected function getForm_TypeGuesser_Doctrine_MongodbService()
    {
        return $this->services['form.type_guesser.doctrine.mongodb'] = new \Symfony\Bundle\DoctrineMongoDBBundle\Form\DoctrineMongoDBTypeGuesser($this->get('doctrine.odm.mongodb.default_document_manager'));
    }
    protected function getForm_TypeGuesser_ValidatorService()
    {
        return $this->services['form.type_guesser.validator'] = new \Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser($this->get('validator.mapping.class_metadata_factory'));
    }
    protected function getHttpKernelService()
    {
        return $this->services['http_kernel'] = new \Symfony\Bundle\FrameworkBundle\HttpKernel($this->get('event_dispatcher'), $this, new \Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver($this, $this->get('controller_name_converter'), $this->get('monolog.logger.request')));
    }
    protected function getKernelService()
    {
        throw new \RuntimeException('You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service.');
    }
    protected function getLoggerService()
    {
        $this->services['logger'] = $instance = new \Symfony\Bridge\Monolog\Logger('app');
        $instance->pushHandler($this->get('monolog.handler.main'));
        return $instance;
    }
    protected function getMailerService()
    {
        return $this->services['mailer'] = new \Swift_Mailer($this->get('swiftmailer.transport'));
    }
    protected function getMonolog_Handler_MainService()
    {
        return $this->services['monolog.handler.main'] = new \Monolog\Handler\FingersCrossedHandler($this->get('monolog.handler.nested'), 400, 0, true, true);
    }
    protected function getMonolog_Handler_NestedService()
    {
        return $this->services['monolog.handler.nested'] = new \Monolog\Handler\StreamHandler('E:/work/Code/Justsy Push-RESTService/Trunk/app/logs/prod.log', 100, true);
    }
    protected function getMonolog_Logger_DoctrineService()
    {
        $this->services['monolog.logger.doctrine'] = $instance = new \Symfony\Bridge\Monolog\Logger('doctrine');
        $instance->pushHandler($this->get('monolog.handler.main'));
        return $instance;
    }
    protected function getMonolog_Logger_RequestService()
    {
        $this->services['monolog.logger.request'] = $instance = new \Symfony\Bridge\Monolog\Logger('request');
        $instance->pushHandler($this->get('monolog.handler.main'));
        return $instance;
    }
    protected function getMonolog_Logger_RouterService()
    {
        $this->services['monolog.logger.router'] = $instance = new \Symfony\Bridge\Monolog\Logger('router');
        $instance->pushHandler($this->get('monolog.handler.main'));
        return $instance;
    }
    protected function getMonolog_Logger_SecurityService()
    {
        $this->services['monolog.logger.security'] = $instance = new \Symfony\Bridge\Monolog\Logger('security');
        $instance->pushHandler($this->get('monolog.handler.main'));
        return $instance;
    }
    protected function getPdoService()
    {
        return $this->services['pdo'] = new \PDO('mysql:host=127.0.0.1;dbname=justsy_sns;port=3306', 'justsy_sns', 'justsy_sns');
    }
    protected function getRequestService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('request', 'request');
        }
        throw new \RuntimeException('You have requested a synthetic service ("request"). The DIC does not know how to construct this service.');
    }
    protected function getResponseListenerService()
    {
        return $this->services['response_listener'] = new \Symfony\Component\HttpKernel\EventListener\ResponseListener('UTF-8');
    }
    protected function getRouterService()
    {
        return $this->services['router'] = new \Symfony\Bundle\FrameworkBundle\Routing\Router($this, 'E:/work/Code/Justsy Push-RESTService/Trunk/app/config/routing.yml', array('cache_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod', 'debug' => false, 'generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper', 'generator_cache_class' => 'appprodUrlGenerator', 'matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper', 'matcher_cache_class' => 'appprodUrlMatcher'));
    }
    protected function getRouterListenerService()
    {
        return $this->services['router_listener'] = new \Symfony\Bundle\FrameworkBundle\EventListener\RouterListener($this->get('router'), 80, 443, $this->get('monolog.logger.request'));
    }
    protected function getRouting_LoaderService()
    {
        $a = $this->get('file_locator');
        $b = $this->get('annotation_reader');
        $c = new \Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader($b);
        $d = new \Symfony\Component\Config\Loader\LoaderResolver();
        $d->addLoader(new \Symfony\Component\Routing\Loader\XmlFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\YamlFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\PhpFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\AnnotationDirectoryLoader($a, $c));
        $d->addLoader(new \Symfony\Component\Routing\Loader\AnnotationFileLoader($a, $c));
        $d->addLoader($c);
        return $this->services['routing.loader'] = new \Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader($this->get('controller_name_converter'), $this->get('monolog.logger.router'), $d);
    }
    protected function getSecurity_Access_MethodInterceptorService()
    {
        return $this->services['security.access.method_interceptor'] = new \JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor($this->get('security.context'), $this->get('security.authentication.manager'), $this->get('security.access.decision_manager'), new \JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManager(array()), new \JMS\SecurityExtraBundle\Security\Authorization\RunAsManager('RunAsToken', 'ROLE_'), $this->get('logger'));
    }
    protected function getSecurity_ContextService()
    {
        return $this->services['security.context'] = new \Symfony\Component\Security\Core\SecurityContext($this->get('security.authentication.manager'), $this->get('security.access.decision_manager'), false);
    }
    protected function getSecurity_EncoderFactoryService()
    {
        return $this->services['security.encoder_factory'] = new \Symfony\Component\Security\Core\Encoder\EncoderFactory(array('Justsy\\BaseBundle\\Login\\UserSession' => array('class' => 'Symfony\\Component\\Security\\Core\\Encoder\\MessageDigestPasswordEncoder', 'arguments' => array(0 => 'sha512', 1 => true, 2 => 5000))));
    }
    protected function getSecurity_Extra_ControllerListenerService()
    {
        return $this->services['security.extra.controller_listener'] = new \JMS\SecurityExtraBundle\Controller\ControllerListener($this, $this->get('annotation_reader'));
    }
    protected function getSecurity_FirewallService()
    {
        return $this->services['security.firewall'] = new \Symfony\Component\Security\Http\Firewall(new \Symfony\Bundle\SecurityBundle\Security\FirewallMap($this, array('security.firewall.map.context.dev' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/(_(profiler|wdt)|css|images|js|upload)/'), 'security.firewall.map.context.login' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/login$'), 'security.firewall.map.context.login_ref' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/login_ref$'), 'security.firewall.map.context.autologin' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/login/outside/personalhome*$'), 'security.firewall.map.context.register' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/register(/.*)*$'), 'security.firewall.map.context.active' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/active(/.*)*$'), 'security.firewall.map.context.getfile' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/getfile(/.*)*$'), 'security.firewall.map.context.uploadfile' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/uploadfile(/.*)*$'), 'security.firewall.map.context.deletefile' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/deletefile(/.*)*$'), 'security.firewall.map.context.viewcred' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/viewcred(/.*)$'), 'security.firewall.map.context.qr' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/qr(/.*)*$'), 'security.firewall.map.context.api' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/api(/.*)*$'), 'security.firewall.map.context.publicpage' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/publicpage(/.*)*$'), 'security.firewall.map.context.setupfile' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/setupfile(/.*)*$'), 'security.firewall.map.context.interface_logincheck' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/logincheck$'), 'security.firewall.map.context.interface_getserverdifftime' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/getserverdifftime$'), 'security.firewall.map.context.interface_emailtostaffs' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/emailtostaffs$'), 'security.firewall.map.context.interface_mobiletostaffs' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/mobiletostaffs$'), 'security.firewall.map.context.interface_nametostaffs' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/nametostaffs$'), 'security.firewall.map.context.interface_findpwd' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/validcode$'), 'security.firewall.map.context.interface_resetpwd' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/resetpwd$'), 'security.firewall.map.context.interface_mobileregister' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/interface/mobileregister(/.*)*$'), 'security.firewall.map.context.help' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/help(/.*)*$'), 'security.firewall.map.context.home' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/home(/.*)*$'), 'security.firewall.map.context.default' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/default$'), 'security.firewall.map.context.webim' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/webim(/.*)*$'), 'security.firewall.map.context.getfaces' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/get/faces$'), 'security.firewall.map.context.share_login' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/share/sharelogin$'), 'security.firewall.map.context.share' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/share(/.*)*$'), 'security.firewall.map.context.mca_public' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/ma/mca/public(/.*)*$'), 'security.firewall.map.context.zj_public' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/ma/zj/public(/.*)*$'), 'security.firewall.map.context.mapp_download' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/mapp/download(/.*)*$'), 'security.firewall.map.context.secured_area' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/'))), $this->get('event_dispatcher'));
    }
    protected function getSecurity_Firewall_Map_Context_ActiveService()
    {
        return $this->services['security.firewall.map.context.active'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_ApiService()
    {
        return $this->services['security.firewall.map.context.api'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_AutologinService()
    {
        return $this->services['security.firewall.map.context.autologin'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_DefaultService()
    {
        return $this->services['security.firewall.map.context.default'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_DeletefileService()
    {
        return $this->services['security.firewall.map.context.deletefile'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_DevService()
    {
        return $this->services['security.firewall.map.context.dev'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_GetfacesService()
    {
        return $this->services['security.firewall.map.context.getfaces'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_GetfileService()
    {
        return $this->services['security.firewall.map.context.getfile'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_HelpService()
    {
        return $this->services['security.firewall.map.context.help'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_HomeService()
    {
        return $this->services['security.firewall.map.context.home'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceEmailtostaffsService()
    {
        return $this->services['security.firewall.map.context.interface_emailtostaffs'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceFindpwdService()
    {
        return $this->services['security.firewall.map.context.interface_findpwd'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceGetserverdifftimeService()
    {
        return $this->services['security.firewall.map.context.interface_getserverdifftime'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceLogincheckService()
    {
        return $this->services['security.firewall.map.context.interface_logincheck'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceMobileregisterService()
    {
        return $this->services['security.firewall.map.context.interface_mobileregister'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceMobiletostaffsService()
    {
        return $this->services['security.firewall.map.context.interface_mobiletostaffs'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceNametostaffsService()
    {
        return $this->services['security.firewall.map.context.interface_nametostaffs'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_InterfaceResetpwdService()
    {
        return $this->services['security.firewall.map.context.interface_resetpwd'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_LoginService()
    {
        return $this->services['security.firewall.map.context.login'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_LoginRefService()
    {
        return $this->services['security.firewall.map.context.login_ref'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_MappDownloadService()
    {
        return $this->services['security.firewall.map.context.mapp_download'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_McaPublicService()
    {
        return $this->services['security.firewall.map.context.mca_public'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_PublicpageService()
    {
        return $this->services['security.firewall.map.context.publicpage'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_QrService()
    {
        return $this->services['security.firewall.map.context.qr'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_RegisterService()
    {
        return $this->services['security.firewall.map.context.register'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_SecuredAreaService()
    {
        $a = $this->get('security.context');
        $b = $this->get('we_user_provider');
        $c = $this->get('monolog.logger.security');
        $d = $this->get('event_dispatcher');
        $e = $this->get('security.http_utils');
        $f = $this->get('security.authentication.manager');
        $g = new \Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices(array(0 => $b), '335a052790228abbc6ea61b49ie9280adfaow808', 'secured_area', array('lifetime' => 604800, 'path' => '/', 'domain' => NULL, 'name' => 'REMEMBERME', 'secure' => false, 'httponly' => true, 'always_remember_me' => false, 'remember_me_parameter' => '_remember_me'), $c);
        $h = new \Symfony\Component\Security\Http\Firewall\LogoutListener($a, $e, '/logout', '/', NULL);
        $h->addHandler(new \Symfony\Component\Security\Http\Logout\SessionLogoutHandler());
        $h->addHandler($g);
        $i = new \Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener($a, $f, $this->get('security.authentication.session_strategy'), $e, 'secured_area', array('check_path' => '/login_check', 'login_path' => '/login', 'use_forward' => true, 'always_use_default_target_path' => false, 'default_target_path' => '/', 'target_path_parameter' => '_target_path', 'use_referer' => false, 'failure_path' => NULL, 'failure_forward' => false, 'username_parameter' => '_username', 'password_parameter' => '_password', 'csrf_parameter' => '_csrf_token', 'intention' => 'authenticate', 'post_only' => true), NULL, NULL, $c, $d);
        $i->setRememberMeServices($g);
        return $this->services['security.firewall.map.context.secured_area'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(0 => $this->get('security.channel_listener'), 1 => new \Symfony\Component\Security\Http\Firewall\ContextListener($a, array(0 => $b), 'secured_area', $c, $d), 2 => $h, 3 => $i, 4 => new \Symfony\Component\Security\Http\Firewall\RememberMeListener($a, $g, $f, $c, $d), 5 => $this->get('security.access_listener')), new \Symfony\Component\Security\Http\Firewall\ExceptionListener($a, $this->get('security.authentication.trust_resolver'), $e, new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint($this->get('http_kernel'), $e, '/login', true), NULL, NULL, $c));
    }
    protected function getSecurity_Firewall_Map_Context_SetupfileService()
    {
        return $this->services['security.firewall.map.context.setupfile'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_ShareService()
    {
        $a = $this->get('security.context');
        $b = $this->get('we_user_provider');
        $c = $this->get('monolog.logger.security');
        $d = $this->get('event_dispatcher');
        $e = $this->get('security.authentication.manager');
        $f = $this->get('security.http_utils');
        $g = new \Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices(array(0 => $b), '335a052790228abbc6ea61b49ie9280adfaow808', 'share', array('lifetime' => 604800, 'path' => '/', 'domain' => NULL, 'name' => 'REMEMBERME', 'secure' => false, 'httponly' => true, 'always_remember_me' => false, 'remember_me_parameter' => '_remember_me'), $c);
        $h = new \Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener($a, $e, $this->get('security.authentication.session_strategy'), $f, 'share', array('check_path' => '/share/sharelogin_check', 'login_path' => '/share/sharelogin', 'use_forward' => true, 'always_use_default_target_path' => false, 'default_target_path' => '/', 'target_path_parameter' => '_target_path', 'use_referer' => false, 'failure_path' => NULL, 'failure_forward' => false, 'username_parameter' => '_username', 'password_parameter' => '_password', 'csrf_parameter' => '_csrf_token', 'intention' => 'authenticate', 'post_only' => true), NULL, NULL, $c, $d);
        $h->setRememberMeServices($g);
        return $this->services['security.firewall.map.context.share'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(0 => $this->get('security.channel_listener'), 1 => new \Symfony\Component\Security\Http\Firewall\ContextListener($a, array(0 => $b), 'share', $c, $d), 2 => $h, 3 => new \Symfony\Component\Security\Http\Firewall\RememberMeListener($a, $g, $e, $c, $d), 4 => $this->get('security.access_listener')), new \Symfony\Component\Security\Http\Firewall\ExceptionListener($a, $this->get('security.authentication.trust_resolver'), $f, new \Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint($this->get('http_kernel'), $f, '/share/sharelogin', true), NULL, NULL, $c));
    }
    protected function getSecurity_Firewall_Map_Context_ShareLoginService()
    {
        return $this->services['security.firewall.map.context.share_login'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_UploadfileService()
    {
        return $this->services['security.firewall.map.context.uploadfile'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_ViewcredService()
    {
        return $this->services['security.firewall.map.context.viewcred'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_WebimService()
    {
        return $this->services['security.firewall.map.context.webim'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Firewall_Map_Context_ZjPublicService()
    {
        return $this->services['security.firewall.map.context.zj_public'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(), NULL);
    }
    protected function getSecurity_Rememberme_ResponseListenerService()
    {
        return $this->services['security.rememberme.response_listener'] = new \Symfony\Bundle\SecurityBundle\EventListener\ResponseListener();
    }
    protected function getSensioFrameworkExtra_Cache_ListenerService()
    {
        return $this->services['sensio_framework_extra.cache.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\CacheListener();
    }
    protected function getSensioFrameworkExtra_Controller_ListenerService()
    {
        return $this->services['sensio_framework_extra.controller.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener($this->get('annotation_reader'));
    }
    protected function getSensioFrameworkExtra_Converter_Doctrine_OrmService()
    {
        return $this->services['sensio_framework_extra.converter.doctrine.orm'] = new \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter($this->get('doctrine'));
    }
    protected function getSensioFrameworkExtra_Converter_ListenerService()
    {
        return $this->services['sensio_framework_extra.converter.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener($this->get('sensio_framework_extra.converter.manager'));
    }
    protected function getSensioFrameworkExtra_Converter_ManagerService()
    {
        $this->services['sensio_framework_extra.converter.manager'] = $instance = new \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterManager();
        $instance->add($this->get('sensio_framework_extra.converter.doctrine.orm'), 0);
        return $instance;
    }
    protected function getSensioFrameworkExtra_View_ListenerService()
    {
        return $this->services['sensio_framework_extra.view.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\TemplateListener($this);
    }
    protected function getServiceContainerService()
    {
        throw new \RuntimeException('You have requested a synthetic service ("service_container"). The DIC does not know how to construct this service.');
    }
    protected function getSessionService()
    {
        return $this->services['session'] = new \Symfony\Component\HttpFoundation\Session($this->get('session.storage.pdo'), 'zh');
    }
    protected function getSession_Storage_PdoService()
    {
        return $this->services['session.storage.pdo'] = new \Symfony\Component\HttpFoundation\SessionStorage\PdoSessionStorage($this->get('pdo'), array(), array('db_table' => 'session', 'db_id_col' => 'session_id', 'db_data_col' => 'session_value', 'db_time_col' => 'session_time'));
    }
    protected function getSessionListenerService()
    {
        return $this->services['session_listener'] = new \Symfony\Bundle\FrameworkBundle\EventListener\SessionListener($this, true);
    }
    protected function getSwiftmailer_Plugin_MessageloggerService()
    {
        return $this->services['swiftmailer.plugin.messagelogger'] = new \Symfony\Bundle\SwiftmailerBundle\Logger\MessageLogger();
    }
    protected function getSwiftmailer_TransportService()
    {
        $this->services['swiftmailer.transport'] = $instance = new \Swift_Transport_EsmtpTransport(new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory()), array(0 => new \Swift_Transport_Esmtp_AuthHandler(array(0 => new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(), 1 => new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(), 2 => new \Swift_Transport_Esmtp_Auth_PlainAuthenticator()))), new \Swift_Events_SimpleEventDispatcher());
        $instance->setHost('mailserver');
        $instance->setPort(25);
        $instance->setEncryption(NULL);
        $instance->setUsername('mailuser');
        $instance->setPassword('mailpwd');
        $instance->setAuthMode('login');
        return $instance;
    }
    protected function getTemplatingService()
    {
        return $this->services['templating'] = new \Symfony\Bundle\TwigBundle\TwigEngine($this->get('twig'), $this->get('templating.name_parser'), $this->get('templating.globals'));
    }
    protected function getTemplating_Asset_PackageFactoryService()
    {
        return $this->services['templating.asset.package_factory'] = new \Symfony\Bundle\FrameworkBundle\Templating\Asset\PackageFactory($this);
    }
    protected function getTemplating_GlobalsService()
    {
        return $this->services['templating.globals'] = new \Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables($this);
    }
    protected function getTemplating_Helper_ActionsService()
    {
        return $this->services['templating.helper.actions'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper($this->get('http_kernel'));
    }
    protected function getTemplating_Helper_AssetsService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('templating.helper.assets', 'request');
        }
        return $this->services['templating.helper.assets'] = $this->scopedServices['request']['templating.helper.assets'] = new \Symfony\Component\Templating\Helper\CoreAssetsHelper(new \Symfony\Bundle\FrameworkBundle\Templating\Asset\PathPackage($this->get('request'), NULL, NULL), array());
    }
    protected function getTemplating_Helper_CodeService()
    {
        return $this->services['templating.helper.code'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\CodeHelper(NULL, 'E:/work/Code/Justsy Push-RESTService/Trunk/app', 'UTF-8');
    }
    protected function getTemplating_Helper_FormService()
    {
        $a = new \Symfony\Bundle\FrameworkBundle\Templating\PhpEngine($this->get('templating.name_parser'), $this, $this->get('templating.loader'), $this->get('templating.globals'));
        $a->setCharset('UTF-8');
        $a->setHelpers(array('slots' => 'templating.helper.slots', 'assets' => 'templating.helper.assets', 'request' => 'templating.helper.request', 'session' => 'templating.helper.session', 'router' => 'templating.helper.router', 'actions' => 'templating.helper.actions', 'code' => 'templating.helper.code', 'translator' => 'templating.helper.translator', 'form' => 'templating.helper.form', 'security' => 'templating.helper.security', 'assetic' => 'assetic.helper.static'));
        return $this->services['templating.helper.form'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper($a, array(0 => 'FrameworkBundle:Form'));
    }
    protected function getTemplating_Helper_RequestService()
    {
        return $this->services['templating.helper.request'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\RequestHelper($this->get('request'));
    }
    protected function getTemplating_Helper_RouterService()
    {
        return $this->services['templating.helper.router'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper($this->get('router'));
    }
    protected function getTemplating_Helper_SecurityService()
    {
        return $this->services['templating.helper.security'] = new \Symfony\Bundle\SecurityBundle\Templating\Helper\SecurityHelper($this->get('security.context'));
    }
    protected function getTemplating_Helper_SessionService()
    {
        return $this->services['templating.helper.session'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\SessionHelper($this->get('request'));
    }
    protected function getTemplating_Helper_SlotsService()
    {
        return $this->services['templating.helper.slots'] = new \Symfony\Component\Templating\Helper\SlotsHelper();
    }
    protected function getTemplating_Helper_TranslatorService()
    {
        return $this->services['templating.helper.translator'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper($this->get('translator.default'));
    }
    protected function getTemplating_LoaderService()
    {
        return $this->services['templating.loader'] = new \Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader($this->get('templating.locator'));
    }
    protected function getTemplating_NameParserService()
    {
        return $this->services['templating.name_parser'] = new \Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser($this->get('kernel'));
    }
    protected function getTranslation_Loader_PhpService()
    {
        return $this->services['translation.loader.php'] = new \Symfony\Component\Translation\Loader\PhpFileLoader();
    }
    protected function getTranslation_Loader_XliffService()
    {
        return $this->services['translation.loader.xliff'] = new \Symfony\Component\Translation\Loader\XliffFileLoader();
    }
    protected function getTranslation_Loader_YmlService()
    {
        return $this->services['translation.loader.yml'] = new \Symfony\Component\Translation\Loader\YamlFileLoader();
    }
    protected function getTranslator_DefaultService()
    {
        $this->services['translator.default'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\Translator($this, new \Symfony\Component\Translation\MessageSelector(), array('translation.loader.php' => 'php', 'translation.loader.yml' => 'yml', 'translation.loader.xliff' => 'xliff'), array('cache_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/translations', 'debug' => false), $this->get('session'));
        $instance->setFallbackLocale('zh');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.ca.xliff', 'ca', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.cs.xliff', 'cs', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.da.xliff', 'da', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.de.xliff', 'de', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.es.xliff', 'es', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.et.xliff', 'et', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.eu.xliff', 'eu', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.fa.xliff', 'fa', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.fi.xliff', 'fi', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.fr.xliff', 'fr', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.he.xliff', 'he', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.hr.xliff', 'hr', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.hu.xliff', 'hu', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.hy.xliff', 'hy', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.id.xliff', 'id', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.it.xliff', 'it', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.ja.xliff', 'ja', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.lb.xliff', 'lb', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.lt.xliff', 'lt', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.mn.xliff', 'mn', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.nl.xliff', 'nl', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.pl.xliff', 'pl', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.pt_BR.xliff', 'pt_BR', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.pt_PT.xliff', 'pt_PT', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.ro.xliff', 'ro', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.ru.xliff', 'ru', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.sk.xliff', 'sk', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.sl.xliff', 'sl', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.sr.xliff', 'sr', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.sv.xliff', 'sv', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.ua.xliff', 'ua', 'validators');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bundle\\FrameworkBundle/Resources/translations\\validators.zh_CN.xliff', 'zh_CN', 'validators');
        $instance->addResource('yml', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\BaseBundle/Resources/translations\\messages.en.yml', 'en', 'messages');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\Justsy\\OpenAPIBundle/Resources/translations\\messages.fr.xliff', 'fr', 'messages');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\WebIM\\ImChatBundle/Resources/translations\\messages.fr.xliff', 'fr', 'messages');
        $instance->addResource('xliff', 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\src\\WebIM\\ImMainBundle/Resources/translations\\messages.fr.xliff', 'fr', 'messages');
        return $instance;
    }
    protected function getTwigService()
    {
        $this->services['twig'] = $instance = new \Twig_Environment($this->get('twig.loader'), array('debug' => false, 'strict_variables' => false, 'exception_controller' => 'Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController::showAction', 'cache' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/twig', 'charset' => 'UTF-8'));
        $instance->addExtension(new \Symfony\Bundle\SecurityBundle\Twig\Extension\SecurityExtension($this->get('security.context')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($this->get('translator.default')));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\AssetsExtension($this));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\ActionsExtension($this));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\CodeExtension($this));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\RoutingExtension($this->get('router')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\YamlExtension());
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\FormExtension(array(0 => 'form_div_layout.html.twig')));
        $instance->addExtension(new \Symfony\Bundle\AsseticBundle\Twig\AsseticExtension($this->get('assetic.asset_factory'), false, array()));
        $instance->addGlobal('FILE_WEBSERVER_URL', 'http://112.126.77.162:8000/getfile/');
        $instance->addGlobal('ISDEBUG', false);
        $instance->addGlobal('IM_SERVER', 'http://112.126.77.162:5280');
        $instance->addGlobal('JS_OPEN_API_URL', 'http://112.126.77.162:8000');
        $instance->addGlobal('WEBIM_URL', 'http://112.126.77.162:8000');
        $instance->addGlobal('APPCENTER_URL', 'http://localhost:8080');
        $instance->addGlobal('USER_AUTH_METHD', 'WefafaAuth');
        return $instance;
    }
    protected function getTwig_ExceptionListenerService()
    {
        return $this->services['twig.exception_listener'] = new \Symfony\Component\HttpKernel\EventListener\ExceptionListener('Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController::showAction', $this->get('monolog.logger.request'));
    }
    protected function getTwig_LoaderService()
    {
        $this->services['twig.loader'] = $instance = new \Symfony\Bundle\TwigBundle\Loader\FilesystemLoader($this->get('templating.locator'), $this->get('templating.name_parser'));
        $instance->addPath('E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Bridge\\Twig/Resources/views/Form');
        return $instance;
    }
    protected function getValidatorService()
    {
        return $this->services['validator'] = new \Symfony\Component\Validator\Validator($this->get('validator.mapping.class_metadata_factory'), new \Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory($this, array('doctrine.orm.validator.unique' => 'doctrine.orm.validator.unique', 'doctrine_odm.mongodb.unique' => 'doctrine_odm.mongodb.validator.unique')), array(0 => $this->get('doctrine.orm.validator_initializer')));
    }
    protected function getWeDataAccessService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('we_data_access', 'request');
        }
        return $this->services['we_data_access'] = $this->scopedServices['request']['we_data_access'] = new \Justsy\BaseBundle\DataAccess\DataAccess($this, 'default');
    }
    protected function getWeDataAccessImService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('we_data_access_im', 'request');
        }
        return $this->services['we_data_access_im'] = $this->scopedServices['request']['we_data_access_im'] = new \Justsy\BaseBundle\DataAccess\DataAccess($this, 'im');
    }
    protected function getWeDataAccessTestService()
    {
        return $this->services['we_data_access_test'] = new \Justsy\BaseBundle\DataAccess\DataAccess($this, 'test');
    }
    protected function getWeDataAccessWordpressService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('we_data_access_wordpress', 'request');
        }
        return $this->services['we_data_access_wordpress'] = $this->scopedServices['request']['we_data_access_wordpress'] = new \Justsy\BaseBundle\DataAccess\DataAccess($this, 'wordpress');
    }
    protected function getWeSysParamService()
    {
        return $this->services['we_sys_param'] = new \Justsy\BaseBundle\DataAccess\SysParam($this);
    }
    protected function getWeUserProviderService()
    {
        return $this->services['we_user_provider'] = new \Justsy\BaseBundle\Login\UserProvider($this);
    }
    protected function getDatabaseConnectionService()
    {
        return $this->get('doctrine.dbal.default_connection');
    }
    protected function getDoctrine_Odm_Mongodb_CacheService()
    {
        return $this->get('doctrine.odm.mongodb.cache.array');
    }
    protected function getDoctrine_Odm_Mongodb_DocumentManagerService()
    {
        return $this->get('doctrine.odm.mongodb.default_document_manager');
    }
    protected function getDoctrine_Odm_Mongodb_Metadata_AnnotationReaderService()
    {
        return $this->get('annotation_reader');
    }
    protected function getDoctrine_Orm_EntityManagerService()
    {
        return $this->get('doctrine.orm.default_entity_manager');
    }
    protected function getSession_StorageService()
    {
        return $this->get('session.storage.pdo');
    }
    protected function getTranslatorService()
    {
        return $this->get('translator.default');
    }
    protected function getAssetic_AssetFactoryService()
    {
        return $this->services['assetic.asset_factory'] = new \Symfony\Bundle\AsseticBundle\Factory\AssetFactory($this->get('kernel'), $this, new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag($this->getDefaultParameters()), 'E:/work/Code/Justsy Push-RESTService/Trunk/app/../web', false);
    }
    protected function getControllerNameConverterService()
    {
        return $this->services['controller_name_converter'] = new \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser($this->get('kernel'));
    }
    protected function getSecurity_Access_DecisionManagerService()
    {
        return $this->services['security.access.decision_manager'] = new \Symfony\Component\Security\Core\Authorization\AccessDecisionManager(array(0 => new \Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter(new \Symfony\Component\Security\Core\Role\RoleHierarchy(array('ROLE_ADMIN' => array(0 => 'ROLE_USER'), 'ROLE_SUPER_ADMIN' => array(0 => 'ROLE_USER', 1 => 'ROLE_ADMIN', 2 => 'ROLE_ALLOWED_TO_SWITCH')))), 1 => new \Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter($this->get('security.authentication.trust_resolver'))), 'affirmative', false, true);
    }
    protected function getSecurity_AccessListenerService()
    {
        return $this->services['security.access_listener'] = new \Symfony\Component\Security\Http\Firewall\AccessListener($this->get('security.context'), $this->get('security.access.decision_manager'), $this->get('security.access_map'), $this->get('security.authentication.manager'), $this->get('monolog.logger.security'));
    }
    protected function getSecurity_AccessMapService()
    {
        return $this->services['security.access_map'] = new \Symfony\Component\Security\Http\AccessMap();
    }
    protected function getSecurity_Authentication_ManagerService()
    {
        $a = $this->get('we_user_provider');
        $b = $this->get('security.encoder_factory');
        $c = new \Symfony\Component\Security\Core\User\UserChecker();
        return $this->services['security.authentication.manager'] = new \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager(array(0 => new \Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider($a, $c, 'share', $b, true), 1 => new \Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider($c, '335a052790228abbc6ea61b49ie9280adfaow808', 'share'), 2 => new \Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider($a, $c, 'secured_area', $b, true), 3 => new \Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider($c, '335a052790228abbc6ea61b49ie9280adfaow808', 'secured_area')));
    }
    protected function getSecurity_Authentication_SessionStrategyService()
    {
        return $this->services['security.authentication.session_strategy'] = new \Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy('migrate');
    }
    protected function getSecurity_Authentication_TrustResolverService()
    {
        return $this->services['security.authentication.trust_resolver'] = new \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver('Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken', 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken');
    }
    protected function getSecurity_ChannelListenerService()
    {
        return $this->services['security.channel_listener'] = new \Symfony\Component\Security\Http\Firewall\ChannelListener($this->get('security.access_map'), new \Symfony\Component\Security\Http\EntryPoint\RetryAuthenticationEntryPoint(80, 443), $this->get('monolog.logger.security'));
    }
    protected function getSecurity_HttpUtilsService()
    {
        return $this->services['security.http_utils'] = new \Symfony\Component\Security\Http\HttpUtils($this->get('router'));
    }
    protected function getTemplating_LocatorService()
    {
        return $this->services['templating.locator'] = new \Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator($this->get('file_locator'), 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod');
    }
    protected function getValidator_Mapping_ClassMetadataFactoryService()
    {
        return $this->services['validator.mapping.class_metadata_factory'] = new \Symfony\Component\Validator\Mapping\ClassMetadataFactory(new \Symfony\Component\Validator\Mapping\Loader\LoaderChain(array(0 => new \Symfony\Component\Validator\Mapping\Loader\AnnotationLoader($this->get('annotation_reader')), 1 => new \Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader(), 2 => new \Symfony\Component\Validator\Mapping\Loader\XmlFilesLoader(array(0 => 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Component\\Form/Resources/config/validation.xml')), 3 => new \Symfony\Component\Validator\Mapping\Loader\YamlFilesLoader(array()))), NULL);
    }
    public function getParameter($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->parameters)) {
            throw new \InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }
        return $this->parameters[$name];
    }
    public function hasParameter($name)
    {
        return array_key_exists(strtolower($name), $this->parameters);
    }
    public function setParameter($name, $value)
    {
        throw new \LogicException('Impossible to call set() on a frozen ParameterBag.');
    }
    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $this->parameterBag = new FrozenParameterBag($this->parameters);
        }
        return $this->parameterBag;
    }
    protected function getDefaultParameters()
    {
        return array(
            'kernel.root_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app',
            'kernel.environment' => 'prod',
            'kernel.debug' => false,
            'kernel.name' => 'app',
            'kernel.cache_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod',
            'kernel.logs_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/logs',
            'kernel.bundles' => array(
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'SecurityBundle' => 'Symfony\\Bundle\\SecurityBundle\\SecurityBundle',
                'TwigBundle' => 'Symfony\\Bundle\\TwigBundle\\TwigBundle',
                'MonologBundle' => 'Symfony\\Bundle\\MonologBundle\\MonologBundle',
                'SwiftmailerBundle' => 'Symfony\\Bundle\\SwiftmailerBundle\\SwiftmailerBundle',
                'DoctrineBundle' => 'Symfony\\Bundle\\DoctrineBundle\\DoctrineBundle',
                'DoctrineMongoDBBundle' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\DoctrineMongoDBBundle',
                'AsseticBundle' => 'Symfony\\Bundle\\AsseticBundle\\AsseticBundle',
                'SensioFrameworkExtraBundle' => 'Sensio\\Bundle\\FrameworkExtraBundle\\SensioFrameworkExtraBundle',
                'JMSSecurityExtraBundle' => 'JMS\\SecurityExtraBundle\\JMSSecurityExtraBundle',
                'JustsyBaseBundle' => 'Justsy\\BaseBundle\\JustsyBaseBundle',
                'JustsyMongoDocBundle' => 'Justsy\\MongoDocBundle\\JustsyMongoDocBundle',
                'JustsyInterfaceBundle' => 'Justsy\\InterfaceBundle\\JustsyInterfaceBundle',
                'JustsyOpenAPIBundle' => 'Justsy\\OpenAPIBundle\\JustsyOpenAPIBundle',
                'JustsyAdminAppBundle' => 'Justsy\\AdminAppBundle\\JustsyAdminAppBundle',
                'WebIMImChatBundle' => 'WebIM\\ImChatBundle\\WebIMImChatBundle',
                'WebIMImMainBundle' => 'WebIM\\ImMainBundle\\WebIMImMainBundle',
            ),
            'kernel.charset' => 'UTF-8',
            'kernel.container_class' => 'appProdProjectContainer',
            'database_driver' => 'pdo_mysql',
            'database_host' => '127.0.0.1',
            'database_port' => '3306',
            'database_name' => 'justsy_sns',
            'database_user' => 'justsy_sns',
            'database_password' => 'justsy_sns',
            'database_driver_im' => 'pdo_mysql',
            'database_host_im' => '127.0.0.1',
            'database_port_im' => '3306',
            'database_name_im' => 'justsy_im',
            'database_user_im' => 'justsy_im',
            'database_password_im' => 'justsy_im',
            'mailer_transport' => 'smtp',
            'mailer_host' => 'mailserver',
            'mailer_user' => 'mailuser',
            'mailer_password' => 'mailpwd',
            'locale' => 'zh',
            'secret' => '335a052790228abbc6ea61b49ie9280adfaow808',
            'mongodb_server' => 'mongodb://127.0.0.1:27017',
            'mongodb_default_database' => 'we',
            'mongodb_username' => 'we',
            'mongodb_password' => 'we',
            'ejabberd-server-http' => 'http://112.126.77.162:5280',
            'fafa_webim_url' => 'http://112.126.77.162:8000',
            'open_api_url' => 'http://112.126.77.162:8000',
            'fafa_appcenter_url' => 'http://localhost:8080',
            'fafa_wefafa_url' => 'http://112.126.77.162:8000',
            'fafa_reg_svr_url' => 'http://localhost:800',
            'fafa_reg_jid_url' => 'http://localhost:9527',
            'app_auth_url' => 'http://localhost/FaFaAppSm.ashx',
            'app_list_url' => 'http://localhost/FaFaAppMgr.ashx',
            'file_webserver_url' => 'http://112.126.77.162:8000/getfile/',
            'fafa_findpwd_url' => 'https://112.126.77.162/register/pwd/retrieve',
            'sms_act' => 'sms_id',
            'sms_pwd' => 'sms_pwd',
            'sms_url' => 'http://sms.c8686.com/Api/BayouSmsApiEx.aspx',
            'im_sender' => 'admin-100001@fafacn.com',
            'im_receiver' => '10001-100001@fafacn.com',
            'deploy_mode' => 'C',
            'ssoauthmodule' => 'WefafaAuth',
            'start_model' => 'MAPP',
            'eno' => '100000',
            'edomain' => 'justsy.com',
            'pdo.db_options' => array(
                'db_table' => 'session',
                'db_id_col' => 'session_id',
                'db_data_col' => 'session_value',
                'db_time_col' => 'session_time',
            ),
            'router_listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\RouterListener',
            'controller_resolver.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerResolver',
            'controller_name_converter.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerNameParser',
            'response_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ResponseListener',
            'event_dispatcher.class' => 'Symfony\\Bundle\\FrameworkBundle\\ContainerAwareEventDispatcher',
            'http_kernel.class' => 'Symfony\\Bundle\\FrameworkBundle\\HttpKernel',
            'filesystem.class' => 'Symfony\\Component\\Filesystem\\Filesystem',
            'cache_warmer.class' => 'Symfony\\Component\\HttpKernel\\CacheWarmer\\CacheWarmerAggregate',
            'file_locator.class' => 'Symfony\\Component\\HttpKernel\\Config\\FileLocator',
            'translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\Translator',
            'translator.identity.class' => 'Symfony\\Component\\Translation\\IdentityTranslator',
            'translator.selector.class' => 'Symfony\\Component\\Translation\\MessageSelector',
            'translation.loader.php.class' => 'Symfony\\Component\\Translation\\Loader\\PhpFileLoader',
            'translation.loader.yml.class' => 'Symfony\\Component\\Translation\\Loader\\YamlFileLoader',
            'translation.loader.xliff.class' => 'Symfony\\Component\\Translation\\Loader\\XliffFileLoader',
            'kernel.secret' => '335a052790228abbc6ea61b49ie9280adfaow808',
            'kernel.trust_proxy_headers' => false,
            'session.class' => 'Symfony\\Component\\HttpFoundation\\Session',
            'session.storage.native.class' => 'Symfony\\Component\\HttpFoundation\\SessionStorage\\NativeSessionStorage',
            'session.storage.filesystem.class' => 'Symfony\\Component\\HttpFoundation\\SessionStorage\\FilesystemSessionStorage',
            'session_listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\SessionListener',
            'session.default_locale' => 'zh',
            'session.storage.options' => array(
            ),
            'form.extension.class' => 'Symfony\\Component\\Form\\Extension\\DependencyInjection\\DependencyInjectionExtension',
            'form.factory.class' => 'Symfony\\Component\\Form\\FormFactory',
            'form.type_guesser.validator.class' => 'Symfony\\Component\\Form\\Extension\\Validator\\ValidatorTypeGuesser',
            'form.csrf_provider.class' => 'Symfony\\Component\\Form\\Extension\\Csrf\\CsrfProvider\\SessionCsrfProvider',
            'form.type_extension.csrf.enabled' => true,
            'form.type_extension.csrf.field_name' => '_token',
            'validator.class' => 'Symfony\\Component\\Validator\\Validator',
            'validator.mapping.class_metadata_factory.class' => 'Symfony\\Component\\Validator\\Mapping\\ClassMetadataFactory',
            'validator.mapping.cache.apc.class' => 'Symfony\\Component\\Validator\\Mapping\\Cache\\ApcCache',
            'validator.mapping.cache.prefix' => '',
            'validator.mapping.loader.loader_chain.class' => 'Symfony\\Component\\Validator\\Mapping\\Loader\\LoaderChain',
            'validator.mapping.loader.static_method_loader.class' => 'Symfony\\Component\\Validator\\Mapping\\Loader\\StaticMethodLoader',
            'validator.mapping.loader.annotation_loader.class' => 'Symfony\\Component\\Validator\\Mapping\\Loader\\AnnotationLoader',
            'validator.mapping.loader.xml_files_loader.class' => 'Symfony\\Component\\Validator\\Mapping\\Loader\\XmlFilesLoader',
            'validator.mapping.loader.yaml_files_loader.class' => 'Symfony\\Component\\Validator\\Mapping\\Loader\\YamlFilesLoader',
            'validator.validator_factory.class' => 'Symfony\\Bundle\\FrameworkBundle\\Validator\\ConstraintValidatorFactory',
            'validator.mapping.loader.xml_files_loader.mapping_files' => array(
                0 => 'E:\\work\\Code\\Justsy Push-RESTService\\Trunk\\vendor\\symfony\\src\\Symfony\\Component\\Form/Resources/config/validation.xml',
            ),
            'validator.mapping.loader.yaml_files_loader.mapping_files' => array(
            ),
            'router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\Router',
            'routing.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\DelegatingLoader',
            'routing.resolver.class' => 'Symfony\\Component\\Config\\Loader\\LoaderResolver',
            'routing.loader.xml.class' => 'Symfony\\Component\\Routing\\Loader\\XmlFileLoader',
            'routing.loader.yml.class' => 'Symfony\\Component\\Routing\\Loader\\YamlFileLoader',
            'routing.loader.php.class' => 'Symfony\\Component\\Routing\\Loader\\PhpFileLoader',
            'router.options.generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'router.options.matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'router.cache_warmer.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\RouterCacheWarmer',
            'router.options.matcher.cache_class' => 'appprodUrlMatcher',
            'router.options.generator.cache_class' => 'appprodUrlGenerator',
            'router.resource' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/config/routing.yml',
            'request_listener.http_port' => 80,
            'request_listener.https_port' => 443,
            'templating.engine.delegating.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\DelegatingEngine',
            'templating.name_parser.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TemplateNameParser',
            'templating.cache_warmer.template_paths.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplatePathsCacheWarmer',
            'templating.locator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\TemplateLocator',
            'templating.loader.filesystem.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\FilesystemLoader',
            'templating.loader.cache.class' => 'Symfony\\Component\\Templating\\Loader\\CacheLoader',
            'templating.loader.chain.class' => 'Symfony\\Component\\Templating\\Loader\\ChainLoader',
            'templating.finder.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplateFinder',
            'templating.engine.php.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\PhpEngine',
            'templating.helper.slots.class' => 'Symfony\\Component\\Templating\\Helper\\SlotsHelper',
            'templating.helper.assets.class' => 'Symfony\\Component\\Templating\\Helper\\CoreAssetsHelper',
            'templating.helper.actions.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\ActionsHelper',
            'templating.helper.router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\RouterHelper',
            'templating.helper.request.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\RequestHelper',
            'templating.helper.session.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\SessionHelper',
            'templating.helper.code.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\CodeHelper',
            'templating.helper.translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\TranslatorHelper',
            'templating.helper.form.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\FormHelper',
            'templating.globals.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\GlobalVariables',
            'templating.asset.path_package.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Asset\\PathPackage',
            'templating.asset.url_package.class' => 'Symfony\\Component\\Templating\\Asset\\UrlPackage',
            'templating.asset.package_factory.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Asset\\PackageFactory',
            'templating.helper.code.file_link_format' => NULL,
            'templating.helper.form.resources' => array(
                0 => 'FrameworkBundle:Form',
            ),
            'templating.loader.cache.path' => NULL,
            'templating.engines' => array(
                0 => 'twig',
            ),
            'annotations.reader.class' => 'Doctrine\\Common\\Annotations\\AnnotationReader',
            'annotations.cached_reader.class' => 'Doctrine\\Common\\Annotations\\CachedReader',
            'annotations.file_cache_reader.class' => 'Doctrine\\Common\\Annotations\\FileCacheReader',
            'security.context.class' => 'Symfony\\Component\\Security\\Core\\SecurityContext',
            'security.user_checker.class' => 'Symfony\\Component\\Security\\Core\\User\\UserChecker',
            'security.encoder_factory.generic.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\EncoderFactory',
            'security.encoder.digest.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\MessageDigestPasswordEncoder',
            'security.encoder.plain.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\PlaintextPasswordEncoder',
            'security.user.provider.entity.class' => 'Symfony\\Bridge\\Doctrine\\Security\\User\\EntityUserProvider',
            'security.user.provider.in_memory.class' => 'Symfony\\Component\\Security\\Core\\User\\InMemoryUserProvider',
            'security.user.provider.in_memory.user.class' => 'Symfony\\Component\\Security\\Core\\User\\User',
            'security.user.provider.chain.class' => 'Symfony\\Component\\Security\\Core\\User\\ChainUserProvider',
            'security.authentication.trust_resolver.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationTrustResolver',
            'security.authentication.trust_resolver.anonymous_class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken',
            'security.authentication.trust_resolver.rememberme_class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken',
            'security.authentication.manager.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationProviderManager',
            'security.authentication.session_strategy.class' => 'Symfony\\Component\\Security\\Http\\Session\\SessionAuthenticationStrategy',
            'security.access.decision_manager.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\AccessDecisionManager',
            'security.access.simple_role_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\RoleVoter',
            'security.access.authenticated_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\AuthenticatedVoter',
            'security.access.role_hierarchy_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\RoleHierarchyVoter',
            'security.firewall.class' => 'Symfony\\Component\\Security\\Http\\Firewall',
            'security.firewall.map.class' => 'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallMap',
            'security.firewall.context.class' => 'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallContext',
            'security.matcher.class' => 'Symfony\\Component\\HttpFoundation\\RequestMatcher',
            'security.role_hierarchy.class' => 'Symfony\\Component\\Security\\Core\\Role\\RoleHierarchy',
            'security.http_utils.class' => 'Symfony\\Component\\Security\\Http\\HttpUtils',
            'security.authentication.retry_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\RetryAuthenticationEntryPoint',
            'security.channel_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ChannelListener',
            'security.authentication.form_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\FormAuthenticationEntryPoint',
            'security.authentication.listener.form.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\UsernamePasswordFormAuthenticationListener',
            'security.authentication.listener.basic.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\BasicAuthenticationListener',
            'security.authentication.basic_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\BasicAuthenticationEntryPoint',
            'security.authentication.listener.digest.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\DigestAuthenticationListener',
            'security.authentication.digest_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\DigestAuthenticationEntryPoint',
            'security.authentication.listener.x509.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\X509AuthenticationListener',
            'security.authentication.listener.anonymous.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\AnonymousAuthenticationListener',
            'security.authentication.switchuser_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\SwitchUserListener',
            'security.logout_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\LogoutListener',
            'security.logout.handler.session.class' => 'Symfony\\Component\\Security\\Http\\Logout\\SessionLogoutHandler',
            'security.logout.handler.cookie_clearing.class' => 'Symfony\\Component\\Security\\Http\\Logout\\CookieClearingLogoutHandler',
            'security.access_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\AccessListener',
            'security.access_map.class' => 'Symfony\\Component\\Security\\Http\\AccessMap',
            'security.exception_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ExceptionListener',
            'security.context_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ContextListener',
            'security.authentication.provider.dao.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\DaoAuthenticationProvider',
            'security.authentication.provider.pre_authenticated.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\PreAuthenticatedAuthenticationProvider',
            'security.authentication.provider.anonymous.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\AnonymousAuthenticationProvider',
            'security.authentication.provider.rememberme.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\RememberMeAuthenticationProvider',
            'security.authentication.listener.rememberme.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\RememberMeListener',
            'security.rememberme.token.provider.in_memory.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\RememberMe\\InMemoryTokenProvider',
            'security.authentication.rememberme.services.persistent.class' => 'Symfony\\Component\\Security\\Http\\RememberMe\\PersistentTokenBasedRememberMeServices',
            'security.authentication.rememberme.services.simplehash.class' => 'Symfony\\Component\\Security\\Http\\RememberMe\\TokenBasedRememberMeServices',
            'security.rememberme.response_listener.class' => 'Symfony\\Bundle\\SecurityBundle\\EventListener\\ResponseListener',
            'templating.helper.security.class' => 'Symfony\\Bundle\\SecurityBundle\\Templating\\Helper\\SecurityHelper',
            'data_collector.security.class' => 'Symfony\\Bundle\\SecurityBundle\\DataCollector\\SecurityDataCollector',
            'security.access.denied_url' => NULL,
            'security.authentication.session_strategy.strategy' => 'migrate',
            'security.access.always_authenticate_before_granting' => false,
            'security.authentication.hide_user_not_found' => true,
            'security.role_hierarchy.roles' => array(
                'ROLE_ADMIN' => array(
                    0 => 'ROLE_USER',
                ),
                'ROLE_SUPER_ADMIN' => array(
                    0 => 'ROLE_USER',
                    1 => 'ROLE_ADMIN',
                    2 => 'ROLE_ALLOWED_TO_SWITCH',
                ),
            ),
            'twig.class' => 'Twig_Environment',
            'twig.loader.class' => 'Symfony\\Bundle\\TwigBundle\\Loader\\FilesystemLoader',
            'templating.engine.twig.class' => 'Symfony\\Bundle\\TwigBundle\\TwigEngine',
            'twig.cache_warmer.class' => 'Symfony\\Bundle\\TwigBundle\\CacheWarmer\\TemplateCacheCacheWarmer',
            'twig.extension.trans.class' => 'Symfony\\Bridge\\Twig\\Extension\\TranslationExtension',
            'twig.extension.assets.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\AssetsExtension',
            'twig.extension.actions.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\ActionsExtension',
            'twig.extension.code.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\CodeExtension',
            'twig.extension.routing.class' => 'Symfony\\Bridge\\Twig\\Extension\\RoutingExtension',
            'twig.extension.yaml.class' => 'Symfony\\Bridge\\Twig\\Extension\\YamlExtension',
            'twig.extension.form.class' => 'Symfony\\Bridge\\Twig\\Extension\\FormExtension',
            'twig.exception_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ExceptionListener',
            'twig.exception_listener.controller' => 'Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController::showAction',
            'twig.form.resources' => array(
                0 => 'form_div_layout.html.twig',
            ),
            'twig.options' => array(
                'debug' => false,
                'strict_variables' => false,
                'exception_controller' => 'Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController::showAction',
                'cache' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/twig',
                'charset' => 'UTF-8',
            ),
            'monolog.logger.class' => 'Symfony\\Bridge\\Monolog\\Logger',
            'monolog.handler.stream.class' => 'Monolog\\Handler\\StreamHandler',
            'monolog.handler.fingers_crossed.class' => 'Monolog\\Handler\\FingersCrossedHandler',
            'monolog.handler.group.class' => 'Monolog\\Handler\\GroupHandler',
            'monolog.handler.buffer.class' => 'Monolog\\Handler\\BufferHandler',
            'monolog.handler.rotating_file.class' => 'Monolog\\Handler\\RotatingFileHandler',
            'monolog.handler.syslog.class' => 'Monolog\\Handler\\SyslogHandler',
            'monolog.handler.null.class' => 'Monolog\\Handler\\NullHandler',
            'monolog.handler.test.class' => 'Monolog\\Handler\\TestHandler',
            'monolog.handler.firephp.class' => 'Symfony\\Bridge\\Monolog\\Handler\\FirePHPHandler',
            'monolog.handler.debug.class' => 'Symfony\\Bridge\\Monolog\\Handler\\DebugHandler',
            'monolog.handler.swift_mailer.class' => 'Monolog\\Handler\\SwiftMailerHandler',
            'monolog.handler.native_mailer.class' => 'Monolog\\Handler\\NativeMailerHandler',
            'swiftmailer.class' => 'Swift_Mailer',
            'swiftmailer.transport.sendmail.class' => 'Swift_Transport_SendmailTransport',
            'swiftmailer.transport.mail.class' => 'Swift_Transport_MailTransport',
            'swiftmailer.transport.failover.class' => 'Swift_Transport_FailoverTransport',
            'swiftmailer.plugin.redirecting.class' => 'Swift_Plugins_RedirectingPlugin',
            'swiftmailer.plugin.impersonate.class' => 'Swift_Plugins_ImpersonatePlugin',
            'swiftmailer.plugin.messagelogger.class' => 'Symfony\\Bundle\\SwiftmailerBundle\\Logger\\MessageLogger',
            'swiftmailer.plugin.antiflood.class' => 'Swift_Plugins_AntiFloodPlugin',
            'swiftmailer.plugin.antiflood.threshold' => 99,
            'swiftmailer.plugin.antiflood.sleep' => 0,
            'swiftmailer.data_collector.class' => 'Symfony\\Bundle\\SwiftmailerBundle\\DataCollector\\MessageDataCollector',
            'swiftmailer.transport.smtp.class' => 'Swift_Transport_EsmtpTransport',
            'swiftmailer.transport.smtp.encryption' => NULL,
            'swiftmailer.transport.smtp.port' => 25,
            'swiftmailer.transport.smtp.host' => 'mailserver',
            'swiftmailer.transport.smtp.username' => 'mailuser',
            'swiftmailer.transport.smtp.password' => 'mailpwd',
            'swiftmailer.transport.smtp.auth_mode' => 'login',
            'swiftmailer.spool.enabled' => false,
            'swiftmailer.sender_address' => NULL,
            'swiftmailer.single_address' => NULL,
            'doctrine.dbal.logger.debug.class' => 'Doctrine\\DBAL\\Logging\\DebugStack',
            'doctrine.dbal.logger.class' => 'Symfony\\Bridge\\Doctrine\\Logger\\DbalLogger',
            'doctrine.dbal.configuration.class' => 'Doctrine\\DBAL\\Configuration',
            'doctrine.data_collector.class' => 'Symfony\\Bridge\\Doctrine\\DataCollector\\DoctrineDataCollector',
            'doctrine.dbal.connection.event_manager.class' => 'Doctrine\\Common\\EventManager',
            'doctrine.dbal.connection_factory.class' => 'Symfony\\Bundle\\DoctrineBundle\\ConnectionFactory',
            'doctrine.dbal.events.mysql_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\MysqlSessionInit',
            'doctrine.dbal.events.oracle_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\OracleSessionInit',
            'doctrine.class' => 'Symfony\\Bundle\\DoctrineBundle\\Registry',
            'doctrine.entity_managers' => array(
                'default' => 'doctrine.orm.default_entity_manager',
            ),
            'doctrine.default_entity_manager' => 'default',
            'doctrine.dbal.connection_factory.types' => array(
            ),
            'doctrine.connections' => array(
                'default' => 'doctrine.dbal.default_connection',
                'im' => 'doctrine.dbal.im_connection',
            ),
            'doctrine.default_connection' => 'default',
            'doctrine.orm.configuration.class' => 'Doctrine\\ORM\\Configuration',
            'doctrine.orm.entity_manager.class' => 'Doctrine\\ORM\\EntityManager',
            'doctrine.orm.cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine.orm.cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine.orm.cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine.orm.cache.memcache_host' => 'localhost',
            'doctrine.orm.cache.memcache_port' => 11211,
            'doctrine.orm.cache.memcache_instance.class' => 'Memcache',
            'doctrine.orm.cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'doctrine.orm.metadata.driver_chain.class' => 'Doctrine\\ORM\\Mapping\\Driver\\DriverChain',
            'doctrine.orm.metadata.annotation.class' => 'Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver',
            'doctrine.orm.metadata.annotation_reader.class' => 'Symfony\\Bridge\\Doctrine\\Annotations\\IndexedReader',
            'doctrine.orm.metadata.xml.class' => 'Symfony\\Bridge\\Doctrine\\Mapping\\Driver\\XmlDriver',
            'doctrine.orm.metadata.yml.class' => 'Symfony\\Bridge\\Doctrine\\Mapping\\Driver\\YamlDriver',
            'doctrine.orm.metadata.php.class' => 'Doctrine\\ORM\\Mapping\\Driver\\PHPDriver',
            'doctrine.orm.metadata.staticphp.class' => 'Doctrine\\ORM\\Mapping\\Driver\\StaticPHPDriver',
            'doctrine.orm.proxy_cache_warmer.class' => 'Symfony\\Bridge\\Doctrine\\CacheWarmer\\ProxyCacheWarmer',
            'form.type_guesser.doctrine.class' => 'Symfony\\Bridge\\Doctrine\\Form\\DoctrineOrmTypeGuesser',
            'doctrine.orm.validator.unique.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntityValidator',
            'doctrine.orm.validator_initializer.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\EntityInitializer',
            'doctrine.orm.auto_generate_proxy_classes' => false,
            'doctrine.orm.proxy_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/orm/Proxies',
            'doctrine.orm.proxy_namespace' => 'Proxies',
            'doctrine.odm.mongodb.connection.class' => 'Doctrine\\MongoDB\\Connection',
            'doctrine.odm.mongodb.configuration.class' => 'Doctrine\\ODM\\MongoDB\\Configuration',
            'doctrine.odm.mongodb.document_manager.class' => 'Doctrine\\ODM\\MongoDB\\DocumentManager',
            'doctrine.odm.mongodb.logger.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\Logger\\DoctrineMongoDBLogger',
            'doctrine.odm.mongodb.data_collector.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\DataCollector\\DoctrineMongoDBDataCollector',
            'doctrine.odm.mongodb.event_manager.class' => 'Doctrine\\Common\\EventManager',
            'doctrine.odm.mongodb.proxy_namespace' => 'Proxies',
            'doctrine.odm.mongodb.proxy_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/odm/mongodb/Proxies',
            'doctrine.odm.mongodb.auto_generate_proxy_classes' => false,
            'doctrine.odm.mongodb.hydrator_namespace' => 'Hydrators',
            'doctrine.odm.mongodb.hydrator_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/doctrine/odm/mongodb/Hydrators',
            'doctrine.odm.mongodb.auto_generate_hydrator_classes' => false,
            'doctrine.odm.mongodb.cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine.odm.mongodb.cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine.odm.mongodb.cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine.odm.mongodb.cache.memcache_host' => 'localhost',
            'doctrine.odm.mongodb.cache.memcache_port' => 11211,
            'doctrine.odm.mongodb.cache.memcache_instance.class' => 'Memcache',
            'doctrine.odm.mongodb.cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'doctrine.odm.mongodb.metadata.driver_chain.class' => 'Doctrine\\Common\\Persistence\\Mapping\\Driver\\MappingDriverChain',
            'doctrine.odm.mongodb.metadata.annotation.class' => 'Doctrine\\ODM\\MongoDB\\Mapping\\Driver\\AnnotationDriver',
            'doctrine.odm.mongodb.metadata.xml.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\Mapping\\Driver\\XmlDriver',
            'doctrine.odm.mongodb.metadata.yml.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\Mapping\\Driver\\YamlDriver',
            'doctrine.odm.mongodb.mapping_dirs' => array(
            ),
            'doctrine.odm.mongodb.xml_mapping_dirs' => array(
            ),
            'doctrine.odm.mongodb.yml_mapping_dirs' => array(
            ),
            'doctrine.odm.mongodb.document_dirs' => array(
            ),
            'doctrine.odm.mongodb.security.user.provider.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\Security\\DocumentUserProvider',
            'doctrine.odm.mongodb.proxy_cache_warmer.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\CacheWarmer\\ProxyCacheWarmer',
            'doctrine.odm.mongodb.hydrator_cache_warmer.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\CacheWarmer\\HydratorCacheWarmer',
            'doctrine_odm.mongodb.validator.unique.class' => 'Symfony\\Bundle\\DoctrineMongoDBBundle\\Validator\\Constraints\\UniqueValidator',
            'doctrine.odm.mongodb.document_managers' => array(
                0 => 'default',
            ),
            'assetic.asset_factory.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\AssetFactory',
            'assetic.asset_manager.class' => 'Assetic\\Factory\\LazyAssetManager',
            'assetic.asset_manager_cache_warmer.class' => 'Symfony\\Bundle\\AsseticBundle\\CacheWarmer\\AssetManagerCacheWarmer',
            'assetic.cached_formula_loader.class' => 'Assetic\\Factory\\Loader\\CachedFormulaLoader',
            'assetic.config_cache.class' => 'Assetic\\Cache\\ConfigCache',
            'assetic.config_loader.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\Loader\\ConfigurationLoader',
            'assetic.config_resource.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\Resource\\ConfigurationResource',
            'assetic.coalescing_directory_resource.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\Resource\\CoalescingDirectoryResource',
            'assetic.directory_resource.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\Resource\\DirectoryResource',
            'assetic.filter_manager.class' => 'Symfony\\Bundle\\AsseticBundle\\FilterManager',
            'assetic.worker.ensure_filter.class' => 'Assetic\\Factory\\Worker\\EnsureFilterWorker',
            'assetic.node.paths' => array(
            ),
            'assetic.cache_dir' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/cache/prod/assetic',
            'assetic.bundles' => array(
                0 => 'FrameworkBundle',
                1 => 'SecurityBundle',
                2 => 'TwigBundle',
                3 => 'MonologBundle',
                4 => 'SwiftmailerBundle',
                5 => 'DoctrineBundle',
                6 => 'DoctrineMongoDBBundle',
                7 => 'AsseticBundle',
                8 => 'SensioFrameworkExtraBundle',
                9 => 'JMSSecurityExtraBundle',
                10 => 'JustsyBaseBundle',
                11 => 'JustsyMongoDocBundle',
                12 => 'JustsyInterfaceBundle',
                13 => 'JustsyOpenAPIBundle',
                14 => 'JustsyAdminAppBundle',
                15 => 'WebIMImChatBundle',
                16 => 'WebIMImMainBundle',
            ),
            'assetic.twig_extension.class' => 'Symfony\\Bundle\\AsseticBundle\\Twig\\AsseticExtension',
            'assetic.twig_formula_loader.class' => 'Assetic\\Extension\\Twig\\TwigFormulaLoader',
            'assetic.helper.dynamic.class' => 'Symfony\\Bundle\\AsseticBundle\\Templating\\DynamicAsseticHelper',
            'assetic.helper.static.class' => 'Symfony\\Bundle\\AsseticBundle\\Templating\\StaticAsseticHelper',
            'assetic.php_formula_loader.class' => 'Symfony\\Bundle\\AsseticBundle\\Factory\\Loader\\AsseticHelperFormulaLoader',
            'assetic.debug' => false,
            'assetic.use_controller' => false,
            'assetic.enable_profiler' => false,
            'assetic.read_from' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/../web',
            'assetic.write_to' => 'E:/work/Code/Justsy Push-RESTService/Trunk/app/../web',
            'assetic.java.bin' => 'C:\\Program Files\\Java\\jdk1.7.0_11\\bin\\java.EXE',
            'assetic.node.bin' => '/usr/bin/node',
            'assetic.ruby.bin' => '/usr/bin/ruby',
            'assetic.sass.bin' => '/usr/bin/sass',
            'assetic.filter.cssrewrite.class' => 'Assetic\\Filter\\CssRewriteFilter',
            'assetic.twig_extension.functions' => array(
            ),
            'assetic.asset_writer_cache_warmer.class' => 'Symfony\\Bundle\\AsseticBundle\\CacheWarmer\\AssetWriterCacheWarmer',
            'assetic.asset_writer.class' => 'Assetic\\AssetWriter',
            'sensio_framework_extra.controller.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ControllerListener',
            'sensio_framework_extra.routing.loader.annot_dir.class' => 'Symfony\\Component\\Routing\\Loader\\AnnotationDirectoryLoader',
            'sensio_framework_extra.routing.loader.annot_file.class' => 'Symfony\\Component\\Routing\\Loader\\AnnotationFileLoader',
            'sensio_framework_extra.routing.loader.annot_class.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Routing\\AnnotatedRouteControllerLoader',
            'sensio_framework_extra.converter.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ParamConverterListener',
            'sensio_framework_extra.converter.manager.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Request\\ParamConverter\\ParamConverterManager',
            'sensio_framework_extra.converter.doctrine.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Request\\ParamConverter\\DoctrineParamConverter',
            'sensio_framework_extra.view.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\TemplateListener',
            'security.secured_services' => array(
            ),
            'security.access.method_interceptor.class' => 'JMS\\SecurityExtraBundle\\Security\\Authorization\\Interception\\MethodSecurityInterceptor',
            'security.access.run_as_manager.class' => 'JMS\\SecurityExtraBundle\\Security\\Authorization\\RunAsManager',
            'security.authentication.provider.run_as.class' => 'JMS\\SecurityExtraBundle\\Security\\Authentication\\Provider\\RunAsAuthenticationProvider',
            'security.run_as.key' => 'RunAsToken',
            'security.run_as.role_prefix' => 'ROLE_',
            'security.access.after_invocation_manager.class' => 'JMS\\SecurityExtraBundle\\Security\\Authorization\\AfterInvocation\\AfterInvocationManager',
            'security.access.after_invocation.acl_provider.class' => 'JMS\\SecurityExtraBundle\\Security\\Authorization\\AfterInvocation\\AclAfterInvocationProvider',
            'security.extra.controller_listener.class' => 'JMS\\SecurityExtraBundle\\Controller\\ControllerListener',
            'security.access.iddqd_voter.class' => 'JMS\\SecurityExtraBundle\\Security\\Authorization\\Voter\\IddqdVoter',
            'security.extra.secure_all_services' => false,
        );
    }
}
