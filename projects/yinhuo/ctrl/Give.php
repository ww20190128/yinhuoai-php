<?php
namespace ctrl;

/**
 * 赠送
 *
 * @author
 */
class Give extends CtrlBase
{
    /**
     * 赠送
     *
     * @return array
     */
    public function give()
    {
    	$params = $this->params;
    	$testPaperId = $this->paramFilter('testPaperId', 'intval'); // 测评Id
    	if (empty($testPaperId)) {
    	    throw new $this->exception('请求参数错误');
    	}
    	$giveSv = \service\Give::singleton();
    	return $giveSv->give($this->userId, $testPaperId);
    }
    
    /**
     * 赠送测评信息
     *
     * @return array
     */
    public function giveInfo()
    {
    	$params = $this->params;
    	$giveId = $this->paramFilter('giveId', 'intval');      
    	if (empty($giveId)) {
            throw new $this->exception('请求参数错误');
        }
    	$giveSv = \service\Give::singleton();
    	$giveInfo = $giveSv->giveInfo($giveId, $this->userId);
    	
    	return array(
    	    'info' => $giveInfo,
    	);
    }
    
    /**
     * 领取
     *
     * @return array
     */
    public function draw()
    {
        $params = $this->params;
        $giveId = $this->paramFilter('giveId', 'intval');
        if (empty($giveId)) {
            throw new $this->exception('请求参数错误');
        }
        if (empty($this->userId)) {
        	throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
        }
        $giveSv = \service\Give::singleton();
        return $giveSv->draw($this->userId, $giveId);
    }
	
    /**
     * 获取赠送记录
     *
     * @return array
     */
    public function giveList()
    {
    	$params = $this->params;	
    	$type = $this->paramFilter('type', 'intval', 1); // 1 赠送   2 领取
    	if (empty($type)) {
    	    throw new $this->exception('请求参数错误');
    	}
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	$giveSv = \service\Give::singleton();
    	$list = $giveSv->giveList($this->userId, $type, $pageNum, $pageLimit);
    	return array(
    	    'list' => array_values($list),
    	);
    }
    
}