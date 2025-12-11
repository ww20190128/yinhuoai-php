<?php
namespace service\report;

/**
 * 样式-图片
 * 
 * @author 
 */
trait HtmlImg
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;
	
	/**
	 * 单例模式
	 *
	 * @return DEPI
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new HtmlImg();
		}
		return self::$instance;
	}
	
	/**
	 * 居中大图
	 * 
	 * @return string
	 */
	protected static function imgMain($img, $center = false, $subDir = 'report')
	{
		$commonSv = \service\Common::singleton();
		$img = $commonSv::formartImgUrl($img, $subDir);
		
		if (!empty($center)) {
			return <<<EOT
<div class ='img-center'><img src="{$img}" /></div>
EOT;
		}
		return <<<EOT
<div class ='img-main'><img src="{$img}" /></div>
EOT;
	} 
	
}