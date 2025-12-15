<?php
/**
 * 公共管理后台 - 配置文件
 * 
 * 说明:
 * 注释带*号的为必选项
 * 服务器id段:测试{1, 10}, 正式  {100, 999}
 * 服务器类型: 1 考教师  2 公考
 */
$conf = array(
    'id' 					=> 1, 							// *服务器id
	'type' 					=> 2, 								// *服务器类型, 必须与[project_code]一一对应
    'mark' 					=> 'publicAdmin-1', 				// *服务器标识
	'project_code' 			=> 'publicAdmin', 					// *项目代号, 必须与projects对应的项目名称一致
    'debug' 				=> false, 							// *调试模式
	'session_switch' 		=> true, 							// *是否启用session
    'language' 				=> 'zh_CN', 						// 语言
    'server_start_time' 	=> '2016-03-11 12:00:00', 			// 开服时间
    'maintain_start_time' 	=> '2016-03-11 12:00:00', 			// 维护开始时间
    'maintain_end_time' 	=> '2016-03-11 12:00:00', 			// 维护结束时间
   	'center' 				=> '123.59.131.101:8080', 			// 中心服务器域名
   	'private_key' 			=> 'thsiadfasdf', 					// 服务器私钥 
   	'time_zone' 			=> 'Asia/Shanghai', 				// 时区 
	'inner_ip' 				=> '192.168.0.108:8017', 			// 内网ip(php服务器的内网ip)
	'database' => array ( // *数据库
		'public_server' 	=>	array ( // publicServer 服务器数据库
			'db_host' 		=> '39.106.73.19',
			'db_port' 		=> '3306',
			'db_user' 		=> 'root',
			'db_pass' 		=> 'root',
			'db_name' 		=> 'question',
			'persistence' 	=> false,
			'log_query' 	=> false
		),
  	),
    'cache' => array ( // *缓存
        'Memcached' => array (
        	array(
            	'cache_host'    => '192.168.0.170',
               	'cache_port'    => '11211',
          	),
    	),
	 	'Memcache' => array (
    		array(
            	'cache_host'    => '192.168.0.170',
              	'cache_port'    => '11211',
      		),
  		),
    	'Redis' => array(
	    	'dynamic' => array (
		        'cache_host'    => '127.0.0.1',
		        'cache_port'    => '6379',
		        'out_time'      => '0',
		        'serialize'     => 'true',
				'database'     	=> '0',
		        'auth'     		=> 'redis#$@JustDoIt2021',
     		),
		     'static' => array (
		        'cache_host'    => '192.168.0.170',
		        'cache_port'    => '6379',
		        'out_time'      => '0',
		       	'serialize'     => 'true',
				'database'     	=> '0',
		      	'auth'     		=> 'redis#$@JustDoIt2021',
		   	),
		)    
	),
	'communicate' => array ( // 通讯配置
	   	'socket' => array (
	   		'clientLinkIp' 		=> '123.59.147.214',	// 客户端连接ip(php服务器的外网ip)
	       	'host' 				=> '127.0.0.1',			// 如果php服务器跟聊天服务器在同一机器上, 不要修改此项	
	 		'app_port'  		=> '8851', 				// 应用服务端口
	   		'user_port' 		=> '8962', 				// 玩家端口
	  	),
 	),
	'white_list' => array( // ip白名单
		'172.16.13.97',
		'172.16.13.96',
	),
	'urls' => array(
		'epub' => 'http://47.104.195.241:81/epub/',
	),
	'epubDir' => '/data/www/static/epub/',
	'epubTmpDir' => '/data/www/static/tmp/',
);

if ($conf['type'] == 1) { // 考教师
	$conf['dao'] = array( // dao数据库操作组件配置
		'mysql' => $conf['database']['public_server'],
		'redis' => $conf['cache']['Redis']['dynamic'],
	);
} else {
	$conf['dao'] = array( // dao数据库操作组件配置
		'mysql' => $conf['database']['public_server'],
		'redis' => $conf['cache']['Redis']['dynamic'],
	);
}
if ($conf['id'] >= 200) { // 考教师
	$conf['database']['public_server'] = array ( // publicAdmin 服务器数据库
		'is_main' 		=> true, // *是否为主数据库
		'db_host' 		=> 'pc-2ze702qx860944pjx.rwlb.rds.aliyuncs.com',
		'db_port' 		=> '3306',
		'db_user' 		=> 'public_admin',
		'db_pass' 		=> 'JustDoIt2019',
		'db_name' 		=> 'public_admin',
		'persistence' 	=> false,
		'log_query' 	=> false
	);
	$conf['dao'] = array( // dao数据库操作组件配置
		'mysql' => $conf['database']['public_server'],
		'redis' => $conf['cache']['Redis']['dynamic'],
	);
} else {
	
}
return $conf;