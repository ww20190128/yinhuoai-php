<?php
namespace entity;

/**
 * Mindfulness 实体类
 * 
 * @author 
 */
class Mindfulness extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'mindfulness';

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
    public $name = '';

    /**
     * 所属分类
     *
     * @var varchar
     */
    public $classify = '';

    /**
     * 封面
     *
     * @var varchar
     */
    public $coverImg = '';

    /**
     * 状态
     *
     * @var tinyint
     */
    public $status = 0;
    
    /**
     * 标签
     *
     * @var varchar
     */
    public $tagList = '';

    /**
     * 价格
     *
     * @var decimal(6,2)
     */
    public $price = 0.00;

    /**
     * 原价
     *
     * @var decimal(6,2)
     */
    public $originalPrice = 0.00;

    /**
     * 简介
     *
     * @var text
     */
    public $desc;

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