<?php
namespace dao;

/**
 * Lens 数据库类
 * 
 * @author 
 */
class Lens extends DaoBase
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
     * @return Lens
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Lens();
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