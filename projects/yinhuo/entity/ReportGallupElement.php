<?php
namespace entity;

/**
 * ReportGallupElement 实体类
 * 
 * @author 
 */
class ReportGallupElement extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_gallup_element';

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
    public $name;

    /**
     * 所属类型
     *
     * @var varchar
     */
    public $gallupName = '';

    /**
     * 颜色
     *
     * @var char
     */
    public $color = '';

    /**
     * 简介
     *
     * @var text
     */
    public $desc;

    /**
     * 内容
     *
     * @var text
     */
    public $content;

    /**
     * 优势-标题1
     *
     * @var varchar
     */
    public $advantageTitle1 = '';

    /**
     * 优势-内容1
     *
     * @var text
     */
    public $advantageContent1;

    /**
     * 优势-标题2
     *
     * @var varchar
     */
    public $advantageTitle2 = '';

    /**
     * 优势-内容2
     *
     * @var text
     */
    public $advantageContent2;

    /**
     * 优势-标题3
     *
     * @var varchar
     */
    public $advantageTitle3 = '';

    /**
     * 优势-内容3
     *
     * @var text
     */
    public $advantageContent3;

    /**
     * 盲点-标题1
     *
     * @var varchar
     */
    public $weaknessTitle1 = '';

    /**
     * 盲点-内容1
     *
     * @var text
     */
    public $weaknessContent1;

    /**
     * 盲点-标题2
     *
     * @var varchar
     */
    public $weaknessTitle2 = '';

    /**
     * 盲点-内容2
     *
     * @var text
     */
    public $weaknessContent2;

    /**
     * 盲点-标题3
     *
     * @var varchar
     */
    public $weaknessTitle3 = '';

    /**
     * 盲点-内容3
     *
     * @var text
     */
    public $weaknessContent3;

    /**
     * 发挥优势-面对自己
     *
     * @var text
     */
    public $advantageSelf;

    /**
     * 发挥优势-面对他人-内容1
     *
     * @var text
     */
    public $advantageOthers;

    /**
     * 发挥优势-面对环境
     *
     * @var text
     */
    public $advantageEnv;

    /**
     * 领导方法1
     *
     * @var text
     */
    public $leadMethod;

    /**
     * 推荐职业
     *
     * @var varchar
     */
    public $recommendProfession = '';

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