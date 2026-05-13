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
     * 查询创建的订单
     *
     * @return array
     */
    public function xpayQueryOrder()
    {
    	$params = $this->params;
    	$userId = empty($this->userId) ? 0 : $this->userId;
    	$orderId = $this->paramFilter('orderId', 'intval'); // 订单Id
    	if (empty($orderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$orderSv = \service\Order::singleton();
    	return $orderSv->xpayQueryOrder($userId, $orderId);
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

// $tmpData = '{"header":{"TEMP":"\/tmp","TMPDIR":"\/tmp","TMP":"\/tmp","PATH":"\/usr\/local\/bin:\/usr\/bin:\/bin","HOSTNAME":"iZ2zebb122t63qsoruf52iZ","USER":"www","HOME":"\/home\/www","HTTP_WECHATPAY_TIMESTAMP":"1778468702","HTTP_PRAGMA":"no-cache","HTTP_WECHATPAY_SIGNATURE_TYPE":"WECHATPAY2-SHA256-RSA2048","HTTP_WECHATPAY_SIGNATURE":"P6uotaSEJqiAphqZKnCR45ey\/UgwuJxmb4kG4Dl2FvpTKn17Zd6Tcpfpp9KJIJpoQsd8VF8JrkYFxYlEIAPeunbXnatG7PmY438SHe\/EOhPQvgb0hsp3ZNHbZaUU1IkdURkUfXYPw1XZqYfSJ9yTn6EAC8yUZBUSF3MSoewYs4WBPVl0KIVbgK90XRpSp1Cm00MQ1sErc2ddl35IIitwfkvCjBcHjwHKj9CW+3xi\/ptB8fWLc14yu+liNG+jX7LZUa0gFWooJub6MYk1ALBCoEyH4l5T6RsjuOkU3MZJi0NENCUsODNlcTjugIP6zt4OruJQTs6gaXPzZbbSp7NoSg==","HTTP_WECHATPAY_SERIAL":"1B84A18252BFC94060DF9B551587288FC432AC84","HTTP_CONTENT_TYPE":"application\/json","HTTP_WECHATPAY_NONCE":"CNJ4BPnn1qgVR0zV8EL22BhOEKJH5EZn","HTTP_HOST":"server.yinhuoai.com","HTTP_ACCEPT":"*\/*","HTTP_USER_AGENT":"Mozilla\/4.0","HTTP_CONNECTION":"Keep-Alive","HTTP_CONTENT_LENGTH":"919","REDIRECT_STATUS":"200","SERVER_NAME":"server.yinhuoai.com","SERVER_PORT":"443","SERVER_ADDR":"172.30.244.95","REMOTE_PORT":"46709","REMOTE_ADDR":"101.226.103.16","SERVER_SOFTWARE":"nginx\/1.27.0","GATEWAY_INTERFACE":"CGI\/1.1","HTTPS":"on","REQUEST_SCHEME":"https","SERVER_PROTOCOL":"HTTP\/1.1","DOCUMENT_ROOT":"\/data\/www\/yinhuoai-php\/Webroot","DOCUMENT_URI":"\/index.php","REQUEST_URI":"\/order\/payNotify","SCRIPT_NAME":"\/index.php","CONTENT_LENGTH":"919","CONTENT_TYPE":"application\/json","REQUEST_METHOD":"POST","QUERY_STRING":"","SCRIPT_FILENAME":"\/data\/www\/yinhuoai-php\/Webroot\/index.php","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1778468702.383788,"REQUEST_TIME":1778468702},"body":"{\"id\":\"4f3f36ab-376a-53ed-be0c-a2b14d4c9ddd\",\"create_time\":\"2026-05-11T11:05:01+08:00\",\"resource_type\":\"encrypt-resource\",\"event_type\":\"TRANSACTION.SUCCESS\",\"summary\":\"\u652f\u4ed8\u6210\u529f\",\"resource\":{\"original_type\":\"transaction\",\"algorithm\":\"AEAD_AES_256_GCM\",\"ciphertext\":\"5gmwa\/ETkCGznBkPXG9fxp9qpEiJLpXMWXAZ464zbIpiaSAXiIThoZ+6Gq7JQvovBzMPL\/BrKEC0NQqo2bOgxJUXhnjROelTxr8R0jJ9dzKDKlMKd\/T+WX1gCBsbG2H0n9MQBZMGxs2lbQrz1So4v8KpGSC6p5LHDVL4is9iQMu2RFZKiu\/YeoanDmPXraXp16LzGyt4qZv\/99vs2lM8asPy3LljHpfucI6qYSDjDpBUqNKglABZNj4LdycpqyyM\/kQnX3kj+WfqTg1VW\/nW+vbyKMQK5uFrV4XXJx2uiIVlS+gKQZXY6+mgTBPFObm\/L89UaaQQTNawp\/RHZLb99+xalCn5Imdc+vAtOgiVCsWz0b2\/USCILppIfplvifPCuf7qS+siPWOA9wbRN6Rx80pxihjQOCqMvTTCEAIgo0h9gF0IKvgN+eOGcd8\/TatcIs8bOKvzOEaatCLWOYRSoeswll53iRAMKczVJs\/q7GYm6f\/Ir7Xh\/tOEcaIn1QwMSWM5\/ueOhuSB6yQUCG0D5RF2CfVEec76l7lNfnmWsu0sC3W6yMTycKwizzQFx9oFhNI\/LUjhj3+WFA==\",\"associated_data\":\"transaction\",\"nonce\":\"bXBIcI4vVBqB\"}}","params":{"op":"Order.payNotify"}}';
// $tmpData = json_decode($tmpData, true);
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
     * 处理订单分账
     *
     * @return array
     */
    public function doProfitSharing()
    {
    	$orderSv = \service\Order::singleton();
    	return $orderSv->doProfitSharing();
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
    
    /**
     * 获取返利订单列表
     *
     * @return array
     */
    public function getProfitSharingList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$searchStartTime = $this->paramFilter('searchStartTime', 'intval'); // 开始时间
    	$searchEndTime = $this->paramFilter('searchEndTime', 'intval'); // 结束时间
    	$searchStatus = $this->paramFilter('searchStatus', 'intval'); // 返利状态
    	$searchTopLevel = $this->paramFilter('searchTopLevel', 'intval'); // 返利等级
    	$info = array(
    		'searchStatus' 	  => $searchStatus,
    		'searchTopLevel'  => $searchTopLevel,
    		'searchStartTime' => empty($searchStartTime) ? 0 : strtotime($searchStartTime),
    		'searchEndTime'   => empty($searchEndTime) ? 0 : strtotime($searchEndTime) + 86399,
    	);
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	$orderSv = \service\Order::singleton();
    	return $orderSv->getProfitSharingList($this->userId, $info, $pageNum, $pageLimit);
    }
    
}