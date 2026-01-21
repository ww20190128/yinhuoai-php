<?php
namespace service;

/**
 * TestPaper 逻辑类
 * 
 * @author 
 */
class TestPaper extends ServiceBase
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
     * @return TestPaper
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestPaper();
        }
        return self::$instance;
    }
    
    /**
     * 获取最近一次测试订单
     *
     * @return array
     */
    public function getLastTestOrderInfo($testPaperInfo, $userInfo, $promotionModel = array())
    {
        if (empty($testPaperInfo['id']) || empty($userInfo['userId'])) {
            return array();
        }
        $testOrderDao = \dao\TestOrder::singleton();
        $testOrderEtt = $testOrderDao->getLastTestOrderInfo($testPaperInfo['id'], $userInfo['userId'], empty($promotionModel) ? 0 : $promotionModel['id']);

        if (empty($testOrderEtt)) {
            return array();
        }
        $testOrderModel =  $testOrderEtt->getModel();
        
    	if ($testOrderModel['status'] != 0) { // 答完了，检查是否需要支付
    		$orderSv = \service\Order::singleton();
    		$checkPayResult = $orderSv->checkTestOrderPay($testOrderModel['id'], $testOrderModel['userId']);
    		if (empty($checkPayResult['needPay'])) {  // 是否需要支付   1  需要支付  0 不需要支付
    			$testOrderModel['status'] = 2; // 无需支付
    		}	
    	}
       	return $testOrderModel;
    }

    /**
     * 获取测卷详情
     * 
     * @return array
     */
    public function testPaperInfo($testPaperId, $couponId = 0)
    {
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEtt = $testPaperDao->readByPrimary($testPaperId);
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $model = $testPaperEtt->getModel();
        $couponInfo = array();  // 优惠券信息
        if (!empty($couponId)) {
            $couponSv = \service\Coupon::singleton();
            $couponInfo = $couponSv->couponInfo($couponId);
            if ($couponInfo['status'] == \constant\Coupon::STATUS_NORMAL) { // 正常
                $discountValue = 0;
                if ($couponInfo['type'] == \constant\Coupon::TYPE_CASH_DEDUCTION) { // 现金抵扣
                    $discountValue = number_format($couponInfo['value'], 2);
                } elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_GIVE) { // 测评赠送
                    $discountValue = number_format($model['price'], 2);
                } elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_DISCOUNT) { // 测评折扣
                    $discountValue = number_format($model['price'] * (100 - $couponInfo['value']) * 0.01, 2);
                } else {
                    throw new $this->exception('优惠券不可用');
                }
                if (!empty($couponInfo['targetInfos'])) { // 验证目标
                    $targetIds = array_column($couponInfo['targetInfos'], 'id');
                    if (!in_array($testPaperId, $targetIds)) {
                        throw new $this->exception('优惠券不可用');
                    }
                }
                // 优惠券折扣信息
                $newPrice = max(0, $model['price'] - $discountValue);
                $couponInfo['discountValue'] = $discountValue; // 折扣值
                $couponInfo['newPrice'] = number_format($newPrice, 2); // 折扣值
                $model['oldPrice'] = $model['price']; // 更正前价格
                $model['price'] = number_format($newPrice, 2); // 更正价格
            } else { // 失效，或已使用
            	$couponInfo = array();
            }
            $model['couponInfo'] = $couponInfo;
        }
        return $model;
    }
    
    /**
     * 获取测卷题目
     *
     * @return array
     */
    public function getTestOrderQuestionInfo($testPaperName, $version, $userAnswerList = array(), $source = '')
    {
    	$questions = \entity\TestPaper::getQuestions($testPaperName);
   
    	if (empty($questions[$version])) {
    		throw new $this->exception('该测评没有配置题目，请联系管理员');
    	}
    	$testQuestionModels = array();
    	$questionGroupMap = array();
    	if (is_iteratable($questions[$version])) foreach ($questions[$version] as $index => $testQuestionModel) {
    		$testQuestionModel['id'] = $version * 1000 + $index;
    		$testQuestionModel['index'] = intval($index);
    		$selections = array_values($testQuestionModel['selections']);
    		$testQuestionModel['selections'] = $selections;
    		$testQuestionModel['scoreValue'] = empty($testQuestionModel['scoreValue']) ? '' : $testQuestionModel['scoreValue']; // 评分类型
    		if (!empty($userAnswerList) && isset($userAnswerList[$testQuestionModel['id']])) {
    			$testQuestionModel['userAnswer'] = $userAnswerList[$testQuestionModel['id']]; // 用户的作答
    			$testQuestionModel['analysis'] = empty($testQuestionModel['analysis']) ? '' : $testQuestionModel['analysis']; // 题目解析
    			$testQuestionModel['answer'] = empty($testQuestionModel['scoreValue']) ? '' : $testQuestionModel['scoreValue']; // 正确答案
    			$testQuestionModel['groupName'] = empty($testQuestionModel['groupName']) ? '' : $testQuestionModel['groupName']; // 分组
    		}
    		$testQuestionModels[] = $testQuestionModel;
    		if (!empty($testQuestionModel['groupName'])) { // 有分组
    			$questionGroupMap[$testQuestionModel['groupName']][$index] = intval($index);
    		}
    	}
    	
//  $testQuestionModels = array_slice($testQuestionModels, 0, 5);
    	$questionGroupModel = array();
    	if (!empty($questionGroupMap)) foreach ($questionGroupMap as $groupName => $list) {
    		$questionGroupModel[] = array(
    			'name' => $groupName,
    			'start' => min($list),
    			'end' => max($list),
    		);
    	}

    	return array(
    		'questionGroup' => $questionGroupModel,
    		'questionList' => $testQuestionModels,
    	);
    }
    
    /**
     * 重新测试
     *
     * @return array
     */
    public function resetTestOrder($testOrderId, $info, $userId)
    {
        $testOrderDao = \dao\TestOrder::singleton();
        $testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
        if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
//         if (!empty($testOrderEtt->testOrderId)) { // 本身为重测试卷，通过母卷去创建
//         	$testOrderEtt = $testOrderDao->readByPrimary($testOrderEtt->testOrderId);
//         	$testOrderId = $testOrderEtt->id;
//         }
        if (!empty($info['promotionId'])) { // 创建推广订单
            $promotionDao = \dao\Promotion::singleton();
            $promotionEtt = $promotionDao->readByPrimary($info['promotionId']);
            if (empty($promotionEtt) || $promotionEtt->status == \constant\Common::DATA_DELETE) {
                throw new $this->exception('推广活动已下架');
            }
            if (!empty($info['testPaperId']) && $info['testPaperId'] != $promotionEtt->testPaperId) {
                throw new $this->exception('推广活动已下架');
            }
            $info['testPaperId'] = $promotionEtt->testPaperId;
        }
        
        $testOrderModel = $testOrderEtt->getModel();
        $deviceInfo = $testOrderModel['deviceInfo'];
        $now = $this->frame->now;
        
        $testOrderEtt->set('answerList', '');
        $testOrderEtt->set('age', 0);
        $testOrderEtt->set('price', 0);
        $testOrderEtt->set('redPacketType', 0);
        $testOrderEtt->set('redPacketStatus', 0);
        $testOrderEtt->set('testCompleteTime', 0);
        $testOrderEtt->set('status', \constant\Order::ORDER_STATUS_NORMAL); // 测试中
        $testOrderEtt->set('updateTime', $now);
        $testOrderEtt->set('createTime', $now);
        $testOrderDao->update($testOrderEtt);
        return array(
            'testOrderId' => $testOrderEtt->id,
        );
    }
    
    /**
     * 创建测试订单
     *
     * @return array
     */
    public function createTestOrder($info, $deviceInfo, $userId, $answerList = array())
    {
    	$vipInfo = array(); // 用户的vip信息
    	$userEtt = null;
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    		$userSv = \service\User::singleton();
    		$userInfo = $userSv->userInfo($userId);
    		$vipInfo = empty($userInfo['userInfo']['vipInfo']) ? array() : $userInfo['userInfo']['vipInfo'];
    	}
    
    	if (!empty($info['promotionId'])) { // 推广订单
    		$promotionDao = \dao\Promotion::singleton();
    		$promotionEtt = $promotionDao->readByPrimary($info['promotionId']);
    		if (empty($promotionEtt) || $promotionEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('测评已下架');
    		}
    		if (!empty($info['testPaperId']) && $info['testPaperId'] != $promotionEtt->testPaperId) {
    			throw new $this->exception('测评已下架');
    		}
    		$info['testPaperId'] = $promotionEtt->testPaperId;
    	}
    	 
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperEtt = $testPaperDao->readByPrimary($info['testPaperId']);
    	if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('测评已删除');
    	}
    	$basePrice = $testPaperEtt->price; // 需要支付的价格
    	if (!empty($promotionEtt)) { // 推广测评，已经推广信息为准
    		$basePrice = $promotionEtt->price;
    	}
    	$userGiveDao = \dao\UserGive::singleton();
    	$userCouponDao = \dao\UserCoupon::singleton();
    	$discountType = 0; // 抵扣类型
    	$discountId = 0; // 抵扣关联的Id
    	$discountValue = 0; // 抵扣值
    	
    	if (!empty($info['promotionId'])) { // 推广订单，vip  赠送，折扣券都无效
    		$discountType = 0; // 抵扣类型
    		$discountId = 0; // 抵扣关联的Id
    		$discountValue = 0; // 抵扣值
    	} else { // 非推广订单
	    	if (!empty($info['giveId']) && !empty($userEtt)) { // 赠送
	    		$userGiveEtt = $userGiveDao->readByPrimary($info['giveId']);
	    		if (empty($userGiveEtt) || $userGiveEtt->status == \constant\Common::DATA_DELETE || $userGiveEtt->drawUserId != $userEtt->userId) {
	    			throw new $this->exception('赠送已失效！');
	    		} elseif ($userGiveEtt->status != \constant\Give::STATUS_DRAWED) {
	    			throw new $this->exception('赠送已使用！');
	    		}
	    		$giveSv = \service\Give::singleton();
	    		$giveInfo = $giveSv->giveInfo($userGiveEtt, $userEtt);
	    		if (empty($giveInfo['testPaperInfo']) || $giveInfo['testPaperInfo']['id'] != $info['testPaperId']) {
	    			throw new $this->exception('赠送已失效！');
	    		}
	    		if (empty($userId) && !empty($userGiveEtt->drawUserId)) {
	    			$userId = $userGiveEtt->drawUserId;
	    		}
	    		//  赠送测评，无需支付
	    		$discountType = \constant\Order::DISCOUNT_TYPE_GIVE; // 赠送
	    		$discountId = $info['giveId'];
	    		$discountValue = $basePrice;
	    	} elseif (!empty($info['couponId']) && !empty($userEtt)) { // 优惠券，验证优惠券
	    		$userCouponEtt = $userCouponDao->readByPrimary($info['couponId']);
	    		if (empty($userCouponEtt) || $userCouponEtt->status == \constant\Common::DATA_DELETE || $userCouponEtt->userId != $userEtt->userId) {
	    			throw new $this->exception('优惠券已使用');
	    		}
	    		$couponSv = \service\Coupon::singleton();
	    		$couponInfo = $couponSv->couponInfo($userCouponEtt, $userEtt);
	    	
	    		if ($couponInfo['status'] == \constant\Coupon::STATUS_USED) { // 优惠券已使用
	    			throw new $this->exception('优惠券已使用');
	    		} elseif ($couponInfo['status'] == \constant\Coupon::STATUS_OVERDUE) { // 优惠券已到期
	    			throw new $this->exception('优惠券已到期');
	    		} elseif ($couponInfo['status'] == \constant\Coupon::STATUS_NORMAL) { // 优惠券可正常使用
	    			if ($couponInfo['type'] == \constant\Coupon::TYPE_CASH_DEDUCTION) { // 现金抵扣，根据余额判断是否还需支付
	    				$discountValue = $couponInfo['value'];
	    			} elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_GIVE) { // 测评赠送，无需支付
	    				$discountValue = $basePrice;
	    			} elseif ($couponInfo['type'] == \constant\Coupon::TYPE_TEST_PAPER_DISCOUNT) { // 测评折扣，有余额，还需支付
	    				$discountValue = $basePrice * (100 - $couponInfo['value']) * 0.01;
	    			} else {
	    				throw new $this->exception('优惠券不可用');
	    			}
	    			// 验证目标
	    			if (!empty($couponInfo['targetInfos'])) { // 优惠价有指定使用目标
	    				$targetIds = array_column($couponInfo['targetInfos'], 'id');
	    				if (!in_array($info['testPaperId'], $targetIds)) {
	    					throw new $this->exception('优惠券不可用');
	    				}
	    			}
	    			// 优惠券折扣信息
	    			$couponInfo['discountValue'] = $discountValue; // 折扣值
	    			$couponInfo['newPrice'] = max(0, $basePrice - $discountValue); // 折扣后的价格
	    		}
	    		$discountType = \constant\Order::DISCOUNT_TYPE_COUPON; // 优惠券
	    		$discountId = $info['couponId'];
	    	} 
	    	if (!empty($vipInfo)) { // vip 有效
	    		if (!empty($vipInfo['surplusTestPaperNum'])) { // 有测评次数限制，无需支付
	    			$discountType = \constant\Order::DISCOUNT_TYPE_VIP; // vip
	    			$discountId = $vipInfo['testEffectVipId'];
	    			$discountValue = $basePrice;
	    		}
	    	}
	    	
    	}
    	$now = $this->frame->now;

    	// 当实际支付的价格为0时，不创建支付订单
    	$actualPrice = max(0, $basePrice - $discountValue);
    	$actualPrice = is_float($actualPrice) ? floor($actualPrice * 100) / 100 : $actualPrice;

    	// 创建测试订单
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->getNewEntity();
    
    	$testOrderEtt->promotionId = $info['promotionId'];
    	$testOrderEtt->testPaperId = $info['testPaperId'];
    	$testOrderEtt->price = $actualPrice; // 实际支付的价格
    	$testOrderEtt->userId = $userId;
    	$testOrderEtt->discountType = $discountType; // 折扣类型
    	$testOrderEtt->discountId = $discountId; // 折扣Id
    	$testOrderEtt->discountValue = $discountValue; // 折扣值
    	$testOrderEtt->orderId = 0;
    	$testOrderEtt->version = $info['version'];
    	$testOrderEtt->deviceInfo = empty($deviceInfo) ? '' : json_encode($deviceInfo); // 设备信息
    	$testOrderEtt->updateTime = $now;
    	$testOrderEtt->createTime = $now;
    	$testOrderEtt->status = \constant\Order::ORDER_STATUS_NORMAL; // 测试中
    	$testOrderEtt->shareCode = empty($info['shareCode']) ? '' : $info['shareCode']; // 分享推广码
    	$testOrderEtt->testCompleteTime = 0; // 测试完成时间
    	if (!empty($answerList)) { // 推广测评有答案，创建答案
    		$questionInfo = $this->getTestOrderQuestionInfo($testPaperEtt->name, $testOrderEtt->version);
    		if (empty($questionInfo)) {
    			throw new $this->exception('测评已删除');
    		}
    		$questionList = array_column($questionInfo['questionList'], null, 'id');
    		$map = array();
    		foreach ($answerList as $row) {
    			if (empty($questionList[$row['id']])) {
    				continue;
    			}
    			$map[$row['id']] = intval($row['answer']);
    		}
    		if (empty($map) || count($map) != count($questionList)) {
    			throw new $this->exception('有题目尚未作答');
    		}
    		$testOrderEtt->testCompleteTime = $now; // 测试完成时间
    		$testOrderEtt->status = \constant\Order::ORDER_STATUS_TESTED; // 完成测试
    		$newAnswerStr = empty($map) ? '' : json_encode($map);
    		$testOrderEtt->set('answerList', $newAnswerStr);
    	}
    	// 创建订单
    	$testOrderId = $testOrderDao->create($testOrderEtt);
    	return array(
    		'testOrderId' => intval($testOrderId),
    	);
    }
    
    /**
     * 获取重测列表
     *
     * @return array
     */
    public function resetTestOrderList($testOrderId, $userId)
    {
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除');
    	} elseif (!empty($testOrderEtt->userId) && !empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    		if ($testOrderEtt->userId != $userId) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    	}
    	if (!empty($testOrderEtt->testOrderId)) { // 本事为重测试卷，通过母卷去获取
    		$testOrderEtt = $testOrderDao->readByPrimary($testOrderEtt->testOrderId);
    		$testOrderId = $testOrderEtt->id;
    	}
    	$orderSv = \service\Order::singleton();
    	$checkResult = $orderSv->checkTestOrderPay($testOrderId, $userId, true);
    	if (empty($checkResult['testComplete'])) {
    		return array();
    	}
    	if (!empty($checkResult['needPay'])) {
    		return array();
    	}
    	$where = "`testOrderId` = {$testOrderId} and `status` != " . \constant\Common::DATA_DELETE;
    	$testOrderDao = \dao\TestOrder::singleton();
    	$restTestOrderEttList = $testOrderDao->readListByWhere($where);
    	$models = array($testOrderEtt->getModel());
    	if (is_iteratable($restTestOrderEttList)) foreach ($restTestOrderEttList as $restTestOrderEtt) {
    		$models[] = $restTestOrderEtt->getModel();
    	}
    	// 根据创建时间排序
    	$commonSv = \service\Common::singleton();
    	uasort($models, array($commonSv, 'sortByCreateTime'));
    	return $models;
    }

    /**
     * 创建重测订单
     * 
     * @return array
     */
    public function createResetTestOrder($testOrderId, $userId)
    {
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除');
    	}
    	if (!empty($testOrderEtt->testOrderId)) { // 本身为重测试卷，通过母卷去创建
    		$testOrderEtt = $testOrderDao->readByPrimary($testOrderEtt->testOrderId);
    		$testOrderId = $testOrderEtt->id;
    	}
    	$orderSv = \service\Order::singleton();
    	$checkResult = $orderSv->checkTestOrderPay($testOrderId, $userId, true);
    	if (empty($checkResult['testComplete'])) {
    		throw new $this->exception('测试未完成，请继续答题');
    	}
    	if (!empty($checkResult['needPay'])) {
    		throw new $this->exception('订单未支付，请尽快支付');
    	}
    	// 获取重测列表
    	$resetTestOrderList = $this->resetTestOrderList($testOrderId, $userId);
    	if (count($resetTestOrderList) >= 4) {
    		throw new $this->exception('重测次数已达4次，无法重测');
    	}
  
    	$now = $this->frame->now;
    	$resetTestOrderEtt = $testOrderDao->getNewEntity();
    	$resetTestOrderEtt->status = \constant\Order::ORDER_STATUS_NORMAL; // 测试中
    	$resetTestOrderEtt->testCompleteTime = 0; // 测试完成时间
    	$resetTestOrderEtt->testOrderId = $testOrderId;
    	$resetTestOrderEtt->promotionId = $testOrderEtt->promotionId;
    	$resetTestOrderEtt->testPaperId = $testOrderEtt->testPaperId;
    	$resetTestOrderEtt->userId = $testOrderEtt->userId;
    	$resetTestOrderEtt->version = $testOrderEtt->version;
    	$resetTestOrderEtt->createTime = $now;
    	$resetTestOrderEtt->updateTime = $now;
    	$resetTestOrderEtt->deviceInfo = '';
    	$resetTestOrderEtt->answerList = '';
    	$resetTestOrderEtt->price = 0; // 折扣前价格
    	$testOrderId = $testOrderDao->create($resetTestOrderEtt);
    	return array(
    		'testOrderId' => intval($testOrderId),
    	);
    }
    
    /**
     * 获取测评订单详情
     * 
     * @return array
     */
    public function testOrderInfo($testOrderEtt)
    {
        if (is_numeric($testOrderEtt)) {
            $testOrderDao = \dao\TestOrder::singleton();
            $testOrderEtt = $testOrderDao->readByPrimary($testOrderEtt);
        }
        if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception(' '); // 订单已删除，请重新测试
        }
        $promotionModel = array();
        if (!empty($testOrderEtt->promotionId)) { // 推广订单
            $promotionDao = \dao\Promotion::singleton();
            $promotionEtt = $promotionDao->readByPrimary($testOrderEtt->promotionId);
            if (empty($promotionEtt) || $promotionEtt->status == \constant\Common::DATA_DELETE) {
                throw new $this->exception(' '); // 订单已删除，请重新测试
            }
            $promotionModel = $promotionEtt->getModel();
        }
        // 测评
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEtt = $testPaperDao->readByPrimary($testOrderEtt->testPaperId);
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
        	throw new $this->exception('测评已删除，请重新测试');
        }
        $testOrderModel = $testOrderEtt->getModel();
        $testPaperModel = $testPaperEtt->getModel();
        // 获取通用配置
        $conf = getStaticData($testPaperModel['name'], 'common');
        $commonSv = \service\Common::singleton();
        $payInfo = array( // 支付信息
            'originalPrice'  => $testPaperModel['originalPrice'], // 原价
            'payDesc'        => empty($conf['payDesc']) ? '' : $conf['payDesc'], // 支付描述
            'payConfirmDec'  => empty($conf['payConfirmDec']) ? '' : $conf['payConfirmDec'], // 支付确认-描述
            'ui_payBtnColor' => 'rgba(82,140,240,1)', // 解锁你的专业报告按钮颜色，  支付按钮颜色   float_btn_color
            'ui_payBtnText'  => '立即解锁你的专属报告', // 支付按钮的文本   float_btn_color
            'payStyleType'	 => empty($conf['extend']['payStyleType']) ? 0 : $conf['extend']['payStyleType'], // mbti 有该字段
          	'payPcContent' => empty($conf['payPcContent']) ? '' : $commonSv::replaceImgSrc($conf['payPcContent'], 'pay'), // pc端支付介绍 mbti 有该字段
        	'payMobileContent' => empty($conf['payMobileContent']) ? '' : $commonSv::replaceImgSrc($conf['payMobileContent'], 'pay'), // 移动端支付介绍
        );
        // 支付信息
        $orderEtt = $testOrderEtt->order;
        if (!empty($orderEtt)) { // 有支付信息
        	$orderModel = $orderEtt->getModel();
        	$payInfo['status'] = $orderModel['status']; // 当前的支付状态
        	$payInfo['price'] = number_format($orderModel['price'], 2); // 当前的支付价格
        } else { // 没有支付信息
        	if ($testOrderModel['price'] <= 0) { // 需要支付的价格为0 ，无需支付
        		$payInfo['status'] = \constant\Order::PAY_STATUS_NO_NEED_PAY; // 当前的支付状态: 无需支付
        		$payInfo['price'] = 0; // 当前的支付价格
        	} else { // 需要支付，当前未支付
        		$payInfo['status'] = \constant\Order::PAY_STATUS_DURING; // 未支付
        		$payInfo['price'] = number_format($testOrderModel['price'], 2); // 当前的支付价格
        	}
        }
    
        if (!empty($promotionModel)) { // 只推广测评有红包配置
        	$payInfo['redPacketConfig'] = $promotionModel['redPacketConfig'];
        }
        // MBTI 解锁不同内容，需要的价格不一样， 3个档次，1. 测试结果，完整解读，完整解读pro
        if (!empty($conf['priceList'])) { // 有不同的价格区间配置
        	$priceIndex = 0;
        	foreach ($conf['priceList'] as $originalPrice => $price) {
        		$originalPricePro = empty($priceIndex) ? 'originalPrice' : 'originalPrice' . $priceIndex;
        		$pricePro = empty($priceIndex) ? 'price' : 'price' . $priceIndex;
        		$payInfo[$pricePro] = $price;
        		$payInfo[$originalPricePro] = $originalPrice;
        		$priceIndex++;
        	}
        }
        return array(
        	'testOrderInfo' => $testOrderModel,
        	'promotionInfo' => $promotionModel,
        	'testPaperInfo' => $testPaperModel,
        	'payInfo' => $payInfo, // 支付信息
        );  
    }
    
    /**
     * 获取已作答记录
     *
     * @return array
     */
    public function getAnswerRecord($testOrderId)
    {
        $testOrderDao = \dao\TestOrder::singleton();
        $testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
        if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEtt = $testPaperDao->readByPrimary($testOrderEtt->testPaperId);
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $map = empty($testOrderEtt->answerList) ? array() : json_decode($testOrderEtt->answerList, true);
        $answerList = array();
        if (is_iteratable($map)) foreach ($map as $questionId => $answer) {
            $answerList[] = array(
                'id' => intval($questionId),
                'answer' => intval($answer), // 正确答案
            );
        }
        return array(
            'answerList' => $answerList,
        );
    }
    
    /**
     * 提交答案
     *
     * @return array
     */
    public function submitAnswers($testOrderId, $answerList)
    {
        $testOrderDao = \dao\TestOrder::singleton();
        $testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
        if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEtt = $testPaperDao->readByPrimary($testOrderEtt->testPaperId);
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $questionInfo = $this->getTestOrderQuestionInfo($testPaperEtt->name, $testOrderEtt->version);
        if (empty($questionInfo)) {
            throw new $this->exception('测评已删除');
        }
        $questionList = array_column($questionInfo['questionList'], null, 'id');
        $map = array();
        foreach ($answerList as $row) {
            if (empty($questionList[$row['id']])) {
                continue;
            }
            $map[$row['id']] = intval($row['answer']);
        }
        $now = $this->frame->now;
        $newAnswerStr = empty($map) ? '' : json_encode($map);
        $testOrderEtt->set('answerList', $newAnswerStr);
        $testOrderEtt->set('updateTime', $now);
        $testOrderEtt->set('status', \constant\Order::ORDER_STATUS_NORMAL); // 测试中
        $testOrderDao->update($testOrderEtt);
        return array(
            'result' => 1,
        );
    }
    
    /**
     * 提交试卷
     *
     * @return array
     */
    public function submitTest($testOrderId, $answerList)
    {
        $testOrderDao = \dao\TestOrder::singleton();
        $testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
        if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEtt = $testPaperDao->readByPrimary($testOrderEtt->testPaperId);
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已删除');
        }
        $questionInfo = $this->getTestOrderQuestionInfo($testPaperEtt->name, $testOrderEtt->version);
        if (empty($questionInfo)) {
            throw new $this->exception('测评已删除');
        }
       
        $questionList = array_column($questionInfo['questionList'], null, 'id'); 
        $map = array();
        foreach ($answerList as $row) {
            if (empty($questionList[$row['id']])) {
                continue;
            }
            $map[$row['id']] = intval($row['answer']);
        }
        if (empty($map) || count($map) != count($questionList)) {
            throw new $this->exception('有题目尚未作答');
        }
        $testOrderNewStatus = \constant\Order::ORDER_STATUS_TESTED; // 新状态：已作答
        // 检查是否需要支付
        $orderSv = \service\Order::singleton();
        $checkResult = $orderSv->checkTestOrderPay($testOrderEtt->id);
        if (empty($checkResult['needPay'])) { // 不需要支付
            $testOrderNewStatus = \constant\Order::PAY_STATUS_NO_NEED_PAY;
        }
        $now = $this->frame->now;
        // 扣除折扣信息
        if (!empty($testOrderEtt->discountType)) { // 有折扣
        	if ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_GIVE) { // 赠送
        		$userGiveDao = \dao\UserGive::singleton();
        		$userGiveEtt = $userGiveDao->readByPrimary($testOrderEtt->discountId);
        		if (!empty($userGiveEtt)) {
        			$userGiveEtt->set('status', \constant\Give::STATUS_USED); // 已使用
        			$userGiveEtt->set('updateTime', $now);
        			$userGiveDao->update($userGiveEtt);
        		}
        	} elseif ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_COUPON) { // 优惠券
        		$userCouponDao = \dao\UserCoupon::singleton();
        		$userCouponEtt = $userCouponDao->readByPrimary($testOrderEtt->discountId);
        		if (!empty($userCouponEtt)) {
        		    $couponConfigDao = \dao\CouponConfig::singleton();
        		    $couponConfigEtt = $couponConfigDao->readByPrimary($userCouponEtt->couponId);
        		    if ($couponConfigEtt->type == \constant\Coupon::TYPE_TEST_PAPER_GIVE) { // 赠送券
        		        $userCouponEtt->set('status', \constant\Coupon::STATUS_USED); // 已使用
        		        $userCouponEtt->set('updateTime', $now);
        		        $userCouponDao->update($userCouponEtt);
        		    } elseif ($testOrderNewStatus == \constant\Order::PAY_STATUS_COMPLETE) { // 折扣券
        		        $userCouponEtt->set('status', \constant\Coupon::STATUS_USED); // 已使用
        		        $userCouponEtt->set('updateTime', $now);
        		        $userCouponDao->update($userCouponEtt);
        		    }
        		}
        	} elseif ($testOrderEtt->discountType == \constant\Order::DISCOUNT_TYPE_VIP) {
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
        
        $newAnswerStr = empty($map) ? '' : json_encode($map);
        $testOrderEtt->set('answerList', $newAnswerStr);
        $testOrderEtt->set('updateTime', $now);
        $testOrderEtt->set('status', $testOrderNewStatus);
        $testOrderEtt->set('testCompleteTime', $now); // 更新测评完成时间
        $testOrderDao->update($testOrderEtt);
        return array(
            'result' => 1,
        );
    }
    
    /**
     * 修改订单设置
     *
     * @return array
     */
    public function updateTestOrder($testOrderId, $info)
    {
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('测评已删除');
    	}
    	if (isset($info['age']) && $info['age'] != $testOrderEtt->age) {
    	    $testOrderEtt->set('age', $info['age']);
    	}	
    	if (isset($info['redPacketType']) && $info['redPacketType'] != $testOrderEtt->redPacketType) {
    	    $testOrderEtt->set('redPacketType', $info['redPacketType']);
    	}
    	$now = $this->frame->now;
    	$testOrderEtt->set('updateTime', $now);
    	$testOrderDao->update($testOrderEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 获取报告生成流程
     *
     * @return array
     */
    public function getReportProcess($testOrderInfo)
    {
// return array();
    	$reportProcessDao = \dao\ReportProcess::singleton();
    	$reportProcessEttList = $reportProcessDao->readListByIndex(array(
    		'testPaperId' => $testOrderInfo['testPaperId'],
    		'version' => $testOrderInfo['version'],
    	));
    	$map = array();
    	if (is_iteratable($reportProcessEttList)) foreach ($reportProcessEttList as $reportProcessEtt) {
    		$map[$reportProcessEtt->groupName][$reportProcessEtt->id] = array(
    			'id' => intval($reportProcessEtt->id),	
    			'title' => $reportProcessEtt->title,
    			'titleColor' => $reportProcessEtt->titleColor,
    			'groupName' => $reportProcessEtt->groupName,
    			'executeTime' => intval($reportProcessEtt->executeTime),
    			'index' => intval($reportProcessEtt->index),
    			'updateTime' => intval($reportProcessEtt->updateTime),
    			'createTime' => intval($reportProcessEtt->createTime),
    		);
    	}

    	$result = array();
    	$commonSv = \service\Common::singleton();
    	$index = 1;
    	foreach ($map as $groupName => $list) {
    		 uasort($list, array($commonSv, 'sortByIndex'));
    		 $result[] = array(
    		 	'id' => $index++,
    		 	'name' => $groupName,
    		 	'subList' => array_values($list),
    		 ); 
    	}
    	return $result;
    }
    
}