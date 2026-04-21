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
    	return $shareKs->code2AccessToken($code);
    }
    
    /**
     * 上传视频
     *
     * @return array
     */
    public function upload()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$clipId = $this->paramFilter('id', 'string');
    	if (empty($clipId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$upload_token = $this->paramFilter('upload_token', 'string');
    	$endpoint = $this->paramFilter('endpoint', 'string');
    	if (empty($upload_token) || empty($endpoint)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->upload($clipId, $upload_token, $endpoint);
    }

    /**
     * 发布视频
     * 
     * @return array
     */
    public function publish()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$clipId = $this->paramFilter('id', 'string');
    	if (empty($clipId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$access_token = $this->paramFilter('access_token', 'string');
    	$upload_token = $this->paramFilter('upload_token', 'string');
    	if (empty($access_token) || empty($upload_token)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$shareKs = \service\ShareKs::singleton();
    	return $shareKs->publish($clipId, $access_token, $upload_token);
    }
    
}