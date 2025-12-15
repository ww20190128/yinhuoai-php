<?php
namespace ctrl;

/**
 * 用户
 * 
 * @package ctrl
 */
class User extends CtrlBase
{
	/**
	 * 微信登录
	 *
	 * @return array
	 */
	public function loginByWeChat()
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
     * 获取用户信息
     *
     * @return array
     */
    public function userInfo()
    {
        $params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
        $userSv = \service\User::singleton();
        return $userSv->userInfo($this->userId);
    }

}