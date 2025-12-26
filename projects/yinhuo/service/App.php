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
     * @return Index
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }
        return self::$instance;
    }
    
    /**
     * 获取热门音乐分类
     *
     * @return array
     */
    public function getMusicClassifys()
    {
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '推荐',
    	);
    	$list[] = array(
    		'id' => 2,
    		'name' => '热门榜',
    	);
    	$list[] = array(
    		'id' => 3,
    		'name' => '飙升榜(新)',
    	);
    	$list[] = array(
    		'id' => 4,
    		'name' => '原创榜',
    	);
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
     *duration:100, // 播放时长
    
     * @return array
     */
    public function getMusicList()
    {
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '音乐名称',
    		'url' => 'https:xxxx',
    		'duration' => 119,
    	);
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

}