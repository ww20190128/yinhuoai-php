<?php
namespace entity;

/**
 * MindfulnessClassifyRelation 实体类
 * 
 * @author 
 */
class MindfulnessClassifyRelation extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'mindfulnessClassifyRelation';

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
     * 分类Id
     *
     * @var int
     */
    public $classifyId = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

    /**
     * 课程Id
     *
     * @var int
     */
    public $mindfulnessId = 0;

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