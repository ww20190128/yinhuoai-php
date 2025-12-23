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
	 * 获取任务时间线
	 *
	 * @return array
	 */
	public function getTimeline($editingInfo)
	{
		// 镜头
		$lensList = empty($editingInfo['lensList']) ? array() : $editingInfo['lensList'];
		$videoTracks = array(); // 视频轨列表（镜头）

		$subtitleTracks = array();
		foreach ($lensList as $lensRow) {
			$mediaList = $lensRow['mediaList']; // 镜头素材
			$videoTrackClips = array();
			
			foreach ($mediaList as $mediaRow) {
				$videoTrackClip = array(
					'MediaURL' => $mediaRow['url'], // 播放链接，视频/图片
					'Type' => $mediaRow['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				if (!empty($mediaRow['duration'])) { // 选择时长(秒) 选择时长
					$videoTrackClip['Duration'] = $mediaRow['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
				}
				$effects = array(); // 转场设置
				if (!empty($lensRow['transitionType'])) { // 有设置转场
					if ($lensRow['transitionType'] == 1 && !empty($lensRow['transitionIds'])) { // 自选转场
						$effects[] = array(
							'Type' => 'Transition',
							'SubType' => implode(',', $lensRow['transitionIds']),
						);
					} elseif ($lensRow['transitionType'] == 2) { // 随机转场， 59个随机
						$effects[] = array(
							'Type' => 'Transition',
							'SubType' => 'random',
						);
					}
				}
				if (empty($mediaRow['originalSound'])) { // 关闭原声
					$effects[] = array(
						'Type' => 'Volume',
						'Gain' => 0,
					);
				}
				if (!empty($effects)) {
					$videoTrackClip['Effects'] = $effects;
				}
				$videoTrackClips[] = $videoTrackClip;
			}
			$videoTracks[] = array(
				'VideoTrackClips' => $videoTrackClips,
			);
		}
		
		$audioTracks = array();
		// 全局配音,如果有剪辑全局配音 ，镜头配音就不生效
		if (!empty($editingInfo['dubType'])) { // 配音类型  1 手动设置  2  配音文件(文件夹-旁白配音)
			$audioTrackClips = array();
			if ($editingInfo['dubType'] == 1 && !empty($editingInfo['dubCaptionList'])) {
				foreach ($editingInfo['dubCaptionList'] as $captionRow) {
					
				}
			} elseif ($editingInfo['dubType'] == 2 && !empty($editingInfo['dubMediaList'])) {
				foreach ($editingInfo['dubMediaList'] as $mediaRow) {
					$audioTrackClip = array(
						'MediaURL' => $mediaRow['url'], // 播放链接，旁白配音
					);
					$audioTrackClips[] = $audioTrackClip;
				}
			}
			$audioTracks[] = array(
				'AudioTrackClips' => $audioTrackClips,
			);
		} else { // 镜头配音
			$audioTrackClips = array();
			foreach ($lensList as $lensRow) {
				if (!empty($lensRow['dubType'])) { // 镜头配音
					if ($lensRow['dubType'] == 1 && !empty($lensRow['dubCaptionList'])) { // 手动设置
					
					} elseif ($lensRow['dubType'] == 2 && !empty($lensRow['dubMediaList'])) { // 配音文件
						foreach ($lensRow['dubMediaList'] as $mediaRow) {
							$audioTrackClip = array(
								'MediaURL' => $mediaRow['url'], // 播放链接，旁白配音
							);
							$audioTrackClips[] = $audioTrackClip;
						}
					}
				}
			}
			$audioTracks[] = array(
				'AudioTrackClips' => $audioTrackClips,
			);
		}
		
		/**
		 * 1. 如果有剪辑全局配音 ，镜头配音就不生效
		 * 2. 只有文本配音时 ，才需要选配音演员
		 */
		
		$subtitleTracks = array(); // 字幕轨列表， 标题组
		$titleList = empty($editingInfo['titleList']) ? array() : $editingInfo['titleList'];
		foreach ($titleList as $titleRow) {
			$start = $titleRow['start'];
			$end = $titleRow['end'];
			foreach ($titleRow['captionList'] as $captionRow) {
				
				$subtitleTrackClip = array( // 文案1
					'TimelineIn' => 0, // 显示时长-开始
					'TimelineOut' => 0, // 显示时长-结束
					'Type' => 'Text', // 类型
					'X' => '0',
					'Y' => '200', // 位置
					'Font' => "KaiTi", // 字体
					'Content' => '这里是标题', // 文案内容
					'AdaptMode' => 'AutoWrap', // 自动换行
					'Alignment' => 'TopCenter', // 排版
					'FontSize' => '80', // 字号
					'FontColorOpacity' => '1', // 
					'EffectColorStyle' => 'CS0003-000011', // 花字
					'FontColor' => "#ffffff", // 样式-颜色
							
					"Outline" => 2, // 字体样式- 字幕边框- 边框大小
					"OutlineColour" => "#0e0100",  // 字体样式- 字幕边框- 边框颜色		
					'BorderStyle' => 3 , // BorderStyle 不透明背景必须设置 BoderStyle = 3 
					"BackColour" => "#000000", // 背景颜色
				);
				if (!empty($start)) {
					$subtitleTrackClip['TimelineIn'] = $start; // 显示时长-开始
				}
				if (!empty($end)) {
					$subtitleTrackClip['TimelineOut'] = $end; // 显示时长-结束
				}
				$audioTrackClips[] = $audioTrackClip;
			}
		}
		
		return array(
			'VideoTracks' => $videoTracks,
			'AudioTracks' => $audioTracks,
			'SubtitleTracks' => $subtitleTracks,
		);
	}
	
	/**
	 * 创建云剪辑工程
	 * 
	 * @return array
	 */
	public function createEditingProject($editingEtt)
	{
		$timeline = array(
			
			'AudioTracks' => array( // 	音频轨列表 （第二步）
				array(
					'AudioTrackClips' => array(
						array(
							'Content' => "回龙观盒马鲜生开业啦,盒马鲜生开业啦,附近的商场新开了一家盒马鲜生，今天是第一天开业,商场里的人不少，零食、酒水都比较便宜大家也快来看看呀",
							'Type' => 'AI_TTS',
							'Voice' => 'zhiqing',
							'Effects' => array(
								array( // 音量
									'Type' => 'Volume',
									'Gain' => 1,
								),
								array(
									'FontSize' => 34, // 字号
									'Y' => 0.658, // 位置
									'Alignment' => 'TopCenter', // 排版
									'AdaptMode' => 'AutoWrap',
									'Type' => 'AI_ASR',
									'Font' => 'FZHei-B01S' // 字体
								),
							),
						
						),
						
					),	
						
					'AudioTrackClips' => array(
						array( // 配音文件1
							"MediaUrl" => "https://your-bucket.oss-cn-shanghai.aliyuncs.com/your_audio.mp3",
						), 
						array( // 配音文件2
							"MediaUrl" => "https://your-bucket.oss-cn-shanghai.aliyuncs.com/your_audio.mp3",
						),
					),
				),
				array(
						
				),
			),
			'SubtitleTracks' => array( // 标题组（字幕轨列表）
				array( 
					'SubtitleTrackClips' => array(
						array( // 文案1
							'TimelineIn' => 0, // 显示时长-开始
							'TimelineOut' => 0, // 显示时长-结束
							'Type' => 'Text', // 类型
							'X' => '0',
							'Y' => '200', // 位置
							'Font' => "KaiTi", // 字体
							'Content' => '这里是标题', // 文案内容
							'AdaptMode' => 'AutoWrap', // 自动换行
							'Alignment' => 'TopCenter', // 排版
							'FontSize' => '80', // 字号
							'FontColorOpacity' => '1', // 
							'EffectColorStyle' => 'CS0003-000011', // 花字
							'FontColor' => "#ffffff", // 样式-颜色
							
							"Outline" => 2, // 字体样式- 字幕边框- 边框大小
							"OutlineColour" => "#0e0100",  // 字体样式- 字幕边框- 边框颜色
							
							'BorderStyle' => 3 , // BorderStyle 不透明背景必须设置 BoderStyle = 3 
							"BackColour" => "#000000", // 背景颜色
						),
						array( // 文案2
							'Type' => 'Text',
							'X' => '0',
							'Y' => '200',
							'Content' => '这里是标题',
							'Alignment' => 'TopCenter',
							'FontSize' => '80',
							'FontColorOpacity' => '1',
							'EffectColorStyle' => 'CS0003-000011',
						),
					),
				),
				
			),
			'EffectTracks' => array( // 针对全局画面添加滤镜
				array(
					'EffectTrackItems' => array(
						array(
							'Type' => 'Filter',
							'SubType' => 'wiperight,perlin', // random 随机转场
							'Duration' => 2, // 转场时长
								'ExtParams' => json_encode(array()), // 颜色调整
						),
					),
				),
			), 
		// 
		); // 云剪辑工程时间线
		$MaterialMaps = array(); // 工程关联素材
		
		try {
			// 创建云剪辑工程
    	$request = new CreateEditingProjectRequest();
   	 	$request->title = $editingEtt->name;
    	$request->description = "测试工程描述";
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