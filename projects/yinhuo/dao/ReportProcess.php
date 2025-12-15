<?php
namespace dao;

/**
 * ReportProcess 数据库类
 * 
 * @author 
 */
class ReportProcess extends DaoBase
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
     * @return ReportProcess
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportProcess();
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