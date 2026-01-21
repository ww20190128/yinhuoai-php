<?php
namespace dao;

/**
 * Withdraw 数据库类
 * 
 * @author 
 */
class Withdraw extends DaoBase
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
     * @return Withdraw
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Withdraw();
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