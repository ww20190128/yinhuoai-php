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
     * 镜头名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 所属剪辑Id
     *
     * @var int
     */
    public $editingId = 0;

    /**
     * 素材Id列表
     *
     * @var varchar
     */
    public $mediaIds = '';

    /**
     * 是否开启原声
     *
     * @var tinyint
     */
    public $originalSound = 0;

    /**
     * 选择时长
     *
     * @var int
     */
    public $duration = 0;

    /**
     * 转场类型 1 自选 2 随机
     *
     * @var tinyint
     */
    public $transitionType = 1;

    /**
     * 选择的转场ID
     *
     * @var varchar
     */
    public $transitionIds = '';

    /**
     * 配音类型  1 手动设置  2 配音文件
     *
     * @var tinyint
     */
    public $dubType = 1;

    /**
     * 手动设置 字幕ID列表
     *
     * @var varchar
     */
    public $dubCaptionIds = '';

    /**
     * 配音文件 旁白素材ID列表
     *
     * @var varchar
     */
    public $dubMediaIds = '';

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