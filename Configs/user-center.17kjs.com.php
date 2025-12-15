<?php
/**
 * 用户中心系统 - 配置文件
 * 
 * 说明:
 * 注释带*号的为必选项
 * 服务器id段:测试{1, 10}, 正式  {100, 999}
 * 服务器类型: 1 考教师  2 公考
 */
$conf = array(
    'id' 					=> 129, 							// *服务器id
	'type' 					=> 4, 								// *服务器类型, 必须与[project_code]一一对应
    'mark' 					=> 'UCenter-1', 				// *服务器标识
	'project_code' 			=> 'UCenter', 					// *项目代号, 必须与projects对应的项目名称一致
    'debug' 				=> true, 							// *调试模式
	'session_switch' 		=> true, 							// *是否启用session
    'language' 				=> 'zh_CN', 						// 语言
    'server_start_time' 	=> '2016-03-11 12:00:00', 			// 开服时间
    'maintain_start_time' 	=> '2016-03-11 12:00:00', 			// 维护开始时间
    'maintain_end_time' 	=> '2016-03-11 12:00:00', 			// 维护结束时间
   	'center' 				=> '123.59.131.101:8080', 			// 中心服务器域名
   	'private_key' 			=> 'thsiadfasdf', 					// 服务器私钥 
   	'time_zone' 			=> 'Asia/Shanghai', 				// 时区 
	'inner_ip' 				=> '172.16.108.77', 			// 内网ip(php服务器的内网ip)
	'database' => array ( // *数据库
		'user_center' 	=>	array (
            'is_main' 		=> true,
            'db_host' 		=> 'pc-2ze8cot498a3w9o56.rwlb.rds.aliyuncs.com',
			'db_port' 		=> '3306',
			'db_user' 		=> 'public_admin',
			'db_pass' 		=> 'JustDoIt2019',
			'db_name' 		=> 'user_center',
			'persistence' 	=> false,
			'log_query' 	=> false
		),
  	),

    // *缓存
    'cache' => array(
        'Redis' => array(
            'dynamic' => array(
                'cache_host'  => 'r-2zexflx53iwnqb817r.redis.rds.aliyuncs.com',
                'cache_port'  => '6379',
                'out_time'    => '0',
                'serialize'   => 'true',
                'database'    => '0',
                'auth'        => 'redis#$@JustDoIt2021',
                'cachePrefix' => 'ucenter',
            ),
            'static'  => array(
                'cache_host'  => 'r-2zexflx53iwnqb817r.redis.rds.aliyuncs.com',
                'cache_port'  => '6379',
                'out_time'    => '0',
                'serialize'   => 'true',
                'database'    => '1',
                'auth'        => 'redis#$@JustDoIt2021',
                'cachePrefix' => 'ucenter',
            ),
        )
    ),

    'dingdingParams' => array(
        'login' => array(
            // 用户中心测试服，新版公用的扫码应用
            '0' => array(
                'appid'     => 'dingoada2nva2rv7jklofd',
                'appsecret' => 'EAxYwyF6SXI_zAM4lbZuiTQ98zve-H3CLnpSzeHdN4K93S4g07lnNoHLklK0YXtk',
            ),

            '11' => array(
                'appid'     => 'dingoavaizl2pdtwa8bohl',
                'appsecret' => 'PRmaMIU7W5M_s9_6HouG4XgVyV4ZDA7wZuMvma0W3NmqwxMLR3KSF5thyKfRocPd',
            ),
            '12' => array(
                'appid'     => 'dingoanbz65dopa8ta2ql4',
                'appsecret' => 'hT04gE2gXM0T_LIexmm1Z0mdO-OnU7Sv28_W6VRlPaxviRfnD5mSwMaKC5YTN5jH',
            ),
            '13' => array(
                'appid'     => 'dingoaiixeaecajrrqrbcs',
                'appsecret' => '01dlnSUzXFHPNr7KWAxVla4VTsET-O_O9VbiITEJ__sLwQthRTUsVjd-CsWN_Rad',
            ),
            // 17学堂-CRM
            '17' => array(
                'appid'     => 'dingoayen0kaca4nmjzlwq',
                'appsecret' => '7Id9-jsg32-5s71TSpk_NnUTFL1h8k75mXQrL-DpQSN-YcOlpMG0uFSCKrlShiv5',
            ),
            // 数据看板-教考
            '18' => array(
                'appid'     => 'dingoavfqjpc5v1tq0rlxy',
                'appsecret' => '3srNrHjemXP-CwQSARcMKvvMOliphT16JdXy2J_ToVLa-T0OxSiROVrNNYrW3Ycq',
            ),
            // 数据看板-公考
            '19' => array(
                'appid'     => 'dingoasppckjnz1dhwgbmk',
                'appsecret' => 'vCRg2CvjgyfYY86qamvjwdFIuNZY3-FY_u2wxvsDgf2hVmEcAEslDhL-rWsAg9VU',
            ),
            // 教考-学情数据平台
            '20' => array(
                'appid'     => 'dingoaphjjbgntfywg7oev',
                'appsecret' => 'QMai5_bNcRXXy7haWZcepV5zxN4JsJoKH5YbKS0J60JVFGPWH7J_UEyc5tfNrQHj',
            ),
            // 公考-学情数据平台
            '21' => array(
                'appid'     => 'dingoatehx0km13k2ujg5r',
                'appsecret' => 'cK5pzdlE6krdHO3UpcgX6iPQ3X3Q9Yd5PesHEHLv2ax9EzW3kmm-5E06TmHGeii4',
            ),
            // 雷鹏宾-素材纠错
            '22' => array(
                'appid'     => 'dingoawb1hzbsz19vlyn5j',
                'appsecret' => 'SCpHcqIpWX5CjCHNjm-H0t_Em8KfneapuKkblxXrEM3QnCqOi8PIMB5UZ55KOgHm',
            ),
            // 高辛鹏H5-Builder
            '23' => array(
                'appid'     => 'dingoabtwi5ddh72ksmhyg',
                'appsecret' => 'CulcD6qghJdBFouJIOibCLWTOW6T-UYnp_PM0uuKUgdbxFzIiFYHT22RrJXQXj6S',
            ),
            // 雷鹏宾-素材纠错-公考
            '24' => array(
                'appid'     => 'dingoalqrv3mzyqkl2nfaz',
                'appsecret' => 'KgrvUbm8pefhDobx-cKvlYOsNeEDe2lQOM1W1RVKjZ9PJJnBWpfCMG62f9RxX_lj',
            ),
            // 一起考教师管理后台
            '25' => array(
                'appid'     => 'dingoaxzsw5cciedjgifl3',
                'appsecret' => 'pLzVU0MoV2fLCzjsxjtSTWce9fOR9ol6lJ0WJkhV6qj4092RiM5Jft2c10YePsnJ',
            ),
            // 一起考公考管理后台
            '26' => array(
                'appid'     => 'dingoaimcdkisowsfn4gqr',
                'appsecret' => 'AkSQZRgIqIgInG6qr--1FIa3fWuUMr6qmFv1LK7pk7bcDyGBpQAcgr98QNM-NPEt',
            ),
        ),
        'user'  => array(
            'agent_id'  => '686832982',
            'appkey'    => 'dinglywycyyrwf8ircbp',
            'appsecret' => '1KSuHJATfjo1PcrxvKKUcm84OnhQiuafCZgEkmn7DEQgyNrcVqPzJzzQpY7Zod48',
        ),

        'H5' => [
            // 企业内部H5微应用
            '11'    => [
                'appKey'    => 'dingozh89wfrzvaetxbp',
                'appSecret' => 'QWe50DXQxPNhBswYT9gyrkYRIH7qxnLOdU2x9yBcM5V1SIFLgINNrjh6rzraSYvb',
            ],
            // 教考
            '12'    => [
                'appKey'    => 'ding9nguct47tyu8oehg',
                'appSecret' => '7AW8nmB1ApPxQeN9ujs4psRC8zr2c6eptU03oGBtdpZLjfRnbSz4XhvP-RWnp_6e',
            ],
            '13'    => [
                'appKey'    => 'dingvyja4jhcerxhlbod',
                'appSecret' => 'w9WYMehHn2mamPfB9IGFo3KgrhxsqaB2IOTmI_OMMcwTlASsOpkl3vNzJUCmu3iX',
            ],

            // 教考APP 和 公考APP的管理后台，
            // 目前为方便调取发送钉钉消息的接口而配置的，不是真实的appi、appsecret
            '15' => array(
                'appid'     => 'dingoanbz65dopa8ta2ql4',
                'appsecret' => 'hT04gE2gXM0T_LIexmm1Z0mdO-OnU7Sv28_W6VRlPaxviRfnD5mSwMaKC5YTN5jH',
            ),
            // 公考APP的管理后台
            '16' => array(
                'appid'     => 'dingoanbz65dopa8ta2ql4',
                'appsecret' => 'hT04gE2gXM0T_LIexmm1Z0mdO-OnU7Sv28_W6VRlPaxviRfnD5mSwMaKC5YTN5jH',
            ),

            // 17学堂-CRM
            '17' => array(
                'appKey'    => 'dingi7ljnntzzwxsfbs5',
                'appSecret' => 'wCgDyviMVqBhmovvVsWMHiEQLkurPffafVnXMN8tjtV6TYZ-oy1BPC7JKGcMm15i',
            ),
        ],
    ),

    // 企业微信
    'weixin' => [
        [
            'corpid'  => 'ww72da75059f3c0ef7',
            'agentid' => '1000023',
            'secret'  => 'gABaYN9LEayo2x7bcaPyFh4uTEcKPOdKkFZVWAsbkVY'
        ], // 北京清众教育企业号
        [
            'corpid'  => 'ww89894b583e88aabe',
            'agentid' => '1000010',
            'secret'  => 'XHzHj1emG6pwBZl8RBsZ2zFIz7kTzlQcx-Nfgg7OU8w'
        ], // 北京掌上壹柒企业号
        [
            'corpid'  => 'ww41ce706e424dd476',
            'agentid' => '1000019',
            'secret'  => 'jxBIXLnJunvDtdWjDTKmRTMaJnvvOHiE5z99gRCtrFs'
        ], // 北京掌上园丁企业号
        [
            'corpid'  => 'wwfad1debfdf5ad6fe',
            'agentid' => '1000016',
            'secret'  => 'bmRhCuZf_cAGpG8iieWYEnzzUaMFKeg85BIcpVNbRK0'
        ], // 北京清众教育企业号
    ],

    // 短信配置
    'sms'   => array(
        'apiUrl'    => 'http://smssh1.253.com/msg/send/json',
        'account'   => 'N7107001',
        'password'  => 'fXUVxdW6w7611e'
    ),

    // kafka 配置
    'kafka' => [
        'topic'   => ['kjs_message_bus'],
        'groupId' => 'uc_subscriber',
        'server'  => '172.17.63.172:9092,172.17.63.173:9092,172.17.63.174:9092',
    ],
	'desktopUrl' => "http://112.125.26.210:8019/index.php",
);

$conf['dao'] = array(
    'mysql' => $conf['database']['user_center'],
    'redis' => $conf['cache']['Redis']['dynamic'],
);

return $conf;