<?php
namespace entity;

/**
 * ClassifyRelation 实体类
 * 
 * @author 
 */
class ClassifyRelation extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'classifyRelation';

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
     * 测卷Id
     *
     * @var int
     */
    public $testPaperId = 0;

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