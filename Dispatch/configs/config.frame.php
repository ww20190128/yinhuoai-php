<?php
/**
 * 框架配置
 * 
 * @author wangwei
 */
// 定义路径
define('CONF_PATH', dirname(dirname(__DIR__)) . '/Configs/'); 							// 服务器配置文件路径(需要修改的配置)
define('DS', DIRECTORY_SEPARATOR);  													// 路径分割符
define('CS', '\\');  																	// 类分割符
if (!defined('DISPATCH_PATH')) define('DISPATCH_PATH', ROOT_PATH . 'Dispatch' . DS); 	// 调度路径
define('LIB_PATH', ROOT_PATH . 'Lib' . DS); 											// 第三方库存放路径
define('DISPATCH_CORE_PATH', DISPATCH_PATH . 'core' . DS);								// 调度核心路径
define('DISPATCH_CONFIGS_PATH', DISPATCH_PATH . 'configs' . DS); 						// 调度配置路径
define('DISPATCH_UTILITY_PATH', DISPATCH_PATH . 'utility' . DS); 						// 框架工具包
define('DISPATCH_DIRVE_PATH', DISPATCH_PATH . 'drive' . DS);							// 框架驱动组件存放路径
define('DIRVE_DB_PATH', DISPATCH_DIRVE_PATH .'db' . DS); 								// 框架db包
define('DIRVE_VIEW_PATH', DISPATCH_DIRVE_PATH . 'view' . DS); 							// 框架视图包
define('DIRVE_CACHE_PATH', DISPATCH_DIRVE_PATH . 'cache' . DS); 						// 框架缓存包
define('DIRVE_EXCEPTION_PATH', DISPATCH_DIRVE_PATH . 'exception' . DS); 				// 框架异常处理包
define('DIRVE_DISPATCHER_PATH', DISPATCH_DIRVE_PATH . 'dispatcher' . DS); 				// 框架请求调度包

define('CONFIGS_PATH', ROOT_PATH . 'Configs' . DS); 									// 框架配置
define('ITEM_SET_PATH', ROOT_PATH . 'projects' . DS); 									// 项目集合路径
define('CACHE_PATH', ROOT_PATH . 'cache' . DS);                                         // 缓存层
define('CACHE_TABLES_PATH', CACHE_PATH . 'tables' . DS);                               	// 表结构缓存目录

define('CONSTANT_PATH', ITEM_SET_PATH . 'constant' . DS); 								// 项目集合公用常理包

define('COMMON_PATH', ITEM_SET_PATH . 'common' . DS); 									// 工具包
define('COMMON_FUNCTION_PATH', COMMON_PATH . 'function' . DS);                          // 工具包 - 方法
define('COMMON_CLASS_PATH', COMMON_PATH . 'class' . DS);                                // 工具包 - 类

// 设置编码
header('Content-Type:text/html;charset=utf-8');  										// 设置系统的输出字符为utf-8
return;