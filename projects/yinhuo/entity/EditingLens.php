<?php
namespace entity;

/**
 * EditingLens 实体类
 * 
 * @author 
 */
class EditingLens extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editingLens';

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
     * 所属剪辑ID
     *
     * @var int
     */
    public $editingId;
    
    /**
     * 素材ID列表
     *
     * @var string
     */
    public $mediaIds;
    
    /**
     * 镜头名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 是否开启原声
     *
     * @var varchar
     */
    public $originalSound = '';

    /**
     * 选择时长
     *
     * @var varchar
     */
    public $duration = '';

    /**
     * 字幕ID列表
     *
     * @var varchar
     */
    public $captionIds = '';

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