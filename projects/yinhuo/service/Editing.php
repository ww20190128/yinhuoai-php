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
     * 创建剪辑或模板
     *[id] => 
    [name] => 
    [userId] => 0
    [type] => 
    [status] => 0
    [voiceIds] => 
    [showCaption] => 0
    [createTime] => 0
    [updateTime] => 0
     * @return array
     */
    public function createEditing($editingId)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->getNewEntity();
    	
    	print_r($editingEtt);exit;
    	
        return ;
    }

    /**
     * 创建剪辑或模板
     * 
     * @return array
     */
    public function addLensMedias($userId, $editingId, $lensId, $mediaIds)
    {
    	// 素材（视频或者图片）
    	$mediaDao = \dao\Media::singleton();
    	$mediaEttList = $mediaDao->readListByPrimary($mediaIds);
    	$mediaEttList = $mediaDao->refactorListByKey($mediaEttList);
    	if (!empty($mediaEttList)) foreach ($mediaEttList as $mediaId => $mediaEtt) {
    		if (in_array($mediaEtt->type, array(
    			\constant\Folder::FOLDER_TYPE_VIDEO,
    			\constant\Folder::FOLDER_TYPE_IMAGE,
    		))) {
    			throw new $this->exception('仅支持上传视频或者图片');
    		}
    		if ($mediaEtt->status == \constant\Common::DATA_DELETE) {
				unset($mediaEttList[$mediaId]);
				continue;
			}
    	}
    	if (empty($mediaEttList)) {
    		throw new $this->exception('请选择上传的素材');
    	}
    	$now = $this->frame->now;
    	// 剪辑任务
    	$editingDao = \dao\Editing::singleton();
    	if (empty($editingId)) { // 创建剪辑工程
    		$editingEtt = $editingDao->getNewEntity();
    		$editingEtt->name = '';
    		$editingEtt->userId = $userId;
    		$editingEtt->createTime = $now;
    		$editingEtt->updateTime = $now;
    		$editingId = $editingDao->create($editingEtt);
    	} else {
    		$editingEtt = $editingDao->readByPrimary($editingId);
    		if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('剪辑工程已删除');
    		}
    		if ($editingEtt->userId == $userId) {
    			throw new $this->exception('剪辑工程已删除');
    		}
    	}
    	// 镜头
    	$lensDao = \dao\Lens::singleton();
    	if (empty($lensId)) {
    		$lensEtt = $lensDao->getNewEntity();
    		$lensEtt->name = '镜头6';
    		$lensEtt->editingId = $editingId;
    		$lensEtt->mediaIds = implode(',', array_keys($mediaEttList));
    		$lensEtt->createTime = $now;
    		$lensEtt->updateTime = $now;
    		$lensDao->create($lensEtt);
    	} else {
    		$lensEtt = $lensDao->readByPrimary($lensId);
    		if (empty($lensEtt) || $lensEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('镜头已删除');
    		}
    		if ($lensEtt->editingId == $editingId) {
    			throw new $this->exception('镜头已删除');
    		}
    		$haveMediaIds = empty($lensEtt->mediaIds) ? array() : explode(',', $lensEtt->mediaIds);
    		$haveMediaIds = array_merge(array_keys($mediaEttList), $haveMediaIds);
    		$lensEtt->set('mediaIds', implode(',', array_keys($mediaEttList)));
    		$lensEtt->set('updateTime', $now);
    		$lensDao->update($lensEtt);
    	} 
    	return ;
    }
    
    /**
     * 设置镜头
     *
     * @return array
     */
    public function reviseLens($userId, $lensId, $info)
    {
    	$lensDao = \dao\Lens::singleton();
    	$lensEtt = $lensDao->readByPrimary($editingId);
    	if (empty($lensEtt) || $lensEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('镜头已删除');
    	}
    	if ($lensEtt->userId == $userId) {
    		throw new $this->exception('镜头已删除');
    	}
    	if (!empty($info['name']) && $info['name'] != $lensEtt->name) { // 镜头名称
    		$lensEtt->set('name', $info['name']);
    	}
    	if (!empty($info['transitionIds'])) { // 转场Id列表
    		$lensEtt->set('transitionIds', implode(',', $info['transitionIds']));
    	}
    	if (!empty($info['duration']) && $info['duration'] != $lensEtt->duration) { // 选择时长
    		$lensEtt->set('duration', $info['duration']);
    	}
    	if (!empty($info['originalSound']) && $info['originalSound'] != $lensEtt->originalSound) { // 原声
    		$lensEtt->set('originalSound', $info['originalSound']);
    	}
    	if (!empty($info['removeMediaIds'])) { // 删除素材
    		$haveMediaIds = empty($lensEtt->mediaIds) ? array() : explode(',', $lensEtt->mediaIds);
    		foreach ($haveMediaIds as $key => $haveMediaId) {
    			if (in_array($haveMediaId, $info['removeMediaIds'])) {
    				unset($haveMediaIds[$key]);
    			}
    		}
    		$lensEtt->set('mediaIds', implode(',', $haveMediaIds));
    	}
    	$lensEtt->set('updateTime', $now);
    	$lensDao->update($lensEtt);
    	
    	return ;
    }
}