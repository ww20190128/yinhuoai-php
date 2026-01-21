<?php
namespace dao;

/**
 * ReportJung 数据库类
 * 
 * @author 
 */
class ReportJung extends DaoBase
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
     * @return ReportJung
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportJung();
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