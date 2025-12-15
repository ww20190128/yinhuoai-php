<?php
namespace entity;

/**
 * Banner 实体类
 * 
 * @author 
 */
class Banner extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'banner';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键id
     *
     * @var int
     */
    public $id;

    /**
     * 名称
     *
     * @var varchar
     */
    public $name;

    /**
     * 名称
     *
     * @var varchar
     */
    public $url = '';

    /**
     * 跳转
     *
     * @var varchar
     */
    public $goto = '';

    /**
     * 状态0 正常 1禁用
     *
     * @var int
     */
    public $status = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}