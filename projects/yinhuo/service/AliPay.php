<?php
namespace service;
require_once('vendor/autoload.php');
use \Alipay\OpenAPISDK\ApiException;
use Alipay\OpenAPISDK\Util\AlipayConfigUtil;
use Alipay\OpenAPISDK\Util\GenericExecuteApi;
use Alipay\OpenAPISDK\Util\Model\AlipayConfig;
use Alipay\OpenAPISDK\Util\Model\CustomizedParams;
use Alipay\OpenAPISDK\Util\Model\OpenApiGenericRequest;
use GuzzleHttp\Client;
use Alipay\OpenAPISDK\Util\AlipaySignature;
/**
 * 支付宝支付
 *
 * @author
*/
class AliPay extends ServiceBase
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * 支付实例
	 *
	 * @var object
	 */
	private static $apiInstance;
	
	/**
	 * 单例模式
	 *
	 * @return AliPay
	 * 
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new AliPay();
			$alipayConfig = new AlipayConfig();
			$alipayConf = self::$instance->frame->conf['alipay'];
			$alipayConfig->setServerUrl('https://openapi.alipay.com');
			$alipayConfig->setAppId($alipayConf['appId']); // 应用Id
			$alipayConfig->setPrivateKey($alipayConf['private_key']); // 应用私钥
			$alipayConfig->setAlipayPublicKey($alipayConf['alipay_public_key']); // 支付宝公钥
			

			$alipayConfigUtil = new AlipayConfigUtil($alipayConfig);
			$apiInstance = new GenericExecuteApi(
			    $alipayConfigUtil,
			    new Client()
			);
			self::$apiInstance = $apiInstance;
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
	 * 支付宝h5  支付（pc端， 没用到）
	 * 
	 * @return array
	 */
	public function pcH5($userEtt = '', $orderEtt = '', $description = '')
	{
		$weChatConf = $this->frame->conf['weChat'];
		$notify_url = $this->frame->conf['serve_url'] . '/order/aliPayNotify';
		$actualAmount = max(0, $orderEtt->price - $orderEtt->redPacketValue);
		$data = array(
			'out_trade_no' 	=> $orderEtt->outTradeNo,
			'total_amount' 	=> $actualAmount,	
			'subject'		=> $description,
			'product_code'	=> 'FAST_INSTANT_TRADE_PAY',
			'notify_url'    => $notify_url,
			'qr_pay_mode'	=> 1,
		);
		// 构造请求参数以调用接口
		$bizParams = array(
			'biz_content' => $data,
		);
		try {
		    // 如果是第三方代调用模式，请设置app_auth_token（应用授权令牌）
			$pageRedirectionData = self::$apiInstance->pageExecute('alipay.trade.page.pay', 'POST', $bizParams, null);
			return $pageRedirectionData;
		} catch (ApiException $e) {
		    return false;
		}
	}
	
	/**
	 * 支付宝h5  支付（手机端）
	 *
	 * @return array
	 */
	public function wapH5($userEtt, $orderEtt, $description, $info = array())
	{
		$weChatConf = $this->frame->conf['weChat'];
		$notify_url = $this->frame->conf['serve_url'] . '/order/aliPayNotify';
		$actualAmount = max(0, $orderEtt->price - $orderEtt->redPacketValue);
		$data = array(
			'out_trade_no' 	=> $orderEtt->outTradeNo,
			'total_amount' 	=> $actualAmount,
			'subject'		=> $description,
			'product_code'	=> 'QUICK_WAP_WAY',
			
			'quit_url' 		=> $info['redirectUrl'], // 用户付款中途退出返回商户网站的地址
		);
		// 构造请求参数以调用接口
		$bizParams = array(
			'biz_content' => $data,
			'notify_url' => $notify_url,
			'return_url' => $info['redirectUrl'], // 用户付款成功的页面
		);
		try {
			// 如果是第三方代调用模式，请设置app_auth_token（应用授权令牌）
			$pageRedirectionData = self::$apiInstance->pageExecute('alipay.trade.wap.pay', 'POST', $bizParams, null);
			return $pageRedirectionData;
		} catch (ApiException $e) {
			return false;
		}
	}
	
	/**
	 * 支付通知回调
	 * 
	 * @return array
	 */
	public function payNotify($params)
	{
		$alipayConf = self::$instance->frame->conf['alipay'];
		// 支付宝公钥
		$alipayPublicKey = $alipayConf['alipay_public_key'];
	
		// 验签代码	$params 待验签的从支付宝接收到的参数Map
		$flag = AlipaySignature::rsaCheckV1($params, $alipayPublicKey);
		if (empty($flag)) {
			return false;
		}

		// 查找支付订单
		$orderDao = \dao\Order::singleton();
		$orderEtt = $orderDao->readListByIndex(array(
			'outTradeNo' => $params['out_trade_no'], // 订单ID
		), true);

		if (empty($orderEtt)) {
			return false;
		}
		// 完结订单
		$orderSv = \service\Order::singleton();
		$orderSv->finishOrder($orderEtt, $params, \constant\Order::PAY_STATUS_COMPLETE);
		return true;
	}
	
}