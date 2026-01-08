<?php
namespace ctrl;

/**
 * 剪辑工程
 * 
 * @package ctrl
 */
class Project extends CtrlBase
{
	/**
	 * 为任务完成的回调url
	 * 
	 * @return array
	 */
	public function producingJobcallback()
	{
		$params = $this->params;
    	$body = file_get_contents('php://input');
    	$body = empty($body) ? array() : json_decode($body, true);
    	$body = empty($body['MessageBody']) ? array() : $body['MessageBody'];
    	if (!empty($body['JobId']) && !empty($body['MediaURL']) && !empty($body['MediaId'])) {
    		$projectClipDao = \dao\ProjectClip::singleton();
    		$aliEditingSv = \service\AliEditing::singleton();
	    	$projectClipEttList = $projectClipDao->readListByIndex(array(
	    		'jobId' => $body['JobId'],
	    	));
	    	$now = $this->frame->now;
	    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
	    		if (empty($projectClipEtt->mediaURL)) { // 未生成成片
	    			$mediaInfo = $aliEditingSv->getMediaInfo($body['MediaId']); // 媒体信息
	    			$projectClipEtt->set('mediaURL', $body['MediaURL']);
	    			$projectClipEtt->set('mediaId', $body['MediaId']);
	    			$projectClipEtt->set('jobStatus', 'Success');
	    			$projectClipEtt->set('duration', empty($mediaInfo['duration']) ? 0 : intval($mediaInfo['duration']));
	    			$projectClipEtt->set('updateTime', $now);
	    			$projectClipDao->update($projectClipEtt);
	    		} else {
	    			if ($projectClipEtt->jobStatus != 'Success') {
	    				$projectClipEtt->set('jobStatus', 'Success');
	    				$projectClipDao->update($projectClipEtt);
	    			}
	    		}
	    	}
    	}
		return true;
	}
	
	/**
	 * 获取预览
	 *
	 * @return array
	 */
	public function getPreview()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->getPreview($this->userId, $editingId);
	}
	
	/**
	 * 创建剪辑工程
	 *
	 * @return array
	 */
	public function createProject()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id	
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array(
			'type' => $this->paramFilter('type', 'intval', 1), // 1  创建剪辑 2 保存模板 3保存模板&创建剪辑 
		);
		if (!empty($params['name'])) {
			$info['name'] = $this->paramFilter('name', 'string');
		}
		if (!empty($params['numLimit'])) {
			$info['numLimit'] = $this->paramFilter('numLimit', 'intval');
			$info['numLimit'] = min($info['numLimit'], 2000);
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->createProject($this->userId, $editingId, $info);
	}
	
	/**
	 * 获取剪辑工程列表
	 *
	 * @return array
	 */
	public function getProjectList()
	{

		
		
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$projectSv = \service\Project::singleton();
		$dataList = $projectSv->getProjectList($this->userId);
		$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
		// 符合条件的总条数
		$totalNum = count($dataList);
		// 分页显示
		$dataList = array_slice($dataList, ($pageNum - 1) * $pageLimit, $pageLimit);
		return array(
			'totalNum' => $totalNum,
			'list' => array_values($dataList),
		);
	}
	
	/**
	 * 删除剪辑工程
	 *
	 * @return array
	 */
	public function deleteProject()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$id = $this->paramFilter('id', 'string');
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->deleteProject($this->userId, $id);
	}
	
	/**
	 * 修改剪辑
	 *
	 * @return array
	 */
	public function reviseProject()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$params = (array)$params;
		$id = $this->paramFilter('id', 'string');
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$info = array();
		if (isset($params['name'])) {
			$info['name'] = $this->paramFilter('name', 'string');
		}	
		$projectSv = \service\Project::singleton();
		return $projectSv->reviseProject($this->userId, $id, $info);
	}
	
	/**
	 * 预览列表
	 *
	 * @return array
	 */
	public function getProjectPreviewList()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$id = $this->paramFilter('id', 'string');
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
		$projectSv = \service\Project::singleton();
		return $projectSv->getProjectPreviewList($this->userId, $id, $pageNum, $pageLimit);
	}
	
	/**
	 * 成品库
	 *
	 * @return array
	 */
	public function getProjectClipList()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$id = $this->paramFilter('id', 'string');
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$projectSv = \service\Project::singleton();
		$dataList = $projectSv->getProjectClipList($this->userId, $id);
		$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
		// 符合条件的总条数
		$totalNum = count($dataList);
		// 分页显示
		$dataList = array_slice($dataList, ($pageNum - 1) * $pageLimit, $pageLimit);
		return array(
			'totalNum' => $totalNum,
			'list' => array_values($dataList),
		);
	}
	
	/**
	 * 删除成品
	 *
	 * @return array
	 */
	public function deleteProjectClips()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$ids = $this->paramFilter('ids', 'array');
		if (empty($ids)) {
			throw new $this->exception('请求参数错误');
		}

		$projectSv = \service\Project::singleton();
		return $projectSv->deleteProjectClips($this->userId, $ids);
	}
	
	/**
	 * 生成成片
	 *
	 * @return array
	 */
	public function createProjectClips()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$ids =  $this->paramFilter('ids', 'array');
		if (empty($ids)) {
			throw new $this->exception('请求参数错误');
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->createProjectClips($this->userId, $ids);
	}

	/**
	 * 生成成片
	 *
	 * @return array
	 */
	public function createProjectClipsByNum()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$id = $this->paramFilter('id', 'string');
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$num = $this->paramFilter('num', 'intval');
		if (empty($num)) {
			throw new $this->exception('请求参数错误');
		}
		if ($num > 200) {
			throw new $this->exception('一次最多生成200个');
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->createProjectClipsByNum($this->userId, $id, $num);
	}
	
	/**
	 * 生成成片
	 *ac33f7841bf84a5f8288505a260c5d49
	 * @return array
	 */
	public function test()
	{
		$params = $this->params;
		$chipParam = <<<EOT
{"id":13,"name":"20260105-剪辑","topic":"说话,一有空","title":"咖喱鸡块","ratio":"9:16","durationType":2,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":2,"updateTime":1767866108,"createTime":1767866108,"lensList":[{"id":39,"name":"片头","index":-1,"type":1,"createTime":1767866108,"updateTime":1767866195,"mediaIds":[7,6,14],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[438,439],"dubMediaIds":[],"mediaInfo":{"id":7,"name":"q8c2OsRlkYHHd574652057c263bf6c323b7d8bb621fc.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/d574652057c263bf6c323b7d8bb621fc.mp4","updateTime":1767586327,"createTime":1767586327,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/78753b25e158ffd6db7f0298e969a743.jpg","duration":3,"size":336831},"dubCaptionInfo":{"id":438,"editingId":13,"text":"99999999999999999999999999","font":{"text-align":"center","position":37,"font-size":40,"font-family":"FZShuSong-Z01S"},"style":{"styleType":1,"color":"#ffffff","fontType":2,"background":"#333333","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767866108,"updateTime":1767866160},"transitionSubType":"random"},{"id":40,"name":"片中1","index":1,"type":2,"createTime":1767866108,"updateTime":1767866221,"mediaIds":[3,4],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[440],"dubMediaIds":[],"mediaInfo":{"id":4,"name":"fqUNL7Cg3Ys54afc0dd0bd07adc1ce003885ada43877.png","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/5\/4afc0dd0bd07adc1ce003885ada43877.png","updateTime":1767581829,"createTime":1767581829,"coverURL":"","duration":0,"size":1026940},"dubCaptionInfo":{"id":440,"editingId":13,"text":"新的字幕内容不知道不知道不知道不知道hhdhdhdhdhdjdjd","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ff0000","fontType":2,"background":"#9acd32","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767866108,"updateTime":1767866221},"transitionSubType":"random"},{"id":41,"name":"片尾","index":100,"type":3,"createTime":1767866108,"updateTime":1767866108,"mediaIds":[1,2],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[],"dubMediaIds":[],"mediaInfo":{"id":2,"name":"UTf5p9FwibLY255493c6d5fddec5a93f4e621a9e6eba.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/255493c6d5fddec5a93f4e621a9e6eba.mp4","updateTime":1767581796,"createTime":1767581796,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/e4baa9f8bbcfcc0b729e9c95869477be.jpg","duration":46,"size":2719547},"transitionSubType":"random"}],"transitionSubType":"random","previewMediaId":7,"musicInfo":{"id":77,"conId":958,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/2\/765731720671535.mp3","name":"小孩（反盗版）","duration":23,"updateTime":1767866108,"createTime":1767866108},"actorInfo":{"name":"艾雅","id":"aiya","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/audio_ai\/aiya.wav"}}
EOT;
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);
	
		
		$aliEditingSv = \service\AliEditing::singleton();
		$tries = 3;
		do {
			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
		} while (empty($jobId) && --$tries > 0);
		sleep(10);

		//$jobId = 'ac33f7841bf84a5f8288505a260c5d49';
		$tries = 3;
		do {
			$mediaProducingJob = $aliEditingSv->getMediaProducingJob($jobId);
		} while (empty($mediaProducingJob) && --$tries > 0);
		$preview = array();
		$preview['jobStatus'] = $mediaProducingJob['status'];
		$preview['mediaURL'] = empty($mediaProducingJob['mediaURL']) ? '' : $mediaProducingJob['mediaURL'];
		$preview['duration'] = empty($mediaProducingJob['duration']) ? 0 : ceil($mediaProducingJob['duration']);
		print_r($preview);exit;
	}
}