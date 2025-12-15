<?php
namespace entity;

/**
 * Withdraw 实体类
 * 
 * @author 
 */
class Withdraw extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'withdraw';

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
     * 用户ID
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 状态 1 申请状态 2 提现成功
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 提现金额
     *
     * @var decimal(6,2)
     */
    public $amount = 0.00;

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
    
    /**
     * 提现单号
     *
     * @var int
     */
    public $outBillNo = '';
    
    /**
     * 转账信息
     *
     * @var int
     */
    public $transferInfo = '';
    
    /**
     * 转账回调信息
     *
     * @var int
     */
    public $transferNotifyInfo = '';
// 表结构end
}