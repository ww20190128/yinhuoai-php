<?php
namespace entity;

/**
 * Caption 实体类
 * 
 * @author 
 */
class Caption extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'caption';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键ID
     *
     * @var int
     */
    public $id;

    /**
     * 文本
     *
     * @var varchar
     */
    public $text = '';

    /**
     * 字体
     *
     * @var varchar
     */
    public $font = '';

    /**
     * 样式
     *
     * @var varchar
     */
    public $style = '';

    /**
     * 状态
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

    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;

// 表结构end
}