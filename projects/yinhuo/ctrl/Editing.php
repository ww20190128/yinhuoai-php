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
		return $editingSv->addLensMedias($userId, $editingId, $lensId, $mediaIds);
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
		$removeMediaIds = $this->paramFilter('removeMediaIds', 'array'); // 删除的素材Id
		$info = array(
			'name' => $lensName,
			'duration' => $duration,
			'transitionIds' => $transitionIds,
			'removeMediaIds' => $removeMediaIds,
		);
		$editingSv = \service\Editing::singleton();
		return $editingSv->reviseLens($userId, $lensId, $info);
	}
	
	/**
	 * 剪辑工程详情
	 *
	 * @return array
	 */
	public function clipsInfo()
	{
		$params = $this->params;
		$code = $this->paramFilter('code', 'string'); // 回调码
		if (empty($code)) {
			throw new $this->exception('请求参数错误');
		}
		$userSv = \service\User::singleton();
		return $userSv->loginByWeChat($code);
	}

}