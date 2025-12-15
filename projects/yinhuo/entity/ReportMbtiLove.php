<?php
namespace entity;

/**
 * ReportMbtiLove 实体类
 * 
 * @author 
 */
class ReportMbtiLove extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'report_mbti_love';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'type';

    
    
    /**
     * 名称
     *
     * @var varchar
     */
    public $name;

    /**
     * 让你着迷的气质
     *
     * @var varchar
     */
    public $fascination = '';

    /**
     * 和你互补的气质
     *
     * @var varchar
     */
    public $complementary = '';

    
    /**
     * 相遇-描述
     *
     * @var text
     */
    public $meetDesc;


    /**
     * 相知-描述
     *
     * @var text
     */
    public $knowDesc;


    /**
     * 相爱-描述
     *
     * @var text
     */
    public $loveDesc;


    /**
     * 相惜-描述
     *
     * @var text
     */
    public $cherishDesc;


    /**
     * 相守-描述
     *
     * @var text
     */
    public $togetherDesc;

    /**
     * 匹配情人
     *
     * @var varchar
     */
    public $matching = '';

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