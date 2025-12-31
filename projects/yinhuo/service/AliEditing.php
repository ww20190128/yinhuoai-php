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
			$effectFont['ReferenceClipId'] = 'lens_' . $lensRow['id']; // 镜头标记，用于对齐
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
			if (!empty($captionRow['font']['text-align'])) { // 排版
				$effectFont['Alignment'] = $captionRow['font']['text-align'] == 'center' ? 'CenterCenter' : 'CenterLeft';
			}
			if (!empty($captionRow['font']['position'])) { // 位置
				$effectFont['Y'] = $captionRow['font']['position'];
			}
			if (!empty($captionRow['font']['font-size'])) { // 字号
				$effectFont['FontSize'] = $captionRow['font']['font-size'];
			}
			if (!empty($captionRow['font']['font-family'])) { // 字体
				$effectFont['Font'] = $captionRow['font']['font-family'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 2 && !empty($captionRow['style']['EffectColorStyle'])) { // 花字
				$effectFont['EffectColorStyle'] = $captionRow['style']['EffectColorStyle'];
			}
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['style']['color'])) { // 颜色
					$effectFont['FontColor'] = $captionRow['style']['color'];
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 2 && !empty($captionRow['style']['background'])) { // 字幕背景
					$effectFont['BackColour'] = $captionRow['style']['background'];
					$effectFont['BoderStyle'] = 3; // 不透明背景必须设置 BoderStyle = 3
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 3) { // 字幕边框
					if (!empty($captionRow['style']['border-size'])) { // 边框大小
						$effectFont['Outline'] = $captionRow['style']['border-size'];
					}
					if (!empty($captionRow['style']['border-color'])) { // 边框颜色
						$effectFont['OutlineColour'] = $captionRow['style']['border-color'];
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
			if (!empty($captionRow['font']['text-align'])) { // 排版
				$subtitleTrackClip['Alignment'] = $captionRow['font']['text-align'] == 'center' ? 'CenterCenter' : 'CenterLeft';
			}
			if (!empty($captionRow['font']['position'])) { // 位置
				$subtitleTrackClip['Y'] = $captionRow['font']['position'];
			}
			if (!empty($captionRow['font']['font-size'])) { // 字号
				$subtitleTrackClip['FontSize'] = $captionRow['font']['font-size'];
			}
			if (!empty($captionRow['font']['font-family'])) { // 字体
				$subtitleTrackClip['Font'] = $captionRow['font']['font-family'];
			}
		}
		if (!empty($captionRow['style'])) { // 样式
			if (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 2 && !empty($captionRow['style']['EffectColorStyle'])) { // 花字
				$subtitleTrackClip['EffectColorStyle'] = $captionRow['style']['EffectColorStyle'];
			} elseif (!empty($captionRow['style']['styleType']) && $captionRow['style']['styleType'] == 1) { // 普通样式
				if (!empty($captionRow['style']['color'])) { // 颜色
					$subtitleTrackClip['FontColor'] = $captionRow['style']['color'];
				}
				if (!empty($captionRow['style']['fontType']) && $captionRow['style']['fontType'] == 2 && !empty($captionRow['style']['background'])) { // 字幕背景
					$subtitleTrackClip['BackColour'] = $captionRow['style']['background'];
					$subtitleTrackClip['BoderStyle'] = 3; // 不透明背景必须设置 BoderStyle = 3
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
	private function getTimeline($editingInfo)
	{
		$editingBackgroundColorEffect = array(); // 纯色背景色
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
		$VideoTracks = array();
		if (!empty($editingBackgroundVideoTrackClip)) {
			$VideoTracks[] = array(
				'VideoTrackClips' => array(
					$editingBackgroundVideoTrackClip
				),
			);
		}
		// 镜头
		$lensMediaVideoTrack = $this->getLensMediaVideoTrack($editingInfo, $editingBackgroundColorEffect);
		$VideoTracks[] = $lensMediaVideoTrack;
		
		// 贴纸
		$decalVideoTrack = $this->getDecalVideoTrack($editingInfo);
		if (!empty($decalVideoTrack)) {
			$VideoTracks[] = $lensMediaVideoTrack;
		}
		$AudioTracks = array();
		// 全局配音
		$editingDubAudioTrack = $this->getEditingDubAudioTrack($editingInfo);
		if (!empty($editingDubAudioTrack)) {
			$AudioTracks[] = $editingDubAudioTrack;
		} else { // 镜头配音
			$lensDubAudioTrack = $this->getLensDubAudioTrack($editingInfo);
			if (!empty($lensDubAudioTrack)) {
				$AudioTracks[] = $lensDubAudioTrack;
			}
		}
		// 背景音乐
		$musicAudioTrack = $this->getMusicAudioTrack($editingInfo);
		if (!empty($musicAudioTrack)) {
			$AudioTracks[] = $musicAudioTrack;
		}
		$SubtitleTracks = array();
		// 标题
		$subtitleTrack = $this->getSubtitleTrack($editingInfo);
		if (!empty($subtitleTrack)) {
			$SubtitleTracks[] = $subtitleTrack;
		}
		$EffectTrack = array();
		// 特效
		$effectTrack = $this->getEffectTrack($editingInfo);
		if (!empty($effectTrack)) {
			$EffectTrack[] = $effectTrack;
		}
		$result = array();
		if (!empty($VideoTracks)) {
			$result['VideoTracks'] = $VideoTracks;
		}
		if (!empty($AudioTracks)) {
			$result['AudioTracks'] = $AudioTracks;
		}
		if (!empty($SubtitleTracks)) {
			$result['SubtitleTracks'] = $SubtitleTracks;
		}
		if (!empty($EffectTrack)) {
			$result['EffectTrack'] = $EffectTrack;
		}
		return $result;
	}
	
	/**
	 * 特效轨道
	 *
	 * @return EffectTrack
	 */
	private function getEffectTrack($editingInfo)
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
		$effectTrack = array();
		if (!empty($effectTrackItems)) { // 针对全局画面添加滤镜，只加1个滤镜
			$effectTrack = array(
				'EffectTrackItems' => $effectTrackItems,
			);
		}
		return $effectTrack;
	}
	
	
	/**
	 * 标题轨道
	 * 
	 * @return SubtitleTrack
	 */
	private function getSubtitleTrack($editingInfo) 
	{
		// 标题
		$subtitleTrack = array();
		if (!empty($editingInfo['titleInfo']))  {
			$titleInfo = $editingInfo['titleInfo'];
			$subtitleTrackClips = array();
			if (!empty($titleInfo['captionList'])) foreach ($titleInfo['captionList'] as $captionRow) {
				$subtitleTrackClip = self::captionToSubtitleTrack($captionRow, $titleInfo);
				$subtitleTrackClips[] = $subtitleTrackClip;
			}
			if (!empty($subtitleTrackClips)) {
				$subtitleTrack = array(
					'SubtitleTrackClips' => $subtitleTrackClips,
				);
			}
		}
		return $subtitleTrack;
	}
	
	/**
	 * 背景音乐轨道
	 *
	 * @return AudioTrack
	 */
	private function getMusicAudioTrack($editingInfo)
	{
		$audioTrack = array();
		if (!empty($editingInfo['musicInfo'])) {
			$audioTrackClip = array(
				'MediaURL' => $editingInfo['musicInfo']['url'],
				'LoopMode' => true, // 循环
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
			$audioTrackClips[] = $audioTrackClip;
		}
		if (!empty($audioTrackClips)) {
			$audioTrack = array(
				'AudioTrackClips' => $audioTrackClips,
			);
		}
		return $audioTrack;
	}
	
	/**
	 * 全局配音轨道
	 * 如果有剪辑全局配音 ，镜头配音就不生效
	 * 
	 * @return AudioTrack
	 */
	private function getEditingDubAudioTrack($editingInfo)
	{
		$audioTrackClips = array(); // 全局配音
		if (!empty($editingInfo['dubCaptionInfo'])) { // 手动配音
			$audioTrackClip = self::captionToAudioTrackClip($editingInfo['dubCaptionInfo'], $editingInfo);
			if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 2) { // 配音时长
				$audioTrackClip['Main'] = true;
			}
			$audioTrackClips[] = $audioTrackClip;
		} elseif (!empty($editingInfo['dubMediaInfo'])) { // 配音文件
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
				'MediaURL' => $editingInfo['dubMediaInfo']['url'], // 播放链接，视频/图片
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
			if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 2) { // 配音时长
				$audioTrackClip['Main'] = true;
			}
			$audioTrackClips[] = $audioTrackClip;	
		}
		$audioTrack = array();
		if (!empty($audioTrackClips)) {
			$audioTrack = array(
				'AudioTrackClips' => $audioTrackClips,
			);
		}
		return $audioTrack;
	}
	
	/**
	 * 镜头配音轨道
	 *
	 * @return AudioTrack
	 */
	private function getLensDubAudioTrack($editingInfo)
	{
		$lensAudioTrackClips = array();
		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensKey => $lensRow) {
			// 配音 - 文本字幕
			if (!empty($lensRow['dubCaptionInfo'])) { // 手动配音
				$audioTrackClip = self::captionToAudioTrackClip($lensRow['dubCaptionInfo'], $editingInfo, $lensRow);
				if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 2) { // 配音时长
					$audioTrackClip['Main'] = true;
				}
				$lensAudioTrackClips[] = $audioTrackClip;
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
					'ReferenceClipId' => 'lens_' . $dubMediaInfo['id'], // 镜头标记，用于对齐
				);
				if (!empty($editingInfo['volume']['dubSpeed'])) { // 配音语速
					$audioTrackClip['Speed'] = $editingInfo['volume']['dubSpeed'];
				}
				if (empty($editingInfo['showCaption'])) { // 是否显示字幕  0 不显示,  在配音中无效
						
				}
				// 素材特效列表
				$effects = array();
				if (!empty($effectVolume)) {
					$effects[] = $effectVolume;
				}
				if (!empty($effects)) {
					$audioTrackClip['Effects'] = $effects;
				}
				if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 2) { // 配音时长
					$audioTrackClip['Main'] = true;
				}
				$lensAudioTrackClips[] = $audioTrackClip;
			}
		}
		$audioTrack = array();
		if (!empty($lensAudioTrackClips)) {
			$audioTrack = array(
				'AudioTrackClips' => $lensAudioTrackClips,
			);
		}
		return $audioTrack;
	}
	
	/**
	 * 镜头素材轨道
	 *
	 * @return VideoTrack
	 */
	private function getLensMediaVideoTrack($editingInfo, $editingBackgroundColorEffect = array())
	{
		if (!empty($editingInfo['lensList'])) foreach ($editingInfo['lensList'] as $lensKey => $lensRow) {
			// #关闭原声  #转场设置  #选择时长
			$lensVolumeEffect = array(); // 镜头的效果-关闭原声
			$lensTransitionEffect = array(); // 镜头的效果-转场 在素材间转场，1种效果
			if (!empty($lensRow['transitionSubType']) && $lensKey != count($editingInfo['lensList']) - 1) { // #转场设置
				$lensTransitionEffect = array(
					'Type' => 'Transition',
					'SubType' => $lensRow['transitionSubType'],
				);
			}
			if (!empty($lensRow['originalSound'])) { // #关闭原声
				$lensVolumeEffect = array(
					'Type' => 'Volume',
					'Gain' => 0,
				);
			}
			if (!empty($lensRow['mediaInfo']))  {
				$mediaInfo = $lensRow['mediaInfo'];
				$videoTrackClip = array(
					'MediaURL' => $mediaInfo['url'], // 播放链接，视频/图片
					'ClipId' => 'lens_' . $mediaInfo['id'],
					'Type' => $mediaInfo['type'] == \constant\Folder::FOLDER_TYPE_VIDEO ? 'Video' : 'Image', // Video（视频）Image（图片）
				);
				if (!empty($editingInfo['durationType']) && $editingInfo['durationType'] == 1) { // 视频时长
					$videoTrackClip['Main'] = true;
				}
				if (!empty($mediaInfo['duration'])) { // 镜头设置 - 选择时长(秒) 
					$videoTrackClip['Duration'] = $mediaInfo['duration']; // 素材片段的时长，一般在素材类型是图片时使用。单位：秒，精确到小数点后4位。
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
				$lensVideoTrackClips[] = $videoTrackClip;
			}
		}
		$videoTrack = array();
		if (!empty($lensVideoTrackClips)) {
			$videoTrack = array(
				'VideoTrackClips' => $lensVideoTrackClips,
			);
		}
		return $videoTrack;
	}
	
	/**
	 * 贴纸轨道
	 *
	 * @return VideoTrack
	 */
	private function getDecalVideoTrack($editingInfo)
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
					$videoTrackClip['ReferenceClipId'] = 'lens_' . reset($clipIds); // 镜头ID
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
			$videoTrack = array();
			if (!empty($videoTrackClips)) {
				$videoTrack = array(
					'VideoTrackClips' => $videoTrackClips,
				);
			}
			return $videoTrack;
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
		$timeline = $this->getTimeline($chipParam);
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
		$aliEditingConf = self::$instance->frame->conf['aliEditing'];
		$mediaURL = $aliEditingConf['chipUrlBase'] . $chipParam['id'] . '_' . strtotime(date('Y-m-d H:i:s')) . '.mp4';
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
		$serve_url = $aliEditingConf = self::$instance->frame->conf['serve_url'];
		$userData = array(
			'NotifyAddress' => $serve_url . 'op=Project.producingJobcallback', // 为任务完成的回调url
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
	
}