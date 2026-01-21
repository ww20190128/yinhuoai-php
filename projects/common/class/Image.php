<?php
namespace service;
define('C_IMAGE_PATH', '/data/tmp/image/'); // 图片存放目录

/**
 * 图片 类的 封装
 * 
 * @author
 */
class Image
{
	private  static $image;  	    	// 图片
	private  static $imageType;     	// 图片类型
	private  static $width;         	// 图片宽度
	private  static $height;        	// 图片高度
	private  static $colorArr;      	// 颜色数组
	private  static $wordWidth  = 9;    // 字宽
	private  static $wordHeight = 13;   // 字高
	private  static $offsetX    = 7;    // x轴起点
	private  static $offsetY    = 3;    // y轴起点
	private  static $wordSpace  = 4;    // y轴起点
	
	/**
	 * 数字对应的二进制列表
	 * 
	 * @var array
	 */
	private static $binaryMap = array(
		'0' => '000111000011111110011000110110000011110000011110000011110000011110000011110000011110000011011000110011111110000111000',
		'1' => '000111000011111000011111000000011000000011000000011000000011000000011000000011000000011000000011000011111111011111111',
		'2' => '011111000111111100100000110000000111000000110000001100000011000000110000001100000011000000110000000011111110111111110',
		'3' => '011111000111111110100000110000000110000001100011111000011111100000001110000000111000000110100001110111111100011111000',
		'4' => '000001100000011100000011100000111100001101100001101100011001100011001100111111111111111111000001100000001100000001100',
		'5' => '111111110111111110110000000110000000110000000111110000111111100000001110000000111000000110100001110111111100011111000',
		'6' => '000111100001111110011000010011000000110000000110111100111111110111000111110000011110000011011000111011111110000111100',
		'7' => '011111111011111111000000011000000010000000110000001100000001000000011000000010000000110000000110000001100000001100000',
		'8' => '001111100011111110011000110011000110011101110001111100001111100011101110110000011110000011111000111011111110001111100',
		'9' => '001111000011111110111000111110000011110000011111000111011111111001111011000000011000000110010000110011111100001111000',
	);

    /**
     * 构造函数
     *
     * @param  string $imageName  图片名
     *
     * @param null $wordWidth
     * @param null $wordHeight
     * @param null $offsetX
     * @param null $offsetY
     * @param null $wordSpace
     * @return \service\Image
     */
    public function __construct($imageName, $wordWidth  = null, $wordHeight = null, 
    	$offsetX = null, $offsetY  = null, $wordSpace  = null)
    {
    	self::$image = C_IMAGE_PATH . $imageName;
		$size = getimagesize(self::$image);
    	if ($size) {				
    		self::$width = array_shift($size);
    	 	self::$height = array_shift($size);
			self::$imageType = $size['mime'];
		    $wordWidth  and self::$wordWidth  = $wordWidth;
		    $wordHeight and self::$wordHeight = $wordHeight;
		    $offsetX    and self::$offsetX    = $offsetX;
		    $offsetY    and self::$offsetY    = $offsetY;
		    $wordSpace  and self::$wordSpace  = $wordSpace;	    
    	} else {
    		die('image not found');
    	}
    	self::getColorData();
    }
    
    /**
     * 提取内容
     * 
     * @param	int		$num 	需要提取的文字个数
     * 
     * @return array 
     */
	public function extract($num = 4)
	{
		if (empty(self::$colorArr) || $num <= 0) return null;
		$data =  array_fill(0, $num, '');
		for ($i = 0; $i < $num; $i++) {
			$x = ($i * (self::$wordWidth + self::$wordSpace)) + self::$offsetX;
			$y = self::$offsetY;
			for ($height = $y; $height < (self::$offsetY + self::$wordWidth); $height++) {
				for ($width = $x; $width < ($x + self::$wordWidth); $width++) {
					$data[$i] .= self::$colorArr[$height][$width];
				}
			}
		}
		// 进行关键字匹配
		$result = array();
		foreach ($data as $numKey => $numString) {
			$max = 0.0;
			$num = 0;
			foreach (self::$binaryMap as $key => $value) {
				similar_text($value, $numString, $percent);
				if (intval($percent) > $max) {
					$max = $percent;
					$num = $key;
					echo $percent ."\n";
					if (intval($percent) > 95) {
						break;
					}
				}
			}
			$result[] = $num;
		}
		return $result;
	}
	
    /**
	 * 图片识别
     * 
     * @return void
     */
    public function distinguish()
    {	
    	$colorData = self::getColorData();
    	$horizontalData = self::imageHorizontalData($colorData); 
    	$horDraw = self::drawWH($horizontalData);
    	$verticallyData = self::imageVerticallyData($horizontalData);
    	$number = $this->findNumber($horizontalData);
    	return $number;
    }
    
	/**
 	 * 颜色分离转换
 	 * 
 	 * @return array
 	 */
    private static function getColorData()
    {   
    	$res = null;
    	switch (self::$imageType) {
    		case 'image/jpeg':
    			$res = imagecreatefromjpeg(self::$image);	
    			break;
    		case 'image/gif':
    			$res = imagecreatefromgif(self::$image);	
    			break;
    		case 'image/png':
    			$res = imagecreatefrompng(self::$image);	
    			break;
    		case 'image/wbmp':
    			$res = imagecreatefromwbmp(self::$image);	
    			break;
    		case 'image/xbm':
    			$res = imagecreatefromxbm(self::$image);
    		case 'image/xpm':
    			$res = imagecreatefromxpm(self::$image);
    		default:
    			die('image type error');								
    	}
    	self::$colorArr = array();
    	for ($height = 0; $height < self::$height; $height++) {
    		for ($width = 0; $width < self::$width; $width++) {
    			$rgbarray = imagecolorsforindex($res, imagecolorat($res, $width, $height));
    			self::$colorArr[$height][$width] = ($rgbarray['red'] < 125 
    				|| $rgbarray['green']<125 || $rgbarray['blue'] < 125) ? 1 : 0;
    		}
    	}	
    	return self::$colorArr;
    }
    
	/**
 	 * 颜色分离后的数据横向整理
 	 * 
 	 * @param array $colorData 颜色数据
 	 * 
 	 * @return array
 	 */
	private static function imageHorizontalData($colorData = null)
	{
		$colorData = $colorData ? $colorData : self::$colorArr;
		$index = 0;
		$horizontalData = array();
		for ($height = 0; $height < self::$height; $height++) {
        	if (in_array('1', $colorData[$height])) {
            	$index++;
            	for	($width = 0; $width < self::$width; $width++) {
                	$horizontalData[$index][$width] = $colorData[$height][$width] ? 1 : 0;
            	}           
       		}
    	}
    	return $horizontalData;
	}
		
	/**
	 * 整理纵向数据...
	 *
	 *  @param array $colorData 颜色数据
	 *  
	 *  @return array
	 */
	public function imageVerticallyData($colorData) {
	
	}
	
	/**
 	 * 分离显示
 	 *
 	 * @param array $colorData 颜色数据
 	 * 
 	 * @return string
 	 */
	private static function drawWH($colorData) {
		$c = null;
		if (is_array($colorData)) {
        	foreach ($colorData as $key => $val) {
            	foreach ($val as $k => $v) {
                	if (!$v) {
                    	$c .= "<font color='#FFFFFF'>".$v."</font>";
                	}else{
                  		$c .= $v;
                	}
            	}
            	$c .= "<br/>";
        	}
    	}
    	return $c;
	}

    /**
     * 显示数数字
     *
     * @param $data
     * @return unknown
     */
	public function findNumber($data)
    {
		$number = null;
	    $index = 0;
	    $numberArr = array();
	    $numberArr[$index] =  null;
	    foreach ($data as $key => $val) {
	        if (in_array(1, $val)) {
	            foreach ($val as $k => $v) {
	               $numberArr[$index] .= $v;
	            }
	        }        
	        if (!in_array(1, $val)) {
	            $index++;
	        }
	    }
	    foreach ($numberArr as $value) {
	        $number .= $this->initData($value);
	    }
	    return $number;
	}
	
    /**
     * 初始数据...
     *
     * @param string $numStr 数字二进制串
     * 
     * @return int
     */
    public function initData($numStr)
    {
        $result = null;
        foreach (self::$binaryMap as $key => $val) {
           similar_text($numStr, $val, $pre);
           if ($pre > 95) {  //相似度95%以上
               $result = $key;
               break;
           }
        }
        return $result;
    }

}