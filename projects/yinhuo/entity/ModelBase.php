<?php
namespace entity;

/**
 * 可转换为模型的实体基类
 * 
 * @author wangwei
 */
abstract class ModelBase extends EntityBase
{
    /**
     * 模型缓存
     *
     * @var mixed
     */
    protected $modelCache;

    /**
     * 获取当前对象的模型
     *
     * @param bool $flushCache 是否刷新模型缓存
     *
     * @return mixed
     */
    public function getModel($flushCache = false)
    {
        if (null === $this->modelCache || $flushCache) {
            $model = $this->createModel();
            $this->modelCache = $model;
        }
        return $this->modelCache;
    }

    /**
     * 创建当前对象的模型
     *
     * @return mixed
     */
    abstract protected function createModel();

    /**
     * 清空模型缓存
     *
     * @return void
     */
    public function clearModel()
    {
        $this->modelCache = null;
    }

}