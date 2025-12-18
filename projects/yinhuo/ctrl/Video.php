<?php
namespace ctrl;

/**
 * 视频制作
 * 
 * @package ctrl
 */
class Video extends CtrlBase
{
	/**
	 * 创建剪辑或模板
	 *
	 * @return array
	 */
	public function createClips()
	{
		$params = $this->params;
		$code = $this->paramFilter('code', 'string'); // 回调码
		if (empty($code)) {
			throw new $this->exception('请求参数错误');
		}
		$userSv = \service\User::singleton();
		return $userSv->loginByWeChat($code);
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