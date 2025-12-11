<?php
namespace dao;

/**
 * ReportMbtiLoveTemperament 数据库类
 * 
 * @author 
 */
class ReportMbtiLoveTemperament extends DaoBase
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
     * @return ReportMbtiLoveTemperament
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportMbtiLoveTemperament();
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