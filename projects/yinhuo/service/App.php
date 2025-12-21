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
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '豆包大模型2.0',
    	);
    	$list[] = array(
    		'id' => 2,
    		'name' => '通用模型',
    	);
    	$list[] = array(
    		'id' => 3,
    		'name' => 'IP仿音',
    	);
    	return $list;
    }
    
    /**
     * 获取热门音乐分类
     *duration:100, // 播放时长

     * @return array
     */
    public function getActorList()
    {
    	$list = array();
    	$list[] = array(
    		'id' => 1,
    		'name' => '四郎',
    		'url' => 'https:xxxx',
    		
    	);
    	$list[] = array(
    			'id' => 2,
    			'name' => '熊二',
    			'url' => 'https:xxxx',
    	
    	);
    	return $list;
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
    	return array(
    		'filterList' => $filterList,
    		'transitionList' => $transitionList,
    	);
    }

}