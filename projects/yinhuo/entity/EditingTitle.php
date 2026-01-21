<?php
namespace entity;

/**
 * EditingTitle 实体类
 * 
 * @author 
 */
class EditingTitle extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editingTitle';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 标题组ID
     *
     * @var int
     */
    public $id;

    /**
     * 剪辑ID
     *
     * @var int
     */
    public $editingId;
    
    /**
     * 开始时间
     *
     * @var int
     */
    public $start;
    
    /**
     * 结束时间
     *
     * @var int
     */
    public $end;
    
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