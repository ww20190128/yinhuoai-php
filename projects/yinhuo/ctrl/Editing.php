<?php
namespace ctrl;

/**
 * 视频剪辑
 * 
 * @package ctrl
 */
class Editing extends CtrlBase
{
	/**
	 * 创建剪辑或模板
	 *
	 * @return array
	 */
	public function createEditing()
	{
		$params = $this->params;
		$editingId = $this->paramFilter('editingId', 'intval'); // 剪辑Id
		$editingSv = \service\Editing::singleton();
		return $editingSv->createEditing($editingId);
	}
	
	/**
	 * 添加镜头素材
	 *
	 * @return array
	 */
	public function addLensMedias()
	{
		$params = $this->params;
		$editingId = $this->paramFilter('editingId', 'intval'); // 剪辑Id
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		$mediaIds = $this->paramFilter('mediaIds', 'array'); // 素材Id
		$editingSv = \service\Editing::singleton();
		return $editingSv->addLensMedias($this->userId, $editingId, $lensId, $mediaIds);
	}
	
	/**
	 * 修改镜头信息
	 *
	 * @return array
	 */
	public function reviseLens()
	{
		$params = $this->params;
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		$lensName = $this->paramFilter('lensName', 'string', 0); // 镜头名称
		$duration = $this->paramFilter('duration', 'intval', 0); // 选择时长
		$transitionIds = $this->paramFilter('transitionIds', 'intval', 0); // 选择时长
		$deleteMediaIds = $this->paramFilter('deleteMediaIds', 'array'); // 删除的素材Id列表
		
		// 配音相关
		$captionType = $this->paramFilter('captionType', 'intval'); // 配音类型 1   手动设置    2 配音文件
		
		$addCaptionIds = $this->paramFilter('addCaptionId', 'array'); // 添加字幕Id
		$deleteCaptionIds = $this->paramFilter('deleteCaptionId', 'array'); // 删除字幕Id
		
		$addCaptionMediaIds = $this->paramFilter('addCaptionMediaIds', 'array'); // 添加配音文件Id（旁白配音）
		$deleteCaptionMediaIds = $this->paramFilter('deleteCaptionMediaIds', 'array'); // 删除配音文件Id（旁白配音）
		
		$info = array(
			'name' => $lensName,
			'duration' => $duration,
			'transitionIds' => $transitionIds,
			'deleteMediaIds' => $deleteMediaIds,
		);
		$editingSv = \service\Editing::singleton();
		return $editingSv->reviseLens($userId, $lensId, $info);
	}
	
	/**
	 * 剪辑工程详情
	 *
	 * @return array
	 */
	public function editingInfo()
	{
		$params = $this->params;
		$editingId = $this->paramFilter('editingId', 'intval'); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->editingInfo($this->userId, $editingId);
	}
	
	/**
	 * 修改字幕
	 *
	 * @return array
	 */
	public function reviseCaption()
	{
		$captionId = $this->paramFilter('captionId', 'intval'); // 字幕Id
	}
	

	/**
	 * 修改剪辑
	 *
	 * @return array
	 */
	public function reviseEditing()
	{
		$params = $this->params;
		$editingId = $this->paramFilter('editingId', 'intval'); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		
		$deleteVoiceActorIds = $this->paramFilter('deleteVoiceActorIds', 'array'); // 删除的配音演员Id列表
		$addVoiceActorIds = $this->paramFilter('addVoiceIds', 'array'); // 添加的配音演员Id列表
		$showCaption = $this->paramFilter('showCaption', 'intval'); // 字幕显示
		
		
		// 配音设置
		$captionType = $this->paramFilter('captionType', 'intval'); // 配音类型 1   手动设置    2 配音文件
		
		$addCaptionIds = $this->paramFilter('addCaptionId', 'array'); // 添加字幕Id
		$deleteCaptionIds = $this->paramFilter('deleteCaptionId', 'array'); // 删除字幕Id
		
		$addCaptionMediaIds = $this->paramFilter('addCaptionMediaIds', 'array'); // 添加配音文件Id（旁白配音）
		$deleteCaptionMediaIds = $this->paramFilter('deleteCaptionMediaIds', 'array'); // 删除配音文件Id（旁白配音）
		
		
		// 标题
		$addTitleIds = $this->paramFilter('addTitleIds', 'array'); // 删除标题Id
		$deleteTitleIds = $this->paramFilter('deleteTitleIds', 'array'); // 删除标题Id
		
		// 音乐
		$addMusicIds = $this->paramFilter('addMusicIds', 'array'); // 添加音乐Id
		$deleteMusicIds = $this->paramFilter('deleteMusicIds', 'array'); // 删除音乐Id
		// 贴纸
		$addTagsMediaIds = $this->paramFilter('addTagsMediaIds', 'array'); // 添加贴纸Id（视频或者图片）
		$deleteTagsMediaIds = $this->paramFilter('deleteTagsMediaIds', 'array'); // 删除贴纸Id（视频或者图片）
		
		// 视频比例
		// 视频时长
		// 视频帧率
		// 音量调节
		// 转场/滤镜
		// 颜色调整
		
		
		$editingSv = \service\Editing::singleton();
		return $editingSv->reviseEditing($this->userId, $editingId);
	}
}