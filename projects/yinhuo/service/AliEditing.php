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
	 * 将文本组织成AudioTrackClip（文本字幕）
	 * 
	 * @return array
	 */
	private static function captionToAudioTrackClip($captionRow, $editingInfo, $lensRow = array())
	{
		$audioTrackClip = array( // 文案1
			'Type' => 'AI_TTS', // 类型
			'Content' => $captionRow['text'], // 文案内容
			'Voice' => 'zhiqing', // 配音   全局
		);
		// 字体效果
		$effectFont = array(
			'type' => 'AI_ASR',
		);
		if (!empty($lensRow)) {
			$effectFont['ClipId'] = 'lens_' . $lensRow['id']; // 镜头标记，用于对齐
		}
		$effectVolume = array(); // 音量效果
		if (!empty($editingInfo['volume'])) {
			if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
				$effectVolume = array(
					'Type' => 'Volume',
					'Gain' => $editingInfo['volume']['dubVolume'],
				);
			}
			if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速
				$effectFont['SpeechRate'] = $editingInfo['volume']['dubSpeed'];
			}
		}
		if (empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示
			$effectFont['FontColorOpacity'] = 0;
		}
		if (!empty($captionRow['font'])) { // 字体
			if (!empty($captionRow['text-align'])) { // 排版
				$effectFont['Alignment'] = $captionRow['text-align'] == 'center' ? 'CenterCenter' : 'CenterLeft';
			}
			if (!empty($captionRow['position'])) { // 位置
				$effectFont['Y'] = $captionRow['position'];
			}
			if (!empty($captionRow['font-size'])) { // 字号
				$effectFont['FontSize'] = $captionRow['font-size'];
			}
			if (!empty($captionRow['font-family'])) { // 字体
				$effectFont['Font'] = $captionRow['font-size'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['styleType']) && $captionRow['styleType'] == 2 && !empty($captionRow['EffectColorStyle'])) { // 花字
				$effectFont['EffectColorStyle'] = $captionRow['EffectColorStyle'];
			}
			if (!empty($captionRow['styleType']) && $captionRow['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['color'])) { // 颜色
					$effectFont['FontColor'] = $captionRow['color'];
				}
				if (!empty($captionRow['fontType']) && $captionRow['fontType'] == 2 && !empty($captionRow['background'])) { // 字幕背景
					$effectFont['BackColour'] = $captionRow['background'];
					$effectFont['BoderStyle'] = 3; // 不透明背景必须设置 BoderStyle = 3
				}
				if (!empty($captionRow['fontType']) && $captionRow['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['border-size'])) { // 边框大小
						$effectFont['Outline'] = $captionRow['border-size'];
					}
					if (!empty($captionRow['border-color'])) { // 边框颜色
						$effectFont['OutlineColour'] = $captionRow['border-color'];
					}
				}
			}
		}
		$effects = array();
		if (!empty($effectFont)) {
			$effects[] = $effectFont;
		}
		if (!empty($effectVolume)) {
			$effects[] = $effectVolume;
		}
		if (!empty($effects)) {
			$audioTrackClip['Effects'] = $effects;
		}
		return $audioTrackClip;
	}
	
	/**
	 * 将文本组织成SubtitleTrack
	 * 
	 * @return array
	 */
	private function getSubtitleTrackByCaption($captionRow, $titleRow = array())
	{
		$subtitleTrackClip = array( // 文案1
			'Type' => 'Text', // 类型
			'Content' => $captionRow['text'], // 文案内容
			'AdaptMode' => 'AutoWrap', // 自动换行
		);
		if (!empty($titleRow['start'])) {
			$subtitleTrackClip['TimelineIn'] = $titleRow['start']; // 显示时长-开始
		}
		if (!empty($titleRow['end'])) {
			$subtitleTrackClip['TimelineOut'] = $titleRow['end']; // 显示时长-结束
		}
		if (!empty($captionRow['font'])) { // 字体
			if (!empty($captionRow['text-align'])) { // 排版
				$subtitleTrackClip['Alignment'] = $captionRow['text-align'] == 'center' ? 'CenterCenter' : 'CenterLeft';
			}
			if (!empty($captionRow['position'])) { // 位置
				$subtitleTrackClip['Y'] = $captionRow['position'];
			}
			if (!empty($captionRow['font-size'])) { // 字号
				$subtitleTrackClip['FontSize'] = $captionRow['font-size'];
			}
			if (!empty($captionRow['font-family'])) { // 字体
				$subtitleTrackClip['Font'] = $captionRow['font-size'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['styleType']) && $captionRow['styleType'] == 2 && !empty($captionRow['EffectColorStyle'])) { // 花字
				$subtitleTrackClip['EffectColorStyle'] = $captionRow['EffectColorStyle'];
			}
			if (!empty($captionRow['styleType']) && $captionRow['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['color'])) { // 颜色
					$subtitleTrackClip['FontColor'] = $captionRow['color'];
				}
				if (!empty($captionRow['fontType']) && $captionRow['fontType'] == 2 && !empty($captionRow['background'])) { // 字幕背景
					$subtitleTrackClip['BackColour'] = $captionRow['background'];
					$subtitleTrackClip['BoderStyle'] = 3; // 不透明背景必须设置 BoderStyle = 3
				}
				if (!empty($captionRow['fontType']) && $captionRow['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['border-size'])) { // 边框大小
						$subtitleTrackClip['Outline'] = $captionRow['border-size'];
					}
					if (!empty($captionRow['border-color'])) { // 边框颜色
						$subtitleTrackClip['OutlineColour'] = $captionRow['border-color'];
					}
				}
			}
		}
		return $subtitleTrackClip;
	}
	
	/**
	 * 获取任务时间线
	 *
	 * @return array
	 */
	private function getTimeline($editingInfo)
	{
		// 镜头
		$lensVideoTracks = array(); // 视频轨列表（镜头）
		$lensAudioTracks = array(); // 视频轨列表（配音）
		/**
		 * 一个镜头一个VideoTracks 元素array('VideoTrackClips'=> $lensVideoTrackClips)
		 * 一个镜头一个AudioTracks 元素array('AudioTrackClips'=> $lensAudioTrackClips)
		 */
		// 全局配音，如果有剪辑全局配音 ，镜头配音就不生效
		$globalAudioTrackClips = array(); // 全局配音
		if (!empty($editingInfo['dubType'])) { // 配音类型  1 手动设置  2  配音文件(文件夹-旁白配音)
			if (!empty($editingInfo['dubCaptionList']) && $editingInfo['dubType'] == 1) { // 手动配音
				foreach ($editingInfo['dubCaptionList'] as $captionRow) {
					$audioTrackClip = self::captionToAudioTrackClip($captionRow, $editingInfo);
					$globalAudioTrackClips[] = $audioTrackClip;
				}
			} elseif (!empty($editingInfo['dubMediaList']) && $editingInfo['dubType'] == 2) { // 配音文件
				$effectVolume = array(); // 音量效果
				if (!empty($editingInfo['volume'])) {
					if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
						$effectVolume = array(
							'Type' => 'Volume',
							'Gain' => $editingInfo['volume']['dubVolume'],
						);
					}
				}
				foreach ($editingInfo['dubMediaList'] as $mediaRow) {
					$audioTrackClip = array(
						'MediaURL' => $mediaRow['url'], // 播放链接，视频/图片
					);
					if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速
						$audioTrackClip['Speed'] = $editingInfo['volume']['dubSpeed'];
					}
					if (empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示,  在配音中无效
					}
					$effects = array();
					if (!empty($effectVolume)) {
						$effects[] = $effectVolume;
					}
					if (!empty($effects)) {
						$audioTrackClip['Effects'] = $effects;
					}
					$globalAudioTrackClips[] = $audioTrackClip;
				}
			}
		}

		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensRow) {
			$lensVideoTrackClips = array(); // 镜头的VideoTracks 视频/图片
			
			// #关闭原声  #转场设置  #选择时长
			$lensVolumeEffects = array(); // 镜头的效果-关闭原声
			$lensTransitionEffects = array(); // 镜头的效果-转场
			if (!empty($lensRow['transitionType'])) { // #转场设置
				if ($lensRow['transitionType'] == 1 && !empty($lensRow['transitionIds'])) { // 自选转场
					$lensTransitionEffects[] = array(
						'Type' => 'Transition',
						'SubType' => implode(',', $lensRow['transitionIds']),
					);
				} elseif ($lensRow['transitionType'] == 2) { // 随机转场， 59个随机
					$lensTransitionEffects[] = array(
						'Type' => 'Transition',
						'SubType' => 'random',
					);
				}
			}
			if (!empty($lensRow['originalSound'])) { // #关闭原声
				$lensVolumeEffects[] = array(
					'Type' => 'Volume',
					'Gain' => 0,
				);
			}
			if (empty($lensRow['mediaList'])) foreach ($lensRow['mediaList'] as $mediaRow) {
				$videoTrackClip = array(
					'MediaURL' => $mediaRow['url'], // 播放链接，视频/图片
					'ReferenceClipId' => "lens_" . $lensRow['id'], // 镜头标记，用于对齐
					'Type' => $mediaRow['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				if (!empty($lensRow['duration'])) { // 镜头设置 - 选择时长(秒) 
					$videoTrackClip['Duration'] = $lensRow['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
				}
				// 素材特效列表
				$effects = array();
				if (!empty($lensTransitionEffects)) {
					$effects = array_merge($effects, $lensTransitionEffects);
				}
				if (!empty($lensVolumeEffects)) {
					$effects = array_merge($effects, $lensVolumeEffects);
				}
				if (!empty($effects)) {
					$videoTrackClip['Effects'] = $effects;
				}
				$lensVideoTrackClips[] = $videoTrackClip;
			}
			$lensAudioTrackClips = array(); // 镜头的AudioTracks 配音
			// 配音 - 文本字幕
			if (!empty(empty($globalAudioTrackClips) && $lensRow['dubCaptionList']) && $lensRow['dubType'] == 1) { // 手动配音
				foreach ($lensRow['dubCaptionList'] as $captionRow) {
					$audioTrackClip = self::captionToAudioTrackClip($captionRow, $editingInfo, $lensRow);
					$audioTrackClips[] = $audioTrackClip;
				}
			} elseif (empty($globalAudioTrackClips) && !empty($lensRow['dubMediaList']) && $lensRow['dubType'] == 2) { // 配音文件
				$effectVolume = array(); // 音量效果
				if (!empty($editingInfo['volume'])) {
					if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
						$effectVolume = array(
							'Type' => 'Volume',
							'Gain' => $editingInfo['volume']['dubVolume'],
						);
					}
				}
				foreach ($lensRow['dubMediaList'] as $mediaRow) {
					$audioTrackClip = array(
						'MediaURL' => $mediaRow['url'], // 播放链接，视频/图片
						'ClipId' => "lens_" . $lensRow['id'], // 镜头标记，用于对齐
					);
					if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速
						$audioTrackClip['Speed'] = $editingInfo['volume']['dubSpeed'];
					}
					if (empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示,  在配音中无效
						
					}
					$effects = array();
					if (!empty($effectVolume)) {
						$effects[] = $effectVolume;
					}
					if (!empty($effects)) {
						$audioTrackClip['Effects'] = $effects;
					}
					$lensAudioTrackClips[] = $audioTrackClip;
				}
			}
			if (!empty($lensVideoTrackClips)) {
				$lensVideoTracks[] = array(
					'VideoTrackClips' => $lensVideoTrackClips,
				);
			}
			if (!empty($lensAudioTrackClips)) {
				$lensAudioTracks[] = array(
					'AudioTrackClips' => $lensAudioTrackClips,
				);
			}
		}

//======================================================
		// 标题
		$subtitleTracks = array(); // 字幕轨列表， 标题组
		if (!empty($editingInfo['titleList'])) foreach ($editingInfo['titleList'] as $titleRow) {
			foreach ($titleRow['captionList'] as $captionRow) {
				$subtitleTrackClip = $this->getSubtitleTrack($captionRow, $titleRow);
				$subtitleTrackClips[] = $subtitleTrackClip;
			}
			$subtitleTracks[] = array(
				'subtitleTrackClips' => $subtitleTrackClips,	
			);
		}
		
		// 贴纸
		$videoTracks = array();
		$decalList = empty($editingInfo['decalList']) ? array() : $editingInfo['decalList'];
		foreach ($decalList as $decalRow) {
			if (!empty($decalRow['media1'])) {
				$videoTrackClip = array( // 文案1
					'Type' => $decalRow['media1']['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $decalRow['media1']['url'],
				);
				if (!empty($decalRow['mediaSize1'])) { // 大小
					$subtitleTrackClip['Width'] = 1;
					$subtitleTrackClip['Height'] = $decalRow['mediaSize1'] * 0.01;
				}
				if (!empty($decalRow['mediaPostion1'])) { // 位置
					$subtitleTrackClip['X'] = $decalRow['mediaPostion1'];
					$subtitleTrackClip['Y'] = $decalRow['mediaPostion1'];
				}
			}
		}
		
		// 音乐
		$audioTrackClips = array();
		$musicList = empty($editingInfo['musicList']) ? array() : $editingInfo['musicList'];
		foreach ($musicList as $musicRow) {
			$audioTrackClip = array(
				'MediaURL' => $musicRow['url'],
			);
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
		$OutputMediaConfig = array(
			// 
			'MaxDuration' =>1, // 最大时长
			'Video' => array(
				'Fps'=> 50, // 帧率
				'Orientation' => 1,// 视频比例
			),	
		);
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
							// https://help.aliyun.com/zh/ims/developer-reference/effect-configuration-description?scm=20140722.S_help%40%40%E6%96%87%E6%A1%A3%40%40198824._.ID_help%40%40%E6%96%87%E6%A1%A3%40%40198824-RL_ExtParams-LOC_doc%7EUND%7Eab-OR_ser-PAR1_2102029c17665518368855674d3268-V_4-PAR3_r-RE_new5-P0_0-P1_0&spm=a2c4g.11186623.help-search.i40
								'ExtParams' => json_encode(array(
										
									// brightness 亮度
									// contrast 对比度
									// 	saturation  饱和度
									// tint  色度（色调）
								)), // 颜色调整
						),
					),
				),
			), 
		// 
		
		/**
		 * 
		 * 音量调整Effect Type:Volume 调音
		 * 		配音音量
		 * 		背景音量
		 * 		配音语速
		 * 
		 * https://help.aliyun.com/zh/ims/developer-reference/access-the-video-clip-web-sdk?scm=20140722.S_help%40%40%E6%96%87%E6%A1%A3%40%40453478._.ID_help%40%40%E6%96%87%E6%A1%A3%40%40453478-RL_%E8%AF%AD%E9%80%9F-LOC_doc%7EUND%7Eab-OR_ser-PAR1_212a5d3d17665522380104558d0553-V_4-PAR3_r-RE_new5-P0_1-P1_0&spm=a2c4g.11186623.help-search.i75
		 * interface VoiceConfig {
  volume: number; // 音量，取值0~100，默认值50
  speech_rate: number; // 语速，取值范围：-500～500，默认值：0
  pitch_rate: number; // 语调，取值范围：-500～500，默认值：0
  format?: string; // 输出文件格式，支持：PCM/WAV/MP3
}


 1.  视频比例
 2.  视频时长
 3. 视频帧率
		 */
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