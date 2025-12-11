<?php
namespace entity;

/**
 * UserVip 实体类
 * 
 * @author 
 */
class UserVip extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'userVip';

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
     * 购买的vipId
     *
     * @var int
     */
    public $vipId = 0;

    /**
     * 用户ID
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 已赠送的次数
     *
     * @var int
     */
    public $useGiveNum = 0;

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 购买时间
     *
     * @var int
     */
    public $createTime = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;
    
    /**
     * 生效时间
     *
     * @var int
     */
    public $effectTime = 0;
    
    /**
     * 已使用的测评
     *
     * @var text
     */
    public $useTestIds = '';

// 表结构end
}