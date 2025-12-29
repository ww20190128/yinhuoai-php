<?php
namespace entity;

/**
 * Music 实体类
 * 
 * @author 
 */
class Music extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'music';

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
     * 工程ID
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 时长
     *
     * @var int
     */
    public $duration = 0;

    /**
     * 地址
     *
     * @var varchar
     */
    public $publishUrl = '';

    /**
     * 播放地址
     *
     * @var varchar
     */
    public $playUrl = '';

    /**
     * 分类ID
     *
     * @var int
     */
    public $classifyId = 0;

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