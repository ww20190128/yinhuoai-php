<?php
namespace ctrl;

/**
 * 分销
 *
 * @author
 */
class Brokerage extends CtrlBase
{
    /**
     * 推广信息
     *
     * @return array
     */
    public function info()
    {
        $params = $this->params;
        if (empty($this->userId)) {
        	throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
        }
    	$brokerageSv = \service\Brokerage::singleton();
    	return $brokerageSv->info($this->userId);
    }
    
    /**
     * 分享信息
     *
     * @return array
     */
    public function createShareInfo()
    {
    	$params = $this->params;
    	$testPaperId = $this->paramFilter('testPaperId', 'intval'); // 测评Id
    	if (empty($testPaperId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$brokerageSv = \service\Brokerage::singleton();
    	return $brokerageSv->createShareInfo($testPaperId, $this->userId);
    }
    
    /**
     * 提现申请
     *
     * @return array
     */
    public function withdrawApply()
    {	
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$withdrawValue = $this->paramFilter('withdrawValue', 'string', 0); // 提现金额
    	// 移除千分位逗号，保留小数点
    	$withdrawValue = str_replace(',', '', $withdrawValue);
    	// 转换为浮点数
    	$withdrawValue = (float)$withdrawValue;
    	if (empty($withdrawValue) || $withdrawValue <= 0) {
    		throw new $this->exception("请输入正确的提现金额");
    	}
    	$brokerageSv = \service\Brokerage::singleton();
    	return $brokerageSv->withdrawApply($this->userId, $withdrawValue);
    }
    
    /**
     * 提现信息查询
     *
     * @return array
     */
    public function wxTransferInfo()
    {
    	$params = $this->params;
    	$id = $this->paramFilter('id', 'string'); // 订单Id
    	if (empty($id)) {
    		throw new $this->exception("请求参数错误");
    	}
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEtt = $withdrawDao->readByPrimary($id);
    	if (empty($withdrawEtt)) {
    		throw new $this->exception("请求参数错误");
    	}
    	$status = intval($withdrawEtt->status);
    	if ($withdrawEtt->status != \constant\Order::BROKERAGE_STATUS_RECEIVED) { // 没收到， 查询下订单状态
    		$paySv = \service\Pay::singleton();
    		$wxTransferInfo = $paySv->getwxTransferInfo($withdrawEtt);
    		if (!empty($wxTransferInfo['status'])) {
    			$status = $wxTransferInfo['status'];
    		}
    	}
    	return array(
    		'status' => $status,
    	);
    }
    
    /**
     * 取消提现
     *
     * @return array
     */
    public function wxTransferCancel()
    {
    	$params = $this->params;
    	$id = $this->paramFilter('id', 'string'); // 订单Id
    	if (empty($id)) {
    		throw new $this->exception("请求参数错误");
    	}
    	$withdrawDao = \dao\Withdraw::singleton();
    	$withdrawEtt = $withdrawDao->readByPrimary($id);
    	if (empty($withdrawEtt)) {
    		throw new $this->exception("请求参数错误");
    	}
    	$status = intval($withdrawEtt->status);
    	if ($withdrawEtt->status != \constant\Order::BROKERAGE_STATUS_RECEIVED) { // 没收到， 查询下订单状态
    		$paySv = \service\Pay::singleton();
    		$wxTransferInfo = $paySv->wxTransferCancel($withdrawEtt);
    		$withdrawEtt->set('status', \constant\Order::BROKERAGE_STATUS_FAIL_AUDIT);
    		$withdrawDao->update($withdrawEtt);
    	}
    	return array(
    		'status' => $status,
    	);
    }
    
    /**
     * 收益明细
     *
     * @return array
     */
    public function yieldList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($this->userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$brokerageSv = \service\Brokerage::singleton();
    	$info = array(
    		'searchShareUserIds' => array($userEtt->userId),
    	);
    	return $brokerageSv->yieldList($info, 1, 500);
    }
    
    /**
     * 提现记录
     *
     * @return array
     */
    public function withdrawList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($this->userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$brokerageSv = \service\Brokerage::singleton();
    	return $brokerageSv->withdrawList($userEtt);
    }
    
    
    /**
     * 收益明细
     *
     * @return array
     */
    public function test()
    {
    	$userDao = \dao\User::singleton();
        $userEtt = $userDao->readByPrimary(25);
   
    	$paySv = \service\Pay::singleton();
    	$wxTransferResult = $paySv->wxTransfer($userEtt, 1);
    	
    	print_r($wxTransferResult);exit;
    }
    
    
}