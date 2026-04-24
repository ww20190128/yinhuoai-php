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
    public function code2AccessToken($userId, $code)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$conf = self::$conf;
    	$url = "https://open.kuaishou.com/oauth2/access_token?app_id={$conf['app_id']}&app_secret={$conf['app_secret']}&code={$code}&grant_type=authorization_code";

		$response = httpGetContents($url);
    	$response = empty($response) ? array() : json_decode($response, true);

    	if (empty($response['access_token'])) {
    		throw new $this->exception('授权失败');
    	}

    	$shareKsAccess = array(
    		'access_token' => $response['access_token'],
    		'refresh_token' => $response['refresh_token'],
    	);

    	$userEtt->set('shareKsAccess', json_encode($shareKsAccess));
    	$userDao->update($userEtt);
    	$shareKsAccess['url'] = "http://localhost:5173/kuaishou-publish?userId={$userId}";
    	return $shareKsAccess;
    }
    
    /**
     * 获取token
     *
     * @return array
     */
    public function refreshAccessToken($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$shareKsAccess = empty($userEtt->shareKsAccess) ? array() : json_decode($userEtt->shareKsAccess, true);
    	if (empty($shareKsAccess['refresh_token'])) {
    		throw new $this->exception('未授权，请重新授权');
    	}
    	$conf = self::$conf;
    	$url = "https://open.kuaishou.com/oauth2/refresh_token?app_id={$conf['app_id']}&app_secret={$conf['app_secret']}&refresh_token={$shareKsAccess['refresh_token']}&grant_type=refresh_token";
    	$response = httpGetContents($url);
    	$response = empty($response) ? array() : json_decode($response, true);
    	if (empty($response['refresh_token'])) {
    		throw new $this->exception('请重新授权');
    	}
    	$shareKsAccess['access_token'] = $response['access_token'];
    	$shareKsAccess['refresh_token'] = $response['refresh_token'];
    	$userEtt->set('shareKsAccess', json_encode($shareKsAccess));
    	$userDao->update($userEtt);
    	return $shareKsAccess;
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
    public function upload($userId, $clipId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	// 授权信息
    	$shareKsAccess = empty($userEtt->shareKsAccess) ? array() : json_decode($userEtt->shareKsAccess, true);
    	if (empty($shareKsAccess['refresh_token'])) {
    		throw new $this->exception('未授权，请重新授权');
    	}
    	if (empty($shareKsAccess['endpoint']) || empty($shareKsAccess['upload_token'])) {
    		$uploadResult = $this->startUpload($shareKsAccess['access_token']);
    		$shareKsAccess['endpoint'] = $uploadResult['endpoint'];
    		$shareKsAccess['upload_token'] = $uploadResult['upload_token'];
    		$userEtt->set('shareKsAccess', json_encode($shareKsAccess));
    		$userDao->update($userEtt);
    	}
    	if (empty($shareKsAccess['endpoint']) || empty($shareKsAccess['upload_token'])) {
    		throw new $this->exception('未授权，请重新授权');
    	}
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEtt = $projectClipDao->readByPrimary($clipId);
    	if (empty($projectClipEtt->mediaURL) || $projectClipEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('成品不存在');
    	}
    	// 需要上传的文件内容
    	$mediaURL = $projectClipEtt->mediaURL;
    	$url = "http://{$shareKsAccess['endpoint']}/api/upload";
    
		// 读取远程视频文件，兼容快手上传格式
		$videoContent = file_get_contents($mediaURL);
		$tmpFile = tmpfile();
		$tmpPath = stream_get_meta_data($tmpFile)['uri'];
		fwrite($tmpFile, $videoContent);
	
		// 构建上传参数，新增视频文件
		$data = array(
			'upload_token' => $shareKsAccess['upload_token'],
			'video' => new \CURLFile($tmpPath, 'video/mp4', 'upload.mp4')
		);
		$curlHandler = curl_init($url);
		curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curlHandler, CURLOPT_POST, TRUE);
		curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curlHandler, CURLOPT_TIMEOUT, 600);
		curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array(
			'Content-Type: multipart/form-data'
		));
		$response = curl_exec($curlHandler);
		curl_close($curlHandler);
		// 关闭临时文件
		fclose($tmpFile);
		$response = empty($response) ? array() : json_decode($response, true);
		return $response;
    }
    
    /**
     * 发布
     *
     * @return array
     */
    public function publish($userId, $clipId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$shareKsAccess = empty($userEtt->shareKsAccess) ? array() : json_decode($userEtt->shareKsAccess, true);
    	if (empty($shareKsAccess['refresh_token'])) {
    		throw new $this->exception('未授权，请重新授权');
    	}
    	if (empty($shareKsAccess['endpoint']) || empty($shareKsAccess['upload_token'])) {
    		$uploadResult = $this->startUpload($shareKsAccess['access_token']);
    		$shareKsAccess['endpoint'] = $uploadResult['endpoint'];
    		$shareKsAccess['upload_token'] = $uploadResult['upload_token'];
    		$userEtt->set('shareKsAccess', json_encode($shareKsAccess));
    		$userDao->update($userEtt);
    	}
    	if (empty($shareKsAccess['endpoint']) || empty($shareKsAccess['upload_token'])) {
    		throw new $this->exception('未授权，请重新授权');
    	}
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEtt = $projectClipDao->readByPrimary($clipId);
    	if (empty($projectClipEtt->mediaURL) || $projectClipEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('成品不存在');
    	}
    	// 需要上传的文件内容
    	$mediaURL = $projectClipEtt->mediaURL;
    	$cover = $projectClipEtt->previewUrl;
    	$caption = '';
    	$conf = self::$conf;
    	$postData = array(
    		'access_token' => $shareKsAccess['access_token'],
    		'app_id' => $conf['app_id'],
    		'upload_token' => $shareKsAccess['upload_token'],
    	);
    	
    
    	
    	// 发布接口调用（修正参数格式）
	    $url = "https://open.kuaishou.com/openapi/photo/publish";
	    // URL参数（官方要求直接放在url中）
	    $urlParams = http_build_query([
	        'access_token' => $shareKsAccess['access_token'],
	        'app_id' => $conf['app_id'],
	        'upload_token' => $shareKsAccess['upload_token']
	    ]);
	    $url .= '?' . $urlParams;
	
	    // 构建multipart/form-data格式的发布参数（封面和标题为必填）
	    $postData = array(
	        'cover' => new \CURLFile($cover, 'image/jpeg', 'cover.jpg'), // 封面文件
	        'caption' => $caption // 标题
	    );
	
	    $curlHandler = curl_init($url);
	    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($curlHandler, CURLOPT_POST, TRUE);
	    curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 3);
	    curl_setopt($curlHandler, CURLOPT_TIMEOUT, 300);
	    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
	    
	    $response = curl_exec($curlHandler);
	    curl_close($curlHandler);
	
	    $response = empty($response) ? array() : json_decode($response, true);
	    return $response;
    }
    
}
