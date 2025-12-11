<?php
namespace service;

/**
 * ReportMbtiSuggest 逻辑类
 * 
 * @author 
 */
class ReportMbtiSuggest extends ServiceBase
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
     * @return ReportMbtiSuggest
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportMbtiSuggest();
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