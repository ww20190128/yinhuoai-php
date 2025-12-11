<?php
/**
 * 引导程序
 * 
 * @author wangwei
 */
final class Bootstrap 
{
	/**
	 * 框架运行模式: 只加载不运行
	 * 
	 * @var int 
	 */
	const RUN_MODE_NO = 0;

	/**
	 * 框架运行模式: web
	 * 
	 * @var int 
	 */
	const RUN_MODE_WEB = 1;

	/**
	 * 框架运行模式: shell
	 * 
	 * @var int 
	 */
	const RUN_MODE_SHELL = 2;

	/**
	 * 运行模式
	 * 
	 * @var int 
	 */
	public static $runType;

	/**
	 * 请求的host
	 * 
	 * @var string 
	 */
	public static $hostName;

	/**
	 * 构造函数设为私有属性,禁止实例化
	 */
	final private function __construct() {}

	public static $configuration = array(); // 框架配置参数

	/**
	 * 读取所有配置
	 * 
	 * @param string|int	$hostName	域名 |或者服务器id
	 * 
	 * @return array 
	 */
	public static function getConfigures($hostName = null) 
	{
		$hostName = str_replace(':', '-', $hostName); // 如果配置文件中存在':', 使用'-'替换, 例如192.168.0.172:5555
		$files = glob(CONF_PATH . (empty($hostName) || is_numeric($hostName) ? '*' : $hostName) . '.php');	
		$confs = array();
		foreach ($files as $file) {
			$conf = require ($file);
			if (empty($conf)) {
				continue;
			}
			if (!isset($conf['id'])) {
				$conf['id'] = 0;
			}
			$conf['host'] = rtrim(basename($file), '.php');
			$confs[intval($conf['id'])] = $conf;
			if (!is_null($hostName) && ($hostName == $conf['host'] || $hostName == $conf['id'])) {
				return $conf;
			}
		}
		return $confs;
	}

	/**
	 * 获取运行的host
	 * 
	 * @param int $runType 运行模式
	 * @return string 
	 */
	public static function getHost($runType = null) {
		// 运行模式
		self::$runType = $runType ? $runType : (in_array(substr(PHP_SAPI, 0, 3), array('cgi',
			'cli')) ? self::RUN_MODE_SHELL : self::RUN_MODE_WEB);
		if (self::$runType == self::RUN_MODE_WEB) { // http 请求
			if (isset($_SERVER['HTTP_HOST'])) {
				$hostName = $_SERVER['HTTP_HOST']; // 根据域名 请求
			} else {
				$hostName = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']; // 根据ip跟端口访问
			}
		} else { // CGI 模式
			$opts = getopt('h:', array('help'));
			$hostName = empty($opts['h']) ? null : $opts['h'];
			if (isset($opts['help'])) {
				self::usage();
			}
			// 处理参数
			$argv = $_SERVER['argv'];
			unset($argv[0]);
			if ($key = array_search('-h', $argv)) {
				unset($argv[$key]);
				unset($argv[$key + 1]);
			}
			$_SERVER['argv'] = array_values($argv);
		}
		self::$hostName = $hostName;
		return $hostName;
	}

	/**
	 * shell 模式使用说明
	 * 
	 * @return void 
	 */
	private static function usage() {
		$info = "shell模式下使用说明:\n";
		$info .= "模式1：php main --help  查看使用说明\n";
		$info .= "模式2：php main -h 域名 请求 参数 例如：php main -h t0.hero.com shell a=1 b=2\n";
		$info .= "模式3：php main 请求 参数 例如：php main shell a=1 b=2\n";
		die($info);
	}

	/**
	 * 初始化
	 * 
	 * @param 	int 	$runType 	运行模式
	 * 
	 * @return bool 
	 */
	public static function initialization($runType = null)
 	{
		if (self::$configuration) {
			return true;
		}
		// 定义根目录
		define('ROOT_PATH', realpath(dirname(__file__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);
		// 定义框架调度目录
		define('DISPATCH_PATH', ROOT_PATH . 'Dispatch' . DIRECTORY_SEPARATOR);
		// 动态地对PHP.ini中include_path进行修改, 针对include和require的路径范围进行限定, 提高文件引入的效率
		set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH);		
		// 加载框架配置
		$configs = glob(DISPATCH_PATH . 'configs' . DIRECTORY_SEPARATOR . 'config.*.php');	
		array_walk($configs, function ($file) {require $file;});
		// 获取项目配置文件
		$conf = self::getConfigures(self::getHost($runType));
	
		if (empty($conf['type'])) { // 没有定义服务器类型(中心服务器)使用127.0.0.1本地服务器的配置
			self::$hostName = '127.0.0.1'; // 默认域名
			$conf = self::getConfigures(self::$hostName);
			if (empty($conf)) {
				die('127.0.0.1.php 未配置');
			}
		}
		if (empty($conf['project_code']) || !is_dir(ROOT_PATH . 'projects' . DS . $conf['project_code'] . DS)) {
			die('未定义项目代号, 请在Configs目录下的配置文件[project_code]字段中定义');
		}
		// 定义项目代码区
		define('CODE_PATH', ROOT_PATH . 'projects' . DS . $conf['project_code'] . DS);
		// 加载框架core文件夹下的基类 , Configs文件夹下的项目配置
		$configs = array_merge(glob(DISPATCH_CORE_PATH . '*.php'), 	// 框架核心基类
			glob(CONFIGS_PATH . 'config.*.php'), 				// 项目配置(op操作列表等)
			glob(CONFIGS_PATH . 'static.*.php'), 				// 静态配置(公式等)
			glob(COMMON_FUNCTION_PATH . '*.php'), 				// 通用方法
			glob(DISPATCH_UTILITY_PATH . '*.php'), 				// 框架工具方法包
			glob(CACHE_TABLES_PATH . '*.php') 					// 表结构文件缓存
		);
		array_walk($configs, function ($file) {require $file; });
		// 加载项目配置
		$configs = glob(CODE_PATH . 'configs' . DIRECTORY_SEPARATOR . 'config.*.php');
		array_walk($configs, function ($file) {
			require $file; }
		);
		set_exception_handler('exceptionHandler'); // 自定义异常处理函数
		set_error_handler('errorHandler', E_ALL & ~ (E_NOTICE)); // 自定义错误处理函数
		if (openlog('pw', LOG_ODELAY, LOG_LOCAL5) === false) { // 开启syslog日志记录
			die('无法开启日志记录通道');
		}
		// 报错模式
		ini_set('display_errors', empty($conf['debug']) ? 'Off' : 'On');
		// 时区设置
		ini_set('date.timezone', empty($conf['time_zone']) ? 'Asia/Shanghai' : $conf['time_zone']);
		// 加载中心调度文件
		require DISPATCH_PATH . 'Application.php';
		self::$configuration = self::initConfiguration($conf);
		return true;
	}

	/**
	 * 初始化配置参数
	 * 
	 * @param 	array 	$conf 	服务器配置
	 * 
	 * @return array 
	 */
	public static function initConfiguration($conf) {
		// 框架参数
		$configuration['server'] = array(
			'runType' 	=> self::$runType, 									// 运行模式
			'id' 		=> empty($conf['id']) ? 0 : $conf['id'], 			// 服务器id
			'mark' 		=> empty($conf['mark']) ? null : $conf['mark'], 	// 服务器标识
			'conf' 		=> $conf, 											// 服务器配置
		);		
		// 防止数据库, 缓存账号信息暴露
		unset($configuration['server']['conf']['database'], $configuration['server']['conf']['cache']);
		// 数据库
		if (defined('TYPE_DAOHELPER') && TYPE_DAOHELPER) {
			$configuration['drive']['db'][TYPE_DAOHELPER] = empty($conf['database']) ? array('args' =>
				array(), 'createObj' => true) : array('args' => $conf['database'], 'createObj' => true);
		} else {
			$configuration['drive']['db']['PDO'] = empty($conf['database']) ? array('args' =>
				array(), 'createObj' => true) : array('args' => $conf['database'], 'createObj' => true);
		}
		$cacheType = defined('TYPE_CACHE') && TYPE_CACHE ? TYPE_CACHE : 'Redis';
		// 缓存
		if (empty($conf['cache'])) {
			$configuration['drive']['cache'][$cacheType] = array('args' => array());
		} else {
			$foo = ucfirst($cacheType);
			if (isset($conf['cache'][$foo])) {
				$configuration['drive']['cache'][$cacheType] = array(
					'args' => array_merge($conf['cache'][$foo], array('serverId' => $configuration['server']['id']))
				);
			} else {
				$configuration['drive']['cache'][$cacheType] = array(
					'args' => array('serverId' => $configuration['server']['id'])
				);
			}
		}
		// 视图
		if(defined('TYPE_VIEW') && TYPE_VIEW) {
			$configuration['drive']['view'][TYPE_VIEW] = array('createObj' => false);
		} else{
			$configuration['drive']['view']['Json'] = array('createObj' => false);
		}
		// 异常
		if(defined('TYPE_EXCEPTION') && TYPE_EXCEPTION) {
			$configuration['drive']['exception'][TYPE_EXCEPTION] = array('createObj' => false);
		} else{
			$configuration['drive']['exception']['AppException'] = array('createObj' => false);
		}
		return $configuration;
	}

}
// 引导初始化
Bootstrap::initialization();
return;