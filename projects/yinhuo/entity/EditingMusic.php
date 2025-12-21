<?php
namespace entity;

/**
 * EditingMusic 实体类
 * 
 * @author 
 */
class EditingMusic extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editingMusic';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 镜头ID
     *
     * @var int
     */
    public $id;

    /**
     * 所属剪辑Id
     *
     * @var int
     */
    public $editingId = 0;

    /**
     * 音乐类型
     *
     * @var int
     */
    public $type = 0;

    /**
     * 音乐id 或素材Id
     *
     * @var int
     */
    public $conId = 0;

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