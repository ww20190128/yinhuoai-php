<?php
namespace ctrl;

/**
 * AI
 * 
 * @package ctrl
 */
class AI extends CtrlBase
{
	/**
	 * 分镜字幕润色
	 *
	 * @return array
	 */
	public function lensCaptionTextPolish()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$lensId = $this->paramFilter('lensId', 'intval', 0); // 镜头Id
		if (empty($lensId)) {
			throw new $this->exception('请求参数错误');
		}
		$dubCaptionId = $this->paramFilter('dubCaptionId', 'intval', 0); // 字幕ID
		if (empty($dubCaptionId)) {
			throw new $this->exception("请求参数错误");
		}
		$AISv = \service\AI::singleton();
		return $AISv->captionTextPolish($this->userId, $lensId, $dubCaptionId);
	}
	
	/**
	 * 全局字幕润色
	 *
	 * @return array
	 */
	public function editingCaptionTextPolish()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$editingId = $this->paramFilter('editingId', 'intval', 0); // 剪辑Id
		if (empty($editingId)) {
			throw new $this->exception('请求参数错误');
		}
		$text = $this->paramFilter('text', 'string'); // 字幕
		if (empty($text)) {
			throw new $this->exception("请求参数错误");
		}
		$type = $this->paramFilter('type', 'intval', 1); // 类型
		$AISv = \service\AI::singleton();
		return $AISv->editingCaptionTextPolish($this->userId, $editingId, $text, $type);
	}
	
	/**
	 * 聊天
	 *
	 * @return array
	 */
	public function chat()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$info = array();
		$text = $this->paramFilter('text', 'string');

		$suggestTextLen = $this->paramFilter('suggestTextLen', 'intval'); // 建议文本长度
		$num = $this->paramFilter('num', 'intval', 1); // 生成的条数
		if (empty($text)) {
			throw new $this->exception("请输入信息");
		}
		if (empty($suggestTextLen)) {
			$suggestTextLen = mb_strlen($text);
		}
		$info = array(
			'text' => $text, // 文本
			'suggestTextLen' => $suggestTextLen, // 建议文本长度
			'num' => $num, // 生成的数量
		);
		$AISv = \service\AI::singleton();
		return $AISv->chat($this->userId, $info);
	}
	
}