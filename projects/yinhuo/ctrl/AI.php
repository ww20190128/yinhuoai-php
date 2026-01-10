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
	 * test
	 *
	 * @return array
	 */
	public function test()
	{
		$AISv = \service\AI::singleton();
		$AISv->test();
	}
}