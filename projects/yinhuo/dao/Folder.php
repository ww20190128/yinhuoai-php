<?php
namespace dao;

/**
 * Folder 数据库类
 * 
 * @author 
 */
class Folder extends DaoBase
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
     * @return Folder
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Folder();
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