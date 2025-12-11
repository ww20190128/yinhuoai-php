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

    /**
     * 获取分享信息
     *
     * @return array
     */
    public function getShareTestPaperInfo($testPaperId = 0, $promotionId = 0, $webLink = '', $shareCode = '', $webDesc = '')
    {
    	$host = empty($this->frame->conf['web_url']) ? array() : $this->frame->conf['web_url'];
    	$result = array();
    	if (!empty($promotionId)) { // 推广
    		$promotionSv = \service\Promotion::singleton();
    		$promotionInfo = $promotionSv->getPromotionInfo($promotionId);
    		if (!empty($promotionInfo)) {
    			//$link = "https://one.1cece.top/goods?testPaperId={$testPaperId}&source=goodsShare";
    			$link = $host . "/detail?promotionId={$promotionId}&source=goodsShare";
    			if (!empty($shareCode)) {
    				$link .= "&shareCode=" . $shareCode;
    			}
    			$result = array(
    				'desc' => empty($promotionInfo['testPaperInfo']['subhead']) ? '' : $promotionInfo['testPaperInfo']['subhead'], // desc
    				'link' => $link,
    				'title' => empty($promotionInfo['promotionInfo']['name']) ? '' : $promotionInfo['promotionInfo']['name'],
    				'imgUrl' => empty($promotionInfo['testPaperInfo']['coverImg']) ? '' : $promotionInfo['testPaperInfo']['coverImg'],
    			);
    		}
    	} elseif (!empty($testPaperId)) { // 测评信息
    		$testPaperSv = \service\TestPaper::singleton();
    		$testPaperInfo = $testPaperSv->testPaperInfo($testPaperId);
    		if (!empty($testPaperInfo)) {
    		    //$link = "https://one.1cece.top/goods?testPaperId={$testPaperId}&source=goodsShare";
				$link = $host . "/detail?testPaperId={$testPaperId}&source=goodsShare";
				if (!empty($shareCode)) {
					$link .= "&shareCode=" . $shareCode;
				}
    		    $result = array(
    		        'desc' => empty($testPaperInfo['subhead']) ? '' : $testPaperInfo['subhead'], // desc
    		        'link' => $link,
    		        'title' => empty($testPaperInfo['name']) ? '' : $testPaperInfo['name'],
    		        'imgUrl' => empty($testPaperInfo['coverImg']) ? '' : $testPaperInfo['coverImg'],
    		    );
    		}
    	}
    	if (!empty($webLink)) {
    	    $result['link'] = $webLink;
    	}
    	if (!empty($webDesc)) {
    		$result['desc'] = $webDesc;
    	}
    	return $result;
    }
    
    /**
     * 获取分享信息（正念）
     *
     * @return array
     */
    public function getShareMindfulnessInfo($mindfulnessId)
    {
    	$host = empty($this->frame->conf['web_url']) ? array() : $this->frame->conf['web_url'];
    	$mindfulnessSv = \service\Mindfulness::singleton();
    	$mindfulnessInfo = $mindfulnessSv->mindfulnessInfo(0, $mindfulnessId);
    	if (empty($mindfulnessInfo)) {
    		 throw new $this->exception('正念课程已删除');
    	}
    	$link = $host . "/mindfulness/detail?mindfulnessId={$mindfulnessId}&source=goodsShare";
    	$result = array(
    		'desc' => empty($mindfulnessInfo['mindfulness']['desc']) ? '' : $mindfulnessInfo['mindfulness']['desc'], // desc
    		'link' => $link,
    		'title' => empty($mindfulnessInfo['mindfulness']['name']) ? '' : $mindfulnessInfo['mindfulness']['name'],
    		'imgUrl' => empty($mindfulnessInfo['mindfulness']['coverImg']) ? '' : $mindfulnessInfo['mindfulness']['coverImg'],
    	);
    	return $result;
    }
    
    /**
     * 首页
     *
     * @return array
     */
    public function main()
    {
        // 轮播图
        $bannerDao = \dao\Banner::singleton();
        $bannerEttList = $bannerDao->readListByIndex(array(
            'status' => 0,
        ));
        $commonSv = \service\Common::singleton();
        $bannerList = array();
        if (is_iteratable($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
            $bannerList[] = array(
                'id' => intval($bannerEtt->id),  
                'url' => $commonSv::formartImgUrl($bannerEtt->url, 'banner'),
                'goto' => $bannerEtt->goto,
            );
        }
        
        // 分类
        $classifySv = \service\Classify::singleton();
        $classifyList = $classifySv->getClassifyList(true);
        // 测评
        $classifySv = \service\Classify::singleton();
        $hotResult = $classifySv->getListByClassify(101, array(), 1, 4); // 热卖爆款
        $newResult = $classifySv->getListByClassify(102, array(), 1, 4); // 新品首发
        $choicenessResult = $classifySv->getListByClassify(103, array(), 1, 100); // 精选推荐
        $freeResult = $classifySv->getListByClassify(103, array(), 1, 100); // 精选推荐
        $userRecommendReslut = $classifySv->getListByClassify(103, array(), 1, 100); // 精选推荐
        $testPaperList = array(
        	'choicenessList' => empty($choicenessResult['list']) ? array() : $choicenessResult['list'], // 精选推荐
            'freeList' => empty($freeResult['list']) ? array() : $freeResult['list'], // 限时免费
            'userRecommendList' => empty($userRecommendReslut['list']) ? array() : $userRecommendReslut['list'], // 用户推荐
        	'hotList' => empty($hotResult['list']) ? array() : $hotResult['list'], // 热卖爆款
        	'newList' => empty($newResult['list']) ? array() : $newResult['list'], // 新品首发
        	'newTotalNum' => empty($newResult['totalNum']) ? 0 : $newResult['totalNum'], // 新品首发-数量
        );
        $appConfig = empty($this->frame->conf['appConfig']) ? array() : $this->frame->conf['appConfig'];
        return array(
        	'appConfig' => $appConfig,
            'bannerList' => $bannerList,
            'classifyList' => array_values($classifyList),
            'testPaperList' => $testPaperList,
        );
    }

}