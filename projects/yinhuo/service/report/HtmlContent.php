<?php
namespace service\report;

/**
 * 样式-内容
 * 
 * @author 
 */
trait HtmlContent
{
	/**
	 * 边框
	 *
	 * @return string
	 */
	protected static function contentBorder($content, $color = 'blue')
	{
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		if (!in_array($color, array('blue', 'yellow', 'red'))) {
			$color = 'blue';
		}
		// 下划线
		return <<<EOT
<div class="border-{$color}">{$content}</div>
EOT;
	}
	
	/**
	 * 线框-卡片
	 *
	 * @return string
	 */
	protected static function contentWireframe($content, $color = 'gray')
	{
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		if (!in_array($color, array('gray', 'purple', 'green'))) {
			$color = 'gray';
		}
		// 下划线
		return <<<EOT
<div class="card-wireframe-{$color}">{$content}</div>
EOT;
	}
	
	/**
	 * 背景色-卡片
	 * 
	 * @return string
	 */
	protected static function contentBg($content, $color = 'gray', $paddingLeft = '', $fontSize = '')
	{
		if (empty($content)) {
			return '';
		}
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		if (!in_array($color, array('gray', 'purple', 'green'))) {
			$color = 'gray';
		}
		$style ='';
		if (!empty($paddingLeft)) {
			$style = <<<EOT
style="margin-left: {$paddingLeft}px !important;"
EOT;
		}

		return <<<EOT
<div class="card-bg-{$color}" {$style}>{$content}</div>
EOT;
	}
	
	/**
	 * 给内容左前后加竖线
	 * 
	 * @return string
	 */
	protected static function addBorderLeft($content)
	{
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		return '<span class="border-left">' . $content . '</span>';
	}
	
	/**
	 * 三级标题-菱形靠左
	 * 内容左边框
	 * 
	 * @return string
	 */
	protected static function combine1($title, $content, $subTitle = '')
	{
		if (empty($subTitle)) {
			return <<<EOT
<div class="title2-combination"><span class="title">{$title}</span></div>
<div class="content-border-left">{$content}</div>
EOT;
		}
		return <<<EOT
<div class="title2-combination"><span class="title">{$title}</span> <span class="sub-title">{$subTitle}</span></div>
<div class="content-border-left">{$content}</div>
EOT;
	}
	
	/**
	 * 最普通的样式，没有内边框
	 * 
	 * @return string
	 */
	protected static function content($content, $paddingLeft = '', $fontSize = '')
	{
		// 样式列表
		$styleArr = array(
			'margin-top: 20px !important;',
			'margin-bottom: 20px !important;',
		);
		if (!empty($paddingLeft)) {
			$styleArr[] = "padding-left: {$paddingLeft}px !important;";
		}	
		if (!empty($fontSize)) {
			if ($fontSize == 'sm') {
				$styleArr[] = "font-size: @font-size-sm !important;";
			} else {
				$styleArr[] = "font-size: {$fontSize}px !important;";
			}
		}
		$style = 'style="' . implode('', $styleArr) . '"';
		
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		return <<<EOT
<div class="content-font" {$style}>{$content}</div>
EOT;
	}
	
	/**
	 * 组织标题内容卡片
	 *
	 * @return string
	 */
	protected static function combineBox($title, $content, $index = '', $showIndex = true, $margin = 1, $color = 'bule')
	{
		$style = '';
		if (empty($margin)) { // 默认要底部间距
			$style = <<<EOT
style="margin-top: 0 !important;margin-bottom: -1px !important;"
EOT;
		}
		if (is_array($content)) {
			foreach ($content as $key => $value) {
				$content[$key] = '<p>' . $value . '</p>';
			}
			$content = implode('', $content);
		}
		if (!in_array($color, array('red', 'blue'))) {
			$color = 'blue';
		}
		if (empty($showIndex) || empty($index)) {
			return <<<EOT
<div class="combine-box-{$color}" {$style}>
	<div class="title">
		<span class="name">{$title}</span>
	</div>
	<div class="content">{$content}</div>
</div>
EOT;
		}
		return <<<EOT
<div class="combine-box-{$color}" {$style}>
	<div class="title">
		<div class="index">{$index}</div>
		<span class="name">{$title}</span>
	</div>
	<div class="content">{$content}</div>
</div>
EOT;
	}
	

	/**
	 * 列表
	 * 
	 * $type  1  无样式   2  前面加点  3  加背景 
	 * 
	 * @return string
	 */
	protected static function listBox($list, $type = 1)
	{
		$content = '';
		if (is_array($list)) {
			$spanList = array();
			foreach ($list as $key => $value) {
				$spanList[] = '<span>' . $value . '</span>';
			}
			$content = implode('', $spanList);
		} else {
			$content = $list;
		}
		return <<<EOT
<div class="list-box">{$content}</div>
EOT;
	}
	
	/**
	 * 图片列表
	 * 
	 * @return string
	 */
	protected static function imgListBox($list)
	{
		$itemList = array();
		foreach ($list as $row) {
			$itemList[] = <<<EOT
<div class="item">
	<img src="{$row['img']}" />
	<p class="top">{$row['top']}</p>
	<p class="bottom">{$row['bottom']}</p>
</div>
EOT;
		}
		$content = implode('', $itemList);
		return <<<EOT
<div class="img-list-box">{$content}
</div>
EOT;
	}
	
	/**
	 * 提示框
	 * 
	 * @return string
	 */
	protected static function tabBox($title, $content, $icon = 'fa-exclamation-circle', $color = 'blue')
	{
		$i = '';
		if (!empty($icon)) { // 默认要底部间距
			$i = <<<EOT
<i class="icon fa {$icon}"></i>
EOT;
		}
		return <<<EOT
<div class="tab-box-{$color}">
	<div class="head">{$i}{$title}</div>
	<div class="content">{$content}</div>
</div>
EOT;
	}
	
	/**
	 * 添加下间距
	 * 
	 * @return string
	 */
	protected static function marginBottom($size = 20)
	{
		$style = <<<EOT
style="margin-bottom: {$size}px !important;"
EOT;
		return <<<EOT
<div {$style}></div>
EOT;
	}
}