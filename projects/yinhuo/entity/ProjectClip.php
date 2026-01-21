<?php
namespace entity;

/**
 * ProjectClip 实体类
 * 
 * @author 
 */
class ProjectClip extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'projectClip';

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
     * 工程ID
     *
     * @var int
     */
    public $projectId = '';
    
    /**
     * 视频预览地址
     *
     * @var varchar
     */
    public $previewUrl = '';

    /**
     * 参数
     *
     * @var text
     */
    public $chipParam;

    /**
     * 任务Id
     *
     * @var varchar
     */
    public $jobId = '';

    /**
     * 任务状态
     *
     * @var char
     */
    public $jobStatus = '';

    /**
     * 视频地址
     *
     * @var varchar
     */
    public $mediaURL = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 创建时间
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
     * 时长 
     *
     * @var int
     */
    public $duration = 0;
    
    /**
     * 媒资Id
     *
     * @var int
     */
    public $mediaId = 0;
// 表结构end
}