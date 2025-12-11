<?php
$GLOBALS['logStartTime'] = microtime(); // 记录性能分析的开始时间点, 用于_e()函数打印程序执行时间
// header('Access-Control-Allow-Origin:*');

/**
 * web入口
 * 
 * @author wei.wang
 */
// 加载引导文件
require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..')
	. DIRECTORY_SEPARATOR . 'Dispatch' . DIRECTORY_SEPARATOR . 'Bootstrap.php';
// 运行框架
Application::run();