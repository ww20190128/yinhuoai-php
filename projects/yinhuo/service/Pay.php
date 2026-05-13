<?php
namespace service;

require_once('vendor/autoload.php');
use WeChatPay\Formatter;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WeChatPay\Crypto\AesGcm;

/**
 * 支付
 *
 * @author
*/
class Pay extends ServiceBase
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * 微信支付实例
	 *
	 * @var object
	 */
	private static $weChatPayInstance;
	
	/**
	 * 单例模式
	 *
	 * @return Pay
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new Pay();
			$weChatConf = self::$instance->frame->conf['weChat'];
			$serverId = self::$instance->frame->conf['id'];
			$apiclientDir = CONFIGS_PATH . 'apiclient_' . $serverId . DS;
			// 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
			$merchantPrivateKeyInstance = Rsa::from('file://' . $apiclientDir . 'apiclient_key.pem', Rsa::KEY_TYPE_PRIVATE);
			// 从本地文件中加载「微信支付平台证书」或者「微信支付平台公钥」，用来验证微信支付应答的签名
			$platformPublicKeyInstance = Rsa::from('file://' . $apiclientDir . 'wechatpay_key.pem', Rsa::KEY_TYPE_PUBLIC);	
			// 构造一个 APIv3 客户端实例
			self::$weChatPayInstance = Builder::factory(array(
				'mchid'      => $weChatConf['merchantId'],
				'serial'     => $weChatConf['APICertificateKey'],
				'privateKey' => $merchantPrivateKeyInstance,
				'certs'      => array($weChatConf['RSA'] => $platformPublicKeyInstance),
			));
		}
		return self::$instance;
	}
	
	/**
	 * 支付通知回调
	 *
	 * @return array
	 */
	public function wxPayNotify($resourceArr, $bodyJson, $info)
	{
        $weChatConf = $this->frame->conf['weChat'];
        $serverId = self::$instance->frame->conf['id'];
        $apiclientDir = CONFIGS_PATH . 'apiclient_' . $serverId . DS;
        // 根据通知的平台证书序列号，查询本地平台证书文件，
        $platformPublicKeyInstance = Rsa::from('file://' . $apiclientDir . 'wechatpay_key.pem', Rsa::KEY_TYPE_PUBLIC);
        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$info['payTime']);
        $verifiedStatus = Rsa::verify(
            Formatter::joinedByLineFeed($info['payTime'], $info['nonce'], $bodyJson),
            $info['signature'],
            $platformPublicKeyInstance
        );

        if ($timeOffsetStatus && $verifiedStatus) {
            // 加密文本消息解密
            $bodyResource = AesGcm::decrypt($resourceArr['ciphertext'], $weChatConf['APIv3Key'], $resourceArr['nonce'], $resourceArr['associated_data']);
            $bodyResource = empty($bodyResource) ? array() : json_decode($bodyResource, true);
            if (empty($bodyResource) || empty($bodyResource['appid']) || empty($weChatConf['merchantId']) 
            	|| empty($bodyResource['out_trade_no']) || empty($bodyResource['payer']['openid'])) {
            	return false;
            }
            if ($bodyResource['appid'] != $weChatConf['appId'] || $bodyResource['mchid'] != $weChatConf['merchantId']) {
            	return false;
            }
            $out_trade_no = $bodyResource['out_trade_no']; // 订单ID
            $transaction_id = $bodyResource['transaction_id']; // 交易ID
            $trade_type = $bodyResource['trade_type']; // 交易类型
            $openid = $bodyResource['payer']['openid']; // 交易账号ID
            $total = $bodyResource['amount']['total']; // 支付金额

            // 查找支付订单
            $orderDao = \dao\Order::singleton();
            $orderEtt = $orderDao->readListByIndex(array(
            	'outTradeNo' => $out_trade_no,
            ), true);
            if (empty($orderEtt)) {
            	return false;
            }
            $orderEtt->set('transactionId', $transaction_id);
            $orderDao->update($orderEtt);
     
            // 完结订单
            $orderSv = \service\Order::singleton();
            $orderSv->finishOrder($orderEtt, array_merge(json_decode($bodyJson, true), $resourceArr, $info), \constant\Order::PAY_STATUS_COMPLETE);
        } else {
            // 调用微信查询订单API
            return false;
        }
        return true;
	}
	
	/**
	 * 发起转账
	 *
	 * @return array
	 */
	public function wxTransfer($userEtt, $transferAmount)
	{
		$appConfig = $this->frame->conf['appConfig'];
		$weChatConf = $this->frame->conf['weChat'];
		$notify_url = $this->frame->conf['serve_url'] . '/order/wxTransferNotify';
		$now = self::$instance->frame->now;
		$out_bill_no = date('YmdHis', $now) . $userEtt->userId . rand(10, 99) . rand(10, 99);
		$data = array(
			'appid' => $weChatConf['appId'],
			'out_bill_no' => $out_bill_no, // 单号
			'transfer_scene_id' => '1005', // 转账场景ID
			'openid' => $userEtt->openid, // 用户openid
			'transfer_amount' => $transferAmount * 100, // 转账金额(分)
			'transfer_remark' => $appConfig['name'] . '-分享佣金', // 转账备注
			'transfer_scene_report_infos'=> array( // 转账场景报备信息参数 https://pay.weixin.qq.com/doc/v3/merchant/4013774588
				array(
					'info_type' => '岗位类型',
					'info_content' => '推广员',
				),
				array(
					'info_type' => '报酬说明',
					'info_content' => '分享佣金',
				),
			),
			'notify_url' => $notify_url,
		);
		if ($data['transfer_amount'] >= 20000) { // 转账金额超出2千 需要真实姓名
			return false;
		}
		try {
			$response = self::$weChatPayInstance->chain('v3/fund-app/mch-transfer/transfer-bills')->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {
			return false;
		}
		$response = empty($response) ? array() : json_decode($response, true);
		return array(
			'outBillNo' => $out_bill_no,
			'transferInfo' => $response,
			'state' => empty($response['state']) ? '' : $response['state'],
			'mchId' => $weChatConf['merchantId'],
			'appId' => $weChatConf['appId'],
			'packageInfo' => empty($response['package_info']) ? '' : $response['package_info'],
		);
	}
	
	/**
	 * 撤销转账（微信）
	 *
	 * @return array
	 */
	public function wxTransferCancel($withdrawEtt)
	{
		$data = array();
		try {
			$response = self::$weChatPayInstance->chain("v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$withdrawEtt->outBillNo}/cancel")->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {
			return false;
		}
		$response = empty($response) ? array() : json_decode($response, true);
		return true;
	}
	
	/**
	 * 转账信息查询（微信）
	 *
	 * @return array
	 */
	public function getwxTransferInfo($withdrawEtt)
	{
		$data = array();
		try {
			$response = self::$weChatPayInstance->chain("v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$withdrawEtt->outBillNo}")->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {
			return false;
		}
		$response = empty($response) ? array() : json_decode($response, true);
		$status = \constant\Order::BROKERAGE_STATUS_RECEIVED; // 已到账
		if (!empty($response['state']) && $response['state'] == 'WAIT_USER_CONFIRM') { // 等待用户确认
			$response['status'] = \constant\Order::BROKERAGE_STATUS_APPROVE;
		} 
		return $response;
	}
	
	/**
	 * 生成支付签名
	 * 
	 * @return array
	 */
	private function getPaySign($weChatConf, $prepayId)
	{
		$serverId = self::$instance->frame->conf['id'];
		$apiclientDir = CONFIGS_PATH . 'apiclient_' . $serverId . DS;
		$merchantPrivateKeyInstance = Rsa::from('file://' . $apiclientDir . 'apiclient_key.pem');
		$params = array(
			'appId' 	=> $weChatConf['appId'],
			'timeStamp' => (string)Formatter::timestamp(),
			'nonceStr' 	=> Formatter::nonce(),
			'package' 	=> 'prepay_id=' . $prepayId,
		);
		$params += ['paySign' => Rsa::sign(
			Formatter::joinedByLineFeed(...array_values($params)),
			$merchantPrivateKeyInstance
		), 'signType' => 'RSA'];
		return $params;
	}
	
	/**
	 * 微信虚拟支付 获取 paySig(虚拟支付)
	 * 算法：to_hex(hmac_sha256(appKey, uri + '&' + signData))
	 * @param array $signData 签名字段数组（会自动按 key=value 拼接并排序）
	 * @param string $appKey 平台密钥 appKey
	 * @param string $uri 请求接口 URI 如 /xpay/get_prepay_id
	 * @return string paySig 最终签名
	 */
	private function getVirtualPaySig($signData, $appKey, $uri)
	{
		// 1. 参数按 key 字典序升序排序
		ksort($signData);
		// 2. 拼接成 key1=value1&key2=value2 格式
		$signStr = '';
		foreach ($signData as $key => $value) {
			$signStr .= $key . '=' . $value . '&';
		}
		$signStr = rtrim($signStr, '&');
		// 3. 拼接原文：uri + & + 签名字符串
		$rawStr = $uri . '&' . $signStr;
		// 4. hmac_sha256 加密
		$hmac = hash_hmac('sha256', $rawStr, $appKey, true);
		// 5. 转 16 进制小写（微信要求）
		$paySig = bin2hex($hmac);
		return $paySig;
	}
	
	/**
	 * 微信虚拟支付 获取 paySig(虚拟支付)
	 * 
	 * @return string
	 */
	private function getVirtualSignature($signData, $sessionKey)
	{
		ksort($signData);
		$signStr = '';
		foreach ($signData as $key => $value) {
			$signStr .= $key . '=' . $value . '&';
		}
		$signStr = rtrim($signStr, '&');
		$rawStr = $signStr;
		$hmac = hash_hmac('sha256', $rawStr, $sessionKey, true);
		$paySig = bin2hex($hmac);
		return $paySig;
	}
	
	/**
	 * 生成支付签名(虚拟支付)
	 *
	 * @return array
	 */
	private function getPaySignVirtual($weChatConf, $prepayId, $data, $sessionKey)
	{
		$weChatConf = $this->frame->conf['weChat'];
		$buyQuantity = ($data['amount']['total'] * 0.01) / $weChatConf['goodsPrice']; // 购买数量

		$serverId = self::$instance->frame->conf['id'];
		$apiclientDir = CONFIGS_PATH . 'apiclient_' . $serverId . DS;
		$merchantPrivateKeyInstance = Rsa::from('file://' . $apiclientDir . 'apiclient_key.pem');
		$signData = array( // 具体支付参数见signData
			'offerId' => $weChatConf['offerId'], // mp-支付基础配置中的offerid
			'buyQuantity' => $buyQuantity, // 购买数量
			'currencyType' => 'CNY', // 币种
			'productId' => $weChatConf['productId'], // 道具ID, **该字段仅mode=short_series_goods时需要必填**
			'goodsPrice' => intval($weChatConf['goodsPrice'] * 100), // 道具单价(分)
			'activitySellingPrice' => intval($weChatConf['goodsPrice'] * 100), // 道具优惠价格（分），**非必填，该字段需与goodsPrice一起传入**。如用户使用优惠券、积分等，需要以低于道具价格下单时可传入，传入后该价格即为实际下单价格。
			'outTradeNo' => $data['out_trade_no'],
			'attach' => 'testdata',
		);
$uri = '/xpay/query_order';
		$mode = 'short_series_goods'; // 支付的类型 道具直购
		$paySig = self::getVirtualPaySig($signData, $weChatConf['appKey'], $uri);
		$signature = self::getVirtualSignature($signData, $sessionKey);
		return array(
			'signData' => $signData,
			'mode' => $mode,
			'paySig' => $paySig,
			'signature' => $signature,
		);
	}
	
	/**
	 * 微信JSAPI 支付
	 *
	 * @return array
	 */
	public function prepare($userEtt, $orderEtt, $description, $needProfitSharing = false)
	{
		$weChatConf = $this->frame->conf['weChat'];
		$notify_url = $this->frame->conf['serve_url'] . '/order/payNotify';
		$actualAmount = ceil(100 * max(0, $orderEtt->price - $orderEtt->redPacketValue));
		$data = array(
			'mchid' 	   => $weChatConf['merchantId'], // 服务商商户号  必填
			'out_trade_no' => $orderEtt->outTradeNo, // 商户订单号
			'appid'        => $weChatConf['appId'], // 服务商APPID
			'description'  => $description, // 商品描述
			'notify_url'   => $notify_url, // 商户回调地址
			'amount' 	   => array('total' => $actualAmount, 'currency' => 'CNY'), // 订单金额
			'payer'        => array('openid' => $userEtt->openid) // 用户在服务商appid下的唯一标识
		);
		if (!empty($needProfitSharing)) { // 分账
			$data['settle_info'] = array(
				'profit_sharing' => true,
			);
		}
		try {
			$response = self::$weChatPayInstance->chain('v3/pay/transactions/jsapi')->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {
			return false;
		}

		$response = empty($response) ? '' : json_decode($response, true);
		$prepayId = empty($response['prepay_id']) ? '' : $response['prepay_id']; //
		if (empty($prepayId)) {
			return false;
		}
//$prepayId = 'wx13142238646189f86b2285607b13f10001';
		// 获取sign
		$result = $this->getPaySignVirtual($weChatConf, $prepayId, $data, $userEtt->session_key);
		return $result;
	}

	/**
	 * 获取客户端IP
	 * 
	 * @return string
	 */
	private static function getClientIP()
	{
		if (@$_SERVER["HTTP_ALI_CDN_REAL_IP"]) {
			$ip = $_SERVER["HTTP_ALI_CDN_REAL_IP"];
		} elseif (@$_SERVER["HTTP_X_FORWARDED_FOR"] ?: false) {
			$ips = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
			$ip = $ips[0];
		} elseif (@$_SERVER["HTTP_CDN_SRC_IP"] ?: false) {
			$ip = $_SERVER["HTTP_CDN_SRC_IP"];
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$ip = str_replace(['::ffff:', '[', ']'], ['', '', ''], $ip);
		return $ip;
	}
	
	/**
	 * 添加分账关系
	 *
	 * @return array
	 */
	public function profitsharingReceiversAdd($openid)
	{
		$weChatConf = $this->frame->conf['weChat'];
		$data = array(
			'appid' => $weChatConf['appId'], // 服务商APPID
			'type' => 'PERSONAL_OPENID',
			'account' => $openid,
			'relation_type' => 'DISTRIBUTOR',
		);
		try {
			$response = self::$weChatPayInstance->chain('v3/profitsharing/receivers/add')->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {
			return false;
		}
		return $openid;
	}
	
	/**
	 * 处理分账
	 *
	 * @return array
	 */
	public function profitsharing($orderEtt)
	{
		if (empty($orderEtt->transactionId)) {
			return false;
		}
		$userDao = \dao\User::singleton();
		$userEtt = $userDao->readByPrimary($orderEtt->userId);
		if (empty($userEtt) || empty($userEtt->parentUserId)) { // 不需要分账
			return false;
		}
		$profitSharingDao = \dao\ProfitSharing::singleton();
		$profitSharingEttList = $profitSharingDao->readListByIndex(array(
			'orderId' => $orderEtt->id,
		));
		if (empty($profitSharingEttList)) {
			return false;
		}
		$vipConfigDao = \dao\VipConfig::singleton();
		$vipConfigEtt = $vipConfigDao->readByPrimary($orderEtt->goodsId);
		if (empty($vipConfigEtt) || $vipConfigEtt->status == \constant\Common::DATA_DELETE) {
			return false;
		}
		$now = $this->frame->now;
		// 添加分账关系
		$receivers = array();
		foreach ($profitSharingEttList as $key => $profitSharingEtt) {
			$profitSharingUserEtt = $userDao->readByPrimary($profitSharingEtt->userId);
			if (empty($profitSharingUserEtt)) { // 不需要分账
				unset($profitSharingEttList[$key]);
				continue;
			}
			if (empty($profitSharingEtt->receiverAddOpenId)) {
				$receiverAddOpenId = $this->profitsharingReceiversAdd($profitSharingUserEtt->receiverAddOpenId);
				if (empty($receiverAddOpenId)) {
					unset($profitSharingEttList[$key]);
					continue;
				}
				$profitSharingEtt->set('updateTime', $now);
				$profitSharingEtt->set('receiverAddOpenId', $receiverAddOpenId);
				$profitSharingDao->update($profitSharingEtt);
			}
			$amount = 0;
			$description = '';
			if ($profitSharingEtt->fromUserId == $orderEtt->userId) { // 上级
				$amount = intval($vipConfigEtt->topProfitSharing1 * 100);
				$description = '直接分账';
			} else { // 上上级
				$amount = intval($vipConfigEtt->topProfitSharing2 * 100);
				$description = '间接分账';
			}
			if ($profitSharingEtt->status == 1) { // 已完成分账
				unset($profitSharingEttList[$key]);
				continue;
			}
			$receivers[] = array(
				'type' 	=> 'PERSONAL_OPENID', // 个人openid
				'account' => $profitSharingEtt->receiverAddOpenId, // 个人OpenID
				'amount' => $amount,
				'description' => $description
			);
		}
		if (empty($receivers)) {
			return false;
		}
		// 处理分账
		$weChatConf = $this->frame->conf['weChat'];
		$data = array(
			'mchid' 	   		=> $weChatConf['merchantId'], // 服务商商户号  必填
			'out_order_no' 		=> $orderEtt->outTradeNo, // 商户订单号
			'appid'        		=> $weChatConf['appId'], // 服务商APPID
			'transaction_id'  	=> $orderEtt->transactionId,
			'receivers' 		=> $receivers,
			'unfreeze_unsplit' 	=> true,
		);
		try {
			$response = self::$weChatPayInstance->chain('v3/profitsharing/orders')->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
			
			
			print_r($response);
		} catch (\Exception $e) {
			
			print_r($e);
			return false;
		}
		foreach ($profitSharingEttList as $profitSharingEtt) {
			// 添加火币
			$profitSharingUserEtt = $userDao->readByPrimary($profitSharingEtt->userId);
			if (empty($profitSharingUserEtt)) { // 不需要分账
				continue;
			}
			if ($profitSharingEtt->status == 0) {
				$profitSharingUserEtt->add('gold', $profitSharingEtt->addGold);
				$profitSharingUserEtt->set('updateTime', $now);
				$userDao->update($profitSharingUserEtt);
			}
			// 修改分账状态
			$profitSharingEtt->set('status', 1);
			$profitSharingEtt->set('updateTime', $now);
			$profitSharingDao->update($profitSharingEtt);
		}
		return true;
	}
	
}