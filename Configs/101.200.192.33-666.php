<?php
/**
 * 心理产品 - 配置文件
 *
 * 说明:
 * 注释带*号的为必选项
 * 服务器id段:测试{1, 10}, 正式  {100, 999}
 */
$conf = array(
    'id' 					=> 100, 							// *服务器id
    'type' 					=> 1, 								// *服务器类型, 必须与[project_code]一一对应
    'mark' 					=> 'mood-1', 				        // *服务器标识
    'project_code' 			=> 'mood', 					        // *项目代号, 必须与projects对应的项目名称一致
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
	'web_url'				=> 'http://101.200.192.33',			// 前端地址
	'database' => array ( // *数据库
		'mood' 	=>	array ( // 服务器数据库
		    'db_host' 		=> '127.0.0.1',
		    'db_port' 		=> '3306',
		    'db_user' 		=> 'root',
		    'db_pass' 		=> '295012469',
		    'db_name' 		=> 'mood',
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
    	'images' => 'http://101.200.192.33:88/',
	),
	'tmpDir'  => '/data/www/static-kjs/tmp/',
    'epubDir' => '/data/www/static-kjs/epub/',
    'fileDir' => '/data/www/static-kjs/file/',
    'epubTmpDir' => '/data/www/static-kjs/tmp/',
	'imageDir' => '/data/www/static-kjs/images/',
	'dingdingParams' => array(
		'login' => array(
			'appid'     => 'dingoanbz65dopa8ta2ql4',
			'appsecret' => 'hT04gE2gXM0T_LIexmm1Z0mdO-OnU7Sv28_W6VRlPaxviRfnD5mSwMaKC5YTN5jH',
		),
			'user'  => array(
			'appkey'    => 'dinglywycyyrwf8ircbp',
			'appsecret' => '1KSuHJATfjo1PcrxvKKUcm84OnhQiuafCZgEkmn7DEQgyNrcVqPzJzzQpY7Zod48',
		)
	),
	'weChat' => array(
		'appId' => 'wx6dddec6a996d6094',
		'appSecret' => 'b68967c1cba3901e66c31b15fee9665e',
	),
	'alipay' => array(
		'appId' => '2021005113605464', // 应用Id
		// 支付宝公钥
		'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnqdFuxXYEeIDf3nusuAKNBtGKa/oteohmUtpBsdS0ocFGlTL9nc5KbWi4eA8BdVhDto9zHnUq5mKZ5xqnWwNcDHROc4rpPZ5ZwaoXGc0hk0BgEwIXlHNsL8/OniZpyK9dOMg3qLoYVsICgmTcO+Y1lFLjhDG4VGz9GjdI2hZWIQhj+jVTUOoveo8kSOA05qx8/hsWcaT3y63mFeg9SY/iJdkDcZxzMqk1RHttOJbHeOYMO+ht1kXWO9fOwRsSXkrMacBKzl0CvFJaUJSdkQcimiGGfnTnh7BaDUZEXeV+qCh+QTgLdSUobnPWRDV7VH6U1R+tgdAdPqjT8WuxnN+cwIDAQAB',
		// 应用私钥
		'private_key' => 'MIIEwAIBADANBgkqhkiG9w0BAQEFAASCBKowggSmAgEAAoIBAQCaD47Ik0n0dsZSIArbmFEosXpN4e8XrPzhbdxOcPCs8A2EwR0ZzrXbB8IULxqBFOevhPlZKhjtvjhlblRyAonyguUgRHbvtq7gR0he/f+uwWl9qwzeQsribfFy1oW2tDMnaXiVfb3q/oVDwEMxuhwKqbVQ8o4SOAX/S5FOJEWp/L69vhSN96zb6cQUZ3IPJmzXRDeBBWmOqifctXIqhU6lLsEd5LHt+5GKTX6vBVkC6rgoOlL7LXuM0hgzCJpasW+Av2oswlrrXt3cpmsa+oqVdvVKCo0mRwkRNScd59L3zLQysS4TScYKat2oLTaP5GzlNfndRzPOl/JX2k695B2BAgMBAAECggEBAJVhZHdDqb/qsx3Kh/ypUnh8rjR6UFTKnWEQHz/H7vYgxVrlzQvLDTZV7W+YxMBIvuXHr+cvFIDhotlnA2aQ46VMGlMRT6nbnvFE7P1+l76hN2JsystwRD0NkcJiE8E2NSuP7yY0iEQlip5I/F5pMOt95puYtP0haV9DrJ7yOMI7GJAIzpClQgoip8v+zaxDS4BjlsRCT87iAZtXye7wClbr3DFHq24xe7pJYqdZHYX7iqrJ2aEBEGwazjxhOqlLNP8847mq52vtXXqixvAdavUPzIPwvySvLsDAkLDQxOLMFX6PlX+ejYuwBY16rKhLzNq+X5oPsfYmfNVGzn4lBgkCgYEAy6mOaaxqL/Wr7+gsV0/fXTe+m5IMOgZmiW51qUjAeEzJpp9bjLi5zD99FIuX2bt9MAVwQZE5uoEjg00YYwZqQ0IgLvwZjQgkRqKdEoeydSGrsoPUszJAPOnLIrJwyd5EAJJQvRYejW8yc90b99bjdqw8SWNvB+ne8V1cp/85gPMCgYEAwabWR2z9k4a8vMGNPY7PVShcORCTT4vuTse+DzGbe5oLOLAamSrLiECNiVMec/ZcS38U+1V1xEOHephJH2W46A4cPf3hcyDjGbhrhi7rk0VbiLH/eCIzZ3bFlL324S+0EGj6vFOZBPNwnW79QTE7FNyaaX+sLeZ6Mb/YIEZsZLsCgYEAnK9R9VLETyl+og/JLVMx0RG1/xIZffq2oDzvINHr4aGR8jLfyB/GMbEWldvfc4+e/Hbyqj8EXsxDehyyCl1BG4WgJQWm0q3U2tL4bO0hCqRg5/IsjcC3UyW7NnJ5+5SYVKg8nXyK/Nzzv9f4UHBuhzpzFzL8lOYEs0TUqBrfLqkCgYEAmZ6U+6JNYnuPO5m7RqsEdISI7Efm5EKYMGypq/npYtrnrfedkgXt4c97uAV08f7sZoOQjHTV8HS1g55M25hhX8zZwJ+m+iKLpXqz6YVNIc3yd/TkOVPAPYJt6Lntn+OszxvIVB9pExFfM7S5OL8qJDmAUNKULvFX3CFY/PmAwUMCgYEAxwTMFDWxOFIHGfoKFZYQv0U4DWBdo9kj5KEXQgGKX+TPakMtkPkPT5LZqXmEc4e46Q+ngxR11sKsl51Yl9SbdF7wHoXNWfvYgFijzibl+gTq5Ppt1dBmTlm+Hr1EL7l92AdHpawabXCtX0KjTR+YWgp9VdV5nxtYIngKYHGDuu4=',
	),
);

$conf['dao'] = array( // dao数据库操作组件配置
    'mysql' => $conf['database']['mood'],
    'redis' => $conf['cache']['Redis']['dynamic'],
);
return $conf;