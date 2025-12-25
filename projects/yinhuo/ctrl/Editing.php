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
	 * 剪辑工程详情
	 *
	 * @return array
	 */
	public function editingInfo()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		$editingSv = \service\Editing::singleton();
		return $editingSv->editingInfo($this->userId, $editingId);
	}
	
	/**
	 * 创建字幕
	 * 
	 * @return array
	 */
	public function createCaption()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$params = (array)$params;
		$info = array();
		// 文本内容
		if (!empty($params['text'])) {
			$info['text'] = $this->paramFilter('text', 'string');
		}
		// 排版
		if (!empty($params['text-align'])) {
			$info['text-align'] = $this->paramFilter('text-align', 'string');
		}
		// 位置，取值范围0~100
		if (isset($params['position'])) {
			$info['position'] = $this->paramFilter('position', 'intval');
		}
		// 字号，取值  12~48
		if (!empty($params['font-size'])) {
			$info['font-size'] = $this->paramFilter('font-size', 'intval');
		}
		// 字体
		if (!empty($params['font-family'])) {
			$info['font-family'] = $this->paramFilter('font-family', 'string');
		}
		// 样式类型  1 普通样式  2 花字
		if (!empty($params['styleType'])) {
			$info['styleType'] = $this->paramFilter('styleType', 'intval');
		}
		// 花字效果
		if (isset($params['effectColorStyle'])) {
			$info['effectColorStyle'] = $this->paramFilter('effectColorStyle', 'string');
		}
		// 颜色代码
		if (!empty($params['color'])) {
			$info['color'] = $this->paramFilter('color', 'string');
		}
		// 字体样式  1  暂时不设置   2 字幕背景  3 字幕边框
		if (!empty($params['fontType'])) {
			$info['fontType'] = $this->paramFilter('fontType', 'intval');
		}
		if (isset($params['background'])) { // 背景颜色    fontType == 2
			$info['background'] = $this->paramFilter('background', 'string');
		}
		if (!empty($params['border-color'])) { // 边框颜色    fontType == 3
			$info['border-color'] = $this->paramFilter('border-color', 'string');
		}
		if (!empty($params['border-size'])) { // 边框颜色    fontType == 3  1~4
			$info['border-size'] = $this->paramFilter('border-size', 'intval');
		}
		$captionId = $this->paramFilter('captionId', 'intval', 0); // 字幕Id
		$editingSv = \service\Editing::singleton();
		return $editingSv->createCaption($this->userId, $editingId, $captionId, $info);
	}
	
	/**
	 * 创建标题组
	 *
	 * @return array
	 */
	public function createTitle()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		$titleId = $this->paramFilter('titleId', 'intval', 0); // 标题组Id
		if (!empty($params['start'])) { // 显示时长-开始
			$info['start'] = $this->paramFilter('start', 'intval');
		}
		if (!empty($params['end'])) { // 显示时长-结束
			$info['end'] = $this->paramFilter('end', 'intval');
		}
		$captionIds = $this->paramFilter('captionIds', 'array'); // 文案列表
		if (!empty($captionIds)) { // 显示时长-结束
			$info['captionIds'] = $captionIds;
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->createTitle($this->userId, $editingId, $titleId, $info);
	}
	
	/**
	 * 创建贴纸
	 *
	 * @return array
	 */
	public function createDecal()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		$decalId = $this->paramFilter('decalId', 'intval', 0); // 贴纸ID
		$useLensIds = $this->paramFilter('useLensIds', 'array'); // 适应的镜头ID
		if (!empty($useLensIds)) {
			$info['useLensIds'] = $useLensIds;
		}
		if (isset($params['mediaId1'])) {
			$info['mediaId1'] = $this->paramFilter('mediaId1', 'intval');
		}
		if (isset($params['mediaId2'])) {
			$info['mediaId2'] = $this->paramFilter('mediaId2', 'intval');
		}
		if (isset($params['mediaPostion1'])) {
			$info['mediaPostion1'] = $this->paramFilter('mediaPostion1', 'string');
		}
		if (isset($params['mediaPostion2'])) {
			$info['mediaPostion2'] = $this->paramFilter('mediaPostion2', 'string');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->createDecal($this->userId, $editingId, $decalId, $info);
	}
	
	/**
	 * 创建音乐
	 *
	 * @return array
	 */
	public function createMusic()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		$musicId = $this->paramFilter('musicId', 'intval', 0); // 音乐ID
		if (!empty($params['type'])) {
			$info['type'] = $this->paramFilter('type', 'intval');
		}
		if (!empty($params['conId'])) {
			$info['conId'] = $this->paramFilter('conId', 'intval');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->createMusic($this->userId, $editingId, $musicId, $info);
	}
	
	/**
	 * 镜头详情
	 *
	 * @return array
	 */
	public function lensInfo()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		if (empty($lensId)) {
			throw new $this->exception('请求参数错误');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->lensInfo($lensId, $this->userId);
	}
	
	/**
	 * 添加镜头
	 * 
	 * @return array
	 */
	public function createLens()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->createLens($this->userId, $editingId);
	}
	
	/**
	 * 删除镜头信息
	 *
	 * @return array
	 */
	public function deleteLens()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		if (empty($lensId)) {
			throw new $this->exception('请求参数错误');
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->deleteLens($this->userId, $lensId);
	}
	
	/**
	 * 修改镜头信息
	 * 
	 * @return array
	 */
	public function reviseLens()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		if (empty($lensId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		$addMediaIds = $this->paramFilter('addMediaIds', 'array'); // 添加素材ID列表
		if (!empty($addMediaIds)) {
			$info['addMediaIds'] = $addMediaIds;
		}
		$deleteMediaIds = $this->paramFilter('deleteMediaIds', 'array'); // 删除素材ID列表
		if (!empty($deleteMediaIds)) {
			$info['deleteMediaIds'] = $deleteMediaIds;
		}
		$name = $this->paramFilter('name', 'string'); // 镜头名称
		if (!empty($name)) {
			$info['name'] = $name;
		}
		if (isset($params['duration'])) { // 选择时长
			$info['duration'] = $this->paramFilter('duration', 'intval');
		}
		if (isset($params['originalSound'])) { // 关闭/开启原声
			$info['originalSound'] = $this->paramFilter('originalSound', 'intval');
		}
		// 转场设置
		if (isset($params['transitionType'])) { // 转场设置-类型
			$info['transitionType'] = $this->paramFilter('transitionType', 'intval');
		}
		if (isset($params['transitionIds'])) { // 转场设置-自选的ID
			$info['transitionIds'] = $this->paramFilter('transitionIds', 'array');
		}
		// 配音
		if (isset($params['dubType'])) { // 配音设置-类型 1  手动设置 2 配音文件
			$info['dubType'] = $this->paramFilter('dubType', 'intval');
		}
		$addDubCaptionIds = $this->paramFilter('addDubCaptionIds', 'array'); // 添加配音字幕ID列表（手动设置）
		if (!empty($addDubCaptionIds)) {
			$info['addDubCaptionIds'] = $addDubCaptionIds;
		}
		$deleteDubCaptionIds = $this->paramFilter('deleteDubCaptionIds', 'array'); // 删除配音字幕ID列表（手动设置）
		if (!empty($deleteDubCaptionIds)) {
			$info['deleteDubCaptionIds'] = $deleteDubCaptionIds;
		}
		$addDubMediaIds = $this->paramFilter('addDubMediaIds', 'array'); // 添加配音旁白素材ID列表（自动设置）
		if (!empty($addDubMediaIds)) {
			$info['addDubMediaIds'] = $addDubMediaIds;
		}
		$deleteDubMediaIds = $this->paramFilter('deleteDubMediaIds', 'array'); // 删除配音旁白素材ID列表（自动设置）
		if (!empty($deleteDubMediaIds)) {
			$info['deleteDubMediaIds'] = $deleteDubMediaIds;
		}
		$editingSv = \service\Editing::singleton();
		return $editingSv->reviseLens($this->userId, $lensId, $info);
	}

	/**
	 * 修改剪辑
	 *
	 * @return array
	 */
	public function reviseEditing()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$editingId = $this->paramFilter('editingId', 'intval'); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		if (isset($params['backgroundType'])) { // 背景填充-类型 1 纯色 2  视频/图片 3 视频拉伸模糊
			$info['backgroundType'] = $this->paramFilter('backgroundType', 'intval');
		}
		if (isset($params['backgroundColor'])) { // 背景填充-类型 1 纯色  颜色
			$info['backgroundColor'] = $this->paramFilter('backgroundColor', 'string');
		}
		if (isset($params['backgroundMediaIds'])) { // 背景填充-类型 1 纯色  颜色 媒体ID列表
			$info['backgroundMediaIds'] = $this->paramFilter('backgroundMediaIds', 'array');
		}
		if (isset($params['showCaption'])) { // 字幕/配音-字幕显示
			$info['showCaption'] = $this->paramFilter('showCaption', 'intval');
		}
		// 添加配音演员ID列表
		if (!empty($params['addActorIds'])) {
			$info['addActorIds'] = array_map('trim', explode(',', str_replace('，', ',', $params['addActorIds'])));
		}
		// 删除配音演员ID列表
		if (!empty($params['deleteActorIds'])) {
			$info['deleteActorIds'] = array_map('trim', explode(',', str_replace('，', ',', $params['deleteActorIds'])));
		}
		// 配音
		if (isset($params['dubType'])) { // 配音设置-类型 1  手动设置 2 配音文件
			$info['dubType'] = $this->paramFilter('dubType', 'intval');
		}
		$addDubCaptionIds = $this->paramFilter('addDubCaptionIds', 'array'); // 添加配音字幕ID列表（手动设置）
		if (!empty($addDubCaptionIds)) {
			$info['addDubCaptionIds'] = $addDubCaptionIds;
		}
		$deleteDubCaptionIds = $this->paramFilter('deleteDubCaptionIds', 'array'); // 删除配音字幕ID列表（手动设置）
		if (!empty($deleteDubCaptionIds)) {
			$info['deleteDubCaptionIds'] = $deleteDubCaptionIds;
		}
		$addDubMediaIds = $this->paramFilter('addDubMediaIds', 'array'); // 添加配音旁白素材ID列表（自动设置）
		if (!empty($addDubMediaIds)) {
			$info['addDubMediaIds'] = $addDubMediaIds;
		}
		$deleteDubMediaIds = $this->paramFilter('deleteDubMediaIds', 'array'); // 删除配音旁白素材ID列表（自动设置）
		if (!empty($deleteDubMediaIds)) {
			$info['deleteDubMediaIds'] = $deleteDubMediaIds;
		}
		
		// 标题
		$deleteTitleIds = $this->paramFilter('deleteTitleIds', 'array'); // 删除标题组
		if (!empty($deleteTitleIds)) {
			$info['deleteTitleIds'] = $deleteTitleIds;
		}
		// 音乐
		$deleteMusicIds = $this->paramFilter('deleteMusicIds', 'array'); // 删除音乐
		if (!empty($deleteMusicIds)) {
			$info['deleteMusicIds'] = $deleteMusicIds;
		}
		// 视频比例
		if (isset($params['ratio'])) {
			$info['ratio'] = $this->paramFilter('ratio', 'string');
		}
		if (isset($params['title'])) {
			$info['title'] = $this->paramFilter('title', 'string');
		}
		if (isset($params['name'])) {
			$info['name'] = $this->paramFilter('name', 'string');
		}
		if (isset($params['topic'])) {
			$info['topic'] = $this->paramFilter('topic', 'string');
		}
		if (isset($params['durationType'])) {
			$info['durationType'] = $this->paramFilter('durationType', 'intval');
		}
		if (isset($params['fps'])) {
			$info['fps'] = $this->paramFilter('fps', 'intval');
		}
		if (isset($params['voiceoverVolume'])) {
			$info['voiceoverVolume'] = $this->paramFilter('voiceoverVolume', 'intval');
		}
		if (isset($params['backgroundVolume'])) {
			$info['backgroundVolume'] = $this->paramFilter('backgroundVolume', 'intval');
		}
		if (isset($params['voiceoverSpeed'])) {
			$info['voiceoverSpeed'] = $this->paramFilter('voiceoverSpeed', 'intval');
		}
		if (isset($params['filterIds'])) {
			$info['filterIds'] = $this->paramFilter('filterIds', 'intval');
		}
		if (isset($params['transitionIds'])) {
			$info['transitionIds'] = $this->paramFilter('transitionIds', 'intval');
		}
		if (isset($params['contrast'])) { // 对比度  -100~100
			$info['contrast'] = $this->paramFilter('contrast', 'intval');
		}
		if (isset($params['saturability'])) {
			$info['saturability'] = $this->paramFilter('saturability', 'intval');
		}
		if (isset($params['luminance'])) {
			$info['luminance'] = $this->paramFilter('luminance', 'intval');
		}
		if (isset($params['chroma'])) {
			$info['chroma'] = $this->paramFilter('chroma', 'intval');
		}
		if (isset($params['saveTemplate'])) {
			$info['saveTemplate'] = $this->paramFilter('saveTemplate', 'intval');
		}
		if (isset($params['numList'])) {
			$info['numList'] = $this->paramFilter('numList', 'intval');
		}
		
		$editingSv = \service\Editing::singleton();
		return $editingSv->reviseEditing($this->userId, $editingId, $info);
	}
	

}