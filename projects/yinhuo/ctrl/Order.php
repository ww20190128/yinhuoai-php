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

$tmpData = '{"header":{"TEMP":"\/tmp","TMPDIR":"\/tmp","TMP":"\/tmp","PATH":"\/usr\/local\/bin:\/usr\/bin:\/bin","HOSTNAME":"iZ2zebb122t63qsoruf52iZ","USER":"www","HOME":"\/home\/www","HTTP_WECHATPAY_TIMESTAMP":"1778419973","HTTP_PRAGMA":"no-cache","HTTP_WECHATPAY_SIGNATURE_TYPE":"WECHATPAY2-SHA256-RSA2048","HTTP_WECHATPAY_SIGNATURE":"YCqfnI4yLSATBAFrSIeNoPcaI4wgTcyM8NV1cPYUdIStkCqkx0VQ+nVhnFvi8UKSOylexmvPvChL1xcwT7g3yd6lU5ctZ6yjF\/DdLnz4zMsl7Li4i9MCKsFYVzSjwdJxgp5zaffZ1LYNKTeiMdDSpZYi2zoN8gQlV4Xv05YvOZm5Sk8YEuYG\/pHul3ROw+8qHa5Zzww2ifPFP42ih5aw3TSFrj1E\/pVSr4abkwQUQHGT5CLFLpKv7GeO6UWH8GnN3wEhf7Au+0HqsoZaUTvqd51NfaTgtJ4lko+u9FgrWxMhdTtQr8B1Kh1SZj5QIJsH7LU4bHRVEtbk4U\/YDu0FQw==","HTTP_WECHATPAY_SERIAL":"1B84A18252BFC94060DF9B551587288FC432AC84","HTTP_CONTENT_TYPE":"application\/json","HTTP_WECHATPAY_NONCE":"KwoGcjVzGIHtg3uhRbAnlmipdXjfuFvC","HTTP_HOST":"server.yinhuoai.com","HTTP_ACCEPT":"*\/*","HTTP_USER_AGENT":"Mozilla\/4.0","HTTP_CONNECTION":"Keep-Alive","HTTP_CONTENT_LENGTH":"919","REDIRECT_STATUS":"200","SERVER_NAME":"server.yinhuoai.com","SERVER_PORT":"443","SERVER_ADDR":"172.30.244.95","REMOTE_PORT":"41120","REMOTE_ADDR":"101.226.103.16","SERVER_SOFTWARE":"nginx\/1.27.0","GATEWAY_INTERFACE":"CGI\/1.1","HTTPS":"on","REQUEST_SCHEME":"https","SERVER_PROTOCOL":"HTTP\/1.1","DOCUMENT_ROOT":"\/data\/www\/yinhuoai-php\/Webroot","DOCUMENT_URI":"\/index.php","REQUEST_URI":"\/order\/payNotify","SCRIPT_NAME":"\/index.php","CONTENT_LENGTH":"919","CONTENT_TYPE":"application\/json","REQUEST_METHOD":"POST","QUERY_STRING":"","SCRIPT_FILENAME":"\/data\/www\/yinhuoai-php\/Webroot\/index.php","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1778419974.036848,"REQUEST_TIME":1778419974},"body":"{\"id\":\"a857aa3d-7881-58a9-b05f-bd8073ab5dcf\",\"create_time\":\"2026-05-10T21:32:53+08:00\",\"resource_type\":\"encrypt-resource\",\"event_type\":\"TRANSACTION.SUCCESS\",\"summary\":\"\u652f\u4ed8\u6210\u529f\",\"resource\":{\"original_type\":\"transaction\",\"algorithm\":\"AEAD_AES_256_GCM\",\"ciphertext\":\"Rg2\/bVqWEavMQfuZQFiHVKa3pQm7Pxm69q\/KVceKW7eOkDr5qf4+5IBeSdkyORiFpe5qf89+MCV6XwxhaBYPp3Emlgzr3JsGDzVMttxcdawdTABLbrjyGY4j40PkAsfN9GL1nnQFeQrZxuHuHJEcZXixHwBmr180Ikwqn72UqCXZ2ZR0YJKmdNM8hA8lYEljeLnMmkWq1cYbVKRdOteMjUGJzTHxHD+IsMbafw\/Azt45HJXVfIyoujohJtODUALAjBp0LLdPr2oECRwqa9y8rnSVkELnos79VWrSq7KYYBsfoReMMLuyhFEYwcXGAd5JKBiNM1mtfAVtvW+qmmJuhnFMrb9fdZxq6flUjvO2EVqJM22zsoImgKLWUwVMeF1TWUGI9hKKKOyTCpAtu\/00jHSuoFRgmMPHdjxbF1clioruirB4v\/j77torWmh5tlhr2E2iy\/1rbSiyrwNrWERRXcGdGt6NPMxPwXgi6u5CD\/oRwM1TglcvSgEPEZPLw\/Dzhcmm6f\/dXRL2vXmaOxVjdBGjnf48NbBxu6yDMDXv2rP7lI19iI020qsimV2QO7aY7NEtAPikoEA3Dw==\",\"associated_data\":\"transaction\",\"nonce\":\"ayY4cqE8mRAd\"}}","params":{"op":"Order.payNotify"}}';		
				$tmpData = json_decode($tmpData, true);
		$header = $tmpData['header'];
		$bodyJson = $tmpData['body'];


// $file = CACHE_PATH . 'payNotify.txt';
// @file_put_contents($file, json_encode(array(
//     		'header' => $header,
//     		'body' => $bodyJson,
//     		'params' => $params,	
//     	)));

// exit;
    	
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