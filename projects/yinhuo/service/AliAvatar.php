<?php
namespace service;
require_once('vendor/autoload.php');
use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use AlibabaCloud\Dara\Exception\DaraUnableRetryException;
use AlibabaCloud\SDK\ICE\V20201109\ICE;
use AlibabaCloud\SDK\ICE\V20201109\Models;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\Dara\Models\RuntimeOptions;

use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;

use AlibabaCloud\SDK\ICE\V20201109\Models\CreateAvatarTrainingJobRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\SubmitAvatarTrainingJobRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetAvatarTrainingJobRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetAvatarRequest;

/**
 * 阿里云-数字人
 *
 * @author
*/
class AliAvatar extends ServiceBase
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
	 * @return AliAvatar
	 * 
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new AliAvatar();
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
	 * 创建数字人训练任务，配置数字人基础信息与训练所需要的素材信息等
	 *
	 * @return array
	 */
	public function createAvatarTraining($param)
	{
		$requestParam = array(
			'avatarName' => empty($param['avatarName']) ? '' : $param['avatarName'],	// 数字人名称
			'avatarDescription' => empty($param['avatarDescription']) ? '' : $param['avatarDescription'], // 数字人描述	
			'avatarType' => '2DAvatar', // 数字人类型
			'thumbnail' => empty($param['thumbnail']) ? '' : $param['thumbnail'], // 缩略图 URL
			'portrait' => empty($param['portrait']) ? '' : $param['portrait'],	//  头像图片的媒资 Id
			'video' => empty($param['video']) ? '' : $param['video'], // 训练视频媒资 ID	
			'transparent' => true,	// 训练视频是否支持透明通道
		);
		$client = self::$client;
		$createAvatarTrainingJobRequest = new CreateAvatarTrainingJobRequest($requestParam);
		$runtime = new RuntimeOptions([]);
		try {
			$response = $client->createAvatarTrainingJobWithOptions($createAvatarTrainingJobRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
	
	/**
	 * 正式提交数字人训练任务： 1. 首次提交训练； 2. 在训练失败后重新提交训练
	 *
	 * @return array
	 */
	public function submitAvatarTraining($jobId)
	{
		$requestParam = array(
			'jobId' => $jobId,		
		);
		$client = self::$client;
		$submitAvatarTrainingJobRequest = new SubmitAvatarTrainingJobRequest($requestParam);
		$runtime = new RuntimeOptions([]);
	
		try {
			$response = $client->submitAvatarTrainingJobWithOptions($submitAvatarTrainingJobRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
	
	/**
	 * 查询单个数字人任务的详细信息
	 *
	 * @return array
	 */
	public function getAvatarTraining($jobId)
	{
		$requestParam = array(
			'jobId' => $jobId,
		);
		$client = self::$client;
		$getAvatarTrainingJobRequest = new GetAvatarTrainingJobRequest($requestParam);
		$runtime = new RuntimeOptions([]);
	
		try {
			$response = $client->getAvatarTrainingJobWithOptions($getAvatarTrainingJobRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
	
	/**
	 * 查询某个已经训练成功的数字人的详细信息
	 *
	 * @return array
	 */
	public function getAvatar($jobId)
	{
		$requestParam = array(
				'jobId' => $jobId,
		);
		$client = self::$client;
		$getAvatarRequest = new GetAvatarRequest($requestParam);
		$runtime = new RuntimeOptions([]);
	
		try {
			$response = $client->getAvatarWithOptions($getAvatarRequest, $runtime);
			$jobId = empty($response->body->jobId) ? '' : $response->body->jobId;
		}  catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}
}