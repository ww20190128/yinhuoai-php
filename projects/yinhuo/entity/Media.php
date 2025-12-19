<?php
namespace entity;

/**
 * Media 实体类
 * 
 * @author 
 */
class Media extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'media';

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
     * 访问的URL
     *
     * @var varchar
     */
    public $url = '';

    /**
     * 文件大小
     *
     * @var int
     */
    public $size = 0;
    
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