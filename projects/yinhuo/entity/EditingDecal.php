<?php
namespace entity;

/**
 * EditingDecal 实体类
 * 
 * @author 
 */
class EditingDecal extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editingDecal';

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
     * 适用的镜头ID列表
     *
     * @var varchar
     */
    public $useLensIds = '';

    /**
     * 素材1
     *
     * @var int
     */
    public $mediaId1 = 0;

    /**
     * 素材1大小
     *
     * @var int
     */
    public $mediaSize1 = 0;

    /**
     * 素材2
     *
     * @var int
     */
    public $mediaId2 = 0;

    /**
     * 素材2大小
     *
     * @var int
     */
    public $mediaSize2 = 0;

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