<?php
namespace entity;

/**
 * Folder 实体类
 * 
 * @author 
 */
class Folder extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'folder';

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
     * 名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 用户Id
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 类型
     *
     * @var string
     */
    public $type = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 父级文件夹Id
     *
     * @var int
     */
    public $parentId = 0;

    /**
     * 媒资Id列表
     *
     * @var varchar
     */
    public $mediaIds = '';

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