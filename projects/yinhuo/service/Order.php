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
     * 创建正念课程支付订单
     *
     * @return array
     */
    public function createMindfulnessOrder($userId, $mindfulnessId, $deviceInfo)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$mindfulnessDao = \dao\Mindfulness::singleton();
    	$mindfulnessEtt = $mindfulnessDao->readByPrimary($mindfulnessId);
    	if (empty($mindfulnessEtt) || $mindfulnessEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$basePrice = $mindfulnessEtt->price; // 原始价格
  
    	
    	$price = max(0, $basePrice);
    	$now = $this->frame->now;
    	// 创建订单
    	$orderDao = \dao\Order::singleton();
    	$orderEtt = $orderDao->getNewEntity();
    	$orderEtt->goodsType = \constant\Order::TYPE_GOODS_MINDFULNESS; // 购买正念课程
    	$orderEtt->goodsId = $mindfulnessId;
    	$orderEtt->userId = $userId;
    	$orderEtt->status = \constant\Order::PAY_STATUS_DURING; // 未支付
    	$orderEtt->price = $price; // 需要支付的金额
    	$orderEtt->updateTime = $now;
    	$orderEtt->createTime = $now;
    	$orderEtt->outTradeNo = ''; // 订单号为空
    	$orderEtt->tradeInfo = ''; // 交易信息为空
    	$orderId = $orderDao->create($orderEtt);
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
    public function vipOrderPay($userId, $orderId, $info, $couponId = 0)
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
    	
    	// 生成订单号
    	$outTradeNo = self::createOutTradeNo($userId, $orderEtt->id);
    	$orderEtt->set('outTradeNo', $outTradeNo);
    	// 执行微信支付
    	$paySv = \service\Pay::singleton();
    	if ($info['tradeType'] == 'MWEB') { // h5支付(手机浏览器)
    		$alipaySv = \service\AliPay::singleton();
    		$payResult = $alipaySv->wapH5($userEtt, $orderEtt, $vipConfigEtt->name, $info);
    	} else {
    		$payResult = $paySv->prepare($info['tradeType'], $userEtt, $orderEtt, $vipConfigEtt->name);
    	}
    	
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
     * 正念课程订单支付（只操作不执行，在回调中执行）
     *
     * @return array
     */
    public function mindfulnessOrderPay($userId, $orderId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$userSv = \service\User::singleton();
    	$userInfo = $userSv->userInfo($userEtt);
    	$orderDao = \dao\Order::singleton();
    	$orderEtt = $orderDao->readByPrimary($orderId);
    	$now = $this->frame->now;
    	if (empty($orderEtt) || $orderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除，请重新下单！');
    	}
    	if ($orderEtt->goodsType != \constant\Order::TYPE_GOODS_MINDFULNESS) {
    		throw new $this->exception('订单类型错误，请重新下单！');
    	} elseif ($orderEtt->status == \constant\Order::PAY_STATUS_COMPLETE) {
    		throw new $this->exception('订单已支付，无需重复支付！');
    	} elseif ($orderEtt->status == \constant\Order::PAY_STATUS_PAST_DUE) {
    		throw new $this->exception('订单已逾期，请重新下单！');
    	}
    	$mindfulnessDao = \dao\Mindfulness::singleton();
    	$mindfulnessEtt = $mindfulnessDao->readByPrimary($orderEtt->goodsId);
    	if (empty($mindfulnessEtt) || $mindfulnessEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除，请重新下单！');
    	}
    	
    	// ！！！！开始预支付
    	 
    	// 生成订单号
    	$outTradeNo = self::createOutTradeNo($userId, $orderEtt->id);
    	$orderEtt->set('outTradeNo', $outTradeNo);
    	// 执行微信支付
    	$paySv = \service\Pay::singleton();
    	if ($info['tradeType'] == 'MWEB') { // h5支付(手机浏览器)
    		$alipaySv = \service\AliPay::singleton();
    		$payResult = $alipaySv->wapH5($userEtt, $orderEtt, $mindfulnessEtt->name, $info);
    	} else {
    		$payResult = $paySv->prepare($info['tradeType'], $userEtt, $orderEtt, $mindfulnessEtt->name);
    	}
    	
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
    	} elseif ($orderEtt->goodsType == \constant\Order::TYPE_GOODS_TEST_PAPER) { // 测评
    		// 获取测试订单
    		$testOrderDao = \dao\TestOrder::singleton();
    		$testOrderEtt = $testOrderDao->readListByIndex(array(
    			'orderId' => $orderEtt->id,
    		), true);
    		if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    			return false;
    		}
    		if (!empty($testOrderEtt->discountType)) { // 有折扣
    			if ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_GIVE) { // 赠送
    				$userGiveDao = \dao\UserGive::singleton();
    				$userGiveEtt = $userGiveDao->readByPrimary($testOrderEtt->discountId);
    				if (!empty($userGiveEtt) && $userGiveEtt->status != \constant\Give::STATUS_USED) {
    					$userGiveEtt->set('status', \constant\Give::STATUS_USED); // 已使用
    					$userGiveEtt->set('updateTime', $now);
    					$userGiveDao->update($userGiveEtt);
    				}
    			} elseif ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_COUPON) { // 优惠券
    				$userCouponDao = \dao\UserCoupon::singleton();
    				$userCouponEtt = $userCouponDao->readByPrimary($testOrderEtt->discountId);
    				if (!empty($userCouponEtt) && $userCouponEtt->status != \constant\Give::STATUS_USED) {
    					$userCouponEtt->set('status', \constant\Coupon::STATUS_USED); // 已使用
    					$userCouponEtt->set('updateTime', $now);
    					$userCouponDao->update($userCouponEtt);
    				}
    			} elseif ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_VIP) { // vip
    				if (!empty($testOrderEtt->discountId)) {
    					$userVipDao = \dao\UserVip::singleton();
    					$userVipEtt = $userVipDao->readByPrimary($testOrderEtt->discountId);
    					if (!empty($userVipEtt)) {
    						$useTestIds = empty($userVipEtt->useTestIds) ? array() : array_map('intval', explode(',', $userVipEtt->useTestIds));
    						$useTestIds[] = intval($testOrderEtt->testPaperId);
    						$useTestIds = array_unique($useTestIds);
    						$userVipEtt->set('useTestIds', implode(',', $useTestIds));
    						$userVipEtt->set('updateTime', $now);
    						$userVipDao->update($userVipEtt);
    					}
    				}
    			}
    		}
    		if (!empty($testOrderEtt->shareCode)) { // 有分享码，记录分享收益
    			$brokerageSv = \service\Brokerage::singleton();
    			$brokerageSv->createYieldRecord($testOrderEtt, $orderEtt);
    		}
    	} elseif ($orderEtt->goodsType == \constant\Order::TYPE_GOODS_MINDFULNESS) { // 正念课程
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($orderEtt->userId);
    		if (!empty($userEtt)) {
    			$mindfulnessIds = empty($userEtt->mindfulnessIds) ? array() : explode(',', $userEtt->mindfulnessIds);
    			$mindfulnessIds[] = intval($orderEtt->goodsId);
    			$mindfulnessIds = array_unique($mindfulnessIds);
    			$userEtt->set('mindfulnessIds', implode(',', $mindfulnessIds));
    			$userEtt->set('updateTime', $now);
    			$userDao->update($userEtt);
    		}
    	} 
    	return true;
    }
    
    /**
     * 测试订单支付
     *
     * @return array
     */
    public function testOrderPay($testOrderId, $info, $userId = 0, $couponId = 0)
    {
    	$userEtt = '';
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    		$userSv = \service\User::singleton();
    		$userInfo = $userSv->userInfo($userEtt);
    	}
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) { // 订单已删除
    	    throw new $this->exception('订单已删除');
    	} elseif ($testOrderEtt->status != \constant\Order::ORDER_STATUS_TESTED) { // 测评没有做完
    	    throw new $this->exception('测评未完成作答');
    	}
    	// 获取订单信息
    	$testPaperSv = \service\TestPaper::singleton();
    	$testOrderInfo = $testPaperSv->testOrderInfo($testOrderEtt);
    	// 获取测评信息
    	$testPaperInfo = empty($testOrderInfo['testPaperInfo']) ? array() : $testOrderInfo['testPaperInfo'];
    	// 支付页面
    	$payInfo = empty($testOrderInfo['payInfo']) ? array() : $testOrderInfo['payInfo'];
    	if (empty($testPaperInfo) || empty($payInfo)) {
    		throw new $this->exception('订单已失效，请重新下单！');
    	}
    	if ($payInfo['status'] == \constant\Order::PAY_STATUS_COMPLETE) { // 已支付
    		return array(
    			'noNeedPay' => 1,
    		);
    	} elseif ($payInfo['status'] == \constant\Order::PAY_STATUS_NO_NEED_PAY) { // 订单无需支付
    		return array(
    			'noNeedPay' => 1,
    		);
    	}
    	$now = $this->frame->now;
    	// 根据选择的红包及解锁的类型计算价格
    	$actualPrice = $payInfo['price']; // 实际支付价格
    	
    	// 有设置档次funlockIndex
    	if (empty($info['unlockIndex']) || $info['unlockIndex'] == 1) {
    		$actualPrice = $payInfo['price']; // 39
    	} elseif ($info['unlockIndex'] == 2 && !empty($payInfo['price1'])) {
    		$actualPrice = $payInfo['price1'];
    	} elseif ($info['unlockIndex'] == 3 && !empty($payInfo['price2'])) {
    		$actualPrice = $payInfo['price2']; // 49
    	} 
 		// 有领取红包
    	$redPacketValue = 0;
    	if (!empty($info['redPacketType']) && !empty($payInfo['redPacketConfig'])) { // 有红包配置
    		if ($info['redPacketType'] == 1 && !empty($payInfo['redPacketConfig']['value1'])) {
    			$redPacketValue = max(0, $payInfo['redPacketConfig']['value1']); 
    		}
    		if ($info['redPacketType'] == 2 && !empty($payInfo['redPacketConfig']['value2'])) {
    			$redPacketValue = max(0, $payInfo['redPacketConfig']['value2']);
    		}
    	}

    	$orderEtt = $testOrderEtt->order;
    	$orderDao = \dao\Order::singleton();
    	if (($actualPrice - $redPacketValue) <= 0) { // 支付金额小于0， 无需支付
    		if (!empty($orderEtt)) { // 有支付订单
    			$this->finishOrder($orderEtt, array(), \constant\Order::PAY_STATUS_NO_NEED_PAY); // 完结订单
    		}
    		return array(
    			'noNeedPay' => 1,
    		);
    	} else { // 需要支付
    		if (!empty($orderEtt)) { // 有支付订单
	    		if ($orderEtt->price != $actualPrice) { // 更正支付金额
	    			$orderEtt->set('price', $actualPrice); // 实际支付金额
	    			$orderEtt->set('updateTime', $now);
	    			$orderDao->update($orderEtt);
	    		}
	    		if ($orderEtt->redPacketValue != $redPacketValue) { // 更正红包金额
	    			$orderEtt->set('redPacketValue', $redPacketValue);
	    			$orderEtt->set('updateTime', $now);
	    			$orderDao->update($orderEtt);
	    		}
    		}
    	}
    	$outTradeNo = self::createOutTradeNo($userId, $testOrderEtt->id);
    	// 需要支付，拉取支付流程
    	if (empty($orderEtt)) { // 创建支付订单
    		$orderEtt = $orderDao->getNewEntity();
    		$orderEtt->goodsType = \constant\Order::TYPE_GOODS_TEST_PAPER; // 测评订单
    		$orderEtt->goodsId = $testOrderEtt->id; // 测评订单Id
    		$orderEtt->userId = $userId;
    		$orderEtt->status = \constant\Order::PAY_STATUS_DURING; // 未支付
    		$orderEtt->price = $actualPrice;
    		$orderEtt->redPacketValue = $redPacketValue;
    		$orderEtt->outTradeNo = $outTradeNo;
    		$orderEtt->tradeInfo = '';
    		$orderEtt->couponId = $couponId;
    		$orderEtt->updateTime = $now;
    		$orderEtt->createTime = $now;
    		$orderId = $orderDao->create($orderEtt);
    		$testOrderEtt->set('orderId', $orderId);
    		$testOrderEtt->set('updateTime', $now);
    		$testOrderDao->update($testOrderEtt);
    	} else {
    		$orderEtt->set('outTradeNo', $outTradeNo);
    	}
    	// 执行微信支付
    	$paySv = \service\Pay::singleton();
    	if ($info['tradeType'] == 'MWEB') { // h5支付(手机浏览器)
    		if ($info['h5Type'] == 'zfb') { // 支付宝
    			$alipaySv = \service\AliPay::singleton();
    			$payResult = $alipaySv->wapH5($userEtt, $orderEtt, $testPaperInfo['name'], $info);
    		} else {
    			$payResult = $paySv->prepare($info['tradeType'], $userEtt, $orderEtt, $testPaperInfo['name'], $info);
    		}
    	} else {
    		$payResult = $paySv->prepare($info['tradeType'], $userEtt, $orderEtt, $testPaperInfo['name']);
    	}

    	if (empty($payResult)) {
    		throw new $this->exception('支付失败，请重新下单！');
    	}
    	$orderEtt->set('updateTime', $now);
    	$orderDao->update($orderEtt);
    	return array(
    	    'info' => $payResult,
    	);
    }
    
    /**
     * 检查测评订单状态
     * 
     * 1. 是否完成测试
     * 2. 是否需要支付
     *
     * @return array
     */
    public function checkTestOrderPay($testOrderId, $userId = 0, $getOrderInfo = false)
    {
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		    throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    	}
    	// 获取订单信息
    	$testPaperSv = \service\TestPaper::singleton();
    	$testOrderInfo = $testPaperSv->testOrderInfo($testOrderId);

    	// 获取测评信息
    	$testPaperInfo = empty($testOrderInfo['testPaperInfo']) ? array() : $testOrderInfo['testPaperInfo'];
    	// 支付页面
    	$payInfo = empty($testOrderInfo['payInfo']) ? array() : $testOrderInfo['payInfo'];
 
    	$testComplete = 1; // 是否完成测试，1 完成  0  未完成
    	if (empty($testOrderInfo['testOrderInfo']['testCompleteTime'])) { // 测试没有完成
    		$testComplete = 0;
    	}
    	if (empty($testPaperInfo) || empty($payInfo)) {
    	    throw new $this->exception('订单已失效，请重新下单！');
    	}
    	$needPay = true; // 是否需要支付  1 需要 0 不需要
    	if (in_array($payInfo['status'], array(
    	    \constant\Order::PAY_STATUS_COMPLETE, // 已支付
    	    \constant\Order::PAY_STATUS_NO_NEED_PAY, // 无需支付
    	))) {
    	    $needPay = false;
    	}
    	return array(
    	    'needPay' => empty($needPay) ? 0 : 1, // 是否需要支付   1  需要支付  0 不需要支付
    	    'testComplete' => $testComplete, // 是否完成测试，1 完成  0  未完成
    		'testOrderInfo' => empty($getOrderInfo) ? array() : $testOrderInfo,
    	);
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
     * 检查正念订单是否需要支付
     *
     * @return array
     */
    public function checkMindfulnessOrderPay($userId, $orderId)
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
    	if ($orderEtt->goodsType != \constant\Order::TYPE_GOODS_VIP && $orderEtt->goodsType != \constant\Order::TYPE_GOODS_MINDFULNESS) {
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
    
}