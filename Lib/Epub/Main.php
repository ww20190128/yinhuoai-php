<?php
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR); 	// 路径分割符
}

/**
 * 生成一本Epub文件(当前的)
 * 
 * @author  wangwei
 */
class Epub
{
	private $tmpDir 	= '';			// 临时文件目录
	private $oebpsDir 	= '';			// OEBPS 文件夹目录
	private $imagesDir 	= '';			// 图片存放目录
	private $textDir 	= '';			// 文本存放目录
	private $cssDir 	= '';			// css样式存放目录
	private $jsDir 		= '';			// Miscjs文件存放目录
	private $info 		= array();		// epub信息
	private $titles 	= array();		// 目录
	private $images     = array();		// 图片

    /**
     * 构造函数
     * 
     * @param 	string 		$name 			书名
     * @param 	string 		$author 		作者
     * @param 	int 		$publishTime 	出版时间
     * 
     * @return 
     */
    public function __construct($name, $author = '', $publishTime = '')
    {
    	$this->tmpDir = __DIR__ . DS . 'cache' .  DS . $name . '_' . getmypid() . DS;
    	$this->oebpsDir 		= $this->tmpDir . 'OEBPS' . DS; 	// OEBPS 文件夹目录
    	$this->imagesDir 		= $this->oebpsDir . 'Images' . DS;	// 图片存放目录
    	$this->textDir 			= $this->oebpsDir . 'Text' . DS;	// 文本存放目录
    	$this->cssDir 			= $this->oebpsDir . 'Styles' . DS;	// css样式存放目录
    	$this->jsDir 			= $this->oebpsDir . 'Misc' . DS;	// Miscjs文件存放目录
    	$this->info = array(
    		'book' 		=> $name, 		 	 											// 书名
    		'author' 	=> empty($author) 		? '佚名' : $author, 		 				// 作者
    		'publish' 	=> empty($publishTime) 	? '' : date('Y-m-d', $publishTime),	 	// 发布时间
    	);
    	$this->makeDir($this->tmpDir);
    	$text = <<<EOT
application/epub+zip
EOT;
    	file_put_contents($this->tmpDir . 'mimetype', $text);
    	// 构建META-INF文件夹下的内容
    	$metaInfDir = $this->tmpDir . 'META-INF' . DS;
    	$this->makeDir($metaInfDir);
    	$text = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
   </rootfiles>
</container>
EOT;
    	file_put_contents($metaInfDir . 'container.xml', $text);
    	// 构建OEBPS文件夹下的内容
    	$this->makeDir($this->oebpsDir);
    	$this->makeDir($this->imagesDir);
    	$this->makeDir($this->textDir);
    	$this->makeDir($this->cssDir);
    	$this->makeDir($this->jsDir);
    	$this->css();			// 添加css样式
    	$this->javascript();	// 添加js脚本
    	return ;
    }
    
    /**
     * 是否可遍历
     *
     * @param 	mix  $mixedValue   参数
     *
     * @return boolen
     */
    public function isIteratable($mixedValue)
    {
    	return (is_array($mixedValue) || is_object($mixedValue)) && $mixedValue;
    }
    
    
    /**
     * 录入数据
     * 
     * @param  	array 	$data
     * 
	 * @return bool
     */
    public function loadData($data)
    {
    	$dataArr = array();
    	if ($this->isIteratable($data)) foreach ($data as $level1Index => $level1Row) {
			$sublevels1 = empty($level1Row['sublevels']) ? array() : $level1Row['sublevels']; // 一集子类
			$one1 = array(
				'level' 	=> 1, 					// 一集
				'title' 	=> $level1Row['title'],	// 一集目录
			);
			if (empty($sublevels1)) {
				$one1['content'] = empty($level1Row['content']) ? '' : $level1Row['content'];
				$dataArr[] = $one1;
				continue;
			}
			if ($this->isIteratable($sublevels1)) { // 遍历一集子类
				$one1['content'] = empty(reset($sublevels1)['content']) ? '' : reset($sublevels1)['content'];
				$dataArr[] = $one1; // 保存一集目录
				foreach ($sublevels1 as $level2Index => $level2Row) {
					$sublevels2 = empty($level2Row['sublevels']) ? array() : $level2Row['sublevels']; // 二集子类
					$one2 = array(
						'level' 	=> 2, 					// 二集
						'title' 	=> $level2Row['title'],	// 二集目录
					);
					if (empty($sublevels2)) {
						$one2['content'] = empty($level2Row['content']) ? '' : $level2Row['content'];
						$dataArr[] = $one2;
						continue;
					}
					if ($this->isIteratable($sublevels2)) { // 遍历二集子类
						$one2['content'] = empty(reset($sublevels2)['content']) ? '' : reset($sublevels2)['content'];
						$dataArr[] = $one2; // 保存二集目录
						foreach ($sublevels2 as $level3Index => $level3Row) {
							$sublevels3 = empty($level3Row['sublevels']) ? array() : $level3Row['sublevels']; // 三集子类
							$one3 = array(
								'level' 	=> 3, 					// 三集
								'title' 	=> $level3Row['title'],	// 三集目录
							);
							if (empty($sublevels3)) {
								$one3['content'] = empty($level3Row['content']) ? '' : $level3Row['content'];
								$dataArr[] = $one3;
								continue;
							}
							if ($this->isIteratable($sublevels3)) { // 遍历三集子类
								$one3['content'] = empty(reset($sublevels3)['content']) ? '' : reset($sublevels3)['content'];
								$dataArr[] = $one3; // 保存三集目录
								foreach ($sublevels3 as $level4Index => $level4Row) {
									$sublevels4 = empty($level4Row['sublevels']) ? array() : $level4Row['sublevels']; // 四集子类
									$one4 = array(
										'level' 	=> 4, 					// 四集
										'title' 	=> $level4Row['title'],	// 四集目录
									);
									if (empty($sublevels4)) {
										$one4['content'] = empty($level4Row['content']) ? '' : $level4Row['content'];
										$dataArr[] = $one4;
										continue;
									}
									if ($this->isIteratable($sublevels4)) { // 遍历四集子类
										$one4['content'] = empty(reset($sublevels4)['content']) ? '' : reset($sublevels4)['content'];
										$dataArr[] = $one4; // 保存四集目录
										foreach ($sublevels4 as $level5Index => $level5Row) {
											$sublevels5 = empty($level5Row['sublevels']) ? array() : $level5Row['sublevels']; // 五集子类
											$one5 = array(
												'level' 	=> 5, 					// 五集
												'title' 	=> $level5Row['title'],	// 五集目录
											);
											if (empty($sublevels5)) {
												$one5['content'] = empty($sublevels5['content']) ? '' : $sublevels5['content'];
												$dataArr[] = $one5;
												continue;
											} else {
												return false;
											}
										}
									}
								}
							}
						}
					}
				}
			}	
		}
		if (empty($dataArr)) {
			return false;
		}
		$this->data = $dataArr;
		return true;
    }
    
    /**
     * 添加内容
     * 
     * @param 	int  		$index   	 索引
     * @param 	string  	$title   	 标题
     * @param 	int  		$level  	 级别
     * @param 	string  	$article   	 文章
     *
     * @return array
     */
    private function add($index, $title, $level = 0, $article = '')
    {
    	if ($index < 0 || empty($title)) {
    		return false;
    	}
    	$this->xthml($index, $title, $article); // 生成单篇文章
    	$this->titles[] = array('title' => $title, 'id' => $index, 'level' => $level);
    	return true;
    }
    
    /**
     * 生成单篇文章
     * 
     * @param 	int  		$index   	 索引作文章链接名称
	 * @param 	string  	$title   	 文章标题
	 * @param 	string  	$article   	 文章内容
	 * 
     * @return int
     */
    private function xthml($index, $title, $article)
    {
    	$contentHtml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"
		\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">
	
		<html xmlns=\"http://www.w3.org/1999/xhtml\">
		<head>
		<link href=\"../Styles/Style_0001.css\" rel=\"stylesheet\" type=\"text/css\" />
		<title></title>
		</head>
		<body>
		<h3 align='center'>{$title}</h3>
		<div>
		{$article}
		</div>
		</body>
		</html>";
    	return file_put_contents($this->textDir . $index . '.xhtml', $contentHtml);
    }

    /**
     * 设置一个默认的css
     *
     * @return bool
     */
    private function css()
    {
    	$css = "@CHARSET \"UTF-8\";";
//     	$css .= 'body{\font-family: \"微软雅黑\", Arial, Verdana, Sans-serif;} h1{} h2{}  h3{} h4{} h5{} h6{} div{text-indent: 2em;line-height: 2em;} img{max-width:100%;margin: 0 auto;display:block;}';
    	$css .= 'body{\font-family: "微软雅黑", Arial, Verdana, Sans-serif;} h1{} h2{}  h3{} h4{} h5{} h6{}';
    	if (!is_dir($this->cssDir)) {
    		@mkdir($this->cssDir);
    	}
    	return file_put_contents($this->cssDir . 'Style_0001.css', $css);
    }
    
    /**
     * 设置一个默认的
     *
     * @return bool
     */
    private function javascript()
    {
    	$javascript = '';
    	if (!is_dir($this->jsDir)) {
    		@mkdir($this->jsDir);
    	}
    	return file_put_contents($this->jsDir . 'Misc_js.js', $javascript);
    }
    
    /**
     * 析构函数
     *
     * @return void
     */
    public function __destruct()
    {
		$this->deleteDirAndFile($this->tmpDir, true);
    }
    
    /**
     * 删除目录及目录下所有文件或删除指定文件
     *
     * @param 	string 		$path   		待删除目录路径
     * @param 	bool 		$deleteDir 		是否删除目录, true删除目录, false则只删除文件保留目录（包含子目录）
     *
     * @return bool 返回删除状态
     */
    private function deleteDirAndFile($path, $deleteDir = false)
    {
    	if (!is_dir($path)) {
    		return false;
    	}
    	$handle = opendir($path);
    	if ($handle) {
    		while (false !== ($item = readdir($handle))) {
    			if (!in_array($item, array('.', '..'))) {
    				is_dir($path . DS . $item) ? deleteDirAndFile($path . DS . $item, $deleteDir) : @unlink($path . DS . $item);
    			}
    		}
    		closedir($handle);
    		if ($deleteDir) { // 删除文件夹
    			return @rmdir($path);
    		}
    	} else {
    		if (file_exists($path)) {
    			return @unlink($path);
    		} else {
    			return false;
    		}
    	}
    	return true;
    }
    
    /**
     * 中文名称
     *
     * @param  string  		$fileName   	文件名称
     *
     * @return string
     */
    private static function md5FileName($fileName)
    {
    	if (!preg_match('/[\x4e00-\x9fa5]/u', $fileName)) {
    		return $fileName;
    	}
    	return md5($fileName) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
    }
    
    /**
     * 创建目录
     *
     * @param 	string 	$dir  	目录名称
     *
     * @return bool
     */
    private function makeDir($dir)
    {
    	if (!is_dir($dir)) {
    		@mkdir($dir, 0777, true);
    		@chmod($dir, 0777);
    		@chown($dir, 'nobody');
    		@chgrp($dir, 'nobody');
    	}
    	return true;
    }
    
    /**
     * 全书页面引用的图片
     *
     * @param array   $images   图片列表
     *
     * @return bool
     */
    public function loadImages($images, $bool = 0)
    {
    	if (empty($images)) {
    		return true;
    	}
    	$this->images = array_keys($images);
    	if (!is_dir($this->imagesDir)) {
    		$this->makeDir($this->imagesDir);
    	}
    	foreach ($images as $pictureFileName => $pictureContent) {
    		if (empty($pictureFileName)) {
    			continue;
    		}
    		$fileName = $this->imagesDir . $pictureFileName;
    		//if (strpos(PHP_OS, "Linux") !== false) {
    		if ($bool) {
    			file_put_contents($fileName, $pictureContent);
    		} else {
    			if (file_put_contents($fileName, hex2bin($pictureContent)) == 0) {
    				echo $fileName . PHP_EOL;
    				return false;
    			}
    		}
    	}
    	return true;
    }
    
    /**
     * 带超时控制的从远程获取内容
     *
     * @param   string          $url        地址
     * @param   null|string     $data       post信息
     * @param   int             $timeout    超时时间
     * @param   array           $headers    头内容
     * @param   int             $tries      尝试次数
     * @param   int             $errno      错误码
     *
     * @return string
     */
    private function httpGetContents($url, $data = null, $timeout = 40, $headers = array(), $tries = 1, &$errno = 0)
    {
    	if (is_array($data)) {
    		$data = http_build_query($data);
    	}
    	do {
    		if (extension_loaded('curl')) {
    			$ch = curl_init($url);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    			if (isset($data)) {
    				curl_setopt($ch, CURLOPT_POST, 1);
    				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    				if (isset($data[1024])) {
    					$headers[] = 'Expect:';
    				}
    			}
    			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    			$body = curl_exec($ch);
    			if ($body === false) {
    				$errno = curl_errno($ch);
    			}
    			curl_close($ch);
    		} else {
    			$options = array(
    					'http' => array(
    							'timeout' => $timeout,
    					)
    			);
    			if (isset($data)) {
    				$options['http']['method'] = 'POST';
    				$options['http']['header'] = 'Content-type: application/x-www-form-urlencoded';
    				$options['http']['content'] = $data;
    			}
    			$context = stream_context_create($options);
    			$body = file_get_contents($url, FILE_BINARY, $context);
    		}
    	} while ($body === false && --$tries > 0);
    	return $body;
    }
    
    /**
     * 输出epub
     *
     * @param 	string   $fileName 		指定输出epub的名称
     *
     * @return bool
     */
    public function exec($fileName, $zip = false)
    {
    	if (empty($this->data)) {
    		return false;
    	}
    	foreach ($this->data as $index => $row) {
    		$content = empty($row['content']) ? '' : $row['content'];
			$this->add($index, $row['title'], $row['level'], $content); // 加入标题内容
    	}
    	// content.opf
    	$dOMDocument = new DOMDocument('1.0', 'utf-8');
    	$dOMDocument->formatOutput = true;
    	$dOMElement = $dOMDocument->createElement('package');
    	$dOMAttr = $dOMDocument->createAttribute('unique-identifier');
    	$dOMAttr->value = 'MagazineId';
    	$dOMElement->appendChild($dOMAttr);
    	$dOMAttr = $dOMDocument->createAttribute('version');
    	
    	$dOMAttr->value = '2.0';
    	$dOMElement->appendChild($dOMAttr);
    	
    	
    	$dOMAttr = $dOMDocument->createAttribute('xmlns');
    	$dOMAttr->value = 'http://www.idpf.org/2007/opf';
    	$dOMElement->appendChild($dOMAttr);
    	
    	$metaData = $dOMDocument->createElement('metadata');
    	$dOMAttr = $dOMDocument->createAttribute('xmlns:dc');
    	$dOMAttr->value = "http://purl.org/dc/elements/1.1/";
    	$metaData->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('xmlns:opf');
    	$dOMAttr->value = "http://www.idpf.org/2007/opf";
    	$metaData->appendChild($dOMAttr);
    	
    	$dc = $dOMDocument->createElement('dc:identifier', 'urn:uuid:' . basename($fileName));
    	$dOMAttr = $dOMDocument->createAttribute('id');
    	$dOMAttr->value = "Bookid";
    	$dc->appendChild($dOMAttr);
    	$metaData->appendChild($dc);

    	//caijie add start
    	$dc = $dOMDocument->createElement('dc:title', $this->info['book']);
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('dc:creator', $this->info['author']);
    	$dOMAttr = $dOMDocument->createAttribute('opf:role');
    	$dOMAttr->value = "author";
    	$dc->appendChild($dOMAttr);
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('dc:language', "zh");
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('dc:title', $this->info['book']);
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('dc:language', "zh");
    	$metaData->appendChild($dc);
    	
    	// 出版日期
    	$dc = $dOMDocument->createElement('dc:date', ''); // $this->info['publish']
    	$dOMAttr = $dOMDocument->createAttribute('opf:event');
    	$dOMAttr->value = "modification";
    	$dc->appendChild($dOMAttr);
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('meta');
    	$dOMAttr = $dOMDocument->createAttribute('content');
    	$dOMAttr->value = 'Wand';
    	$dc->appendChild($dOMAttr);
    	$dOMAttr = $dOMDocument->createAttribute('name');
    	$dOMAttr->value = 'Product name';
    	$dc->appendChild($dOMAttr);
    	$metaData->appendChild($dc);
    	
    	$dc = $dOMDocument->createElement('meta');
    	$dOMAttr = $dOMDocument->createAttribute('content');
    	$dOMAttr->value = '1.1.0r';
    	$dc->appendChild($dOMAttr);
    	$dOMAttr = $dOMDocument->createAttribute('name');
    	$dOMAttr->value = 'Product version';
    	$dc->appendChild($dOMAttr);
    	$metaData->appendChild($dc);
    	
    	// caijie add end
    	$dOMElement->appendChild($metaData);
    	
    	$manifest = $dOMDocument->createElement('manifest');
    	$item = $dOMDocument->createElement('item');
    	$dOMAttr = $dOMDocument->createAttribute('href');
    	$dOMAttr->value = 'toc.ncx';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('id');
    	$dOMAttr->value = 'ncx';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('media-type');
    	$dOMAttr->value = 'application/x-dtbncx+xml';
    	$item->appendChild($dOMAttr);
    	$manifest->appendChild($item);
    	
    	// caijie 引入样式和脚本 start
    	$item = $dOMDocument->createElement('item');
    	$dOMAttr = $dOMDocument->createAttribute('href');
    	$dOMAttr->value = 'Styles/Style_0001.css';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('id');
    	$dOMAttr->value = 'Styles_Style_0001.css';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('media-type');
    	$dOMAttr->value = 'text/css';
    	$item->appendChild($dOMAttr);
    	$manifest->appendChild($item);
    	
    	$item = $dOMDocument->createElement('item');
    	$dOMAttr = $dOMDocument->createAttribute('href');
    	$dOMAttr->value = 'Misc/Misc_js.js';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('id');
    	$dOMAttr->value = 'Misc_Misc_js.js';
    	$item->appendChild($dOMAttr);
    	
    	$dOMAttr = $dOMDocument->createAttribute('media-type');
    	$dOMAttr->value = 'text/plain';
    	$item->appendChild($dOMAttr);
    	$manifest->appendChild($item);
    	// caijie 引入样式和脚本 end
    	
    	$spine = $dOMDocument->createElement('spine');
    	$dOMAttr = $dOMDocument->createAttribute('toc');
    	$dOMAttr->value = 'ncx';
    	$spine->appendChild($dOMAttr);
    	
    	// toc.ncx
    	$toc = new DOMDocument('1.0', 'utf-8');
    	$toc->formatOutput = true;
    	$ncx = $toc->createElement('ncx');
    	$dOMAttr = $toc->createAttribute('xmlns');
    	$dOMAttr->value = "http://www.daisy.org/z3986/2005/ncx/";
    	$ncx->appendChild($dOMAttr);
    	
    	$head = $toc->createElement('head');
    	$meta = $toc->createElement('meta');
    	$dOMAttr = $toc->createAttribute('name');
    	$dOMAttr->value = "dtb:depth";
    	$meta->appendChild($dOMAttr);
    	$dOMAttr = $toc->createAttribute('content');
    	$dOMAttr->value = "0";
    	$meta->appendChild($dOMAttr);
    	$head->appendChild($meta);
    	
    	$meta = $toc->createElement('meta');
    	$dOMAttr = $toc->createAttribute('name');
    	$dOMAttr->value = "dtb:totalPageCount";
    	$meta->appendChild($dOMAttr);
    	$dOMAttr = $toc->createAttribute('content');
    	$dOMAttr->value = "0";
    	$meta->appendChild($dOMAttr);
    	$head->appendChild($meta);
    	
    	$meta = $toc->createElement('meta');
    	$dOMAttr = $toc->createAttribute('name');
    	$dOMAttr->value = "dtb:maxPageNumber";
    	$meta->appendChild($dOMAttr);
    	$dOMAttr = $toc->createAttribute('content');
    	$dOMAttr->value = "0";
    	$meta->appendChild($dOMAttr);
    	$head->appendChild($meta);
    	
    	$dOMAttr = $toc->createAttribute('version');
    	$dOMAttr->value = "2005-1";
    	$ncx->appendChild($dOMAttr);
    	
    	$docTitle = $toc->createElement('docTitle');
    	$text = $toc->createElement('text', $this->info['author']);//caijie edit
    	$docTitle->appendChild($text);
    	$ncx->appendChild($docTitle);
    	
    	$navMap = $toc->createElement('navMap');

    	// 图片
    	if (!empty($this->images)) foreach ($this->images as $value) {
    		$item = $dOMDocument->createElement('item');
    		$dOMAttr = $dOMDocument->createAttribute('href');
    		$value = self::md5FileName($value);
    		$dOMAttr->value = "Images/{$value}";
    		$item->appendChild($dOMAttr);
    		$dOMAttr = $dOMDocument->createAttribute('id');
    		$dOMAttr->value = "{$value}";
    		$item->appendChild($dOMAttr);
    		 
    		$dOMAttr = $dOMDocument->createAttribute('media-type');
    		$dOMAttr->value = 'image/jpeg';
    		if (stripos($value, '.png')) {
    			$dOMAttr->value = 'image/png';
    		}
    		if (stripos($value, '.gif')) {
    			$dOMAttr->value = 'image/gif';
    		}
    		$item->appendChild($dOMAttr);
    		$manifest->appendChild($item);
    	}
   		$this->forCatalog($dOMDocument, $manifest, $spine, $toc, $navMap);
    	$dOMElement->appendChild($manifest);
    	$dOMElement->appendChild($spine);
    	$guide = $dOMDocument->createElement('guide', ' ');
    	$dOMElement->appendChild($guide);
    	$dOMDocument->appendChild($dOMElement);
    	$saveXML = $dOMDocument->saveXML();
   
    	file_put_contents($this->oebpsDir . 'content.opf', $saveXML);
    	$ncx->appendChild($navMap);
    	$toc->appendChild($ncx);
    	$saveXML = $toc->saveXML();
    	file_put_contents($this->oebpsDir . 'toc.ncx', $saveXML);
    	// 创建iisue目录
    	if (empty($zip)) {
    		return true;
    	}
    	// 压缩epub
    	$zip = new ZipArchive();
    	$zipResult = $zip->open($fileName, ZipArchive::CREATE);

    	if ($zipResult === true) {
    		$this->zipAddFile($this->tmpDir, $zip);
    	} else {
    		echo "zip open is err: {$fileName}" . PHP_EOL;
    		return;
    	}
    	if (!empty($zip)) {
    		@$zip->close();
    	}
    	return true;
    }
   
    /**
     * 构造目录
     * 
     * @param unknown $dOMDocument
     * @param unknown $manifest
     * @param unknown $spine
     * @param unknown $toc
     * @param unknown $navMap
     * 
     * @return bool
     */
	private function forCatalog($dOMDocument, $manifest, $spine, $toc, $navMap)
    {
    	// 将每一级最新保存在这里
    	$parent = array();
    	$spineArr = array();
    	if (!empty($this->titles)) foreach ($this->titles as $key => $value) {
    		$num = $key + 1;
    		// content
    		$item = $dOMDocument->createElement('item');
    		$dOMAttr = $dOMDocument->createAttribute('href');
    		$dOMAttr->value = "Text/{$value['id']}.xhtml";
    		$item->appendChild($dOMAttr);
    		
    		$dOMAttr = $dOMDocument->createAttribute('id');
    		$dOMAttr->value = "{$value['id']}.xhtml";
    		$item->appendChild($dOMAttr);

    		$dOMAttr = $dOMDocument->createAttribute('media-type');
    		$dOMAttr->value = 'application/xhtml+xml';
    		$item->appendChild($dOMAttr);
    		$manifest->appendChild($item);
    		// caijie edit 目录链接不正确
    		if (!key_exists($value['id'], $spineArr)) {

    			$itemref = $dOMDocument->createElement('itemref');
    			$dOMAttr = $dOMDocument->createAttribute('idref');
    			$dOMAttr->value = "{$value['id']}.xhtml";
    			$itemref->appendChild($dOMAttr);
    			$spine->appendChild($itemref);
    			$spineArr[] = $value['id'];
    		}
    		// toc
    		$navPoint = $toc->createElement('navPoint');
    		$dOMAttr = $toc->createAttribute('id');
    		$dOMAttr->value = "navPoint-{$num}";
    		$navPoint->appendChild($dOMAttr);
    	
    		$dOMAttr = $toc->createAttribute('playorder');
    		$dOMAttr->value = $num;
    		$navPoint->appendChild($dOMAttr);
    	
    		$navLabel = $toc->createElement('navLabel');
    		$text = $toc->createElement('text', htmlspecialchars($value['title']));
    		$navLabel->appendChild($text);
    		$navPoint->appendChild($navLabel);
    	
    		$content = $toc->createElement('content');
    		$dOMAttr = $toc->createAttribute('src');
    		$dOMAttr->value = "Text/{$value['id']}.xhtml";
    		$content->appendChild($dOMAttr);
    		$navPoint->appendChild($content);
    		if ($value['level'] > 0 && !empty($parent[$value['level'] - 1])) {
    			$parent[$value['level'] - 1]->appendChild($navPoint);
    		} else {
    			$navMap->appendChild($navPoint);
    		}
    		$parent[$value['level']] = $navPoint;
    	}
    	return true;
    }

    /**
     * 压缩文件夹
     * 
     * @param 	sting 	$path  	文件夹
     * @param 	zip  	$zip  	zip
     * 
     * @return bool
     */
    private function zipAddFile($path, $zip)
    {
        if (empty($path)) {
            return true;
        }
        $handle = opendir($path);
        while (($fileName = readdir($handle)) !== false) {
            if (empty($fileName)) {
                return;
            }
            if (in_array($fileName, array('.', '..'))) {
                continue;
            }
            $tmpFile = $path . DS . $fileName;
            if (is_dir($tmpFile)) {
                $this->zipAddFile($tmpFile, $zip);
            } else {
                $zip->addFile($tmpFile, str_ireplace($this->tmpDir, '', $tmpFile));
            }
        }
        return true;
    }

}