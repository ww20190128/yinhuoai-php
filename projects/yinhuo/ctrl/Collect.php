<?php
namespace ctrl;

/**
 * 收藏
 *
 * @author
 */
class Collect extends CtrlBase
{
    /**
     * 加入收藏或者取消收藏
     *
     * @return array
     */
    public function doCollect()
    {
    	$params = $this->params;
    	$testPaperId = $this->paramFilter('testPaperId', 'intval'); // 测评Id
    	$mindfulnessId = $this->paramFilter('mindfulnessId', 'intval'); // 课程ID

    	if (empty($testPaperId) && empty($mindfulnessId)) {
    	    throw new $this->exception('请求参数错误');
    	}
    	$collectSv = \service\Collect::singleton();
    	return $collectSv->doCollect($this->userId, $testPaperId, $mindfulnessId);
    }
   
    /**
     * 收藏记录
     *
     * @return array
     */
    public function collectList()
    {
    	$params = $this->params;	
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$collectSv = \service\Collect::singleton();
    	return $collectSv->collectList($this->userId);
    }
    
}