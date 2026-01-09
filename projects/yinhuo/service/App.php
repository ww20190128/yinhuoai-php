<?php
namespace service;

/**
 * 首页 逻辑类
 * 
 * @author 
 */
class App extends ServiceBase
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
     * @return App
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }
        return self::$instance;
    }
    
    /**
     * 
     *
     * @return array
     */
    public function uploadMusic()
    {
    	$aliEditingSv = \service\AliEditing::singleton();
    	$ossSv = \service\reuse\OSS::singleton();
    	$ossConf = cfg('server.oss.zhile'); // 阿里云配置
    	$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    	$musicDao = \dao\Music::singleton();
    	$musicEttList = $musicDao->readListByWhere("url = ''");
    	$now = $this->frame->now;
    	foreach ($musicEttList as $musicEtt) {
    		$publishUrl = $musicEtt->publishUrl;
    		$publishUrlContent = @file_get_contents($publishUrl);
    		$publishUrlSize = strlen($publishUrlContent);
    		if ($publishUrlSize > 0) {
    			$fileName = md5($publishUrl);
    			$profileKey = "resources/music/{$fileName}.mp3"; // 上传的目录
    			$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    			$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, $publishUrlContent);
    			if (empty($ossResult)) {
    				continue;
    			}
    			$url = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    			$musicEtt->set('url', $url);
    			$musicDao->update($musicEtt);
    			echo $url . "\n";
    		}
    	}
    	exit;
	
    }
    
    /**
     * 同步音乐数据
     *
     * @return array
     */
    public function sysnMusic($authorization)
    {
    	//$this->uploadMusic();exit;
    	$musicDao = \dao\Music::singleton();
		$musicClassifyDao = \dao\MusicClassify::singleton();
    	$url = "https://api.pyp.canzan.com/company/material/hot_music_cate";
    	$response = httpGetContents($url, null, 5, ["authorization: {$authorization}"]);
    	$response = empty($response) ? array() : json_decode($response, true);
    	$response = empty($response) ? array() : $response['data'];
    	$now = $this->frame->now;
    	foreach ($response as $row) {
    		$musicClassifyEtt = $musicClassifyDao->readByPrimary($row['id']);
    		if (empty($musicClassifyEtt)) {
    			$musicClassifyEtt = $musicClassifyDao->getNewEntity();
    			$musicClassifyEtt->id = $row['id'];
    			$musicClassifyEtt->name = $row['title'];
    			$musicClassifyDao->create($musicClassifyEtt);
    		}
    		$classifyId = $row['id'];
    		$listUrl = "https://api.pyp.canzan.com/company/material/hot_music_list?cate={$row['id']}&limit=1000";
    		$response = httpGetContents($listUrl, null, 5, ["authorization: {$authorization}"]);
    		$response = empty($response) ? array() : json_decode($response, true);
    		$list = empty($response['data']['list']) ? array() : $response['data']['list'];
    		$ids = array();
    		foreach ($list as $val) {
    			$ids[] = $val['id'];
    		}
    		$haveMusicEttList = $musicDao->readListByPrimary($ids);
    		$haveMusicEttList = $musicDao->refactorListByKey($haveMusicEttList);
    		foreach ($list as $val) {
    			if (!empty($haveMusicEttList[$val['id']])) {
    				continue;
    			}
    			$musicEtt = $musicDao->getNewEntity();
    			$musicEtt->id = $val['id'];
    			$musicEtt->name = $val['name'];
    			$musicEtt->duration = $val['duration'];
    			$musicEtt->publishUrl = $val['publish_url'];
    			$musicEtt->playUrl = $val['play_url'];
    			$musicEtt->classifyId = $classifyId;
    			$musicEtt->createTime = $now;
    			$musicEtt->updateTime = $now;
    			$musicDao->create($musicEtt);
    		}
    	}
    	exit;
    }
    
    /**
     * 获取热门音乐分类
     *
     * @return array
     */
    public function getMusicClassifys()
    {
		$musicClassifyDao = \dao\MusicClassify::singleton();
		$musicClassifyEttList = $musicClassifyDao->readListByWhere();
		$list = array();
		if (!empty($musicClassifyEttList)) foreach ($musicClassifyEttList as $musicClassifyEtt) {
			$list[] = array(
				'id' 	=> intval($musicClassifyEtt->id),
				'name' 	=> $musicClassifyEtt->name,
			);
		}
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     * 
     * @return array
     */
    public function getActorClassifys()
    {
    	$actorArr = cfg('actorAil');
    	$map = array();
    	if (!empty($actorArr)) foreach ($actorArr as $key => $value) {
    		$listArr = explode("\n", $value);
    		foreach ($listArr as $row) {
    			$rowArr = explode("|", $row);
    			if (count($rowArr) != 3) {
    				continue;
    			}
    			$url = 'https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/audio_ai/' . $rowArr['1'] . ".wav";
    			$one = array(
    				'name' 	=> $rowArr['0'],
    				'id' 	=> $rowArr['1'],
    				'url'	=> $url,
    			);
    			$map[$key][$one['id']] = $one;
    		}
    	}
    	$classifyList = array();
    	foreach ($map as $key => $list) {
    		$classifyList[md5($key)] = array(
    			'id' 	=> md5($key),
    			'name'	=> $key,
    			'list'	=> array_values($list),
    		);
    	}
    	return $classifyList;
    }
    
    /**
     * 获取配音演员列表
     * 
     * @return array
     */
    public function getActorList($id)
    {
    	$classifyList = $this->getActorClassifys();
    	return empty($classifyList[$id]) ? array() : $classifyList[$id]['list'];
    }
    
    /**
     * 获取热门音乐分类
     * 
     * @return array
     */
    public function getMusicList($classifyId)
    {
    	$musicDao = \dao\Music::singleton();
    	$musicEttList = $musicDao->readListByIndex(array(
    		'classifyId' => $classifyId,
    	));
    	$list = array();
    	if (!empty($musicEttList)) foreach ($musicEttList as $musicEtt) {
    		$list[] = array(
    			'id' 			=> intval($musicEtt->id),
    			'name'			=> $musicEtt->name,
    			'duration'		=> intval($musicEtt->duration),
    			'publishUrl'	=> $musicEtt->publishUrl,
    			'playUrl'		=> $musicEtt->playUrl,
    		);
    	}
    	return $list;
    }

    /**
     * 获取静态配置
     *
     * @return array
     */
    public function getStaticConfig()
    {
    	$filterList = array();
    	$transitionList = array();
    	$filterArr = cfg('filter');
    	$transitionArr = cfg('transition');
    	foreach ($filterArr as $name => $id) {
    		$filterList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	foreach ($transitionArr as $name => $id) {
    		$transitionList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    		);
    	}
    	$effectColorStyleArr = cfg('effectColorStyle');
    	$base = "https://wb-yinhuo.oss-cn-beijing.aliyuncs.com/effect/";
    	foreach ($effectColorStyleArr as $name => $id) {
    		$effectColorStyleList[] = array(
    			'id' 	=> $id,
    			'name' 	=> $name,
    			'url'	=> $base . $name,
    		);
    	}
    	return array(
    		'filterList' => $filterList,
    		'transitionList' => $transitionList,
    		'effectColorStyleList' => $effectColorStyleList,
    	);
    }

    /**
     * 轮播图
     *
     * @return array
     */
    public function getBannerList()
    {
    	// 轮播图
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEttList = $bannerDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$commonSv = \service\Common::singleton();
    	$bannerList = array();
    	if (is_iteratable($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
    		$bannerList[] = array(
    			'id' => intval($bannerEtt->id),
    			'url' => $commonSv::formartImgUrl($bannerEtt->url, 'banner'),
    			'goto' => $bannerEtt->goto,
    		);
    	}
    	return $bannerList;
    }
    
    /**
     * 资讯
     *
     * @return array
     */
    public function getNewsList()
    {
    	// 轮播图
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEttList = $bannerDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$commonSv = \service\Common::singleton();
    	$bannerList = array();
    	if (is_iteratable($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
    		$bannerList[] = array(
    			'id' => intval($bannerEtt->id),
    			'url' => $commonSv::formartImgUrl($bannerEtt->url, 'banner'),
    			'goto' => $bannerEtt->goto,
    		);
    	}
    }
}