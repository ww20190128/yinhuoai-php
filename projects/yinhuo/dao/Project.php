<?php
namespace dao;

/**
 * Project 数据库类
 * 
 * @author 
 */
class Project extends DaoBase
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
     * @return Project
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Project();
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