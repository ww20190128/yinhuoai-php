<?php
namespace dao;

/**
 * UserGive 数据库类
 * 
 * @author 
 */
class UserGive extends DaoBase
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
     * @return UserGive
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserGive();
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