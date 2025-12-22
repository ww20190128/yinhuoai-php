<?php
namespace service;
require_once('vendor/autoload.php');
use AlibabaCloud\SDK\ICE\V20201109\ICE;
use AlibabaCloud\SDK\ICE\V20201109\Models;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\ICE\V20201109\Models\UploadMediaByURLRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetUrlUploadInfosRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\RefreshUploadMediaRequest;

/**
 * 阿里云-剪辑
 *
 * @author
*/
class AliEditing extends ServiceBase
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
	 * @return AliPay
	 * 
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new AliEditing();
			$aliEditingConf = self::$instance->frame->conf['aliEditing'];
			$credential = new Credential([]);
			$config = new Config([
				'credential' => $credential,
				'endpoint' => 'ice.cn-shanghai.aliyuncs.com'
			]);
			$config->accessKeyId = $aliEditingConf['accessKeyId'];
			$config->accessKeySecret = $aliEditingConf['accessKeySecret'];
			$client = new ICE($config);
			self::$client = $client;
		}
		return self::$instance;
	}
	
	/**
	 * 通过URL上传
	 *
	 * @return array
	 */
	public function uploadMediaByURL($urlArr)
	{
		if (is_array($urlArr)) {
			$uploadURLs = implode(',', $urlArr);
		} else {
			$uploadURLs = $urlArr;
		}
		$aliEditingConf = self::$instance->frame->conf['aliEditing'];
		$uploadTargetConfig = array(
			'StorageType' => $aliEditingConf['StorageType'],
			'StorageLocation' => $aliEditingConf['StorageLocation'],
		);
		try {
			$request = new UploadMediaByURLRequest();
			$request->uploadURLs = $uploadURLs; // 媒体源文件 URL
			$request->uploadTargetConfig = json_encode($uploadTargetConfig);
			$response = $client->uploadMediaByURL($request);
			echo json_encode($response->body);
		} catch (TeaUnableRetryError $e) {
			var_dump($e);
		}
	}
	
	/**
	 * 获取URL上传信息
	 *
	 * @return array
	 */
	public function getUrlUploadInfos($urlArr, $jobIds = array())
	{
		if (is_array($urlArr)) { // 最多支持 10 个
			$uploadURLs = implode(',', $urlArr);
		} else {
			$uploadURLs = $urlArr;
		}
		try {
			$request = new GetUrlUploadInfosRequest();
			$request->uploadURLs = $uploadURLs; // 媒体源文件 URL
			$response = $client->uploadMediaByURL($request);
			echo json_encode($response->body);
		} catch (TeaUnableRetryError $e) {
			var_dump($e->getMessage());
    		var_dump($e->getErrorInfo());
    		var_dump($e->getLastException());
    		var_dump($e->getLastRequest());
		}
	}
	
	/**
	 * 获取URL上传信息（获取媒资数据）
	 * 
	 * @return array
	 */
	public function refreshUploadMedia($mediaId)
	{
		try {
			$request = new RefreshUploadMediaRequest();
			$request->mediaId = $mediaId; // 媒资 ID
			$response = $client->refreshUploadMedia($request);
			echo json_encode($response->body);
		} catch (TeaUnableRetryError $e) {
			var_dump($e->getMessage());
			var_dump($e->getErrorInfo());
			var_dump($e->getLastException());
			var_dump($e->getLastRequest());
		}
	}
	
	/**
	 * 获取音视频、图片和辅助媒资的上传地址和凭证。并创建媒资信息。
	 * 
	 * 获取上传地址和凭证为智能媒体服务的核心基础，是每个上传操作的必经过程。
	 * 如果视频上传凭证失效（默认有效期为 3000 秒），请调用刷新视频上传凭证接口重新获取上传凭证。
	 * 上传后，可通过配置回调，接收上传事件通知或调用 GetMediaInfo 接口根据返回的媒资状态来判断是否上传成功。
	 * 本接口返回的 MediaId 参数，可以用于媒资生命周期管理或媒体处理。
	 * @return array
	 */
	public function createUploadMedia($mediaId)
	{
		/**
		 * Type（必填）：文件类型，取值 video、image、audio、text、other。
Name（必填）：文件名，不带扩展名。
Size（选填）：文件大小。
Ext（必填）：文件扩展名。
		 */
		$fileInfo = array(
			'Type' => $Type, // 文件类型，取值 video、image、audio、text、other。
			'Name' => $name, // 文件名，不带扩展名。
			'Size' => $Size, // 文件大小。
			'Ext' => $Ext, // 文件扩展名。
		);
		$mediaMetaData = array( // 上传媒资的元数据
			'Title' => $Type, // 文件类型，取值 video、image、audio、text、other。
			'Description' => $name, // 文件名，不带扩展名。
			'CateId' => $Size, // 文件大小。
			'BusinessType' => $Ext, // 文件扩展名
		); 
		try {
			$request = new CreateUploadMediaRequest();
			$request->fileInfo = json_encode($fileInfo); // 文件信息
			$request->mediaMetaData = json_encode($mediaMetaData);
			$request->uploadTargetConfig = "fullText = '中国'";
			$response = $client->createUploadMedia($request);
			echo json_encode($response->body);
		} catch (TeaUnableRetryError $e) {
			var_dump($e->getMessage());
			var_dump($e->getErrorInfo());
			var_dump($e->getLastException());
			var_dump($e->getLastRequest());
		}
	}
	
// 云剪辑工程管理================
	/**
	 * 创建云剪辑工程
	 * 
	 * @return array
	 */
	public function createEditingProject($title, $description)
	{
		/**
		 * Type（必填）：文件类型，取值 video、image、audio、text、other。
		 Name（必填）：文件名，不带扩展名。
		 Size（选填）：文件大小。
		 Ext（必填）：文件扩展名。
		 */
		$fileInfo = array(
				'Type' => $Type, // 文件类型，取值 video、image、audio、text、other。
				'Name' => $name, // 文件名，不带扩展名。
				'Size' => $Size, // 文件大小。
				'Ext' => $Ext, // 文件扩展名。
		);
		$mediaMetaData = array( // 上传媒资的元数据
				'Title' => $Type, // 文件类型，取值 video、image、audio、text、other。
				'Description' => $name, // 文件名，不带扩展名。
				'CateId' => $Size, // 文件大小。
				'BusinessType' => $Ext, // 文件扩展名
		);
		try {
			$request = new CreateEditingProjectRequest();
		    $request->title = $title;
		    $request->description = $description;
		    $request->timeline = "{\"VideoTracks\":[{\"VideoTrackClips\":[{\"MediaId\":\"****9b4d7cf14dc7b83b0e801cbe****\"},{\"MediaId\":\"****9b4d7cf14dc7b83b0e801cbe****\"}]}]}";
		    $request->coverURL = "http://xxxx/coverUrl.jpg";
		    $response = $client->createEditingProject($request);
		    $projectId = $response->body->project->projectId;
		    var_dump($response);
		} catch (TeaUnableRetryError $e) {
			var_dump($e->getMessage());
			var_dump($e->getErrorInfo());
			var_dump($e->getLastException());
			var_dump($e->getLastRequest());
		}
	}
}