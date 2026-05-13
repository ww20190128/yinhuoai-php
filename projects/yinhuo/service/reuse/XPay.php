<?php
namespace service\reuse;
require_once 'config.php';
require_once 'vendor/autoload.php';

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WeChatPay\Formatter;

/**
 * User 通用类
 *
 * @author wangwei
 */
class User extends \service\ServiceBase
{
	/** @var WeChatPay */
	private static $weChatPayInstance = null;
	
	/** @var string AppKey（从 mp-支付基础配置 获取） */
	private static $appKey = '';
	
	/**
	 * 初始化
	 */
	public static function init(string $appKey)
	{
		self::$appKey = $appKey;
	}
	
	/**
	 * 计算 pay_sig（后台签名）
	 * 公式: hmac_sha256_hex(appKey, uri . '&' . postBody)
	 */
	public static function calcPaySig(string $uri, string $postBody): string
	{
		$msg = $uri . '&' . $postBody;
		return hash_hmac('sha256', $msg, self::$appKey);
	}
	
	/**
	 * 计算 signature（用户登录态签名）
	 * 公式: hmac_sha256_hex(sessionKey, postBody)
	 */
	public static function calcSignature(string $postBody, string $sessionKey): string
	{
		return hash_hmac('sha256', $postBody, $sessionKey);
	}
	
	/**
	 * 获取 access_token（通过 authorizer_access_token）
	 */
	public static function getAccessToken(): string
	{
		// 方式1：直接用现成的 authorizer_access_token
		// return $GLOBALS['authorizer_access_token'];
	
		// 方式2：通过 component_access_token 换取
		$componentAccessToken = self::getComponentAccessToken();
		$authorizerAppid = WX_APPID;
		$authorizerRefreshToken = self::getAuthorizerRefreshToken();
	
		$url = "https://api.weixin.qq.com/wxa/getavailableauthority?access_token={$componentAccessToken}";
		$response = self::httpPost($url, json_encode([
				'authorizer_appid' => $authorizerAppid,
				'authorizer_refresh_token' => $authorizerRefreshToken,
		]));
	
		$data = json_decode($response, true);
		return $data['authorizer_access_token'] ?? '';
	}
	
	/**
	 * 查询代币余额
	 * POST /xpay/query_user_balance
	 */
	public static function queryBalance(string $openid, string $sessionKey, int $env = 0, string $userIp = ''): array
	{
		$uri = '/xpay/query_user_balance';
		$postBody = json_encode([
				'openid'  => $openid,
				'env'     => $env,
				'user_ip' => $userIp ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
		], JSON_UNESCAPED_UNICODE);
	
		return self::xpayRequest($uri, $postBody, $sessionKey);
	}
	
	/**
	 * 扣减代币（代币支付开通VIP）
	 * POST /xpay/currency_pay
	 */
	public static function currencyPay(
			string $openid,
			string $sessionKey,
			int    $amount,
			string $orderId,
			string $payItem = '',
			string $remark  = 'VIP开通',
			int    $env     = 0,
			string $userIp  = ''
	): array {
		$uri = '/xpay/currency_pay';
		$postBody = json_encode([
				'openid'   => $openid,
				'env'      => $env,
				'user_ip'  => $userIp ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
				'amount'   => $amount,
				'order_id' => $orderId,
				'payitem'  => $payItem,
				'remark'   => $remark,
		], JSON_UNESCAPED_UNICODE);
	
		return self::xpayRequest($uri, $postBody, $sessionKey);
	}
	
	/**
	 * 代币支付退款
	 * POST /xpay/cancel_currency_pay
	 */
	public static function cancelCurrencyPay(
			string $openid,
			string $sessionKey,
			string $payOrderId,
			string $refundOrderId,
			int    $amount,
			int    $env = 0,
			string $userIp = ''
	): array {
		$uri = '/xpay/cancel_currency_pay';
		$postBody = json_encode([
				'openid'       => $openid,
				'env'          => $env,
				'user_ip'      => $userIp ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
				'pay_order_id' => $payOrderId,
				'order_id'     => $refundOrderId,
				'amount'       => $amount,
		], JSON_UNESCAPED_UNICODE);
	
		return self::xpayRequest($uri, $postBody, $sessionKey);
	}
	
	/**
	 * 统一请求入口（核心）
	 * GET authorizer_access_token -> 加上 pay_sig 和 signature -> 请求微信
	 */
	private static function xpayRequest(string $uri, string $postBody, string $sessionKey): array
	{
		$accessToken = self::getAccessToken();
		$paySig      = self::calcPaySig($uri, $postBody);
		$signature   = self::calcSignature($postBody, $sessionKey);
	
		$url = 'https://api.weixin.qq.com' . $uri
		. '?access_token=' . urlencode($accessToken)
		. '&signature='    . urlencode($signature)
		. '&pay_sig='      . urlencode($paySig);
	
		$response = self::httpPost($url, $postBody);
		$result   = json_decode($response, true);
	
		if (($result['errcode'] ?? 0) !== 0) {
			throw new RuntimeException(
					'xpay error: ' . ($result['errmsg'] ?? 'unknown') . ' [' . ($result['errcode'] ?? -1) . ']'
			);
		}
	
		return $result;
	}
	
	private static function httpPost(string $url, string $data): string
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => $data,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
		CURLOPT_TIMEOUT        => 10,
		]);
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new RuntimeException('CURL Error: ' . curl_error($ch));
		}
		curl_close($ch);
		return $response;
	}
	
	private static function getComponentAccessToken(): string
	{
		// 第三方平台 component_access_token，需提前缓存/刷新
		// 此处用占位，具体实现替换为你自己的缓存逻辑
		return $GLOBALS['component_access_token'] ?? '';
	}
	
	private static function getAuthorizerRefreshToken(): string
	{
		return $GLOBALS['authorizer_refresh_token'] ?? '';
	}
}