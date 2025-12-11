<?php
define('MAGIC_QUOTES_GPC', ini_set('magic_quotes_runtime',0) ? True : False);
/**
 * 框架实体
 * 
 * @author wangwei
 */
class Application
{	
    private static $cleanup = array();			// 进程结束清理资源数组
    private static $configuration = array(); 	// 框架配置参数
    // 框架组件模块
    public static $Locator;    		// 单例定位器
    public static $Cache;      		// 缓存
    public static $DaoHelper;  		// 数据库操作对象
    public static $Dispatcher; 		// 调度器
    // 不构建实体,只获取类名
    public static $View;       		// 视图
  	public static $Exception;  		// 异常
  	// 框架标准
    public static $Frame = array();
        
	/**
     * 框架预置
     * 
     * @param	bool	$useSession		是否启用session
     * 
     * @return void
     */
    private static function initialization($useSession = true)
    {
		if (!Bootstrap::$configuration) {
			return;
		}
		$configuration = Bootstrap::$configuration;	
    	self::setConfiguration($configuration['drive']);
        $drives = array();
        foreach (self::$configuration as $drive => $args) {
            list($drive, $module) = explode('.', $drive);
        	$drives[$drive][$module] = self::loadDrive($drive, $args->type, $args->args, $args->createObj);
        }        
        self::$DaoHelper	= isset($drives['db'])			? array_pop($drives['db'])	        : null;
        self::$Cache		= isset($drives['cache'])     	? array_pop($drives['cache'])	    : null;   
        self::$View			= isset($drives['view'])      	? array_pop($drives['view'])	    : null;
        self::$Exception	= isset($drives['exception']) 	? array_pop($drives['exception'])	: null; 
       	self::$Locator		= new SingletonConstructor(); // 保存单例定位器
        // 初始化框架标准
        self::initCriterion($configuration['server']);
        self::initRuntime(); // 初始化运行的内存, 时间，用于_e函数统计程序执行效率
       	register_shutdown_function(array(__CLASS__, 'shutdown')); // 注册进程结束回
       	
       	if (empty($_REQUEST['op'])) {
       	    if (!empty($_SERVER['REQUEST_URI'])) {
       	        $pathInfo = parse_url($_SERVER['REQUEST_URI']);
       	        $pathInfo = empty($pathInfo['path']) ? '' : explode(DS, $pathInfo['path']);
       	        if (count($pathInfo) >= 3) {
       	            $_REQUEST['op'] = ucfirst($pathInfo['1']) . '.' . $pathInfo['2'];
       	        }
       	    }
       	}

       	// 防注入过滤
//     	if (!get_magic_quotes_gpc()) {
    		recursion_addslashes($_GET);
            recursion_addslashes($_COOKIE);
            recursion_addslashes($_POST);
            recursion_addslashes($_REQUEST);
//         }
        $dispatcherClassName = 'drive' . CS . 'dispatcher' . CS 
        	. ucfirst(Bootstrap::$runType == Bootstrap::RUN_MODE_WEB ? 'web' : 'shell') . 'Dispatcher';
        self::$Dispatcher = new $dispatcherClassName();
     	// 只有单个服务器才启动session
        if (Bootstrap::$runType == Bootstrap::RUN_MODE_WEB 
        	&& $useSession && !empty($configuration['server']['conf']['session_switch'])) {
       		$params = self::$Dispatcher->getParams();
       		if (!empty($params->key) && strlen($params->key) >= 5) {
				session_id($params->key);
       		}
       		new \Session(self::$Cache, $configuration['server']); // 启动session
       	}
        return;
    }
    
	/**
	 * 应用程序入口
	 * 
     * @param	int		$runType	运行模式
     * 
     * @return void
     */
    public static function run($runType = Bootstrap::RUN_MODE_WEB) 
    {   
    	self::initialization(); // 初始化
    	if ($runType != Bootstrap::RUN_MODE_NO) { // 请求分发
            $dispatcher = self::$Dispatcher;  
            $params = self::$Dispatcher->getParams();
            $output = null; // 响应结果
            if (!empty($params->key) && !empty($params->requestFlag)) { // 从缓存结果中判断是否重试
            	$requestResult = self::$Cache->get('requestResult:' . $params->key);
            	if (!empty($requestResult['requestFlag']) && $requestResult['requestFlag'] == $params->requestFlag) {
            		$output = $requestResult; // 重试结果
            	}
            }
            if (is_null($output)) {
            	$output = $dispatcher->distribute();
            }
            try {
            	if (class_exists(self::$View)) {
            		$view = new self::$View;
            		$opSpendTime = _e(true);
            		
            		if ($opSpendTime >= 30 && !empty($_REQUEST['op'])) { // 超过2秒
            			$recordLog = true; // 记录日志
            			if ($_REQUEST['op'] == 'Question.getList' && $opSpendTime <= 4) { // 题目搜索
            				$recordLog = false;
            			}
            			if (!empty($recordLog)) {
            				$userId = empty($params->userId) ? 0 : $params->userId;
            				unset($params->userId, $params->loginKey, $params->op);
            				$log = array(
            					'op' 			=> $_REQUEST['op'],
            					'userId'		=> $userId,
            					'time'			=> $opSpendTime,
            					'params'		=> json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            					'createTime'	=> time(),
            				);
            				$sql = 'INSERT INTO `requestLog` (`' . implode('`,`', array_keys($log)) . "`) VALUES ('"
            					. implode("','", array_values($log)) . "');";
            				self::$DaoHelper->execBySql($sql);
            			}
            		}
            		$view->display($output);
            	} else {
            	   
            		echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            		exit;
            	}
            } catch (Exception $e) {
            }
        }
        return;
    }
    
	/**
     * 根据服务器id重载框架
     * 
     * @param	int		$serverId	服务器id
     * 
     * return bool
     */
    public static function reload($serverId) 
    {
    	$conf = Bootstrap::getConfigures($serverId);
    	if (empty($conf)) {
    		return false;
    	}
    	self::$configuration 	= array();
    	self::$Frame 			= array();
    	Bootstrap::$configuration = Bootstrap::initConfiguration($conf);
		self::initialization(false); // 初始化
		return true;
    }
   	
	/**
     * 初始化框架标准
     *
     * @param   array   $server     服务器信息
     * 
     * @return void
     */
    private static function initCriterion($server)
    {
    	self::$Frame['now'] = time();
    	self::$Frame['serverStartTime'] = empty($server['conf']['server_start_time']) 
    		? 0 : strtotime($server['conf']['server_start_time']);
      	self::$Frame = array_merge(self::$Frame, $server);
        self::$Frame = (object)self::$Frame;  
        return;
    }
    
    /**
     * 初始化运行信息
     * 
     * @return void
     */
    private static function initRuntime() 
    {
        $GLOBALS['runtime'] = array(
            'memoryUsage' => isset($GLOBALS['runtime']['memoryUsage']) 
        						 ? $GLOBALS['runtime']['memoryUsage'] : memory_get_usage(),
            'startTime'   => isset($GLOBALS['runtime']['startTime'])
        						 ? $GLOBALS['runtime']['startTime'] : microtime(true),
        );      
        return;
    }
    
	/**
     * 设置框架参数
     * 
     * @param 	array  $configuration   框架参数
     * 
     * @return bool
     */
    private static function setConfiguration(array $configuration) 
    {	
    	if (self::$configuration) {
    		return true;
    	}
    	foreach($configuration as $option => $args) {
            if ($args) foreach ($args as $type => $info) {
    			if (isset($info['args']['switch']) && !$info['args']['switch']) {
    				continue;	
    			}
        		self::$configuration[$option . '.' . $type] = (object)array(
        			'type' 		=> $type, 
        			'args' 		=> isset($info['args']) ? $info['args'] : null,
        			'createObj' => isset($info['createObj']) ? $info['createObj'] : true,
        		);
    		}
    	}
    	return true;
    }
    
	/**
     * 加载功能模块
     * 
     * @param  string		$drive    	功能名 
     * @param  string 		$type      	类型 
     * @param  array|obj	$args       参数列表
     * @param  bool			$createObj	是否返回实体
     * 
     * @return obj|string
     */
    private static function loadDrive($drive, $type, $args = null, $createObj = true)
    {
    	$class = 'drive' . CS . $drive . CS . $type . ucfirst($drive);
        if ($createObj) {
        	return new $class($args);
        } elseif ($args && function_exists($class::init)) { // 模块提供初始化的静态接口init
        	$class::init($args);
        }
        return $class;
    }
        
	/**
     * 注册回调入口
     * 
     * @param array $callBack 回调信息
     *
     * @return vold
     */
    public static function addShutdownCallBack($callBack)
    {	
    	$className = get_class($callBack['0']['0']);
    	if (!empty(self::$cleanup[$className])) {
    		return;
    	}
        self::$cleanup[$className][] = $callBack;
    }
    
	/**
     * 进程结束时回调, (必须设置为public属性)
     *
     * @return void
     */
	public static function shutdown()
    {	
        foreach(self::$cleanup as $callBacks) {
        	foreach($callBacks as $callBack) {
	        	try {        		
	                call_user_func_array('call_user_func', $callBack);
	            } catch (Exception $e) {
	                syslog(LOG_ERR, 'shutdown: ' . $e->getMessage());
	            }
        	}
        }
        return;
    }
	
}