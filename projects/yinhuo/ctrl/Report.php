<?php
namespace ctrl;

/**
 * 报告
 *
 * @author
 */
class Report extends CtrlBase
{
	/**
	 * 获取MBTI的文章
	 *
	 * @return array
	 */
	public function articleInfo()
	{
		$params = $this->params;
		$articleId = $this->paramFilter('id', 'intval'); // 文章Id
		if (empty($articleId)) {
			throw new $this->exception('请求参数错误');
		}
		$reportSv = \service\Report::singleton();
		return $reportSv->articleInfo($articleId);
	}
	
	/**
	 * 获取报告
	 *
	 * @return array
	 */
	public function create()
	{
		$params = $this->params;
		$reportSv = \service\Report::singleton();
		return $reportSv->create();
	}
	
    /**
     * 获取报告
     *
     * @return array
     */
    public function reportInfo()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $reportSv = \service\Report::singleton();
        return $reportSv->reportInfo($this->userId, $testOrderId);
    }

    /**
     * 报告收藏
     *
     * @return array
     */
    public function reportCollect()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $reportSv = \service\Report::singleton();
        return $reportSv->reportCollect($this->userId, $testOrderId);
    }
    
    /**
     * 报告评论
     *
     * @return array
     */
    public function reportComment()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
        
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $experience = $this->paramFilter('experience', 'intval'); // 答题体验感
        $accuracy = $this->paramFilter('accuracy', 'intval'); // 结果准确度
        $satisfaction = $this->paramFilter('satisfaction', 'intval'); // 报告满意度
        $content = $this->paramFilter('content', 'string'); // 评论的内容
        $info = array(
        	'experience' => $experience,	
        	'accuracy' => $accuracy,
        	'satisfaction' => $satisfaction,
        	'content' => $content,
        );
        $reportSv = \service\Report::singleton();
        return $reportSv->reportComment($this->userId, $testOrderId, $info);
    }
   
    /**
     * 更新报告
     *
     * @return array
     */
    public function updateReport()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        return array(
            'totalNum' => 0,
        );
    }
    
    /**
     * 获取答题情况
     *
     * @return array
     */
    public function answerQuestionInfo()
    {
    	$params = $this->params;
    	$testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
    	if (empty($testOrderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$onlyError = $this->paramFilter('onlyError', 'intval', 1); // 只获取错题
    	$ravenSv = \service\report\Raven::singleton();
    	return $ravenSv->answerQuestionInfo($this->userId, $testOrderId, $onlyError);
    }
    
}