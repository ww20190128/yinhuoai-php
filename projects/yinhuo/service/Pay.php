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
            // 触发分账
            $this->profitsharing($orderEtt, $transaction_id);
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
			
			print_r($e);exit;
			return false;
		}
    	
		$response = empty($response) ? '' : json_decode($response, true);
		$prepayId = empty($response['prepay_id']) ? '' : $response['prepay_id']; // 
		if (empty($prepayId)) {
			return false;
		}
		// 获取sign
		$result = $this->getPaySign($weChatConf, $prepayId);
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
	 * 处理分账
	 *
	 * @return array
	 */
	private function profitsharing($orderEtt, $transactionId)
	{
		$userDao = \dao\User::singleton();
		$userEtt = $userDao->readByPrimary($orderEtt->userId);
		if (empty($userEtt) || empty($userEtt->parentUserId)) { // 不需要分账
			return false;
		}
		$parentUserEtt = $userDao->readByPrimary($userEtt->parentUserId);
		if (empty($parentUserEtt)) { // 不需要分账
			return false;
		}
		$profitsharingArr = array();
		// 1级
		$profitsharingArr[] = array(
			'userId' => $userEtt->parentUserId,
			'amount' => 0.02,
			'openid' => $userEtt->openid,
			'description' => "{$userEtt->userName}的充值分账",
		);
		if (!empty($parentUserEtt->parentUserId)) {
			$parentUserEtt2 = $userDao->readByPrimary($parentUserEtt->parentUserId);
			if (!empty($parentUserEtt2)) { // 不需要分账
				// 2级
				$profitsharingArr[] = array(
					'userId' => $parentUserEtt2->parentUserId,
					'amount' => 0.01,
					'openid' => $parentUserEtt2->openid,
					'description' => "{$userEtt->userName}的充值分账",
				);
			}
		}
		// 处理分账
		$weChatConf = $this->frame->conf['weChat'];
		$receivers = array(); // 接收方信息
		foreach ($profitsharingArr as $row) {
			$receivers[] = array(
				'type' 	=> 'PERSONAL_OPENID', // 个人openid
				'account' => $row['openid'], // 个人OpenID
				'amount' => array('total' => $row['amount'], 'currency' => 'CNY'), // 订单金额
				'description' => $row['description']
			);
		}
		$data = array(
			'mchid' 	   		=> $weChatConf['merchantId'], // 服务商商户号  必填
			'out_trade_no' 		=> $orderEtt->outTradeNo, // 商户订单号
			'appid'        		=> $weChatConf['appId'], // 服务商APPID
			'transaction_id'  	=> $transactionId,
			'unfreeze_unsplit' 	=> true,
			'receivers' 		=> $receivers,
		);
		try {
			$response = self::$weChatPayInstance->chain('v3/pay/profitsharing/orders')->post(array('json' => $data));
			$response = empty($response) ? '' : $response->getBody()->getContents();
		} catch (\Exception $e) {

			$file = CACHE_PATH . 'profitsharing.txt';
			@file_put_contents($file, json_encode($e));
			return false;
		}

		$file = CACHE_PATH . 'profitsharing2.txt';
		@file_put_contents($file, json_encode($response));
		return true;
	}
}