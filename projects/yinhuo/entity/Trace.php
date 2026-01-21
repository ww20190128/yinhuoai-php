<?php
namespace entity;

/**
 * Trace 实体类
 * 
 * @author 
 */
class Trace extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'trace';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 日志id
     *
     * @var bigint unsigned
     */
    public $id;

    /**
     * 服务器id
     *
     * @var int unsigned
     */
    public $serverId = 0;

    /**
     * 日志类型
     *
     * @var tinyint unsigned
     */
    public $type = 0;

    /**
     * 用户id
     *
     * @var bigint unsigned
     */
    public $userId = 0;

    /**
     * 变化数量
     *
     * @var int
     */
    public $num = 0;

    /**
     * 关联类型(题目id等)
     *
     * @var varchar
     */
    public $refer = '';

    /**
     * 其他扩展参数1
     *
     * @var varchar
     */
    public $param1 = '';

    /**
     * 其他扩展参数2
     *
     * @var varchar
     */
    public $param2 = '';

    /**
     * 其他扩展参数3
     *
     * @var varchar
     */
    public $param3 = '';

    /**
     * 其他扩展参数4
     *
     * @var varchar
     */
    public $param4 = '';

    /**
     * 跟踪时间
     *
     * @var int unsigned
     */
    public $traceTime = 0;

    /**
     * 记录时间
     *
     * @var timestamp
     */
    public $recordTime = '';

    /**
     * 请求接口
     *
     * @var varchar
     */
    public $requestOp = '';

    /**
     * 请求参数
     *
     * @var text
     */
    public $requestParams;

    /**
     * 其他信息
     *
     * @var text
     */
    public $other;

// 表结构end
}