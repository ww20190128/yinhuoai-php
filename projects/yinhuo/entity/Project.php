<?php
namespace entity;

/**
 * Project 实体类
 * 
 * @author 
 */
class Project extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'project';

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
    public $projectId = '';

    /**
     * 剪辑ID
     *
     * @var int
     */
    public $editingId = 0;

    /**
     * 用户ID
     *
     * @var int
     */
    public $userId = 0;
    
    /**
     * 名称
     *
     * @var varchar
     */
    public $name = '';

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