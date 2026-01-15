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
{"id":12,"name":"20260114-剪辑","topic":"","title":"","ratio":"9:16","durationType":2,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":0,"dubType":1,"updateTime":1768457581,"createTime":1768457581,"lensList":[{"id":11,"name":"片头","index":-1,"type":1,"createTime":1768457581,"updateTime":1768458350,"mediaIds":[7,28],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[11,12],"dubMediaIds":[],"mediaInfo":{"id":7,"name":"q8c2OsRlkYHHd574652057c263bf6c323b7d8bb621fc.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/d574652057c263bf6c323b7d8bb621fc.mp4","updateTime":1767586327,"createTime":1767586327,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/78753b25e158ffd6db7f0298e969a743.jpg","duration":3,"size":336831},"dubCaptionInfo":{"id":12,"editingId":12,"text":"头部头部头部头部头部头部头部头部","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ffffff","fontType":2,"background":"#666666","border-color":"#ffffff","border-size":2,"effectColorStyle":""},"createTime":1768457581,"updateTime":1768458350,"dubKey":"1d354737c7476c86246ebda550309e27","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/1d354737c7476c86246ebda550309e27.mp3","duration":"2.5050"}},{"id":12,"name":"片中1","index":1,"type":2,"createTime":1768457581,"updateTime":1768457613,"mediaIds":[13],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[13,15],"dubMediaIds":[],"mediaInfo":{"id":13,"name":"tmp_c2d551b4c2acfae6d3513eea4d2f8374176761fab9cb4cd5.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/4\/0491a9e46673ebf0afdb67d695af44f2.mp4","updateTime":1767771372,"createTime":1767771372,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/8b8add758d7efd1f59bb6a35a2220407.jpg","duration":119,"size":18688300},"dubCaptionInfo":{"id":15,"editingId":12,"text":"我的字幕文件","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ffffff","fontType":2,"background":"#9370db","border-color":"#ffffff","border-size":1,"effectColorStyle":"CS0001-000001"},"createTime":1768457581,"updateTime":1768457581,"dubKey":"5cde3bd631a72d6cc7c34a34df17d486","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/5cde3bd631a72d6cc7c34a34df17d486.mp3","duration":"1.3250"}},{"id":13,"name":"片尾","index":100,"type":3,"createTime":1768457581,"updateTime":1768457581,"mediaIds":[14,7],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[14],"dubMediaIds":[],"mediaInfo":{"id":14,"name":"tmp_3b28356244eea5553464559070fd45fbe1a1ba0541c2183c.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/4\/31d00151e2e0753b2c86d2a8754da8be.mp4","updateTime":1767771428,"createTime":1767771428,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/0bb04851487f0d1d2a17f6808f2c4e0f.jpg","duration":2,"size":209394},"dubCaptionInfo":{"id":14,"editingId":12,"text":"片尾片尾片尾片尾片尾片尾片尾片尾","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#999999","fontType":3,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768457581,"updateTime":1768457581,"dubKey":"a79ab6d5e037d8072e46023160073b4c","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/a79ab6d5e037d8072e46023160073b4c.mp3","duration":"3.7050"}}],"actorInfo":{"name":"邻家女孩","id":"zh_female_linjianvhai_moon_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/邻家女孩.mp3","classify":"通用场景","resourceId":"seed-tts-1.0","language":""},"previewMediaId":7,"titleInfo":{"id":1,"updateTime":1768458251,"createTime":1768458133,"start":0,"end":5,"captionIds":[16],"title":"标题2标22题标题2","captionList":[{"id":16,"editingId":12,"text":"标题2标22题标题2","font":{"text-align":"center","position":22,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ff6b6b","fontType":2,"background":"#98fb98","border-color":"#ffffff","border-size":2,"effectColorStyle":""},"createTime":1768458132,"updateTime":1768458251}]},"musicInfo":{"id":6,"conId":1009,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/3\/765732180194762.mp3","name":"致未来的你我-片段1","duration":20,"updateTime":1768457581,"createTime":1768457581}}
EOT;
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);

//		$chipParam['lensList']['1']['dubCaptionInfo'] = array();
		
	
// 		$chipParam['lensList']['0']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/5/e8e915335017b622f9fb3ff0f6e5c218.mp4';
// 		$chipParam['lensList']['1']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/5/6785bec3c9b1af42a5d5909030c8e939.mp4';
// 		$chipParam['lensList']['2']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/4/578cc2985fea45b8f87c23ee84ab191c.mp4';
// 		$chipParam['lensList']['3']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/7/d39bbfef96dbd7a97e0f33959d22db01.mp4';
// 		$chipParam['lensList']['4']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/2/915b97c0573b5356a0ba67066a0c4da1.mp4';

		
// 		$chipParam['lensList']['0']['dubCaptionInfo']['text'] = '请进';
// 		$chipParam['lensList']['1']['dubCaptionInfo']['text'] = '各位考官大家好，我是9号考生，谢谢考官';
// 		$chipParam['lensList']['2']['dubCaptionInfo']['text'] = '考生 你好，欢迎参加今天的面试。面试时间为10分钟，共3道题。最后2分钟有提示，请注意把握好时间，每回答完一道题请说回答完毕，准备好了吗？好 ，现在开始。
// 考生请听第一题：xx';
// 		$chipParam['lensList']['3']['dubCaptionInfo']['text'] = '答题环节';
// 		$chipParam['lensList']['4']['dubCaptionInfo']['text'] = '考生还有需要补充的吗？没有了，谢谢考官！好，考生请退场';


		
// 		$chipParam['volume']['dubVolume'] = 500;
		
// 		$chipParam['volume']['dubSpeed'] = 100;
//print_r($chipParam);exit;
		

// 		$url = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/project/20_1767935929.mp4';
// 		$folderSv = \service\Folder::singleton();
// 		$a = $folderSv->getMediaInfoByUrl($url);
		
		
// 		print_r($a);exit;
		
	//print_r($chipParam);
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