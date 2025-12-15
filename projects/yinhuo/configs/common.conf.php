<?php
/**
 * 通用配置
 */
return array(
	'regular' => array( // 正则表达式
		'phone' => "/^1[23456789]{1}\d{9}$/", // 手机号
	),
	'sms' => array( // 验证码发送相关的配置
		'dxt' => array( // 蝶信通
			'url' 		=> 'http://61.129.57.233:7891/mt',
			'userName' 	=> '100112',
			'password' 	=> 'm@g0ok591',
		),
		'ali' => array( // 阿里
			'appKey' 			=> '23274584',
			'appSecre' 			=> '1fc7163f24451d066021349fa551020e',
			'signName' 			=> '博看网',
			'smsType' 			=> 'normal',
			'respondFormat' 	=> 'json', // 响应格式, 默认为xml, 可选xml,json
		),
		'ytx' => array( // 云通讯
			'appId' 			=> '8a48b55147f223e10147f61e0e7e0559',
			'accountSid' 		=> 'aaf98f8947f222a30147f61a74ed04fa',
			'accountToken' 		=> '8f3e6844d9eb46c580cc01966ac784f3',
			'url' 				=> 'app.cloopen.com',
			'port' 				=> '8883',
			'version' 			=> '2013-12-26',
		),
	),
	// 静态资源域名
	'resourceDir' => array(
		'activityImg' => '/home/www/phpItemSet-zone/Webroot/static/activity/', // 活动图片地址'
	),
	'cacheSecond'=> 3600,
	//'imageUrl' => 'http://cdn-test.17kjs.com/questions/',
	'imageUrl' => 'http://192.168.3.133:88/',  
);