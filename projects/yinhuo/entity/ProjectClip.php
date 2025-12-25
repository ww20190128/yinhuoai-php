<?php
namespace entity;

/**
 * ProjectClip 实体类
 * 
 * @author 
 */
class ProjectClip extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'projectClip';

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
    public $projectId = 0;

    /**
     * 地址
     *
     * @var varchar
     */
    public $url = '';

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