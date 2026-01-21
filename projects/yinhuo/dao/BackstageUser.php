<?php
namespace dao;

/**
 * BackstageUser 数据库类
 * 
 * @author 
 */
class BackstageUser extends DaoBase
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
     * @return BackstageUser
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BackstageUser();
        }
        return self::$instance;
    }

    /**
     * 用户列表
     * 
     * @return array
     */
    public function getList($info)
    {
    	if (!empty($info['searchUserName'])) {
    		$whereArr[] = " `userName` like '%{$info['searchUserName']}%'";
    	}
    	if (!empty($info['searchPhone'])) {
    		$whereArr[] = " `phone` like '%{$info['searchPhone']}%'";
    	}
    	if (!empty($info['searchCreateUserId'])) {
    		$whereArr[] = " `createUserId` = {$info['searchCreateUserId']}";
    	}
    	if (!empty($info['searchUserId'])) {
    		$whereArr[] = " `userId` = {$info['searchUserId']}";
    	}
    	if (!empty($info['searchShowPrivilegeId'])) {
    		$whereArr[] = "FIND_IN_SET({$info['searchShowPrivilegeId']}, `showPrivileges`)";
    	}
    	if (!empty($info['searchOpPrivilegeId'])) {
    		$whereArr[] = "FIND_IN_SET({$info['searchOpPrivilegeId']}, `opPrivileges`)";
    	}
    	if (!empty($info['searchShareUserId']) ) {
    		$whereArr[] = "FIND_IN_SET({$info['searchShareUserId']}, `shareUserIds`)";
    	}
    	$where = empty($whereArr) ? 1 : implode(' AND ', $whereArr);
    	$sql = "SELECT * FROM `{$this->mainTable}` where {$where} ORDER BY `createTime` asc";
    	$datas = $this->readDataBySql($sql, $this->mainTable);
    	return empty($datas) ? array() : $datas;
    }

}