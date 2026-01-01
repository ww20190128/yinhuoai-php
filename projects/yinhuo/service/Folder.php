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

    		// 注册媒体资源
    		$mediaId = $aliEditingSv->registerMediaInfo($url);
    		$mediaInfo = array();
    		if (!empty($mediaId)) { // 获取资源信息
    			$mediaInfo = $aliEditingSv->getMediaInfo($mediaId);
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
    		$folderMediaIds[] = $mediaId;
    	}
    	$folderEtt->set('mediaIds', implode(',', $folderMediaIds));
    	$folderEtt->set('updateTime', $now);
    	$folderDao->update($folderEtt);
    	return $this->info($folderEtt, $userEtt);
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
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$mediaInfo = empty($mediaEtt->mediaInfo) ? array() : json_decode($mediaEtt->mediaInfo, true);
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
    		$mediaInfo = empty($mediaEtt->mediaInfo) ? array() : json_decode($mediaEtt->mediaInfo, true);
    		$mediaModels[] = array(
    			'id' 			=> intval($mediaEtt->id),
    			'name'			=> $mediaEtt->name,
    			'type'			=> $mediaEtt->type,
    			'url'			=> $mediaEtt->url,
    			'createTime'	=> intval($mediaEtt->createTime),
    			'coverURL'		=> empty($mediaInfo['coverURL']) ? '' : $mediaInfo['coverURL'], // 视频封面
    			'duration'		=> empty($mediaInfo['duration']) ? 0 : ceil($mediaInfo['duration']), // 时长
    			'size'			=> empty($mediaInfo['fileSize']) ? 0 : ceil($mediaInfo['fileSize']), // 文件大小
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
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($subList, array($commonSv, 'sortByCreateTime'));
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
    		'subList'	=> $subList,
    		'mediaList'	=> $mediaModels,
    		'mediaNum'  => $mediaTotalNum,
    	);
    }
    
}