<?php
namespace service;

/**
 * 工具
 * 
 * @author 
 */
class Tools extends ServiceBase
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
     * @return Tools
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Tools();
        }
        return self::$instance;
    }

    /**
     * 创建支付订单
     *
     * @return array
     */
    public function createOrder($userId, $vipId, $deviceInfo)
    {
        $userDao = \dao\User::singleton();
        $userEtt = $userDao->readByPrimary($userId);
        if (empty($userEtt)) {
            throw new $this->exception('登录失效，请重新登陆!');
        }
        $vipConfigDao = \dao\VipConfig::singleton();
        $vipConfigEtt = $vipConfigDao->readByPrimary($vipId);
        if (empty($vipConfigEtt) || $vipConfigEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('vip已删除');
        }

        $price = $vipConfigEtt->price;
        $now = $this->frame->now;
        $orderDao = \dao\Order::singleton();
        $orderEtt = $orderDao->getNewEntity();
        $orderEtt->goodsType = \constant\Pay::TYPE_GOODS_VIP;
        $orderEtt->goodsId = $vipId;
        $orderEtt->userId = $userId;
        $orderEtt->status = \constant\Pay::PAY_STATUS_DURING;
        $orderEtt->price = $price;
        $orderEtt->updateTime = $now;
        $orderEtt->createTime = $now;
        $orderId = $orderDao->create($orderEtt);
        return array(
            'orderId' => intval($orderId), // 加密
        );
    }
}