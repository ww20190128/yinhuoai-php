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
    'mark' 					=> 'yinhuo-1', 				        // *服务器标识
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
    'web_url'				=> 'http://192.168.3.41:8080',		// 前端地址
    'serve_url'				=> 'http://192.168.3.133:666', 		// 服务器地址
	'database' => array ( // *数据库
		'yinhuo' 	=>	array ( // 服务器数据库
		    'db_host' 		=> '127.0.0.1',
		    'db_port' 		=> '3306',
		    'db_user' 		=> 'root',
		    'db_pass' 		=> '1',
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
  
    'white_list' => array( // ip白名单
        '172.16.13.97',
        '172.16.13.96',
    ),
	'fileDir' => '/data/www/mood-static/shareCode/',
    'urls' => array(
		'images' => 'http://192.168.3.133:88/',
    	'files' => 'http://192.168.3.133:88/files/',
//     	'images' => 'https://zhile-static.oss-cn-beijing.aliyuncs.com/resources/',
	),

	// 用vip930531微信扫码
	'weChat' => array(
		'appId' => 'wxde609c2255df3268',
		'appSecret' => '341a3503556f33ccea14c02e442c3182',
		'merchantId' => '1696193979', // 微信支付分配的商户号
		'APICertificateKey' => '19DA57F2314830A34CD6268FDEF225491046646D', // 商户API证书
// 		'APIv2Key' => '194CD48DA6268FD3157F2330AEF22514', // 商户APIv2密钥
		'RSA' => '775099B06253CAC60E2244C28D0AB82D519E823A', // 平台证书
		'APIv3Key' => 'F2D48DA62625148F194CD3157F2330AE', // APIv3密钥
	),
	'alipay' => array(
		'appId' => '2021005113605464', // 应用Id
		// 支付宝公钥
		'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnqdFuxXYEeIDf3nusuAKNBtGKa/oteohmUtpBsdS0ocFGlTL9nc5KbWi4eA8BdVhDto9zHnUq5mKZ5xqnWwNcDHROc4rpPZ5ZwaoXGc0hk0BgEwIXlHNsL8/OniZpyK9dOMg3qLoYVsICgmTcO+Y1lFLjhDG4VGz9GjdI2hZWIQhj+jVTUOoveo8kSOA05qx8/hsWcaT3y63mFeg9SY/iJdkDcZxzMqk1RHttOJbHeOYMO+ht1kXWO9fOwRsSXkrMacBKzl0CvFJaUJSdkQcimiGGfnTnh7BaDUZEXeV+qCh+QTgLdSUobnPWRDV7VH6U1R+tgdAdPqjT8WuxnN+cwIDAQAB',
		// 应用私钥
		'private_key' => 'MIIEwAIBADANBgkqhkiG9w0BAQEFAASCBKowggSmAgEAAoIBAQCaD47Ik0n0dsZSIArbmFEosXpN4e8XrPzhbdxOcPCs8A2EwR0ZzrXbB8IULxqBFOevhPlZKhjtvjhlblRyAonyguUgRHbvtq7gR0he/f+uwWl9qwzeQsribfFy1oW2tDMnaXiVfb3q/oVDwEMxuhwKqbVQ8o4SOAX/S5FOJEWp/L69vhSN96zb6cQUZ3IPJmzXRDeBBWmOqifctXIqhU6lLsEd5LHt+5GKTX6vBVkC6rgoOlL7LXuM0hgzCJpasW+Av2oswlrrXt3cpmsa+oqVdvVKCo0mRwkRNScd59L3zLQysS4TScYKat2oLTaP5GzlNfndRzPOl/JX2k695B2BAgMBAAECggEBAJVhZHdDqb/qsx3Kh/ypUnh8rjR6UFTKnWEQHz/H7vYgxVrlzQvLDTZV7W+YxMBIvuXHr+cvFIDhotlnA2aQ46VMGlMRT6nbnvFE7P1+l76hN2JsystwRD0NkcJiE8E2NSuP7yY0iEQlip5I/F5pMOt95puYtP0haV9DrJ7yOMI7GJAIzpClQgoip8v+zaxDS4BjlsRCT87iAZtXye7wClbr3DFHq24xe7pJYqdZHYX7iqrJ2aEBEGwazjxhOqlLNP8847mq52vtXXqixvAdavUPzIPwvySvLsDAkLDQxOLMFX6PlX+ejYuwBY16rKhLzNq+X5oPsfYmfNVGzn4lBgkCgYEAy6mOaaxqL/Wr7+gsV0/fXTe+m5IMOgZmiW51qUjAeEzJpp9bjLi5zD99FIuX2bt9MAVwQZE5uoEjg00YYwZqQ0IgLvwZjQgkRqKdEoeydSGrsoPUszJAPOnLIrJwyd5EAJJQvRYejW8yc90b99bjdqw8SWNvB+ne8V1cp/85gPMCgYEAwabWR2z9k4a8vMGNPY7PVShcORCTT4vuTse+DzGbe5oLOLAamSrLiECNiVMec/ZcS38U+1V1xEOHephJH2W46A4cPf3hcyDjGbhrhi7rk0VbiLH/eCIzZ3bFlL324S+0EGj6vFOZBPNwnW79QTE7FNyaaX+sLeZ6Mb/YIEZsZLsCgYEAnK9R9VLETyl+og/JLVMx0RG1/xIZffq2oDzvINHr4aGR8jLfyB/GMbEWldvfc4+e/Hbyqj8EXsxDehyyCl1BG4WgJQWm0q3U2tL4bO0hCqRg5/IsjcC3UyW7NnJ5+5SYVKg8nXyK/Nzzv9f4UHBuhzpzFzL8lOYEs0TUqBrfLqkCgYEAmZ6U+6JNYnuPO5m7RqsEdISI7Efm5EKYMGypq/npYtrnrfedkgXt4c97uAV08f7sZoOQjHTV8HS1g55M25hhX8zZwJ+m+iKLpXqz6YVNIc3yd/TkOVPAPYJt6Lntn+OszxvIVB9pExFfM7S5OL8qJDmAUNKULvFX3CFY/PmAwUMCgYEAxwTMFDWxOFIHGfoKFZYQv0U4DWBdo9kj5KEXQgGKX+TPakMtkPkPT5LZqXmEc4e46Q+ngxR11sKsl51Yl9SbdF7wHoXNWfvYgFijzibl+gTq5Ppt1dBmTlm+Hr1EL7l92AdHpawabXCtX0KjTR+YWgp9VdV5nxtYIngKYHGDuu4=',
	),

	'aliEditing' => array( // 阿里云剪辑
		'accessKeyId' => 'LTAI5tLTC2fRsgwKXZP75Wow',
		'accessKeySecret' => 'p52taYKYaKAJpkaWqvbH2gWuZJDZcs',
		'StorageType' => 'oss', // 仅支持 oss
		//oss-cn-beijing-internal.aliyuncs.com
		'StorageLocation' => 'cn-beijing.oss.aliyuncs.com', // 仅支持 VOD 点播存储，不支持用户自有 OSS 存储。
	),
	'' => array(
		'keyId' => 'api-key-20251222143438',
		'keySecret' => 'a3e46e90-e3d8-4fcc-bcbc-ded163538359',
	),
	'appConfig' => array(
		'name' => '智乐心理',
		'logo' => 'https://zhile-static.oss-cn-beijing.aliyuncs.com/resources/logo.png',
		'customerServiceLink' => 'https://work.weixin.qq.com/kfid/kfc22928e4f537a14da', // 客服
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