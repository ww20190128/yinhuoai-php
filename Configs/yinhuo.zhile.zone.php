<?php
/**
 * 因火产品 - 配置文件
 *
 * 说明:
 * 注释带*号的为必选项
 * 服务器id段:测试{1, 10}, 正式  {100, 999}
 */
$conf = array(
    'id' 					=> 110, 							// *服务器id
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
	'serve_url'				=> 'https://xince.zhile.zone', 		// 服务器地址
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

    'fileDir' => '/data/www/static-kjs/file/',
	'weChat' => array(
		'appId' => 'wxde609c2255df3268',
		'appSecret' => '341a3503556f33ccea14c02e442c3182',
			
			//=========================================
		'merchantId' => '1708405300', // 微信支付分配的商户号
		'APICertificateKey' => '407D6C1B24DBF31F53D46E20B4C76C38117C196D', // 商户API证书
// 		'APIv2Key' => '1C4E4D3E89FFC9EAD0583AD6993A8ECBC6BFDC77', // 商户APIv2密钥
		'RSA' => '1A96C3333AA1C9FEB368827F70A9A2E3C26BA9C3', // 平台证书
		'APIv3Key' => 'F8F194CD3157F2330AE2D48DA6262514', // APIv3密钥
	),
	// php CertificateDownloader.php -k F8F194CD3157F2330AE2D48DA6262514 -m 1708405300 -f /data/www/mood-php/Configs/xince/apiclient_key.pem -s 407D6C1B24DBF31F53D46E20B4C76C38117C196D -o /data/www/mood-php/Configs/xince/
	
	'alipay' => array(
		'appId' => '2021005132647113', // 应用Id   AppID:2021005132647113
		// 支付宝公钥
		'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiT+p9I6i9WXQ6QZKwxgKG28xh410OJIcoY40Am9B59ky+KQ7UHsPCTigCo5j5G0w5LteC65bLHFVa5ttsklPdC6jTu/Jfaxuorn4NbfXUc+gkGKZUZIH4lXEkqBsCuaKqq7Jv0YpvYOR1Ys9etHjx6CSpwe1X859XsiIS0FD6kks9dYPD2YOI16hdbCAMLphbWXOyn4ikVJQ3B+xBen3iHsuZIxEyDqMAfi3J8Nd31MMiu/ajVAI0glmrcojeIV0d2JNHlTwB5udvEkMBXIjafS4rFSopyCm8heHURVZVUnf017OzRffCGGch4Wtx3swsZxAyqLshLwT5jeDzJd7kQIDAQAB',
		// 应用私钥
		'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCW4xkkN3ZJHalZt3Q39E4Jm5E+B4x5md5YQTEsLNwv00EM9b22BhgNUJLRznta4lQ3ajJtrH847wtro3mMBN67xXOm37rz31eCvbmda/IV4iIw0sMv+eBqwcq0VgU8LP2sFM67npOY9OYE8HcIhGqZlN33qzORrRvygLmiCp/JFSmuZYESnRPKQDLPeuIxrxJGGje0qhpMGjyU9C6h6GpmHS9cR11mQMQbAmDSkLZCqV+azpRHMwsiY7nMvpoAsUlL7D8mHlPZoSsq5/fouYK6aVMq/Uj0bzDZjXk3mGqSDlOxqTQtC4u9gewxgnRFBfriDQEQYdIFmuQULaFtXB1hAgMBAAECggEAEPO0yGfexzoo46aDzSGKfvPWbpSkiKjr3Rh98MuddVYTseQOC1xF6YEK7b14CG7zLUKmJcJCjN/2dYJpTnzhlVEKvE3YkDugdlTgfLo6+ZBtbPTQ7xvwxa2+G2KqtecMHQA9dcDMLlwdV6K4jOFrpJOgGIpIge5j/GvP70+oQgwNEieDUI2HuNEBBmvV5NhQd8fd/Bjihdj587i5v8I2H8KHu/z+ZBpTl1dIDoQ7ytXVwZWpcfjVVB2Aue9/nhqdB/yyLN0FFEVsrUa3eL8KtXJN9Q7I4ULl4NwX/w/yIJjkXP7n9+fVZKhn8boC1ANwEi9I8gyOXcy8tc1FicPnkQKBgQDgyNHU3enmG35JP6Mh42DDUGUH4ubzr5l8jQ8hkgAl30mBd0slND2a9UHstX5TNQdjPBWGLPahgyZI97ZFFTAWyXgBHWKKa+hK97kLuoxw3JqbHmoPkiFV8HZ8tCirp9qq6ggfVeyqoLYit5fl9Sp/J+cFrX8Lzugo52tLAArmdQKBgQCr1zJ6hPxSWe6YZcce9Oi0RY8ohO24eWd6/cDpBnOfp95wuAEb0ipj4gb8InxQzCRYzgCJt42w1F89vfknSGXdVz8TY3jt2xYOl2gx6OZD82XylimrWnRE9nEcdc8ts/ZdKZAC6AZkWKO2fPFRA6ujlbvChzx2X3pakcMXN3r1vQKBgCU7Pf4bD43MiftJ7hRD3Bgdrc5Dl+tO74ZAuvvdeebL+BnYj3rHD1kmPFgfq5/Ojb2zCwGhWuxfk6zMUsVYgBGWJylQG60/uEcKhvzZVj+vWnBM9lZD5v+cB5QaJw5fjAl5IAVIrx2H5wMTE7bEB9jt3AcFuKBVEgEMa6oNhMCtAoGAXgQPChlYM3YgpCCLINS9vGOSP4j6xsMlapUKxnNRLziY6vLBKIeDycIQMEJt4YbPHAcZJD/YtbZ7pTwa5PMnSEJDsEfsEbacCr+rsiLKWMMCNAcUJTwIAPMUT43lHAwp7i6fK/fmB2C3sVAKd1iav5VcdMGowtraBlNZeYpRK8ECgYEAr3Pag4gnHQ0lT6sw/Zl1Irm4fDNozPER/Uhxl12QwBA4B7GUROG4DeA/WL2uxPhYXILDCHha/3DMKhDceUAlvk9vVDEZFqOJpWWeDhJ+JRNTKIKSzmdP6YZClDihU6m5tqBAMN35x+kGhA5ZCF1kfib3Kbv+k+coyz2d9BJm7aE=',
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