<?php
namespace ctrl;

/**
 * 文件夹（因火）
 * 
 * @package ctrl
 */
class Folder extends CtrlBase
{
	/**
	 * 创建文件夹
	 *
	 * @return array
	 */
	public function createFolder()
	{
		$params = $this->params;
		$name = $this->paramFilter('name', 'string'); // 文件夹名称
		$type = $this->paramFilter('type', 'string'); // 文件夹类型
		$parentId = $this->paramFilter('parentId', 'intval', 0); // 父级文件夹Id
		if (empty($name) || !in_array($type, array(
			\constant\Folder::FOLDER_TYPE_VIDEO,
			\constant\Folder::FOLDER_TYPE_IMAGE,
			\constant\Folder::FOLDER_TYPE_AUDIO,
			\constant\Folder::FOLDER_TYPE_TEXT,
		))) {
			throw new $this->exception('请求参数错误');
		}
		if (mb_strlen($name) >= 30) {
			throw new $this->exception('文件名最多30个汉字');
		}
		
		$folderSv = \service\Folder::singleton();
		return $folderSv->createFolder($this->userId, $type, $name, $parentId);
	}
	
	/**
	 * 修改文件夹名称
	 *
	 * @return array
	 */
	public function revise()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 文件夹id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$name = $this->paramFilter('name', 'string');
		if (empty($name)) {
			throw new $this->exception('请设置文件夹名称');
		}
		if (mb_strlen($name) >= 30) {
			throw new $this->exception('文件名最多30个汉字');
		}
		$info = array(
			'name' => $name, // 名称
		);
		$folderSv = \service\Folder::singleton();
		return $folderSv->revise($this->userId, $id, $info);
	}
	
	/**
	 * 删除文件夹
	 *
	 * @return array
	 */
	public function deleteFolder()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 文件夹id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$folderSv = \service\Folder::singleton();
		return $folderSv->deleteFolder($this->userId, $id);
	}
	
	/**
	 * 文件夹上传素材
	 *
	 * @return array
	 */
	public function uploadMedias()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 文件夹id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$uploadFiles = array();
		$files = empty($_FILES) ? array() : $_FILES; // 上传的图片信息
		if (!empty($files)) foreach ($files as $file) {
			$fileInfo = pathinfo($file['name']);
			$fileTmp = '/tmp/' .  $file['name'];
			move_uploaded_file($file['tmp_name'], $fileTmp);
			if (!file_exists($fileTmp)) {
				throw new $this->exception('文件上传失败');
			}
			$pictureFile = '';
			@copy($fileTmp, '/data/www/yinhuo-static/' . $file['name']);
			
			$uploadFiles[] = array(
				'extension' => $fileInfo['extension'],
				'file' 		=> $fileTmp,
				'name' 		=> $file['name'],
			);
		}	
		$folderSv = \service\Folder::singleton();
		return $folderSv->uploadMedias($this->userId, $id, $uploadFiles);
	}
	
	/**
	 * 删除文件夹的素材
	 *
	 * @return array
	 */
	public function deleteMedias()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 文件夹id
		$mediaIds = $this->paramFilter('mediaIds', 'array'); // 素材id
		if (empty($id) || empty($mediaIds)) {
			throw new $this->exception('请求参数错误');
		}
		$folderSv = \service\Folder::singleton();
		return $folderSv->deleteMedias($this->userId, $id, $mediaIds);
	}
	
	/**
	 * 文件夹列表
	 *
	 * @return array
	 */
	public function getList()
	{
		$params = $this->params;
		$type = $this->paramFilter('type', 'string'); // 文件夹类型
		if (!in_array($type, array(
			\constant\Folder::FOLDER_TYPE_VIDEO,
			\constant\Folder::FOLDER_TYPE_IMAGE,
			\constant\Folder::FOLDER_TYPE_AUDIO,
			\constant\Folder::FOLDER_TYPE_TEXT,
		))) {
			throw new $this->exception('请求参数错误');
		}
		$folderSv = \service\Folder::singleton();
		$dataList = $folderSv->getList($this->userId, $type);
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
	 * 单个文件夹详情
	 *
	 * @return array
	 */
	public function info()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 文件夹id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
		
		$folderSv = \service\Folder::singleton();
		$info = $folderSv->info($id, $this->userId, $pageNum, $pageLimit);
		return $info;
	}
	
}