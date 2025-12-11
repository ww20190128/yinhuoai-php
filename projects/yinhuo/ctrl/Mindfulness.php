<?php
namespace ctrl;

/**
 * 正念
 *
 * @author
 */
class Mindfulness extends CtrlBase
{
    /**
     * 首页信息
     *
     * @return array
     */
    public function info()
    {
        $params = $this->params;
        $userId = empty($this->userId) ? 0 : intval($this->userId);
    	$mindfulnessSv = \service\Mindfulness::singleton();
    	return $mindfulnessSv->info($userId);
    }
    
    /**
     * 根据分类获取数据
     *
     * @return array
     */
    public function getListByClassify()
    {
    	$params = $this->params;
    	$userId = empty($this->userId) ? 0 : intval($this->userId);
    	$mindfulnessClassifyId = $this->paramFilter('mindfulnessClassifyId', 'intval', 0);
    	$mindfulnessSv = \service\Mindfulness::singleton();
    	$mindfulnessList = $mindfulnessSv->getListByClassify($userId, array(), $mindfulnessClassifyId);
    	return array(
    		'list' => array_values($mindfulnessList),
    	);
    }
    
    /**
     * 获取正念课程详情
     *
     * @return array
     */
    public function makeAudio()
    {
    	$params = $this->params;
    	$mindfulnessClassifyId = $this->paramFilter('mindfulnessClassifyId', 'intval', 0);
    	$mindfulnessSv = \service\Mindfulness::singleton();
    	$mindfulnessList = $mindfulnessSv->makeAudio();
    	return array(
    		'list' => array_values($mindfulnessList),
    	);
    }
    
    /**
     * 获取正念课程详情
     *
     * @return array
     */
    public function mindfulnessInfo()
    {
    	$params = $this->params;
    	$userId = empty($this->userId) ? 0 : intval($this->userId);
    	$mindfulnessId = $this->paramFilter('mindfulnessId', 'intval');
    	if (empty($mindfulnessId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$mindfulnessSv = \service\Mindfulness::singleton();
    	return $mindfulnessSv->mindfulnessInfo($userId, $mindfulnessId);
    }
    
}