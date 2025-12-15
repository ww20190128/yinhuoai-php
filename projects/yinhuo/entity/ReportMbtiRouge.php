<?php
namespace entity;

/**
 * ReportMbtiRouge 实体类
 * 
 * @author 
 */
class ReportMbtiRouge extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_rouge';

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
     * 标题
     *
     * @var varchar
     */
    public $title = '';
    
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