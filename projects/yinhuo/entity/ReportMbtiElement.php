<?php
namespace entity;

/**
 * ReportMbtiElement 实体类
 * 
 * @author 
 */
class ReportMbtiElement extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_element';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键id
     *
     * @var char
     */
    public $id = '';

    /**
     * 名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 描述
     *
     * @var varchar
     */
    public $desc = '';

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}