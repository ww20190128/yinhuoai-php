<?php
/**
 * 服务器相关的配置
 * 
 * @author wangwei
 */
return array(
    /**
     * 初始服务进程配置
     */
    'queue' => array(
        'master' => array( // 主服务控制进程
            'switch'            => true,    // 控制开关(是否开启)
        ),
        'daemon' => array( // 守护进程
            'switch'            => false,
            'desiredmsgtype'    => 55,
        ),
        'tracer' => array( // 用户跟踪进程
            'switch'            => false,
            'desiredmsgtype'    => 60,
            'timeout'           => 0.500, // 根据写并发,cpu核数及系统设置的ulimit数量调整
        ),
        'logging' => array( // 日志写入进程
            'switch'            => false,
            'timeout'           => 1.920,
        ),
        'messager' => array( // 消息进程, 
            'switch'            => false, // 设置为false 可用redis代替
            'desiredmsgtype'    => 70,
            'timeout'           => 1.000,
        ),
        'thread' => array( // 后台工作进程
            'switch'            => true,
            'queue_num'         => 8,
            'desiredmsgtype'    => 75,
        ),
        'timer' => array( // 计划任务调度服务
            'switch'            => false,
            'desiredmsgtype'    => 80,
        	'timeout'           => 3,
        ),
    	'online' => array( // 用户在线进程
            'switch'            => false,
            'desiredmsgtype'    => 50,
        ),
        'looper' => array( // 每20秒执行一次的循环
            'switch'            => false,
        	'timeout'           => 20.000,
        ),
        'epollMonitor' => array( // 聊天服务器epoll监控进程, 
            'switch'            => false,
            'timeout'           => 1.000,
        ),
    ),

    /**
     * 运行环境配置
     */
    'environ' => array(
        'php_cli'       => '/bin/php',                  	// php cgi目录
        'runtime_dir'   => '/data/weblogs/runtime',    		// 进程运行时候的目录
        'user'          => 'www',                        	// 进程用户
        'group'         => 'www',                        	// 进程用户组
        'msg_queue_key' => ftok(__FILE__, 'a'),             // 消息队列key
        // 日志文件配置信息
        'logfile' => array(
            'header_format' 		=> 'vvVVVx16', 	// 头格式
        	'header_size' 			=> '32', 		// 头大小
	        'header_magic' 			=> 'PW', 		// 头模式
	        'header_tail_offset' 	=> '12', 		// 头信息中起始位置(startPosition)存储的起始位置 8
	        'header_tail_size' 		=> '4', 		// 头信息中位置信息(startPosition or endPosition)存储的所用的大小
	        'header_head_offset' 	=> '8', 		// 缓冲区可储存的最大快数量
	        'header_head_size' 		=> '4', 		// 每个块的大小
	        'buffer_size' 			=> '335544320',	// 原33554432
	        'block_size' 			=> '4000',		// 原40
	        'sync_interval' 		=> '5.000',
	        'alarm_threshold' 		=> '0.500',
	        'max_blocks' 			=> '8192',
        ),
	),
    
	/**
	 *平台充值ip白名单列表
	 */
	'platformIps' => array( 
		'192.168.21.1-255',
	),
		
	/**
	 * 阿里云上传
	 */
	'oss' => array(
		
	)
);