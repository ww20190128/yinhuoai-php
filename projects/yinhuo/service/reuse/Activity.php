<?php
namespace service\reuse;
use Dispatch\EventBase;

/**
 * 活动相关的逻辑
 *
 * @author
 */
class Activity extends EventBase
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        self::$handle = 'handle'; // 注册事件处理句柄
    }

    /**
     * 活动处理函数，更新用户活动进度
     *
     * @param   array   $listener       事件
     * @param   array   $userInfo       玩家信息
     *
     * @return string
     */
    public static function handle($listener, $userInfo)
    {
        return "handle";
    }

    /**
     * 初始化监听器
     *
     * @return array
     */
    public function initListeners()
    {
        $this->listeners = array();
        $this->listeners['1'][] = array(
            'handle'    => self::$handle, // 事件句柄
            'condition' => 'num >= 10', // 条件: 需要登录次数>= 10
            'progress'  => array( // 事件进度
                'userNum'   => 0,       // 已满足条件的玩家数
                'totalNum'  => 0,       // 累计登录数
            ),
        );
        $this->listeners['1'][] = array(
            'handle'    => self::$handle, // 事件句柄
            'condition' => 'num >= 15', // 条件: 需要登录次数>= 10
            'progress'  => array( // 事件进度
                'userNum'   => 0,       // 已满足条件的玩家数
                'totalNum'  => 0,       // 累计登录数
            ),
        );
        $this->listeners['2'][] = array(
            'handle'    => self::$handle, // 事件句柄
            'condition' => 'time >= 123645555', // 条件: 需要登录次数>= 10
            'progress'  => array( // 事件进度
                'userNum'   => 0,       // 已满足条件的玩家数
                'totalNum'  => 0,       // 累计登录数
            ),
        );
        return $this->listeners;
    }

    /**
     * 检查条件是否满足, 如果已经达成条件返回true, 还未达成条件返回false
     *
     * @param   array   $condition  条件
     * <code>
     *  'handle'    => self::$handle, // 事件句柄
     *  'condition' => 'num >= 10', // 条件: 需要登录次数>= 10
     *  'progress'  => array( // 事件进度
     *      'userNum'   => 0,       // 已满足条件的玩家数
     *      'totalNum'  => 0,       // 累计登录数
     *  ),
     * </code>
     * @param   array   $userInfo   玩家信息
     * <code>
     * 'type'       => 1, // 事件id
     * 'userId'     => 4, // 用户id
     * 'params'     => array( // 其他参数
     *      'ip' => 127.0.0.1
     *  ),
     * 'progress' => array(
     *     'num' => 9,
     * )
     * </code>
     * @return bool
     */
    public static function satisfy($condition, $userInfo)
    {
        return false;
    }

}