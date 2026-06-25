<?php
namespace service;

require_once('vendor/autoload.php');
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * 首页 逻辑类
 * 
 * @author 
 */
class App extends ServiceBase
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
     * @return App
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }
        return self::$instance;
    }
    
    /**
     * 上传音乐
     *
     * @return array
     */
    public function uploadMusic()
    {
    	$aliEditingSv = \service\AliEditing::singleton();
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.yinhuo'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    	$musicDao = \dao\Music::singleton();
    	$musicEttList = $musicDao->readListByWhere("url = ''");
    	$now = $this->frame->now;
    	foreach ($musicEttList as $musicEtt) {
    		$publishUrl = $musicEtt->publishUrl;
    		$publishUrlContent = @file_get_contents($publishUrl);
    		$publishUrlSize = strlen($publishUrlContent);
    		if ($publishUrlSize > 0) {
    			$fileName = md5($publishUrl);
    			$profileKey = "resources/music/{$fileName}.mp3"; // 上传的目录
    			$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    			$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $publishUrlContent);
    			if (empty($ossResult)) {
    				continue;
    			}
    			$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    			$musicEtt->set('url', $url);
    			$musicDao->update($musicEtt);
    			echo $url . "\n";
    		}
    	}
    	exit;
    }
    
    /**
     * 同步音乐数据
     *
     * @return array
     */
    public function sysnMusic($authorization)
    {
//     $this->uploadMusic();exit;
    	$musicDao = \dao\Music::singleton();
		$musicClassifyDao = \dao\MusicClassify::singleton();
    	$url = "https://api.pyp.canzan.com/company/material/hot_music_cate";
    	$response = httpGetContents($url, null, 5, ["authorization: {$authorization}"]);
    	$response = empty($response) ? array() : json_decode($response, true);
    	$response = empty($response) ? array() : $response['data'];

    	$now = $this->frame->now;
    	foreach ($response as $row) {
    		$musicClassifyEtt = $musicClassifyDao->readByPrimary($row['id']);
    		if (empty($musicClassifyEtt)) {
    			$musicClassifyEtt = $musicClassifyDao->getNewEntity();
    			$musicClassifyEtt->id = $row['id'];
    			$musicClassifyEtt->name = $row['title'];
    			$musicClassifyDao->create($musicClassifyEtt);
    		}
    		$classifyId = $row['id'];
    		$listUrl = "https://api.pyp.canzan.com/company/material/hot_music_list?cate={$row['id']}&limit=1000";
    		$response = httpGetContents($listUrl, null, 5, ["authorization: {$authorization}"]);
    		$response = empty($response) ? array() : json_decode($response, true);
    		$list = empty($response['data']['list']) ? array() : $response['data']['list'];
    		$ids = array();
    		foreach ($list as $val) {
    			$ids[] = $val['id'];
    		}
    		$haveMusicEttList = $musicDao->readListByPrimary($ids);
    		$haveMusicEttList = $musicDao->refactorListByKey($haveMusicEttList);
    		foreach ($list as $val) {
    			if (!empty($haveMusicEttList[$val['id']])) {
    				continue;
    			}
    			$musicEtt = $musicDao->getNewEntity();
    			$musicEtt->id = $val['id'];
    			$musicEtt->name = $val['name'];
    			$musicEtt->duration = $val['duration'];
    			$musicEtt->publishUrl = $val['publish_url'];
    			$musicEtt->playUrl = $val['play_url'];
    			$musicEtt->classifyId = $classifyId;
    			$musicEtt->createTime = $now;
    			$musicEtt->updateTime = $now;
    			$musicDao->create($musicEtt);
    		}
    	}
    	exit;
    }
    
    /**
     * 同步配音数据
     *
     * @return array
     */
    public function sysnActor($authorization)
    {
    	$url = "https://api.pyp.canzan.com/company/product/preset/voices";
    	$response = httpGetContents($url, null, 5, ["authorization: {$authorization}"]);
    	$response = empty($response) ? array() : json_decode($response, true);
    	$response = empty($response) ? array() : $response['data'];
    	var_export($response);exit;
    }
    
    /**
     * 获取热门音乐分类
     *
     * @return array
     */
    public function getMusicClassifys()
    {
		$musicClassifyDao = \dao\MusicClassify::singleton();
		

		$musicClassifyEttList = $musicClassifyDao->readListByWhere();
		$list = array();
		if (!empty($musicClassifyEttList)) foreach ($musicClassifyEttList as $musicClassifyEtt) {
			$list[] = array(
				'id' 	=> intval($musicClassifyEtt->id),
				'name' 	=> $musicClassifyEtt->name,
			);
		}
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     * 
     * @return array
     */
    public function getActorClassifys()
    {
    	$actorArr = cfg('actorDoubao');
    	$classifyList = array();
    	$volcTTSSv = \service\reuse\VolcTTS::singleton();
    	
    	$folderSv = \service\Folder::singleton();
    	$dubFileDao = \dao\DubFile::singleton();
    	$now = $this->frame->now;
    	$text = "欢迎使用因火AI配音";
    	foreach ($actorArr as $actorClassify => $actorList) {
//     		if ($actorClassify != '1.0音色') {
//     			continue;
//     		}
    		$list = array();
    		foreach ($actorList as $rowArr) {
    			$list[] = array(
    				'name' 			=> $rowArr['name'],
    				'id' 			=> $rowArr['tag'],
    				'url'			=> $rowArr['link'],
    				'classify'		=> $actorClassify,
    				'resourceId'	=> 'seed-tts-1.0',
    				'language'		=> empty($rowArr['language']) ? '' : $rowArr['language'],
    			);
    			
    			continue;
    			
    			$dubId = md5($rowArr['tag'] . $text);
    			$dubFileEtt = $dubFileDao->readByPrimary($dubId);
    			if (empty($dubFileEtt)) {
    				$dubFileEtt = $dubFileDao->getNewEntity();
    				$dubFileEtt->id = $dubId;
    				$dubFileEtt->duration = 0;
    				$dubFileEtt->content = '';
    				$dubFileEtt->actorSpeaker = $rowArr['tag'];
    				$dubFileEtt->actorName = $rowArr['name'];
    				$dubFileEtt->actorClassify = $actorClassify;
    				$dubFileEtt->resourceId = '';
    				$dubFileEtt->text = $text;
    				$dubFileEtt->url = '';
    				$dubFileEtt->createTime = $now;
    				$dubFileEtt->updateTime = $now;
    				$dubFileDao->create($dubFileEtt);
    			}
    			$resourceIds = array('seed-tts-1.0', 'seed-tts-2.0', 'seed-tts-1.0-concurr'); // 默认都用的seed-tts-1.0
    			if (empty($dubFileEtt->url)) foreach ($resourceIds as $resourceId) {
    				$ttsParams = array();
    				if (!empty($rowArr['language'])) {
    					$ttsParams['language'] = $rowArr['language'];
    				}
    				$ttsResult = $volcTTSSv->runByV3($text, $rowArr['tag'], $resourceId, $ttsParams);
    				if (!empty($ttsResult['content'])) {
    					$dubFileEtt->set('content', base64_encode($ttsResult['content']));
    					$dubFileEtt->set('duration', ceil($ttsResult['duration']));
    					$dubFileEtt->set('resourceId', $resourceId);
    					$dubFileDao->update($dubFileEtt);
    					$dubFileUrl = $folderSv->createAudio($ttsResult['content'], $ttsResult['duration'], $dubId);
    					if (!empty($dubFileUrl)) {
    						$dubFileEtt->set('url', $dubFileUrl);
    						$dubFileDao->update($dubFileEtt);
    						break;
    					}
    				}
    			}
    		}
    		$classifyList[md5($actorClassify)] = array(
    			'id' 	=> md5($actorClassify),
    			'name'	=> $actorClassify,
    			'list'	=> array_values($list),
    		);
    	}
    	return $classifyList;
    	
    	$actorArr = cfg('actorAil');
    	$map = array();
    	if (!empty($actorArr)) foreach ($actorArr as $key => $value) {
    		$listArr = explode("\n", $value);
    		foreach ($listArr as $row) {
    			$rowArr = explode("|", $row);
    			if (count($rowArr) != 3) {
    				continue;
    			}
    			$url = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/audio_ai/' . $rowArr['1'] . ".wav";
    			
    			$url = 'https://zhile-static.oss-cn-beijing.aliyuncs.com/audio_ai/' . $rowArr['1'] . ".wav";
    			$one = array(
    				'name' 	=> $rowArr['0'],
    				'id' 	=> $rowArr['1'],
    				'url'	=> $url,
    			);
    			$map[$key][$one['id']] = $one;
    		}
    	}
    	$classifyList = array();
    	foreach ($map as $key => $list) {
    		$classifyList[md5($key)] = array(
    			'id' 	=> md5($key),
    			'name'	=> $key,
    			'list'	=> array_values($list),
    		);
    	}
    	return $classifyList;
    }
    
    /**
     * 获取配音演员列表
     * 
     * @return array
     */
    public function getActorList($id)
    {
    	$classifyList = $this->getActorClassifys();
    	return empty($classifyList[$id]) ? array() : $classifyList[$id]['list'];
    }
    
    /**
     * 获取热门音乐分类
     * 
     * @return array
     */
    public function getMusicList($classifyId)
    {
    	$musicDao = \dao\Music::singleton();
    	$musicEttList = $musicDao->readListByIndex(array(
    		'classifyId' => $classifyId,
    	));
    	$list = array();
    	if (!empty($musicEttList)) foreach ($musicEttList as $musicEtt) {
    		$list[] = array(
    			'id' 			=> intval($musicEtt->id),
    			'name'			=> $musicEtt->name,
    			'duration'		=> intval($musicEtt->duration),
    			'publishUrl'	=> $musicEtt->publishUrl,
    			'playUrl'		=> $musicEtt->playUrl,
    		);
    	}
    	return $list;
    }

    /**
     * 获取静态配置
     *
     * @return array
     */
    public function getStaticConfig()
    {
    	$filterList = array();
    	$transitionList = array();
    	$filterArr = cfg('filter');
    	$transitionArr = cfg('transition');
    	foreach ($filterArr as $name => $id) {
    		$filterList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	foreach ($transitionArr as $name => $id) {
    		$transitionList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	$effectColorStyleArr = cfg('effectColorStyle');
    	$base = "https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/effect/";
    	foreach ($effectColorStyleArr as $name => $id) {
    		$effectColorStyleList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    			'url'	=> $base . $name,
    		);
    	}
    	return array(
    		'filterList' => $filterList,
    		'transitionList' => $transitionList,
    		'effectColorStyleList' => $effectColorStyleList,
    	);
    }

    /**
     * 获取二维码
     *
     * @return array
     */
    public function getQrCode($url)
    {

    	// 生成二维码并保存到文件
		$result = Builder::create()
		    ->writer(new PngWriter())
		    ->data($url)  // 二维码内容
		    ->encoding(new Encoding('UTF-8'))
		    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())  // 高容错级别
		    ->size(300)  // 二维码尺寸（像素）
		    ->margin(10)  // 边距
		    ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
		    
		    ->validateResult(false)
		    ->build();
		// 保存到文件
		//$result->saveToFile(CACHE_PATH . 'qrcode.png');
		// 输出图片到浏览器
		header('Content-Type: ' . $result->getMimeType());
		$content =  $result->getString();
		if (empty($content)) {
			throw new $this->exception('生成二维码失败');
		}
	
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.yinhuo'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET'], true);
    	
    	
    	$fileName = md5($url);
    	$extension = 'png';
    	$profileKey = "resources/qrcode/{$fileName}.{$extension}"; // 上传的目录
    	$ossResult = $ossSv::privateUploadContent($ossConf['BUCKET'], $profileKey, $content);
    	if (empty($ossResult)) {
    		throw new $this->exception('生成二维码失败');
    	}
    	$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    	return array(
    		'qrCode' => $url,
    	);
    }
    
    /**
     * 文本内容安全识别
     *
     * @return array
     */
    public function getAccessToken()
    {
    	$accessTokenFile = CACHE_PATH . 'accessToken.txt';
    	$response = file_get_contents($accessTokenFile);
    	$response = empty($response) ? array() : json_decode($response, true);
    	$now = $this->frame->now;
    	if (!empty($response['access_token']) && !empty($response['expires_in']) 
    		&& ($now - $response['createTime']) <= $response['expires_in']) {
    		return $response['access_token'];
    	}
    	$weChat = empty($this->frame->conf['weChat']) ? array() : $this->frame->conf['weChat'];
    	if (empty($weChat)) {
    		return '';
    	}
    	$appId = $weChat['appId'];
    	$appSecret = $weChat['appSecret'];
    	$url = "https://api.weixin.qq.com/cgi-bin/token?appid={$appId}&secret={$appSecret}&grant_type=client_credential";
    	$response = httpGetContents($url);
    	$response = empty($response) ? array() : json_decode($response, true);
    	if (empty($response['access_token'])) {
    		throw new $this->exception("获取access_token失败！");
    	}
    	$response['createTime'] = $now;
		@file_put_contents($accessTokenFile, json_encode($response));
    	return $response['access_token'];
    }
    
    /**
     * 文本内容安全识别
     *
     * @return array
     */
    public function msgSecCheck($userId, $content, $info = array())
    {
    	if (empty($info['openid'])) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt)) {
    			return false;
    		}
    		$info['openid'] = $userEtt->openid;
    	}
    	$accessToken = $this->getAccessToken();
    	$weChatConf = $this->frame->conf['weChat'];
    	$data = array(
    		'content' => $content,
    		'version' => empty($info['version']) ? 2 : $info['version'],
    		'scene' => empty($info['scene']) ? 1 : $info['scene'], // 1 资料；2 评论；3 论坛；4 社交日志
    		'openid' => $info['openid'],
    	);
    	$apiPath = '/wxa/msg_sec_check';
    	$postBody = json_encode($data, JSON_UNESCAPED_UNICODE);
    	$signMessage = $apiPath . '&' . $postBody;
    	$paySig = hash_hmac('sha256', $signMessage, $weChatConf['appKey']);
    
    	// 完整请求URL
    	$url = 'https://api.weixin.qq.com' . $apiPath . '?access_token=' . $accessToken . '&pay_sig=' . $paySig;
    
    	$ch = curl_init();
    	curl_setopt_array($ch, [
    	CURLOPT_URL            => $url,
    	CURLOPT_POST           => true,
    	CURLOPT_POSTFIELDS     => $postBody,
    	CURLOPT_RETURNTRANSFER => true,
    	CURLOPT_HTTPHEADER     => [
    	'Content-Type: application/json; charset=utf-8',
    	],
    	CURLOPT_TIMEOUT        => 30,
    	]);
    	$response = curl_exec($ch);
    	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	$curlErr  = curl_error($ch);
    	curl_close($response);
    	$response = json_decode($response, true);
    	return $response;
    }
    
    /**
     * 文本内容安全识别
     *
     * @return array
     */
    public function mediaCheckAsync($userId, $media_type, $media_url,  $info = array())
    {
    	if (empty($info['openid'])) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt)) {
    			return false;
    		}
    		$info['openid'] = $userEtt->openid;
    	}
    	$accessToken = $this->getAccessToken();
    	$weChatConf = $this->frame->conf['weChat'];
    	$data = array(
    		'media_url' => $media_url,
    		'media_type' => intval($media_type),
    		'version' => empty($info['version']) ? 2 : $info['version'],
    		'scene' => empty($info['scene']) ? 1 : $info['scene'], // 1 资料；2 评论；3 论坛；4 社交日志
    		'openid' => $info['openid'],
    	);
    	$apiPath = '/wxa/media_check_async';
    	$postBody = json_encode($data, JSON_UNESCAPED_UNICODE);
    	$signMessage = $apiPath . '&' . $postBody;
    	$paySig = hash_hmac('sha256', $signMessage, $weChatConf['appKey']);
    
    	// 完整请求URL
    	$url = 'https://api.weixin.qq.com' . $apiPath . '?access_token=' . $accessToken . '&pay_sig=' . $paySig;
    
    	$ch = curl_init();
    	curl_setopt_array($ch, [
    	CURLOPT_URL            => $url,
    	CURLOPT_POST           => true,
    	CURLOPT_POSTFIELDS     => $postBody,
    	CURLOPT_RETURNTRANSFER => true,
    	CURLOPT_HTTPHEADER     => [
    	'Content-Type: application/json; charset=utf-8',
    	],
    	CURLOPT_TIMEOUT        => 30,
    	]);
    	$response = curl_exec($ch);
    	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	$curlErr  = curl_error($ch);
    	curl_close($response);
    	$response = json_decode($response, true);
    	return $response;
    }
    
}