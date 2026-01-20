<?php
namespace service;

/**
 * 文件夹 逻辑类
 * 
 * @author 
 */
class Folder extends ServiceBase
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
     * @return Folder
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Folder();
        }
        return self::$instance;
    }

    /**
     * 创建文件夹
     * 
     * @return array
     */
    public function createFolder($userId, $type, $name, $parentId = 0)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEtt = $folderDao->getNewEntity();
		if (!empty($parentId)) { // 创建子文件夹
			$parentFolderEtt = $folderDao->readByPrimary($parentId);
			if (empty($parentFolderEtt) || $parentFolderEtt->status == \constant\Common::DATA_DELETE) {
				throw new $this->exception('文件夹已删除');
			}
			if (!empty($parentFolderEtt->parentId)) {
				throw new $this->exception('文件夹层级最多两级');
			}
		}
    	$now = $this->frame->now;
    	$folderEtt->userId 		= $userId;
    	$folderEtt->name 		= $name;
    	$folderEtt->type 		= $type;
    	$folderEtt->parentId 	= $parentId;
    	$folderEtt->mediaIds 	= ''; // 素材Id
    	$folderEtt->createTime 	= $now;
    	$folderEtt->updateTime 	= $now;
    	$folderId = $folderDao->create($folderEtt);
    	return $this->info($folderEtt, $userEtt);
    }
    
    /**
     * 获取媒体信息
     *
     * @return array
     */
    public function getMediaInfo($mediaEtt)
    {
    	$mediaInfo = empty($mediaEtt->mediaInfo) ? array() : json_decode($mediaEtt->mediaInfo, true);
    	$aliEditingSv = \service\AliEditing::singleton();
    	$mediaDao = \dao\Media::singleton();
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	if ($mediaEtt->type == \constant\Folder::FOLDER_TYPE_VIDEO && empty($mediaInfo['coverURL'])) { // 注册媒体资源
    		$mediaInfo = $this->getMediaInfoByUrl($mediaEtt->url);
    		if (!empty($mediaInfo)) {
    			$mediaEtt->set('mediaInfo', json_encode($mediaInfo, JSON_UNESCAPED_UNICODE));
    			$mediaDao->update($mediaEtt);
    		}
    	}
    	return $mediaInfo;
    }

    /**
     * 获取媒体信息
     *
     * @return array
     */
    private function getMediaInfoByUrl($url)
    {
    	$aliEditingSv = \service\AliEditing::singleton();
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	$registerMediaId = $aliEditingSv->registerMediaInfo($url);
    	if (!empty($registerMediaId)) { // 获取资源信息
    		$mediaInfo = $aliEditingSv->getMediaInfo($registerMediaId);
    	} else {
    		$mediaInfo = $aliEditingSv->getMediaInfo('', $url);
    	}
    	if (!empty($mediaInfo['coverURL'])) { // 上传封面
    		$coverContent = @file_get_contents($mediaInfo['coverURL']);
    		if (!empty($coverContent) && strlen($coverContent) > 0) {
    			$fileName = md5($mediaInfo['coverURL']);
    			$profileKey = "resources/cover/{$fileName}.jpg"; // 上传的目录
    			$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    			$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $coverContent);
    			if (!empty($ossResult)) {
    				$coverURL = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    				$mediaInfo['coverURL'] = $coverURL;
    			}
    		}
    	}
    	return $mediaInfo;
    }
    
    /**
     * 修改文件夹名称
     *
     * @return array
     */
    public function revise($userId, $id, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEtt = $folderDao->readByPrimary($id);
    	if (empty($folderEtt) || $folderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('文件夹已删除');
    	}
    	// 修改名称
    	if (!empty($info['name']) && $info['name'] != $folderEtt->name) {
    		$folderEtt->set('name', $info['name']);
    	}
    	$now = $this->frame->now;    
    	$folderEtt->set('updateTime', $now);
    	$folderDao->update($folderEtt);
    	return $this->info($folderEtt, $userEtt);
    }
    
    /**
     * 删除文件夹
     *
     * @return array
     */
    public function deleteFolder($userId, $id)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEtt = $folderDao->readByPrimary($id);
    	if (empty($folderEtt) || $folderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('文件夹已删除');
    	}
    	$mediaIds = empty($folderEtt->mediaIds) ? array() : explode(',', $folderEtt->mediaIds);
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = $mediaDao->readListByPrimary($mediaIds);
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $key => $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			unset($mediaEttList[$key]);
    		}
    	}
    	if (!empty($mediaEttList)) {
    		throw new $this->exception('文件夹下有素材，无法删除');
    	}
    	$folderDao->remove($folderEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 文件夹上传素材
     *
     * @return array
     */
    public function uploadMedias($userId, $id, $uploadFiles)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEtt = $folderDao->readByPrimary($id);
    	if (empty($folderEtt) || $folderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('文件夹已删除');
    	}
    	$mediaDao = \dao\Media::singleton();
    	$now = $this->frame->now;
    	$folderMediaIds = empty($folderEtt->mediaIds) ? array() : explode(',', $folderEtt->mediaIds);
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    	$aliEditingSv = \service\AliEditing::singleton();
    	if (is_iteratable($uploadFiles)) foreach ($uploadFiles as $uploadFile) {
    		$file = $uploadFile['file']; // 文件内容
    		$fileSize = filesize($file); // 文件大小
    		$fileInfo = pathInfo($file);
    		$fileName = md5(implode('', file($file)));
    		$extension = $fileInfo['extension'];
    		$subFolder = (ord(substr($fileName, 0, 1)) + ord(substr($fileName, 1, 1))) % 8;
    		$profileKey = "resources/{$folderEtt->type}/{$subFolder}/{$fileName}.{$extension}"; // 上传的目录
    		$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, file_get_contents($file));
    		if (empty($ossResult)) {
    			continue;
    		}
    		$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    		$mediaInfo = array();
    		if ($folderEtt->type == \constant\Folder::FOLDER_TYPE_VIDEO) { // 注册媒体资源
    			$mediaInfo = $this->getMediaInfoByUrl($url);
    		}
    		$mediaEtt = $mediaDao->getNewEntity();
    		$mediaEtt->name = $uploadFile['name'];
    		$mediaEtt->type = $folderEtt->type;
    		$mediaEtt->size = $fileSize;
    		$mediaEtt->url = $url;
    		$mediaEtt->mediaInfo = empty($mediaInfo) ? '' : json_encode($mediaInfo, JSON_UNESCAPED_UNICODE);
    		$mediaEtt->createTime = $now;
    		$mediaEtt->updateTime = $now;
    		$mediaId = $mediaDao->create($mediaEtt);
    		$folderMediaIds[] = intval($mediaId);
    	}
    	$folderEtt->set('mediaIds', implode(',', $folderMediaIds));
    	$folderEtt->set('updateTime', $now);
    	$folderDao->update($folderEtt);
    	return $this->info($folderEtt, $userEtt);
    }
    
    /**
     * 创建音频
     *
     * @return array
     */
    public function createAudio($content, $duration, $dubId)
    {
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    	$aliEditingSv = \service\AliEditing::singleton();
    	$extension = 'mp3';
    	$profileKey = "resources/dubAudio/{$dubId}.{$extension}"; // 上传的目录
    	$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $content);
    	if (empty($ossResult)) {
    		return false;
    	}
    	$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    	return $url;
    }
    
    /**
     * 删除文件夹的素材
     *
     * @return array
     */
    public function deleteMedias($userId, $id, $deleteMediaIds)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEtt = $folderDao->readByPrimary($id);
    	if (empty($folderEtt) || $folderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('文件夹已删除');
    	}
    	// 需要删除的媒体
    	$mediaDao = \dao\Media::singleton();
    	$deleteMediaEttList = $mediaDao->readListByPrimary($deleteMediaIds);
    	$deleteMediaEttList = $mediaDao->refactorListByKey($deleteMediaEttList);
    	if (!empty($deleteMediaEttList)) foreach ($deleteMediaEttList as $key => $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			unset($deleteMediaEttList[$key]);
    		}
    	}
    	$folderMediaIds = empty($folderEtt->mediaIds) ? array() : explode(',', $folderEtt->mediaIds);
    	foreach ($folderMediaIds as $key => $val) {
    		if (!empty($deleteMediaEttList[$val])) {
    			unset($folderMediaIds[$key]);
    		}
    	}
    	$now = $this->frame->now;
    	$folderEtt->set('mediaIds', empty($folderMediaIds) ? '' : implode(',', $folderMediaIds));
    	$folderEtt->set('updateTime', $now);
    	$folderDao->update($folderEtt);
    	return $this->info($folderEtt, $userEtt);
    }
    
    /**
     * 文件夹列表
     *
     * @return array
     */
    public function getList($userId, $type)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$folderDao = \dao\Folder::singleton();
    	$folderEttList = $folderDao->readListByIndex(array(
    		'userId' => $userId,
    		'type' => $type,
    	));
    	$folderModels = array();
    	$allMediaIds = array();
    	if (!empty($folderEttList)) foreach ($folderEttList as $folderEtt) {
    		if ($folderEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if (!empty($folderEtt->parentId)) {
    			continue;
    		}
    		// 媒体
    		$mediaIds = empty($folderEtt->mediaIds) ? array() : explode(',', $folderEtt->mediaIds);
    		$folderModels[$folderEtt->id] = array(
    			'id' 			=> intval($folderEtt->id),
    			'name'			=> $folderEtt->name,
    			'type'			=> $folderEtt->type,
    			'mediaIds'		=> $mediaIds,
    			'createTime'	=> intval($folderEtt->createTime),
    		);
    		$allMediaIds = array_merge($allMediaIds, $mediaIds);
    	}
    	$allMediaIds = array_unique($allMediaIds);
    	$subFolderEttList = empty($folderModels) ? array() : 
    		$folderDao->readListByWhere("`parentId` in (" . implode(',', array_keys($folderModels)) . ")");
    	$subFolderMap = array();
    	if (!empty($subFolderEttList)) foreach ($subFolderEttList as $subFolderEtt) {
    		if ($subFolderEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$subFolderMap[$subFolderEtt->parentId] = $subFolderEtt;
    	}
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = $mediaDao->readListByPrimary($allMediaIds);
    	$allMediaModels = array();
    	$aliEditingSv = \service\AliEditing::singleton();
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$mediaInfo = $this->getMediaInfo($mediaEtt);
    		$allMediaModels[$mediaEtt->id] = array(
    			'id' 			=> intval($mediaEtt->id),
    			'fileName'		=> $mediaEtt->name,
    			'type'			=> $mediaEtt->type,
    			'url'			=> $mediaEtt->url,
    			'createTime'	=> intval($mediaEtt->createTime),
    				
    			'coverURL'		=> empty($mediaInfo['coverURL']) ? '' : $mediaInfo['coverURL'], // 视频封面
    			'duration'		=> empty($mediaInfo['duration']) ? 0 : ceil($mediaInfo['duration']), // 时长
    			'size'			=> empty($mediaInfo['fileSize']) ? 0 : ceil($mediaInfo['fileSize']), // 文件大小
    		);
    	}
    	foreach ($folderModels as $folderId => $folderModel) {
    		$mediaModels = array();
    		foreach ($folderModel['mediaIds'] as $mediaId) {
    			if (empty($allMediaModels[$mediaId])) {
    				continue;
    			}
    			$mediaModels[] = $allMediaModels[$mediaId];
    		}
    		unset($folderModel['mediaIds']);
    		$folderModel['mediaNum'] = count($mediaModels);
    		$folderModel['subNum'] = empty($subFolderMap[$folderId]) ? 0 : count($subFolderMap[$folderId]);
    		$folderModels[$folderId] = $folderModel;
    	}
    	$commonSv   = \service\Common::singleton();
    	uasort($folderModels, array($commonSv, 'sortByCreateTime'));
    	return $folderModels;
    }
    
    /**
     * 文件夹详情
     *
     * @return array
     */
    public function info($folderEtt, $userEtt, $pageNum = 0, $pageLimit = 20)
    {
    	$folderDao = \dao\Folder::singleton();
    	if (is_numeric($folderEtt)) {
    		$folderEtt = $folderDao->readByPrimary($folderEtt);
    	}
    	$userDao = \dao\User::singleton();
    	if (is_numeric($userEtt)) {
    		$userEtt = $userDao->readByPrimary($userEtt);
    	}
    	if (empty($folderEtt) || $folderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('文件夹已删除');
    	}
   
    	if ($folderEtt->userId != $userEtt->userId) {
    		throw new $this->exception('文件夹已删除');
    	}
    	$mediaIds = empty($folderEtt->mediaIds) ? array() : explode(',', $folderEtt->mediaIds);
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = $mediaDao->readListByPrimary($mediaIds);
    	$mediaModels = array();
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$mediaInfo = $this->getMediaInfo($mediaEtt);
    		$mediaModels[] = array(
    			'id' 			=> intval($mediaEtt->id),
    			'name'			=> $mediaEtt->name,
    			'type'			=> $mediaEtt->type,
    			'url'			=> $mediaEtt->url,
    			'createTime'	=> intval($mediaEtt->createTime),
    			'updateTime'	=> intval($mediaEtt->updateTime),
    			'coverURL'		=> empty($mediaInfo['coverURL']) ? '' : $mediaInfo['coverURL'], // 视频封面
    			'duration'		=> empty($mediaInfo['duration']) ? 0 : intval($mediaInfo['duration']), // 时长
    			'size'			=> empty($mediaInfo['fileSize']) ? 0 : intval($mediaInfo['fileSize']), // 文件大小
    		);
    	}
    	$subFolderEttList = $folderDao->readListByIndex(array(
    		'parentId' => intval($folderEtt->id),
    	));
    	$subList = array(); // 子文件夹
    	if (!empty($subFolderEttList)) foreach ($subFolderEttList as $subFolderEtt) {
    		if ($subFolderEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$subMediaIds = empty($subFolderEtt->mediaIds) ? array() : explode(',', $subFolderEtt->mediaIds);
    		$subList[] = array(
    			'id' 			=> intval($subFolderEtt->id),
    			'name'			=> $subFolderEtt->name,
    			'type'			=> $subFolderEtt->type,
    			'mediaNum'		=> count($subMediaIds),
    			'createTime'	=> intval($subFolderEtt->createTime),
    			'createTimeStr'	=> date('Y-m-d H:i:s', $subFolderEtt->createTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($subList, array($commonSv, 'sortByCreateTime'));
    	//$subList = array_reverse($subList);
    	uasort($mediaModels, array($commonSv, 'sortByCreateTime'));
    	
    	// 符合条件的总条数
    	$mediaTotalNum = count($mediaModels); // 素材总数量
    	if (!empty($pageNum)) {
    		$mediaModels = array_slice($mediaModels, ($pageNum - 1) * $pageLimit, $pageLimit);
    	}
    	return array(
    		'id' 		=> intval($folderEtt->id),
    		'name'		=> $folderEtt->name,
    		'type'		=> $folderEtt->type,
    		'subList'	=> array_values($subList),
    		'mediaList'	=> array_values($mediaModels),
    		'mediaNum'  => $mediaTotalNum,
    	);
    }
    
    /**
     * 获取配音
     *
     * @return array
     */
    public function getTts($actorInfo, $dubCaptionInfo, $needUrl = false)
    {
    	// 如果配音有指定配音演员，使用指定的配音演员
    	$speaker = empty($dubCaptionInfo['speaker']) ? $actorInfo['id'] : $dubCaptionInfo['speaker'];
    	if (empty($dubCaptionInfo['text']) || empty($speaker)) {
    		return false;
    	}
    	$ttsParams = array();
    	if (!empty($actorInfo['language'])) {
    		$ttsParams['language'] = $actorInfo['language'];
    	}
    	$dubId = md5($speaker . $dubCaptionInfo['text']); // 字幕唯一标识
    	$dubFileDao = \dao\DubFile::singleton();
    	$dubFileEtt = $dubFileDao->readByPrimary($dubId);
    	$volcTTSSv = \service\reuse\VolcTTS::singleton();
    	$now = $this->frame->now;
    	$ttsFile = CACHE_PATH . 'tts' . DS . $dubId . '.mp3'; // 配音源文件
    	$content = '';
    	if (!empty($dubFileEtt) && !empty($dubFileEtt->url)) { // 有生成的远程链接，不需要重复生成
    		return array(
    			'id' 		=> $dubFileEtt->id,
    			'duration'	=> $dubFileEtt->duration,
    			'url'		=> $dubFileEtt->url,
    		);
    	} elseif (!empty($dubFileEtt)) { // 没有远程链接
    		if (file_exists($ttsFile)) {
    			$content = @file_get_contents($ttsFile);
    		}
    	}
    	if (empty($content)) { // 没有原内容，从火山云获取
    		$tries = 3;
    		do {
    			$ttsResult = $volcTTSSv->runByV3($dubCaptionInfo['text'], $speaker, $ttsParams);
    		} while (empty($ttsResult['content']) && --$tries > 0);
    		if (!empty($ttsResult['content'])) { // 配音成功
    			$content = $ttsResult['content'];
    			@file_put_contents($ttsFile, $content);
    		} else {
    			return false;
    		}
    	}
    	if (empty($dubFileEtt)) {
    		$dubFileEtt = $dubFileDao->getNewEntity();
    		$dubFileEtt->id = $dubId;
    		$dubFileEtt->duration = 0;
    		$dubFileEtt->content = '';
    		$dubFileEtt->url = '';
    		$dubFileEtt->actorSpeaker = $speaker;
    		$dubFileEtt->resourceId = empty($ttsResult['resourceId']) ? '' : $ttsResult['resourceId'];
    		$dubFileEtt->text = $dubCaptionInfo['text'];
    		$dubFileEtt->createTime = $now;
    		$dubFileEtt->updateTime = $now;
    		$dubFileDao->create($dubFileEtt);
    	}

    	// 需要生成音频链接
    	if (!empty($needUrl) && empty($dubFileEtt->url) && !empty($content)) {
    		$ossSv = \service\reuse\OSS::singleton();
    		$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    		$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    		$aliEditingSv = \service\AliEditing::singleton();
    		$extension = 'mp3';
    		$profileKey = "resources/dubAudio/{$dubId}.{$extension}"; // 上传的目录
    		$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $content);
    		if (!empty($ossResult)) {
    			$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    			$mediaInfo = $this->getMediaInfoByUrl($url); // 注册到媒资
    			$dubFileEtt = $dubFileDao->readByPrimary($dubId);
    			$dubFileEtt->set('url', $url);
    			$dubFileEtt->set('duration', empty($mediaInfo['duration'] ? '' : $mediaInfo['duration']));
    			$dubFileDao->update($dubFileEtt);
    		}
    	}
    	return array(
    		'id' 		=> $dubId,
    		'duration'	=> $dubFileEtt->duration,
    		'url'		=> $dubFileEtt->url,
    	);
    }
    
    /**
     * 获取配音
     *
     * @return array
     */
    public function getTtsByText($text, $speaker)
    {
    	$dubId = md5($speaker . $text);
    	// 配音源文件
    	$ttsFile = CACHE_PATH . 'tts' . DS . $dubId . '.mp3';
    	$content = '';
    	if (file_exists($ttsFile)) {
    		$content = @file_get_contents($ttsFile);	
    	} else {
    		$volcTTSSv = \service\reuse\VolcTTS::singleton();
    		$ttsResult = $volcTTSSv->runByV3($text, $speaker);
    		if (empty($ttsResult['content'])) {
    			return false;
    		}
    		@file_put_contents($ttsFile, $ttsResult['content']);
    		$content = $ttsResult['content'];
    	}

    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    	$aliEditingSv = \service\AliEditing::singleton();
    	$extension = 'mp3';
    	$profileKey = "resources/dubAudio/{$dubId}.{$extension}"; // 上传的目录
    	$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $content);
    	if (empty($ossResult)) {
    		return false;
    	}
    	$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    	$mediaInfo = $this->getMediaInfoByUrl($url); // 注册到媒资
    	$mediaInfo['url'] = $url;
    	return $mediaInfo;
    }
    
}