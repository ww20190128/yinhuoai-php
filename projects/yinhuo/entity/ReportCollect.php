<?php
namespace entity;

/**
 * ReportCollect 实体类
 * 
 * @author 
 */
class ReportCollect extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'reportCollect';

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
     * 订单Id
     *
     * @var int
     */
    public $testOrderId = 0;

    /**
     * 评论人
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 状态0 正常 1禁用
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}