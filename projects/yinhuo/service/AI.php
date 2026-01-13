<?php
namespace service;
require_once('vendor/autoload.php');
use Volcengine\Kernel\VolcengineClient;
use Volcengine\Kernel\Credentials\StaticCredentials;

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
    	$client = new TtsClient();
    	print_r($client);exit;
    	
//     	$AK = '';
//     	$SK = '';
//     	$config = \Volcengine\Common\Configuration::getDefaultConfiguration()
//     		->setAk("Your AK")
//     		->setSk("Your SK")
//     		->setRegion("cn-beijing");
    	return ;
    }
    
    
    /**
     * 主方法
     * private $accessKey = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";    // AccessKey ID
    private $secretKey = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";    // SecretKey
    private $appKey = "d294de9a-a197-42e4-8a00-e29eaa05a0df";       // TTS应用AppKey
     * @return void
     */
    public function test()
    {
    	$text = "这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。这是火山引擎TTS V3最新版本的测试文本，倍速可以设置到5倍，也能精准控制慢速。";
    	$speaker = 'zh_female_shuangkuaisisi_moon_bigtts';
    	$volcTTSSv = \service\reuse\VolcTTS::singleton();
    	$ttsResult = $volcTTSSv->runByV1($text, $speaker);
    	
    	_e();exit;
    	
    	print_r($ttsResult);exit;
        return ;
    }

}