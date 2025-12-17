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
    	// 第二步：通过code换取网页授权access_token
    	$url = "https://api.weixin.qq.com/sns/jscode2session/access_token?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
    	$response = httpGetContents($url);
    	$now = $this->frame->now;
    	$response = empty($response) ? array() : json_decode($response, true);
    	
    	$userInfo = array();
    	if (empty($response['session_key'])) {
    	    return $response;
    	    throw new $this->exception('2.获取用户授权失败' . empty($response['errmsg']) ? '' : '：' . $response['errmsg'], array('status' => 2));
    	}
    	$openid = empty($response['openid']) ? '' : $response['openid']; // 用户唯一标识
    	$unionid = empty($response['unionid']) ? '' : $response['unionid']; // 用户唯一标识
    	$session_key = empty($response['session_key']) ? '' : $response['session_key'];
    	$userInfo = $response;
    	
    	// 第三步：刷新access_token（如果需要）
    	// 第四步：拉取用户信息(需scope为 snsapi_userinfo)
  
    	$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
    	$response = httpGetContents($url);
    	$response = empty($response) ? array() : json_decode($response, true);
  
    	$openid = empty($response['openid']) ? '' : $response['openid'];
    	if (empty($openid)) {
    	    return $response;
    	    throw new $this->exception('4.获取用户授权失败' . empty($response['errmsg']) ? '' : '：' . $response['errmsg'], array('status' => 2));
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readListByIndex(array(
    	    'openid' => $response['openid'],
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
        // 用户购买的vip
        $userVipDao = \dao\UserVip::singleton();
        $userVipEttList = $userVipDao->readListByIndex(array(
            'userId' => $userEtt->userId,
        ));
        $vipSv = \service\Vip::singleton();
        $vipConfigList = $vipSv->getConfigList();
        
        $now = $this->frame->now;
        $userVipList = array(); // 用户购买的vip列表
        if (is_iteratable($userVipEttList)) foreach ($userVipEttList as $userVipEtt) {
            if (empty($vipConfigList[$userVipEtt->vipId]) || empty($userVipEtt->effectTime)) { // 未支付
                continue;
            }
            $vipConfigModel = $vipConfigList[$userVipEtt->vipId];
            // 生效的时间
            $effectEndTime = $userVipEtt->effectTime + $vipConfigModel['effectDay'] * 86400;
            if ($effectEndTime <= $now) { // vip已失效
                continue;
            }
            $useTestIds = empty($userVipEtt->useTestIds) ? array() : array_map('intval', explode(',', $userVipEtt->useTestIds));
            $userVipList[$userVipEtt->id] = array(
                'id' => intval($userVipEtt->id),
                'userId' => intval($userVipEtt->userId),
                'useGiveNum' => intval($userVipEtt->useGiveNum), // 已赠送次数
                'giveNumLimit' => $vipConfigModel['giveNum'], // 赠送次数上限
                'useTestIds' => $useTestIds, // 已测试的ID
                'testPaperNumLimit' => empty($vipConfigModel['testPaperNum']) ? 0: $vipConfigModel['testPaperNum'], // 测评次数上限
                'effectTime' => intval($userVipEtt->effectTime), // 生效时间
                'effectEndTime' => $effectEndTime, // 效果结束时间
                'effectDay' => ceil(($effectEndTime - $userVipEtt->effectTime) / 86400), // vip有效时长
                
                'createTime' => intval($userVipEtt->createTime),
                'updateTime' => intval($userVipEtt->updateTime),
                'type' => intval($vipConfigModel['type']), // vip 类型
                'name' => $vipConfigModel['name'], // vip名称
                'price' => $vipConfigModel['price'], // 价格
                'originalPrice' => $vipConfigModel['originalPrice'], // 原始价格
                'outTradeNo' => '',
            );
        }
        // 获取购买的订单号
        if (!empty($userVipList)) {
        	$orderDao = \dao\Order::singleton();
        	$where = "`goodsType`=" . \constant\Order::TYPE_GOODS_VIP . ' and `goodsId` in (' . implode(',', array_keys($userVipList)) . ')';
        	$orderEttList = $orderDao->readListByWhere($where);
        	if (is_iteratable($orderEttList)) foreach ($orderEttList as $orderEtt) {
        		if (!empty($userVipList[$orderEtt->goodsId])) {
        			$userVipList[$orderEtt->goodsId]['outTradeNo'] = $orderEtt->outTradeNo;
        		}
        	}
        }
        $vipUseGiveNum = 0; // vip 已赠送的次数
        $vipGiveLimit = 0; // vip 可赠送的次数
        $giveEffectVip = array(); // 优先消耗的赠送
        $testEffectVip = array(); // 优先消耗的次数限制
        $showVipModel = array(); // 优先展示的vip，展示级别最高，到期时间靠后的
        foreach ($userVipList as $userVip) {
            if ($userVip['effectEndTime'] <= $now) { // vip已失效
                continue;
            }
            $vipUseGiveNum += $userVip['useGiveNum'];
            $vipGiveLimit += $userVip['giveNumLimit'];
            if (empty($showVipModel)) {
                $showVipModel = $userVip;
            } elseif ($userVip['type'] > $showVipModel['type']) { // 显示当前生效且最牛逼的
                $showVipModel = $userVip;
            } elseif ($userVip['type'] == $showVipModel['type'] && $userVip['effectEndTime'] > $showVipModel['effectEndTime']) {
                $showVipModel = $userVip;
            }
            if ($userVip['useGiveNum'] <= $userVip['giveNumLimit']) { // 有可赠送的次数
                if (empty($giveEffectVip)) {
                    $giveEffectVip = $userVip;
                } elseif ($userVip['effectEndTime'] < $giveEffectVip['effectEndTime']) { // 优先使用快到期的
                    $giveEffectVip = $userVip;
                }
            }
            if (!empty($userVip['testPaperNumLimit']) && count($userVip['useTestIds']) < $userVip['testPaperNumLimit']) {
                if (empty($testEffectVip)) {
                    $testEffectVip = $userVip;
                } elseif ($userVip['effectEndTime'] < $testEffectVip['effectEndTime']) { // 优先使用快到期的
                    $testEffectVip = $userVip;
                }
            }
        }
        $vipModel = array(); // vip信息
        $surplusTestPaperNum = 0; // vip 剩余可测试次数
        if (!empty($showVipModel)) {
            if (empty($showVipModel['testPaperNumLimit'])) { // 没有次数限制
                $surplusTestPaperNum = 9999;
                $testEffectVip = array();
            } elseif (!empty($testEffectVip)) { // 有次数限制
                $surplusTestPaperNum = $testEffectVip['testPaperNumLimit'] - count($testEffectVip['useTestIds']);
            }
            
        	$vipModel = $showVipModel;
        	$vipModel['vipGiveNum'] = intval($vipUseGiveNum); // 已赠送次数
        	$vipModel['vipGiveLimit'] = intval($vipGiveLimit); // 赠送次数限制
        	$vipModel['giveEffectVipId'] = empty($giveEffectVip['id']) ? 0 : $giveEffectVip['id'];
        	$vipModel['testEffectVipId'] = empty($testEffectVip['id']) ? 0 : $testEffectVip['id'];
        	$vipModel['surplusTestPaperNum'] = $surplusTestPaperNum;
        }
        $userModel = $userEtt->getModel();
        $appSv = \service\App::singleton();
      
        $userModel['vipInfo'] = $vipModel;
        return array(
        	'userInfo' => $userModel,
        	'configList' => array(),
            'userVipOrderList' => $userVipList,
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