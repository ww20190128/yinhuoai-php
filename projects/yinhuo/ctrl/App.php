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
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiYBearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiY
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
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiYBearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiY
EOT;
		$appSv = \service\App::singleton();
		$appSv->sysnMusic($authorization);
	}
	
	/**
	 * 同步配音数据
	 *
	 * @return array
	 */
	public function sysnActor()
	{
		$authorization = <<<EOT
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiYBearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwaS5weXAuY2FuemFuLmNvbS9jb21wYW55L2xvZ2luIiwiaWF0IjoxNzcwNDU3NzIyLCJleHAiOjE3NzMwNDk3MjIsIm5iZiI6MTc3MDQ1NzcyMiwianRpIjoiUzY4SlMzTTFLaFNzY3pwUiIsInN1YiI6IjY5NiIsInBydiI6ImRmMTBhMTdmMDVjNmYxMDQwMWI3ZmRiMjUxZGRmNTc3MGY2MjU2YWEifQ.oGlmAcpLDC6cPPfm6fGS7noXNwL6hZZpeF05ffYWeiY
EOT;
		$appSv = \service\App::singleton();
		$appSv->sysnActor($authorization);
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

    /**
     * 获取vip的配置
     *
     * @return array
     */
    public function getVipConfig()
    {
    	$params = $this->params;
    	$couponId = $this->paramFilter('couponId', 'intval'); // 优惠券ID
    	$vipSv = \service\Vip::singleton();
    	$vipConfigList = $vipSv->getConfigList($couponId);
    
    	$couponInfo = array();
    	if (!empty($couponId)) { // 优惠券
    		$couponSv = \service\Coupon::singleton();
    		$couponInfo = $couponSv->couponInfo($couponId, $this->userId);
    	}
    	$vipInfo = empty($couponInfo['vipInfo']) ? array() : $couponInfo['vipInfo'];
    	if (empty($vipInfo) && !empty($this->userId)) {
    		$userSv = \service\User::singleton();
    		$userInfo = $userSv->userInfo($this->userId);
    		$vipInfo = empty($userInfo['userInfo']['vipInfo']) ? array() : $userInfo['userInfo']['vipInfo'];
    	}
    	return array(
    		'vipConfigList' => array_values($vipConfigList),
    		'couponInfo' => $couponInfo, // 优惠券信息
    		'vipInfo' => $vipInfo,
    	);
    }
    
    /**
     * 获取二维码
     *
     * @return array
     */
    public function getQrCode()
    {
    	$params = $this->params;
    	$url = $this->paramFilter('url', 'string'); // 链接
    	if (empty($url)) {
			throw new $this->exception('请求参数错误');
		}
    	$appSv = \service\App::singleton();
    	return $appSv->getQrCode($url);
    }
    
}