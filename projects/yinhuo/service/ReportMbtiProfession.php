<?php
namespace service;

/**
 * ReportMbtiProfession 逻辑类
 * 
 * @author 
 */
class ReportMbtiProfession extends ServiceBase
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
     * @return ReportMbtiProfession
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportMbtiProfession();
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