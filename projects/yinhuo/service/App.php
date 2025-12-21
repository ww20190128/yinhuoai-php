<?php
namespace service;

/**
 * 首页 逻辑类
 * 
 * @author 
 */
class App extends ServiceBase
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
     * @return Index
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }
        return self::$instance;
    }
    
    /**
     * 获取热门音乐分类
     *
     * @return array
     */
    public function getMusicClassifys()
    {
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '推荐',
    	);
    	$list[] = array(
    		'id' => 2,
    		'name' => '热门榜',
    	);
    	$list[] = array(
    		'id' => 3,
    		'name' => '飙升榜(新)',
    	);
    	$list[] = array(
    		'id' => 4,
    		'name' => '原创榜',
    	);
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     *
     * @return array
     */
    public function getActorClassifys()
    {
    	$list = array();
    	$list[] = array(
    			'id' => 1,
    			'name' => '豆包大模型2.0',
    	);
    	$list[] = array(
    			'id' => 2,
    			'name' => '通用模型',
    	);
    	$list[] = array(
    			'id' => 3,
    			'name' => 'IP仿音',
    	);
  
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     *duration:100, // 播放时长

     * @return array
     */
    public function getActorList()
    {
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '四郎',
    		'url' => 'https:xxxx',
    		
    	);
    	$list[] = array(
    			'id' => 2,
    			'name' => '熊二',
    			'url' => 'https:xxxx',
    	
    	);
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     *duration:100, // 播放时长
    
     * @return array
     */
    public function getMusicList()
    {
    	$list = array();
    	$list[] = array(
    			'id' => 1,
    			'name' => '音乐名称',
    			'url' => 'https:xxxx',
    			'duration' => 119,
    	);
    
    	return $list;
    }
    
    
    /**
     * 获取静态配置
     *
     * @return array
     */
    public function getStaticConfig()
    {
    	$filterList = array();
    	$transitionList = array();
    	$filterArr = cfg('filter');
    	$transitionArr = cfg('transition');
    	foreach ($filterArr as $name => $id) {
    		$filterList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	foreach ($transitionArr as $name => $id) {
    		$transitionList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	return array(
    		'filterList' => $filterList,
    		'transitionList' => $transitionList,
    	);
    }

    /**
     * 获取微信配置
     *
     * @return array
     */
    public function getWeChatConfig($url)
    {
    	$weChat = empty($this->frame->conf['weChat']) ? array() : $this->frame->conf['weChat'];
    	if (empty($weChat)) {
    		throw new $this->exception('获取微信配置失败！');
    	}

    	$appId = $weChat['appId'];
    	$appSecret = $weChat['appSecret'];
    	// 从文件缓存中
    	$appConfigFile = CACHE_PATH . $appId . '_appConfig';
    	if (file_exists($appConfigFile)) {
    		$appConfig = file_get_contents($appConfigFile);
    		$appConfig = empty($appConfig) ? array() : json_decode($appConfig, true);
    	}
    	$now = $this->frame->now;
    	if (empty($appConfig) || ($now - $appConfig['createTime'] + 60 >= $appConfig['expires_in'])) { // 过期
    		// 获取access_token
    		$tmpUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
    		$response = httpGetContents($tmpUrl);
    		$response = empty($response) ? array() : json_decode($response, true);
    		$access_token = empty($response['access_token']) ? '' : $response['access_token'];
    		if (empty($access_token)) {

    			throw new $this->exception('获取微信配置失败！');
    		}
    		// 获取ticket
    		$tmpUrl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=" . $access_token;
    		$response = httpGetContents($tmpUrl);
    		$response = empty($response) ? array() : json_decode($response, true);
    		$ticket = empty($response['ticket']) ? '' : $response['ticket'];
    		if (empty($ticket)) {
    			throw new $this->exception('获取微信配置失败！');
    		}
    		$appConfig = array(
    			'access_token' => $access_token,
    			'expires_in' => empty($response['expires_in']) ? '' : $response['expires_in'],
    			'createTime' => $now,
    			'ticket' => $ticket,
    		);
    		@file_put_contents($appConfigFile, json_encode($appConfig));
    	}
    	// 生成noncestr和timestamp
    	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    	$noncestr = "";
    	$length = 16;
    	for ($i = 0; $i < $length; $i++) {
    		$noncestr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    	}
    	$timeStamp = $this->frame->now;	
    	$signature = "jsapi_ticket=" . $appConfig['ticket'] . "&noncestr=" . $noncestr . "&timestamp=" . $timeStamp . "&url=" . $url;
    	$signature = sha1($signature);
    	$jsApiList = array("updateAppMessageShareData", "updateTimelineShareData", "chooseWXPay", "requestMerchantTransfer");
    	$weChatConfig = array( // 微信配置
    		'beta' => 0,
    		'debug' => 0,
    		'appId' => $appId,
    		'timestamp' => $timeStamp,
    		'nonceStr' => $noncestr,
    		'signature' => $signature,
    		'jsApiList' => $jsApiList,
    		'openTagList' => array(),
    		'url' => $url,
    	);
    	return $weChatConfig;
    }


}