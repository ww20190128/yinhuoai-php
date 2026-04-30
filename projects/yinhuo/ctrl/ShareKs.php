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
    	$code = $this->paramFilter('code', 'string');
    	if (empty($code)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$userId = $this->paramFilter('userId', 'intval');
    	if (empty($userId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->code2AccessToken($userId, $code);
    }
    
    /**
     * 获取token
     *
     * @return array
     */
    public function refreshAccessToken()
    {
    	$params = $this->params;
    	$userId = $this->paramFilter('userId', 'intval');
    	if (empty($userId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->refreshAccessToken($userId);
    }
    
    /**
     * 上传视频
     *
     * @return array
     */
    public function upload()
    {
    	$params = $this->params;
    	$userId = $this->paramFilter('userId', 'intval');
    	if (empty($userId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$clipId = $this->paramFilter('id', 'string');
    	if (empty($clipId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->upload($userId, $clipId);
    }

    /**
     * 发布视频
     * 
     * @return array
     */
    public function publish()
    {
    	$params = $this->params;
    	$userId = $this->paramFilter('userId', 'intval');
    	if (empty($userId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$clipId = $this->paramFilter('id', 'string');
    	if (empty($clipId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->publish($userId, $clipId);
    }
    
}