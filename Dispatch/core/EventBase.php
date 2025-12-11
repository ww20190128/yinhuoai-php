<?php
namespace Dispatch;

/**
 * 事件抽象类
 * 
 * @author wangwei
 */
abstract class EventBase
{
    /**
     * 句柄
     *
     * @var string
     */
    protected static $handle;

    /**
     * 监听器列表
     *
     * @var array
     */
    protected $listeners;

    /**
     * 构造函数
     */
    abstract public function __construct();

    /**
     * 初始化监听器
     */
    abstract public function initListeners();

    /**
     * 将给定语句解析成条件序列
     *
     * @param   string  $conditionStr   条件字符串
     *
     * @return array
     */
    public static function parseCondition($conditionStr)
    {
        $condition = array();
        $escape = " \t\"";
        foreach (preg_split('/\band\b/i', $conditionStr) as $row) {
            if (preg_match('/(.+)==(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '==');

            } else if (preg_match('/(.+)\bin\b(.+)/', $row, $matchs)) {
                $list = explode(',', trim($matchs['2'], '()'));
                foreach ($list as $key => $val) {
                    $list[$key] = trim($val, $escape);
                }
                $condition[] = array(trim($matchs['1']), $list, 'in');
            } else if (preg_match('/(.+)>=(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '>=');
            } else if (preg_match('/(.+)>(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '>');
            } else if (preg_match('/(.+)<=(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '<=');
            } else if (preg_match('/(.+)<(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '<');
            } else if (preg_match('/(.+)!=(.+)/', $row, $matchs)) {
                $condition[] = array(trim($matchs['1'], $escape), trim($matchs['2'], $escape), '!=');
            }
        }
        return $condition;
    }

    /**
     * 检查条件是否满足
     *
     * @param   array   $condition 条件
     *
     * @param $context
     *
     * @return bool
     */
    public static function satisfy($condition, $context)
    {
return true;
        list($lv, $rv, $op) = $condition;
        switch ($op) {
            case '==':
                $result = (isset($context[$lv]) && $context[$lv] == $rv);
                break;
            case '>=':
                $result = (isset($context[$lv]) && $context[$lv] >= $rv);
                break;
            case '>':
                $result = (isset($context[$lv]) && $context[$lv] > $rv);
                break;
            case '<':
                $result = (isset($context[$lv]) && $context[$lv] < $rv);
                break;
            case '<=':
                $result = (isset($context[$lv]) && $context[$lv] <= $rv);
                break;
            case 'in':
                $result = (isset($context[$lv]) && in_array($context[$lv], $rv));
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

}