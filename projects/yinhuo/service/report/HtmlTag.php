<?php
namespace service\report;

/**
 * 样式-标签
 * 
 * @author 
 */
trait HtmlTag
{
	/**
	 * 靠左圆角
	 *
	 * @return string
	 */
	protected static function tagRounded($name, $color = 'blue', $marginLeft = '')
	{
		if (!in_array($color, array('red', 'purple', 'gray', 'blue'))) {
			$color = 'blue';
		}
		if ($marginLeft !== '' && is_numeric($marginLeft)) {
			$marginLeft = <<<EOT
style="margin-left: {$marginLeft}px !important;"
EOT;
		}
		return <<<EOT
<div class="report-tag-{$color}" {$marginLeft}><span>{$name}</span></div>
EOT;
	}
	
	/**
	 * 悬浮标签
	 *
	 * @return string
	 */
	protected static function tagSuspension($name, $color = 'blue', $marginLeft = '')
	{
		if (!in_array($color, array('red', 'pink', 'blue', 'orange', 'green'))) {
			$color = 'blue';
		}
		if ($marginLeft !== '' && is_numeric($marginLeft)) {
			$marginLeft = <<<EOT
style="margin-left: {$marginLeft}px !important;"
EOT;
		}
		return <<<EOT
<div class="report-tag-suspension-{$color}" {$marginLeft}><span>{$name}</span></div>
EOT;
	}
	
	/**
	 * 三角标签
	 *
	 * @return string
	 */
	protected static function tagTriangle($name, $color = 'blue', $marginLeft = '')
	{
		if (!in_array($color, array('red', 'pink', 'blue', 'orange', 'green'))) {
			$color = 'blue';
		}
		if ($marginLeft !== '' && is_numeric($marginLeft)) {
			$marginLeft = <<<EOT
style="margin-left: {$marginLeft}px !important;"
EOT;
		}
		return <<<EOT
<div class="report-tag-triangle-{$color}" {$marginLeft}><span>{$name}</span></div>
EOT;
	}
	
	/**
	 * 内容分割线
	 *
	 * @return string
	 */
	protected static function cardSplit()
	{
		return <<<EOT
<div class="card-split"></div>
EOT;
	}
}