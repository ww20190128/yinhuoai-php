<?php
namespace Dispatch;
use Application;

/**
 * 框架根基类 
 * 
 * @author wangwei
 */
abstract class FrameBase
{
	/**
     * 服务器id
     * 
     * @var int
     */
    protected $serverId;
	
    /**
     * 单例构造器
     * 
     * @var SingletonLocator
     */
    protected $locator;

    /**
     * 用于修复dao多次实例化问题
     *
     * @var
     */
    private static $_dao;
    
    /**
     * 数据库操作组件
     *
     * @var $daoHelper
     */
    protected $dao;
    
    /**
     * 缓存器
     * 
     * @var ICache
     */
   	protected $cache;
    
    /**
     * 异常器
     * 
     * @var exception
     */
    protected $exception;
     
    /**
     * 动态数据库操作器
     * 
     * @var $daoHelper
     */
    protected $daoHelper;
    
    /**
     * 调度操作器
     *
     * @var $dispatcher
     */
    protected $dispatcher;
    
    /**
     * 静态数据库操作器
     * 
     * @var $daoHelper
     */
    protected $staticDaoHelper;

    /**
     * 视图操作器
     *
     * @var $view
     */
    protected $view;
    
    /**
     * 框架标准数组
     * 
     * @var array
     */
    protected $frame;

    /**
     * 构造函数  初始化环境
     *
     * @param  string 	继承子类的令名空间
     *
     * @return
     */
    public function __construct($childNameSpace = null)
    {
    	$this->frame = &Application::$Frame;
    	$this->serverId = $this->frame->id;		// 初始化服务器id
    	$this->cache = &Application::$Cache;	// 缓存器
        switch ($childNameSpace) {
        	case 'ctrl':
        		$this->view = &Application::$View;
        		$this->dispatcher = &Application::$Dispatcher;		
        		break;
        	case 'dao':   		
        		$this->daoHelper = &Application::$DaoHelper; // 数据库操作器
        		$this->staticDaoHelper = clone Application::$DaoHelper; 	// 静态数据库操作器
               	$this->addShutdownCallBack(array($this->daoHelper, 'flush'));
        		break;
        	default:
        		break;		
        }
        // 加载数据库操作组件
        if (empty(self::$_dao) && !empty($this->frame->conf['dao'])) {
            loadFile(array('DaoHelper', 'PDODb', 'RedisCache'), LIB_PATH . 'Dao' . DS);
            self::$_dao = \Dao\DaoHelper::singleton();
            self::$_dao->init($this->frame->conf['dao']);
        }
        if (!empty(self::$_dao)) {
            $this->dao = self::$_dao;
        }

        $this->locator   = &Application::$Locator;		// 定位器
        $this->exception = &Application::$Exception;	// 异常处理器       
        return;
    }

    /**
     * 动态加载框架信息
     *
     * @param   string  $name   属性
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == 'Global_serverConfs') {
            $confs =  \Bootstrap::getConfigures();
            $return = array();
            if (is_iteratable($confs)) foreach ($confs as $conf) {
            	if (empty($conf['database']['dynamic']) || empty($conf['id'])
            		|| empty($conf['communicate']) 
            		|| (!empty($conf['host']) && $conf['host'] == '127.0.0.1')) {
            		continue;
            	}
            	$return[$conf['id']] = $conf;
            }      
            return $return;
        } elseif ($name == 'Global_serverConf') {
        	return (object)\Bootstrap::getConfigures(\Bootstrap::getHost());
        } 
        return;
    }
    
	/**
     * 注册回调入口
     * 
     * @return vold
     */
    public function addShutdownCallBack()
    {
       	\Application::addShutdownCallBack(func_get_args());
        return;
    }

}