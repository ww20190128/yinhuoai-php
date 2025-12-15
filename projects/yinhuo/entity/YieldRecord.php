<?php
namespace entity;

/**
 * YieldRecord 实体类
 * 
 * @author 
 */
class YieldRecord extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'yieldRecord';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键id
     *
     * @var int
     */
    public $id;

    /**
     * 分享用户ID
     *
     * @var int
     */
    public $shareUserId = 0;
    
    /**
     * 测试用户ID
     *
     * @var int
     */
    public $testUserId = 0;

    /**
     * 测评ID
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 状态 1 申请状态 2 提现成功
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 分享码
     *
     * @var varchar
     */
    public $code = '';

    /**
     * 收益
     *
     * @var varchar
     */
    public $amount = 0;
    
    /**
     * 订单ID
     *
     * @var int
     */
    public $testOrderId = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}