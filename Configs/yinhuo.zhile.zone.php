<?php
/**
 * 因火产品 - 配置文件
 *
 * 说明:
 * 注释带*号的为必选项
 * 服务器id段:测试{1, 10}, 正式  {100, 999}
 */
$conf = array(
    'id' 					=> 100, 							// *服务器id
    'type' 					=> 1, 								// *服务器类型, 必须与[project_code]一一对应
    'mark' 					=> 'yinhuo-2', 				        // *服务器标识
    'project_code' 			=> 'yinhuo', 					        // *项目代号, 必须与projects对应的项目名称一致
    'debug' 				=> true, 							// *调试模式
    'session_switch' 		=> true, 							// *是否启用session
    'language' 				=> 'zh_CN', 						// 语言
    'server_start_time' 	=> '2016-03-11 12:00:00', 			// 开服时间
    'maintain_start_time' 	=> '2016-03-11 12:00:00', 			// 维护开始时间
    'maintain_end_time' 	=> '2016-03-11 12:00:00', 			// 维护结束时间
    'center' 				=> '123.59.131.101:8080', 			// 中心服务器域名
    'private_key' 			=> 'thsiadfasdf', 					// 服务器私钥
    'time_zone' 			=> 'Asia/Shanghai', 				// 时区
    'inner_ip' 				=> '192.168.0.107:8080', 			// 内网ip(php服务器的内网ip)
	'web_url'				=> 'https://xinlice.top',			// 前端地址
	'serve_url'				=> 'https://yinhuo.zhile.zone', 	// 服务器地址
	'database' => array ( // *数据库
		'yinhuo' 	=>	array ( // 服务器数据库
		    'db_host' 		=> '127.0.0.1',
		    'db_port' 		=> '3306',
		    'db_user' 		=> 'root',
		    'db_pass' 		=> '295012469',
		    'db_name' 		=> 'yinhuo',
		    'persistence' 	=> false,
		    'log_query' 	=> false,
		    'is_main' 		=> true,			// *是否为主数据库
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
    	'images' => 'https://zhile-static.oss-cn-beijing.aliyuncs.com/resources/',
//     	'images' => 'http://static.zhile.ink/resources/',
	),
	// 火山引擎
	'volcengine' => array(
		'appId' => 'd294de9a-a197-42e4-8a00-e29eaa05a0df', // 控制台获取的APP ID，
		'arkApiKey' => '38078d13-166f-4194-8fa1-1c0bd4ba2084', // AI请求的 ARK_API_KEY
	),
    'fileDir' => '/data/www/static-kjs/file/',
	'weChat' => array(
		'appId' => 'wxde609c2255df3268',
		'appSecret' => '341a3503556f33ccea14c02e442c3182',
			
		//=========================================
		'merchantId' => '1105591112', // yinhuo商户号
		'APICertificateKey' => '77E994FDB61CDA61B3C64F7AF06936DB73770E07', // 商户API证书
// 		'APIv2Key' => 'J3XbRCLj6hKh3c3ieZiB92QtLeZwgZYE', // 商户APIv2密钥
		'RSA' => '1B84A18252BFC94060DF9B551587288FC432AC84', // 平台证书
		'APIv3Key' => '3UCb52tx4RMNhUQfAFXAqgHNNUV7ViV3', // APIv3密钥
	),
	// php CertificateDownloader.php -k APIv3Key -m merchantId -f /data/www/mood-php/Configs/xince/apiclient_key.pem -s APICertificateKey -o /data/www/mood-php/Configs/xince/
	
	'aliEditing1' => array( // 阿里云剪辑(ww)
		'accessKeyId' => 'LTAI5tLTC2fRsgwKXZP75Wow',
		'accessKeySecret' => 'p52taYKYaKAJpkaWqvbH2gWuZJDZcs',
		'StorageType' => 'oss', // 仅支持 oss
		//oss-cn-beijing-internal.aliyuncs.com
		'StorageLocation' => 'cn-beijing.oss.aliyuncs.com', // 仅支持 VOD 点播存储，不支持用户自有 OSS 存储。
		'chipUrlBase' => 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/project/',
	),
	'aliEditing' => array( // 阿里云剪辑(yinhuo)
		'accessKeyId' => 'LTAI5tGXcM3Lvis6R3pZkzUH',
		'accessKeySecret' => 'rWTzE3XhS1gvjJbFeI0VmXqeUWcI4B',
		'StorageType' => 'oss', // 仅支持 oss
		//oss-cn-beijing-internal.aliyuncs.com
		'StorageLocation' => 'cn-beijing.oss.aliyuncs.com', // 仅支持 VOD 点播存储，不支持用户自有 OSS 存储。
		'chipUrlBase' => 'https://yinhuo-ai.oss-cn-beijing.aliyuncs.com/project/',		
	),
	'appConfig' => array(
		'name' => '心测MBTI',
		'logo' => 'https://zhile-static.oss-cn-beijing.aliyuncs.com/resources/xince-logo.png',
		'customerServiceLink' => 'https://work.weixin.qq.com/kfid/kfc13fae6947b453b23', // 客服
		'h5Pay' => array('wx', 'zfb'), // 支持的h5支付方式
		'customerServiceQR' => 'https://zhile-static.oss-cn-beijing.aliyuncs.com/resources/xinlice_qrcode.jpg',
		'customerServiceWechat' => 'zhilei', // 客服微信
	),
);

$conf['dao'] = array( // dao数据库操作组件配置
    'mysql' => $conf['database']['yinhuo'],
    'redis' => $conf['cache']['Redis']['dynamic'],
);
return $conf;