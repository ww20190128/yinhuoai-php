<?php
namespace service\report;

/**
 * 样式-标题
 * 
 * @author 
 */
trait HtmlTitle
{
	/**
	 * 【大标题】【居中】【圆形线框，前后2个黄色圆点】
	 * 
	 * @return string
	 */
	protected static function titleBigRound($title, $size = 'big')
	{
		$style = ''; // 样式
		if ($size == 'sm') {
			$style = ' style="padding: 5px 10px !important; font-size: 20px !important; " ';
		}
		// 圆形线框
		return <<<EOT
<div class="title-wireframe"><span{$style}>{$title}</span></div>
EOT;
	}
	
	/**
	 * 【大标题】【居中】【圆形线框，渐变背景色】
	 *
	 * @return string
	 */
	protected static function titleBigBg($title, $color = 'blue')
	{
		if (!in_array($color, array('blue', 'pink'))) {
			$color = 'blue';
		}
		return
		<<<EOT
<div class="title1-bg-{$color}"><span>{$title}</span></div>
EOT;
	}
	
	/**
	 * 【大标题】【靠左】【双语】
	 *
	 * @return string
	 */
	protected static function titleBigBilingual($title, $englishTitle, $color = '')
	{
		$style = ''; // 样式
		if (!empty($color)) {
			$style = ' style="color=' . $color . '" ';
		}
		return
		<<<EOT
<div class="title1-bilingual"><span>{$englishTitle}</span><p{$style}>{$title}</p></div>
EOT;
	}
	
	/**
	 * 【大标题】【组合】【居中】【大标题 + 副标题】
	 *<img src="@/assets/images/gallup/arrow-left.png" class="arrow arrow-left" />
	 * <img src="@/assets/images/gallup/arrow-left.png" class="arrow arrow-right" />
	 * @return string
	 */
	protected  static function titleBigCombination($title, $subTitle = '')
	{
		return <<<EOT
<div class="title1-combination">
	<div class="text-warper">
    	<span class="title">{$title}</span>
   		<span class="tag">{$subTitle}</span>
   	</div>
</div>
EOT;
	}
	
	/**
	 * 【小标题】【居中】【底部下划线】
	 * 
	 * @return string
	 */
	protected static function titleUnderline($title, $type = 1, $postion = 'center', $color = 'red')
	{
		// 下划线
		return <<<EOT
<div class="title-underline"><span>{$title}</span></div>
EOT;
	}
	
	/**
	 * 【小标题】【靠左】【背景】
	 *
	 * @return string
	 */
	protected static function titleBg($title, $color = 'red')
	{
		// 下划线
		return <<<EOT
<div class="title2-bg"><span>{$title}</span></div>
EOT;
	}
	
	/**
	 * 【小标题】【靠左】【圆形序号】
	 *
	 * @return string
	 */
	protected  static function titleIndex($index, $title)
	{
		return <<<EOT
<div class="title2-index"><div class="index">{$index}</div> <span class="title">{$title}</span></div>
EOT;
	}
	
	/**
	 * 【小标题】【组合】【居中】【大标题 + 小标题】 
	 *
	 * @return string
	 */
	protected  static function titleCombination($title, $subTitle = '')
	{
		$style = ''; // 样式
		if (in_array($title, array('一', '二', '三', '四', '五', '六')) || is_numeric($title)) {
			$style = ' style="padding: 0 2px!important;"';
		}
		return <<<EOT
<div class="title2-combination"><div class="title" {$style}>{$title}</div><div class="sub-title">{$subTitle}</div></div>
EOT;
	}

	/**
	 * 【小标题】【组合】【居中】(无背景，圆框) 数字序号 + 标题
	 *
	 * @return string
	 */
	protected  static function titleIndexNum($index, $title = '')
	{
		$style = ''; // 样式
		if (in_array($title, array('一', '二', '三', '四', '五', '六')) || is_numeric($title)) {
			$style = ' style="padding: 0 2px!important;';
		}
		return <<<EOT
<div class="title2-index-num">
	<div class="index">{$index}</div>
 	<span class="title">{$title}</span>
</div>
EOT;
	}
	
	/**
	 * 【小标题】【靠左】(无背景，前圆点)
	 *
	 * @return string
	 */
	protected  static function titleDot($title)
	{
		return <<<EOT
<div class="title2-dot">{$title}</div>
EOT;
	}
	
}