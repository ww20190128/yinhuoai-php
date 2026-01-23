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