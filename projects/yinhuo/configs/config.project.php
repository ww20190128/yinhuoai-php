<?php
/**
 * 项目配置
 *
 * @author wangwei
 */
define('CTRL_PATH', CODE_PATH . 'ctrl' . DS); 											// 控制层
define('SERVICE_PATH', CODE_PATH . 'service' . DS); 									// 逻辑层
define('SERVICE_REUSE_PATH', SERVICE_PATH . 'reuse' . DS); 						        // 可复用逻辑层
define('SERVICE_REPORT_PATH', SERVICE_PATH . 'report' . DS); 						    // 报告逻辑层
define('SERVICE_BACKSTAGE_PATH', SERVICE_REUSE_PATH . 'backstage' . DS); 			    // 后台程序逻辑层
define('SERVICE_EPOLL_PATH', SERVICE_PATH . 'epoll' . DS); 						        // epllserver相关的逻辑层

define('DAO_PATH', CODE_PATH . 'dao' . DS); 											// 数据库层
define('ENTITY_PATH', CODE_PATH . 'entity' . DS); 										// 实体层

define('TEMPLATE_PATH', CODE_PATH . 'template' . DS); 									// 模板层
define('HTML_PATH', TEMPLATE_PATH . 'html' . DS); 										// html 层
define('CSS_PATH', TEMPLATE_PATH . 'css' . DS); 										// css 层
define('RESOURCE_PATH', TEMPLATE_PATH . 'resource' . DS); 								// resource 层

// 功能配置
define('TYPE_CACHE', 'Redis');  			// 缓存
define('TYPE_VIEW', 'Json');				// 试图输出类型
define('TYPE_DAOHELPER', 'PDO');			// 数据库封装
define('TYPE_EXCEPTION', 'App'); 			// 异常类型

if (in_array(TYPE_VIEW, array('Template', 'Smarty'))) {
	if (!defined('SMARTY_SPL_AUTOLOAD')) define('SMARTY_SPL_AUTOLOAD', 1);
	define('LIB_SMARTY_PATH', LIB_PATH . 'smarty' . DS); 								// 第三方库 smarty 存放路径
	define('SMARTY_LEFT_DELIMITER', '[{'); 												// smarty 左分割符
	define('SMARTY_RIGHT_DELIMITER', '}]'); 											// smarty 右分割符
	define('CACHE_SMARTY_PATH', CACHE_PATH . 'smarty' . DS); 							// smarty 缓存路径
	define('SMARTY_CACHE_DIR', CACHE_SMARTY_PATH . 'cache' . DS); 						// smarty 缓存路径
	define('SMARTY_TEMPLATE_DIR', TEMPLATE_PATH); 					  					// smarty 模板路径
	define('SMARTY_COMPILE_DIR', CACHE_SMARTY_PATH . 'compile' . DS); 					// smarty 编译路径
	
	define('CSS', "../Code/template/css");
	define('JS', "../Code/template/js");
	define('IMAGES', "../Code/template/images");
}

// 是否mysql开启事务
define('SWITCH_MYSQL_TRANSACTION', false); 