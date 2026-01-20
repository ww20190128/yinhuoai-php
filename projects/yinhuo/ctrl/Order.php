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
    	$paymentType = $this->paramFilter('paymentType', 'intval'); // 支付类型  1 微信支付
    	$redirectUrl = $this->paramFilter('redirectUrl', 'string'); // 跳转URL
    	if (empty($orderId) || empty($redirectUrl) || empty($paymentType)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$couponId = $this->paramFilter('couponId', 'intval', 0); // 优惠券Id
    	$info = array(
    		'paymentType' => $paymentType,
    		'tradeType' => $tradeType,
    		'redirectUrl' => $redirectUrl,
    	);
    	$orderSv = \service\Order::singleton();
    	return $orderSv->vipOrderPay($userId, $orderId, $info, $couponId);
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

// $tmpData = '{"header":{"TEMP":"\/tmp","TMPDIR":"\/tmp","TMP":"\/tmp","PATH":"\/usr\/local\/bin:\/usr\/bin:\/bin","HOSTNAME":"iZ2zege6fs9ot6hgcl821aZ","USER":"www","HOME":"\/home\/www","HTTP_WECHATPAY_TIMESTAMP":"1731035750","HTTP_PRAGMA":"no-cache","HTTP_WECHATPAY_SIGNATURE_TYPE":"WECHATPAY2-SHA256-RSA2048","HTTP_WECHATPAY_SIGNATURE":"WYpA8CsznEXuB8zy6hiDnq4JXWt0HugKYPDiJ\/y1z\/J8Ab7hlnu1j7IjstS+b885h5fDRHg6tnnyeCaydJITnLEPahLRgxzLIlMcVaCBdpYxnZ04d2K9FEmNds9KNiZRIV56am\/kS7H58TCh+KFacU0aANwNOlWHv+4UG+ByL1CZYHD5lWB+m8M39l8Ag1A5F6eEbp9RhA2\/YgH8u9WfIuxOGtAd0Ihfl6it4Xq1jM6vAIM9sCKgxpDfPPlyLvIqXZJ9TJ7YMLJlBnQqsyqkLFfvrRSbqHDrsWlrKAEr7\/wuAaCEqRT\/HbzCGOvjGOM0BuJoR4rCUtMkz1v5Gi7QqQ==","HTTP_WECHATPAY_SERIAL":"775099B06253CAC60E2244C28D0AB82D519E823A","HTTP_CONTENT_TYPE":"application\/json","HTTP_WECHATPAY_NONCE":"CzFfIFUharlmztUMxXLaRMA8aKQ86D6j","HTTP_HOST":"serve.zhile.ink","HTTP_ACCEPT":"*\/*","HTTP_USER_AGENT":"Mozilla\/4.0","HTTP_CONNECTION":"Keep-Alive","HTTP_CONTENT_LENGTH":"911","REDIRECT_STATUS":"200","SERVER_NAME":"serve.zhile.ink","SERVER_PORT":"443","SERVER_ADDR":"172.20.73.12","REMOTE_PORT":"36605","REMOTE_ADDR":"101.226.103.24","SERVER_SOFTWARE":"nginx\/1.21.6","GATEWAY_INTERFACE":"CGI\/1.1","HTTPS":"on","REQUEST_SCHEME":"https","SERVER_PROTOCOL":"HTTP\/1.1","DOCUMENT_ROOT":"\/data\/www\/phpItemSet-mood\/Webroot","DOCUMENT_URI":"\/index.php","REQUEST_URI":"\/order\/payNotify","SCRIPT_NAME":"\/index.php","CONTENT_LENGTH":"911","CONTENT_TYPE":"application\/json","REQUEST_METHOD":"POST","QUERY_STRING":"","SCRIPT_FILENAME":"\/data\/www\/phpItemSet-mood\/Webroot\/index.php","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1731035750.215225,"REQUEST_TIME":1731035750},"body":"{\"id\":\"2fbe72bd-4318-56a4-9233-82ea57410f50\",\"create_time\":\"2024-11-08T11:15:49+08:00\",\"resource_type\":\"encrypt-resource\",\"event_type\":\"TRANSACTION.SUCCESS\",\"summary\":\"\u652f\u4ed8\u6210\u529f\",\"resource\":{\"original_type\":\"transaction\",\"algorithm\":\"AEAD_AES_256_GCM\",\"ciphertext\":\"agda\/i\/T+5nhRgiuJM0s3J37lKlE5MUEBN54QthARMItl7Wgg0QPFTxKXrvSk8j+d4Ry+myN2o1xzGD5ty0Lbxr2QjCqCFkkKqQ+kijdn6Z\/qOP973cqn3vuEsP9Wpns0aY+wXN461imnlgFay3doQPOLKyAQHh+h1Lgw4\/QW8AryYWD8h\/ZtldTwC1uDzslBwj5q84kp4xNM2cHV7q2h\/pa6\/KYLmkRzfq7+8IdmMjum71SrPjtZ7BcxlI0KLNYN+6ITHy79dpTWET4n5\/09dxEol2yZUy6LJc5\/RqhfFAn7lVIN+em1UpVN\/xky7ceR4602T\/Ypg6FfF0xlOq5ytfXD+TQwvs\/Ln8ix9iuCv9wlFnMC5Y9wgHG8v\/WlrQaVddJm52lGWQD+h90qn87J8dT956YpS3DkQrV0iwDvuEhMTwRt5Jo5PCf5Z8HLq3sgjTQrl4n11dgenkue0wKgLLSUrox0kp2HJovg7F1yMU4QeQS3CQdonqC4BOlV0O6vPI2HK5df6v5w2V7qmsS2fm4wh761YY7evMEranGRvDMAPHXdhmClSKUFwRyQzxqPUsTHE8=\",\"associated_data\":\"transaction\",\"nonce\":\"gpXaZpJ710lB\"}}","params":{"op":"Order.payNotify"}}';
// 		$tmpData = json_decode($tmpData, true);
// 		$header = $tmpData['header'];
// 		$bodyJson = $tmpData['body'];


// $file = CACHE_PATH . 'payNotify.txt';
// @file_put_contents($file, json_encode(array(
//     		'header' => $header,
//     		'body' => $bodyJson,
//     		'params' => $params,	
//     	)));
    	
    	
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
		return true;
    }
    
}