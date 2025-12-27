<?php
namespace service;
require_once('vendor/autoload.php');
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use AlibabaCloud\Dara\Exception\DaraUnableRetryException;

use AlibabaCloud\SDK\ICE\V20201109\ICE;
use AlibabaCloud\SDK\ICE\V20201109\Models;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\ICE\V20201109\Models\UploadMediaByURLRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetUrlUploadInfosRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\RefreshUploadMediaRequest;
use AlibabaCloud\Credentials\Credential;

use AlibabaCloud\SDK\ICE\V20201109\Models\SearchEditingProjectRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\CreateEditingProjectRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetEditingProjectRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\UpdateEditingProjectRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\DeleteEditingProjectsRequest;

use AlibabaCloud\SDK\ICE\V20201109\Models\SubmitMediaProducingJobRequest;
use AlibabaCloud\SDK\ICE\V20201109\Models\GetMediaProducingJobRequest;

use AlibabaCloud\SDK\ICE\V20201109\Models\GetMediaInfoRequest;

use AlibabaCloud\SDK\ICE\V20201109\Models\RegisterMediaInfoRequest;
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
				$effectFont['Font'] = $captionRow['font-family'];
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
	private static function captionToSubtitleTrack($captionRow, $titleInfo = array())
	{
		$subtitleTrackClip = array( // 文案1
			'Type' => 'Text', // 类型
			'Content' => $captionRow['text'], // 文案内容
			'AdaptMode' => 'AutoWrap', // 自动换行
		);
		if (!empty($titleInfo['start'])) {
			$subtitleTrackClip['TimelineIn'] = $titleInfo['start']; // 显示时长-开始
		}
		if (!empty($titleInfo['end'])) {
			$subtitleTrackClip['TimelineOut'] = $titleInfo['end']; // 显示时长-结束
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
				$subtitleTrackClip['Font'] = $captionRow['font-family'];
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
$chlipInfo['transitionIds'] = array(-1);
$chlipInfo['filterIds'] = array(-1);
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
		if (!empty($editingInfo['titleInfo']))  {
			$titleInfo = $editingInfo['titleInfo'];
			$subtitleTrackClips = array();
			if (!empty($titleInfo['captionList'])) foreach ($titleInfo['captionList'] as $captionRow) {
				$subtitleTrackClip = self::captionToSubtitleTrack($captionRow, $titleInfo);
				$subtitleTrackClips[] = $subtitleTrackClip;
			}
			if (!empty($subtitleTrackClips)) {
				$subtitleTracks[] = array(
					'subtitleTrackClips' => $subtitleTrackClips,
				);
			}
		}

		// 背景音乐
		$musicAudioTrackClips = array();
		if (!empty($editingInfo['musicInfo'])) {
			$audioTrackClip = array(
				'MediaURL' => $editingInfo['musicInfo']['url'],
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
			$musicAudioTrackClips[] = $audioTrackClip;
		}

		// 贴纸
		$decalVideoTracks = array();
		if (!empty($editingInfo['decalInfo'])) {
			$decalInfo = $editingInfo['decalInfo'];
			$useLensList = $decalInfo['useLensList']; // 适用的场景 
			$clipIds = array(); // 适用的镜头ID
			foreach ($useLensList as $useLensRow) {
				if ($useLensRow['id'] == -1) {
					$clipIds = array();
					break;
				} else {
					$clipIds[] = $useLensRow['id'];
				}
			}
			if (empty($decalInfo['useLensList'])) {
				$clipIds = array();
			}
	
			$decalVideoTrackClips = array();
			if (!empty($decalInfo['media1'])) { // 第1个素材
				$videoTrackClip = array( // 文案1
					'Type' => $decalInfo['media1']['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $decalInfo['media1']['url'],
				);
				if (!empty($decalInfo['media1']['size'])) { // 大小
					$videoTrackClip['Width'] = 1;
					$videoTrackClip['Height'] = $decalInfo['media1']['size'] * 0.01;
				}
				if (!empty($decalInfo['media1']['x']) && !empty($decalInfo['media1']['y'])) { // 位置
					$videoTrackClip['X'] = $decalInfo['media1']['x'];
					$videoTrackClip['Y'] = $decalInfo['media1']['y'];
				}
				if (!empty($clipIds)) { // 适用的镜头
					$videoTrackClip['ClipId'] = reset($clipIds); // 镜头ID
				}
				$decalVideoTrackClips[] = $videoTrackClip;
			}
			if (!empty($decalInfo['media2'])) { // 第2个素材
				$videoTrackClip = array( // 文案1
					'Type' => $decalInfo['media2']['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $decalInfo['media2']['url'],
				);
				if (!empty($decalInfo['media2']['size'])) { // 大小
					$videoTrackClip['Width'] = 1;
					$videoTrackClip['Height'] = $decalInfo['media2']['size'] * 0.01;
				}
				if (!empty($decalInfo['media2']['x']) && !empty($decalInfo['media2']['y'])) { // 位置
					$videoTrackClip['X'] = $decalRow['media2']['x'];
					$videoTrackClip['Y'] = $decalRow['media2']['y'];
				}
				if (!empty($clipIds)) { // 适用的镜头
					$videoTrackClip['ClipId'] = reset($clipIds); // 镜头ID
				}
				$decalVideoTrackClips[] = $videoTrackClip;
			}
			if (!empty($decalVideoTrackClips)) {
				$decalVideoTracks[] = array(
					'VideoTrackClips' => $decalVideoTrackClips,
				);
			}
		}
	
		// 全局配音，如果有剪辑全局配音 ，镜头配音就不生效
		$editingAudioTrackClips = array(); // 全局配音
		if (!empty($editingInfo['dubType'])) { // 配音类型  1 手动设置  2  配音文件(文件夹-旁白配音)
			if (!empty($editingInfo['dubCaptionInfo']) && $editingInfo['dubType'] == 1) { // 手动配音
				$audioTrackClip = self::captionToAudioTrackClip($editingInfo['dubCaptionInfo'], $editingInfo);
				$editingAudioTrackClips[] = $audioTrackClip;
			} elseif (!empty($editingInfo['dubMediaInfo']) && $editingInfo['dubType'] == 2) { // 配音文件
				$dubMediaInfo = $editingInfo['dubMediaInfo'];
				$effectVolume = array(); // 音量效果
				if (!empty($editingInfo['volume'])) {
					if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
						$effectVolume = array(
							'Type' => 'Volume',
							'Gain' => $editingInfo['volume']['dubVolume'],
						);
					}
				}
				
				$audioTrackClip = array(
					'MediaURL' => $dubMediaInfo['url'], // 播放链接，视频/图片
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
			if (!empty($lensRow['mediaInfo']))  {
				$mediaInfo = $lensRow['mediaInfo'];
				$videoTrackClip = array(
					'MediaURL' => $mediaInfo['url'], // 播放链接，视频/图片
					'ReferenceClipId' => $mediaInfo['id'], // 镜头标记，用于对齐
					'Type' => $mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				if (!empty($mediaInfo['duration'])) { // 镜头设置 - 选择时长(秒) 
					$videoTrackClip['Duration'] = $mediaInfo['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
				}
				// 素材特效列表
				$effects = array();
				if (!empty($lensVolumeEffects)) {
					$effects = array_merge($effects, $lensVolumeEffects);
				}
				if (!empty($editingTransitionEffect)) { // 添加镜头间转场
					$effects[] = $editingTransitionEffect;
				}
				if (!empty($effects)) {
					$videoTrackClip['Effects'] = $effects;
				}
				$lensVideoTrackClips[] = $videoTrackClip;
			}
			$lensAudioTrackClips = array(); // 镜头的 AudioTracks 配音
			// 配音 - 文本字幕
			if (empty($editingAudioTrackClips) && !empty($lensRow['dubCaptionInfo']) && $lensRow['dubType'] == 1) { // 手动配音
				$audioTrackClip = self::captionToAudioTrackClip($lensRow['dubCaptionInfo'], $editingInfo, $lensRow);
				$audioTrackClips[] = $audioTrackClip;
			} elseif (empty($editingAudioTrackClips) && !empty($lensRow['dubMediaInfo']) && $lensRow['dubType'] == 2) { // 配音文件
				$effectVolume = array(); // 音量效果
				if (!empty($editingInfo['volume'])) {
					if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
						$effectVolume = array(
							'Type' => 'Volume',
							'Gain' => $editingInfo['volume']['dubVolume'],
						);
					}
				}
				$dubMediaInfo = $lensRow['dubMediaInfo'];
				
				$audioTrackClip = array(
					'MediaURL' => $dubMediaInfo['url'], // 播放链接，视频/图片
					'ClipId' => $dubMediaInfo['id'], // 镜头标记，用于对齐
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
		
		$effectTracks = array();
		$result = array();
		$videoTracks = array_merge($lensVideoTracks, $decalVideoTracks);// 视频轨道
		$audioTracks = array_merge($lensAudioTracks, $musicAudioTracks); // 音频轨道
		if (!empty($videoTracks)) {
			$result['VideoTracks'] = $videoTracks;
		}
		if (!empty($audioTracks)) {
			$result['AudioTracks'] = $audioTracks;
		}
		if (!empty($editingFilterEffectTrackItem)) { // 针对全局画面添加滤镜，只加1个滤镜
			$result['EffectTracks'] = array(
				array(
					'EffectTrackItems' => array($editingFilterEffectTrackItem),
				)
			);
		}
		if (!empty($subtitleTracks)) { // 标题
			$result['SubtitleTracks'] = $subtitleTracks;
		}
		return $result;
	}
	
	
	
	/**
	 * 获取云剪辑工程
	 *
	 * @return array
	 */
	public function getEditingProject($projectId)
	{
		$request = new GetEditingProjectRequest();
		$request->projectId = $projectId;
		$response = self::$client->getEditingProject($request);
		$project = $response->body->project;
		
		print_r($project);exit;
	}

//======================================	
	/**
	 * 创建云剪辑工程
	 *
	 * @return array
	 */
	public function createEditingProject($chipParam)
	{
		// 获取时间线
		$timeline = $this->getTimeline($chipParam);
		if (empty($timeline)) {
			return false;
		}
		// 创建云剪辑工程
		try {
			$request = new CreateEditingProjectRequest();
			$request->title = $chipParam['name'];
			$request->description = $chipParam['topic'];
			$request->timeline = json_encode($timeline);
			$response = self::$client->createEditingProject($request);
			$projectId = empty($response->body->project->projectId) ? array() : $response->body->project->projectId;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $projectId;
	}
	
	/**
	 * 删除云剪辑工程
	 *
	 * @return array
	 */
	public function deleteEditingProjects($projectIds)
	{
		try {
    		$request = new DeleteEditingProjectsRequest();
   	 		$request->projectIds = is_array($projectIds) ? implode(',', $projectIds) : $projectIds;
    		$response = $client->deleteEditingProjects($request);
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return true;
	}
	
	/**
	 * 通过project创建合成任务
	 *
	 * @return array
	 */
	public function submitMediaProducingJob($projectId, $chipParam)
	{
		$orientation = '';
		$width = $height = 0;
		if ($chipParam['ratio'] == '9:16') {
			$orientation = 'Horizontal';
		} elseif ($chipParam['ratio'] == '16:9') {
			$orientation = 'Vertical';
		} else {
			$width = 900;
			$height = 900;
		}
		$outputMediaConfig = array(
			'MediaURL' => '', // 指定输出到OSS的媒资文件URL。
			'Video' => array(
				'Fps' => $chipParam['fps'], // 输出视频流帧率
			),	
		);
		if (!empty($width) && empty($height)) {
			$outputMediaConfig['Width'] = $width;
			$outputMediaConfig['Height'] = $height;
		}
		$UserData = array(
			'NotifyAddress' => '', // 为任务完成的回调url
			'RegisterMediaNotifyAddress' => '', // 为成片媒资分析完成的回调	
		);
		try {
			// 通过project创建合成任务
		    $request = new SubmitMediaProducingJobRequest();
		    $request->projectId = $projectId;
		    $request->outputMediaConfig = json_encode($outputMediaConfig);
		    $response = self::$client->submitMediaProducingJob($request);
			// 获取资源id
			
		} catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return true;
	}

}