<?php
namespace entity;

/**
 * ReportJung 实体类
 * 
 * @author 
 */
class ReportJung extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_jung';

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
     * 颜色
     *
     * @var char
     */
    public $color = '';

    /**
     * 标题
     *
     * @var varchar
     */
    public $title = '';

    /**
     * 简介
     *
     * @var text
     */
    public $desc;

    /**
     * 介绍图片
     *
     * @var varchar
     */
    public $descImg = '';

    /**
     * 背景图片
     *
     * @var varchar
     */
    public $bgImg = '';

    /**
     * 原型标签
     *
     * @var varchar
     */
    public $archetypeTags = '';

    /**
     * 原型介绍
     *
     * @var text
     */
    public $archetypeDesc;

    /**
     * 正面标签
     *
     * @var varchar
     */
    public $positiveTags = '';

    /**
     * 正面描述
     *
     * @var text
     */
    public $positiveDesc;

    /**
     * 负面标签
     *
     * @var varchar
     */
    public $negativeTags = '';

    /**
     * 负面描述
     *
     * @var text
     */
    public $negativeDesc;

    /**
     * 对你的影响
     *
     * @var text
     */
    public $influence;

    /**
     * 共处方法-标题1
     *
     * @var varchar
     */
    public $coexistTitle1 = '';

    /**
     * 共处方法-内容1
     *
     * @var text
     */
    public $coexistContent1;

    /**
     * 共处方法-标题2
     *
     * @var varchar
     */
    public $coexistTitle2 = '';

    /**
     * 共处方法-内容2
     *
     * @var text
     */
    public $coexistContent2;

    /**
     * 共处方法-标题3
     *
     * @var varchar
     */
    public $coexistTitle3 = '';

    /**
     * 共处方法-内容3
     *
     * @var text
     */
    public $coexistContent3;

    /**
     * 标题4
     *
     * @var varchar
     */
    public $coexistTitle4 = '';

    /**
     * 共处方法-内容4
     *
     * @var text
     */
    public $coexistContent4;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}