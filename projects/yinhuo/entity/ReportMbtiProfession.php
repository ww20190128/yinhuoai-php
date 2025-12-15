<?php
namespace entity;

/**
 * ReportMbtiProfession 实体类
 * 
 * @author 
 */
class ReportMbtiProfession extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_profession';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'role';

    /**
     * 角色
     *
     * @var varchar
     */
    public $role = '';

    /**
     * 描述
     *
     * @var text
     */
    public $desc;

    /**
     * 举例
     *
     * @var text
     */
    public $example;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}