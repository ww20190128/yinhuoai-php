<?php
namespace ctrl;

/**
 * 工具
 * 
 * @package ctrl
 */
class Tools extends CtrlBase
{
	/**
	 * 发送优惠券
	 *
	 * @return array
	 */
	public function sendCoupon()
	{

		$couponConfigDao = \dao\CouponConfig::singleton();
		$couponConfigEttList = $couponConfigDao->readListByIndex(array(
			'status' => 0,
		));
$userId = 1;
$couponId = 10;
		$couponSv = \service\Coupon::singleton();
    	$result = $couponSv->receive($couponId, $userId);
	}

	/**
	 * 创建优惠券
	 *
	 * @return array
	 */
	public function createCoupon()
	{
		$couponConfigDao = \dao\CouponConfig::singleton();
		$couponConfigEttList = $couponConfigDao->readListByIndex(array(
			'status' => 0,
		));
		$userId = 1;
	
		$couponId = 7;
		$couponSv = \service\Coupon::singleton();
		$result = $couponSv->receive($couponId, $userId);
		 
		 
	}
   
}