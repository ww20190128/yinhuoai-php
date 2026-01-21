<?php
namespace dao;

/**
 * BackstagePrivilege 数据库类
 * 
 * @author 
 */
class BackstagePrivilege extends DaoBase
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
     * @return BackstagePrivilege
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BackstagePrivilege();
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