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
	 * 视频默认时长
	 *
	 * @var object
	 */
	const VIDEO_DEFAULT_DURATION = 7;
	
	/**
	 * 转场时长
	 *
	 * @var object
	 */
	const TRANSITION_DURATION = 1;
	
	/**
	 * 单例模式
	 *
	 * @return AliEditing
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
	private static function captionToAudioTrackClipByUrl($captionRow, $editingInfo, $lensRow = array())
	{
		$folderSv = \service\Folder::singleton();
		if (empty($captionRow['url']) || (isset($captionRow['duration']) && $captionRow['duration'] <= 0) || empty($captionRow['subtitles'])) {
			// 配音演员
			$actorInfo = empty($editingInfo['actorInfo']) ? array() : $editingInfo['actorInfo'];
			$ttsResult = $folderSv->getTts($actorInfo, $captionRow, true);		
			if (!empty($ttsResult['url'])) {
				$captionRow['url'] = $ttsResult['url'];
				$captionRow['subtitles'] = $ttsResult['subtitles'];
				$captionRow['duration'] = $ttsResult['duration'];
			}
		}

		if (empty($captionRow['url'])) {
			return false;
		}
		$audioTrackClip = array( // 文案1
			'MediaURL' => $captionRow['url'],
			'mediaDuration' => $captionRow['duration'], // 资源的时长
		);

		if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速 -500～500，默认值：0  0.2~3
			$audioTrackClip['Speed'] = $editingInfo['volume']['dubSpeed'];
		}
		// 字幕
		$subtitles = empty($captionRow['subtitles']) ? array() : $captionRow['subtitles'];
	
		$subtitleTrackClips = array();
		if (!empty($subtitles)) {
			foreach ($subtitles as $subtitleRow) {
				$text = $subtitleRow['text'];
				$sectionArr = empty($subtitleRow['subtitles']) ? array() : $subtitleRow['subtitles'];
				$startTime = 0; // 文本开始时间
				$endTime = 0; // 文本结束时间
				foreach ($sectionArr as $sectionRow) { // 段落
					$subtitleTrackClip = self::dubSubtitleTrack($sectionRow, $captionRow, $lensRow);
					if (!empty($subtitleTrackClip)) {
						$subtitleTrackClips[] = $subtitleTrackClip;
					}
				}
			}
		} else { // 没有找到配音字幕
			
		}
		$effectVolume = array(); // 音量效果
		if (!empty($editingInfo['volume'])) {
			if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量0~3
				$effectVolume = array(
					'Type' => 'Volume',
					'Gain' => $editingInfo['volume']['dubVolume'],
				);
			}
		}
		$effects = array();
		if (!empty($effectVolume)) {
			$effects[] = $effectVolume;
		}
		if (!empty($effects)) {
			$audioTrackClip['Effects'] = $effects;
		}
		return array(
			'audioTrackClip' => $audioTrackClip,
			'subtitleTrackClips' => $subtitleTrackClips, 
		);
	}
	
	/**
	 * 将文本组织成AudioTrackClip（文本字幕）【未使用】
	 *
	 * @return array
	 */
	private static function captionToAudioTrackClipByAI($captionRow, $editingInfo, $lensRow = array())
	{
		// 配音演员
		$actorInfo = empty($editingInfo['actorInfo']) ? array() : $editingInfo['actorInfo'];
		$audioTrackClipAI_TTS = array( // 文案1
				'Type' => 'AI_TTS', // 类型
				'Content' => $captionRow['text'], // 文案内容
				'Voice' => empty($actorInfo) ? 'zhiqing' : $actorInfo['id'], // 配音   全局
		);
		if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速 -500～500，默认值：0
			$audioTrackClipAI_TTS['SpeechRate'] = $editingInfo['volume']['dubSpeed'];
		}
		// 字体效果AI_ASR 语音转文字
		$effectAI_ASR = array(
				'Type' => 'AI_ASR',
				'AdaptMode' => 'AutoWrap', // 字段换行
				'FontFace' => array(
						'Bold' => true,
						'Italic' => false,
						'Underline' => false,
				),
		);
		$effectVolume = array(); // 音量效果
		if (!empty($editingInfo['volume'])) {
			if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量
				$effectVolume = array(
						'Type' => 'Volume',
						'Gain' => $editingInfo['volume']['dubVolume'],
				);
			}
		}
	
		if (empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示
			$effectAI_ASR['FontColorOpacity'] = 0;
		}
		if (!empty($captionRow['font'])) { // 字体
			if (!empty($captionRow['font']['text-align'])) { // 排版
				$effectAI_ASR['Alignment'] = $captionRow['font']['text-align'] == 'center' ? 'TopCenter' : 'TopLeft';
			}
			if (!empty($captionRow['font']['position'])) { // 位置 0~ 100
				$effectAI_ASR['Y'] = min(100, max(0, $captionRow['font']['position']))  * 0.01;
			}
			if (!empty($captionRow['font']['font-size'])) { // 字号  12 ~ 48
				$effectAI_ASR['FontSize'] = min(48, max(12, $captionRow['font']['font-size'])) * 3;
			}
			if (!empty($captionRow['font']['font-family'])) { // 字体
				$effectAI_ASR['Font'] = $captionRow['font']['font-family'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 2 && !empty($captionRow['style']['effectColorStyle'])) { // 花字
				$effectAI_ASR['EffectColorStyle'] = $captionRow['style']['effectColorStyle'];
			}
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['style']['color'])) { // 颜色
					$effectAI_ASR['FontColor'] = $captionRow['style']['color'];
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 2 && !empty($captionRow['style']['background'])) { // 字幕背景
					$effectAI_ASR['SubtitleEffects'] = array(
							array(
									'Type' => 'Box',
									'Color' => $captionRow['style']['background'],
									'Opacity' => 0.9,
							),
					);
				}
	
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['style']['border-size'])) { // 边框大小
						$effectAI_ASR['Outline'] = $captionRow['style']['border-size'];
					}
					if (!empty($captionRow['style']['border-color'])) { // 边框颜色
						$effectAI_ASR['OutlineColour'] = $captionRow['style']['border-color'];
					}
				}
			}
		}
		$effects = array();
		if (!empty($effectAI_ASR)) {
			$effects[] = $effectAI_ASR;
		}
		if (!empty($effectVolume)) {
			$effects[] = $effectVolume;
		}
		if (!empty($effects)) {
			$audioTrackClipAI_TTS['Effects'] = $effects;
		}
		return $audioTrackClipAI_TTS;
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
			if (!empty($captionRow['font']['text-align'])) { // 排版
				$subtitleTrackClip['Alignment'] = $captionRow['font']['text-align'] == 'center' ? 'TopCenter' : 'TopLeft';
			}
			if (!empty($captionRow['font']['position'])) { // 位置 0~ 100
				$subtitleTrackClip['Y'] = min(100, max(0, $captionRow['font']['position']))  * 0.01;
			}
			if (!empty($captionRow['font']['font-size'])) { // 字号  12 ~ 48 
				$subtitleTrackClip['FontSize'] = min(48, max(12, $captionRow['font']['font-size'])) * 3;
			}
			if (!empty($captionRow['font']['font-family'])) { // 字体
				$subtitleTrackClip['Font'] = $captionRow['font']['font-family'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 2 && !empty($captionRow['style']['effectColorStyle'])) { // 花字
				$subtitleTrackClip['EffectColorStyle'] = $captionRow['style']['effectColorStyle'];
			} elseif (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['style']['color'])) { // 颜色
					$subtitleTrackClip['FontColor'] = $captionRow['style']['color'];
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 2 && !empty($captionRow['style']['background'])) { // 字幕背景
					$subtitleTrackClip['SubtitleEffects'] = array(
						array(
							'Type' => 'Box',
							'Color' => $captionRow['style']['background'],
							'Opacity' => 0.9,
						),
					);
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['style']['border-size'])) { // 边框大小
						$subtitleTrackClip['Outline'] = $captionRow['style']['border-size'];
					}
					if (!empty($captionRow['style']['border-color'])) { // 边框颜色
						$subtitleTrackClip['OutlineColour'] = $captionRow['style']['border-color'];
					}
				}
			}
		}
		if (isset($captionRow['lensIndex'])) { // 适用的镜头
			$subtitleTrackClip['ReferenceClipId'] = 'lens_' . $captionRow['lensIndex']; // 镜头ID
			unset($subtitleTrackClip['TimelineIn'], $subtitleTrackClip['TimelineOut']);
		}
		return $subtitleTrackClip;
	}
	
	/**
	 * 将文本组织成SubtitleTrack （配音）
	 *
	 * @return array
	 */
	private static function dubSubtitleTrack($subtitle, $captionRow, $lensRow = array())
	{
		$subtitleTrackClip = array( // 文案1
			'Type' => 'Text', // 类型
			'Content' => $subtitle['text'], // 文案内容
			'AdaptMode' => 'AutoWrap', // 自动换行
		);
		if (isset($lensRow['index'])) {
			$subtitleTrackClip['lensIndex'] = $lensRow['index'];
		}
		if (!empty($subtitle['startTime'])) {
			$subtitleTrackClip['startTime'] = $subtitle['startTime']; // 显示时长-开始
		}
		if (!empty($subtitle['endTime'])) {
			$subtitleTrackClip['endTime'] = $subtitle['endTime']; // 显示时长-结束
		}
		if (!empty($captionRow['font'])) { // 字体
			if (!empty($captionRow['font']['text-align'])) { // 排版
				$subtitleTrackClip['Alignment'] = $captionRow['font']['text-align'] == 'center' ? 'TopCenter' : 'TopLeft';
			}
			if (!empty($captionRow['font']['position'])) { // 位置 0~ 100
				$subtitleTrackClip['Y'] = min(100, max(0, $captionRow['font']['position']))  * 0.01;
			}
			if (!empty($captionRow['font']['font-size'])) { // 字号  12 ~ 48
				$subtitleTrackClip['FontSize'] = min(48, max(12, $captionRow['font']['font-size'])) * 3;
				
			}
			if (!empty($captionRow['font']['font-family'])) { // 字体
				$subtitleTrackClip['Font'] = $captionRow['font']['font-family'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 2 && !empty($captionRow['style']['effectColorStyle'])) { // 花字
				$subtitleTrackClip['EffectColorStyle'] = $captionRow['style']['effectColorStyle'];
			} elseif (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['style']['color'])) { // 颜色
					$subtitleTrackClip['FontColor'] = $captionRow['style']['color'];
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 2 && !empty($captionRow['style']['background'])) { // 字幕背景
					$subtitleTrackClip['SubtitleEffects'] = array(
						array(
							'Type' => 'Box',
							'Color' => $captionRow['style']['background'],
							'Opacity' => 0.9,
						),
					);
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['style']['border-size'])) { // 边框大小
						$subtitleTrackClip['Outline'] = $captionRow['style']['border-size'];
					}
					if (!empty($captionRow['style']['border-color'])) { // 边框颜色
						$subtitleTrackClip['OutlineColour'] = $captionRow['style']['border-color'];
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
	private static function getTimeline($editingInfo)
	{
		$editingBackgroundColorEffect = array(
			'Type' 		=> 'Background',
			'SubType' 	=> 'Blur',
			'Radius'	=> 0.1,
		);
		$editingBackgroundVideoTrackClip = array(); // 背景图片或视频
		if (!empty($editingInfo['background']) && !empty($editingInfo['background']['type'])) { // 背景
			if ($editingInfo['background']['type'] == 1 && !empty($editingInfo['background']['color'])) { // 纯色
				$editingBackgroundColorEffect = array(
					'Type' 		=> 'Background',
					'SubType' 	=> 'Color',
					'Color'		=> $editingInfo['background']['color'],
				);
			} elseif ($editingInfo['background']['type'] == 2 && !empty($editingInfo['background']['mediaInfo'])) {
				$editingBackgroundVideoTrackClip = array(
					'MediaURL' 	=> $editingInfo['background']['mediaInfo']['url'],
					'AdaptMode' => 'Cover',
					'Effects'	=> array(
						array(
							'Type' => 'Volume',
							'Gain' => 0,
						),
					),
				);
			} elseif ($editingInfo['background']['type'] == 3) { // 视频拉伸模糊
				$editingBackgroundColorEffect = array(
					'Type' 		=> 'Background',
					'SubType' 	=> 'Blur',
					'Radius'	=> 0.1,
				);
			} 
		}
		// 镜头视频
		$lensMediaVideoTrackClips = self::getLensMediaVideoTrackClips($editingInfo, $editingBackgroundColorEffect);
	
		// 贴纸
		$decalVideoTrackClips = self::getDecalVideoTrackClips($editingInfo);
		// 背景音乐
		$musicAudioTrackClips = self::getMusicAudioTrackClips($editingInfo);
		// 全局配音
		$editingDubAudioTrackClipsResult = self::getEditingDubAudioTrackClips($editingInfo);
		$lensDubAudioTrackClips = array(); // 配音-音频
		$dubSubtitleTrackClipsMap = array(); // 配音-字幕

		if (!empty($editingDubAudioTrackClipsResult['audioTrackClips'])) { // 全局配音
			if (!empty($editingDubAudioTrackClipsResult['subtitleTrackClips'])) { // 配音-字幕
				$dubSubtitleTrackClipsMap = $editingDubAudioTrackClipsResult['subtitleTrackClips'];
			}
		} else {
			$lensDubAudioTrackClipsResult = self::getLensDubAudioTrackClips($editingInfo);

			if (!empty($lensDubAudioTrackClipsResult['audioTrackClips'])) {
				$lensDubAudioTrackClips = $lensDubAudioTrackClipsResult['audioTrackClips'];
			}
			if (!empty($lensDubAudioTrackClipsResult['subtitleTrackClips'])) { // 配音-字幕
				$dubSubtitleTrackClipsMap = $lensDubAudioTrackClipsResult['subtitleTrackClips'];
			}
		}

		// 标题
		$subtitleTrackClips = self::getSubtitleTrackClips($editingInfo);
		
		// 特效
		$effectTrackItems = self::getEffectTrackItems($editingInfo);
		$result = array();
		$clipPrefix = 'lens_';
		
		// 组织主轨道
		if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 2) { // 以配音时长为主
			if (!empty($editingDubAudioTrackClipsResult['audioTrackClips'])) { // 有全局配音
				$result['AudioTracks'][] = array(
					'MainTrack' => true,
					'AudioTrackClips' => array_values($editingDubAudioTrackClipsResult['audioTrackClips']),
				);
				$result['VideoTracks'][] = array(
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
			} elseif (!empty($lensDubAudioTrackClips)) { // 镜头配音
				foreach ($lensMediaVideoTrackClips as $lensIndex => $VideoTrackClip) {
					if (empty($lensDubAudioTrackClips[$lensIndex])) { // 这段镜头没有配音
						$lensDubAudioTrackClips[$lensIndex] = $VideoTrackClip;
					}
					$VideoTrackClip['ReferenceClipId'] = $clipPrefix . $lensIndex;
					unset($VideoTrackClip['mediaDuration']);
					$lensMediaVideoTrackClips[$lensIndex] = $VideoTrackClip;
				}
	
				$timelineIn = 0;
				$dubTimelineInMap = array(); // 配音时间轴（入点）
				foreach ($lensDubAudioTrackClips as $lensIndex => $AudioTrackClip) {
					if (empty($lensMediaVideoTrackClips[$lensIndex])) {
						unset($lensDubAudioTrackClips[$lensIndex]);
						continue;
					}
					$AudioTrackClip['ClipId'] = $clipPrefix . $lensIndex;
					$dubTimelineInMap[$lensIndex] = $timelineIn;
					$timelineIn += $AudioTrackClip['mediaDuration'];
					unset($AudioTrackClip['mediaDuration']);
					$lensDubAudioTrackClips[$lensIndex] = $AudioTrackClip;
				}
				
				ksort($lensMediaVideoTrackClips);
				ksort($lensDubAudioTrackClips);
				
				$result['VideoTracks'][] = array(
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
				$result['AudioTracks'][] = array(
					'MainTrack' => true,
					'AudioTrackClips' => array_values($lensDubAudioTrackClips),
				);
			} else { // 没有配音，只播放视频
				$result['VideoTracks'][] = array(
					'MainTrack' => true,
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
			}
		} else { // 以视频时长为主
			if (!empty($editingDubAudioTrackClipsResult['audioTrackClips'])) { // 有全局配音
				$result['AudioTracks'][] = array(
					'AudioTrackClips' => array_values($editingDubAudioTrackClipsResult['audioTrackClips']),
				);
				$result['VideoTracks'][] = array(
					'MainTrack' => true,
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
			} elseif (!empty($lensDubAudioTrackClips)) { // 镜头配音
				$timelineIn = 0;
				$dubTimelineInMap = array(); // 配音时间轴（入点）
				foreach ($lensMediaVideoTrackClips as $lensIndex => $VideoTrackClip) {
					// 处理转场时长
					$transitionDuration = 0; // 转场的时长
					if (!empty($VideoTrackClip['Effects'])) foreach ($VideoTrackClip['Effects'] as $effect) {
						if ($effect['Type'] == 'Transition') {
							$transitionDuration = $effect['Duration'];
						}
					}
					$VideoTrackClip['ClipId'] = $clipPrefix . $lensIndex;
					
					$dubTimelineInMap[$lensIndex] = $timelineIn - max(0, ($lensIndex - 1) * $transitionDuration);
					$timelineIn += $VideoTrackClip['mediaDuration'];

					$lensMediaVideoTrackClips[$lensIndex] = $VideoTrackClip;
				}
			
				foreach ($lensDubAudioTrackClips as $lensIndex => $AudioTrackClip) {
					if (empty($lensMediaVideoTrackClips[$lensIndex])) {
						unset($lensDubAudioTrackClips[$lensIndex]);
						continue;
					}
					$AudioTrackClip['TimelineIn'] = $dubTimelineInMap[$lensIndex];
					
					$lensDubAudioTrackClips[$lensIndex] = $AudioTrackClip;
				}
				ksort($lensMediaVideoTrackClips);
				ksort($lensDubAudioTrackClips);
			
				$result['VideoTracks'][] = array(
					'MainTrack' => true,
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
				$result['AudioTracks'][] = array(
					'AudioTrackClips' => array_values($lensDubAudioTrackClips),
				);
			} else { // 没有配音，只播放视频
				$result['VideoTracks'][] = array(
					'MainTrack' => true,
					'VideoTrackClips' => array_values($lensMediaVideoTrackClips),
				);
			}
		}
		
		if (!empty($effectTrackItems)) { // 特效轨列表
			$result['EffectTracks'][] = array(
				'EffectTrackItems' => $effectTrackItems,
			);
		}
		if (!empty($subtitleTrackClips)) { // 标题
			$result['SubtitleTracks'][] = array(
				'SubtitleTrackClips' => $subtitleTrackClips,
			);
		}
		
		
		if (!empty($dubSubtitleTrackClipsMap)) { // 配音-字幕
			// 将配音字幕和音频对齐
			$dubSubtitleTrackClips = array();
			if (!empty($lensDubAudioTrackClips)) { // 镜头配音
				foreach ($dubSubtitleTrackClipsMap as $lensIndex => $list) {
					if (empty($lensDubAudioTrackClips[$lensIndex]) || !isset($dubTimelineInMap[$lensIndex])) {
						continue;
					}
					$TimelineIn = $dubTimelineInMap[$lensIndex]; // 音频的载入点
					foreach ($list as $key => $dubSubtitleTrackClip) {
						$dubSubtitleTrackClip['TimelineIn'] = $TimelineIn + $dubSubtitleTrackClip['startTime']; // 配音载入点
						$showTime = max(0, $dubSubtitleTrackClip['endTime'] - $dubSubtitleTrackClip['startTime']); // 显示时长
						$dubSubtitleTrackClip['TimelineOut'] = $dubSubtitleTrackClip['TimelineIn'] + $showTime; // 音频消失点
						unset($dubSubtitleTrackClip['startTime'], $dubSubtitleTrackClip['endTime']);
						$dubSubtitleTrackClips[] = $dubSubtitleTrackClip;
					}
				}
			} else { // 全局配音
				$TimelineIn = 0;
				foreach ($dubSubtitleTrackClipsMap as $list) {
					foreach ($list as $key => $dubSubtitleTrackClip) {
						$dubSubtitleTrackClip['TimelineIn'] = $TimelineIn + $dubSubtitleTrackClip['startTime']; // 配音载入点
						$showTime = max(0, $dubSubtitleTrackClip['endTime'] - $dubSubtitleTrackClip['startTime']); // 显示时长
						$dubSubtitleTrackClip['TimelineOut'] = $dubSubtitleTrackClip['TimelineIn'] + $showTime; // 音频消失点
						unset($dubSubtitleTrackClip['startTime'], $dubSubtitleTrackClip['endTime']);
						$dubSubtitleTrackClips[] = $dubSubtitleTrackClip;
					}
				}
			}
			if (!empty($dubSubtitleTrackClips)) {
				$result['SubtitleTracks'][] = array(
					'SubtitleTrackClips' => $dubSubtitleTrackClips,
				);
			}
		}
		if (!empty($musicAudioTrackClips)) { // 背景音
			$result['AudioTracks'][] = array(
				'AudioTrackClips' => $musicAudioTrackClips,
			);
		}
		if (!empty($decalVideoTrackClips)) { // 贴纸
			$result['VideoTracks'][] = array(
				'VideoTrackClips' => $decalVideoTrackClips,
			);
		}
		if (!empty($editingBackgroundVideoTrackClip)) { // 背景图片/视频，镜头，贴纸视频/图片
			$result['VideoTracks'][] = array(
				'VideoTrackClips' => array($editingBackgroundVideoTrackClip),
			);
		}

		return $result;
	}
	
	/**
	 * 特效轨道
	 *
	 * @return EffectTrackItems
	 */
	private static function getEffectTrackItems($editingInfo)
	{
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
		// 视频调色
		$editingFilterEffectTrackColorItem = array();
		if (!empty($editingInfo['color'])) { // 颜色配置
			$colorArr = $editingInfo['color'];
			$extParams = array('effect=color');
			if (!empty($colorArr['contrast'])) { // 对比度 取值范围 -100 ~ 100
				$extParams[] = "contrast={$colorArr['contrast']}";
			}
			if (!empty($colorArr['saturation'])) { // 饱和度  取值范围 -100 ~ 100
				$extParams[] = "saturability={$colorArr['saturation']}";
			}
			if (!empty($colorArr['luminance'])) { // 亮度   取值范围 -100 ~ 100
				$extParams[] = "brightness={$colorArr['luminance']}";
			}
			if (!empty($colorArr['chroma'])) { // 色度  取值范围 -100 ~ 100
				$extParams[] = "tint={$colorArr['chroma']}";
			}
			if (count($extParams) > 1) {
				$editingFilterEffectTrackColorItem = array(
					'Type' => 'Filter',
					'SubType' => 'color',
					'ExtParams' => implode(',', $extParams),
				);
			}
		}

		$effectTrackItems = array();
		if (!empty($editingFilterEffectTrackItem)) { // 针对全局画面添加滤镜，只加1个滤镜
			$effectTrackItems[] = $editingFilterEffectTrackItem;
		}
		if (!empty($editingFilterEffectTrackColorItem)) {
			$effectTrackItems[] = $editingFilterEffectTrackColorItem;
		}
		return $effectTrackItems;
	}
	
	/**
	 * 标题
	 * 
	 * @return SubtitleTrackClips
	 */
	private static function getSubtitleTrackClips($editingInfo) 
	{
		$subtitleTrackClips = array();
		if (!empty($editingInfo['titleInfo']))  {
			$titleInfo = $editingInfo['titleInfo'];
			if (!empty($titleInfo['captionList'])) foreach ($titleInfo['captionList'] as $captionRow) {
				$subtitleTrackClip = self::captionToSubtitleTrack($captionRow, $titleInfo);
				if (!empty($subtitleTrackClip)) {
					$subtitleTrackClips[] = $subtitleTrackClip;
				}
			}
		}
		return $subtitleTrackClips;
	}
	
	/**
	 * 背景音乐
	 *
	 * @return AudioTrackClips
	 */
	private static function getMusicAudioTrackClips($editingInfo)
	{
		$audioTrack = array();
		$audioTrackClips = array();
		if (!empty($editingInfo['musicInfo'])) {
			$audioTrackClip = array(
				'MediaURL' => $editingInfo['musicInfo']['url'],
				'LoopMode' => true, // 循环
			);
			$effectVolume = array();
			if (!empty($editingInfo['volume']['backgroundVolume'])) { // 背景音量,取值范围：0 ~ 10， 实际设置范围0~1
				$effectVolume = array(
					'Type' => 'Volume',
					'Gain' => $editingInfo['volume']['backgroundVolume'],
				);
			} else {
				$effectVolume = array(
					'Type' => 'Volume',
					'Gain' => 0.2,
				);
			}
			$effects = array();
			if (!empty($effectVolume)) {
				$effects[] = $effectVolume;
			}
			if (!empty($effects)) {
				$audioTrackClip['Effects'] = $effects;
			}
			$audioTrackClips[] = $audioTrackClip;
		}
		return $audioTrackClips;
	}
	
	/**
	 * 全局配音
	 * 如果有剪辑全局配音 ，镜头配音就不生效
	 * 
	 * @return AudioTrackClips
	 */
	private static function getEditingDubAudioTrackClips($editingInfo)
	{
		$audioTrackClips = array(); // 全局配音
		$dubSubtitleTrackClips = array();
		if (!empty($editingInfo['dubCaptionInfo'])) { // 手动配音
			$audioTrackClipResult = self::captionToAudioTrackClipByUrl($editingInfo['dubCaptionInfo'], $editingInfo);
			if (!empty($audioTrackClipResult['audioTrackClip'])) {
				$audioTrackClips[] = $audioTrackClipResult['audioTrackClip'];
				if (!empty($editingInfo['showCaption']) && !empty($audioTrackClipResult['subtitleTrackClips'])) { // 字幕
					$dubSubtitleTrackClips[] = $audioTrackClipResult['subtitleTrackClips'];
				} else { // 没有找到配音字幕
			
				}
			}
		} elseif (!empty($editingInfo['dubMediaInfo'])) { // 配音文件
			$effectVolume = array(); // 音量效果
			if (!empty($editingInfo['volume'])) {
				if (!empty($editingInfo['volume']['dubVolume'])) { // 配音音量， 取值范围，0~ 10  实际设置范围0~3
					$effectVolume = array(
						'Type' => 'Volume',
						'Gain' => $editingInfo['volume']['dubVolume'],
					);
				}
			}	
			$audioTrackClip = array(
				'MediaURL' => $editingInfo['dubMediaInfo']['url'], // 播放链接，视频/图片
			);
			if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速，取值范围0.1~100  实际设置范围  0~2.8
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
			$audioTrackClips[] = $audioTrackClip;	
		}
		return array(
			'audioTrackClips' => $audioTrackClips,
			'subtitleTrackClips' => $dubSubtitleTrackClips,
		);
	}
	
	/**
	 * 镜头配音
	 *
	 * @return AudioTrackClips
	 */
	private static function getLensDubAudioTrackClips($editingInfo)
	{
		$lensAudioTrackClips = array();
		$dubSubtitleTrackClips = array(); // 配音的字幕
		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensKey => $lensRow) {
			$lensRow['index'] = $lensKey;
			// 配音 - 文本字幕
			if (!empty($lensRow['dubCaptionInfo'])) { // 手动配音
				$audioTrackClipResult = self::captionToAudioTrackClipByUrl($lensRow['dubCaptionInfo'], $editingInfo, $lensRow);

				if (!empty($audioTrackClipResult['audioTrackClip'])) {
					$lensAudioTrackClips[$lensKey] = $audioTrackClipResult['audioTrackClip'];
					if (!empty($audioTrackClipResult['subtitleTrackClips']) && !empty($editingInfo['showCaption'])) { // 字幕
						$dubSubtitleTrackClips[$lensKey] = $audioTrackClipResult['subtitleTrackClips'];
					} else { // 没有找到配音字幕
						
					}
				}
			} elseif (!empty($lensRow['dubMediaInfo'])) { // 配音文件
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
				);
				if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速
					$audioTrackClip['Speed'] = $editingInfo['volume']['dubSpeed'];
				}
				if (!empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示,  在配音中无效
					// TODO  语音转文字 
				}
				// 素材特效列表
				$effects = array();
				if (!empty($effectVolume)) {
					$effects[] = $effectVolume;
				}
				if (!empty($effects)) {
					$audioTrackClip['Effects'] = $effects;
				}
				$lensAudioTrackClips[$lensKey] = $audioTrackClip;
			}
		}
		return array(
			'audioTrackClips' => $lensAudioTrackClips, // 配音的音频
			'subtitleTrackClips' => $dubSubtitleTrackClips, // 配音的字幕
		);
	}
	
	/**
	 * 镜头素材
	 *
	 * @return VideoTrackClips
	 */
	private static function getLensMediaVideoTrackClips($editingInfo, $editingBackgroundColorEffect = array())
	{
		$lensVideoTrackClips = array();
		$folderSv = \service\Folder::singleton();
		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensKey => $lensRow) {
			if ($lensRow['mediaInfo']['type'] == \constant\Folder::FOLDER_TYPE_VIDEO && $lensRow['mediaInfo']['duration'] <= 0) {
				$mediaInfo = $folderSv->getMediaInfoByUrl($lensRow['mediaInfo']['url']);
				$lensRow['mediaInfo']['duration'] = $mediaInfo['duration'];
			}

			// #关闭原声  #转场设置  #选择时长
			$lensVolumeEffect = array(); // 镜头的效果-关闭原声
			$lensTransitionEffect = array(); // 镜头的效果-转场 在素材间转场，1种效果
			
$lensRow['transitionIds'] = array('directional');
			if (!empty($lensRow['transitionIds']) && $lensKey != count($editingInfo['lensList']) - 1) { // #转场设置
				$lensTransitionEffect = array(
					'Type' => 'Transition',
					'SubType' => implode(',', $lensRow['transitionIds']),
					'Duration' => self::TRANSITION_DURATION,
				);
			}
			if (empty($lensRow['originalSound'])) { // #关闭原声
				$lensVolumeEffect = array(
					'Type' => 'Volume',
					'Gain' => 0,
				);
			}
			if (!empty($lensRow['mediaInfo']))  {
				$mediaInfo = $lensRow['mediaInfo'];
				$videoTrackClip = array(
					'MediaURL' => $mediaInfo['url'], // 播放链接，视频/图片
					'Type' => $mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				// 镜头有设置时长
				if (!empty($lensRow['duration']) && !empty($editingInfo['durationType']) && $editingInfo['durationType'] == 1) { // 镜头设置 - 选择时长(秒) 
					$videoTrackClip['In'] = 0;
					$videoTrackClip['Out'] = $lensRow['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
				}
				$mediaDuration = empty($mediaInfo['duration']) ? 0 : $mediaInfo['duration']; // 资源的时长
				if ($mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_IMAGE) { // 图片
					if (empty($lensRow['Duration'])) {
						$videoTrackClip['Duration'] = self::VIDEO_DEFAULT_DURATION; // 图片默认停留7秒
					} else {
						$videoTrackClip['Duration'] = $lensRow['Duration']; 
					}
					$mediaDuration = $videoTrackClip['Duration'];
					$videoTrackClip['AdaptMode'] = 'Cover'; // 被替换的内容在保持其宽高比的同时填充整个目标区域。如果对象的宽高比与内容框不相匹配，该对象将被剪裁以适应目标区域。
				}
				// 素材特效列表
				$effects = array();
				if (!empty($lensVolumeEffect)) {
					$effects[] = $lensVolumeEffect;
				}
				if (!empty($lensTransitionEffect)) { // 添加镜头间转场
					$effects[] = $lensTransitionEffect;
				}
				if (!empty($editingBackgroundColorEffect)) { // 添加镜头背景色
					$effects[] = $editingBackgroundColorEffect;
				}
				if (!empty($effects)) {
					$videoTrackClip['Effects'] = $effects;
				}
				$videoTrackClip['mediaDuration'] = $mediaDuration; // 资源的最大时长
				$lensVideoTrackClips[$lensKey] = $videoTrackClip;
			}
		}
		return $lensVideoTrackClips;
	}
	
	/**
	 * 贴纸
	 *
	 * @return VideoTrackClips
	 */
	private static function getDecalVideoTrackClips($editingInfo)
	{
		$videoTrackClips = array();
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
			$mediaList = array();
			if (!empty($decalInfo['media1'])) {
				$mediaList[] = $decalInfo['media1'];
			}
			if (!empty($decalInfo['media2'])) {
				$mediaList[] = $decalInfo['media2'];
			}
			foreach ($mediaList as $mediaInfo) {
				$videoTrackClip = array( // 文案1
					'Type' => $mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_IMAGE ? 'Image' : 'Vido', // 类型
					'MediaURL' => $mediaInfo['url'],
				);
				if (!empty($mediaInfo['size'])) { // 大小
					$videoTrackClip['Width'] = 1;
					$videoTrackClip['Height'] = $mediaInfo['size'] * 0.01;
				}
				if (!empty($mediaInfo['x']) && !empty($mediaInfo['y'])) { // 位置
					$videoTrackClip['X'] = $mediaInfo['x'];
					$videoTrackClip['Y'] = $mediaInfo['y'];
				}
				if (!empty($clipIds)) { // 适用的镜头
					$videoTrackClip['lensId'] = reset($clipIds); // 镜头ID
				}
				if ($mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_VIDEO) { // 视频静音
					$effectVolume = array(
						'Type' => 'Volume',
						'Gain' => 0,
					);
					$effects = array();
					$effects[] = $effectVolume;
					$videoTrackClip['Effects'] = $effects;
				}
				$videoTrackClips[] = $videoTrackClip;
			}
			return $videoTrackClips;
		}
	}
	
	/**
	 * 创建云剪辑工程
	 *
	 * @return array
	 */
	public function createEditingProject($chipParam)
	{
		// 创建云剪辑工程
		try {
			$request = new CreateEditingProjectRequest();
			$request->title = $chipParam['name'];
			$request->description = $chipParam['topic'];
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
    		$response = self::$client->deleteEditingProjects($request);
    		$requestId = empty($response->body->requestId) ? array() : $response->body->requestId;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $requestId;
	}
	
	/**
	 * 通过project创建合成任务
	 *
	 * @return array
	 */
	public function submitMediaProducingJob($chipParam)
	{
		$timeline = self::getTimeline($chipParam);

		$orientation = 'Horizontal';
		$width = $height = 0;
		if ($chipParam['ratio'] == '9:16') {
			$orientation = 'Horizontal';
		} elseif ($chipParam['ratio'] == '16:9') {
			$orientation = 'Vertical';
		} elseif ($chipParam['ratio'] == '1:1') {
			$width = 900;
			$height = 900;
		}
		$aliEditingConf = self::$instance->frame->conf['aliEditing'];
		$mediaURL = $aliEditingConf['chipUrlBase'] . $chipParam['id'] . '_' . strtotime(date('Y-m-d H:i:s')) . '_' . rand(1, 99999) . '.mp4';
		$outputMediaConfig = array(
			'MediaURL' => $mediaURL, // 指定输出到OSS的媒资文件URL。
			'Video' => array(
				'Fps' => $chipParam['fps'], // 输出视频流帧率
			),	
		);
		if (!empty($orientation)) {
			$outputMediaConfig['Video']['Orientation'] = $orientation;
		}
		if (!empty($width) && !empty($height)) {
			$outputMediaConfig['Width'] = $width;
			$outputMediaConfig['Height'] = $height;
		}
		$serveUrl = $aliEditingConf = self::$instance->frame->conf['serve_url'];
		$userData = array(
			'NotifyAddress' => $serveUrl . '?op=Project.producingJobcallback', // 为任务完成的回调url
		);
		try {
		    $request = new SubmitMediaProducingJobRequest();
		    $request->timeline = json_encode($timeline, JSON_UNESCAPED_UNICODE);
		    $request->outputMediaConfig = json_encode($outputMediaConfig, JSON_UNESCAPED_UNICODE);
		    $request->userData = json_encode($userData, JSON_UNESCAPED_UNICODE);
		    $response = self::$client->submitMediaProducingJob($request);
		    $jobId = empty($response->body->jobId) ? array() : $response->body->jobId;
		    

		} catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $jobId;
	}

	/**
	 * 获取单个合成任务
	 *
	 * @return array
	 */
	public function getMediaProducingJob($jobId)
	{
		try {
			$request = new GetMediaProducingJobRequest();
   	 		$request->jobId = $jobId;
    		$response = self::$client->getMediaProducingJob($request);

    		$mediaProducingJob = empty($response->body->mediaProducingJob) ? array() : $response->body->mediaProducingJob;

		} catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return (array)$mediaProducingJob;
	}

	/**
	 * 注册单个资源
	 *
	 * @return array
	 */
	public function registerMediaInfo($inputURL)
	{
		try {
			$request = new RegisterMediaInfoRequest();
    		$request->inputURL = $inputURL;
    		$response = self::$client->registerMediaInfo($request);
    		$mediaId = empty($response->body->mediaId) ? '' : $response->body->mediaId;
		} catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $mediaId;
	}
	
	/**
	 * 获取单个资源的信息
	 *
	 * @return array
	 */
	public function getMediaInfo($mediaId, $inputURL = '')
	{
		try {
			$request = new GetMediaInfoRequest();
			if (!empty($mediaId)) {
				$request->mediaId = $mediaId;
			} elseif (!empty($inputURL)) {
				$request->inputURL = $inputURL;
			} else {
				return false;
			}
			$response = self::$client->getMediaInfo($request);
			$mediaInfo = empty($response->body->mediaInfo) ? array() : $response->body->mediaInfo;
			$mediaBasicInfo = empty($mediaInfo->mediaBasicInfo) ? array() : $mediaInfo->mediaBasicInfo;
			$mediaId = empty($mediaBasicInfo->mediaId) ? '' : $mediaBasicInfo->mediaId;
			$mediaType = empty($mediaBasicInfo->mediaType) ? '' : $mediaBasicInfo->mediaType;
			$coverURL = empty($mediaBasicInfo->coverURL) ? '' : $mediaBasicInfo->coverURL;
			$fileInfo = empty($mediaInfo->fileInfoList) ? array() : reset($mediaInfo->fileInfoList);
			$fileBasicInfo = empty($fileInfo->fileBasicInfo) ? array() : $fileInfo->fileBasicInfo;
			
			$duration = empty($fileBasicInfo->duration) ? '' : $fileBasicInfo->duration;
			$fileSize = empty($fileBasicInfo->fileSize) ? '' : $fileBasicInfo->fileSize;
			$info = array(
				'mediaId' 	=> $mediaId, // 媒资Id
				'mediaType' => $mediaType, // 媒资类型
				'coverURL' 	=> $coverURL, // 封面
				'duration'	=> $duration, // 时长
				'fileSize'	=> $fileSize, // 文件大小
			);
		} catch (DaraUnableRetryException $e) {
			return false;
		} catch (TeaUnableRetryError $e) {
			return false;
		}
		return $info;
	}
	
}