<?php
namespace dao;

/**
 * ReportGallup 数据库类
 * 
 * @author 
 */
class ReportGallup extends DaoBase
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
     * @return ReportGallup
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportGallup();
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