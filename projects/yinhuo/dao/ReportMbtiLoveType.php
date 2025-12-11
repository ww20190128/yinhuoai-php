<?php
namespace dao;

/**
 * ReportMbtiLoveType 数据库类
 * 
 * @author 
 */
class ReportMbtiLoveType extends DaoBase
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
     * @return ReportMbtiLoveType
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportMbtiLoveType();
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