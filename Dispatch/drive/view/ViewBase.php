<?php
namespace drive\view;

/**
 * 视图基类
 * 
 * @author wangwei
 */
abstract class ViewBase 
{
    /**
     * 视图数据
     * 
     * @var mixed
     */
    private $model;

    /**
     * 展示视图
     */
    abstract function display();
}