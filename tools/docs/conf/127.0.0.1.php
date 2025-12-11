<?php
/**
 * 外网测试服-127.0.0.1-配置文件
 * 
 * 本地回合服务器
 */
return array(
    'id' => 127, 										// 服务器id
	'type' => 3, 										// 服务器类型 (1 游戏服务器 ,2 中心服务器 ,3 默认域名 127.0.0.1服务器)
    'mark' => 'rxsg_t_local', 							// 服务器标识
    'debug' => false, 									// 调试模式
    'language' => 'zh_CN', 								// 语言
    'server_start_time' => '2016-03-11 12:00:00', 		// 开服时间
    'maintain_start_time' => '2016-03-11 12:00:00', 	// 维护开始时间
    'maintain_end_time' => '2016-03-11 12:00:00', 		// 维护结束时间
   	'center' => '123.59.131.101:8080', 					// 中心服务器域名
   	'private_key' => 'thsiadfasdf', 					// 服务器私钥 
   	'time_zone' => 'Asia/Shanghai', 					// 时区 
	'innerIp' => '10.19.55.181', 						// 内网ip(php服务器的内网ip)
	'database' => // 数据库
        array (
            'dynamic' =>
                array (
                    'db_host'       => '10.19.192.79',
                    'db_port'       => '3306',
                    'db_user'       => 'admin',
                    'db_pass'       => 'FJRUDKEISLWO',
                    'db_name'       => 'rxsg_t1',
                    'persistence'   => false,
                    'log_query'     => false,
                ),
            'static' =>
                array (
                    'db_host'       => '10.19.192.79',
                    'db_port'       => '3306',
                    'db_user'       => 'admin',
                    'db_pass'       => 'FJRUDKEISLWO',
                    'db_name'       => 'rxsg_t1_data',
                    'persistence'   => false,
                    'log_query'     => false,
                ),
		 	'log' =>
                array (
                    'db_host'       => '10.19.192.79',
                    'db_port'       => '3306',
                    'db_user'       => 'admin',
                    'db_pass'       => 'FJRUDKEISLWO',
                    'db_name'       => 'rxsg_t_log',
                    'persistence'   => false,
                    'log_query'     => false,
                ),
        	'center' =>
                array (
                    'db_host'       => '10.19.192.79',
                    'db_port'       => '3306',
                    'db_user'       => 'admin',
                    'db_pass'       => 'FJRUDKEISLWO',
                    'db_name'       => 'rxsg_t_center',
                    'persistence'   => false,
                    'log_query'     => false,
                ),
        ),
    'cache' => // 缓存
        array (
            'Memcached' =>
                array (
                    array(
                        'cache_host'    => '10.19.142.113',
                        'cache_port'    => '11211',
                    ),
                ),
	    	'Memcache' =>
                array (
                    array(
                        'cache_host'    => '10.19.142.113',
                        'cache_port'    => '11211',
                    ),
                ),
            'Redis' => array(
                'dynamic' => array (
                    'cache_host'    => '10.19.142.113',
                    'cache_port'    => '6379',
                    'out_time'      => '0',
                    'serialize'     => 'true',
					'database'     	=> '0',
                	//'auth'     		=> 'test',
                ),
                'static' => array (
                    'cache_host'    => '10.19.142.113',
                    'cache_port'    => '6379',
                    'out_time'      => '0',
                    'serialize'     => 'true',
					'database'     	=> '0',
                	//'auth'     		=> 'test',
                ),
           	)
            
        ),
	'communicate' => // 通讯配置
        	array (
	            'socket' =>
	                array (
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
	
);