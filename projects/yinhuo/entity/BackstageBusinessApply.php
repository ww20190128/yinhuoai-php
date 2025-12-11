<?php
namespace entity;

/**
 * BackstageBusinessApply 实体类
 * 
 * @author 
 */
class BackstageBusinessApply extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'backstageBusinessApply';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键ID
     *
     * @var int
     */
    public $id;

    /**
     * 手机号
     *
     * @var varchar
     */
    public $phone = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 昵称
     *
     * @var varchar
     */
    public $nickname = '';

    /**
     * 微信
     *
     * @var varchar
     */
    public $weChat = '';

    /**
     * 公众号或抖音号
     *
     * @var varchar
     */
    public $accounts = '';

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
}