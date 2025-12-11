<?php
namespace service;

define('SHARE_PRIVATE_KEY', '3561@!~ZHIZdhOU@!4&#~.212'); // 私钥前缀

/**
 * Brokerage 逻辑类
 * 
 * @author 
 */
class Brokerage extends ServiceBase
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
     * @return Brokerage
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Brokerage();
        }
        return self::$instance;
    }

    /**
     * 信息
     *
     * @return array
     */
    public function info($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$classifySv = \service\Classify::singleton();
    	$testList = $classifySv->getListByClassify(101, array(), 1, 999); // 分享测评
    	$testPaperList = empty($testList['list']) ? array() : $testList['list'];
    	if (is_iteratable($testPaperList)) foreach ($testPaperList as $key => $row) {
    		$testPaperList[$key]['shareYield'] = self::getShareYieldAmount($row, $userEtt);
    	}
    	$userTypeInfo = array();
    	if (!empty($userEtt->type)) {
    		$commissionRate = number_format($userEtt->commissionRate, 0);
    		$userTypeInfo = array(
    			'type' => intval($userEtt->type),
    			'commissionRate' => $commissionRate,
    		);
    	}
    	$shareYield = $userEtt->shareYield; // 累积分享收益
    	$residueAmount = max(0, $shareYield - $userEtt->withdrawAmount); // 可提现金额
    	return array(
    		'userTypeInfo' => $userTypeInfo,
    		'shareYield' => number_format($shareYield, 2), // 累积分享收益
    		'residueAmount' => number_format($residueAmount, 2), // 可提现金额
    		'testPaperList' => $testPaperList,
    	);
    }
    
    /**
     * 获取测评分享收益
     *
     * @return array
     */
    public static function getShareYieldAmount($testPaperInfo, $userEtt)
    {
    	$commissionRate = 30; // 分成系数 默认30%
    	if (!empty($userEtt->type) && !empty($userEtt->commissionRate)) {
    		$commissionRate = number_format($userEtt->commissionRate, 2);
    	}
    	$commissionRate = min(1, max(0.05, $commissionRate * 0.01)); // 5~100 之间
    	$amount = $testPaperInfo['price'] * $commissionRate; // 推广佣金
    	return number_format($amount, 2);
    }
    
    /**
     * 创建分享信息
     *
     * @return array
     */
    public function createShareInfo($testPaperId, $userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperInfo = $testPaperSv->testPaperInfo($testPaperId);
    	$now = $this->frame->now;
    	$privateKey = md5(SHARE_PRIVATE_KEY . $testPaperId);
   
    	$amount = self::getShareYieldAmount($testPaperInfo, $userEtt);
    	$info = array(
    		$now, // 当前时间
    		$userId, // 用户id
    		$testPaperId, // 测评ID
    		$amount, // 推广佣金
    	);
    	$encryptInfo = encrypt(json_encode($info), $privateKey); // 加密后的信息
		$testShareUrlBase = $this->frame->conf['web_url'];

    	// 生成分享二维码   测评链接 & 分享信息 加密  (分享用户uid   分享时间， 设备信息   ip地址  网络环境， 秘钥 )
    	$url = $testShareUrlBase . "/detail?testPaperId={$testPaperId}&hasParams=1&shareCode=" . $encryptInfo;
    	return array(
    		'testPaperInfo' => $testPaperInfo,
    		'amount' => self::getShareYieldAmount($testPaperInfo, $userEtt),
    		'shareUrl' => $url, // 二维码
    		'shareCode' => $encryptInfo,
    		'userName' => $userEtt->userName,
    		'headImgUrl' => $userEtt->headImgUrl,
    	);
    }
    
    
    /**
     * 提现申请
     * 
     * @return array
     */
    public function withdrawApply($userId, $withdrawValue)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	
    	// 1 检查可提现余额
    	$shareYield = $userEtt->shareYield; // 累积的分享收益
    	$residueAmount = max(0, $shareYield - $userEtt->withdrawAmount); // 可提现金额
    	if ($residueAmount <= 0) {
    		throw new $this->exception('您当前无可提现金额，请分享获得收益！');
    		throw new $this->exception('超出您的提现余额，请修改金额');
    	}
    	// 2. 检查提现金额是否足够
    	if ($withdrawValue > $residueAmount) {
    		throw new $this->exception('超出您的提现余额，请修改金额');
    	}
    	// 3. 检查提现上限
    	if ($withdrawValue >= 2000) {
    		throw new $this->exception('超出提现限额，请联系客服协助操作！');
    	}
    	if ($withdrawValue < 1) {
    		throw new $this->exception('最小提现金额为1RMB');
    	}
    	// 4. 统计单日累计提现金额
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEttList = $withdrawDao->readListByIndex(array(
    		'userId' => $userEtt->userId,
    	));
    	$commonSv = \service\Common::singleton();
    	uasort($withdrawEttList, array($commonSv, 'sortByCreateTime'));
    	$now = $this->frame->now;
    	$today = strtotime(date('Y-m-d', $now));
    	$list = array();
    	$todayWithdrawMap = array(); // 金日已提现
    	$approveWithdrawEtt = null; // 待确认收款的提现
    	$paySv = \service\Pay::singleton();
    	if (is_iteratable($withdrawEttList)) foreach ($withdrawEttList as $withdrawEtt) {
    		if ($withdrawEtt->status == \constant\Order::BROKERAGE_STATUS_APPROVE) { // 待确认
    			$paySv->wxTransferCancel($withdrawEtt);
    			$withdrawEtt->set('updateTime', $now);
    			$withdrawEtt->set('status', \constant\Order::BROKERAGE_STATUS_FAIL_AUDIT);
    			$withdrawDao->update($withdrawEtt);
    			continue;
    		}
    		if ($withdrawEtt->status != \constant\Order::BROKERAGE_STATUS_RECEIVED) { // 已到账
    			continue;
    		}
    		if ($withdrawEtt->updateTime <= $today) {
    			continue;
    		}
    		$todayWithdrawMap[$withdrawEtt->updateTime] = $withdrawEtt->amount * 0.01;
    	}
    
    	if (!empty($approveWithdrawEtt)) {
    		$transferInfo = empty($approveWithdrawEtt->transferInfo) ? array() : json_decode($approveWithdrawEtt->transferInfo, true);
    		$weChatConf = $this->frame->conf['weChat'];
    		return array(
    			'id' => intval($approveWithdrawEtt->id),
    			'residueAmount' => $residueAmount,
    			'outBillNo' => $approveWithdrawEtt->outBillNo,
    			'state' => empty($transferInfo['state']) ? '' : $transferInfo['state'],
    			'mchId' => $weChatConf['merchantId'],
    			'appId' => $weChatConf['appId'],
    			'packageInfo' => empty($transferInfo['package_info']) ? '' : $transferInfo['package_info'],
    		);
    	}
    	$todayWithdraw = empty($todayWithdrawMap) ? 0 : array_sum($todayWithdrawMap);
    	if (($todayWithdraw + $withdrawValue) >= 200) {
    		throw new $this->exception("超出每日限额200RMB，请联系客服操作！");
    	}
    	// 已经成功提现的次数
    	if (count($todayWithdrawMap) >= 10) {
    		throw new $this->exception("超出每日提现次数限制，请联系客服操作！");
    	}
    	
    	$wxTransferResult = $paySv->wxTransfer($userEtt, $withdrawValue);
    	if (empty($wxTransferResult)) {
    		return array(
    			'errorStr' => '提现失败，请联系客服！',
    		);
    	}
    	
    	/**
    	 ACCEPTED: 转账已受理
    	 PROCESSING: 转账锁定资金中。如果一直停留在该状态，建议检查账户余额是否足够，如余额不足，可充值后再原单重试。
    	 WAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认
    	 TRANSFERING: 转账中，可拉起微信收款确认页面再次重试确认收款
    	 SUCCESS: 转账成功
    	 FAIL: 转账失败
    	 CANCELING: 商户撤销请求受理成功，该笔转账正在撤销中
    	 CANCELLED: 转账撤销完成
    	 */
    	if ($wxTransferResult['state'] == 'ACCEPTED' || $wxTransferResult['state'] == 'SUCCESS') { // 转账已受理，转账成功
    		$withdrawStatus = \constant\Order::BROKERAGE_STATUS_RECEIVED; // 佣金状态：已到账
    	} elseif ($wxTransferResult['state'] == 'WAIT_USER_CONFIRM') { // 待收款用户确认
    		$withdrawStatus = \constant\Order::BROKERAGE_STATUS_APPROVE; // 佣金状态：审核通过（发起转账，待用户确认，金额在回调扣除）
    	} elseif($wxTransferResult['state'] == 'FAIL') { // 转账失败
    		$withdrawStatus = \constant\Order::BROKERAGE_STATUS_FAIL_AUDIT; // 佣金状态：失败
    	} else {
    		$withdrawStatus = \constant\Order::BROKERAGE_STATUS_IN_REVIEW; // 佣金状态：审核中
    	}
    	
    	// 创建申请订单
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEtt = $withdrawDao->getNewEntity();
    	$withdrawEtt->userId = $userEtt->userId;
    	$withdrawEtt->status = $withdrawStatus; // 审核中
    	$withdrawEtt->amount = $withdrawValue;
    	$withdrawEtt->outBillNo = $wxTransferResult['outBillNo'];
    	$withdrawEtt->transferInfo = json_encode($wxTransferResult['transferInfo']);
    	$withdrawEtt->updateTime = $now;
    	$withdrawEtt->createTime = $now;
    	$withdrawId = $withdrawDao->create($withdrawEtt);
    	$wxTransferResult['id'] = intval($withdrawId);
    	$wxTransferResult['residueAmount'] = $residueAmount;
    	return $wxTransferResult;
    }
   
    /**
     * 提现申请
     *
     * @return array
     */
    public function withdrawList($userEtt)
    {
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEttList = $withdrawDao->readListByIndex(array(
    		'userId' => $userEtt->userId,
    	));
    	$list = array();
    	if (is_iteratable($withdrawEttList)) foreach ($withdrawEttList as $withdrawEtt) {
    		if ($withdrawEtt->status != \constant\Order::BROKERAGE_STATUS_RECEIVED) {
    			continue;
    		}
    		$list[] = array(
    			'id' => intval($withdrawEtt->id),
    			'status' => intval($withdrawEtt->status),
    			'amount' => $withdrawEtt->amount,
    			'outBillNo' => $withdrawEtt->outBillNo,
    			'updateTime' => intval($withdrawEtt->updateTime),
    			'createTime' => intval($withdrawEtt->createTime),
    		);
    	}
    	return array(
    		'list' => array_values($list),
    	);
    }
    
    /**
     * 收益明细
     *
     * @return array
     */
    public function yieldList($info, $pageNum = 1, $pageLimit = 200, $getOrder = false)
    {

    	$yieldRecordDao = \dao\YieldRecord::singleton();
    	$yieldRecordEttList = $yieldRecordDao->getList($info, $pageNum, $pageLimit);
    	
    	$list = array();
    	$testUserIds = array();
    	$testPaperIds = array();
    	$testOrderIds = array();
    	if (is_iteratable($yieldRecordEttList)) foreach ($yieldRecordEttList as $yieldRecordEtt) {
    		$testPaperIds[] = intval($yieldRecordEtt->testPaperId);
    		$testUserIds[] = intval($yieldRecordEtt->testUserId);
    		$testUserIds[] = intval($yieldRecordEtt->shareUserId);
    		$testOrderIds[] = intval($yieldRecordEtt->testOrderId);
    	}

    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEttList = $testOrderDao->readListByPrimary($testOrderIds);
    	$testOrderEttList = $testOrderDao->refactorListByKey($testOrderEttList);
    	
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    	$testPaperEttList = $testPaperDao->refactorListByKey($testPaperEttList);
    	
    	$userDao = \dao\User::singleton();
    	$userEttList = $userDao->readListByPrimary($testUserIds);
    	$userEttList = $userDao->refactorListByKey($userEttList);
    	if (is_iteratable($yieldRecordEttList)) foreach ($yieldRecordEttList as $yieldRecordEtt) {
    		if (empty($testPaperEttList[$yieldRecordEtt->testPaperId]) 
    			|| empty($userEttList[$yieldRecordEtt->testUserId])
    			|| empty($testOrderEttList[$yieldRecordEtt->testOrderId])) {
    			continue;
    		}
    		$testPaperEtt = $testPaperEttList[$yieldRecordEtt->testPaperId];
    		$testUserEtt = $userEttList[$yieldRecordEtt->testUserId];
    		$testOrderEtt = $testOrderEttList[$yieldRecordEtt->testOrderId];
   
    		$shareUserEtt = $userEttList[$yieldRecordEtt->shareUserId];
    		
    		$list[$yieldRecordEtt->id] = array(
    			'id' => intval($yieldRecordEtt->id),
    			'status' => intval($yieldRecordEtt->status),
    			'amount' => $yieldRecordEtt->amount,
    			'updateTime' => intval($yieldRecordEtt->updateTime),
    			'createTime' => intval($yieldRecordEtt->createTime),
    			'testPaperInfo' => $testPaperEtt->getModel(),
    			'testUserInfo' => $testUserEtt->getModel(), // 测试账号
    			'shareUserInfo' => $shareUserEtt->getModel(), // 分享账号
    			'testOrderInfo' => $testOrderEtt->getModel(),
    		);
    		if (!empty($getOrder)) {
    			$orderEtt = $testOrderEtt->order;
    			$orderModel = array();
    			if (!empty($orderEtt)) { // 支付信息
    				$orderModel = $orderEtt->getModel();
    				$outTradeNo = $orderEtt->outTradeNo;
    				// 交易信息
    				$tradeInfo = empty($orderEtt->tradeInfo) ? array() : json_decode($orderEtt->tradeInfo, true);
    				$deviceInfo = empty($testOrderEtt->deviceInfo) ? array() : json_decode($testOrderEtt->deviceInfo, true);
    				 
    				$orderModel['outTradeNo'] = $outTradeNo; // 订单号
    				$orderModel['phoneModel'] = $deviceInfo['phoneModel']; // 手机型号
    			} 
    			$list[$yieldRecordEtt->id]['orderInfo'] = $orderModel;
    		}
    	}
    
    	return array(
    		'list' => array_values($list),
    	);
    }
    
    /**
     * 创建收益记录
     *
     * @return array
     */
    public function createYieldRecord($testOrderEtt, $orderEtt)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($testOrderEtt->userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		return false;
    	}
    	$testPaperId = $testOrderEtt->testPaperId;
    	$privateKey = md5(SHARE_PRIVATE_KEY . $testPaperId);
    	$shareInfo = decrypt($testOrderEtt->shareCode, $privateKey); // 解密信息
    	$shareInfo = empty($shareInfo) ? array() : json_decode($shareInfo, true);
  
    	if (empty($shareInfo) || count($shareInfo) < 4) {
    		return false;
    	}
    	$shareTime = $shareInfo['0'];
    	$shareUserId = $shareInfo['1'];
    	$shareTestPaperId = $shareInfo['2'];
    	$shareAmount = $shareInfo['3']; // 推广佣金
    	if (empty($shareTime) || empty($shareUserId) || empty($shareTestPaperId) || $shareTestPaperId != $testPaperId || $shareAmount <= 0) {
			return false;
    	}
    	$userDao = \dao\User::singleton();
    	$shareUserEtt = $userDao->readByPrimary($shareUserId);
    	if (empty($shareUserEtt) || $shareUserEtt->status == \constant\Common::DATA_DELETE) {
    		return false;
    	}
    	if ($orderEtt->price < $shareAmount) {
			return false;
    	}
    	$now = $this->frame->now;
    	$yieldRecordDao = \dao\YieldRecord::singleton();
    	$yieldRecordEtt = $yieldRecordDao->getNewEntity();
    	$yieldRecordEtt->shareUserId = $shareUserId;
    	$yieldRecordEtt->testUserId = $testOrderEtt->userId;
    	$yieldRecordEtt->testPaperId = $testPaperId;
    	$yieldRecordEtt->code = $testOrderEtt->shareCode;
    	$yieldRecordEtt->testOrderId = $testOrderEtt->id; // 订单ID
    	$yieldRecordEtt->amount = $shareAmount; // 收益金额
    	$yieldRecordEtt->status = \constant\Order::BROKERAGE_STATUS_NOT_APPLY; // 未申请
    	$yieldRecordEtt->updateTime = $now;
    	$yieldRecordEtt->createTime = $now;
    	$yieldRecordDao->create($yieldRecordEtt);
    	// 添加可提现金额
    	$shareUserEtt->add('shareYield', $yieldRecordEtt->amount);
    	$shareUserEtt->set('updateTime', $now);
    	$userDao->update($shareUserEtt);
    	return true;
    }
     
}