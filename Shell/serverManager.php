#! /usr/bin/env php
<?php
/**
 * shell入口
 * 
 * @author wangwei
 */
ini_set('default_socket_timeout', -1);
// 加载引导文件
require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..')
	. DIRECTORY_SEPARATOR . 'Dispatch' . DIRECTORY_SEPARATOR . 'Bootstrap.php';
// 运行框架
Application::run();

switch($argv[1]){
	case 'serverGateway':	//网关服务
		new Gateway();
		break;
	case 'serverChat':		//聊天服务	
		new Chat();
		break;
	case 'serverStage':		//场景服务
		new Stage();
		break;
	case 'serverWedding':	//婚礼
		new Wedding();
		break;
}