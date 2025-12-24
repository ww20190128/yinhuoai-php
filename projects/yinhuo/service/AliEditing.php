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
	 * 将文本组织成SubtitleTrack （标题）
	 * 
	 * @return array
	 */
	private static function captionToSubtitleTrack($captionRow, $titleRow = array())
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
			} elseif (!empty($captionRow['styleType']) && $captionRow['styleType'] == 1) { // 普通样式
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
		// 转场/滤镜
		$editingTransitionEffect = array(); // 用于镜头间的转场(放到镜头结束点)
		if (!empty($editingInfo['transitionIds'])) { // 转场
			if (in_array(-1, $editingInfo['transitionIds'])) { // 随机转场
				$editingTransitionEffect = array(
					'Type' => 'Transition',
					'SubType' => 'random',
				);
			} else { // 自选转场
				$editingTransitionEffect = array(
					'Type' => 'Transition',
					'SubType' => implode(',', $editingInfo['transitionIds']),
				);
			}
		}
		// 滤镜（针对全局画面添加滤镜）， 只加1种滤镜
		$editingFilterEffectTrackItem = array();
		if (!empty($editingInfo['filterIds'])) { // 滤镜
			if (in_array(-1, $editingInfo['filterIds'])) { // 随机滤镜
				$editingFilterEffectTrackItem = array(
					'Type' => 'Filter',
					'SubType' => 'random',
				);
			} else { // 自选滤镜
				$editingFilterEffectTrackItem = array(
					'Type' => 'Filter',
					'SubType' => implode(',', $editingInfo['filterIds']),
				);
			}
		}
		$lensList = empty($editingInfo['lensList']) ? array() : $editingInfo['lensList'];
		// 标题
		$subtitleTracks = array();
		if (!empty($editingInfo['titleList'])) foreach ($editingInfo['titleList'] as $key => $titleRow) {
			$subtitleTrackClips = array();
			if (!empty($titleRow['captionList'])) foreach ($titleRow['captionList'] as $captionRow) {
				// 跟镜头对齐
				if (empty($lensList[$key])) { // 没有镜头
					continue;
				}
				$subtitleTrackClip = self::captionToSubtitleTrack($captionRow, $titleRow);
				// 跟镜头对齐
				$subtitleTrackClip['ClipId'] = $lensList[$key]['id']; // 镜头ID
				$subtitleTrackClips[] = $subtitleTrackClip;
			}
			$subtitleTracks[] = array(
				'subtitleTrackClips' => $subtitleTrackClips,
			);
		}
		
		// 背景音乐
		$musicAudioTrackClips = array();
		if (!empty($editingInfo['musicList'])) foreach ($editingInfo['musicList'] as $key => $musicRow) {
			$audioTrackClip = array(
				'MediaURL' => $musicRow['url'],
			);
			$effectVolume = array();
			if (!empty($editingInfo['volume']['backgroundVolume'])) { // 背景音量
				$effectVolume = array(
					'Type' => 'Volume',
					'Gain' => $editingInfo['volume']['backgroundVolume'],
				);
			}
			$effects = array();
			if (!empty($effectVolume)) {
				$effects[] = $effectVolume;
			}
			if (!empty($effects)) {
				$audioTrackClip['Effects'] = $effects;
			}
			// 跟镜头对齐
			if (empty($lensList[$key])) { // 没有镜头
				continue;
			}
			$audioTrackClip['ClipId'] = $lensList[$key]['id']; // 镜头ID
			$musicAudioTrackClips[] = $audioTrackClip;
		}
		
		// 贴纸
		$decalVideoTracks = array();
		if (!empty($editingInfo['decalList'])) foreach ($editingInfo['decalList'] as $key => $decalRow) {
			$useLensList = $decalRow['useLensList']; // 适用的场景 
			$clipIds = array(); // 适用的镜头ID
			foreach ($useLensList as $useLensRow) {
				if ($useLensRow['id'] == -1) {
					$clipIds = array();
					break;
				} else {
					$clipIds[] = $useLensRow['id'];
				}
			}
			if (empty($decalRow['useLensList'])) {
				$clipIds = array();
			}
			if (empty($lensList[$key])) {
				continue;
			}
			$decalVideoTrackClips = array();
			if (!empty($decalRow['media1'])) { // 第1个素材
				$videoTrackClip = array( // 文案1
					'Type' => $decalRow['media1']['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $decalRow['media1']['url'],
				);
				if (!empty($decalRow['media1']['size'])) { // 大小
					$videoTrackClip['Width'] = 1;
					$videoTrackClip['Height'] = $decalRow['media1']['size'] * 0.01;
				}
				if (!empty($decalRow['media1']['x']) && !empty($decalRow['media1']['y'])) { // 位置
					$videoTrackClip['X'] = $decalRow['media1']['x'];
					$videoTrackClip['Y'] = $decalRow['media1']['y'];
				}
				if (!empty($clipIds)) { // 适用的镜头
					$videoTrackClip['ClipId'] = reset($clipIds); // 镜头ID
				}
				$decalVideoTrackClips[] = $videoTrackClip;
			}
			if (!empty($decalRow['media2'])) { // 第2个素材
				$videoTrackClip = array( // 文案1
					'Type' => $decalRow['media2']['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $decalRow['media2']['url'],
				);
				if (!empty($decalRow['media2']['size'])) { // 大小
					$videoTrackClip['Width'] = 1;
					$videoTrackClip['Height'] = $decalRow['media2']['size'] * 0.01;
				}
				if (!empty($decalRow['media2']['x']) && !empty($decalRow['media2']['y'])) { // 位置
					$videoTrackClip['X'] = $decalRow['media2']['x'];
					$videoTrackClip['Y'] = $decalRow['media2']['y'];
				}
				if (!empty($clipIds)) { // 适用的镜头
					$videoTrackClip['ClipId'] = reset($clipIds); // 镜头ID
				}
				$decalVideoTrackClips[] = $videoTrackClip;
			}
			$decalVideoTracks[] = array(
				'VideoTrackClips' => $decalVideoTrackClips,
			);
		}
		
		// 全局配音，如果有剪辑全局配音 ，镜头配音就不生效
		$editingAudioTrackClips = array(); // 全局配音
		if (!empty($editingInfo['dubType'])) { // 配音类型  1 手动设置  2  配音文件(文件夹-旁白配音)
			if (!empty($editingInfo['dubCaptionList']) && $editingInfo['dubType'] == 1) { // 手动配音
				foreach ($editingInfo['dubCaptionList'] as $captionRow) {
					$audioTrackClip = self::captionToAudioTrackClip($captionRow, $editingInfo);
					$editingAudioTrackClips[] = $audioTrackClip;
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
					$editingAudioTrackClips[] = $audioTrackClip;
				}
			}
		}

		$editingAudioTracks = array();
		$musicAudioTracks = array(); // 背景音乐
		// $decalVideoTracks
		if (!empty($musicAudioTrackClips)) { // 音乐
			$musicAudioTracks[] = array(
				'VideoTrackClips' => $musicAudioTrackClips,
			);
		}
		if (!empty($editingAudioTrackClips)) {
			$editingAudioTracks[] = array(
				'AudioTrackClips' => $editingAudioTrackClips,
			);
		}
		// 镜头
		$lensVideoTracks = array(); // 视频轨列表（镜头）
		$lensAudioTracks = array(); // 视频轨列表（配音）
		/**
		 * 一个镜头一个VideoTracks 元素array('VideoTrackClips'=> $lensVideoTrackClips)
		 * 一个镜头一个AudioTracks 元素array('AudioTrackClips'=> $lensAudioTrackClips)
		 */
		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensRow) {
			$lensVideoTrackClips = array(); // 镜头的VideoTracks 视频/图片
			// #关闭原声  #转场设置  #选择时长
			$lensVolumeEffects = array(); // 镜头的效果-关闭原声
			$lensTransitionEffects = array(); // 镜头的效果-转场 在素材间转场，1种效果
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
			if (!empty($lensRow['mediaList'])) foreach ($lensRow['mediaList'] as $mediaKey => $mediaRow) {
				$videoTrackClip = array(
					'MediaURL' => $mediaRow['url'], // 播放链接，视频/图片
					'ReferenceClipId' => $lensRow['id'], // 镜头标记，用于对齐
					'Type' => $mediaRow['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				if (!empty($lensRow['duration'])) { // 镜头设置 - 选择时长(秒) 
					$videoTrackClip['Duration'] = $lensRow['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
				}
				// 素材特效列表
				$effects = array();
				if (!empty($lensVolumeEffects)) {
					$effects = array_merge($effects, $lensVolumeEffects);
				}
				if ($mediaKey + 1 == count($lensRow['mediaList'])) { // 最后一个
					if (!empty($editingTransitionEffect)) { // 添加镜头间转场
						$effects[] = $editingTransitionEffect;
					}
				} else {
					if (!empty($lensTransitionEffects)) { // 添加素材间转场
						$effects = array_merge($effects, $lensTransitionEffects);
					}
				}
				if (!empty($effects)) {
					$videoTrackClip['Effects'] = $effects;
				}
				$lensVideoTrackClips[] = $videoTrackClip;
			}
			$lensAudioTrackClips = array(); // 镜头的 AudioTracks 配音
			// 配音 - 文本字幕
			if (empty($editingAudioTrackClips) && !empty($lensRow['dubCaptionList']) && $lensRow['dubType'] == 1) { // 手动配音
				foreach ($lensRow['dubCaptionList'] as $captionRow) {
					$audioTrackClip = self::captionToAudioTrackClip($captionRow, $editingInfo, $lensRow);
					$audioTrackClips[] = $audioTrackClip;
				}
			} elseif (empty($editingAudioTrackClips) && !empty($lensRow['dubMediaList']) && $lensRow['dubType'] == 2) { // 配音文件
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
						'ClipId' => $lensRow['id'], // 镜头标记，用于对齐
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
		return array(
			'VideoTracks' => array_merge($lensVideoTracks, $decalVideoTracks), // 视频轨道
			'AudioTracks' => array_merge($lensAudioTracks, $musicAudioTracks), // 音频轨道
			'EffectTracks' => array( // 针对全局画面添加滤镜，只加1个滤镜
				array(
					'EffectTrackItems' => array($editingFilterEffectTrackItem),
				)
			),
			'SubtitleTracks' => $subtitleTracks, // 标题
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