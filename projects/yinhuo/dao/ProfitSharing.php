<?php
namespace dao;

/**
 * ProfitSharing 数据库类
 * 
 * @author 
 */
class ProfitSharing extends DaoBase
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
     * @return ProfitSharing
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ProfitSharing();
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
    	
    /**
     * 获取分销返利
     * 
     * @return array
     */
    public function getListByOrderIds($orderIds)
    {
    	if (empty($orderIds)) {
    		return array();
    	}
    	// 根据标签id搜索
    	$where = "`orderId` in (" . implode(',', $orderIds) . ")";
    	$mainTable = $this->mainTable;
		$sql = "SELECT * FROM `{$mainTable}` where {$where}
    		ORDER BY `createTime` DESC
    	";
    	$sql .= ';';

    	$result = $this->readDataBySql($sql, $mainTable);
    	$result = empty($result) ? array() : array_values($result);
    	return $result;
    }

}