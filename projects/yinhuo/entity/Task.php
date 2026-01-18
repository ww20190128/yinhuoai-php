<?php
namespace entity;

/**
 * Task 实体类
 * 
 * @author 
 */
class Task extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'task';

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
     * 名称
     *
     * @var varchar
     */
    public $title;

    /**
     * 创建者Id
     *
     * @var int
     */
    public $userId = 0;
    
    /**
     * 详情
     *
     * @var varchar
     */
    public $detail = '';

    /**
     * 跳转
     *
     * @var varchar
     */
    public $goto = '';

    /**
     * 奖励积分
     *
     * @var int
     */
    public $award = 0;

    /**
     * 来源
     *
     * @var varchar
     */
    public $from = '';

    /**
     * 状态0 正常 1禁用
     *
     * @var tinyint
     */
    public $status = 0;

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