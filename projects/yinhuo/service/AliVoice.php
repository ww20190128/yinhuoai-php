<?php
namespace service;
require_once('vendor/autoload.php');
use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use AlibabaCloud\Dara\Exception\DaraUnableRetryException;
use AlibabaCloud\SDK\ICE\V20201109\ICE;
use AlibabaCloud\SDK\ICE\V20201109\Models;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\ICE\V20201109\Models\CreateCustomizedVoiceJobRequest;
use AlibabaCloud\Dara\Models\RuntimeOptions;

use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\SDK\ICE\V20201109\Models\SubmitCustomizedVoiceJobRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetDemonstrationForCustomizedVoiceJobRequest;

/**
 * 阿里云-声音克隆
 *
 * @author
*/
class AliVoice extends ServiceBase
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * 实例
	 *
	 * @var object
	 */
	private static $client;

	/**
	 * 单例模式
	 *
	 * @return AliVoice
	 * 
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new AliVoice();
			$aliEditingConf = self::$instance->frame->conf['aliEditing'];
			if (!empty($aliEditingConf['accessKeyId'])) {
				$credential = new Credential([]);
				$config = new Config([
					'credential' => $credential,
					'endpoint' => 'ice.cn-beijing.aliyuncs.com'
				]);
				$config->accessKeyId = $aliEditingConf['accessKeyId'];
				$config->accessKeySecret = $aliEditingConf['accessKeySecret'];
				$client = new ICE($config);
				self::$client = $client;
			}
		}
		return self::$instance;
	}
	
	/**
	 * 创建人声克隆任务
	 *
	 * @return array
	 */
	public function createCustomized($param)
	{
		$client = self::$client;
		$createCustomizedVoiceJobRequest = new CreateCustomizedVoiceJobRequest($param);
		$runtime = new RuntimeOptions([]);

		try {
			$response = $client->createCustomizedVoiceJobWithOptions($createCustomizedVoiceJobRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
	
	/**
	 * 获取训练个性化人声所需要朗读的文本及示例音频
	 *
	 * @return array
	 */
	public function getDemonstrationForCustomized($param)
	{
		$client = self::$client;
		$getDemonstrationForCustomizedVoiceJob = new GetDemonstrationForCustomizedVoiceJobRequest($param);
		$runtime = new RuntimeOptions([]);
	
		try {
			$response = $client->getDemonstrationForCustomizedVoiceJobWithOptions($getDemonstrationForCustomizedVoiceJob, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
	
	/**
	 * 提交人声克隆任务
	 *
	 * @return array
	 */
	public function submitCustomized($param)
	{	
		$client = self::$client;
		$submitCustomizedVoiceJobRequest = new SubmitCustomizedVoiceJobRequest($param);
		$runtime = new RuntimeOptions([]);
	
		try {
			$response = $client->submitCustomizedVoiceJobWithOptions($submitCustomizedVoiceJobRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
			
			print_r($response);exit;
		}  catch (DaraUnableRetryException $e) {
			
			print_r($e);exit;
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		
		exit;
		return $jobId;
	}
	
}