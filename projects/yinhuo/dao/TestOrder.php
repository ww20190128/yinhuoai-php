<?php
namespace dao;

/**
 * TestOrder 数据库类
 * 
 * @author 
 */
class TestOrder extends DaoBase
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
     * @return TestOrder
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestOrder();
        }
        return self::$instance;
    }

    /**
     * 获取最近一次测试的订单
     *
     * @return object
     */
    public function getLastTestOrderInfo($testPaperId, $userId, $promotionId = 0)
    {
        $where = "`testPaperId` = {$testPaperId} and `userId` = $userId and `status` != " . \constant\Common::DATA_DELETE;
        if (!empty($promotionId)) {
            $where .= "  and `promotionId` = {$promotionId}";
        }
        $sql = "select * from `{$this->mainTable}` where {$where} order by `createTime` desc limit 1";
        $datas = $this->readDataBySql($sql, $this->mainTable);
        return empty($datas) ? array() : reset($datas);
    }
    
    /**
     * 根据抵扣对象获取订单
     *
     * @return array
     */
    public function getTestOrderByDiscount($discountType, $discountIds)
    {
        if (empty($discountIds)) {
            return array();
        }
        $where = "`discountType` = {$discountType} and `status` != " . \constant\Common::DATA_DELETE;
        $where .= "  and `discountId` in ('" . implode("', '", $discountIds) . "')";
        $sql = "select * from `{$this->mainTable}` where {$where} order by `createTime`;";
        $datas = $this->readDataBySql($sql, $this->mainTable);
        return empty($datas) ? array() : $this->refactorListByKey($datas, 'discountId');
    }

}