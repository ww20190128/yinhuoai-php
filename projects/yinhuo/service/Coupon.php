<?php
namespace service;

/**
 * 优惠券
 * 
 * @author 
 */
class Coupon extends ServiceBase
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
     * @return Coupon
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Coupon();
        }
        return self::$instance;
    }
    
    /**
     * 创建优惠券
     *
     * @return array
     */
    public function createCoupon($couponId)
    {	
    	$couponConfigDao = \dao\CouponConfig::singleton();
    	$couponConfigEtt = $couponConfigDao->readByPrimary($couponId);
    	if (empty($couponConfigEtt) || $couponConfigEtt->status == \constant\Common::DATA_DELETE) {
    		return array();
    	}
    	$now = $this->frame->now;
    	$privateKey = md5(SHARE_PRIVATE_KEY);
    	$info = array(
    		$now, // 当前时间
    		$couponId, // 测评ID
    	);
    	$encryptInfo = encrypt(json_encode($info), $privateKey); // 加密后的信息
    	$host = empty($this->frame->conf['web_url']) ? array() : $this->frame->conf['web_url'];
    	$url = $host . "/coupon?couponCode=" . $encryptInfo;
    	return $url;
    }
    
    /**
     * 优惠券信息
     *
     * @return array
     */
    public function couponInfo($userCouponEtt, $userEtt = null)
    {
    	$userCouponDao = \dao\UserCoupon::singleton();
    	if (is_numeric($userCouponEtt)) {
    		$userCouponEtt = $userCouponDao->readByPrimary($userCouponEtt);
    	}
    	if (empty($userCouponEtt) || $userCouponEtt->status == \constant\Common::DATA_DELETE) {
    		return array();
    	}
    	if (!empty($userEtt)) {
    	    if (is_numeric($userEtt)) {
    	        $userDao = \dao\User::singleton();
    	        $userEtt = $userDao->readByPrimary($userEtt);
    	        if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    	            throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	        }
    	    }
    	    if ($userCouponEtt->userId != $userEtt->userId) {
    	        return array();
    	    }
    	    $userSv = \service\User::singleton();
    	    $userInfo = $userSv->userInfo($userEtt);
    	    $userInfo = $userInfo['userInfo'];
    	}
    	$couponConfigDao = \dao\CouponConfig::singleton();
    	$couponConfigEtt = $couponConfigDao->readByPrimary($userCouponEtt->couponId);
    	if (empty($couponConfigEtt) || $couponConfigEtt->status == \constant\Common::DATA_DELETE) {
    		return array();
    	}
    	$couponConfigModel = $couponConfigEtt->getModel();
    	$userCouponModel = $userCouponEtt->getModel();

    	$now = $this->frame->now;
    	$status = $userCouponModel['status']; // 0  正常  1  已使用  2  已到期
    	$effectiveEndTime = 0; // 失效时间
    	if (!empty($couponConfigModel['effectiveDay'])) { // 有时间限制
    		$effectiveEndTime = $userCouponEtt->createTime + $couponConfigModel['effectiveDay'] * 86400;
    		if ($effectiveEndTime <= $now && $status != \constant\Coupon::STATUS_USED) {
    			$status = \constant\Coupon::STATUS_OVERDUE; // 已到期
    		}
    	}
    	$userCouponModel['status'] = $status;
    	$userCouponModel['effectiveEndTime'] = $effectiveEndTime;
    	unset($userCouponModel['id']);
    	$testPaperIds = array(); // 测评ID
    	$targetInfos = array();

    	// 测评赠送券, 测评折扣券
    	if (in_array($couponConfigModel['type'], array(
    		\constant\Coupon::TYPE_TEST_PAPER_GIVE,
    		\constant\Coupon::TYPE_TEST_PAPER_DISCOUNT,
    	))) {
    		if (!empty($couponConfigModel['targetIds'])) {
    			$testPaperIds = $couponConfigModel['targetIds'];
    		}
    	} elseif ($couponConfigModel['type'] == \constant\Coupon::TYPE_VIP_DISCOUNT) { // vip 抵扣券
    	    $vipConfigs = array();
    	    $vipSv = \service\Vip::singleton();
    	    $vipConfigList = $vipSv->getConfigList();
    	    if (!empty($couponConfigModel['targetIds'])) {
    	        foreach ($couponConfigModel['targetIds'] as $targetId) {
    	            if (empty($vipConfigList[$targetId])) {
    	                continue;
    	            }
    	            $vipConfigs[$targetId] = $vipConfigList[$targetId];
    	        }
    	    }
    	    $targetInfos = $vipConfigs;
    	} 
    	
    	if (!empty($testPaperIds)) {
    	    $testPaperDao = \dao\TestPaper::singleton();
    	    $testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    	    $testPaperEttList = $testPaperDao->refactorListByKey($testPaperEttList);
    	    if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $testPaperEtt) {
    	        $targetModel = $testPaperEtt->getModel();
    	        $targetInfos[$targetModel['id']] = $targetModel;
    	    }
    	}
    	$couponConfigModel['targetIds'] = empty($targetInfos) ? array() : array_keys($targetInfos);
    	$userCouponModel['targetInfos'] = array_values($targetInfos);
    	
    	$result = array_merge($couponConfigModel, $userCouponModel);
    	$result['vipInfo'] = empty($userInfo['vipInfo']) ? array() : $userInfo['vipInfo'];
    	return $result;
    	
    }

    /**
     * 领取优惠券
     *
     * @return array
     */
    public function receive($couponCode, $userId = 0)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$privateKey = md5(SHARE_PRIVATE_KEY);
    	$couponInfo = decrypt($couponCode, $privateKey); // 解密信息
    	$couponInfo = empty($couponInfo) ? array() : json_decode($couponInfo, true);
    	if (empty($couponInfo['1'])) {
    		return array(
    			'receiveStatus' => -1, // 领取失败
    		);
    	}
    	$couponConfigDao = \dao\CouponConfig::singleton();
    	$couponConfigEtt = $couponConfigDao->readByPrimary($couponInfo['1']);
    	if (empty($couponConfigEtt) || $couponConfigEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('优惠券已失效!');
    	}
    	$userCouponDao = \dao\UserCoupon::singleton();
    	$userCouponEttList = $userCouponDao->readListByIndex(array(
    		'userId' => $userEtt->userId,
    	));
    	if (is_iteratable($userCouponEttList)) foreach ($userCouponEttList as $userCouponEtt) {
    		if ($userCouponEtt->couponId == $couponInfo['1']) {
    			return array(
    				'receiveStatus' => 2, // 已领取过	
    			);
    		}
    	}
    	if (!empty($couponConfigEtt->limitNum)) {
    		$userCouponEttList = $userCouponDao->readListByWhere("`couponId`={$couponConfigEtt->id}");
    		if (count($userCouponEttList) >= $couponConfigEtt->limitNum) {
    			return array(
    				'receiveStatus' => -1, // 领取失败
    			);
    		}
    	}
    	
 		// 领取
    	$now = $this->frame->now;
    	$userCouponDao = \dao\UserCoupon::singleton();
    	$userCouponEtt = $userCouponDao->getNewEntity();
    	$userCouponEtt->userId = $userId;
    	$userCouponEtt->couponId = $couponConfigEtt->id;
    	$userCouponEtt->createTime = $now;
    	$userCouponEtt->updateTime = $now;
    	$userCouponDao->create($userCouponEtt);
        return array(
    		'receiveStatus' => 1, // 领取成功
    	);
    }
    
    /**
     * 优惠券
     *
     * @return array
     */
    public function couponList($userEtt, $type = 1, $pageNum = 1, $pageLimit = 20)
    {
    	if (is_numeric($userEtt)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userEtt);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			return array();
    		}
    	}
    	$userCouponDao = \dao\UserCoupon::singleton();
    	$userCouponEttList = $userCouponDao->readListByIndex(array(
    		'userId' => $userEtt->userId,
    	));
    	if (empty($userCouponEttList)) {
    		return array();
    	}
    	$couponConfigDao = \dao\CouponConfig::singleton();
    	$couponConfigEttList = $couponConfigDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$couponConfigEttList = $couponConfigDao->refactorListByKey($couponConfigEttList);
    	$now = $this->frame->now;
    	$modelList = array();
    	$testPaperIds = array(); // 测评ID
    	$giveIds = array(); // 赠送ID
    	$vipSv = \service\Vip::singleton();
    	$vipConfigList = $vipSv->getConfigList();
    	$typeMap = array(); // 不同类型优惠券的数量
    	if (is_iteratable($userCouponEttList)) foreach ($userCouponEttList as $userCouponEtt) {
    		$userCouponModel = $userCouponEtt->getModel();
    		$status = intval($userCouponEtt->status); // 0 正常  1 已使用  2 已到期
    		if ($status == \constant\Common::DATA_DELETE) { // 已删除
    			continue;
    		}
    		if (empty($couponConfigEttList[$userCouponEtt->couponId])) { // 优惠券配置信息已删除
    			continue;
    		}
    		// 优惠券配置
    		$couponConfigEtt = $couponConfigEttList[$userCouponEtt->couponId];
    		$effectiveEndTime = 0; // 失效时间
    		if (!empty($couponConfigEtt->effectiveDay)) { // 有时间限制
    			$effectiveEndTime = $userCouponEtt->createTime + $couponConfigEtt->effectiveDay * 86400;
    			if ($effectiveEndTime <= $now && $status != \constant\Coupon::STATUS_USED) {
    				$status = \constant\Coupon::STATUS_OVERDUE; // 已到期
    				if ($now - $effectiveEndTime >= 3 * 86400) { // 删除3天前到期的优惠券
    					$userCouponDao->remove($userCouponEtt);
    					continue;
    				}
    			}
    		}
    
    		$userCouponModel['status'] = $status;
    		$userCouponModel['effectiveEndTime'] = $effectiveEndTime;
    		
    		if (empty($typeMap[$couponConfigEtt->type])) {
    			$typeMap[$couponConfigEtt->type] = 1;
    		} else {
    			$typeMap[$couponConfigEtt->type] ++;
    		}
    		if (!empty($type) && $couponConfigEtt->type != $type) {
    			continue;
    		}
    		
    		$couponConfigModel = $couponConfigEtt->getModel();
    		// 测评赠送券, 测评折扣券
    		if (in_array($couponConfigModel['type'], array(
    			\constant\Coupon::TYPE_TEST_PAPER_GIVE,
    			\constant\Coupon::TYPE_TEST_PAPER_DISCOUNT,
    		))) {
    			if (!empty($couponConfigModel['targetIds'])) {
    				$testPaperIds = array_merge($testPaperIds, $couponConfigModel['targetIds']);
    			}
    		} elseif ($couponConfigModel['type'] == \constant\Coupon::TYPE_VIP_DISCOUNT) { // vip 抵扣券
    			$vipConfigs = array();
    			if (!empty($couponConfigModel['targetIds'])) {
    				foreach ($couponConfigModel['targetIds'] as $targetId) {
    					if (empty($vipConfigList[$targetId])) {
    						continue;
    					}
    					$vipConfigs[] = $vipConfigList[$targetId];
    				}
    			}
    			$couponConfigModel['targetInfos'] = $vipConfigs;
    		}
    		$modelList[] = array_merge($couponConfigModel, $userCouponModel);
    	}
    	
    	$testQuestionMap = array();
    	$testPaperEttList = array();
    	if (!empty($testPaperIds)) {
    		$testPaperDao = \dao\TestPaper::singleton();
    		$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    		$testPaperEttList = $testPaperDao->refactorListByKey($testPaperEttList);
    	}
    	if (is_iteratable($modelList)) foreach ($modelList as $key => $model) {
    		$targetInfos = array();
    		// 测评赠送券, 测评折扣券
    		if (in_array($model['type'], array(
    			\constant\Coupon::TYPE_TEST_PAPER_GIVE,
    			\constant\Coupon::TYPE_TEST_PAPER_DISCOUNT,
    		))) {
    			if (is_iteratable($model['targetIds']) )foreach ($model['targetIds'] as $targetId) {
    				if (empty($testPaperEttList[$targetId])) {
    					continue;
    				}
    				$targetModel = $testPaperEttList[$targetId]->getModel();
    				$targetInfos[] = $targetModel;
    			}
    		}
    		$modelList[$key]['targetInfos'] = $targetInfos;
    	}
    	// 排序  待使用放前面  后创建的放前面
    	$commonSv = \service\Common::singleton();
    	uasort($modelList, array($commonSv, 'sortByCreateTime'));
    	// 符合条件的总条数
    	$totalNum = count($modelList);
    	$typeMap['0'] = array_sum($typeMap);
    	$typeNumList = array();
    	if (is_iteratable($typeMap)) foreach ($typeMap as $type => $num) {
    	    $typeNumList[] = array(
    	        'type' =>  intval($type),
    	        'num' => $num,
    	    );
    	}
    	// 分页显示
    	if ($pageNum > 0) {
    		$modelList = array_slice($modelList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	}
    	return array(
    	    'list' => array_values($modelList),
    	    'typeNumList' => array_values($typeNumList),
    	);
    }
    
    /**
     * 获取分类列表
     *
     * @return array
     */
    public function getListByCouponId($couponId, $pageNum = 1, $pageLimit = 20)
    {
    	$couponInfo = $this->couponInfo($couponId);
    	// 优惠券已使用 或已到期
    	if (empty($couponInfo) || in_array($couponInfo['status'], array(
    		\constant\Coupon::STATUS_USED,
    		\constant\Coupon::STATUS_OVERDUE
    	)) || $couponInfo['type'] == \constant\Coupon::TYPE_VIP_DISCOUNT) {
    		return array(
    			'totalNum' => 0,
    			'list' => array(),
    		);
    	} 
    	$modelList = array();
    	$testPaperDao = \dao\TestPaper::singleton();
    	if (empty($couponInfo['targetInfos'])) { // 没有指定，获取非免费的
    		$testPaperEttList = $testPaperDao->readListByIndex(array(
    			'status' => 0,
    		));
    		if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $key => $testPaperEtt) {
    			if (empty($testPaperEtt->price)) { // 删除免费的
    				unset($testPaperEttList[$key]);
    				continue;
    			}
    			$modelList[] = $testPaperEtt->getModel();
    		}
    	} else {
    		$modelList = $couponInfo['targetInfos'];
    	}
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEttList = $testOrderDao->readListByIndex(array(
    		'userId' => $couponInfo['userId'],
    	));
    	$testOrderMap = array();
    	if (is_iteratable($testOrderEttList)) foreach ($testOrderEttList as $key => $testOrderEtt) {
    		$testOrderMap[$testOrderEtt->testPaperId] = $testOrderEtt->status;
    	}
    	$testList = array(); // 已测试
    	$unTestList = array(); // 未测试
    	if (is_iteratable($modelList)) foreach ($modelList as $key => $model) {
    		if (isset($testOrderMap[$model['id']])) {
    			$testList[$model['id']] = $model;
    		} else {
    			$unTestList[$model['id']] = $model;
    		}
    	}
    	// 根据热度排序
    	uasort($testList, array(self::$instance, 'sortBySaleNum'));
    	uasort($unTestList, array(self::$instance, 'sortBySaleNum'));
    	$modelList = $unTestList + $testList;
    	 
    	// 符合条件的总条数
    	$totalNum = count($modelList);
    	// 分页显示
    	if ($pageNum > 0) {
    		$modelList = array_slice($modelList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	}
    	$testPaperIds = array();
    	if (is_iteratable($modelList)) foreach ($modelList as $model) {
    		$testPaperIds[] = intval($model['id']);
    	}
    	if (is_iteratable($modelList)) foreach ($modelList as $key => $model) {
    		// 赋值价格
    		$price = $model['price'];
    		$discountValue = 0; // 抵扣的金额
    		if ($couponInfo['type'] == \constant\Coupon::TYPE_CASH_DEDUCTION) { // 现金抵扣
    			$discountValue = $couponInfo['value'];
    		} elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_GIVE) { // 测评赠送
    			$discountValue = $price;
    		} elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_DISCOUNT) { // 测评折扣
    			$discountValue = $price * (100 - $couponInfo['value']) * 0.01;
    		} else {
    			throw new $this->exception('优惠券不可用');
    		}
    		// 优惠券折扣信息
    		$price = max(0, $price - $discountValue);
    		$model['discountValue'] = $discountValue; // 折扣值
    		$model['newPrice'] = $price; // 折扣值
    		$modelList[$key] = $model;
    	}
    	return array(
    		'totalNum' => intval($totalNum),
    		'list' => array_values($modelList),
    		'couponInfo' => $couponInfo,
    	);
    }   
    
}