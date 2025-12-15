<?php
namespace entity;

/**
 * BackstagePrivilege 实体类
 * 
 * @author 
 */
class BackstagePrivilege extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'backstagePrivilege';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 权限类型
     *
     * @var smallint
     */
    public $id;

    /**
     * 名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 树id
     *
     * @var int
     */
    public $treeId = 0;

    /**
     * 父id
     *
     * @var int
     */
    public $parentId = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

    /**
     * 层级
     *
     * @var int
     */
    public $level = 0;

    /**
     * 是否默认开启
     *
     * @var int
     */
    public $defaultOpen = 1;

    /**
     * 类型 1 控制显示 2 控制操作
     *
     * @var int
     */
    public $type = 1;

// 表结构end
}