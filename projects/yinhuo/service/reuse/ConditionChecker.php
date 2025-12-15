<?php
namespace service\reuse;
use service\ServiceBase;

/**
 * 条件检查器
 * 
 * @author wangwei
 */
class ConditionChecker extends ServiceBase
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
     * @return ConditionChecker
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ConditionChecker();
        }
        return self::$instance;
    }

    /**
     * 获取 条件类型<=>方法名 映射关系
     * 
     * @return array
     */
    private function getConditionMethodMap()
    {
        return array(
            '1'		=> 'userLevel',			// 角色等级 [当前等级 >= 需要等级]
           	'2'		=> 'passTollgate',		// 通关关卡 [关卡类型, 当前通关关卡id >= 需要id]
        );
    }

    /**
     * 条件处理调度
     * 
     * @param 	string		$condition		条件参数
     * @param 	stdClass	$runtimeArgs  	运行时参数	
     * 
     * @return bool
     */
    public function checkCondition($condition, $runtimeArgs)
    {    	
        if (empty($condition)) {
        	return true;
        }
        $conditionMethodMap = $this->getConditionMethodMap();
        $conditionElements = explode('|', $condition);
        $conditionType = array_shift($conditionElements);
        array_push($conditionElements, $runtimeArgs);
        if (!isset($conditionMethodMap[$conditionType])) {
        	return false;
        }
        $conditionMethod = $conditionMethodMap[$conditionType];
        $result = call_user_func_array(array($this, $conditionMethod), $conditionElements);
        return $result;
    }

    /**
     * 角色等级[当前等级 >= 需要等级]
     * 
     * @param 	int 		$id 			需要的角色等级
     * @param 	\stdClass 	$runtimeArgs 	角色实体
     * 
     * @return bool
     */
    private function userLevel($level, $runtimeArgs)
    {
        return isset($runtimeArgs->level) && $runtimeArgs->level >= $level ? true : false;
    }

	/**
     * 通关关卡 [关卡类型, 当前通关关卡id >= 需要id]
     * 
     * @param 	int 		$type 			关卡类型
     * @param 	int 		$id 			关卡id
     * @param 	\stdClass 	$runtimeArgs 	角色实体
     * 
     * @return bool
     */
    private function passTollgate($type, $id, $runtimeArgs)
    {
		$userFlags = $runtimeArgs->userFlags;
		$passId = 0;  	
		if ($type == \constant\War::TYPE_ARENA_TRIALS) {
			$passId = $userFlags->trialsId;
		} elseif ($type == \constant\War::TYPE_ARENA_ADVENTURE) {
			$passId = $userFlags->adventureId;
		} elseif ($type == \constant\War::TYPE_ARENA_TEAM) {
			$passId = $userFlags->teamId;
		}
        return $passId >= $id ? true : false;
    }
    
}