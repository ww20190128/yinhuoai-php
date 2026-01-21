<?php
namespace dao;

/**
 * User 数据库类
 *
 * @author
 */
class User extends DaoBase
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
     * @return User
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new User();
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