<?php
namespace service;

/**
 * 图片
 * 
 * @author 
 */
class Image extends ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例
     *
     * @var object
     */
    private static $ossConf;
    
    /**
     * 单例模式
     *
     * @return Report
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Image();
            self::$ossConf = cfg('server.oss.prod');
        }
        return self::$instance;
    }
    
    /**
     * 上传题目图片
     *
     * @return array
     */
    public function upload()
    {
    	$dir = '/data/www/mood-static/'; // 图片目录
    	$files = getFilesByDir($dir);

    	$a = array();
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossSv->init(self::$ossConf['ACCESS_KEY_ID'], self::$ossConf['ACCESS_KEY_SECRET']);

    	$urls = array();
    	if (is_iteratable($files)) foreach ($files as $file) {
    		$fileInfo = selfPathInfo($file);
    		$dirname = $fileInfo['dirname'];

    		// 二级目录
    		$subFolder = str_replace($dir, '', $dirname);
    	
    		$subFolder = trim($subFolder, DS);
    		$subFolder = str_replace(DS . DS, DS, $subFolder);
    		if (in_array($subFolder, array('临时')) || empty($fileInfo['filename'])) {
    			continue;
    		}
    
    		$fileName = $fileInfo['filename'];
    		$extension = $fileInfo['extension'];
    		$profileKey = 'resources' . DS . $subFolder . DS . "{$fileName}.{$extension}";
    
    		$ossResult = $ossSv::publicUpload(self::$ossConf['BUCKET'], $profileKey, $file);
  
    		if (empty($ossResult)) {
    			continue;
    		}
    		$url = trim(self::$ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    		$urls[] = $url;
    		
    	}
    	
    	print_r($urls);exit;
    	
    
    	return array(
    			'list' 	=> $result,
    	);
    }
    
    /**
     * 上传图片
     *
     * @return array
     */
    public function uploadPicture($file, $profile = 'questions')
    {
    	$fileInfo = selfPathInfo($file);
    	$fileName = md5(implode('', file($file)));
    	$extension = $fileInfo['extension'];
    	$subFolder = (ord(substr($fileName, 0, 1)) + ord(substr($fileName, 1, 1))) % 8;
    	$profileKey = $profile . "/{$subFolder}/{$fileName}.{$extension}"; // 上传的目录
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossSv->init(self::$ossConf['ACCESS_KEY_ID'], self::$ossConf['ACCESS_KEY_SECRET']);
    	$ossResult = $ossSv::publicUpload(self::$ossConf['BUCKET'], $profileKey, $file);
    	if (empty($ossResult)) {
    		return false;
    	}
    	// <!--[img]2d017ab099bc8fb058f038c3b9fd067d.png[/img]-->
    	$url = trim(self::$ossConf['JSOSS'], 'questions/') . DS . $profileKey;
    	$imgTag = "<!--[img]{$fileName}.{$extension}[/img]-->";
    	$result = array(
    			'url' 		=> $url,
    			'imgTag' 	=> $imgTag,
    			'name'		=> $fileInfo['basename'],
    	);
    	return $result;
    }
    
    /**
     * 识别图片
     * 
     * @return array
     */
    public static function extractImageUrls($text) {
    	// 正则表达式匹配图片URL，支持https:// 和 https:\/\/ 的形式
    	$pattern = '/https?:\\\\?\/\\\\?\/oss.*?\.(png|jpg|jpeg|gif)/i';
    	preg_match_all($pattern, $text, $matches);
    	// 清理URL中的转义字符
    	$urls = array_map(function($url) {
    		return str_replace('\/', '/', $url);
    	}, $matches[0]);
    	return $urls;
    }
    
    /**
     * 下载图片
     *
     * @return array
     */
    public function getImage($imageUrl, $pictureDir, $fileName = '')
    {
    	if (!is_dir($pictureDir)) {
    		@mkdir($pictureDir, 0777, true);
    	}
    	$imageUrlPathInfo = selfPathInfo($imageUrl);
    	
    
    	if (!empty($fileName)) {
    		$pictureFile = $pictureDir . DS . $fileName . '.' . $imageUrlPathInfo['extension']; // 图片文件
    	} else {
    		$pictureFile = $pictureDir . DS . $imageUrlPathInfo['basename']; // 图片文件
    	}

    	if (file_exists($pictureFile)) {
    		return $pictureFile;
    	}
    	$context = stream_context_create(array(
    		'http' => array(
    			'timeout' => 1 // 超时时间，单位为秒
    		)
    	));
    	$imageUrl = stripslashes($imageUrl);
    	$tries = 3;
    	do {
    		$pictureContent = @file_get_contents($imageUrl, 0, $context);
    	} while (empty($pictureContent) && --$tries > 0);
    	if (empty($pictureContent) && substr($imageUrl, 0, 4) != 'http') {
    		$imageUrl = 'https:' . $imageUrl;
    		$tries = 3;
    		do {
    			$pictureContent = @file_get_contents($imageUrl, 0, $context);
    		} while (empty($pictureContent) && --$tries > 0);
    	}
    
    	$pictureContentSize = strlen($pictureContent);
    	$needRepair = false; // 是否需要修复
    	if (file_exists($pictureFile)) {
    		$pictureSize = filesize($pictureFile);
    		if ($pictureContentSize > $pictureSize) { // 图片采集不完整
    			$needRepair = true;
    		}
    	} elseif ($pictureContentSize > 0) {
    		$needRepair = true;
    	}
    	$haveRepair = 0; // 是否有修复
    	if (!empty($needRepair)) {
    		$haveRepair = 1;
    		$tries = 3;
    		do {
    			if ($handle = fopen($pictureFile, 'w')) {
    				$ok = fwrite($handle, $pictureContent);
    			} else {
    				$ok = false;
    			}
    			fclose($handle);
    		} while ($ok === false && --$tries > 0);
    		if (!is_numeric($ok) || $ok < $pictureContentSize) { // 写入失败
    			$tries = 3;
    			do {
    				$ok = file_put_contents($pictureFile, $pictureContent);
    			} while ($ok === false && --$tries > 0);
    			if (!is_numeric($ok) || $ok < $pictureContentSize) { // 写入失败
    				$haveRepair = 2;
    				return $pictureFile;
    			}
    		}
    	}
    
    	if (!is_file($pictureFile)) {
    		return false;
    	}
    	return $pictureFile;
    }
   
    
    /**
     * 汇总图片
     *report_mbti
     *report_mbti_character
     *report_mbti_element
     *report_mbti_love
     *report_mbti_love_temperament
     *report_mbti_love_type
     *report_mbti_profession
     *report_mbti_rouge
     *report_mbti_suggest
     * @return array
     */
    public function collect()
    {
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    	// mbti相关的
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    	$reportMbtiCharacterDao = \dao\ReportMbtiCharacter::singleton();
    	$reportMbtiElementDao = \dao\ReportMbtiElement::singleton();
    	$reportMbtiLoveDao = \dao\ReportMbtiLove::singleton();
    	$reportMbtiLoveTemperamentDao = \dao\ReportMbtiLoveTemperament::singleton();
    	$reportMbtiLoveTypeDao = \dao\ReportMbtiLoveType::singleton();
    	$reportMbtiProfessionDao = \dao\ReportMbtiProfession::singleton();
    	$reportMbtiRougeDao = \dao\ReportMbtiRouge::singleton();
    	$reportMbtiSuggestDao = \dao\ReportMbtiSuggest::singleton();
    	
    	$allImage = array();
    	$coverMap = array();
    	
    	// 上线的测评
    	$onlineMap = \constant\TestPaper::onlineMap();
    	
    	// 图片保存地址
    	$imageDir = '/data/www/imgs/';
    	
    	
    	if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $testPaperEtt) {
    		if (!in_array($testPaperEtt->name, $onlineMap)) {
    			continue;
    		}
    	
    		$content = array();
    		$content[] = $testPaperEtt->coverImg;
    		$content[] = $testPaperEtt->mainImg;
    		$content[] = $testPaperEtt->content;
    		$content[] = $testPaperEtt->notice;
    		$content[] = $testPaperEtt->reportProcessImg;
    		
    		//$coverMap[$testPaperEtt->name] = $testPaperEtt->content;
    		
    		$subUrls = self::extractImageUrls($testPaperEtt->content);
    		if (empty($subUrls)) {
    			continue;
    		}
    		$subUrls = array_unique($subUrls);
    		
    		foreach ($subUrls as $k => $url) {
    			$fileName = $testPaperEtt->name . '_' . ($k + 1);
    			$this->getImage($url, $imageDir, $fileName);
    		}
    		
    		continue;
    		$daoList = array();
    		if ($testPaperEtt->name == \constant\TestPaper::NAME_MBTI) {
    			
    			$daoList = array(
	    			$reportMbtiDao, 
	    			$reportMbtiCharacterDao, 
	    			$reportMbtiElementDao, 
	    			$reportMbtiLoveDao, 
	    			$reportMbtiLoveTemperamentDao, 
	    			$reportMbtiLoveTypeDao,
	    			$reportMbtiRougeDao,
	    			$reportMbtiSuggestDao
    			);
    		}
    		$testPaperDir = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $testPaperEtt->name;
    		// 配置文件
    		$confFiles = getFilesByDir($testPaperDir, 'php');
  			if (!empty($confFiles)) {
  				foreach ($confFiles as $confFile) {
  					$content[] = file_get_contents($confFile);
  				}
  			}
    		// 图片保存地址
    		$imageDir = $testPaperDir . DIRECTORY_SEPARATOR . '图片' . DIRECTORY_SEPARATOR;
    		if (empty($daoList)) foreach ($daoList as $dao) {
    			$datas = $dao->readListByWhere();
    			foreach ($datas as $data) {
    				$fieldArr = array_keys((array)$data);
    				foreach ($fieldArr as $field) {
    					if (!isset($data->$field) || empty($data->$field) || is_numeric($data->$field)) {
    						continue;
    					}
    					$value = $data->$field;
    					if (empty($value)) {
    						continue;
    					}
    					$content[] = $value;
    				}
    			}
    		}
    		$urls = array();
    		foreach ($content as $val) {
    			$subUrls = self::extractImageUrls($val);
    			if (empty($subUrls)) {
    				continue;
    			}
    			$urls = array_merge($urls, $subUrls);
    		}
    		
    		$urls = array_unique($urls);
    		
    		$allImage = array_merge($allImage, $urls);
			echo "总共:" . count($urls) . "张图片\n";
    		$files = array();
//     		foreach ($urls as $url) {
//     			$file = $this->getImage($url, $imageDir);
//     			if (empty($file)) {
//     				continue;
//     			}
//     			$files[] = $file;
//     		}
    		echo $testPaperEtt->name . "\n";
    		echo "下载:" . count($files) . "张图片\n";
    	}
    	
    	
    	
    	$allImage = array_unique($allImage);
    	print_r(count($allImage));exit;
    }
    
    /**
     * 下载题目图片
     * $pictureDir = '/data/www/resources/question';
    	foreach ($questionConf as $key => $list) {
	    	foreach ($list as $k => $row) {
	    		$matterImg = $row['matterImg'];
	    		$matterImgInfo = selfPathInfo($matterImg);
	    		$fileName = md5($matterImg) . '.png';
	    		
	    		$imageSv->getImage($matterImg, $pictureDir, md5($matterImg));
	    		$row['matterImg'] = md5($matterImg) . '.png';
	    		if (!empty($row['selections'])) foreach ($row['selections'] as $mark => $val) {
	    			if (empty($val['img'])) {
	    				continue;
	    			}
	    			$img = $val['img'];
	    		
	    			
	    			$imageSv->getImage($img, $pictureDir, md5($img));
	    			
	    			$row['selections'][$mark]['img'] = md5($img) . '.png';
	    		}
	    		$list[$k] = $row;
	    	}
	    	$questionConf[$key] = $list;
    	}
    	var_export($questionConf);exit;
    	exit;
    	print_r($questionConf);exit;
     */
}