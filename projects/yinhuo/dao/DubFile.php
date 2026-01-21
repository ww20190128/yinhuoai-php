<?php
namespace dao;

/**
 * DubFile 数据库类
 * 
 * @author 
 */
class DubFile extends DaoBase
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
     * @return DubFile
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DubFile();
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