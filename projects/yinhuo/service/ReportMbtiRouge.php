<?php
namespace service;

/**
 * ReportMbtiRouge 逻辑类
 * 
 * @author 
 */
class ReportMbtiRouge extends ServiceBase
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
     * @return ReportMbtiRouge
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportMbtiRouge();
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