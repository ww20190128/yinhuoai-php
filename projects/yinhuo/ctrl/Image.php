<?php
namespace ctrl;

/**
 * 图片处理
 *
 * @author
 */
class Image extends CtrlBase
{
    /**
     * 汇总图片
     *
     * @return array
     */
    public function collect()
    {
    	$imageSv = \service\Image::singleton();
    	return $imageSv->collect();
    }
    
    /**
     * 上传图片
     *
     * @return array
     */
    public function upload()
    {
    	$imageSv = \service\Image::singleton();
    	return $imageSv->upload();
    }
}