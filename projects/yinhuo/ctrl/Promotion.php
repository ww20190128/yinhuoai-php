<?php
namespace ctrl;

/**
 * 推广 控制器类
 * 
 * @author 
 */
class Promotion extends CtrlBase
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
     * @return Promotion
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Promotion();
        }
        return self::$instance;
    }

    /**
     * 获取推广测评详情
     * 
     * @return array
     */
    public function getPromotionInfo()
    {
        $params = $this->params;
        $promotionId = $this->paramFilter('promotionId', 'intval'); // 推广Id
        if (empty($promotionId)) {
            throw new $this->exception('请求参数错误');
        }
        $promotionSv = \service\Promotion::singleton();
        return $promotionSv->getPromotionInfo($promotionId, $this->userId);
    }
    
    /**
     * 获取推广测评详情
     * 
     * @return array
     */
    public function getPromotionInfo2()
    {
        $params = $this->params;
        $promotionId = $this->paramFilter('promotionId', 'intval'); // 推广Id
        $version = $this->paramFilter('version', 'intval', 1); // 版本号
        if (empty($promotionId)) {
            throw new $this->exception('请求参数错误');
        }
        $promotionSv = \service\Promotion::singleton();
        return $promotionSv->getPromotionInfo2($promotionId, $version, $this->userId);
    }
    
}