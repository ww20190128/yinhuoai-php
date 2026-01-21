<?php
namespace entity;

/**
 * MindfulnessAudio 实体类
 * 
 * @author 
 */
class MindfulnessAudio extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'mindfulnessAudio';

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
     * 所属正念课程Id
     *
     * @var varchar
     */
    public $mindfulnessId = 0;

    /**
     * 音频名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 状态
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 时长
     *
     * @var int
     */
    public $time = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

    /**
     * 音频链接
     *
     * @var varchar
     */
    public $url = '';

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