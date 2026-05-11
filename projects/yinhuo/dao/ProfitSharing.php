<?php
namespace dao;

/**
 * ProfitSharing 数据库类
 * 
 * @author 
 */
class ProfitSharing extends DaoBase
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
     * @return ProfitSharing
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ProfitSharing();
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