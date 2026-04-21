<?php
namespace service;

/**
 * ShareKs 逻辑类
 * 
 * @author 
 */
class ShareKs extends ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return ShareKs
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ShareKs();
        }
        return self::$instance;
    }

    /**
     * 获取token
     * 
     * @return array
     */
    public function code2AccessToken($code)
    {
    	$url = "https://open.kuaishou.com";
    	$data = array(
    		'app_id' => 'ks654147848518544440',
    		'app_secret' => '5uIis5sYxWx1dTDT_7zhNw',
    		'code' => $code,
    		'grant_type' => 'authorization_code',
    	);
    	$url = "https://open.kuaishou.com/openapi/user_info?appid={$data['app_id']}&app_secret={$data['app_secret']}&code={$code}&grant_type=authorization_code";
    	$response = httpGetContents($url);
    	return $response;
    }

}