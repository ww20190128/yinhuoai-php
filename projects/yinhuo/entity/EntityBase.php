<?php
namespace entity;
use Application;

/**
 * 实体层抽象基类
 *  
 * @author wangwei
 */
abstract class EntityBase
{
	/**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = '';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = '';
    
	/**
     * 获取表信息
     *
     * @param   string  	$table  	表名
     * @param   string  	$db  		数据库名
     * 
     * @return array
     */
    public static function getTableInfo($table, $db)
    {
        $tableMap = empty($GLOBALS['TABLE_MAP']) ? array() : $GLOBALS['TABLE_MAP']; // 表结构映射
		if (empty($tableMap[$db][$table])) {
			$tableMap[$db][$table] = getTableStructure($table, $db);
		}
    	return $tableMap[$db][$table];
    }
    
    /**
     * 对象变更记录
     *
     * @var array{hash => {name => EntityPropertyChange}}
     */
    private static $objChanges = array();

    /**
     * 改变属性值
     *
     * @param	string 	$name  	属性名
     * @param 	mixed  	$value 	属性值
     *
     * @return $this
     */
    public function add($name, $value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        // 记录对象属性变更并设置属性
        $this->recordChange($name, $value, \entity\EntityChange::CHANGE_TYPE_ADD);
        $this->$name += $value;
        return $this;
    }

    /**
     * 设置属性值
     *
     * @param	string 	$name  	属性名
     * @param 	mixed  	$value 	属性值
     *
     * @return $this
     */
    public function set($name, $value)
    {
        // 记录对象属性变更并设置属性
        $this->recordChange($name, $value, \entity\EntityChange::CHANGE_TYPE_SET);
        $this->$name = $value;
        return $this;
    }

    /**
     * 记录对象属性变更
     *
     * @param string $name  属性名
     * @param mixed  $value 属性值
     * @param int    $type  变更类型
     *
     * @return bool
     */
    protected function recordChange($name, $value, $type)
    {
        $objHash = $this->getObjKey();
        if (isset(self::$objChanges[$objHash][$name])) {
            $changeObj = self::$objChanges[$objHash][$name];
        } else {
            $changeObj = new \entity\EntityChange;
            $changeObj->initValue  = $this->$name;
            $changeObj->changeType = $type;
        }
        if ($type != $changeObj->changeType) {
        	$changeObj->changeType = $type;
            // return false;
        }
        if ($type == \entity\EntityChange::CHANGE_TYPE_ADD) {
            if (null === $changeObj->changeValue) {
            	$changeObj->changeValue = 0;
            }
            $changeObj->changeValue += $value;
        } elseif ($type == \entity\EntityChange::CHANGE_TYPE_SET) {
            $changeObj->changeValue = $value;
        }
        self::$objChanges[$objHash][$name] = $changeObj;
        if (empty(self::$objChanges[$objHash]['oldEntity'])) {
        	self::$objChanges[$objHash]['oldEntity'] = clone $this;
        }      
        return true;
    }

    /**
     * 获取对象属性变更记录
     *
     * @return array
     */
    final public function loadChanges()
    {
        $objHash = $this->getObjKey();
        return isset(self::$objChanges[$objHash]) ? self::$objChanges[$objHash] : array();
    }

    /**
     * 清除对象属性变更记录
     *
     * @return $this
     */
    final public function clearChanges()
    {
        $objHash = $this->getObjKey();
        if (isset(self::$objChanges[$objHash])) {
        	unset(self::$objChanges[$objHash]);
        }
        return $this;
    }

    /**
     * 获取对象的唯一ID
     *
     * @return string
     */
    final private function getObjKey()
    {
        return spl_object_hash($this);
    }

    /**
     * 对象被销毁时，处理掉当前对象的改变
     *
     * @return string
     */
    public function __destruct()
    {
        $this->clearChanges();
    }

    /**
     * 用传入的DAO进行创建
     *
     * @param \dao\DaoBase $dao 数据访问对象
     *
     * @return $this
     */
    public function createBy(\dao\DaoBase $dao)
    {
        $dao->create($this);
        return $this;
    }

    /**
     * 用传入的DAO进行更新
     *
     * @param \dao\DaoBase $dao 数据访问对象
     *
     * @return $this
     */
    public function updateBy(\dao\DaoBase $dao)
    {
        $dao->update($this);
        return $this;
    }

    /**
     * 用传入的DAO进行删除
     *
     * @param \dao\DaoBase $dao 数据访问对象
     *
     * @return bool
     */
    public function deleteBy(\dao\DaoBase $dao)
    {
        return $dao->delete($this);
    }
    
	/**
     * 魔术方法  获取属性值
     *
     * @param   string  $name   属性
     *
     * @return string  属性值
     */
    public function __get($name)
    {
    	if (empty($name)) {
    		return ;
    	}
        $methodName = 'get' . ucfirst($name);
        return method_exists($this, $methodName)
            ? $this->$methodName()
            : (method_exists($this, 'getter')
                ? $this->getter($name)
                : (isset($this->$name)
                    ? $this->$name
                    : (isset($name) ? self::$$name : null)
                )
            ); 
    }
    
	/**
     * 魔术方法，调用一个不可见的方法
     * 
     * @param 	string	$method 	方法名
     * @param 	mixed	$args   	参数列表
     * 
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (in_array($method, array('set', 'add'))) {
            $name = $args['0'];
            $methodName = $method . ucfirst($name);
            $unitive = ($method == 'set') ? 'setter' : 'adder';
            if (method_exists($this, $methodName)) {
                array_shift($args);
                return call_user_func_array(array($this, $methodName), $args);
            } else if (method_exists($this, $unitive)) {
            	return call_user_func_array(array($this, $unitive), $args);
            } else {
                return call_user_func_array(array($this, $method), $args);
            }
        } else {
            return false;
        }
    }
    
	/**
     * 魔术方法，给一个不可见的属性赋值
     * 
     * @param 	string	$name  属性名
     * @param 	max		$value 属性值
     * 
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
     
	/**
     * 去掉实体中protected类型值
     * 
     * @return this
     */
    public function unsetProtected()
    {
    	$entityClass = get_class($this);
    	$frame = &Application::$Frame;
    	// 项目类型
    	$projectType = empty($frame->conf['type']) ? 0 : $frame->conf['type']; // 项目类型 1 考教师 2 公考
    	$serverId = empty($frame->conf['id']) ? 0 : $frame->conf['id']; // 服务器id
    	$database = 'public-admin';
    	switch ($projectType) {
            case 1:
                // 考教师
                if ($serverId >= 100) {
                    $database = 'public-admin';
                } else {
                    $database = 'public_admin_kjs';
                }
                break;
            case 2:
                // 考公考
                if ($serverId >= 100) {
                    $database = 'public-admin';
                } else {
                    $database = 'public_admin_kgk';
                }
                break;
            case 3:
                // 工作台
                $database = 'desktop';
                break;
            case 4:
                // 用户中心
                $database = 'user_center';
                break;
        }
        $getTableInfo = $entityClass::getTableInfo($entityClass::MAIN_TABLE, $database); 
        $columns = array_keys($getTableInfo['column']);      
        $result = clone $this;
        foreach ($result as $key => $row) {
        	if (!in_array($key, $columns)) {
        		$result->$key = null;
        	}
        }
        return $result;
    }

    /**
     * 将实体类型转为数组
     *
     * @return array
     */
    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }
}