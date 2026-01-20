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
		$AISv = \service\AI::singleton();
		return $AISv->chat();
	}
}