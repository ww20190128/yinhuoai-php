<?php
namespace service;

/**
 * Index 逻辑类
 * 
 * @author 
 */
class Index extends ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Index
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Index();
        }
        return self::$instance;
    }
    
}