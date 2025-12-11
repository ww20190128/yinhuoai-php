<?php
namespace constant;

/**
 * 优惠券
 *
 * @author
 */
class Coupon
{
    // 优惠券类型
    /**
     * 类型：VIP现金抵扣券
     * 注:  不需要指定ID，仅对vip有效
     *
     * @var int
     */
    const TYPE_CASH_DEDUCTION = 1;
    
    /**
     * 类型：测评赠送券
     * 注:  需要指定ID
     * 
     * @var int
     */
    const TYPE_TEST_PAPER_GIVE = 2;
    
    /**
     * 类型：测评折扣券
     * 注:  需要指定ID
     * 
     * @var int
     */
    const TYPE_TEST_PAPER_DISCOUNT = 3;
    
    /**
     * 类型：vip折扣券
     * 
     * 类型：VIP现金抵扣券
     * 注:  不需要指定ID，仅对vip有效
     *
     * @var int
     */
    const TYPE_VIP_DISCOUNT = 4;
    
// 状态
    /**
     * 状态：未使用
     *
     * @var int
     */
     const STATUS_NORMAL = 0;
    
     /**
     * 状态：已使用
     *
     * @var int
     */
     const STATUS_USED = 1;
    
     /**
    * 状态：逾期
    *
    * @var int
    */
    const STATUS_OVERDUE = 2;
}