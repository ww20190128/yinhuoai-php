<?php
namespace ctrl;

/**
 * 快手分享
 *
 * @author
 */
class ShareKs extends CtrlBase
{
    /**
     * 获取token
     * 
     * @return array
     */
    public function code2AccessToken()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$code = $this->paramFilter('code', 'string');
    	if (empty($code)) {
    		throw new $this->exception('请求参数错误');
    	}
    	
    	$shareKs = \service\ShareKs::singleton();
    	$shareKs->code2AccessToken($code);
    }

}