<?php
namespace ctrl;

/**
 * 订单
 *
 * @author
 */
class Order extends CtrlBase
{
    /**
     * 创建vip支付订单
     * 
     * @return array
     */
    public function createVipOrder()
    {
        $params = $this->params;
        $vipId = $this->paramFilter('vipId', 'intval'); // vipID  必填
        if (empty($vipId)) {
            throw new $this->exception('请求参数错误');
        }
        // 设备信息
        $deviceInfo = array( 
            'phoneModel'        => $this->paramFilter('phoneModel', 'string'),
            'browserVersion'    => $this->paramFilter('browserVersion', 'string'),
            'network'           => $this->paramFilter('network', 'string'),
            'screenResolution'  => $this->paramFilter('screenResolution', 'string'),
            'hasParams'         => $this->paramFilter('hasParams', 'string'),
            'useEnv'            => $this->paramFilter('useEnv', 'intval'),
        );
        $info = array(
            'couponId' => $this->paramFilter('couponId', 'intval', 0), // 优惠券
        );
        $userId = empty($this->userId) ? 0 : $this->userId;
        $orderSv = \service\Order::singleton();
        return $orderSv->createVipOrder($userId, $vipId, $deviceInfo, $info);
    }
    
    /**
     * vip订单支付
     * 
     * @return array
     */
    public function vipOrderPay()
    {
    	$params = $this->params;
    	$userId = empty($this->userId) ? 0 : $this->userId;
    	$orderId = $this->paramFilter('orderId', 'intval');  // 订单ID
    	if (empty($orderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$couponId = $this->paramFilter('couponId', 'intval', 0); // 优惠券Id
    	$orderSv = \service\Order::singleton();
    	return $orderSv->vipOrderPay($userId, $orderId, $couponId);
    }
   
    /**
     * 检查vip订单是否需要支付
     *
     * @return array
     */
    public function checkVipOrderPay()
    {
    	$params = $this->params;
    	$orderId = $this->paramFilter('orderId', 'intval'); // 订单Id
    	if (empty($orderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$orderSv = \service\Order::singleton();
    	return $orderSv->checkVipOrderPay($this->userId, $orderId);
    }
    
    
    /**
     * 支付通知（腾讯）
     *
     * @return array
     */
    public function payNotify()
    {
    	$params = $this->params;
    	$header = empty($_SERVER) ? array() : $_SERVER;
    	$bodyJson = file_get_contents('php://input');

// $tmpData = '{"header":{"TEMP":"\/tmp","TMPDIR":"\/tmp","TMP":"\/tmp","PATH":"\/usr\/local\/bin:\/usr\/bin:\/bin","HOSTNAME":"iZ2zebb122t63qsoruf52iZ","USER":"www","HOME":"\/home\/www","HTTP_WECHATPAY_TIMESTAMP":"1778418119","HTTP_PRAGMA":"no-cache","HTTP_WECHATPAY_SIGNATURE_TYPE":"WECHATPAY2-SHA256-RSA2048","HTTP_WECHATPAY_SIGNATURE":"r1DS1IYqHz2OsoHDvivy31XKPsF30mUkPu9ovihmKa2jm8qypDRYq9x8vBd43rDr8kogU+6EjtMDYWMYufTg20TErdvaKFQh9KGtSphRZdhd+7UWkTaO4jN+7GlIgorrEptcwb0Age1OK+YdM3U+KWbyszOVZiIZQK\/NRkUNO9CQmRzysKETza2OZazt+R5ZZr39mZi34gK0r09Vcp4eskBvmYI+tNfH1g3ogwVoMWTbKGN3lGtGN946bKfCWfPlZG\/kCr0aB0mx6Ir7s+b9QiqzUglDOJ+z8tDGF5muHeRsjyN9L6KmF6GrtRENxQnnr4zJBEMbXkHbSUJSFQhofg==","HTTP_WECHATPAY_SERIAL":"1B84A18252BFC94060DF9B551587288FC432AC84","HTTP_CONTENT_TYPE":"application\/json","HTTP_WECHATPAY_NONCE":"2NcrUduTuWBktQkRu0ccfLLRg4olf2Sy","HTTP_HOST":"server.yinhuoai.com","HTTP_ACCEPT":"*\/*","HTTP_USER_AGENT":"Mozilla\/4.0","HTTP_CONNECTION":"Keep-Alive","HTTP_CONTENT_LENGTH":"915","REDIRECT_STATUS":"200","SERVER_NAME":"server.yinhuoai.com","SERVER_PORT":"443","SERVER_ADDR":"172.30.244.95","REMOTE_PORT":"16508","REMOTE_ADDR":"101.226.103.17","SERVER_SOFTWARE":"nginx\/1.27.0","GATEWAY_INTERFACE":"CGI\/1.1","HTTPS":"on","REQUEST_SCHEME":"https","SERVER_PROTOCOL":"HTTP\/1.1","DOCUMENT_ROOT":"\/data\/www\/yinhuoai-php\/Webroot","DOCUMENT_URI":"\/index.php","REQUEST_URI":"\/order\/payNotify","SCRIPT_NAME":"\/index.php","CONTENT_LENGTH":"915","CONTENT_TYPE":"application\/json","REQUEST_METHOD":"POST","QUERY_STRING":"","SCRIPT_FILENAME":"\/data\/www\/yinhuoai-php\/Webroot\/index.php","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1778418119.842513,"REQUEST_TIME":1778418119},"body":"{\"id\":\"da51dd9b-cfdd-5676-b5c4-7335c71dcff6\",\"create_time\":\"2026-05-10T21:01:59+08:00\",\"resource_type\":\"encrypt-resource\",\"event_type\":\"TRANSACTION.SUCCESS\",\"summary\":\"\u652f\u4ed8\u6210\u529f\",\"resource\":{\"original_type\":\"transaction\",\"algorithm\":\"AEAD_AES_256_GCM\",\"ciphertext\":\"7G1GU6C\/T0+0nbTddfCLEP7t\/8lUqxeytUQXIknCUjDzyB2dYlx86kJnvIGXBifkRZThnspym8C8ko9l9hggHmk2e9kc+r42ND8FIn0qIch9KZl2DTwIg9ewm4XI2XOwk5aD\/L0jzdlv1mQD4Ocah\/B6T2JVijWrrbGpUP1Rq5VsKuzIQMQwiakqlZlb1R4kBsiwPP88PsxHtNs+kO8aoOMVp449HDh6JyOOTyMS4n7nyryAeceJh2mhBNd\/5HSnxE7rKuBjoyaMwlaSJ6ZmqBlcPzSDRIjOg19IvgByMQq3JBT\/3IBxjzPCVyahTcy8QoVjL9nNmEWtCBxSOtliFAPy7xydMRjir9S4RsdlIv\/b1pEufCniQtr1CG0mJfHBQhBztPGyfug9t49jFTcdNHiyhWUxTbyFp7JatkjcKAOeMUX5nu\/xnKVmDExoVhXUqo7VMScMO4dKQEC8clpqlmdHrKlVN5NQsO5xJYScvREI18TybWNRYQFJvEEcGPGQqgtTXr0\/VasjVRRvvs3824KR5QnzHYBWkQREI8BC6BLCkaHxSNu0GlGJZjYa0jCSIY9eNyDqQA==\",\"associated_data\":\"transaction\",\"nonce\":\"wKOVuq2kqCjw\"}}","params":{"op":"Order.payNotify"}}';
// 		$tmpData = json_decode($tmpData, true);
// 		$header = $tmpData['header'];
// 		$bodyJson = $tmpData['body'];


$file = CACHE_PATH . 'payNotify.txt';
@file_put_contents($file, json_encode(array(
    		'header' => $header,
    		'body' => $bodyJson,
    		'params' => $params,	
    	)));

    	
    	$body = empty($bodyJson) ? array() : json_decode($bodyJson, true);
    	$resource = empty($body['resource']) ? array() : $body['resource'];
    	if (empty($body['id']) || empty($body['event_type']) || $body['event_type'] != 'TRANSACTION.SUCCESS') {
    		return false;
    	}
    	if (empty($resource['ciphertext']) || empty($resource['nonce']) || empty($resource['associated_data'])) {
    		return false;
    	}
    	$info = array(
    		'payTime' => $header['HTTP_WECHATPAY_TIMESTAMP'],
    		'signatureType' => $header['HTTP_WECHATPAY_SIGNATURE_TYPE'],
    		'signature' => $header['HTTP_WECHATPAY_SIGNATURE'],
    		'serial' => $header['HTTP_WECHATPAY_SERIAL'],
    		'nonce' => $header['HTTP_WECHATPAY_NONCE'],
    	);

    	$paySv = \service\Pay::singleton();
    	return $paySv->wxPayNotify($resource, $bodyJson, $info);
    }

    /**
     * 申请提现，发起转账（微信）
     *
     * @return array
     */
    public function wxTransfer()
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary(7);
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEtt = $withdrawDao->readByPrimary(5);
    	
    	$paySv = \service\Pay::singleton();
    	return $paySv->wxTransfer($userEtt, $withdrawEtt);
    }
    
    /**
     * 撤销转账（微信）
     *
     * @return array
     */
    public function wxTransferCancel()
    {
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEtt = $withdrawDao->readByPrimary(5);
    	$paySv = \service\Pay::singleton();
    	return $paySv->wxTransferCancel($withdrawEtt);
    }
    
    /**
     * 微信提现通知
     * 
     * @return array
     */
    public function wxTransferNotify()
    {
    	$params = $this->params;
    	$body = file_get_contents('php://input');
    
// $file = CACHE_PATH . 'xcTransferNotify.txt';
// @file_put_contents($file, $body);

//$body = '{"id":"9bacbd00-5c83-5bee-afc5-ff3a91094a8a","create_time":"2025-04-07T15:09:07+08:00","resource_type":"encrypt-resource","event_type":"MCHTRANSFER.BILL.FINISHED","summary":"商家转账单据终态通知","resource":{"original_type":"mch_payment","algorithm":"AEAD_AES_256_GCM","ciphertext":"Yl2D7u1iopCs9YP4mfyloNWkSWH6iLW65bec29dcgzVlbXnnslX63oMEG/gpMJx7ONMGG8FtLpoJFcjn00kaq/1iqxwdxoQQA4usVovUqKetuAlmKHqcd1p23U5m/aFI/TZSQnNGFntJQQA5PBqBp2hVNI4XSA4vMdKOauJ+L3HMOKF+DpNE3xGYNANTZ0gPEo1WCRQ26DTFuy2cClckQ1yeMJv0E0/QlzGbe0FQZYvfgL6ygBaMn35M4nldiPtanfoomxmAFXj/dh5ySrTAUpR0Yflnh3ojNRvLEtMh53MiSXSMXxC9zAgLpzoe8JDGaryEhESv0TdHSNzi0nXs+efWB9DZoKc5AfyJyE35FgrXzmL9PsJ0wqbVChmm0w8ofwcnQP3AfPyWGSNDU1o4vtBQtGTWRQGuSeZa59k=","associated_data":"mch_payment","nonce":"wsSDmYr377VK"}}';    	
    	$bodyArr = empty($body) ? array() : json_decode($body, true);
    	$ciphertext = empty($bodyArr['resource']['ciphertext']) ? '' : base64_decode($bodyArr['resource']['ciphertext']);

		if (empty($ciphertext)) {
			return false;
		}
		$keyLengthByte = 16;
    	if (strlen($ciphertext) <= $keyLengthByte) {
    		return false;
    	}
    	$ctext = substr($ciphertext, 0, -1 * $keyLengthByte);
    	$authTag = substr($ciphertext, -1 * $keyLengthByte);
  
    	$weChatConf = $this->frame->conf['weChat'];
    	$aesKey = $weChatConf['APIv3Key'];
    	$nonceStr = $bodyArr['resource']['nonce'];
    	$associatedData = $bodyArr['resource']['associated_data'];

		$notifyResult = openssl_decrypt($ctext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
		$notifyResult = empty($notifyResult) ? array() : json_decode($notifyResult, true);

		if (empty($notifyResult) || empty($notifyResult['out_bill_no']) 
			|| empty($notifyResult['state'])|| $notifyResult['state'] != 'SUCCESS') {
			return false;
		}
		$now = $this->frame->now;
		// 获取申请记录
		$withdrawDao = \dao\Withdraw::singleton();
		$withdrawEtt = $withdrawDao->readListByIndex(array(
    	    'outBillNo' => $notifyResult['out_bill_no'],
    	), true);
		if (empty($withdrawEtt) || $withdrawEtt->status == \constant\Order::BROKERAGE_STATUS_RECEIVED) { // 已通知过
			return false;
		}
		$userDao = \dao\User::singleton();
		$userEtt = $userDao->readByPrimary($withdrawEtt->userId);
		if (empty($userEtt)) {
			return false;
		}
		$withdrawEtt->set('updateTime', $now);
		$withdrawEtt->set('status', \constant\Order::BROKERAGE_STATUS_RECEIVED); // 佣金状态：已到账
		$withdrawEtt->set('transferNotifyInfo', json_encode($notifyResult)); // 转账回调信息
		$withdrawDao->update($withdrawEtt);
		
		// 添加已提现金额
		$userEtt->add('withdrawAmount', $notifyResult['transfer_amount'] * 0.01);
		$userEtt->set('updateTime', $now);
		$userDao->update($userEtt);
		
		
		// 处理分账
		$orderSv = \service\Order::singleton();
		$orderSv->profitsharing($this->userId, $info, $pageNum, $pageLimit);
		return true;
    }
    
    /**
     * 获取订单列表
     *
     * @return array
     */
    public function getOrderList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$searchStartTime = $this->paramFilter('searchStartTime', 'intval'); // 开始时间
		$searchEndTime = $this->paramFilter('searchEndTime', 'intval'); // 结束时间
		$searchStatus = $this->paramFilter('searchStatus', 'intval'); // 支付状态
		$info = array(
			'searchStatus' 	  => $searchStatus,
		    'searchStartTime' => empty($searchStartTime) ? 0 : strtotime($searchStartTime),
		    'searchEndTime'   => empty($searchEndTime) ? 0 : strtotime($searchEndTime) + 86399,
		);
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	$orderSv = \service\Order::singleton();
    	return $orderSv->getOrderList($this->userId, $info, $pageNum, $pageLimit);
    }
    
}