<?php
namespace dao;

/**
 * Template 数据库类
 * 
 * @author 
 */
class Template extends DaoBase
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
     * @return Template
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Template();
        }
        return self::$instance;
    }

    /**
     * 主方法
     *
     * @return void
     */
    public function main()
    {
        return ;
    }

}