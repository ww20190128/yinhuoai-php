<?php
namespace entity;

/**
 * DubFile 实体类
 * 
 * @author 
 */
class DubFile extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'dubFile';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键ID
     *
     * @var char
     */
    public $id;

    /**
     * 时长
     *
     * @var int
     */
    public $duration = 0;

    /**
     * 内容
     *
     * @var text
     */
    public $content;

    /**
     * 配音key
     *
     * @var varchar
     */
    public $actorSpeaker = '';

    /**
     * 配音名称
     *
     * @var varchar
     */
    public $actorName = '';

    /**
     * 配音分类
     *
     * @var varchar
     */
    public $actorClassify = '';

    /**
     * 资源Id
     *
     * @var varchar
     */
    public $resourceId = '';

    /**
     * 配音内容
     *
     * @var text
     */
    public $text;

    /**
     * 链接
     *
     * @var varchar
     */
    public $url = '';

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