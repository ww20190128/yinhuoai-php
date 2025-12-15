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
	 * 获取微信SDK配置
	 *
	 * @return array
	 */
	public function getWeChatConfig()
	{
		$params = $this->params;
		$url = $this->paramFilter('url', 'string'); // 地址
		if (empty($url)) {
			throw new $this->exception('请求参数错误');
		}
		$appSv = \service\App::singleton();
		return $appSv->getWeChatConfig($url);
	}
	
	/**
	 * 微信授权认证
	 * 
	 * @return array
	 */
	public function authByWeChat()
	{
	    $params = $this->params;
	    $auth_type = $this->paramFilter('auth_type', 'string'); // 回调码
	    $selfUrl = $this->paramFilter('self_redirect', 'string'); // 回调码
	    $weChat = empty($this->frame->conf['weChat']) ? array() : $this->frame->conf['weChat'];
	    $appId = $weChat['appId'];
	    $appSecret = $weChat['appSecret'];
	
	    if (empty($weChat)) {
	        throw new $this->exception('获取微信配置失败！');
	    }
	    // 生成唯一随机串防CSRF攻击
	    $state  = md5(uniqid(rand(), TRUE));
	    $selfUrl = urlencode($selfUrl);//该回调需要url编码
	    
	    $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$selfUrl}&response_type=code&scope=snsapi_userinfo&state={$state}&connect_redirect=1#wechat_redirect";
	    // 无感知授权
	    //$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$selfUrl}&response_type=code&scope=snsapi_base&state={$state}&connect_redirect=1#wechat_redirect";
	    header("Location:" . $url);
	    exit;
	}

    /**
     * 获取分享信息（测评）
     *
     * @return array
     */
    public function getShareTestPaperInfo()
    {
    	$params = $this->params;
        $testPaperId = $this->paramFilter('testPaperId', 'intval', 0); // 测评id
        $promotionId = $this->paramFilter('promotionId', 'intval', 0); // 推广测评id
        $shareCode = $this->paramFilter('shareCode', 'string'); // 分享码
        $link = $this->paramFilter('link', 'string'); // type == 3 时有该参数
        $desc = $this->paramFilter('desc', 'string'); // type == 3 时有该参数
    	$appSv = \service\App::singleton();
    	return $appSv->getShareTestPaperInfo($testPaperId, $promotionId, $link, $shareCode, $desc);
    }
    
    /**
     * 获取分享信息（正念）
     *
     * @return array
     */
    public function getShareMindfulnessInfo()
    {
    	$params = $this->params;
    	$mindfulnessId = $this->paramFilter('mindfulnessId', 'intval', 0); // 正念id
    	$appSv = \service\App::singleton();
    	return $appSv->getShareMindfulnessInfo($mindfulnessId);
    }
    
    /**
     * 获取分享信息（app）
     *
     * @return array
     */
    public function getShareAppInfo()
    {
    	$appConfig = $this->frame->conf['appConfig'];
    	$host = empty($this->frame->conf['web_url']) ? array() : $this->frame->conf['web_url'];
    	$imgUrlBase = empty($this->frame->conf['urls']['images']) ? array() : $this->frame->conf['urls']['images'];
    	$result = array(
    		'desc' => "{$appConfig['name']}，专注年青人的心理健康，你内心困惑的问题，在{$appConfig['name']}可以找到答案。",
    		'link' => $host . '/home?source=indexShare',
    		'title' => $appConfig['name'] . '-了解更真实的自己',
    		'imgUrl' => $imgUrlBase . 'logo.png',
    	);
    	return $result;
    }
  
    /**
     * 获取分享信息（分销）
     *
     * @return array
     */
    public function getShareBusinessInfo()
    {
    	$appConfig = $this->frame->conf['appConfig'];
    	$host = empty($this->frame->conf['web_url']) ? array() : $this->frame->conf['web_url'];
    	$imgUrlBase = empty($this->frame->conf['urls']['images']) ? array() : $this->frame->conf['urls']['images'];
    	$result = array(
    		'desc' => '只要您有手机就可加入我们的“搞钱计划”，零成本，零风险，收入可观，现金实时到账，快点击了解吧！',
    		'link' => $host . '/business',
    		'title' => $appConfig['name'] . '-分享赚佣金活动',
    		'imgUrl' => $imgUrlBase . 'business-share.png',
    	);
    	return $result;
    }
    
    /**
     * 记录参数
     * 
     * @return array
     */
    public function recordParam()
    {
    	$params = $this->params;
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 首页信息
     *
     * @return array
     */
    public function main()
    {
        $params = $this->params;
        $appSv = \service\App::singleton();
        return $appSv->main();
    }

    /**
     * 获取分类
     *
     * @return array
     */
    public function getClassifyList()
    {
    	$params = $this->params;
    	$getTestPaperNum = $this->paramFilter('getTestPaperNum', 'intval', 0); // 是否获取分下的测评数量
    	$classifySv = \service\Classify::singleton();
    	$classifyList = $classifySv->getClassifyList(0, $getTestPaperNum);
    	return array(
    		'list' => array_values($classifyList),
    	);
    }
    
    /**
     * 根据分类ID获取测评列表
     *
     * @return array
     */
    public function getListByClassify()
    {
    	$params = $this->params;
    	$classifyId = $this->paramFilter('classifyId', 'intval', 0); // 分类id
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	$testPaperName = $this->paramFilter('testPaperName', 'string'); // 测卷名称
    	$sortType = $this->paramFilter('sortType', 'intval', 0); // 排序类型 1 时间 2 热点 3 价格
    	$info = array(
    		'testPaperName' => $testPaperName,
    		'sortType' => $sortType,
    	);
    	$classifySv = \service\Classify::singleton();
    	return $classifySv->getListByClassify($classifyId, $info, $pageNum, $pageLimit);
    }
    
    /**
     * 将测评加入分类
     *
     * @return array
     */
    public function addTestPapersToClassify()
    {
    	$params = $this->params;
    	$classifyId = $this->paramFilter('classifyId', 'intval', 0); // 分类id
    	$testPaperIds = $this->paramFilter('testPaperIds', 'array', 0); // 测评ID
		if (empty($testPaperIds)) {
			throw new $this->exception('请选中添加的测评');
		}
    	$classifySv = \service\Classify::singleton();
    	return $classifySv->addTestPapersToClassify($classifyId, $testPaperIds);
    }
    
    /**
     * 将测评从分类移除
     *
     * @return array
     */
    public function removeTestPapersFromClassify()
    {
    	$params = $this->params;
    	$classifyId = $this->paramFilter('classifyId', 'intval', 0); // 分类id
    	$testPaperIds = $this->paramFilter('testPaperIds', 'array', 0); // 测评ID
    	if (empty($testPaperIds)) {
    		throw new $this->exception('请选中添加的测评');
    	}
    	$classifySv = \service\Classify::singleton();
    	return $classifySv->removeTestPapersFromClassify($classifyId, $testPaperIds);
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
     * 根据测评ID获取推荐测评
     *
     * @return array
     */
    public function getRecommendList()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval', 0); // 测评订单id
       
        $classifySv = \service\Classify::singleton();
        return $classifySv->getRecommendList($testOrderId);
    }
 
    
    /**
     * 保存图片
     * // 将图片上传到服务器端
          var formdata = new FormData(); //创建form对象
          formdata.append("image", canvas.toDataURL("image/png")); // 将图片数据添加到表单

          axios
            .post("http://192.168.3.133:666/App/saveImg", formdata, {
              "Content-Type": "multipart/form-data",
            })
            .then((response) => {
              var res = response.data;
              if (res.status == 0) {
              } else {
              }
            });
     * @return array
     */
    public function saveImg()
    {
    	$params = $this->params;
    	$imageData = $params->image;
		    // 提取base64编码的部分
		$base64Data = explode(',', $imageData)[1];
		
		// 解码base64数据
		$binaryData = base64_decode($base64Data);
		
		// 定义保存图片的文件名
		$fileName = '/data/www/test/example.png';
		
		// 将二进制数据写入文件
		file_put_contents($fileName, $binaryData);
		
		if (file_exists($fileName)) {
		    echo "图片保存成功！";
		} else {
		    echo "图片保存失败！";
		}
    }
}