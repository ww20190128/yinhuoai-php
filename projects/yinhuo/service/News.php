<?php
namespace service;

/**
 * 新闻资讯 逻辑类
 * 
 * @author 
 */
class News extends ServiceBase
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
     * @return News
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new News();
        }
        return self::$instance;
    }

    /**
     * 资讯
     *
     * @return array
     */
    public function getNewsList()
    {
    	$newsDao = \dao\News::singleton();
    	$newsEttList = $newsDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$commonSv = \service\Common::singleton();
    	$newsList = array();
    	if (is_iteratable($newsEttList)) foreach ($newsEttList as $newsEtt) {
    		$newsList[] = array(
    			'id' => intval($newsEtt->id),
    			'title' => $newsEtt->title,
    			'content' => $newsEtt->content,
    			'source' => $newsEtt->source,
    			'coverURL' => $newsEtt->coverURL,
    			'status' => intval($newsEtt->status),
    			'updateTime' => intval($newsEtt->updateTime),
    			'createTime' => intval($newsEtt->createTime),
    		);
    	}
    	return $newsList;
    }

}