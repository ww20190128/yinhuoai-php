<?php
namespace entity;

/**
 * ReportABO 实体类
 * 
 * @author 
 */
class ReportABO extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_ABO';

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
     * 性别特征
     *
     * @var varchar
     */
    public $sexCharacter = '';

    /**
     * 信息素类型
     *
     * @var varchar
     */
    public $pheromoneTag = '';

    /**
     * 信息素描述
     *
     * @var varchar
     */
    public $pheromoneDesc = '';

    /**
     * 人物代表-标题
     *
     * @var varchar
     */
    public $personageTitle = '';

    /**
     * 人物代表-描述
     *
     * @var varchar
     */
    public $personageDesc = '';

    /**
     * 工作
     *
     * @var varchar
     */
    public $work = '';

    /**
     * 人际
     *
     * @var varchar
     */
    public $interpersonal = '';

    /**
     * 情感
     *
     * @var varchar
     */
    public $emotion = '';

    /**
     * 提升建议
     *
     * @var TEXT
     */
    public $suggest = '';
    
    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}