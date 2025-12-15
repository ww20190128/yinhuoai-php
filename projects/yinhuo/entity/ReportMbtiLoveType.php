<?php
namespace entity;

/**
 * ReportMbtiLoveType 实体类
 * 
 * @author 
 */
class ReportMbtiLoveType extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_love_type';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 类型
     *
     * @var int
     */
    public $id = 0;

    /**
     * 类型
     *
     * @var char
     */
    public $type = '';
    
    /**
     * 类型
     *
     * @var char
     */
    public $version = '';

    /**
     * 主图
     *
     * @var varchar
     */
    public $mainImg = '';

    /**
     * 匹配原因
     *
     * @var varchar
     */
    public $matchingReason = '';

    /**
     * 名称
     *
     * @var varchar
     */
    public $name;

    /**
     * 标签
     *
     * @var varchar
     */
    public $tags = '';

    /**
     * 能量方向
     *
     * @var varchar
     */
    public $energyDirection = '';

    /**
     * 体验倾向
     *
     * @var varchar
     */
    public $experienceTend = '';

    /**
     * 决定倾向
     *
     * @var varchar
     */
    public $determiningTend = '';

    /**
     * 组织倾向
     *
     * @var varchar
     */
    public $organizationalTend = '';

    /**
     * TA的个性特点
     *
     * @var varchar
     */
    public $peculiarity = '';

    /**
     * 与TA偶遇-地点
     *
     * @var varchar
     */
    public $meetWithPlace = '';

    /**
     * 爱之初体验-描述
     *
     * @var varchar
     */
    public $firstLoveDesc = '';

    /**
     * 爱之初体验-锦囊-标题
     *
     * @var varchar
     */
    public $firstLoveTitle = '';

    /**
     * 爱之初体验-锦囊-内容
     *
     * @var varchar
     */
    public $firstLoveContent = '';

    /**
     * 捕心行动-描述
     *
     * @var varchar
     */
    public $actionDesc = '';

    /**
     * 捕心行动-锦囊1-标题
     *
     * @var varchar
     */
    public $actionTitle = '';

    /**
     * 捕心行动-锦囊1-内容
     *
     * @var text
     */
    public $actionContent;

    /**
     * 完美的性爱
     *
     * @var text
     */
    public $perfectSex;

    /**
     * 让爱天长地久
     *
     * @var text
     */
    public $longer;

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