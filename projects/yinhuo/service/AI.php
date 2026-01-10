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
    	 $text = "这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。";
    $options = [
        "voice_type" => "zh_female_shuangkuaisisi_moon_bigtts", // 青年男声
        "audio_config" => [
            "speed" => 1.5,                    // 1.5倍速（V3支持0.3~5.0）
            "emotion" => "happy",               // 欢快情感
            "sample_rate" => 48000              // 更高采样率
        ]
    ];
    
    	$volcTTSSv = \service\reuse\VolcTTS::singleton();
    	$a = $volcTTSSv->synthesize($text, $options);
    	
    	
    	print_r($a);exit;
        return ;
    }

}