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
{"id":51,"name":"20260107-剪辑","topic":"xxxc,在学校吃饭,休息休息地方,吃吃吃吃吃吃吃","title":"模拟面试","ratio":"9:16","durationType":2,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":2,"updateTime":1768304464,"createTime":1768193462,"lensList":[{"id":159,"name":"片头","index":-1,"type":1,"createTime":1768193462,"updateTime":1768193462,"mediaIds":[92],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[689],"dubMediaIds":[],"mediaInfo":{"id":92,"name":"入场.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/e8e915335017b622f9fb3ff0f6e5c218.mp4","updateTime":1768139695,"createTime":1768139695,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/3ac933175c6931d3a408f314c20df18f.jpg","duration":6,"size":8901923},"dubCaptionInfo":{"id":689,"editingId":51,"text":"入场","font":{"text-align":"center","position":88,"font-size":30,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768193462,"updateTime":1768193462},"transitionSubType":"random"},{"id":160,"name":"片中1","index":1,"type":2,"createTime":1768193462,"updateTime":1768193462,"mediaIds":[97],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[690],"dubMediaIds":[],"mediaInfo":{"id":97,"name":"答题","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/d39bbfef96dbd7a97e0f33959d22db01.mp4","updateTime":1768140990,"createTime":1768140990,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/058312b090943122736783722e66af2a.jpg","duration":100,"size":49026064},"dubCaptionInfo":{"id":690,"editingId":51,"text":"自我介绍","font":{"text-align":"center","position":86,"font-size":24,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768193462,"updateTime":1768193462},"transitionSubType":"random"},{"id":162,"name":"片中2","index":2,"type":2,"createTime":1768193462,"updateTime":1768193462,"mediaIds":[97],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[691],"dubMediaIds":[],"mediaInfo":{"id":97,"name":"答题","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/d39bbfef96dbd7a97e0f33959d22db01.mp4","updateTime":1768140990,"createTime":1768140990,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/058312b090943122736783722e66af2a.jpg","duration":100,"size":49026064},"dubCaptionInfo":{"id":691,"editingId":51,"text":"读题","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768193462,"updateTime":1768193462},"transitionSubType":"random"},{"id":163,"name":"片中3","index":3,"type":2,"createTime":1768193462,"updateTime":1768193462,"mediaIds":[95],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[688],"dubMediaIds":[],"mediaInfo":{"id":95,"name":"自我介绍.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/6785bec3c9b1af42a5d5909030c8e939.mp4","updateTime":1768139772,"createTime":1768139772,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/7bf2e2ddbb7fe740a46772fedafdc303.jpg","duration":12,"size":15491008},"dubCaptionInfo":{"id":688,"editingId":51,"text":"答题","font":{"text-align":"center","position":88,"font-size":25,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":""},"createTime":1768193462,"updateTime":1768193462},"transitionSubType":"random"},{"id":161,"name":"片尾","index":100,"type":3,"createTime":1768193462,"updateTime":1768193462,"mediaIds":[97],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[692],"dubMediaIds":[],"mediaInfo":{"id":97,"name":"答题","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/d39bbfef96dbd7a97e0f33959d22db01.mp4","updateTime":1768140990,"createTime":1768140990,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/058312b090943122736783722e66af2a.jpg","duration":100,"size":49026064},"dubCaptionInfo":{"id":692,"editingId":51,"text":"退场","font":{"text-align":"center","position":84,"font-size":26,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768193462,"updateTime":1768193462},"transitionSubType":"random"}],"transitionSubType":"random","previewMediaId":92,"musicInfo":{"id":416,"conId":916,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731198455882.mp3","name":"青丝（创业进行曲）","duration":68,"updateTime":1768193462,"createTime":1768193462},"actorInfo":{"name":"妹坨洁儿","id":"zh_female_meituojieer_moon_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/妹坨洁儿.mp3"}}
EOT;
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);
		$volcTTSSv = \service\reuse\VolcTTS::singleton();
		
		$folderSv = \service\Folder::singleton();
		$dubFileDao = \dao\DubFile::singleton();
		if (!empty($chipParam['actorInfo'])) { // 有配音
			$actorInfo = $chipParam['actorInfo'];
			$lensList = $chipParam['lensList'];

			foreach ($lensList as $key => $lensRow) {
				$dubCaptionInfo = $lensRow['dubCaptionInfo'];
		
				
				
				$audioParams = array();

    			$dubId = md5($actorInfo['id'] . $dubCaptionInfo['text']);
    			$ttsResult = $volcTTSSv->runByV3($dubCaptionInfo['text'], $actorInfo['id'], $audioParams);
    			$dubFileUrl = $folderSv->createAudio($ttsResult['content'], $ttsResult['duration'], $dubId);
    			
    			
    			print_r($ttsResult);exit;
    			
    			
    			
    			
    			
    			
    			
    			
    			$dubFileEtt = $dubFileDao->readByPrimary($dubId);
    			if (empty($dubFileEtt)) {
    				$ttsResult = $volcTTSSv->runByV3($dubCaptionInfo['text'], $speaker, $audioParams);
    				$dubFileUrl = $folderSv->createAudio($ttsResult['content'], $ttsResult['duration'], $dubId);
    				$now = $this->frame->now;
    				$dubFileEtt = $dubFileDao->getNewEntity();
    				$dubFileEtt->id = $dubId;
    				$dubFileEtt->duration = ceil($ttsResult['duration']);
    				$dubFileEtt->content = base64_encode($ttsResult['content']);
    				$dubFileEtt->url = $dubFileUrl;
    				$dubFileEtt->createTime = $now;
    				$dubFileEtt->updateTime = $now;
    				$dubFileDao->create($dubFileEtt);
    			} else {
    				$dubFileUrl = $dubFileEtt->url;
    			}
				print_r($dubFileUrl);exit;
			}
			
		}
		print_r($chipParam);exit;
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