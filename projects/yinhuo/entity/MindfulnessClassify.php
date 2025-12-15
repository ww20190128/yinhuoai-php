<?php
namespace entity;

/**
 * MindfulnessClassify 实体类
 * 
 * @author 
 */
class MindfulnessClassify extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'mindfulnessClassify';

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
    public $name;

    /**
     * 状态0 正常 1禁用
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

    /**
     * 图标
     *
     * @var varchar
     */
    public $icon = 0;

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