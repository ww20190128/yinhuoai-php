<?php
namespace drive\view;
use Application;

/**
 *  Smarty视图
 *  
 *  @author wangwei
 */
class SmartyView extends ViewBase
{
	const TYPENAME = 'smarty';
	
	private static $smarty ; // smarty 对象
	private static $tpl ;    // 模板
	private static $args ;   // 参数

    /**
     * 构造函数
     *
     * @return
     */
	public function __construct()
    {
    	if (!defined('SMARTY_SPL_AUTOLOAD')) define('SMARTY_SPL_AUTOLOAD', 1);
 		if (!defined('LIB_SMARTY_PATH')) define('LIB_SMARTY_PATH', LIB_PATH . 'smarty' . DS); 					// 第三方库 smarty 存放路径
		if (!defined('SMARTY_LEFT_DELIMITER')) define('SMARTY_LEFT_DELIMITER', '[{'); 							// smarty 左分割符
		if (!defined('SMARTY_RIGHT_DELIMITER')) define('SMARTY_RIGHT_DELIMITER', '}]'); 						// smarty 右分割符
		if (!defined('CACHE_SMARTY_PATH')) define('CACHE_SMARTY_PATH', CACHE_PATH . 'smarty' . DS); 			// smarty 缓存路径
		if (!defined('SMARTY_CACHE_DIR')) define('SMARTY_CACHE_DIR', CACHE_SMARTY_PATH . 'cache' . DS); 		// smarty 缓存路径
		if (!defined('SMARTY_TEMPLATE_DIR')) define('SMARTY_TEMPLATE_DIR', TEMPLATE_PATH); 						// smarty 模板路径
		if (!defined('SMARTY_COMPILE_DIR')) define('SMARTY_COMPILE_DIR', CACHE_SMARTY_PATH . 'compile' . DS); 	// smarty 编译路径	
		if (!defined('CSS')) define('CSS', "../Code/template/css"); 
		if (!defined('JS')) define('JS', "../Code/template/js");
		if (!defined('IMAGES')) define('IMAGES', "../Code/template/images");
		if (!self::$smarty && loadFile('libs'. DS . 'Smarty.class', LIB_SMARTY_PATH)) {
			$smarty = new \Smarty();
			$smarty->cache_dir       = SMARTY_CACHE_DIR;
   			$smarty->compile_dir     = SMARTY_COMPILE_DIR;
            $smarty->template_dir    = HTML_PATH;
            $smarty->left_delimiter  = SMARTY_LEFT_DELIMITER;
            $smarty->right_delimiter = SMARTY_RIGHT_DELIMITER;
$smarty->caching = false;
$smarty->cache_lifetime = 0; //单位为秒(如果填写-1为永不过期)
            self::$smarty = $smarty;
	       	self::setAutoloadFunction();
        }
        return ;
	}

	/**
	 * 设置自动加载函数
	 * 
	 * @param  $functionName   string  函数名
	 * 
	 * @return 	void
	 */
	private static function setAutoloadFunction($functionName = '__autoload') 
	{
		$registeredAutoLoadFunctions = spl_autoload_functions();	
		// 注销所有的加载函数 
        if (!isset($registeredAutoLoadFunctions[$functionName])) {	
            foreach ($registeredAutoLoadFunctions as $func) {
				spl_autoload_unregister($func);
			}
			spl_autoload_register($functionName);
      	}
      	return ;
	}

    /**
     * 展示
     *
     * @param   array   $args 参数
     * @param   string  $tpl  模板编号
     *
     * @throws
     * @return void
     */
	public function display($args = array(), $tpl = null)
    {
		$args = !$args ? self::$args : $args;
		$smarty = self::$smarty;
		$args and $smarty->assign($args);      
		$templateName = preg_match('/\.html$/', $tpl) ? $tpl : $tpl . '.html';
        if ($templateName && file_exists(HTML_PATH . $templateName)) {
			self::setAutoloadFunction('smartyAutoload');
			$smarty->display($templateName);
			self::setAutoloadFunction();
			exit;
        } else {
        	// 模板[TPL]没找到
            throw new Application::$Exception("模板 '[TPL]' 没找到", array('TPL' => $tpl));
        }
        exit;
        return;
	}

    /**
     * 设置参数
     *
     * @param   string  $name   名字
     * @param   array   $arg    参数
     *
     * @return void
     */
    public  function __set($name, $arg)
	{
		self::$$name = $arg;	
	}

    /**
     * 获取参数
     *
     * @param   array   $arg    参数
     *
     * @return array
     */
	public function __get($arg) 
	{
        return isset(self::$args) ? self::$args : false;
	}

}