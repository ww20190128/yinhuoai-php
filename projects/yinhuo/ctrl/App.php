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
	 * 获取头信息
	 *
	 * @return array
	 */
	private static function getHeaders()
	{
		$authorization = <<<EOT
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYXBpLnB5cC5jYW56YW4uY29tL2NvbXBhbnkvbG9naW4iLCJpYXQiOjE3NjcwMDY5OTUsImV4cCI6MTc2OTU5ODk5NSwibmJmIjoxNzY3MDA2OTk1LCJqdGkiOiIwTFJmbzFHOUVyWFBXVXNQIiwic3ViIjoiNjk2IiwicHJ2IjoiZGYxMGExN2YwNWM2ZjEwNDAxYjdmZGIyNTFkZGY1NzcwZjYyNTZhYSJ9.rEGtMR6gTENuNlxz3vcDV7qskFFQ-5kLzMRBau9QABU
EOT;
		$cookie = empty($cookie) ? '' : trim($cookie); // cookie信息
		$cookie = 'authorization:' . $authorization;
		// 题目
		$headers = <<<EOT
Host: tiku.htexam.com
Connection: keep-alive
Pragma: no-cache
Cache-Control: no-cache
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36
Accept: application/json, text/plain, */*
Accept-Encoding: gzip, deflate
Accept-Language: zh-CN,zh;q=0.9,en;q=0.8
Content-Type: application/json
EOT;
		$headers = explode("\n", $headers); // 头信息
		$headers[] = $cookie;
		return $headers;
	}
	
	/**
	 * 同步音乐数据
	 *
	 * @return array
	 */
	public function sysnMusic()
	{
		$authorization = <<<EOT
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYXBpLnB5cC5jYW56YW4uY29tL2NvbXBhbnkvbG9naW4iLCJpYXQiOjE3NjcwMDY5OTUsImV4cCI6MTc2OTU5ODk5NSwibmJmIjoxNzY3MDA2OTk1LCJqdGkiOiIwTFJmbzFHOUVyWFBXVXNQIiwic3ViIjoiNjk2IiwicHJ2IjoiZGYxMGExN2YwNWM2ZjEwNDAxYjdmZGIyNTFkZGY1NzcwZjYyNTZhYSJ9.rEGtMR6gTENuNlxz3vcDV7qskFFQ-5kLzMRBau9QABU
EOT;
		$appSv = \service\App::singleton();
		$appSv->sysnMusic($authorization);
	}
	
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
		$id = $this->paramFilter('id', 'intval'); // 分类Id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$appSv = \service\App::singleton();
		$list = $appSv->getActorClassifys($id);
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
		$id = $this->paramFilter('id', 'string'); // 分类Id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$appSv = \service\App::singleton();
		$list = $appSv->getActorList($id);
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
		$id = $this->paramFilter('id', 'string'); // 分类Id
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$appSv = \service\App::singleton();
		$dataList = $appSv->getMusicList($id);
		$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval', 200); // 每页数量限制
		// 符合条件的总条数
		$totalNum = count($dataList);
		// 分页显示
		$dataList = array_slice($dataList, ($pageNum - 1) * $pageLimit, $pageLimit);
		return array(
			'totalNum' => $totalNum,
			'list' => array_values($dataList),
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