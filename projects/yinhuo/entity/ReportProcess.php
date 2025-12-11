<?php
namespace entity;

/**
 * ReportProcess 实体类
 * 
 * @author 
 */
class ReportProcess extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'reportProcess';

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
     * 标题
     *
     * @var varchar
     */
    public $title;
    
    /**
     * 标题颜色
     *
     * @var varchar
     */
    public $titleColor;
    
    /**
     * 名称
     *
     * @var varchar
     */
    public $groupName;
    
    /**
     * 执行时间
     *
     * @var int
     */
    public $executeTime;

    /**
     * 测评Id
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 版本
     *
     * @var int
     */
    public $version = 1;

    /**
     * 状态0 正常 1禁用
     *
     * @var int
     */
    public $status = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

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