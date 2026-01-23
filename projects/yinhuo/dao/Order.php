<?php
namespace dao;

/**
 * Order 数据库类
 * 
 * @author 
 */
class Order extends DaoBase
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
     * @return Order
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Order();
        }
        return self::$instance;
    }

    /**
     * 订单搜索
     * 
     * @return array
     */
    public function getList($info, $pageNum = 0, $limitNum = 0)
    {
    	$fieldStr = '*';
    	if ($pageNum < 0) {
    		$fieldStr = 'count(*) as `num`';
    	}
    	$deleteStatus = \constant\Common::DATA_DELETE;
    	// 根据标签id搜索
    	$whereArr = array(
    		"`status` != {$deleteStatus}"
    	);
    	if (!empty($info['searchStatus'])) {
    		$whereArr[] = " `status` = {$info['searchStatus']}";
    	}
    	// 开始时间
    	if (!empty($info['searchStartTime'])) {
    		$whereArr[] = " `createTime` >= {$info['searchStartTime']}";
    	}
    	// 结束时间
    	if (!empty($info['searchEndTime'])) {
    		$whereArr[] = " `createTime` <= {$info['searchEndTime']}";
    	}
    	
    	$where = empty($whereArr) ? 1 : implode(' AND ', $whereArr);
    	$mainTable = $this->mainTable;
   
		$sql = "SELECT {$fieldStr} FROM `{$mainTable}` where {$where}
    		ORDER BY `createTime` DESC
    	";
    	if ($pageNum > 0) {
    		$startLimit = ($pageNum - 1) * $limitNum;
    		$sql .= "limit {$startLimit}, $limitNum";
    	}
    	$sql .= ';';

    	$result = $this->readDataBySql($sql, $mainTable);
    	$result = empty($result) ? array() : array_values($result);
    	if ($pageNum < 0) {
    		$result = reset($result)->num;
    	}
    	return $result;
    }

}