<?php
namespace service;

/**
 * Vip 逻辑类
 * 
 * @author 
 */
class Vip extends ServiceBase
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
     * @return Vip
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Vip();
        }
        return self::$instance;
    }

    /**
     * 获取vip配置
     *
     * @return array
     */
    public function getConfigList()
    {
        $vipConfigDao = \dao\VipConfig::singleton();
        $vipConfigEttList = $vipConfigDao->readListByIndex(array(
            'status' => 0,
        ));
        $modelList = array();
        if (is_iteratable($vipConfigEttList)) foreach ($vipConfigEttList as $vipConfigEtt) {
            $model = $vipConfigEtt->getModel();
            $modelList[$model['id']] = $model;
        }
        return $modelList;
    }
   
    /**
     * vip订单详情
     *
     * @return array
     */
    public function vipOrderInfo($userId, $orderId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt)) {
    		throw new $this->exception('登录失效，请重新登陆!');
    	}
    	$userVipDao = \dao\UserVip::singleton();
    	$userVipEtt = $userVipDao->readByPrimary($orderId);
    	if (empty($userVipEtt) || $userVipEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已失效，请重新购买！');
    	}
    	$vipConfigDao = \dao\VipConfig::singleton();
    	$vipConfigEtt = $vipConfigDao->readByPrimary($userVipEtt->vipId);
    	if (empty($vipConfigEtt) || $vipConfigEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录失效，请重新登陆!');
    	}
    	$vipConfigModel = $vipConfigEtt->getModel();
    	$userVipModel = array(
    		'orderId' => intval($userVipEtt->id),	
    		'userId' => intval($userVipEtt->userId),
    		'useGiveNum' => intval($userVipEtt->useGiveNum),
    		'effectTime' => intval($userVipEtt->effectTime),
    		'createTime' => intval($userVipEtt->createTime),
    		'updateTime' => intval($userVipEtt->updateTime),
    	);
    	return array(
    		'vipConfig'	=> $vipConfigModel,
    		'userVip' => $userVipModel,
    	);
    }
    
}