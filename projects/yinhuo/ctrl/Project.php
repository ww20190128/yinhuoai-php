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
		
		$uploadFiles = array();
		$uploadFiles[] = array(
				'extension' => 'mp4',
				'file' 		=> '/data/www/yinhuoai-php/cache/test.mp4',
				'name' 		=> 'test',
		);
		$folderSv = \service\Folder::singleton();
		return $folderSv->uploadMedias(1, 1, $uploadFiles);
		
		
// 		$text = "测试";
// 		$speaker = 'zh_female_gujie_mars_bigtts';
// 		$volcTTSSv = \service\reuse\VolcTTS::singleton();
// 		$ttsResult = $volcTTSSv->runByV1($text, $speaker);
		
// print_r($ttsResult);exit;
		
		
		
		$answer = "作为一名执法队员，我肩负着维护社会秩序和公正执法的重任。因此，我会第一时间妥善解决：第一，保持冷静和理性，不被群众的情绪激动所影响。安抚好他们的情绪，我会认真倾听群众的投诉，了解他们的具体诉求，确保对他们的困扰和不满有充分的认识。同时，向群众真诚解释之前自己的沟通和规劝工作，表示我对他们再次投诉的理解和重视。第二，立即与施工方进行紧急沟通，详细了解施工项目的进展情况。询问是否存在施工扰民的情况，并且通过测音器进行检测，希望他们提供施工计划和噪音、尘土等污染控制措施。如果施工方确实存在违规行为，我们会依法办事，协助立即采取措施进行整改，确保施工不再对周边居民造成影响。同时，我们也会向施工方的上级部门或街道相关部门汇报这一情况，请求他们加强监管和指导，确保施工项目能够合规进行。我会与相关部门密切合作，共同制定解决方案，并督促施工方按照方案进行整改。最后，及时跟进施工方的整改情况，并定期对施工现场进行检查和监督。我会确保施工方的整改措施得到有效执行，并及时向群众反馈整改结果，确保群众的合法权益得到保障。";
		
// 		$folderSv = \service\Folder::singleton();
// 		$url = $folderSv->getTtsByText($answer, 'zh_female_gujie_mars_bigtts');
		
// 		print_r($url);exit;
		
		
		
		
		
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