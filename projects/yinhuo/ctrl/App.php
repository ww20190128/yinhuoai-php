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
	 * 获取热门音乐分类列表
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
	 * 获取配音演员分类列表
	 *
	 * @return array
	 */
	public function getActorClassifys()
	{
		$params = $this->params;
		$appSv = \service\App::singleton();
		$list = $appSv->getActorClassifys();
		return array(
				'list' => array_values($list),
		);
	}
	/**
	 * 获取配音演员分类列表
	 *
	 * @return array
	 */
	public function getActorList()
	{
		$params = $this->params;
		$appSv = \service\App::singleton();
		$list = $appSv->getActorList();
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

}