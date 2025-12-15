<?php
namespace constant;

/**
 * 赠送
 *
 * @author
 */
class Give
{
    /**
     * 状态：待领取
     *
     * @var int
     */
    const STATUS_NORMAL = 0;
    
    /**
     * 状态：已领取
     *
     * @var int
     */
    const STATUS_DRAWED = 1;
    
    /**
     * 状态：已使用
     *
     * @var int
     */
    const STATUS_USED = 2;
}