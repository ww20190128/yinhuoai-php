<?php
namespace ctrl;

/**
 * 首页
 *
 * @author
 */
class App extends CtrlBase
{
	/**
	 * 获取热门音乐分类
	 *
	 * @return array
	 */
	public function getMusicClassifys()
	{
		$params = $this->params;
		$appSv = \service\App::singleton();
		$list = $appSv->getMusicClassifys();
		return array(
			'list' => array_values($list),
		);
	}
	
	/**
	 * 获取热门音乐
	 *
	 * @return array
	 */
	public function getMusicList()
	{
		$params = $this->params;
		$appSv = \service\App::singleton();
		$list = $appSv->getMusicList();
		return array(
			'list' => array_values($list),
		);
	}
	
    /**
     * 获取静态配置
     *
     * @return array
     */
    public function getStaticConfig()
    {
        $params = $this->params;
        $appSv = \service\App::singleton();
        return $appSv->getStaticConfig();
    }
    
    /**
     * 获取音乐分类
     *
     * @return array
     */
    public function getMuConfig()
    {
    	$params = $this->params;
    	$appSv = \service\App::singleton();
    	return $appSv->getStaticConfig();
    }
    
    
}