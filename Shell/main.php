#! /usr/bin/env php
<?php
ini_set('default_socket_timeout', -1); // 不超时
/**
 * shell入口
 * 
 * @author wangwei
 */
// 加载引导文件
require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..')
	. DIRECTORY_SEPARATOR . 'Dispatch' . DIRECTORY_SEPARATOR . 'Bootstrap.php';
// 运行框架
Application::run();