<?php
namespace dao;

/**
 * YieldRecord 数据库类
 * 
 * @author 
 */
class YieldRecord extends DaoBase
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
     * @return YieldRecord
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YieldRecord();
        }
        return self::$instance;
    }

   /**
     * 列表
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

    	if (!empty($info['searchShareUserIds'])) {
    		$whereArr[] = " `shareUserId` in (" . implode(',', $info['searchShareUserIds']) . ")";
    	}
    	if (!empty($info['searchTestPaperIds'])) {
    		$whereArr[] = " `testPaperId` in (" . implode(',', $info['searchTestPaperIds']) . ")";
    	}
    	// 开始时间
    	if (!empty($info['searchStartTime'])) {
    		$whereArr[] = " `createTime` >= {$info['searchStartTime']}";
    	}
    	// 结束时间
    	if (!empty($info['searchEndTime'])) {
    		$whereArr[] = " `createTime` <= {$info['searchEndTime']}";
    	}
    	// 状态
    	if (!empty($info['searchStatus'])) {
    		$whereArr[] = " `status` = {$info['searchStatus']}";
    	}
    	$where = empty($whereArr) ? 1 : implode(' AND ', $whereArr);  	
    	$sql = "SELECT {$fieldStr} FROM `{$this->mainTable}` where {$where} ORDER BY `createTime` DESC ";

    	if ($pageNum > 0) {
	    	$startLimit = ($pageNum - 1) * $limitNum;
	    	$sql .= " limit {$startLimit}, $limitNum";
    	}
    	$sql .= ';';
    	$result = $this->readDataBySql($sql, $this->mainTable);
    	$result = empty($result) ? array() : array_values($result);
    	if ($pageNum < 0) {
    		$result = reset($result)->num;
    		return $result;
    	} 
    	return $result;
    }
    
}