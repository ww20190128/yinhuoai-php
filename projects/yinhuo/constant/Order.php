<?php
namespace constant;

/**
 * 订单
 *
 * @author
 */
class Order
{
    // 商品类型
    /**
     * 商品类型：vip
     *
     * @var int
     */
    const TYPE_GOODS_VIP = 1;
    
    /**
     * 商品类型：测评
     *
     * @var int
     */
    const TYPE_GOODS_TEST_PAPER = 2;
    
    /**
     * 商品类型：正念课程
     *
     * @var int
     */
    const TYPE_GOODS_MINDFULNESS = 3;
    
//  订单测试状态
    /**
     * 测试订单状态：测试中
     *
     * @var int
     */
    const ORDER_STATUS_NORMAL = 0;
    
    /**
     * 测试订单状态：已测试
     *
     * @var int
     */
    const ORDER_STATUS_TESTED = 1;
// 支付状态
    
    /**
     * 支付状态：未支付
     *
     * @var int
     */
    const PAY_STATUS_DURING = 0;
    
    /**
     * 支付状态：无需支付
     *
     * @var int
     */
    const PAY_STATUS_NO_NEED_PAY = 2;
    
    /**
     * 支付状态：已支付
     *
     * @var int
     */
    const PAY_STATUS_COMPLETE = 3;
    
    
    /**
     * 支付状态：逾期未支付
     *
     * @var int
     */
    const PAY_STATUS_PAST_DUE = 4;
    
// 折扣类型
    /**
     * 折扣类型：vip
     *
     * @var int
     */
     const DISCOUNT_TYPE_VIP = 1;
    
     /**
     * 折扣类型：赠送
     *
     * @var int
      */
     const DISCOUNT_TYPE_GIVE = 2;
      
     /**
    * 折扣类型：优惠券
    *
    * @var int
    */
    const DISCOUNT_TYPE_COUPON = 3;
    
// 佣金状态
    /**
     * 佣金状态：未申请
     *
     * @var int
     */
    const BROKERAGE_STATUS_NOT_APPLY = 1;
    
    /**
     * 佣金状态：审核中
     *
     * @var int
     */
    const BROKERAGE_STATUS_IN_REVIEW = 2;
    
    /**
     * 佣金状态：审核通过（发起转账）
     *
     * @var int
     */
    const BROKERAGE_STATUS_APPROVE = 3;
    
    /**
     * 佣金状态：审核未通过（取消提现）
     *
     * @var int
     */
    const BROKERAGE_STATUS_FAIL_AUDIT = 4;

    /**
     * 佣金状态：已到账
     *
     * @var int
     */
    const BROKERAGE_STATUS_RECEIVED = 5;
}