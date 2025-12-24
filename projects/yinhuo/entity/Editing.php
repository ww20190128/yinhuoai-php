<?php
namespace entity;

/**
 * Editing 实体类
 * 
 * @author 
 */
class Editing extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'editing';

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
     * 名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 标题
     *
     * @var varchar
     */
    public $title = '';
    
    /**
     * 话题
     *
     * @var varchar
     */
    public $topic = '';
    
    /**
     * 用户Id
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 类型
     *
     * @var char
     */
    public $type = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 视频比例 可选 9:16/16:9/1:1
     *
     * @var char
     */
    public $ratio = '9:16';

    /**
     * 视频时长类型 1  按视频时长  2  按配音时长
     *
     * @var varchar
     */
    public $durationType = 1;

    /**
     * 视频帧率 取值：25/30/60
     *
     * @var tinyint
     */
    public $fps = 25;

    /**
     * 音量调节
     *
     * @var varchar
     */
    public $volume = '';

    /**
     * 选中的转场ID
     *
     * @var varchar
     */
    public $transitionIds = '';

    /**
     * 选中的滤镜ID
     *
     * @var varchar
     */
    public $filterIds = '';

    /**
     * 颜色调整
     *
     * @var varchar
     */
    public $color = '';

    /**
     * 背景填充
     *
     * @var varchar
     */
    public $background = '';

    /**
     * 是否显示字幕
     *
     * @var tinyint
     */
    public $showCaption = 0;
    
    /**
     * 演员列表
     *
     * @var varchar
     */
    public $actorIds = '';

    /**
     * 配音类型  1 手动设置  2  配音文件
     *
     * @var tinyint
     */
    public $dubType = 1;

    /**
     * 手动设置-字幕列表
     *
     * @var varchar
     */
    public $dubCaptionIds = '';

    /**
     * 配音文件
     *
     * @var varchar
     */
    public $dubMediaIds = '';

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

// 表结构end
}