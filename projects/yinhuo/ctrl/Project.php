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
	 *
	 * @return array
	 */
	public function test()
	{
		$params = $this->params;
		$chipParam = <<<EOT
{"id":9,"name":"20260107-剪辑","topic":"xxxc,在学校吃饭,休息休息地方,吃吃吃吃吃吃吃","title":"星星消除吃","ratio":"9:16","durationType":1,"fps":25,"volume":[],"transitionIds":[],"filterIds":[],"color":null,"background":{"type":1,"color":"","mediaList":[]},"showCaption":1,"dubType":2,"updateTime":1767837736,"createTime":1767789352,"lensList":[{"id":25,"name":"片头","index":-1,"type":1,"createTime":1767789352,"updateTime":1767837587,"mediaIds":[27],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[26],"dubMediaIds":[],"mediaInfo":{"id":27,"name":"tmp_a0a6d3d565e87880c8dd8c8749ff98172917ecfb7f1e686f.jpg","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/1\/8a9d92d628346521c6aedd5b9f16b75c.jpg","updateTime":1767837559,"createTime":1767837559,"coverURL":"","duration":0,"size":0},"dubCaptionInfo":{"id":26,"editingId":9,"text":"111111111这是第一个镜头的图片","font":{"text-align":"center","position":53,"font-size":40,"font-family":"FZShuSong-Z01S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0002-000013"},"createTime":1767789352,"updateTime":1767789352},"transitionSubType":"random"},{"id":26,"name":"片中1","index":1,"type":2,"createTime":1767789352,"updateTime":1767837595,"mediaIds":[24],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[28],"dubMediaIds":[],"mediaInfo":{"id":24,"name":"tmp_450aa51ee71d2b00c9288b10b3ba5dfeb30f92cc4b6f2b48.jpg","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/2\/4f70b66b69eb76f1d4d6c812e10b5926.jpg","updateTime":1767837558,"createTime":1767837558,"coverURL":"","duration":0,"size":0},"dubCaptionInfo":{"id":28,"editingId":9,"text":"2222222222这是第2个镜头的图片","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767789352,"updateTime":1767789352},"transitionSubType":"random"},{"id":28,"name":"片中2","index":2,"type":2,"createTime":1767789380,"updateTime":1767837608,"mediaIds":[23],"originalSound":0,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[],"dubMediaIds":[],"mediaInfo":{"id":23,"name":"tmp_a39e2712657f328285c4387c8e6bf527a886dd6fe6a430c2.jpg","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/2\/df7fb076cd52fb2f400a7127c4342536.jpg","updateTime":1767837558,"createTime":1767837558,"coverURL":"","duration":0,"size":0},"transitionSubType":"random"},{"id":29,"name":"片中3","index":3,"type":2,"createTime":1767789382,"updateTime":1767837621,"mediaIds":[22],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[],"dubMediaIds":[],"mediaInfo":{"id":22,"name":"tmp_00471578862fff98f9b6466222a8c7793201bda8d0ed7103.jpg","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/7\/c4d3895b5576e2419c5c2f6865c40ee0.jpg","updateTime":1767837558,"createTime":1767837558,"coverURL":"","duration":0,"size":0},"transitionSubType":"random"},{"id":27,"name":"片尾","index":100,"type":3,"createTime":1767789352,"updateTime":1767837630,"mediaIds":[25],"originalSound":1,"transitionType":1,"transitionIds":[],"duration":0,"dubType":1,"dubCaptionIds":[27],"dubMediaIds":[],"mediaInfo":{"id":25,"name":"tmp_fe718cc81e48058e1042d260ab98c7d036954eab6137890a.jpg","type":"image","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/resources\/image\/3\/5f5b5a7349de993cbd94de65cc660b74.jpg","updateTime":1767837558,"createTime":1767837558,"coverURL":"","duration":0,"size":0},"dubCaptionInfo":{"id":27,"editingId":9,"text":"这是最后一个镜头的图片","font":{"text-align":"center","position":46,"font-size":40,"font-family":"WenQuanYi Zen Hei Mono"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767789352,"updateTime":1767789352},"transitionSubType":"random"}],"transitionSubType":"random","previewMediaId":27,"titleInfo":{"id":31,"updateTime":1767797906,"createTime":1767797906,"start":0,"end":5,"captionIds":[290,297,306,314,325,331,338,349,353,361,369,376,384,389,394,399,404,408,412,417,421],"title":"新的字幕内容","captionList":[{"id":412,"editingId":9,"text":"新的字幕内容","font":{"text-align":"center","position":80,"font-size":40,"font-family":"FZFangSong-Z02S"},"style":{"styleType":2,"color":"#ffffff","fontType":1,"background":"#ffffff","border-color":"#ffffff","border-size":2,"effectColorStyle":"CS0001-000001"},"createTime":1767797906,"updateTime":1767797906}]},"musicInfo":{"id":48,"conId":918,"type":1,"url":"https:\/\/pyp-xmt.oss-cn-beijing.aliyuncs.com\/hot_music\/1\/765731228715845.mp3","name":"触摸不到的你","duration":60,"updateTime":1767797921,"createTime":1767797921},"actorInfo":{"name":"知德","id":"zhide","url":"https:\/\/wb-yinhuo.oss-cn-beijing.aliyuncs.com\/audio_ai\/zhide.wav"}}
EOT;
		$chipParam = empty($chipParam) ? array() : json_decode($chipParam, true);
		
		
		$aliEditingSv = \service\AliEditing::singleton();
		$tries = 3;
		do {
			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
		} while (empty($jobId) && --$tries > 0);
		sleep(10);
			
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