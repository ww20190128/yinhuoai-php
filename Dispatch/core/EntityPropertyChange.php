<?php
namespace Dispatch;

/**
 * 实体属性变更记录
 *
 * 作为EntityBase的辅助数据类而存在，存放实体属性变更值
 */
final class EntityPropertyChange
{

    /**
     * 变更类型：ADD
     *
     * @var int
     */
    const CHANGE_TYPE_ADD = 0;

    /**
     * 变更类型：SET
     *
     * @var int
     */
    const CHANGE_TYPE_SET = 1;

    /**
     * 属性初始值
     *
     * @var mixed
     */
    public $initValue;

    /**
     * 变更类型
     *
     * @var int
     */
    public $changeType;

    /**
     * 变更值
     *
     * @var mixed
     */
    public $changeValue;

}