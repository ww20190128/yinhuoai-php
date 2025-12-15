<?php
namespace dao;

/**
 * Trace 数据库类
 * 
 * @author 
 */
class Trace extends DaoBase
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
     * @return Trace
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Trace();
        }
        return self::$instance;
    }

    /**
     * 主方法
     *
     * @return void
     */
    public function main()
    {
        return ;
    }
    
    /**
     * 搜索
     *
     * @return array
     */
    public function getList($info, $pageNum = 0, $limitNum = 60)
    {
    	$fieldStr = '*';
    	if ($pageNum < 0) {
    		$fieldStr = 'count(*) as `num`';
    	}
    	// 操作者id
    	if (!empty($info['searchUserId'])) {
    		$whereArr[] = " `userId` = {$info['searchUserId']}";
    	}
    	// 操作类型
    	if (!empty($info['searchType'])) {
    		$whereArr[] = " `type` = {$info['searchType']}";
    	}
    	// 开始时间
    	if (!empty($info['searchStartTime'])) {
    	    $whereArr[] = " `traceTime` >= {$info['searchStartTime']}";
    	}
    	// 结束时间
    	if (!empty($info['searchEndTime'])) {
    	    $whereArr[] = " `traceTime` <= {$info['searchEndTime']}";
    	}
    	$where = empty($whereArr) ? 1 : implode(' AND ', $whereArr);
    	$sql = "SELECT {$fieldStr} FROM `trace` where {$where}
    		ORDER BY `traceTime` asc
    	";
    	if ($pageNum > 0) {
    		$startLimit = ($pageNum - 1) * $limitNum;
    		$sql .= "limit {$startLimit}, $limitNum";
    	}
    	$sql .= ';';
    	$result = $this->readDataBySql($sql, 'trace');
    	$result = empty($result) ? array() : array_values($result);
    	if ($pageNum < 0) {
    		$result = reset($result)->num;
    	}
    	return $result;
    }

}