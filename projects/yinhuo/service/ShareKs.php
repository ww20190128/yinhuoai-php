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

    // 配置
    private static $conf = array(
    	'app_id' => 'ks654147848518544440',
    	'app_secret' => '5uIis5sYxWx1dTDT_7zhNw',
    );
    
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
    	$conf = self::$conf;
    	$url = "https://open.kuaishou.com/openapi/user_info?appid={$conf['app_id']}&app_secret={$conf['app_secret']}&code={$code}&grant_type=authorization_code";
    	$response = httpGetContents($url);
    	$response = empty($response) ? array() : json_decode($response, true);
    	$access_token = empty($response['access_token']) ? '' : $response['access_token']; // 认证信息
    	if (empty($access_token)) {
    		throw new $this->exception('请重新授权');
    	}
    	$uploadInfo = $this->startUpload($access_token);
    	$uploadInfo['access_token'] = $access_token;
    	return $uploadInfo;
    }

    /**
     * 发起上传
     * 
     * @return array
     */
    private function startUpload($accessToken)
    {
    	$conf = self::$conf;
    	$url = "https://open.kuaishou.com/openapi/photo/start_upload";
    	$conf = self::$conf;
    	$data = array(
    		'access_token' => $access_token,
    		'app_id' => $conf['app_id'],
    	);
    	$response = doPost($url, $data);
    	$response = empty($response) ? array() : json_decode($response, true);
    	if (empty($response['upload_token']) || empty($response['endpoint'])) {
    		throw new $this->exception('请重新授权');
    	}
    	return $response;
    }
    
    /**
     * 上传
     *
     * @return array
     */
    public function upload($clipId, $uploadToken, $endpoint)
    {
    	$conf = self::$conf;
    	$url = "http://{$endpoint}/api/upload";
    	$conf = self::$conf;
    	$data = array(
    		'upload_token' => $uploadToken,
    	);
    	$response = doPost($url, $data);
    	$response = empty($response) ? array() : json_decode($response, true);
    	return $response;
    }
    
    /**
     * 发布
     *
     * @return array
     */
    public function publish($clipId, $uploadToken, $endpoint)
    {
    	$conf = self::$conf;
    	$url = "https://open.kuaishou.com/openapi/photo/publish";
    	$conf = self::$conf;
    	$data = array(
    		'cover' => $cover, // 封面
    		'caption' => $caption, // 标题
    	);
    	$response = doPost($url, $data);
    	$response = empty($response) ? array() : json_decode($response, true);
    	return $response;
    }
}