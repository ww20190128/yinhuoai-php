<?php
namespace service;

/**
 * 用户  逻辑类
 *
 * @author
 */
class User extends ServiceBase
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
     * @return User
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new User();
        }
        return self::$instance;
    }
    
    /**
     * 微信登陆
     * 
     * @return array
     */
    public function loginByWeChat($code)
    {
    	$weChat = empty($this->frame->conf['weChat']) ? array() : $this->frame->conf['weChat'];
    	if (empty($weChat)) {
    		throw new $this->exception('获取微信配置失败！');
    	}
    	$appId = $weChat['appId'];
    	$appSecret = $weChat['appSecret'];
    	// 第1步：通过code换取网页授权信息
    	$url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
    	$response = httpGetContents($url);
    	$now = $this->frame->now;
    	$response = empty($response) ? array() : json_decode($response, true);
    	$userInfo = array();
    	if (empty($response['session_key'])) {
    	    return $response;
    	    throw new $this->exception('2.获取用户授权失败' . empty($response['errmsg']) ? '' : '：' . $response['errmsg'], array('status' => 2));
    	}
    	$openid = empty($response['openid']) ? '' : $response['openid']; // 用户唯一标识
    	$session_key = empty($response['session_key']) ? '' : $response['session_key']; // 会话密钥
    	$userInfo = $response;
    	
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readListByIndex(array(
    	    'openid' => $openid,
    	), true);
    	if (!empty($userEtt) && $userEtt->status == \constant\Common::DATA_DELETE) {
    	    throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	} 
    	$userName = empty($response['nickname']) ? '' : $response['nickname'];
    	$headImgUrl = empty($response['headimgurl']) ? '' : $response['headimgurl'];
    	$sex = empty($response['sex']) ? 0 : $response['sex'];
    	$language = empty($response['language']) ? '' : $response['language'];
    	$country = empty($response['country']) ? '' : $response['country'];
    	$province = empty($response['province']) ? '' : $response['province'];
    	$city = empty($response['city']) ? '' : $response['city'];
    	if (empty($userEtt)) { // 写入用户信息
    	    $userEtt = $userDao->getNewEntity();
    	    $userEtt->openid = $openid;
    	    $userEtt->userName = $userName;
    	    $userEtt->headImgUrl = $headImgUrl;
    	    $userEtt->sex = $sex;
    	    $userEtt->language = $language;
    	    $userEtt->country = $country;
    	    $userEtt->province = $province;
    	    $userEtt->city = $city;
    	    $userEtt->createTime = $now;
    	    $userEtt->updateTime = $now;
    	    $userId = $userDao->create($userEtt);
    	} else { // 更新用户信息
    	    if ($userEtt->status == \constant\Common::DATA_DELETE) {
    	        throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	    }
    	    $userEtt->set('userName', $userName);
    	    $userEtt->set('headImgUrl', $headImgUrl);
    	    $userEtt->set('sex', $sex);
    	    $userEtt->set('language', $language);
    	    $userEtt->set('country', $country);
    	    $userEtt->set('province', $province);
    	    $userEtt->set('city', $city);
    	    $userEtt->set('updateTime', $now);
    	    $userDao->update($userEtt);
    	    $userId = $userEtt->userId;
    	}
    	$userInfo['userId'] = $userId;
    	$token = encrypt(base64_encode(json_encode($userInfo)));
    	return array(
    	    'token' => $token,
    	);
    }
    
    /**
     * 获取用户信息
     *
     * @return array
     */
    public function userInfo($userEtt)
    {
    	if (is_numeric($userEtt)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userEtt);
    	}
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
        $userModel = $userEtt->getModel();
        return array(
        	'userInfo' => $userModel,
        	'configList' => array(),
        );
    }
    
    /**
     * 修改账号信息
     *
     * @return array
     */
    public function reviseUser($userId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('账号不存在');
    	}
    	if (!empty($info['userName']) && $info['userName'] != $userEtt->userName) {
    		$userEtt->set('userName', $info['userName']);
    	}
    	if (!empty($info['phone']) && $info['phone'] != $userEtt->phone) {
    		$userEtt->set('phone', $info['phone']);
    	}
    	if (!empty($info['imageInfo'])) {
    		$file = $info['imageInfo']['file']; // 文件内容
    		$fileSize = filesize($file); // 文件大小
    		$fileInfo = pathInfo($file);
    		$extension = $fileInfo['extension'];
    		$profileKey = "resources/userHeadImg/{$fileName}.{$extension}"; // 上传的目录
    		$ossSv = \service\reuse\OSS::singleton();
    		$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    		$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    		$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, file_get_contents($file));
    		if (empty($ossResult)) {
    			throw new $this->exception('头像上传失败');
    		}
    		$headImgUrl = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    		$userEtt->set('headImgUrl', $headImgUrl);
    	}
    	$now = $this->frame->now;
    	$userEtt->set('headImgUrl', $now);
    	$userDao->update($userEtt);
    	return $this->userInfo($userEtt);
    }
    
    /**
     * 注销登录
     *
     * @param  int  $userId  用户id
     *
     * @return array
     */
    public function logout($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('账号不存在');
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
}