<?php
namespace entity;

/**
 * ReportMbtiSuggest 实体类
 * 
 * @author 
 */
class ReportMbtiSuggest extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_suggest';

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