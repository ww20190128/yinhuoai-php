<?php
namespace entity;

/**
 * ReportMbti 实体类
 * 
 * @author 
 */
class ReportMbti extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti';

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
    public $name;

    /**
     * 气质类型
     *
     * @var varchar
     */
    public $temperament = '';

    /**
     * 擅长类型
     *
     * @var varchar
     */
    public $adeptType = '';

    /**
     * 擅长描述
     *
     * @var varchar
     */
    public $adeptDesc = '';

    /**
     * 占总人口比例
     *
     * @var tinyint
     */
    public $totalRate = 0;

    /**
     * 占男性比例
     *
     * @var tinyint
     */
    public $manRate = 0;

    /**
     * 占女性比例
     *
     * @var tinyint
     */
    public $womanRate = 0;

    /**
     * 名家名人
     *
     * @var varchar
     */
    public $famousPeople = '';

    /**
     * 名家名人代表照片
     *
     * @var varchar
     */
    public $famousPeopleImg = '';

    /**
     * 价值观和动机
     *
     * @var text
     */
    public $valueDesc;

    /**
     * 性格特点-优势
     *
     * @var varchar
     */
    public $characterAdvantage = '';

    /**
     * 性格特点-劣势
     *
     * @var varchar
     */
    public $characterDisadvantage = '';

    /**
     * 成长建议
     *
     * @var text
     */
    public $suggest;

    /**
     * 荣格八维
     *
     * @var varchar
     */
    public $rouge = '';

    /**
     * 恋爱中
     *
     * @var text
     */
    public $loving;

    /**
     * 单身时期
     *
     * @var text
     */
    public $loveSingle;

    /**
     * 恋爱前中期
     *
     * @var text
     */
    public $lovePremetaphase;

    /**
     * 恋爱后期l
     *
     * @var text
     */
    public $loveLate;

    /**
     * 最佳恋爱匹配类型图片
     *
     * @var varchar
     */
    public $loveMatchingImg = '';

    /**
     * 最佳恋爱匹配类型
     *
     * @var varchar
     */
    public $loveMatching = '';

    /**
     * 工作中
     *
     * @var text
     */
    public $workIng;

    /**
     * 团队中
     *
     * @var text
     */
    public $workTeam;

    /**
     * 作为领导
     *
     * @var text
     */
    public $workLead;

    /**
     * 工作中的核心满足感
     *
     * @var text
     */
    public $wrokSatisfaction;

    /**
     * 最佳工作环境
     *
     * @var text
     */
    public $workEnvironmentbest;

    /**
     * 最差工作环境
     *
     * @var text
     */
    public $workEnvironmentWorst;

    /**
     * 职业参考宝典
     *
     * @var varchar
     */
    public $careerRecommend = '';

    /**
     * 职场避雷锦囊
     *
     * @var text
     */
    public $careerEvadeDesc;

    /**
     * 职场避雷-建议-1
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle1 = '';

    /**
     * 职场避雷-建议1
     *
     * @var text
     */
    public $careerEvadeSuggestContent1;

    /**
     * 职场避雷-建议2
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle2 = '';

    /**
     * 职场避雷-建议2
     *
     * @var text
     */
    public $careerEvadeSuggestContent2;

    /**
     * 职场避雷-建议3
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle3 = '';

    /**
     * 职场避雷-建议3
     *
     * @var text
     */
    public $careerEvadeSuggestContent3;

    /**
     * 职场避雷-建议4
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle4 = '';

    /**
     * 职场避雷-建议4
     *
     * @var text
     */
    public $careerEvadeSuggestContent4;

    /**
     * 职场避雷-建议5
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle5 = '';

    /**
     * 职场避雷-建议5
     *
     * @var text
     */
    public $careerEvadeSuggestContent5;

    /**
     * 建议6
     *
     * @var varchar
     */
    public $careerEvadeSuggestTitle6 = '';

    /**
     * 建议6
     *
     * @var text
     */
    public $careerEvadeSuggestContent6;

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