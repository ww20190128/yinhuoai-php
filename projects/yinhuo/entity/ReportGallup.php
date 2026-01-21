<?php
namespace entity;

/**
 * ReportGallup 实体类
 * 
 * @author 
 */
class ReportGallup extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_gallup';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'name';

    /**
     * 名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 简介
     *
     * @var text
     */
    public $desc;

    /**
     * 图标
     *
     * @var varchar
     */
    public $iconImg = '';

    /**
     * 颜色
     *
     * @var char
     */
    public $color = '';

    /**
     * 排行图标
     *
     * @var varchar
     */
    public $sortIcon = '';

    /**
     * 字体颜色
     *
     * @var char
     */
    public $titleColor = '';

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}