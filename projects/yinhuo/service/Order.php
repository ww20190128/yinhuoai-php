<?php
namespace service;

/**
 * 订单 逻辑类
 * 
 * @author 
 */
class Order extends ServiceBase
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
     * @return Order
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Order();
        }
        return self::$instance;
    }

    /**
     * 创建vip支付订单
     *
     * @return array
     */
    public function createVipOrder($userId, $vipId, $deviceInfo, $info = array())
    {
    	$userEtt = null;
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    	}
        $vipConfigDao = \dao\VipConfig::singleton();
        $vipConfigEtt = $vipConfigDao->readByPrimary($vipId);
        if (empty($vipConfigEtt) || $vipConfigEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
        }
        $userSv = \service\User::singleton();
        $userInfo = $userSv->userInfo($userEtt);
        if (!empty($userInfo['vipInfo']['effectDay'])) {
        	throw new $this->exception('已购买VIP，不用重复购买');
        }
        $basePrice = $vipConfigEtt->price; // 原始价格
        // 优惠券信息
        $couponInfo = array();
        $userCouponEtt = null;
        $discountValue = 0; // 折扣的金额
        if (!empty($info['couponId'])) { // 有优惠券
        	$userCouponDao = \dao\UserCoupon::singleton();
        	$userCouponEtt = $userCouponDao->readByPrimary($info['couponId']);
        	if (empty($userCouponEtt) || $userCouponEtt->status == \constant\Common::DATA_DELETE) {
        		throw new $this->exception('优惠券不可用');
        	}
            $couponSv = \service\Coupon::singleton();
            $couponInfo = $couponSv->couponInfo($userCouponEtt, $userEtt);
            if (!empty($couponInfo) && $couponInfo['status'] == \constant\Coupon::STATUS_NORMAL) { // 正常
                if ($couponInfo['type'] == \constant\Coupon::TYPE_CASH_DEDUCTION) { // 现金抵扣
                    $discountValue = $couponInfo['value'];
                } elseif ($couponInfo['type'] == \constant\Coupon::TYPE_VIP_DISCOUNT) { // vip折扣券
                    $discountValue = $basePrice * (100 - $couponInfo['value']) * 0.01;
                } else {
                    throw new $this->exception('优惠券不可用');
                }
                // 验证目标
                if (!empty($couponInfo['targetInfos'])) {
                    if (!in_array($vipConfigEtt->id, array_column($couponInfo['targetInfos'], 'id'))) {
                        throw new $this->exception('优惠券不可用');
                    }
                }
                // 优惠券折扣信息
                $couponInfo['discountValue'] = $discountValue; // 折扣值
                $couponInfo['newPrice'] = max(0, $basePrice - $discountValue); // 折扣后的价格
         
            } else { // 失效，或已使用
                $couponInfo = array();
            }
        }
        $price = max(0, $basePrice - $discountValue);
        $now = $this->frame->now;
        // 创建订单
        $orderDao = \dao\Order::singleton();
        $orderEtt = $orderDao->getNewEntity();
        $orderEtt->goodsType = \constant\Order::TYPE_GOODS_VIP; // 购买vip
        $orderEtt->goodsId = $vipId;
        $orderEtt->userId = $userId;
        $orderEtt->status = \constant\Order::PAY_STATUS_DURING; // 未支付
        $orderEtt->price = $price; // 需要支付的金额
        $orderEtt->updateTime = $now;
        $orderEtt->createTime = $now;
        $orderEtt->outTradeNo = ''; // 订单号为空
        $orderEtt->tradeInfo = ''; // 交易信息为空
		$orderId = $orderDao->create($orderEtt);
		if (!empty($couponId) && !empty($userCouponEtt)) { // 更改优惠券
			$userCouponEtt->set('orderId', $orderId);
			$userCouponEtt->set('updateTime', $now);
			$userCouponDao->update($userCouponEtt);
		}
        return array(
            'orderId' => intval($orderId), // 订单ID
        );
    }
    
    /**
     * 生成交易订单号
     *
     * @return array
     */
    private static function createOutTradeNo($userId, $orderId)
    {
    	$now = self::$instance->frame->now;
    	$out_trade_no = 'X-' . date('YmdHis', $now) . $userId . rand(10, 99) . $orderId . rand(10, 99);
    	return $out_trade_no;
    }
    
    /**
     * vip订单支付（只操作不执行，在回调中执行）
     *
     * @return array
     */
    public function vipOrderPay($userId, $orderId, $couponId = 0)
    {
    	$userEtt = null;
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    		$userSv = \service\User::singleton();
    		$userInfo = $userSv->userInfo($userEtt);
    	}

    	$orderDao = \dao\Order::singleton();
    	$orderEtt = $orderDao->readByPrimary($orderId);
    	$now = $this->frame->now;
    	if (empty($orderEtt) || $orderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除，请重新下单！');
    	}
    	if ($orderEtt->goodsType != \constant\Order::TYPE_GOODS_VIP) {
    		throw new $this->exception('订单类型错误，请重新下单！');
    	} elseif ($orderEtt->status == \constant\Order::PAY_STATUS_COMPLETE) {
    		throw new $this->exception('订单已支付，无需重复支付！');
    	} elseif ($orderEtt->status == \constant\Order::PAY_STATUS_PAST_DUE) {
    		throw new $this->exception('订单已逾期，请重新下单！');
    	}
    	$vipConfigDao = \dao\VipConfig::singleton();
    	$vipConfigEtt = $vipConfigDao->readByPrimary($orderEtt->goodsId);
    	if (empty($vipConfigEtt) || $vipConfigEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除，请重新下单！');
    	}
    	// 有优惠券，验证优惠券，并将优惠券绑定到订单上
    	if (!empty($couponId)) {
    		$userCouponDao = \dao\UserCoupon::singleton();
    		$userCouponEtt = $userCouponDao->readByPrimary($couponId);
    		if (!empty($userCouponEtt) && $userCouponEtt->userId == $orderEtt->userId) {
    			$orderEtt->set('couponId', $couponId); // 将优惠券绑定到订单上
    		}
    	}
    	
    	if ($orderEtt->price <= 0) {
    		// 预支付成功
    		$orderEtt->set('updateTime', $now);
    		$orderDao->update($orderEtt);
    		if (!empty($orderEtt)) { // 有支付订单
    			$this->finishOrder($orderEtt, array(), \constant\Order::PAY_STATUS_NO_NEED_PAY); // 完结订单
    		}
    		return array(
    			'noNeedPay' => 1,
    		);
    	}

    	// ！！！！开始预支付
    	// 判断是否需要分账
    	$needProfitSharing = false;
    	$profitSharingEtt1 = null;
    	$profitSharingEtt2 = null;
    	$profitSharingDao = \dao\ProfitSharing::singleton();
    	$paySv = \service\Pay::singleton();
    	if (!empty($userEtt->parentUserId)) { // 有分账
    		$parentUserEtt = $userDao->readByPrimary($userEtt->parentUserId);
    		if (!empty($parentUserEtt) && $parentUserEtt->status != \constant\Common::DATA_DELETE) {
    			$needProfitSharing = true;
    		}
    		$profitSharingEtt1 = $profitSharingDao->getNewEntity();
    		$profitSharingEtt1->userId = $parentUserEtt->userId;
    		$profitSharingEtt1->orderId = $orderEtt->id;
    		$profitSharingEtt1->status = 0;
    		$profitSharingEtt1->addGold = intval($vipConfigEtt->topGold1);
    		$profitSharingEtt1->currentGold = intval($parentUserEtt->gold);
    		$profitSharingEtt1->parentUserId = intval($parentUserEtt->parentUserId);
    		$profitSharingEtt1->fromUserId = $userEtt->userId;
    		$profitSharingEtt1->receiverAddOpenId = '';
    		$profitSharingEtt1->updateTime = $now;
    		$profitSharingEtt1->createTime = $now;
    		// 查询是否添加过收账关系
    		$where = "`receiverAddOpenId` = '{$parentUserEtt->openid}'";
    		$haveProfitSharingEtt = $profitSharingDao->readListByWhere($where);
    		if (empty($haveProfitSharingEtt)) {
    			$receiverAddOpenId = $paySv->profitsharingReceiversAdd($parentUserEtt->openid);
    			if (!empty($receiverAddOpenId)) {
    				$profitSharingEtt1->receiverAddOpenId = $receiverAddOpenId;
    			}
    		} else {
    			$profitSharingEtt1->receiverAddOpenId = $parentUserEtt->openid;
    		}
    		// 有上上级
    		if (!empty($parentUserEtt->parentUserId)) {
    			$parentUserEtt2 = $userDao->readByPrimary($parentUserEtt->parentUserId);
    			if (!empty($parentUserEtt2) && $parentUserEtt2->status != \constant\Common::DATA_DELETE) {
    				$needProfitSharing = true;
    			}
    			if (!empty($parentUserEtt2->parentUserId)) { // 不允许有3级分账
    				$needProfitSharing = false;
    			}
    			$profitSharingEtt2 = $profitSharingDao->getNewEntity();
    			$profitSharingEtt2->userId = intval($parentUserEtt2->userId);
    			$profitSharingEtt2->orderId = $orderEtt->id;
    			$profitSharingEtt2->status = 0;
    			$profitSharingEtt2->addGold = intval($vipConfigEtt->topGold2);
    			$profitSharingEtt2->currentGold = intval($parentUserEtt2->gold);
    			$profitSharingEtt2->parentUserId = intval($parentUserEtt2->parentUserId);
    			$profitSharingEtt2->fromUserId = $parentUserEtt->userId;
    			$profitSharingEtt2->receiverAddOpenId = '';
    			// 查询是否添加过收账关系
    			$where = "`receiverAddOpenId` = '{$parentUserEtt->openid}'";
    			$haveProfitSharingEtt = $profitSharingDao->readListByWhere($where);
    			if (empty($haveProfitSharingEtt)) {
    				$receiverAddOpenId = $paySv->profitsharingReceiversAdd($parentUserEtt2->openid);
    				if (!empty($receiverAddOpenId)) {
    					$profitSharingEtt2->receiverAddOpenId = $receiverAddOpenId;
    				}
    			} else {
    				$profitSharingEtt2->receiverAddOpenId = $parentUserEtt2->openid;
    			}
    			$profitSharingEtt2->updateTime = $now;
    			$profitSharingEtt2->createTime = $now;
    		}
    	}
    	if (!empty($needProfitSharing)) {
    		if (!empty($profitSharingEtt1)) {
    			$profitSharingDao->create($profitSharingEtt1);
    		}
    		if (!empty($profitSharingEtt2)) {
    			$profitSharingDao->create($profitSharingEtt2);
    		}
    	}
    	// 生成订单号
    	$outTradeNo = self::createOutTradeNo($userId, $orderEtt->id);
    	$orderEtt->set('outTradeNo', $outTradeNo);
    	// 执行微信支付
    	$paySv = \service\Pay::singleton();
    	$payResult = $paySv->prepare($userEtt, $orderEtt, $vipConfigEtt->name, $needProfitSharing);
    	if (empty($payResult)) {
    		throw new $this->exception('支付失败，请重新下单！');
    	}
   		// 预支付成功
    	$orderEtt->set('updateTime', $now);
    	$orderDao->update($orderEtt);
    	return array(
    		'info' => $payResult,
    	);
    }
    
    /**
     * 完结订单
     *
     * @return array
     */
    public function finishOrder($orderEtt, $tradeInfo = array(), $newStatus = '')
    {
    	$now = $this->frame->now;
    	$orderDao = \dao\Order::singleton();
    	if (!empty($newStatus)) { // 更正支付状态
    		$orderEtt->set('status', $newStatus); // 完成支付
    	}
    	$orderEtt->set('updateTime', $now);
    	$orderEtt->set('tradeInfo', json_encode($tradeInfo));
    	$orderDao->update($orderEtt);
    	if (!empty($orderEtt->couponId)) { // 消耗优惠券
    		$userCouponDao = \dao\UserCoupon::singleton();
    		$userCouponEtt = $userCouponDao->readByPrimary($orderEtt->couponId);
    		if (!empty($userCouponEtt)) {
    			$userCouponEtt->set('status', \constant\Coupon::STATUS_USED); // 已使用
    			$userCouponEtt->set('updateTime', $now);
    			$userCouponDao->update($userCouponEtt);
    		}
    	}
    	if ($orderEtt->goodsType == \constant\Order::TYPE_GOODS_VIP) { // 购买vip，创建vip信息
    		$userVipDao = \dao\UserVip::singleton();
    		$userVipEtt = $userVipDao->getNewEntity();
    		$userVipEtt->vipId = $orderEtt->goodsId;
    		$userVipEtt->userId = $orderEtt->userId;
    		$userVipEtt->useGiveNum = 0;
    		$userVipEtt->status = 0;
    		$userVipEtt->effectTime = $now; // vip生效时间
    		$userVipEtt->createTime = $now;
    		$userVipEtt->updateTime = $now;
    		$userVipDao->create($userVipEtt);
    	} 
    	
    	return true;
    }

    /**
     * 检查vip订单是否需要支付
     *
     * @return array
     */
    public function checkVipOrderPay($userId, $orderId)
    {
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    	}
    	
    	$orderDao = \dao\Order::singleton();
    	$orderEtt = $orderDao->readByPrimary($orderId);
    	$now = $this->frame->now;
    	if (empty($orderEtt) || $orderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除，请重新下单！');
    	}
    	if ($orderEtt->goodsType != \constant\Order::TYPE_GOODS_VIP) {
    		throw new $this->exception('订单类型错误');
    	} 
    	$price = $orderEtt->price;
    	$needPay = true; // 是否需要支付  1 需要 0 不需要
    	// 金额为0 无需支付
    	if ($price <= 0 || $orderEtt->status == \constant\Order::PAY_STATUS_COMPLETE) {
    		$needPay = false;
    	}
    	return array(
    		'needPay' => empty($needPay) ? 0 : 1, // 是否需要支付
    	);
    }
    
    /**
     * 获取订单列表
     *
     * @return array
     */
    public function getOrderList($userId, $info, $pageNum, $pageLimit)
    {
    	$orderDao = \dao\Order::singleton();
    	$dataList = $orderDao->getList($info, $pageNum, $pageLimit);
    	$userIds = array_column($dataList, 'userId');
    	$orderIds = array_column($dataList, 'id');
    	
    	$profitSharingDao = \dao\ProfitSharing::singleton();
    	$profitSharingEttList = $profitSharingDao->getListByOrderIds($orderIds);
    	$parentUserIds = array_column($profitSharingEttList, 'parentUserId');
    	$profitSharingUserIds = array_column($profitSharingEttList, 'userId');
    	$userSv = \service\User::singleton();
    	$userModels = $userSv->getUserModels(array_merge($parentUserIds, $profitSharingUserIds, $userIds));
    	
    	$profitSharingModels = array();
    	foreach ($profitSharingEttList as $profitSharingEtt) {
    		$profitSharingModels[$profitSharingEtt->orderId][$profitSharingEtt->id] = array(
    			'id' => intval($profitSharingEtt->id),
    			'receiverAddOpenId' => $profitSharingEtt->receiverAddOpenId,
    			'currentGold' => intval($profitSharingEtt->currentGold),
    			'addGold' => intval($profitSharingEtt->addGold),
    			'status' => intval($profitSharingEtt->status),
    			'parentUserId' => intval($profitSharingEtt->parentUserId),
    			'userId' => intval($profitSharingEtt->userId),
    			'fromUserId' => intval($profitSharingEtt->fromUserId),
    			'updateTime' => intval($profitSharingEtt->updateTime),
    			'createTime' => intval($profitSharingEtt->createTime),
    			'userInfo' => empty($userModels[$profitSharingEtt->userId]) ? array() : $userModels[$profitSharingEtt->userId],
    			'fromUserInfo' => empty($userModels[$profitSharingEtt->fromUserId]) ? array() : $userModels[$profitSharingEtt->fromUserId],
    			'parentUserInfo' => empty($userModels[$profitSharingEtt->parentUserId]) ? array() : $userModels[$profitSharingEtt->parentUserId],
    		);
    	}

    	// 获取查询总数
    	$totalNum = $orderDao->getList($info, -1);
    	$models = array();
    	foreach ($dataList as $data) {
    		$profitSharingList = empty($profitSharingModels[$data->id]) ? array() : $profitSharingModels[$data->id];
    		$profitSharingTop1 = array();
    		$profitSharingTop2 = array();
    		foreach ($profitSharingList as $row) {
    			if ($data->userId == $row['fromUserId']) {
    				$profitSharingTop1 = $row;
    			} else {
    				$profitSharingTop2 = $row;
    			}
    		}
    		$models[] = array(
    			'id' => intval($data->id),
    			'outTradeNo' => $data->outTradeNo,
    			'status' => intval($data->status),
    			'price' => $data->price,
    			'updateTime' => intval($data->updateTime),
    			'createTime' => intval($data->createTime),
    			'userInfo' => empty($userModels[$data->userId]) ? array() : $userModels[$data->userId],
    			'profitSharingTop1' => $profitSharingTop1,
    			'profitSharingTop2' => $profitSharingTop2,
    		);
    	}
    	return array(
    		'list'     => array_values($models),
    		'totalNum' => $totalNum,
    	);
    }
    
    /**
     * 获取返利列表
     *
     * @return array
     */
    public function getProfitSharingList($userId, $info, $pageNum, $pageLimit)
    {
    	$profitSharingDao = \dao\ProfitSharing::singleton();
    	$dataList = $profitSharingDao->getList($info, $pageNum, $pageLimit);
    	$userIds = array_column($dataList, 'userId');
    	$parentUserIds = array_column($dataList, 'parentUserId');
    	$profitSharingUserIds = array_column($dataList, 'userId');
    	$orderIds = array_column($dataList, 'orderId');
    	
    	$orderDao = \dao\Order::singleton();
    	$orderEttList = $orderDao->readListByPrimary($orderIds);
    	$orderEttList = $orderDao->refactorListByKey($orderEttList);
    	$userSv = \service\User::singleton();
    	$userModels = $userSv->getUserModels(array_merge($parentUserIds, $profitSharingUserIds, $userIds));
    	 
    	$models = array();
    	foreach ($dataList as $profitSharingEtt) {
    		$orderEtt = empty($orderEttList[$profitSharingEtt->orderId]) ? array() : $orderEttList[$profitSharingEtt->orderId];
    		$orderInfo = array();
    		$topLevel = 1;
    		if (!empty($orderEtt)) {
    			$orderInfo = array(
	    			'id' => intval($orderEtt->id),
	    			'outTradeNo' => $orderEtt->outTradeNo,
	    			'status' => intval($orderEtt->status),
    				'userId' => intval($orderEtt->userId),
	    			'price' => $orderEtt->price,
	    			'updateTime' => intval($orderEtt->updateTime),
	    			'createTime' => intval($orderEtt->createTime),
	    			'userInfo' => empty($userModels[$orderEtt->userId]) ? array() : $userModels[$orderEtt->userId],
    			);
    			
    			if (!empty($orderInfo['userInfo']) && $orderInfo['userInfo']['parentUserId'] != $profitSharingEtt->userId) {
    				$topLevel = 2;
    			}
    		}
    		$models[] = array(
    			'id' => intval($profitSharingEtt->id),
    			'receiverAddOpenId' => $profitSharingEtt->receiverAddOpenId,
    			'currentGold' => intval($profitSharingEtt->currentGold),
    			'orderId' => intval($profitSharingEtt->orderId),
    			'outTradeNo' => empty($orderInfo['outTradeNo']) ? '' : $orderInfo['outTradeNo'],
    			'orderInfo' => $orderInfo,
    			'addGold' => intval($profitSharingEtt->addGold),
    			'status' => intval($profitSharingEtt->status),
    			'parentUserId' => intval($profitSharingEtt->parentUserId),
    			'topLevel' => $topLevel,
    			'userId' => intval($profitSharingEtt->userId),
    			'fromUserId' => intval($profitSharingEtt->fromUserId),
    			'updateTime' => intval($profitSharingEtt->updateTime),
    			'createTime' => intval($profitSharingEtt->createTime),
    			'userInfo' => empty($userModels[$profitSharingEtt->userId]) ? array() : $userModels[$profitSharingEtt->userId],
    			'fromUserInfo' => empty($userModels[$profitSharingEtt->fromUserId]) ? array() : $userModels[$profitSharingEtt->fromUserId],
    			'parentUserInfo' => empty($userModels[$profitSharingEtt->parentUserId]) ? array() : $userModels[$profitSharingEtt->parentUserId],
    		);
    	}
    	// 获取查询总数
    	$totalNum = $profitSharingDao->getList($info, -1);
    	return array(
    		'list'     => array_values($models),
    		'totalNum' => $totalNum,
    	);
    }
     
    /**
     * 处理订单分账
     *
     * @return array
     */
    public function doProfitSharing()
    {
    	$paySv = \service\Pay::singleton();
    	$info = array();
    	$orderDao = \dao\Order::singleton();
    	$orderEttList = $orderDao->getList($info, 1, 9999);
    	foreach ($orderEttList as $orderEtt) {
    		if (empty($orderEtt->transactionId)) {
    			continue;
    		}
    		// 触发分账
    		$profitsharingResult = $paySv->profitsharing($orderEtt);
    	}
    	echo "执行完成";
    }
}