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
		if (isset($params['status'])) {
			$info['status'] = $this->paramFilter('status', 'intval');
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
	 * 重新生成成片
	 *
	 * @return array
	 */
	public function createProjectClips()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$ids =  $this->paramFilter('ids', 'array'); // 成品Id
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
		$projectId = $this->paramFilter('id', 'string'); // 工程Id
		if (empty($projectId)) {
			throw new $this->exception('请求参数错误');
		}
		$num = $this->paramFilter('num', 'intval');
		if (empty($num)) {
			throw new $this->exception('请求参数错误');
		}
		if ($num > 200) {
			throw new $this->exception('一次最多生成200个成片');
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->createProjectClipsByNum($this->userId, $projectId, $num);
	}
	
	/**
	 * 发布成品
	 *
	 * @return array
	 */
	public function publicClip()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$projectSv = \service\Project::singleton();
		return $projectSv->publicClip($this->userId);
	}
	
	// 数字人
	public function avatarTest()
	{
		$requestParam = array(
			'avatarName' => '数字人测试',	// 数字人名称
			'avatarDescription' => '测试数字人001', // 数字人描述	
			'avatarType' => '2DAvatar', // 数字人类型
			'thumbnail' => '', // 缩略图 URL
			'portrait' => '',	//  头像图片的媒资 Id
			'video' => '', // 训练视频媒资 ID
		);
		$aliAvatarSv = \service\AliAvatar::singleton();
		$jobId = $aliAvatarSv->createAvatarTraining($requestParam);
		
		// 提交训练任务
		$jobId = $aliAvatarSv->submitAvatarTraining($jobId);
		
		// 获取数字人详情
		$avatarId = $aliAvatarSv->getAvatarTraining($jobId);
		
		// 获取数字人详情
		$info = $aliAvatarSv->getAvatar($avatarId);
	}
	
	public function voiceTest()
	{
		$param = array(
			'voiceId' => 'wjy',
			'voiceName' => '王静怡',
			'voiceDesc' => '声音克隆测试',
			'gender' => 'female',
			'scenario' => 'interaction',
		);
		$aliVoiceSv = \service\AliVoice::singleton();
		//$jobId = $aliVoiceSv->createCustomizedVoiceJob($param);
		// $jobId  9c322edb937943f1a7e758a448f09126
		$param = array(
			'voiceId' => 'wjy',
			'demoAudioMediaURL' => 'https://yinhuo-ai.oss-cn-beijing.aliyuncs.com/resources/video/5/492da053c4fefa4d84309ea5c7fa9d1b.mp4',
				
		);
		$jobId = $aliVoiceSv->submitCustomizedVoiceJob($param);
		$param = array(
			'scenario' => 'interaction',
		);
// 		$jobId = $aliVoiceSv->getDemonstrationForCustomizedVoiceJob($param);
		//https://yinhuo-ai.oss-cn-beijing.aliyuncs.com/resources/video/5/492da053c4fefa4d84309ea5c7fa9d1b.mp4
	}
	
	/**
	 * 生成成片
	 *ac33f7841bf84a5f8288505a260c5d49
	 * @return array
	 */
	public function test()
	{
//print_r( strtotime('2026-01-28 00:00:00'));exit;
// 		$uploadFiles = array();
// 		$uploadFiles[] = array(
// 				'extension' => 'mp4',
// 				'file' 		=> '/data/www/yinhuoai-php/cache/test.mp4',
// 				'name' 		=> 'test',
// 		);
// 		$folderSv = \service\Folder::singleton();
// 		return $folderSv->uploadMedias(1, 1, $uploadFiles);
		
		
// 		$text = "测试";
// 		$speaker = 'zh_female_gujie_mars_bigtts';
// 		$volcTTSSv = \service\reuse\VolcTTS::singleton();
// 		$ttsResult = $volcTTSSv->runByV1($text, $speaker);
		
//  print_r($ttsResult);exit;
		
		
		
		$text = "作为一名执法队员，我肩负着维护社会秩序和公正执法的重任。因此，我会第一时间妥善解决：第一，保持冷静和理性，不被群众的情绪激动所影响。安抚好他们的情绪，我会认真倾听群众的投诉，了解他们的具体诉求，确保对他们的困扰和不满有充分的认识。同时，向群众真诚解释之前自己的沟通和规劝工作，表示我对他们再次投诉的理解和重视。第二，立即与施工方进行紧急沟通，详细了解施工项目的进展情况。询问是否存在施工扰民的情况，并且通过测音器进行检测，希望他们提供施工计划和噪音、尘土等污染控制措施。如果施工方确实存在违规行为，我们会依法办事，协助立即采取措施进行整改，确保施工不再对周边居民造成影响。同时，我们也会向施工方的上级部门或街道相关部门汇报这一情况，请求他们加强监管和指导，确保施工项目能够合规进行。我会与相关部门密切合作，共同制定解决方案，并督促施工方按照方案进行整改。最后，及时跟进施工方的整改情况，并定期对施工现场进行检查和监督。我会确保施工方的整改措施得到有效执行，并及时向群众反馈整改结果，确保群众的合法权益得到保障。";
		
		
		$folderSv = \service\Folder::singleton();
		$url = $folderSv->getTtsByText($text, 'zh_female_gujie_mars_bigtts');
	
		
	print_r($url);exit;
		
		
		
		
		
		$params = $this->params;
		$chipParam = <<<EOT
{"id":11,"name":"20260114-剪辑","topic":"","title":"","ratio":"9:16","durationType":1,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":1,"updateTime":1768572518,"createTime":1768376816,"lensList":[{"id":7,"name":"片头","index":-1,"type":1,"createTime":1768376816,"updateTime":1768468825,"mediaIds":[92],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[19],"dubMediaIds":[],"mediaInfo":{"id":92,"name":"入场.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/e8e915335017b622f9fb3ff0f6e5c218.mp4","updateTime":1768139695,"createTime":1768139695,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/3ac933175c6931d3a408f314c20df18f.jpg","duration":6,"size":8901923}},{"id":8,"name":"片中1","index":1,"type":2,"createTime":1768376816,"updateTime":1768468850,"mediaIds":[95],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[20],"dubMediaIds":[],"mediaInfo":{"id":95,"name":"自我介绍.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/6785bec3c9b1af42a5d5909030c8e939.mp4","updateTime":1768139772,"createTime":1768139772,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/7bf2e2ddbb7fe740a46772fedafdc303.jpg","duration":12,"size":15491008}},{"id":15,"name":"片中2","index":2,"type":2,"createTime":1768468559,"updateTime":1768468890,"mediaIds":[95],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[21],"dubMediaIds":[],"mediaInfo":{"id":95,"name":"自我介绍.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/6785bec3c9b1af42a5d5909030c8e939.mp4","updateTime":1768139772,"createTime":1768139772,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/7bf2e2ddbb7fe740a46772fedafdc303.jpg","duration":12,"size":15491008}},{"id":16,"name":"片中3","index":3,"type":2,"createTime":1768468695,"updateTime":1768468910,"mediaIds":[99],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[22],"dubMediaIds":[],"mediaInfo":{"id":99,"name":"退场","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/915b97c0573b5356a0ba67066a0c4da1.mp4","updateTime":1768140990,"createTime":1768140990,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/2f669a07c56c9bc3974b06e9531abedd.jpg","duration":12,"size":12390712}},{"id":9,"name":"片尾","index":100,"type":3,"createTime":1768376816,"updateTime":1768468935,"mediaIds":[97],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[23],"dubMediaIds":[],"mediaInfo":{"id":97,"name":"答题","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/d39bbfef96dbd7a97e0f33959d22db01.mp4","updateTime":1768140990,"createTime":1768140990,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/058312b090943122736783722e66af2a.jpg","duration":100,"size":49026064}}],"actorInfo":{"name":"冷酷哥哥","id":"ICL_zh_male_lengkugege_v1_tob","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/冷酷哥哥.mp3","classify":"通用场景","resourceId":"seed-tts-1.0","language":""},"previewMediaId":92,"titleInfo":{"id":3,"updateTime":1768469193,"createTime":1768469193,"start":0,"end":5,"captionIds":[25,26],"title":"华图","captionList":[{"id":25,"editingId":11,"text":"华图","font":{"text-align":"center","position":80,"font-size":40,"font-family":"Alibaba PuHuiTi"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768469193,"updateTime":1768469193},{"id":26,"editingId":11,"text":"华图","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1768469193,"updateTime":1768469193}]}}
EOT;
		
	
/*
 * 单位接到群众投诉，举报小区附近有施工扰民，由我去进行沟通处理，去到现场后发现正在施工的是街道的一个重点项目。
当时，我对施工现场情况做了沟通和规劝，但一段时间之后，这位居民又再次投诉，说该项工程施工扰民问题再次发生，并认为我涉嫌包庇、要联系媒体曝光。
如果不及时处理，会严重影响单位的公信力。作为一名执法队员，我肩负着维护社会秩序和公正执法的重任。
因此，我会第一时间妥善解决：第一，保持冷静和理性，不被群众的情绪激动所影响。安抚好他们的情绪，我会认真倾听群众的投诉，
了解他们的具体诉求，确保对他们的困扰和不满有充分的认识。同时，向群众真诚解释之前自己的沟通和规劝工作，表示我对他们再次投诉的理解和重视。
第二，立即与施工方进行紧急沟通，详细了解施工项目的进展情况。询问是否存在施工扰民的情况，并且通过测音器进行检测，希望他们提供施工计划和噪音、尘土等污染控制措施。
如果施工方确实存在违规行为，我们会依法办事，协助立即采取措施进行整改，确保施工不再对周边居民造成影响。
同时，我们也会向施工方的上级部门或街道相关部门汇报这一情况，请求他们加强监管和指导，确保施工项目能够合规进行。
我会与相关部门密切合作，共同制定解决方案，并督促施工方按照方案进行整改。最后，及时跟进施工方的整改情况，并定期对施工现场进行检查和监督。
我会确保施工方的整改措施得到有效执行，并及时向群众反馈整改结果，确保群众的合法权益得到保障。
 */
		

		
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);

		
		

		//print_r($chipParam);
$matter = "单位接到群众投诉，举报小区附近有施工扰民，由你去进行沟通处理，去到现场后发现正在施工的是街道的一个重点项目，当时你对施工现场情况做了沟通和规劝，但一段时间之后，这位居民又再次投诉，说该项工程施工扰民问题再次发生，并认为你涉嫌包庇、要联系媒体曝光。如果你作为一个执法队员，请问你怎么办?";
$answer = "作为一名执法队员，我肩负着维护社会秩序和公正执法的重任。因此，我会第一时间妥善解决：第一，保持冷静和理性，不被群众的情绪激动所影响。安抚好他们的情绪，我会认真倾听群众的投诉，了解他们的具体诉求，确保对他们的困扰和不满有充分的认识。同时，向群众真诚解释之前自己的沟通和规劝工作，表示我对他们再次投诉的理解和重视。第二，立即与施工方进行紧急沟通，详细了解施工项目的进展情况。询问是否存在施工扰民的情况，并且通过测音器进行检测，希望他们提供施工计划和噪音、尘土等污染控制措施。如果施工方确实存在违规行为，我们会依法办事，协助立即采取措施进行整改，确保施工不再对周边居民造成影响。同时，我们也会向施工方的上级部门或街道相关部门汇报这一情况，请求他们加强监管和指导，确保施工项目能够合规进行。我会与相关部门密切合作，共同制定解决方案，并督促施工方按照方案进行整改。最后，及时跟进施工方的整改情况，并定期对施工现场进行检查和监督。我会确保施工方的整改措施得到有效执行，并及时向群众反馈整改结果，确保群众的合法权益得到保障。";

// $folderSv = \service\Folder::singleton();
// $url = $folderSv->getTtsByText($answer, 'zh_female_gujie_mars_bigtts');


// print_r($url);exit;
$dubCaptionInfoTmp = array(
	'font' => array(
		'text-align' => 'center',
		'position' => 90,
		'font-size' => 50,
		'font-family' => 'Alibaba PuHuiTi',
	),
	'style' => array(
		'styleType' => 2,
		'color' => '#ffffff',
		'fontType' => 1,
		'background' => '#ffffff',
		'border-color' => '#ffffff',
		'border-size' => 2,
		'effectColorStyle' => 'CS0001-000001',
	),
);

//$chipParam['showCaption'] = 1;
//		$chipParam['lensList']['1']['dubCaptionInfo'] = array();
		
$chipParam['lensList']['0']['transitionIds'] = array('polka'); // 转场
$chipParam['lensList']['1']['transitionIds'] = array('polka'); // 转场
$chipParam['lensList']['2']['transitionIds'] = array('polka'); // 转场
$chipParam['lensList']['3']['transitionIds'] = array('polka'); // 转场
$chipParam['lensList']['4']['transitionIds'] = array('polka'); // 转场

$chipParam['lensList']['0']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/5/e8e915335017b622f9fb3ff0f6e5c218.mp4';
$chipParam['lensList']['1']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/5/6785bec3c9b1af42a5d5909030c8e939.mp4';
$chipParam['lensList']['2']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/4/578cc2985fea45b8f87c23ee84ab191c.mp4';
$chipParam['lensList']['3']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/7/d39bbfef96dbd7a97e0f33959d22db01.mp4';
$chipParam['lensList']['4']['mediaInfo']['url'] = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/resources/video/2/915b97c0573b5356a0ba67066a0c4da1.mp4';


$chipParam['lensList']['0']['dubCaptionInfo'] = $dubCaptionInfoTmp;
$chipParam['lensList']['0']['dubCaptionInfo']['text'] = "请进！";
$chipParam['lensList']['0']['dubCaptionInfo']['speaker'] = 'ICL_zh_male_buyan_v1_tob';
$chipParam['lensList']['0']['dubCaptionInfo']['in'] = '3'; // 从第3秒开始播放
$chipParam['lensList']['0']['dubCaptionInfo']['font']['position'] = 90;

$chipParam['lensList']['1']['dubCaptionInfo'] = $dubCaptionInfoTmp;
$chipParam['lensList']['1']['dubCaptionInfo']['text'] = '各位考官大家好！我是9号 考生，谢谢考官！';
$chipParam['lensList']['1']['dubCaptionInfo']['speaker'] = 'zh_female_yangmi_mars_bigtts';
$chipParam['lensList']['1']['dubCaptionInfo']['in'] = '3'; // 从第3秒开始播放
$chipParam['lensList']['1']['dubCaptionInfo']['font']['position'] = 90;

$chipParam['lensList']['2']['dubCaptionInfo'] = $dubCaptionInfoTmp;
$chipParam['lensList']['2']['dubCaptionInfo']['speaker'] = 'ICL_zh_male_buyan_v1_tob';
$chipParam['lensList']['2']['dubCaptionInfo']['text'] = "考生 你好！欢迎参加今天的面试。面试时间为10分钟，共3道题。最后2分钟有提示，请注意把握好时间，每回答完一道题,请说回答完毕，准备好了吗？好 ，现在开始。考生请听第一题：{$matter}";
$chipParam['lensList']['2']['dubCaptionInfo']['font']['position'] = 90;


// 答题
$chipParam['lensList']['3']['dubCaptionInfo'] = $dubCaptionInfoTmp;
$chipParam['lensList']['3']['dubCaptionInfo']['text'] = "{$answer}";
$chipParam['lensList']['3']['dubCaptionInfo']['speaker'] = 'zh_female_yangmi_mars_bigtts';
$chipParam['lensList']['3']['dubCaptionInfo']['font']['position'] = 90;

$chipParam['lensList']['4']['dubCaptionInfo'] = $dubCaptionInfoTmp;
$chipParam['lensList']['4']['dubCaptionInfo']['in'] = '1'; // 从第1秒开始播放
$chipParam['lensList']['4']['dubCaptionInfo']['speaker'] = 'ICL_zh_male_buyan_v1_tob';
$chipParam['lensList']['4']['dubCaptionInfo']['text'] = '考生还有需要补充的吗？没有了，谢谢考官！好，考生请退场';
$chipParam['lensList']['4']['dubCaptionInfo']['font']['position'] = 90;


// 标题

// $chipParam['titleInfo']['captionList']['0']['text'] = '模拟面试';
// $chipParam['titleInfo']['captionList']['0']['font']['position'] = 5;

$tmp = $chipParam['titleInfo']['captionList']['1'];

$chipParam['titleInfo']['captionList']['0'] = $tmp;
$chipParam['titleInfo']['captionList']['0']['text'] = '入场';
$chipParam['titleInfo']['captionList']['0']['font']['position'] = 10;
$chipParam['titleInfo']['captionList']['0']['font']['font-size'] = 30;
$chipParam['titleInfo']['captionList']['0']['lensIndex'] = 0;

$chipParam['titleInfo']['captionList']['1'] = $tmp;
$chipParam['titleInfo']['captionList']['1']['text'] = '自我介绍';
$chipParam['titleInfo']['captionList']['1']['font']['position'] = 10;
$chipParam['titleInfo']['captionList']['1']['font']['font-size'] = 40;
$chipParam['titleInfo']['captionList']['1']['lensIndex'] = 1;



$chipParam['titleInfo']['captionList']['2'] = $tmp;
$chipParam['titleInfo']['captionList']['2']['text'] = '面试官读题';
$chipParam['titleInfo']['captionList']['2']['font']['position'] = 10;
$chipParam['titleInfo']['captionList']['2']['font']['font-size'] = 40;
$chipParam['titleInfo']['captionList']['2']['lensIndex'] = 2;

$chipParam['titleInfo']['captionList']['3'] = $tmp;
$chipParam['titleInfo']['captionList']['3']['text'] = '考生答题';
$chipParam['titleInfo']['captionList']['3']['font']['position'] = 10;
$chipParam['titleInfo']['captionList']['3']['font']['font-size'] = 40;
$chipParam['titleInfo']['captionList']['3']['lensIndex'] = 3;

$chipParam['titleInfo']['captionList']['4'] = $tmp;
$chipParam['titleInfo']['captionList']['4']['text'] = '考生退场';
$chipParam['titleInfo']['captionList']['4']['font']['position'] = 10;
$chipParam['titleInfo']['captionList']['4']['font']['font-size'] = 40;
$chipParam['titleInfo']['captionList']['4']['lensIndex'] = 4;
		
// 		$chipParam['volume']['dubVolume'] = 500;
		
// 		$chipParam['volume']['dubSpeed'] = 100;


// 		$url = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/project/20_1767935929.mp4';
// 		$folderSv = \service\Folder::singleton();
// 		$a = $folderSv->getMediaInfoByUrl($url);
		
	/**
	 * ICL_zh_male_buyan_v1_tob
	 * 
	 * 男 配音
	 */
// 		print_r($a);exit;
//unset($chipParam['lensList']['1']);
//unset($chipParam['lensList']['2']);
// unset($chipParam['lensList']['3']);
// unset($chipParam['lensList']['4']);

//unset($chipParam['titleInfo']['captionList']['1']);
// unset($chipParam['titleInfo']['captionList']['2']);
// unset($chipParam['titleInfo']['captionList']['3']);
// unset($chipParam['titleInfo']['captionList']['4']);

$chipParam1 = <<<EOT
{"id":51,"name":"20260114-剪辑","topic":"","title":"","ratio":"9:16","durationType":1,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":2,"updateTime":1769510756,"createTime":1769510534,"lensList":[{"id":151,"name":"片头","index":-1,"type":1,"createTime":1769510534,"updateTime":1769510534,"mediaIds":[285,287,288,281],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[281,282,288,294,300],"dubMediaIds":[],"mediaInfo":{"id":285,"name":"6KtcwRTaM_LSd5a5159063dd2f4cbe6ba8cbc9cc1b21.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/d5a5159063dd2f4cbe6ba8cbc9cc1b21.mp4","updateTime":1769076661,"createTime":1769076661,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/01adc6b7c4dc8cb401d4458b340f0851.jpg","duration":67,"size":74909364},"dubCaptionInfo":{"id":300,"editingId":51,"text":"新的字幕内容11","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":""},"createTime":1769510534,"updateTime":1769510534,"dubKey":"de1349bbb84874831c636bed1b66d780","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/de1349bbb84874831c636bed1b66d780.mp3","duration":0}},{"id":152,"name":"片中1","index":1,"type":2,"createTime":1769510534,"updateTime":1769510534,"mediaIds":[13,292],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[283,285,287],"dubMediaIds":[],"mediaInfo":{"id":292,"name":"jD4x07jlfUMxd574652057c263bf6c323b7d8bb621fc.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/d574652057c263bf6c323b7d8bb621fc.mp4","updateTime":1769086472,"createTime":1769086472,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/4bc0e9fb29ae565fc9fe236ba12c5c55.jpg","duration":3,"size":336831},"dubCaptionInfo":{"id":283,"editingId":51,"text":"片中片中片中片中片中片中片中","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0002-000004"},"createTime":1769510534,"updateTime":1769510534,"dubKey":"f27c9c07e9797e59d115d40c9f535001","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/f27c9c07e9797e59d115d40c9f535001.mp3","duration":"4.032000"}},{"id":153,"name":"片尾","index":100,"type":3,"createTime":1769510534,"updateTime":1769510534,"mediaIds":[14,7],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[284,293],"dubMediaIds":[],"mediaInfo":{"id":14,"name":"tmp_3b28356244eea5553464559070fd45fbe1a1ba0541c2183c.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/4\/31d00151e2e0753b2c86d2a8754da8be.mp4","updateTime":1767771428,"createTime":1767771428,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/e259a2092946bdda78072c04f86e4446.jpg","duration":2,"size":209394},"dubCaptionInfo":{"id":293,"editingId":51,"text":"新的字幕内容6666","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1769510534,"updateTime":1769510534,"dubKey":"4c53e3d3a4714617ded3163404c7cb31","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/dubAudio\/4c53e3d3a4714617ded3163404c7cb31.mp3","duration":0}}],"actorInfo":{"name":"开朗学长","id":"en_male_jason_conversation_wvae_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/Gavin.mp3","classify":"通用场景","resourceId":"seed-tts-1.0","language":""},"previewMediaId":285,"musicInfo":{"id":1189,"conId":1007,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/3\/765732169411089.mp3","name":"阳光洒在长街上（剪辑版）","duration":42,"updateTime":1769510534,"createTime":1769510534}}
EOT;
$chipParam1 = empty($chipParam1) ? array() : json_decode($chipParam1, true);

		$aliEditingSv = \service\AliEditing::singleton();
		$tries = 3;
		do {
			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
		} while (empty($jobId) && --$tries > 0);
		//sleep(10);
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
	
	
	/**
	 * 生成成片
	 * 
	 * @return array
	 */
	public function testClip()
	{
		$actorInfo = array(
			'id' => 'zh_male_ruyayichen_emo_v2_mars_bigtts',
		);
		$captionRow = array(
			'text' => '武汉首家十年老店,猛火现炒满是锅气',
			'speaker' => 'zh_male_ruyayichen_emo_v2_mars_bigtts',
		);
		$folderSv = \service\Folder::singleton();
	$ttsResult = $folderSv->getTts($actorInfo, $captionRow, true);	

// 	_e();exit;
// 	print_r($ttsResult);exit;
// 		$url = 'https://yinhuo-ai.oss-cn-beijing.aliyuncs.com/resources/dubAudio/4053bb5d1ac75863d3a37e0edad0937d.mp3';
// 		$mediaInfo = $folderSv->getMediaInfoByUrl($url); // 注册到媒资
		
// 		print_r($mediaInfo);exit;
		
		
		$params = $this->params;
		$chipParam = <<<EOT
{"id":73,"name":"20260602-剪辑","topic":"十年老店,辣到爽,爱吃辣,美食推荐,新疆炒米粉","title":"椒顽新疆炒米粉湖大店，十年正宗新疆炒米粉老店,椒顽新疆炒米粉湖大店，湖大学生私藏炒米粉神店,椒顽新疆炒米粉湖大店，猛火现炒锅气拉满拒绝预制,椒顽新疆炒米粉湖大店，必打卡新疆炒米粉","ratio":"9:16","durationType":2,"fps":30,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"actorList":[{"name":"顾姐","id":"zh_female_gujie_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/console\/bigtts\/zh_female_gujie_mars_bigtts.mp3","classify":"视频配音","resourceId":"seed-tts-1.0","language":""},{"name":"广州德哥","id":"zh_male_guangzhoudege_emo_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/广州德哥.mp3","classify":"豆包大模型2.0","resourceId":"seed-tts-1.0","language":""},{"name":"京腔侃爷","id":"zh_male_jingqiangkanye_emo_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/京腔侃爷.mp3","classify":"豆包大模型2.0","resourceId":"seed-tts-1.0","language":""},{"name":"儒雅男友","id":"zh_male_ruyayichen_emo_v2_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/儒雅男友.mp3","classify":"豆包大模型2.0","resourceId":"seed-tts-1.0","language":""},{"name":"女雷神","id":"zh_female_leidian_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/zh_female_leidian_mars_bigtts.mp3","classify":"IP仿音","resourceId":"seed-tts-1.0","language":""},{"name":"撒娇学妹","id":"zh_female_yuanqinvyou_moon_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/撒娇学妹.mp3","classify":"豆包大模型2.0","resourceId":"seed-tts-1.0","language":""},{"name":"清新女声","id":"zh_female_qingxinnvsheng_mars_bigtts","url":"https:\/\/lf3-static.bytednsdoc.com\/obj\/eden-cn\/lm_hz_ihsph\/ljhwZthlaukjlkulzlp\/portal\/bigtts\/short_trial_url\/清新女声.mp3","classify":"豆包大模型2.0","resourceId":"seed-tts-1.0","language":""}],"dubType":1,"dubCaptionList":[],"dubMediaList":[],"updateTime":1781744579,"createTime":1781744579,"lensList":[{"id":267,"name":"片头","index":-1,"type":1,"createTime":1781744579,"updateTime":1781745128,"mediaIds":[228,229,233,236],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[2146,2147,2148,2149,2150,2151,2152,2153,2154,2155,2156],"dubMediaIds":[],"mediaList":[{"id":228,"name":"1.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/6\/0f4ff6b26687557ecf1d33f597412aa1.mp4","updateTime":1781743910,"createTime":1781743910,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/104a4d46def1d844350e42d1c5f47f2a.jpg","duration":3,"size":6247767},{"id":229,"name":"2.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/0eec9a15395ef53cbac038882f2e5d54.mp4","updateTime":1781743912,"createTime":1781743912,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/3fd3911afb1b73f2228a1181e6c25c3a.jpg","duration":3,"size":5841815},{"id":233,"name":"3.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/162ac0a6587f932a83523dbc9d4904e2.mp4","updateTime":1781743923,"createTime":1781743923,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/1a679aa3076c5e0fbcd399aec6a84615.jpg","duration":4,"size":6186860},{"id":236,"name":"4.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/098686d264486688a0e494471f4cab3e.mp4","updateTime":1781743928,"createTime":1781743928,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/850f4fdded9592d3c2f18be47b87ff10.jpg","duration":4,"size":6254864}],"dubCaptionList":[{"id":2156,"editingId":73,"text":"湖大后街椒顽新疆炒米粉,武汉首家十年线下老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745128,"updateTime":1781745128},{"id":2155,"editingId":73,"text":"湖大后街椒顽疆味炒米粉,武汉初代十年老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745110,"updateTime":1781745110},{"id":2154,"editingId":73,"text":"湖大后街椒顽炒粉,武汉第一家十年经营老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745085,"updateTime":1781745085},{"id":2153,"editingId":73,"text":"湖大后街椒顽新疆米粉,武汉首家十年堂食老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745063,"updateTime":1781745063},{"id":2152,"editingId":73,"text":"湖大后街椒顽疆炒米粉,武汉老牌十年实体店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745041,"updateTime":1781745041},{"id":2151,"editingId":73,"text":"湖大后街椒顽炒米粉,武汉首家十年线下门店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745012,"updateTime":1781745012},{"id":2150,"editingId":73,"text":"湖大后街,椒顽新疆炒粉,武汉最早米粉老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781744972,"updateTime":1781744972},{"id":2149,"editingId":73,"text":"湖大后街,椒顽疆味米粉,武汉开山十年堂食店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781744891,"updateTime":1781744978},{"id":2148,"editingId":73,"text":"湖北大学后街,椒顽米粉,武汉首家,经营十年老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781744865,"updateTime":1781744983},{"id":2147,"editingId":73,"text":"湖大后街,椒顽新疆炒米粉,武汉初代十年实体老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781744825,"updateTime":1781744990},{"id":2146,"editingId":73,"text":"湖大后街椒顽新疆炒米粉,武汉首家十年老店","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781744579,"updateTime":1781744987}],"dubMediaList":[]},{"id":268,"name":"片中1","index":1,"type":2,"createTime":1781744579,"updateTime":1781745406,"mediaIds":[238,239,240,241,242],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[2157,2158,2159,2160,2161,2162,2163,2164,2165,2166,2167],"dubMediaIds":[],"mediaList":[{"id":238,"name":"6月16日(1).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/6\/c333bd4624e0fa5e2bd82ebd3b561dd0.mp4","updateTime":1781743938,"createTime":1781743938,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/0a7fa12a18a804f13d71a98a5c0ec392.jpg","duration":6,"size":7514480},{"id":239,"name":"6月16日(2).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/4539fb3d1ad2f06aa6b4d86b0feaddbc.mp4","updateTime":1781743940,"createTime":1781743940,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/3946a5217fc70c814b11198f4b60ec03.jpg","duration":7,"size":11076145},{"id":240,"name":"6月16日(3).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/d675b0fa5b91f58236c899a704c9388b.mp4","updateTime":1781743943,"createTime":1781743943,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/c0d0b2c722f05145e2b69e7ab2c2458a.jpg","duration":6,"size":11033997},{"id":241,"name":"6月16日(4).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/eda1e92a7db73ba545693f052a8af843.mp4","updateTime":1781743945,"createTime":1781743945,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/a24890a8ba2881c182aea8297c59ad8b.jpg","duration":6,"size":7222099},{"id":242,"name":"6月16日(5).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/6\/ae95a664e5465a55d8dbe1f74f71b6d5.mp4","updateTime":1781743947,"createTime":1781743947,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/7b6d90c93a67d76a8f2071e9f6c948a5.jpg","duration":7,"size":11838958}],"dubCaptionList":[{"id":2167,"editingId":73,"text":"厨师大火现点现炒,进店全是热闹爆炒声音","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745406,"updateTime":1781745406},{"id":2166,"editingId":73,"text":"大厨铁锅大火炒制,满屋飘散诱人爆炒香气","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745388,"updateTime":1781745388},{"id":2165,"editingId":73,"text":"掌勺师傅猛火现炒,进店环绕滋滋爆炒声响","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745365,"updateTime":1781745365},{"id":2164,"editingId":73,"text":"后厨大厨大火颠锅,店内满是浓郁爆炒烟火","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745340,"updateTime":1781745340},{"id":2163,"editingId":73,"text":"师傅猛火热锅炒制,进门便能听见翻炒噪音","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745318,"updateTime":1781745318},{"id":2162,"editingId":73,"text":"专业大厨明火现炒,满屋都是鲜香翻炒声","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745300,"updateTime":1781745300},{"id":2161,"editingId":73,"text":"掌勺大厨猛火翻炒,进店充斥烟火爆炒声","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745276,"updateTime":1781745276},{"id":2160,"editingId":73,"text":"后厨大火现做,满屋都是诱人爆炒锅气","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745250,"updateTime":1781745250},{"id":2159,"editingId":73,"text":"师傅铁锅猛炒,进店就能听见爆炒声响","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745227,"updateTime":1781745227},{"id":2158,"editingId":73,"text":"厨师明火爆炒,一进门满是滋滋翻炒香气","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745171,"updateTime":1781745171},{"id":2157,"editingId":73,"text":"大厨猛火现炒,进店全是浓郁烟火声响","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745152,"updateTime":1781745152}],"dubMediaList":[]},{"id":270,"name":"片中2","index":2,"type":2,"createTime":1781744579,"updateTime":1781745643,"mediaIds":[234,235,237],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[2168,2169,2170,2171,2172,2173,2174,2175,2176,2177,2178],"dubMediaIds":[],"mediaList":[{"id":234,"name":"3-1.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/1a56be7da62b3030e21ef975bf2ef0a3.mp4","updateTime":1781743925,"createTime":1781743925,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/ab50b1d1d9c9d8076cc37ef2149c46d3.jpg","duration":4,"size":8281766},{"id":235,"name":"3-2.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/2066d42344236add936a4226f9cc9929.mp4","updateTime":1781743926,"createTime":1781743926,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/0731aa2295be168c2bd9f7e857e6f740.jpg","duration":5,"size":8467887},{"id":237,"name":"4-6.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/87d06418761ea13e3be9d0eaa253e75e.mp4","updateTime":1781743929,"createTime":1781743929,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/afe699e8b5625cbaf4cd166b2a09f938.jpg","duration":6,"size":10078560}],"dubCaptionList":[{"id":2178,"editingId":73,"text":"核心调味新疆直邮,米粉醇厚香辣够滋味","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745643,"updateTime":1781745643},{"id":2177,"editingId":73,"text":"秘制酱料,新疆原产地直发,米粉香辣地道","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745620,"updateTime":1781745620},{"id":2176,"editingId":73,"text":"灵魂酱料新疆直发,粉条地道香辣超带感","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745595,"updateTime":1781745595},{"id":2175,"editingId":73,"text":"专用酱包新疆直发,米粉正宗香辣超适口","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745576,"updateTime":1781745576},{"id":2174,"editingId":73,"text":"秘制调味新疆直送,粉条地道香辣超上头","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745557,"updateTime":1781745557},{"id":2173,"editingId":73,"text":"风味酱料新疆直发,米粉正宗辣味浓郁","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745539,"updateTime":1781745539},{"id":2172,"editingId":73,"text":"核心酱料新疆配送,粉条醇厚香辣入味","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745516,"updateTime":1781745516},{"id":2171,"editingId":73,"text":"后厨大火现做,满屋都是诱人爆炒锅气","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745496,"updateTime":1781745496},{"id":2170,"editingId":73,"text":"师傅铁锅猛炒,进店就能听见爆炒声响","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745474,"updateTime":1781745474},{"id":2169,"editingId":73,"text":"秘制酱料新疆直运,粉条正宗香辣超解馋","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745458,"updateTime":1781745458},{"id":2168,"editingId":73,"text":"酱料新疆直发,米粉地道香辣超够味","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745434,"updateTime":1781745434}],"dubMediaList":[]},{"id":271,"name":"片中3","index":3,"type":2,"createTime":1781744579,"updateTime":1781745868,"mediaIds":[230,231,232,243,244],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[2179,2180,2181,2182,2183,2184,2185,2186,2187,2188,2189],"dubMediaIds":[],"mediaList":[{"id":230,"name":"2-2.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/9436bc8bfc06c21c44355224c9691230.mp4","updateTime":1781743914,"createTime":1781743914,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/3e54b66ac8766c2f3d51b6330b02a650.jpg","duration":5,"size":9040740},{"id":231,"name":"2-3.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/f4a105542453d42fb5d521bcf9d05484.mp4","updateTime":1781743915,"createTime":1781743915,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/113a047bfda108e6c430d2942c757f96.jpg","duration":5,"size":8915644},{"id":232,"name":"2-4.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/3\/8372eb0a1f72460d4454cf2517d433e0.mp4","updateTime":1781743917,"createTime":1781743917,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/a7fa999fb12765e3fe8f4ac9a3822ace.jpg","duration":6,"size":9659016},{"id":243,"name":"6月16日(6).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/0\/9765c94a164eed710c8cbee092a19d97.mp4","updateTime":1781743957,"createTime":1781743957,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/7f2e0987654090492dde5ec4df0a45f4.jpg","duration":3,"size":5973036},{"id":244,"name":"6月16日.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/5\/3229f7a0cc13a206f826e9671d7dc83a.mp4","updateTime":1781743959,"createTime":1781743959,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/cba79921a41ef9eeafbb568805be6a71.jpg","duration":6,"size":9203749}],"dubCaptionList":[{"id":2189,"editingId":73,"text":"整年数万笔顾客订单,昼夜坚守匠心制粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745868,"updateTime":1781745868},{"id":2188,"editingId":73,"text":"全年海量成交订单,日夜深耕用心做米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745851,"updateTime":1781745851},{"id":2187,"editingId":73,"text":"全年收获数万订单,晨昏用心出品每碗米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745835,"updateTime":1781745835},{"id":2186,"editingId":73,"text":"整年源源不断订单,日夜坚守匠心做米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745816,"updateTime":1781745816},{"id":2185,"editingId":73,"text":"一整年数万订单量,朝暮用心烹制美味米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745800,"updateTime":1781745800},{"id":2184,"editingId":73,"text":"全年累积大量订单,昼夜坚守做好每一份粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745782,"updateTime":1781745782},{"id":2183,"editingId":73,"text":"整年数万成交订单,日夜用心打磨米粉口感","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745759,"updateTime":1781745759},{"id":2182,"editingId":73,"text":"全年持续爆单,朝夕坚守匠心制作米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745735,"updateTime":1781745735},{"id":2181,"editingId":73,"text":"整年上万笔订单,日夜深耕专注好米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745715,"updateTime":1781745715},{"id":2180,"editingId":73,"text":"整年海量订单,晨昏坚守用心烹制米粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745690,"updateTime":1781745690},{"id":2179,"editingId":73,"text":"全年数万订单,日夜坚守用心做好粉","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745668,"updateTime":1781745668}],"dubMediaList":[]},{"id":269,"name":"片尾","index":100,"type":3,"createTime":1781744579,"updateTime":1781746081,"mediaIds":[245,246,247,248,249],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[2190,2191,2192,2193,2194,2195,2196,2197,2198,2199,2200],"dubMediaIds":[],"mediaList":[{"id":245,"name":"6月18日(1).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/4\/84a6a5d1507d072e8cd8f8d6f3cb15c3.mp4","updateTime":1781743961,"createTime":1781743961,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/a9e692fc3fce45cfb5baedb303f7860e.jpg","duration":6,"size":10112118},{"id":246,"name":"6月18日(2).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/4\/c9bcf2d7b11e0bb3061d3795c335dfa8.mp4","updateTime":1781743963,"createTime":1781743963,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/03c88e68ac361e5586ee0022d6505859.jpg","duration":6,"size":9600294},{"id":247,"name":"6月18日(3).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/0\/d4e6a765d22e2f82f6ceb763fab5dc01.mp4","updateTime":1781743965,"createTime":1781743965,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/371b2ae681aeb003d6dcc849768801b1.jpg","duration":7,"size":11084116},{"id":248,"name":"6月18日(4).mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/0\/cefcd44fe4e1e0265db0a5d505eaf909.mp4","updateTime":1781743973,"createTime":1781743973,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/73535c289cceb475dd87762dadcb5f87.jpg","duration":7,"size":7978501},{"id":249,"name":"6月18日.mp4","type":"video","url":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/video\/2\/8205282f2eb8304c4af2ff2b10026e55.mp4","updateTime":1781743975,"createTime":1781743975,"coverURL":"https:\/\/yinhuo-ai.oss-cn-beijing.aliyuncs.com\/resources\/cover\/eb4932c1bbd02cbd071acd75f16e55d3.jpg","duration":5,"size":7137483}],"dubCaptionList":[{"id":2200,"editingId":73,"text":"生日专属限定米粉,无辣不欢同学速冲","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746081,"updateTime":1781746081},{"id":2199,"editingId":73,"text":"专属生日限定套餐,喜辣同学即刻前来","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746063,"updateTime":1781746063},{"id":2198,"editingId":73,"text":"店内生日限定单品,爱吃辣学子抓紧打卡","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746044,"updateTime":1781746044},{"id":2197,"editingId":73,"text":"上新生日限定米粉,嗜辣同学们快来体验","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746023,"updateTime":1781746023},{"id":2196,"editingId":73,"text":"生日专属限定米粉,爱吃辣同学别错过","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746007,"updateTime":1781746007},{"id":2195,"editingId":73,"text":"有专属生日限定款,爱吃辣学子抓紧冲","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745985,"updateTime":1781745985},{"id":2194,"editingId":73,"text":"特设生日限定单品,喜辣学生赶紧前来","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745968,"updateTime":1781745968},{"id":2193,"editingId":73,"text":"专属生日限定米粉,无辣不欢学生别错过","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745948,"updateTime":1781745948},{"id":2192,"editingId":73,"text":"推出生日限定套餐,爱吃辣的同学速来","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745930,"updateTime":1781745930},{"id":2191,"editingId":73,"text":"店内生日专属米粉,嗜辣学子快来打卡","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745911,"updateTime":1781745911},{"id":2190,"editingId":73,"text":"还有生日限定款,爱吃辣的同学快冲","font":{"text-align":"center","position":75,"font-size":25,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781745892,"updateTime":1781745892}],"dubMediaList":[]}],"titleList":[{"id":634,"updateTime":1781746889,"createTime":1781746889,"start":0,"end":5,"captionIds":[2238,2239,2240],"title":"湖大店","captionList":[{"id":2239,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000012"},"createTime":1781746889,"updateTime":1781746889},{"id":2240,"editingId":73,"text":"猛火现炒拒绝预制","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000012"},"createTime":1781746889,"updateTime":1781746889},{"id":2238,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000012"},"createTime":1781746888,"updateTime":1781746888}]},{"id":633,"updateTime":1781746775,"createTime":1781746775,"start":5,"end":10,"captionIds":[2235,2236,2237],"title":"椒顽新疆炒米粉","captionList":[{"id":2235,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746774,"updateTime":1781746774},{"id":2236,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746774,"updateTime":1781746774},{"id":2237,"editingId":73,"text":"十年正宗新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746774,"updateTime":1781746774}]},{"id":632,"updateTime":1781746765,"createTime":1781746765,"start":5,"end":10,"captionIds":[2232,2233,2234],"title":"猛火现炒,辣到上头疆粉","captionList":[{"id":2232,"editingId":73,"text":"猛火现炒,辣到上头疆粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746765,"updateTime":1781746765},{"id":2233,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746765,"updateTime":1781746765},{"id":2234,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746765,"updateTime":1781746765}]},{"id":631,"updateTime":1781746755,"createTime":1781746755,"start":5,"end":10,"captionIds":[2229,2230,2231],"title":"学子私藏宝藏炒粉店","captionList":[{"id":2229,"editingId":73,"text":"学子私藏宝藏炒粉店","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746755,"updateTime":1781746755},{"id":2230,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746755,"updateTime":1781746755},{"id":2231,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746755,"updateTime":1781746755}]},{"id":630,"updateTime":1781746743,"createTime":1781746743,"start":5,"end":10,"captionIds":[2226,2227,2228],"title":"生日限定米粉来袭","captionList":[{"id":2226,"editingId":73,"text":"生日限定米粉来袭","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746742,"updateTime":1781746742},{"id":2227,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746742,"updateTime":1781746742},{"id":2228,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746742,"updateTime":1781746742}]},{"id":629,"updateTime":1781746728,"createTime":1781746728,"start":5,"end":10,"captionIds":[2223,2224,2225],"title":"椒顽新疆炒米粉","captionList":[{"id":2223,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746728,"updateTime":1781746728},{"id":2224,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746728,"updateTime":1781746728},{"id":2225,"editingId":73,"text":"学子私藏宝藏炒粉店","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000009"},"createTime":1781746728,"updateTime":1781746728}]},{"id":628,"updateTime":1781746638,"createTime":1781746638,"start":0,"end":5,"captionIds":[2220,2221,2222],"title":"椒顽新疆炒米粉","captionList":[{"id":2220,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746638,"updateTime":1781746638},{"id":2221,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746638,"updateTime":1781746638},{"id":2222,"editingId":73,"text":"生日限定米粉来袭","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000007"},"createTime":1781746638,"updateTime":1781746638}]},{"id":627,"updateTime":1781746533,"createTime":1781746533,"start":5,"end":10,"captionIds":[2217,2218,2219],"title":"椒顽新疆炒米粉","captionList":[{"id":2217,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746533,"updateTime":1781746533},{"id":2218,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746533,"updateTime":1781746533},{"id":2219,"editingId":73,"text":"猛火现炒,辣到上头疆粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746533,"updateTime":1781746533}]},{"id":626,"updateTime":1781746470,"createTime":1781746470,"start":0,"end":5,"captionIds":[2216],"title":"椒顽新疆炒米粉","captionList":[{"id":2216,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000003"},"createTime":1781746470,"updateTime":1781746470}]},{"id":625,"updateTime":1781746375,"createTime":1781746375,"start":0,"end":5,"captionIds":[2213,2214,2215],"title":"湖大店","captionList":[{"id":2213,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746375,"updateTime":1781746375},{"id":2214,"editingId":73,"text":"十年正宗新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746375,"updateTime":1781746375},{"id":2215,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746375,"updateTime":1781746375}]},{"id":624,"updateTime":1781746358,"createTime":1781746358,"start":5,"end":10,"captionIds":[2210,2211,2212],"title":"椒顽新疆炒米粉","captionList":[{"id":2210,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746358,"updateTime":1781746358},{"id":2211,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746358,"updateTime":1781746358},{"id":2212,"editingId":73,"text":"十年正宗新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746358,"updateTime":1781746358}]},{"id":623,"updateTime":1781746306,"createTime":1781746306,"start":0,"end":5,"captionIds":[2208,2209],"title":"湖大店","captionList":[{"id":2208,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746306,"updateTime":1781746306},{"id":2209,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746306,"updateTime":1781746306}]},{"id":622,"updateTime":1781746297,"createTime":1781746297,"start":0,"end":5,"captionIds":[2206,2207],"title":"湖大店","captionList":[{"id":2206,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000004"},"createTime":1781746297,"updateTime":1781746297},{"id":2207,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746297,"updateTime":1781746297}]},{"id":621,"updateTime":1781746274,"createTime":1781746274,"start":0,"end":5,"captionIds":[2204,2205],"title":"椒顽新疆炒米粉","captionList":[{"id":2204,"editingId":73,"text":"椒顽新疆炒米粉","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746274,"updateTime":1781746274},{"id":2205,"editingId":73,"text":"湖大店","font":{"text-align":"center","position":25,"font-size":30,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746274,"updateTime":1781746274}]},{"id":620,"updateTime":1781746180,"createTime":1781746180,"start":0,"end":5,"captionIds":[2202,2203],"title":"椒顽新疆炒米粉湖大店","captionList":[{"id":2202,"editingId":73,"text":"椒顽新疆炒米粉湖大店","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746180,"updateTime":1781746180},{"id":2203,"editingId":73,"text":"十年正宗新疆炒米粉","font":{"text-align":"center","position":25,"font-size":30,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746180,"updateTime":1781746180}]},{"id":619,"updateTime":1781746130,"createTime":1781746130,"start":0,"end":5,"captionIds":[2201],"title":"椒顽新疆炒米粉湖大店","captionList":[{"id":2201,"editingId":73,"text":"椒顽新疆炒米粉湖大店","font":{"text-align":"center","position":20,"font-size":32,"font-family":"Microsoft YaHei"},"style":{"styleType":1,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1781746130,"updateTime":1781746130}]}],"musicList":[{"id":747,"conId":906,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731132869911.mp3","name":"小孩（反盗版）","duration":23,"updateTime":1781744579,"createTime":1781744579},{"id":748,"conId":907,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731135839669.mp3","name":"只你(直到幸福能触手可及)","duration":25,"updateTime":1781744579,"createTime":1781744579},{"id":749,"conId":909,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731144085718.mp3","name":"换个方向风景会更好看 (剪辑版)","duration":23,"updateTime":1781744579,"createTime":1781744579},{"id":750,"conId":916,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731198455882.mp3","name":"青丝（创业进行曲）","duration":68,"updateTime":1781744579,"createTime":1781744579},{"id":751,"conId":917,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731209214457.mp3","name":"Forever and Ever and Always","duration":120,"updateTime":1781744579,"createTime":1781744579},{"id":752,"conId":923,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731273043408.mp3","name":"蜜桃物语","duration":170,"updateTime":1781744579,"createTime":1781744579},{"id":753,"conId":925,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731337219864.mp3","name":"We Never","duration":236,"updateTime":1781744579,"createTime":1781744579}],"decalList":[]}
EOT;
	
		$chipParam = preg_replace('/[\x00-\x1F\x7F]/', ',', $chipParam);

		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true, 512, JSON_INVALID_UTF8_IGNORE);


		/**
		 * 标题 
		 * 12 * 5~48
		 * 
		 * 
		 */

print_r($chipParam);exit;


		$aliEditingSv = \service\AliEditing::singleton();
		$tries = 3;
		do {
			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
		} while (empty($jobId) && --$tries > 0);
		//sleep(10);
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