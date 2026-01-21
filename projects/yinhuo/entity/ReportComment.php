<?php
namespace entity;

/**
 * ReportComment 实体类
 * 
 * @author 
 */
class ReportComment extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'reportComment';

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
     * 答题体验感
     *
     * @var int
     */
    public $experience = 0;

    /**
     * 结果准确度
     *
     * @var int
     */
    public $accuracy = 0;

    /**
     * 报告满意度
     *
     * @var int
     */
    public $satisfaction = 11;

    /**
     * 评论内容
     *
     * @var text
     */
    public $content;

    /**
     * 状态0 正常 1禁用
     *
     * @var int
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