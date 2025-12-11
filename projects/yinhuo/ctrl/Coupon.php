<?php
namespace ctrl;

/**
 * 优惠券
 *
 * @author
 */
class Coupon extends CtrlBase
{
	/**
	 * 创建优惠券
	 *
	 * @return array
	 */
	public function createCoupon()
	{
		$params = $this->params;
		$couponId = $this->paramFilter('id', 'intval'); // 优惠券ID
		if (empty($couponId)) {
			throw new $this->exception('请求参数错误');
		}
		$couponSv = \service\Coupon::singleton();
		return $couponSv->createCoupon($couponId);
	}

    /**
     * 领取优惠券
     *
     * @return array
     */
    public function receive()
    {
        $params = $this->params;
    	$couponCode = $this->paramFilter('couponCode', 'string'); // 优惠券ID
    	if (empty($couponCode)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$couponSv = \service\Coupon::singleton();
    	return $couponSv->receive($couponCode, $this->userId);
    }
    
    /**
     * 获取用户优惠卷
     *
     * @return array
     */
    public function couponList()
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
    	$type = $this->paramFilter('type', 'intval', 0); // 0 所有   1 现金券  2 赠送券  3 折扣券 4 vip 折扣
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 100); // 每页数量限制
    	$couponSv = \service\Coupon::singleton();
    	return $couponSv->couponList($userEtt, $type, $pageNum, $pageLimit);
    }
    
    /**
     * 优惠券详情
     *
     * @return array
     */
    public function couponInfo()
    {
    	$params = $this->params;
    	$couponId = $this->paramFilter('couponId', 'intval', 0);
    	if (empty($couponId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$couponSv = \service\Coupon::singleton();
    	return $couponSv->couponInfo($couponId);
    }
    
    /**
     * 根据优惠券ID 获取列表
     *
     * @return array
     */
    public function getListByCouponId()
    {
    	$params = $this->params;
    	
    	$couponId = $this->paramFilter('couponId', 'intval'); // 优惠券ID
    	if (empty($couponId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    
    	$couponSv = \service\Coupon::singleton();
    	return $couponSv->getListByCouponId($couponId, $pageNum, $pageLimit);
    }
}