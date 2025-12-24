<?php
namespace service;

/**
 * 剪辑 逻辑类
 * 
 * @author 
 */
class Editing extends ServiceBase
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
     * @return Editing
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Editing();
        }
        return self::$instance;
    }
    
    /**
     * 镜头详情
     *
     * @return array
     */
    public function lensInfo($lensId, $userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$editingLensDao = \dao\EditingLens::singleton();
    	$editingLensEtt = $editingLensDao->readByPrimary($lensId);
    	if (empty($editingLensEtt) || $editingLensEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('镜头已删除');
    	}
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingLensEtt->editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if ($editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$getEditingLensModels = $this->getEditingLensModels(array($editingLensEtt));
    	return $getEditingLensModels[$lensId];
    }
    
    /**
     * 添加镜头
     * $type  1 片头   2 片中  3片尾
     * @return array
     */
    public function createLens($userId, $editingId, $type = 0)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$now = $this->frame->now;
    	$info = $this->editingInfo($userEtt, $editingEtt);
    	$lensList = empty($info['lensList']) ? array() : $info['lensList'];
    	$editingLensDao = \dao\EditingLens::singleton();
    	$editingLensEtt = $editingLensDao->getNewEntity();
    	$editingLensEtt->editingId = $editingId;

    	$editingLensEtt->originalSound = 0; // 默认关闭原声
    	if ($type == 1) { // 片头
    		$editingLensEtt->index = -1;
    		$editingLensEtt->name = '片头';
    	} elseif ($type == 2) { // 片中
    		$editingLensEtt->index = 1;
    		$editingLensEtt->name = '片中' . $editingLensEtt->index;
    	} elseif($type == 3) {
    		$editingLensEtt->index = 100;
    		$editingLensEtt->name = '片尾';
    	} else {
    		$editingLensEtt->index = count($lensList) - 1;
    		$editingLensEtt->name = '片中' . $editingLensEtt->index;
    	}
    	$editingLensEtt->createTime = $now;
    	$editingLensEtt->updateTime = $now;
    	$editingLensId = $editingLensDao->create($editingLensEtt);
    	$getEditingLensModels = $this->getEditingLensModels(array($editingLensEtt));
    	return empty($getEditingLensModels[$editingLensId]) ? array() : $getEditingLensModels[$editingLensId];
    }
    
    /**
     * 删除镜头
     *
     * @return array
     */
    public function deleteLens($userId, $lensId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$editingLensDao = \dao\EditingLens::singleton();
    	$editingLensEtt = $editingLensDao->readByPrimary($lensId);
    	if (empty($editingLensEtt) || $editingLensEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('镜头已删除');
    	}
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingLensEtt->editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if ($editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if ($editingLensEtt->index < 0) {
    		throw new $this->exception('片头无法删除');
    	}
    	if ($editingLensEtt->index == 1) {
    		throw new $this->exception('第一个片中无法删除');
    	}
    	if ($editingLensEtt->index >= 100) {
    		throw new $this->exception('片尾无法删除');
    	}
    	$editingLensDao->remove($editingLensEtt);
    	// 删除字幕 dubCaptionIds
    	return array(
    		'result' => 1,	
    	);
    }
    
    /**
     * 镜头详情
     *
     * @return array
     */
    private function getEditingLensModels($editingLensEttList)
    {
   	 	$editingLensModels = array();
 		$allMediaIds = array();
 		$allCaptionIds = array();
 		if (!empty($editingLensEttList)) foreach ($editingLensEttList as $editingLensEtt) {
	 		if ($editingLensEtt->status == \constant\Common::DATA_DELETE) {
	    		continue;
	    	}
 			$mediaIds = empty($editingLensEtt->mediaIds) ? array() : array_map('intval', explode(',', $editingLensEtt->mediaIds)); // 素材
 			$transitionIds = empty($editingLensEtt->transitionIds) ? array() : array_map('string', explode(',', $editingLensEtt->transitionIds)); // 自选转场选中的ID
 			$dubCaptionIds = empty($editingLensEtt->dubCaptionIds) ? array() : array_map('intval', explode(',', $editingLensEtt->dubCaptionIds)); // 配音-手动设置-字幕
 			$dubMediaIds = empty($editingLensEtt->dubMediaIds) ? array() : array_map('intval', explode(',', $editingLensEtt->dubMediaIds)); // 配音-文件-素材(旁白配音)
 			$type = 2; // 片中
 			if ($editingLensEtt->index < 0) {
 				$type = 1; // 片头
 			}
 			if ($editingLensEtt->index >= 100) {
 				$type = 3; // 片尾
 			}
 			$editingLensModels[$editingLensEtt->id] = array(
 				'id' => intval($editingLensEtt->id),
 				'name' => $editingLensEtt->name,
 				'index' => intval($editingLensEtt->index), // 次序
 				'type' => $type, // 类型 1 片头 2 片中 3片尾
 				'createTime' => intval($editingLensEtt->createTime),
 				'updateTime' => intval($editingLensEtt->updateTime),
 				'mediaIds' => $mediaIds,
 				'originalSound' => intval($editingLensEtt->originalSound), // 是否关闭原声
 				'transitionType' => intval($editingLensEtt->transitionType), // 转场类型  1 自选转场  2 随机转场
 				'transitionIds'	=> $transitionIds, // 随机转场选择的ID
 				'duration' => intval($editingLensEtt->duration), // 自定义时长(秒)
 				'dubType' => intval($editingLensEtt->dubType), // 配音类型  1 手动设置  2  配音文件
 				'dubCaptionIds' => $dubCaptionIds, // 配音-手动设置-字幕
 				'dubMediaIds' => $dubMediaIds, // 配音-文件-素材(旁白配音)
 				'mediaList' => array(), // 素材列表
 				'dubCaptionList' => array(), // 配音-手动设置-字幕
 				'dubMediaList' => array(), // 配音-文件-素材(旁白配音)
 			);
 			$allMediaIds = array_merge($allMediaIds, $mediaIds, $dubMediaIds);
 			$allCaptionIds = array_merge($allCaptionIds, $dubCaptionIds);
 		}
 		// 字幕
 		$editingCaptionDao = \dao\EditingCaption::singleton();
 		$editingCaptionEttList = empty($allCaptionIds) ? array() : $editingCaptionDao->readListByPrimary($allCaptionIds);
 		$editingCaptionModels = $this->getCaptionModels($editingCaptionEttList);
 		// 素材
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = empty($allMediaIds) ? array() : $mediaDao->readListByPrimary($allMediaIds);
    	$mediaModels = $this->getMediaModels($mediaEttList);
    	$commonSv = \service\Common::singleton();
    	if (!empty($editingLensModels)) foreach ($editingLensModels as $key => $editingLensModel) {
    		$mediaList = array();
    		foreach ($editingLensModel['mediaIds'] as $mediaId) {
    			if (empty($mediaModels[$mediaId])) {
    				continue;
    			}
    			$mediaList[$mediaId] = $mediaModels[$mediaId];
    		}
    		$dubCaptionList = array();
    		foreach ($editingLensModel['dubCaptionIds'] as $dubCaptionId) {
    			if (empty($editingCaptionModels[$dubCaptionId])) {
    				continue;
    			}
    			$dubCaptionList[$dubCaptionId] = $editingCaptionModels[$dubCaptionId];
    		}
    		$dubMediaList = array();
    		foreach ($editingLensModel['dubMediaIds'] as $dubMediaId) {
    			if (empty($mediaModels[$dubMediaId])) {
    				continue;
    			}
    			$dubMediaList[$dubMediaId] = $mediaModels[$dubMediaId];
    		}
    		uasort($mediaList, array($commonSv, 'sortByCreateTime'));
    		uasort($dubCaptionList, array($commonSv, 'sortByCreateTime'));
    		uasort($dubMediaList, array($commonSv, 'sortByCreateTime'));
    		$mediaList = array_reverse($mediaList);
    		$editingLensModel['mediaList'] = array_values($mediaList);
    		$editingLensModel['dubCaptionList'] = array_values($dubCaptionList);
    		$editingLensModel['dubMediaList'] = array_values($dubMediaList);
    		$editingLensModels[$key] = $editingLensModel;
    	}
    	uasort($editingLensModels, array($commonSv, 'sortByIndex'));
    	return $editingLensModels;
    }
    
    /**
     * 字幕模型
     *
     * @return array
     */
    private function getCaptionModels($editingCaptionEttList)
    {
    	$editingCaptionModels = array();
    	if (!empty($editingCaptionEttList)) foreach ($editingCaptionEttList as $editingCaptionEtt) {
    		if ($editingCaptionEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$fontArr = empty($editingCaptionEtt->font) ? array(
    			'text-align' => 'left',
    			'position' => 0,
    			'font-size' => 12,
    			'font-family' => '',
    		) : json_decode($editingCaptionEtt->font, true);
    		$styleArr = empty($editingCaptionEtt->style) ? array(
    			'styleType' => 1,
    			'color' => '#ffff',
    			'fontType' => 1,
    			'background' => '#ffff',
    			'border-color' => '#ffff',
    			'border-size' => 1,
    			'effectColorStyle' => '',
    		) : json_decode($editingCaptionEtt->style, true);
    		$editingCaptionModels[$editingCaptionEtt->id] = array(
    			'id' 		=> intval($editingCaptionEtt->id),
    			'editingId' => intval($editingCaptionEtt->editingId),
    			'text'		=> $editingCaptionEtt->text,
    			'font'  	=> $fontArr,
    			'style' 	=> $styleArr,
    			'createTime' => intval($editingCaptionEtt->createTime),
 				'updateTime' => intval($editingCaptionEtt->updateTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($editingCaptionModels, array($commonSv, 'sortByCreateTime'));
    	return $editingCaptionModels;
    }
    
    /**
     * 创建字幕
     * 
     * @return array
     */
    public function createCaption($userId, $editingId, $captionId, $info)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingtt) || $editingtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	if ($editingtt->userId != $userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingCaptionDao = \dao\EditingCaption::singleton();
    	$now = $this->frame->now;
    	if (!empty($captionId)) {
    		$editingCaptionEtt = $editingCaptionDao->readByPrimary($captionId);
    		if (empty($editingCaptionEtt) || $editingCaptionEtt->editingId != $editingId
    			|| $editingCaptionEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('字幕已删除');
    		}
    	} else {
    		$editingCaptionEtt = $editingCaptionDao->getNewEntity();
    		$editingCaptionEtt->editingId = $editingId;
    		$editingCaptionEtt->createTime = $now;
    		$editingCaptionEtt->updateTime = $now;
    		$captionId = $editingCaptionDao->create($editingCaptionEtt);
    	}
    	$editingCaptionModels = $this->getCaptionModels(array($editingCaptionEtt));
    	$editingCaptionModel = $editingCaptionModels[$captionId];
    	$fontArr = $editingCaptionModel['font'];
    	$styleArr = $editingCaptionModel['style'];
    	if (isset($info['text'])) {
    		$editingCaptionEtt->set('text', $info['text']);
    	}
    	if (isset($info['text-align'])) {
    		$fontArr['text-align'] = $info['text-align'];
    	}
    	if (isset($info['position'])) {
    		$fontArr['position'] = intval($info['position']);
    	}
    	if (isset($info['font-size'])) {
    		$fontArr['font-size'] = intval($info['font-size']);
    	}
    	if (isset($info['font-family'])) {
    		$fontArr['font-family'] = $info['font-family'];
    	}
    	if (isset($info['styleType'])) {
    		$styleArr['styleType'] = intval($info['styleType']);
    	}
    	if (isset($info['color'])) {
    		$styleArr['color'] = $info['color'];
    	}
    	if (isset($info['fontType'])) {
    		$styleArr['fontType'] = intval($info['fontType']);
    	}
    	if (isset($info['background'])) {
    		$styleArr['background'] = $info['background'];
    	}
    	if (isset($info['border-color'])) {
    		$styleArr['border-color'] = $info['border-color'];
    	}
    	if (isset($info['border-size'])) {
    		$styleArr['border-size'] = intval($info['border-size']);
    	}
    	if (isset($info['effectColorStyle'])) {
    		$styleArr['effectColorStyle'] = $info['effectColorStyle'];
    	}
    	$editingCaptionEtt->set('font', json_encode($fontArr));
    	$editingCaptionEtt->set('style', json_encode($styleArr));
    	$editingCaptionEtt->set('updateTime', $now);
    	$editingCaptionDao->update($editingCaptionEtt);
    	$editingCaptionModels = $this->getCaptionModels(array($editingCaptionEtt));
    	$editingCaptionModel = empty($editingCaptionModels[$editingCaptionEtt->id]) ? array() : $editingCaptionModels[$editingCaptionEtt->id];
    	return $editingCaptionModel;
    }

    /**
     * 剪辑详情
     *
     * @return array
     */
    public function editingInfo($userEtt, $editingEtt)
    {
    	$userDao = \dao\User::singleton();
    	if (is_numeric($userEtt)) {
    		$userEtt = $userDao->readByPrimary($userEtt);
    	}
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$now = $this->frame->now;
    	$editingDao = \dao\Editing::singleton();
    	if (empty($editingEtt)) { // 获取用户最近一次的剪辑工程
    		$where = "`userId` = {$userEtt->userId} and `status` !=" . \constant\Common::DATA_DELETE;
    		$userEditingEttList = $editingDao->readListByWhere($where);
    		$lastEditingEtt = null; // 用户最近一次的编辑
    		if (!empty($userEditingEttList)) foreach ($userEditingEttList as $userEditingEtt) {
    			if (empty($lastEditingEtt) || $userEditingEtt->updateTime >= $lastEditingEtt->updateTime) {
    				$lastEditingEtt = $userEditingEtt;
    			}
    		}
    		if (empty($lastEditingEtt)) { // 第一次创建
    			$editingEtt = $editingDao->getNewEntity();
    			$editingEtt->name = date('Ymd') . '-剪辑';
    			$editingEtt->userId = $userEtt->userId;
    			$editingEtt->createTime = $now;
    			$editingEtt->updateTime = $now;
    			$editingId = $editingDao->create($editingEtt);
    			// 创建片头，片中，片尾
    			$this->createLens($userEtt->userId, $editingId, 1);
    			$this->createLens($userEtt->userId, $editingId, 2);
    			$this->createLens($userEtt->userId, $editingId, 3);
    		} else {
    			$editingEtt = $lastEditingEtt;
    		}
    	} else {
    		if (is_numeric($editingEtt)) {
    			$editingEtt = $editingDao->readByPrimary($editingEtt);
    		}
    	}
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE 
    		|| $editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
 		// 获取镜头
 		$editingLensDao = \dao\EditingLens::singleton();
 		$editingLensEttList = $editingLensDao->readListByIndex(array(
 			'editingId' => $editingEtt->id,
 		));
 		$editingLensModels = $this->getEditingLensModels($editingLensEttList);
 		// 获取标题
 		$editingTitleDao = \dao\EditingTitle::singleton();
 		$editingTitleEttList = $editingTitleDao->readListByIndex(array(
 			'editingId' => $editingEtt->id,
 		));
 		$editingTitleModels = $this->getTitleModels($editingTitleEttList);
 		// 获取音乐
 		$editingMusicDao = \dao\EditingMusic::singleton();
 		$editingMusicEttList = $editingMusicDao->readListByIndex(array(
 			'editingId' => $editingEtt->id,
 		));
 		$editingMusicModels = $this->getMusicModels($editingMusicEttList);
 		// 获取贴纸
 		$editingDecalDao = \dao\EditingDecal::singleton();
 		$editingDecalEttList = $editingDecalDao->readListByIndex(array(
 			'editingId' => $editingEtt->id,
 		));
 		$editingDecalModels = $this->getDecalModels($editingDecalEttList);
 		
 		// 音量调节
 		$volumeArr = empty($editingEtt->volume) ? array(
 			'dubVolume' => 1, // 配音音量  取值范围  0~3
			'backgroundVolume' => 0.2, // 背景音量  取值范围 0 ~ 1
			'dubSpeed' => 1, // 背景音量 取值范围 0.2 ~ 3
 		) : json_decode($editingEtt->volume, true);
 		// 颜色调整
 		$colorArr = empty($editingEtt->color) ? array(
 			'contrast' => 0, // 对比度 取值范围 -100 ~ 100
 			'saturability' => 0, // 饱和度  取值范围 -100 ~ 100ss
 			'luminance' => 0, // 亮度   取值范围 -100 ~ 100
 			'chroma' => 0, // 色度  取值范围 -100 ~ 100
 		) : array_map('intval', explode(',', json_decode($editingEtt->color, true)));
 		// 背景填充
 		$backgroundArr = empty($editingEtt->background) ? array(
 			'type' => 1, 
 			'color' => '',
 			'mediaList' => array(),
 		) : json_decode($editingEtt->background, true);
 		$mediaDao = \dao\Media::singleton();
		if (!empty($backgroundArr['mediaIds'])) { // 组织素材
			$mediaEttList = $mediaDao->readListByPrimary($backgroundArr['mediaIds']);
			$mediaModels = $this->getMediaModels($mediaEttList);
			$backgroundArr['mediaList'] = array_values($mediaModels);
		}

		// 演员列表
		$actorIds = empty($editingEtt->actorIds) ? array() : array_map('trim', explode(',', $editingEtt->actorIds));
		$appSv = \service\App::singleton();
		$actorClassifys = $appSv->getActorClassifys();
		$actorMap = array();
		foreach ($actorClassifys as $row) {
			if (empty($row['list'])) {
				continue;
			}
			$actorMap = array_merge($actorMap, $row['list']);
		}
		$actorMap = array_column($actorMap, null, 'id');
		$actorList = array();
		foreach ($actorIds as $actorId) {
			if (empty($actorMap[$actorId])) {
				continue;
			}
			$actorList[$actorId] = $actorMap[$actorId];
		}
    	
		$dubCaptionIds = empty($editingEtt->dubCaptionIds) ? array() : array_map('intval', explode(',', $editingEtt->dubCaptionIds));
		$dubMediaIds = empty($editingEtt->dubMediaIds) ? array() : array_map('intval', explode(',', $editingEtt->dubMediaIds));
		
		$mediaEttList = $mediaDao->readListByPrimary($dubMediaIds);
		$mediaModels = $this->getMediaModels($mediaEttList);
		$dubMediaList = array_values($mediaModels);

		$editingCaptionDao = \dao\EditingCaption::singleton();
		$editingCaptionEttList = empty($dubCaptionIds) ? array() : $editingCaptionDao->readListByPrimary($dubCaptionIds);
		$editingCaptionModels = $this->getCaptionModels($editingCaptionEttList);
		$dubCaptionList = array_values($editingCaptionModels);
    	$model = array(
    		'id' 			=> intval($editingEtt->id),
    		'name'			=> $editingEtt->name, // 剪辑名称
    		'topic'			=> $editingEtt->topic, // 话题
    		'title'			=> $editingEtt->title, // 标题
    		'ratio'			=> $editingEtt->ratio, // 视频比例 可选 9:16/16:9/1:1 
    		'durationType' 	=> intval($editingEtt->durationType), // 视频时长类型 1  按视频时长  2  按配音时长
    		'fps' 			=> intval($editingEtt->fps), //  视频帧率  取值：25/30/60
    		'volume' 		=> $volumeArr,
    		'transitionIds' => empty($editingEtt->transitionIds) ? array() : array_map('string', explode(',', $editingEtt->transitionIds)),
    		'filterIds' 	=> empty($editingEtt->filterIds) ? array() : array_map('string', explode(',', $editingEtt->filterIds)),
    		'color' 		=> $colorArr,
    		'background' 	=> $backgroundArr,
    		'showCaption' 	=> intval($editingEtt->showCaption),
    		'actorList' 	=> array_values($actorList),
    		'dubType' 		=> intval($editingEtt->dubType),
    		'dubCaptionList' => $dubCaptionList,
    		'dubMediaList' 	=> $dubMediaList,
    		'updateTime' 	=> intval($editingEtt->updateTime),
    		'createTime' 	=> intval($editingEtt->createTime),
    		'lensList' 		=> array_values($editingLensModels), // 镜头列表
    		'titleList' 	=> array_values($editingTitleModels), // 标题列表
    		'musicList'		=> array_values($editingMusicModels),
    		'decalList'		=> array_values($editingDecalModels),
    	);
    	return $model;
    }

    
    /**
     * 设置镜头
     *
     * @return array
     */
    public function reviseLens($userId, $lensId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$editingLensDao = \dao\EditingLens::singleton();
    	$editingLensEtt = $editingLensDao->readByPrimary($lensId);
    	if (empty($editingLensEtt) || $editingLensEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('镜头已删除');
    	}
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingLensEtt->editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if ($editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$now = $this->frame->now;
    	$mediaDao = \dao\Media::singleton();
    	$editingCaptionDao = \dao\EditingCaption::singleton();
    	$haveMediaIds = empty($editingLensEtt->mediaIds) ? array() : explode(',', $editingLensEtt->mediaIds);
    	if (!empty($info['addMediaIds'])) { // 添加素材
    		// 素材（视频或者图片）
    		$mediaEttList = $mediaDao->readListByPrimary($info['addMediaIds']);
    		$mediaEttList = $mediaDao->refactorListByKey($mediaEttList);
    		if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaId => $mediaEtt) {
    			if (!in_array($mediaEtt->type, array(
    				\constant\Folder::FOLDER_TYPE_VIDEO,
    				\constant\Folder::FOLDER_TYPE_IMAGE,
    			))) {
    				throw new $this->exception('请选择视频或图片');
    			}
    			if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    				unset($mediaEttList[$mediaId]);
    				continue;
    			}
    		}
    		if (empty($mediaEttList)) {
    			throw new $this->exception('请选择视频或图片');
    		}
    		$haveMediaIds = array_merge($haveMediaIds, array_keys($mediaEttList));
    		$haveMediaIds = array_unique($haveMediaIds);
    		$editingLensEtt->set('mediaIds', implode(',', $haveMediaIds));
    	}
    	if (!empty($info['deleteMediaIds'])) { // 删除素材
    		$haveMediaIds = array_diff($haveMediaIds, $info['deleteMediaIds']);
    		$editingLensEtt->set('mediaIds', implode(',', $haveMediaIds));
    	}
    	// 修改镜头名称
    	if (!empty($info['name']) && $info['name'] != $editingLensEtt->name) {
    		$editingLensEtt->set('name', $info['name']);
    	}
    	// 选择时长
    	if (isset($info['duration']) && $info['duration'] != $editingLensEtt->duration) {
    		$editingLensEtt->set('duration', $info['duration']);
    	}
    	// 原声
    	if (isset($info['originalSound']) && $info['originalSound'] != $editingLensEtt->originalSound) {
    		$editingLensEtt->set('originalSound', $info['originalSound']);
    	}
    	// 设置转场
    	if (isset($info['transitionType']) && $info['transitionType'] != $editingLensEtt->transitionType) {
    		$editingLensEtt->set('transitionType', $info['transitionType']);
    	}
    	if (isset($info['transitionIds'])) { // 自选ID
    		$editingLensEtt->set('transitionIds', implode(',', $info['transitionIds']));
    	}
    	// 配音-类型设置
    	if (isset($info['dubType']) && $info['dubType'] != $editingLensEtt->dubType) {
    		$editingLensEtt->set('dubType', $info['dubType']);
    	}
    	// 配音-手动设置
    	$haveDubCaptionIds = empty($editingLensEtt->dubCaptionIds) ? array() : explode(',', $editingLensEtt->dubCaptionIds);
    	if (!empty($info['addDubCaptionIds'])) { // 配音-手动设置-添加配音
    		$editingCaptionEttList = $editingCaptionDao->readListByPrimary($info['addDubCaptionIds']);
    		$editingCaptionEttList = $editingCaptionDao->refactorListByKey($editingCaptionEttList);
    		if (!empty($editingCaptionEttList)) foreach ($editingCaptionEttList as $captionId => $editingCaptionEtt) {
    			if ($editingCaptionEtt->status == \constant\Common::DATA_DELETE) {
    				unset($editingCaptionEttList[$captionId]);
    				continue;
    			}
    		}
    		if (empty($editingCaptionEttList)) {
    			throw new $this->exception('请选择配音');
    		}
    		$haveDubCaptionIds = array_merge($haveDubCaptionIds, array_keys($editingCaptionEttList));
    		$haveDubCaptionIds = array_unique($haveDubCaptionIds);
    		$editingLensEtt->set('dubCaptionIds', implode(',', $haveDubCaptionIds));
    	}
    	if (!empty($info['deleteDubCaptionIds'])) { // 配音-手动设置-删除配音
    		$haveDubCaptionIds = array_diff($haveDubCaptionIds, $info['deleteDubCaptionIds']);
    		$editingLensEtt->set('dubCaptionIds', implode(',', $haveDubCaptionIds));
    	}
    	// 配音-配音文件
    	$haveDubMediaIds = empty($editingLensEtt->dubMediaIds) ? array() : explode(',', $editingLensEtt->dubMediaIds);
    	if (!empty($info['addDubMediaIds'])) { // 添加素材
    		$mediaEttList = $mediaDao->readListByPrimary($info['addDubMediaIds']);
    		$mediaEttList = $mediaDao->refactorListByKey($mediaEttList);
    		if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaId => $mediaEtt) {
    			if (!in_array($mediaEtt->type, array(
    				\constant\Folder::FOLDER_TYPE_TEXT,
    			))) {
    				throw new $this->exception('选择旁白配音');
    			}
    			if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    				unset($mediaEttList[$mediaId]);
    				continue;
    			}
    		}
    		if (empty($mediaEttList)) {
    			throw new $this->exception('请选择旁白配音');
    		}
    		$haveDubMediaIds = array_merge($haveDubMediaIds, array_keys($mediaEttList));
    		$haveDubMediaIds = array_unique($haveDubMediaIds);
    		$editingLensEtt->set('dubMediaIds', implode(',', $haveDubMediaIds));
    	}
    	if (!empty($info['deleteDubMediaIds'])) { // 删除素材
    		$haveDubMediaIds = array_diff($haveDubMediaIds, $info['deleteDubMediaIds']);
    		$editingLensEtt->set('dubMediaIds', implode(',', $haveDubMediaIds));
    	}
    	$editingLensEtt->set('updateTime', $now);
    	$editingLensDao->update($editingLensEtt);
    	return $this->lensInfo($editingLensEtt->id, $userId);
    }
    
    /**
     * 修改剪辑
     *
     * @return array
     */
    public function reviseEditing($userId, $editingId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if ($editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	if (!empty($info['name'])) { // 名称
    		$editingEtt->set('name', $info['name']);
    	}
    	if (isset($info['title'])) { // 标题
    		$editingEtt->set('desc', $info['title']);
    	}
    	if (isset($info['topic'])) { // 话题
    		$editingEtt->set('topic', $info['topic']);
    	}
    	$now = $this->frame->now;
    	// 视频配置-背景填充
    	$backgroundArr = empty($editingEtt->background) ? array(
 			'type' => 1, 
 			'color' => '',
 			'mediaList' => array(),
 		) : json_decode($editingEtt->background, true);
    	if (isset($info['backgroundType'])) {
    		$backgroundArr['type'] = intval($info['backgroundType']);
    	}
    	if (isset($info['backgroundColor'])) {
    		$backgroundArr['color'] = $info['backgroundColor'];
    	}
    	if (isset($info['backgroundMediaIds'])) {
    		$backgroundArr['mediaIds'] = $info['backgroundMediaIds'];
    	}
    	$editingEtt->set('background', json_encode($backgroundArr));
    	// 视频配置-字幕/配音-字幕显示
    	if (isset($info['showCaption'])) {
    		$editingEtt->set('showCaption', $info['showCaption']);
    	}
    	$actorIds = empty($editingEtt->actorIds) ? array() : array_map('trim', explode(',', $editingEtt->actorIds));
    	if (isset($info['addActorIds'])) {
    		$actorIds = array_merge($info['addActorIds'], $actorIds);
    		$actorIds = array_unique($actorIds);
    		$editingEtt->set('actorIds', implode(',', $actorIds));
    	}
    	if (isset($info['deleteActorIds'])) {
    		$actorIds = array_diff($actorIds, $info['deleteActorIds']);
    		$editingEtt->set('actorIds', implode(',', $actorIds));
    	}
    	if (isset($info['dubType'])) {
    		$editingEtt->set('dubType', $info['dubType']);
    	}
    	$dubCaptionIds = empty($editingEtt->dubCaptionIds) ? array() : array_map('intval', explode(',', $editingEtt->dubCaptionIds));

    	if (isset($info['addDubCaptionIds'])) { // 添加字幕
    		$dubCaptionIds = array_merge($dubCaptionIds, $info['addDubCaptionIds']);
    		$dubCaptionIds = array_unique($dubCaptionIds);
    		$editingEtt->set('dubCaptionIds', implode(',', $dubCaptionIds));
    	}
    	if (isset($info['deleteDubCaptionIds'])) {
    		$dubCaptionIds = array_diff($dubCaptionIds, $info['deleteDubCaptionIds']);
    		$editingEtt->set('dubCaptionIds', implode(',', $dubCaptionIds));
    	}
    	$dubMediaIds = empty($editingEtt->dubMediaIds) ? array() : array_map('intval', explode(',', $editingEtt->dubMediaIds));
    	if (isset($info['addDubMediaIds'])) {
    		$dubMediaIds = array_merge($info['addDubMediaIds'], $dubCaptionIds);
    		$dubMediaIds = array_unique($dubMediaIds);
    		$editingEtt->set('dubMediaIds', implode(',', $dubMediaIds));
    	}
    	if (isset($info['deleteDubMediaIds'])) {
    		$dubMediaIds = array_diff($dubMediaIds, $info['deleteDubMediaIds']);
    		$editingEtt->set('dubMediaIds', implode(',', $dubMediaIds));
    	}
    	// 剪辑设置
    	if (!empty($info['ratio'])) { // 视频比例
    		$editingEtt->set('ratio', $info['ratio']);
    	}
    	if (!empty($info['durationType'])) { // 视频时长 1 按视频时长 2  按配音时长
    		$editingEtt->set('durationType', intval($info['durationType']));
    	}
    	if (!empty($info['fps'])) { // 视频帧率
    		$editingEtt->set('fps', intval($info['fps']));
    	}
    	// 音量调节
    	$volumeArr = empty($editingEtt->volume) ? array() : json_decode($editingEtt->volume, true);
    	if (!empty($info['voiceover'])) {
    		$volumeArr['voiceover'] = $info['voiceoverVolume'];
    	}
    	if (!empty($info['backgroundVolume'])) {
    		$volumeArr['background'] = $info['backgroundVolume'];
    	}
    	if (!empty($info['voiceoverSpeed'])) {
    		$volumeArr['speed'] = $info['voiceoverSpeed'];
    	}
    	$editingEtt->set('volume', json_encode($volumeArr));
    	
    	if (isset($info['filterIds'])) { // 滤镜
    		$editingEtt->set('filterIds', implode(',', $info['filterIds']));
    	}
    	if (isset($info['transitionIds'])) { // 转场
    		$editingEtt->set('transitionIds', implode(',', $info['transitionIds']));
    	}
    	// 颜色调整
    	$colorArr = empty($editingEtt->color) ? array() : json_decode($editingEtt->color, true);
    	if (!empty($info['contrast'])) {
    		$colorArr['contrast'] = intval($info['contrast']);
    	}
    	if (!empty($info['saturability'])) {
    		$colorArr['saturability'] = intval($info['saturability']);
    	}
    	if (!empty($info['luminance'])) {
    		$colorArr['luminance'] = intval($info['luminance']);
    	}
    	if (!empty($info['chroma'])) {
    		$colorArr['chroma'] = intval($info['chroma']);
    	}
    	$editingEtt->set('color', json_encode($colorArr));
    	if (!empty($info['deleteTitleIds'])) { // 移除标题组
    		$editingTitleDao = \dao\EditingTitle::singleton();
    		$editingTitleEttList = $editingTitleDao->readListByPrimary($info['deleteTitleIds']);
    		foreach ($editingTitleEttList as $editingTitleEtt) {
    			$editingTitleEtt->set('status', \constant\Common::DATA_DELETE);
    			$editingTitleEtt->set('updateTime', $now);
    			$editingTitleDao->update($editingTitleEtt);
    		}
    	}
    	if (!empty($info['deleteMusicIds'])) { // 移除音乐
    		$editingMusicDao = \dao\EditingMusic::singleton();
    		$editingMusicEttList = $editingMusicDao->readListByPrimary($info['deleteMusicIds']);
    		foreach ($editingMusicEttList as $editingMusicEtt) {
    			$editingMusicEtt->set('status', \constant\Common::DATA_DELETE);
    			$editingMusicEtt->set('updateTime', $now);
    			$editingMusicDao->update($editingMusicEtt);
    		}
    	}
    	$editingEtt->set('updateTime', $now);
    	$editingDao->update($editingEtt);
    	return $this->editingInfo($userEtt, $editingEtt);
    }
    
    /**
     * 创建标题组
     *
     * @return array
     */
    public function createTitle($userId, $editingId, $titleId, $info)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	if ($editingEtt->userId != $userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingTitleDao = \dao\EditingTitle::singleton();
    	$now = $this->frame->now;
    	if (!empty($titleId)) {
    		$editingTitleEtt = $editingTitleDao->readByPrimary($titleId);
    		if (empty($editingTitleEtt) || $editingTitleEtt->editingId != $editingId 
    			|| $editingTitleEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('标题组已删除');
    		}
    	} else {
    		$editingTitleEtt = $editingTitleDao->getNewEntity();
    		$editingTitleEtt->editingId = $editingId;
    		$editingTitleEtt->createTime = $now;
    		$editingTitleEtt->updateTime = $now;
    		$titleId = $editingTitleDao->create($editingTitleEtt);
    	}
    	if (isset($info['start'])) {
    		$editingTitleEtt->set('start', intval($info['start']));
    	}
    	if (isset($info['end'])) {
    		$editingTitleEtt->set('end', intval($info['end']));
    	}
    	$editingCaptionDao = \dao\EditingCaption::singleton();
    	if (!empty($info['captionIds'])) { // 添加文案
    		$editingCaptionEttList = $editingCaptionDao->readListByPrimary($info['captionIds']);
    		$editingCaptionEttList = $editingCaptionDao->refactorListByKey($editingCaptionEttList);
    		if (!empty($editingCaptionEttList)) foreach ($editingCaptionEttList as $captionId => $editingCaptionEtt) {
    			if ($editingCaptionEtt->status == \constant\Common::DATA_DELETE) {
    				unset($editingCaptionEttList[$captionId]);
    				continue;
    			}
    		}
    		$editingTitleEtt->set('captionIds', empty($editingCaptionEttList) ? '' : implode(',', array_keys($editingCaptionEttList)));
    	}
    	$editingTitleEtt->set('updateTime', $now);
    	$editingTitleDao->update($editingTitleEtt);
    	$titleModels = $this->getTitleModels(array($editingTitleEtt));
    	return empty($titleModels[$titleId]) ? array() : $titleModels[$titleId];
    }
    
    /**
     * 获取标题组模型
     *
     * @return array
     */
    private function getTitleModels($editingTitleEttList)
    {
    	$editingTitleModels = array();
    	$allCaptionIds = array();
    	if (!empty($editingTitleEttList)) foreach ($editingTitleEttList as $key => $editingTitleEtt) {
    		if ($editingTitleEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$captionIds = empty($editingTitleEtt->captionIds) ? array() : array_map('intval', explode(',', $editingTitleEtt->captionIds));
    		$editingTitleModels[$editingTitleEtt->id] = array(
    			'id' 			=> intval($editingTitleEtt->id),
    			'updateTime' 	=> intval($editingTitleEtt->updateTime),
    			'captionIds'	=> $captionIds,
    		);
    		$allCaptionIds = array_merge($allCaptionIds, $captionIds);
    	}
    	$editingCaptionDao = \dao\EditingCaption::singleton();
    	$editingCaptionEttList = empty($allCaptionIds) ? array() : $editingCaptionDao->readListByPrimary($allCaptionIds);
    	$editingCaptionModels = $this->getCaptionModels($editingCaptionEttList);
    	$commonSv = \service\Common::singleton();
    	foreach ($editingTitleModels as $key => $editingTitleModel) {
    		$captionList = array();
    		foreach ($editingTitleModel['captionIds'] as $captionId) {
    			if (empty($editingCaptionModels[$captionId])) {
    				continue;
    			}
    			$captionList[] = $editingCaptionModels[$captionId];
    		}
    		uasort($captionList, array($commonSv, 'sortByCreateTime'));
    		$editingTitleModel['title'] = empty($captionList) ? 0 : reset($captionList)['text'];
    		$editingTitleModel['captionList'] = $captionList;
    		$editingTitleModels[$key] = $editingTitleModel;
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($editingTitleModels, array($commonSv, 'sortByCreateTime'));
    	return $editingTitleModels;
    }
    
    /**
     * 获取素材模型
     *
     * @return array
     */
    private function getMediaModels($mediaEttList)
    {
    	$mediaModels = array();
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaEtt) {
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$mediaModels[$mediaEtt->id] = array(
    			'id' 			=> intval($mediaEtt->id),
    			'name'			=> $mediaEtt->name,
    			'type'			=> $mediaEtt->type,
    			'url'			=> $mediaEtt->url,
    			'size'			=> intval($mediaEtt->size), // 大小
    			'duration'		=> 100, // 播放时长
    			'updateTime'	=> intval($mediaEtt->updateTime),
    			'createTime'	=> intval($mediaEtt->createTime),
    		);
    	}
    	return $mediaModels;
    }
    
    /**
     * 获取音乐模型
     *
     * @return array
     */
    private function getMusicModels($editingMusicEttList)
    {
    	$editingMusicModels = array();
    	if (!empty($editingMusicEttList)) foreach ($editingMusicEttList as $editingMusicEtt) {
    		if ($editingMusicEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$editingMusicModels[$editingMusicEtt->id] = array(
    			'id' 			=> intval($editingMusicEtt->id),
    			'conId'			=> intval($editingMusicEtt->conId),
    			'type'			=> intval($editingMusicEtt->type),
    			'url'			=> 'xxxxx',
    			'name'			=> 'xxxx',
    			'duration'		=> 100, // 播放时长
    			'updateTime'	=> intval($editingMusicEtt->updateTime),
    			'createTime'	=> intval($editingMusicEtt->createTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($editingMusicModels, array($commonSv, 'sortByCreateTime'));
    	return $editingMusicModels;
    }
    
    /**
     * 获取贴纸模型
     *
     * @return array
     */
    private function getDecalModels($editingDecalEttList)
    {
    	$editingDecalModels = array();
    	$allLensIds = array();
    	$allMediaIdIds = array();
    	if (!empty($editingDecalEttList)) foreach ($editingDecalEttList as $key => $editingDecalEtt) {
    		if ($editingDecalEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$useLensIds = empty($editingDecalEtt->useLensIds) ? array() : array_map('intval', explode(',', $editingDecalEtt->useLensIds));
    		$editingDecalModels[$editingDecalEtt->id] = array(
    			'id' 			=> intval($editingDecalEtt->id),
    			'updateTime' 	=> intval($editingDecalEtt->updateTime),
    			'useLensIds'	=> $useLensIds,
    			'mediaId1' 		=> intval($editingDecalEtt->mediaId1),
    			'mediaSize1' 	=> intval($editingDecalEtt->mediaSize1),
    			'mediaId2' 		=> intval($editingDecalEtt->mediaId2),
    			'mediaSize2' 	=> intval($editingDecalEtt->mediaSize2),
    			'mediaPostion1' => empty($editingDecalEtt->mediaPostion1) ? '0_0' : $editingDecalEtt->mediaPostion1,
    			'mediaPostion2' => empty($editingDecalEtt->mediaPostion2) ? '0_0' : $editingDecalEtt->mediaPostion2,
    			'media1'		=> array(),
    			'media2'		=> array(),
    			'useLensList'	=> array(),
    		);
    		if (!empty($editingDecalEtt->mediaId1)) {
    			$allMediaIdIds[] = $editingDecalEtt->mediaId1;
    		}
    		if (!empty($editingDecalEtt->mediaId2)) {
    			$allMediaIdIds[] = $editingDecalEtt->mediaId2;
    		}
    		$allLensIds = array_merge($allLensIds, $useLensIds);
    	}
    	// 镜头
    	$editingLensDao = \dao\EditingLens::singleton();
    	$editingLensEttList = $editingLensDao->readListByPrimary($allLensIds);
    	$editingLensEttList = $editingLensDao->refactorListByKey($editingLensEttList);
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = empty($allMediaIdIds) ? array() : $mediaDao->readListByPrimary($allMediaIdIds);
    	$mediaModels = $this->getMediaModels($mediaEttList);
    	foreach ($editingDecalModels as $key => $editingDecalModel) {
    		if (!empty($mediaModels[$editingDecalModel['mediaId1']])) {
    			$editingDecalModel['media1'] = $mediaModels[$editingDecalModel['mediaId1']];
    		}
    		if (!empty($mediaModels[$editingDecalModel['mediaId2']])) {
    			$editingDecalModel['media2'] = $mediaModels[$editingDecalModel['mediaId2']];
    		}
    		$useLensList = array();
    		foreach ($editingDecalModel['useLensIds'] as $useLensId) {
    			if ($useLensId == -1) {
    				$useLensList[] = array(
    					'id' => intval($useLensId),
    					'name' => '全部场景',
    				);
    			} elseif (empty($editingLensEttList[$useLensId])) {
    				continue;
    			}
    			$editingLensEtt = $editingLensEttList[$useLensId];
    			$useLensList[] = array(
    				'id' => intval($editingLensEtt->id),
    				'name' => $editingLensEtt->name,
    			);
    			$editingDecalModel['useLensList'] = $useLensList;
    		}
    		$editingDecalModels[$key] = $editingDecalModel;
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($editingDecalModels, array($commonSv, 'sortByCreateTime'));
    	return $editingDecalModels;
    }

    /**
     * 创建贴纸
     *
     * @return array
     */
    public function createDecal($userId, $editingId, $decalId, $info)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingtt) || $editingtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	if ($editingtt->userId != $userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingDecalDao = \dao\EditingDecal::singleton();
    	$now = $this->frame->now;
    	if (!empty($decalId)) {
    		$editingDecalEtt = $editingDecalDao->readByPrimary($decalId);
    		if (empty($editingDecalEtt) || $editingDecalEtt->editingId != $editingId
    			|| $editingDecalEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('贴纸已删除');
    		}
    	} else {
    		$editingDecalEtt = $editingDecalDao->getNewEntity();
    		$editingDecalEtt->editingId = $editingId;
    		$editingDecalEtt->createTime = $now;
    		$editingDecalEtt->updateTime = $now;
    		$decalId = $editingDecalDao->create($editingDecalEtt);
    	}
    	if (isset($info['useLensIds'])) {
    		$editingDecalEtt->set('useLensIds', $info['useLensIds']);
    	}
    	if (isset($info['mediaId1'])) {
    		$editingDecalEtt->set('mediaId1', $info['mediaId1']);
    	}
    	if (isset($info['mediaSize1'])) {
    		$editingDecalEtt->set('mediaSize1', $info['mediaSize1']);
    	}
    	if (isset($info['mediaId2'])) {
    		$editingDecalEtt->set('mediaId2', $info['mediaId2']);
    	}
    	if (isset($info['mediaSize2'])) {
    		$editingDecalEtt->set('mediaSize2', $info['mediaSize2']);
    	}
    	if (isset($info['mediaPostion1'])) {
    		$editingDecalEtt->set('mediaPostion1', $info['mediaPostion1']);
    	}
    	if (isset($info['mediaPostion2'])) {
    		$editingDecalEtt->set('mediaPostion2', $info['mediaPostion2']);
    	}
    	$editingDecalEtt->set('updateTime', $now);
    	$editingDecalDao->update($editingDecalEtt);
    	$decalModels = $this->getDecalModels(array($editingDecalEtt));
    	return empty($decalModels[$editingDecalEtt->id]) ? array() : $decalModels[$editingDecalEtt->id];
    }
    
    /**
     * 创建音乐
     *
     * @return array
     */
    public function createMusic($userId, $editingId, $musicId, $info)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingtt) || $editingtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	if ($editingtt->userId != $userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingMusicDao = \dao\EditingMusic::singleton();
    	$now = $this->frame->now;
    	if (!empty($musicId)) {
    		$editingMusicEtt = $editingMusicDao->readByPrimary($musicId);
    		if (empty($editingMusicEtt) || $editingMusicEtt->editingId != $editingId
    			|| $editingMusicEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('音乐已删除');
    		}
    	} else {
    		$editingMusicEtt = $editingMusicDao->getNewEntity();
    		$editingMusicEtt->editingId = $editingId;
    		$editingMusicEtt->createTime = $now;
    		$editingMusicEtt->updateTime = $now;
    		$musicId = $editingMusicDao->create($editingMusicEtt);
    	}
    	if (isset($info['conId'])) {
    		$editingMusicEtt->set('conId', $info['conId']);
    	}
    	if (isset($info['type'])) {
    		$editingMusicEtt->set('type', $info['type']);
    	}
    	$editingMusicEtt->set('updateTime', $now);
    	$editingMusicDao->update($editingMusicEtt);
    	$musicModels = $this->getMusicModels(array($editingMusicEtt));
    	return empty($musicModels[$editingMusicEtt->id]) ? array() : $musicModels[$editingMusicEtt->id];
    }
    
}