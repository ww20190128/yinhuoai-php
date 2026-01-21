<?php
namespace service;

/**
 * 赠送
 * 
 * @author 
 */
class Give extends ServiceBase
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
     * @return Give
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Give();
        }
        return self::$instance;
    }

    /**
     * 赠送
     *
     * @return array
     */
    public function give($userId, $testPaperId)
    {
    	$userSv = \service\User::singleton();
    	$userInfo = $userSv->userInfo($userId);
    	$vipInfo = empty($userInfo['userInfo']['vipInfo']) ? array() : $userInfo['userInfo']['vipInfo'];
    	if (empty($vipInfo)) {
    		throw new $this->exception('vip已到期，请购买VIP！');
    	}
    	// 检查赠送次数
    	if ($vipInfo['vipGiveNum'] >= $vipInfo['vipGiveLimit'] || empty($vipInfo['giveEffectVipId'])) {
    		throw new $this->exception('赠送次数已用完，请购买VIP！');
    	}
    	// 获取测评信息
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperInfo = $testPaperSv->testPaperInfo($testPaperId);
    	// 扣除赠送次数
    	$userVipDao = \dao\UserVip::singleton();
    	$userVipEtt = $userVipDao->readByPrimary($vipInfo['giveEffectVipId']);
    	if (empty($userVipEtt)) {
    		throw new $this->exception('赠送次数已用完，请购买VIP！');
    	}
    	$now = $this->frame->now;
    	$userVipEtt->add('useGiveNum', 1);
    	$userVipEtt->set('updateTime', $now);
    	$userVipDao->update($userVipEtt);
    
    	$userGiveDao = \dao\UserGive::singleton();
    	$userGiveEtt = $userGiveDao->getNewEntity();
    	
    	$userGiveEtt->userId 		= $userId;
    	$userGiveEtt->drawUserId 	= 0;
    	$userGiveEtt->drawTime 		= 0;
    	$userGiveEtt->testPaperId 	= $testPaperId;
    	$userGiveEtt->status 		= \constant\Give::STATUS_NORMAL; // 待领取
    	$userGiveEtt->createTime 	= $now;
    	$userGiveEtt->updateTime 	= $now;
    	$giveId = $userGiveDao->create($userGiveEtt);
    	return array(
    		'giveId' => intval($giveId)
    	);
    }
    
    /**
     * 测评赠送信息
     *
     * @return array
     */
    public function giveInfo($userGiveEtt, $userEtt = null)
    {
        $userGiveDao = \dao\UserGive::singleton();
    	if (is_numeric($userGiveEtt)) {
    		$userGiveEtt = $userGiveDao->readByPrimary($userGiveEtt);
    	}
    	if (empty($userGiveEtt) || $userGiveEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('赠送已失效！');
    	}
    	$userDao = \dao\User::singleton();
    	if (!empty($userEtt)) {
    		if (is_numeric($userEtt)) {
    			$userEtt = $userDao->readByPrimary($userEtt);
    		}
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    	}
    	// 获取测评信息
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperEtt = $testPaperDao->readByPrimary($userGiveEtt->testPaperId);
    	if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('测评已删除');
    	}
    	
    	$testOrderInfo = array(); // 测试订单信息
    	if ($userGiveEtt->status != \constant\Give::STATUS_NORMAL) {
    	   $testOrderDao = \dao\TestOrder::singleton();
    	   $testOrderEttList = $testOrderDao->getTestOrderByDiscount(\constant\Order::DISCOUNT_TYPE_GIVE, array($userGiveEtt->id));
    	   
    	   if (!empty($testOrderEttList)) {
    	       $testOrderInfo = reset($testOrderEttList)->getModel();
    	       // 更新状态
    	       if (!empty($testOrderInfo['testCompleteTime']) && $userGiveEtt->status != \constant\Give::STATUS_USED) {
    	           $userGiveEtt->set('status', \constant\Give::STATUS_USED);
    	           $userGiveEtt->set('updateTime', $testOrderInfo['testCompleteTime']);
    	           $userGiveDao->update($userGiveEtt);
    	       }
    	   }
    	}
    	
    	$isGiveUser = 0; // 是否为赠送人
    	$isDrawUser = 0; // 是否为接受人
    	$userSv = \service\User::singleton();
    	$giveUserInfo = $userSv->userInfo($userGiveEtt->userId); // 赠送者信息
    	$giveUserInfo = empty($giveUserInfo['userInfo']) ? array() : $giveUserInfo['userInfo'];
    	
    	$drawUserInfo = array();
    	if (!empty($userGiveEtt->drawUserId)) {
    	    $drawUserInfo = $userSv->userInfo($userGiveEtt->drawUserId);
    		$drawUserInfo = empty($drawUserInfo['userInfo']) ? array() : $drawUserInfo['userInfo'];
    	}
    	if (!empty($userEtt) && !empty($giveUserInfo) && $giveUserInfo['userId'] == $userEtt->userId) {
    		$isGiveUser = 1;
    	} elseif (!empty($userEtt) && !empty($drawUserInfo) && $drawUserInfo['userId'] == $userEtt->userId) {
    		$isDrawUser = 1;
    	}

    	$model = array(
    		'isGiveUser'    => $isGiveUser, // 是否为赠送人
    		'isDrawUser'    => $isDrawUser, // 是否为接受人
    		'id'            => intval($userGiveEtt->id),
    		'createTime' 	=> intval($userGiveEtt->createTime),
    		'testPaperInfo' => $testPaperEtt->getModel(),
    	    'testOrderInfo' => $testOrderInfo,
    		'drawUserInfo' 	=> $drawUserInfo,
    		'giveUserInfo' 	=> $giveUserInfo,
    		'drawTime' 		=> intval($userGiveEtt->drawTime), // 领取时间
    	    'status'        => intval($userGiveEtt->status),
    	);
    	return $model;
    }
    
    /**
     * 领取赠送
     *
     * @return array
     */
    public function draw($userId, $giveId)
    {
        $userDao = \dao\User::singleton();
        $userEtt = $userDao->readByPrimary($userId);
        if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
        }
    	// 赠送信息
        $userGiveDao = \dao\UserGive::singleton();
        $userGiveEtt = $userGiveDao->readByPrimary($giveId);
        if (empty($userGiveEtt) || $userGiveEtt->status == \constant\Common::DATA_DELETE) {
        	throw new $this->exception('赠送已失效！');
        }
        $giveInfo = $this->giveInfo($userGiveEtt, $userEtt);
    	if ($giveInfo['status'] != \constant\Give::STATUS_NORMAL) {
    		if ($giveInfo['status'] == \constant\Give::STATUS_USED) {
    			throw new $this->exception('赠送已领取并使用！');
    		}
    		if ($giveInfo['status'] == \constant\Give::STATUS_DRAWED) {
	    		if (!empty($giveInfo['drawUserInfo']) && $giveInfo['drawUserInfo']['userId'] == $userId) {
	    			throw new $this->exception('赠送已领取！');
	    		} else {
	    			throw new $this->exception("赠送已被 {$giveInfo['drawUserInfo']['userName']} 领取！");
	    		}
    		}
    	}
    	// 更新领取状态
    	$now = $this->frame->now;
    	$userGiveEtt->set('drawUserId', $userId);
    	$userGiveEtt->set('drawTime', $now);
    	$userGiveEtt->set('status', \constant\Give::STATUS_DRAWED); // 已领取
    	$userGiveEtt->set('updateTime', $now);
    	$userGiveDao->update($userGiveEtt);
    	return array(
    		'result' => 1
    	);
    }
    
    /**
     * 获取赠送记录
     *
     * @return array
     */
    public function giveList($userId, $type = 1, $pageNum = 1, $pageLimit = 2000)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		return array();
    	}
    	$userGiveDao = \dao\UserGive::singleton();
    	if ($type == 1) { // 获取赠送
    		$userGiveEttList = $userGiveDao->readListByIndex(array(
    			'userId' => $userId,
    		));
    	} else { // 获取领取
    		$userGiveEttList = $userGiveDao->readListByIndex(array(
    			'drawUserId' => $userId,
    		));
    	}
    	if (empty($userGiveEttList)) {
    		return array();
    	}
    	$testPaperIds = array();
    	$userIds = array();
    	$giveIds = array();
    	if (is_iteratable($userGiveEttList)) foreach ($userGiveEttList as $key => $userGiveEtt) {
    		if ($userGiveEtt->status == \constant\Common::DATA_DELETE) {
    			unset($userGiveEttList[$key]);
    			continue;
    		}
    		$giveIds[] = intval($userGiveEtt->id);
    		$testPaperIds[] = intval($userGiveEtt->testPaperId);
    		$userIds[] = intval($userGiveEtt->drawUserId);
    		$userIds[] = intval($userGiveEtt->userId);
    	}
    	$userIds = array_unique($userIds);
    	$userDao = \dao\User::singleton();
    	$userModels = array();
    	if (!empty($userIds)) {
    		$userEttList = $userDao->readListByPrimary($userIds);
    		if (is_iteratable($userEttList)) foreach ($userEttList as $userEtt) {
    			$userModels[$userEtt->userId] = $userEtt->getModel();
    		}
    	}
    	$testPaperIds = array_unique($testPaperIds);
    	$testPaperDao = \dao\TestPaper::singleton();
    	
    	$testPaperModels = array();
    	if (!empty($testPaperIds)) {
    		$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    		if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $testPaperEtt) {
    			$testPaperModel = $testPaperEtt->getModel();
    			$testPaperModels[$testPaperModel['id']] = $testPaperModel;
    		}
    	}
    	// 测试单号
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEttList = $testOrderDao->getTestOrderByDiscount(\constant\Order::DISCOUNT_TYPE_GIVE, $giveIds);
    	if (empty($testPaperModels)) {
    	    return array();
    	}
    	$modelList = array();
    	if (is_iteratable($userGiveEttList)) foreach ($userGiveEttList as $userGiveEtt) {
    		$testPaperInfo = empty($testPaperModels[$userGiveEtt->testPaperId])
    		  ? array() : $testPaperModels[$userGiveEtt->testPaperId];
    		$drawUserInfo = empty($userModels[$userGiveEtt->drawUserId])
    		  ? array() : $userModels[$userGiveEtt->drawUserId];
    		$giveUserInfo = empty($userModels[$userGiveEtt->userId])
    		  ? array() : $userModels[$userGiveEtt->userId];
    	    $testOrderInfo = array(); // 测试订单信息
    		// 更新订单完成状态
    		if (!empty($testOrderEttList[$userGiveEtt->id])) {
    	       if (!empty($testOrderEttList[$userGiveEtt->id]->testCompleteTime)) {
    		      if ($userGiveEtt->status != \constant\Give::STATUS_USED) {
    		          $userGiveEtt->set('status', \constant\Give::STATUS_USED);
    		          $userGiveEtt->set('updateTime', $testOrderEttList[$userGiveEtt->id]->testCompleteTime);
    		          $userGiveDao->update($userGiveEtt);
    		      }
    		   }
    		   $testOrderInfo = $testOrderEttList[$userGiveEtt->id]->getModel();
    	    }
    		$model = array(
    			'id'            => intval($userGiveEtt->id),
    		    'createTime' 	=> intval($userGiveEtt->createTime),
    		    'testOrderInfo' => $testOrderInfo,
    			'testPaperInfo' => $testPaperInfo,
    			'drawUserInfo' 	=> $drawUserInfo,
    			'giveUserInfo' 	=> $giveUserInfo,
    		    'status'        => intval($userGiveEtt->status),
    			'drawTime' 		=> intval($userGiveEtt->drawTime), // 领取时间
    		);
    		$modelList[$userGiveEtt->id] = $model;
    	}
    	// 排序  待领取放前面  后创建的放前面
    	uasort($modelList, array(self::$instance, 'sortByStatus'));
    	// 符合条件的总条数
    	$totalNum = count($modelList);
    	// 分页显示
    	if ($pageNum > 0) {
    		$modelList = array_slice($modelList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	}
    	return $modelList;
    }
    
}