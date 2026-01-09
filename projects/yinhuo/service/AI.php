<?php
namespace service;
require_once('vendor/autoload.php');


/**
 * AI 逻辑类
 * 
 * @author 
 */
class AI extends ServiceBase
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
     * @return AI
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AI();
        }
        return self::$instance;
    }

    /**
     * 获取认证的key
     *
     * @return void
     */
    public function getApiKey()
    {
    	$AK = '';
    	$SK = '';
    	$config = \Volcengine\Common\Configuration::getDefaultConfiguration()
    		->setAk("Your AK")
    		->setSk("Your SK")
    		->setRegion("cn-beijing");
    	return ;
    }
    
    
    /**
     * 主方法
     *
     * @return void
     */
    public function test()
    {
    	// 
        return ;
    }

}