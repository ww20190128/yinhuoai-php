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
    
$file = CACHE_PATH . 'producingJobcallback.txt';
@file_put_contents($file, $body);
		print_r($file);exit;
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
{"id":8,"name":"20260105-剪辑","topic":"说话,一有空","title":"咖喱鸡块","ratio":"9:16","durationType":2,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":2,"updateTime":1767773878,"createTime":1767773878,"lensList":[{"id":22,"name":"片头","index":-1,"type":1,"createTime":1767773878,"updateTime":1767860705,"mediaIds":[7,6,14],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[21,22],"dubMediaIds":[],"mediaInfo":{"id":7,"name":"q8c2OsRlkYHHd574652057c263bf6c323b7d8bb621fc.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/1\/d574652057c263bf6c323b7d8bb621fc.mp4","updateTime":1767586327,"createTime":1767586327,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/78753b25e158ffd6db7f0298e969a743.jpg","duration":3,"size":336831},"dubCaptionInfo":{"id":21,"editingId":8,"text":"123","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZShuSong-Z01S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767773878,"updateTime":1767773878},"transitionSubType":"random"},{"id":23,"name":"片中1","index":1,"type":2,"createTime":1767773878,"updateTime":1767861350,"mediaIds":[3,4],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[430],"dubMediaIds":[],"mediaInfo":{"id":3,"name":"B6Q6MRzYOdwMd58d2eb6a31f98b739880b8f01a60308.png","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/1\/d58d2eb6a31f98b739880b8f01a60308.png","updateTime":1767581829,"createTime":1767581829,"coverURL":"","duration":0,"size":102780},"dubCaptionInfo":{"id":430,"editingId":8,"text":"新的字幕内容不知道不知道不知道不知道hhdhdhdhdhdjdjd","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767861350,"updateTime":1767861350},"transitionSubType":"random"},{"id":24,"name":"片尾","index":100,"type":3,"createTime":1767773878,"updateTime":1767773878,"mediaIds":[1,2],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[],"dubMediaIds":[],"mediaInfo":{"id":2,"name":"UTf5p9FwibLY255493c6d5fddec5a93f4e621a9e6eba.mp4","type":"video","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/video\/7\/255493c6d5fddec5a93f4e621a9e6eba.mp4","updateTime":1767581796,"createTime":1767581796,"coverURL":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/cover\/e4baa9f8bbcfcc0b729e9c95869477be.jpg","duration":46,"size":2719547},"transitionSubType":"random"}],"transitionSubType":"random","previewMediaId":7,"titleInfo":{"id":35,"updateTime":1767860981,"createTime":1767860981,"start":0,"end":5,"captionIds":[429],"title":"新的字幕内容77546","captionList":[{"id":429,"editingId":8,"text":"新的字幕内容77546","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767860981,"updateTime":1767860981}]},"musicInfo":{"id":29,"conId":958,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/2\/765731720671535.mp3","name":"小孩（反盗版）","duration":23,"updateTime":1767773878,"createTime":1767773878},"actorInfo":{"name":"知小白","id":"zhixiaobai","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/audio_ai\/zhixiaobai.wav"}}
EOT;
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);
	
		
		$aliEditingSv = \service\AliEditing::singleton();
// 		$tries = 3;
// 		do {
// 			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
// 		} while (empty($jobId) && --$tries > 0);
// 		sleep(10);

		$jobId = 'ac33f7841bf84a5f8288505a260c5d49';
		$tries = 3;
		do {
			$mediaProducingJob = $aliEditingSv->getMediaProducingJob($jobId);
			
			print_r($mediaProducingJob);exit;
		} while (empty($mediaProducingJob) && --$tries > 0);
		$preview = array();
		$preview['jobStatus'] = $mediaProducingJob['status'];
		$preview['mediaURL'] = empty($mediaProducingJob['mediaURL']) ? '' : $mediaProducingJob['mediaURL'];
		$preview['duration'] = empty($mediaProducingJob['duration']) ? 0 : ceil($mediaProducingJob['duration']);
		print_r($preview);exit;
	}
}