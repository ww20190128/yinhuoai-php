<?php
namespace entity;

/**
 * News 实体类
 * 
 * @author 
 */
class News extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'news';

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
     * 内容
     *
     * @var text
     */
    public $content;

    /**
     * 来源
     *
     * @var varchar
     */
    public $source = '';

    /**
     * 封面
     *
     * @var varchar
     */
    public $coverURL = '';

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