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
     * 我的报告
     * 只获取已经完成的
     * 
     * @return array
     */
    public function testOrderList($userId, $pageNum = 1, $pageLimit = 20, $searchTestPaperId = 0)
    {
        $userInfo = $this->userInfo($userId);
        $userInfo = $userInfo['userInfo'];

    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEttList = $testOrderDao->readListByIndex(array(
    		'userId' => $userId,
    	));

    	$testPaperIds = array();
    	if (is_iteratable($testOrderEttList)) foreach ($testOrderEttList as $key => $testOrderEtt) {
    	    if (empty($testOrderEtt->testCompleteTime)) {
    	        unset($testOrderEttList[$key]);
    	        continue;
    	    }
    	    if (!empty($testOrderEtt->testOrderId)) { // 过滤重测报告
    	    	unset($testOrderEttList[$key]);
    	    	continue;
    	    }
    	    if (!empty($searchTestPaperId) && $testOrderEtt->testPaperId != $searchTestPaperId) { // 有查找的测评ID
    	    	continue;
    	    }
    	    if (!empty($testOrderEtt->promotionId)) { // 过滤推广测评
    	    	unset($testOrderEttList[$key]);
    	    	continue;
    	    }
    	    $testPaperIds[] = intval($testOrderEtt->testPaperId);
    	}
    	// 根据创建时间排序，后创建的放前面
    	$commonSv = \service\Common::singleton();
    	uasort($testOrderEttList, array($commonSv, 'sortByCreateTime'));
    	// 符合条件的总条数
    	$totalNum = count($testOrderEttList);
    	// 分页显示
    	if ($pageNum > 0) {
    		$testOrderEttList = array_slice($testOrderEttList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	}
    	
    	$testPaperIds = array_unique($testPaperIds);
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperModels = array();
    	if (!empty($testPaperIds)) {
    		$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    		if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $testPaperEtt) {
    			$testPaperModels[$testPaperEtt->id] = $testPaperEtt->getModel();
    		}
    	}
    	$modelList = array();
    	if (is_iteratable($testOrderEttList)) foreach ($testOrderEttList as $testOrderEtt) {
    		$testPaperInfo = empty($testPaperModels[$testOrderEtt->testPaperId]) 
    			? array() : $testPaperModels[$testOrderEtt->testPaperId];
    	    $testOrderModel = $testOrderEtt->getModel();
    	    $testOrderModel['testPaperInfo'] = $testPaperInfo;
    	    $modelList[$testOrderEtt->id] = $testOrderModel;
    	}
    	return array(
            'totalNum' => intval($totalNum),
            'list' => array_values($modelList),
    		'vipInfo' => empty($userInfo['vipInfo']) ? array() : $userInfo['vipInfo'],
        );
    }
    
}