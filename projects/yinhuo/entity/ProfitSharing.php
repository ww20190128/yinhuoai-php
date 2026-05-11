<?php
namespace entity;

/**
 * ProfitSharing 实体类
 * 
 * @author 
 */
class ProfitSharing extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'profitSharing';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键
     *
     * @var bigint
     */
    public $id;

    /**
     * 订单Id
     *
     * @var int
     */
    public $orderId = 0;

    /**
     * 支付状态
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 获益人用户ID
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 添加金币
     *
     * @var int
     */
    public $addGold = 0;

    /**
     * 当前金币
     *
     * @var int
     */
    public $currentGold = 0;

    /**
     * 父级
     *
     * @var int
     */
    public $parentUserId = 0;

    /**
     * 来源用户Id
     *
     * @var int
     */
    public $fromUserId = 0;

    /**
     * 受益人添加的openID
     *
     * @var varchar
     */
    public $receiverAddOpenId = '';

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