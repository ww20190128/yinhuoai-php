<?php
namespace entity;

/**
 * Editing 实体类
 * 
 * @author 
 */
class Editing extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editing';

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
     * @var char
     */
    public $type = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 全局配音Id列表
     *
     * @var varchar
     */
    public $voiceIds = '';

    /**
     * 是否显示字幕
     *
     * @var tinyint
     */
    public $showCaption = 0;

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