<?php
namespace constant;

/**
 * 通用
 *
 * @author wangwei
 */
class Common
{
	/**
	 * 数据状态：删除
	 *
	 * @var int
	 */
	const DATA_DELETE = -1;
	
	/**
	 * 数据状态：正常
	 *
	 * @var int
	 */
	const STATUS_NORMAL = 0;
// 操作池类型
	/**
	 * 操作池类型：题目
	 *
	 * @var int
	 */
	const POOL_TYPE_QUESTION = 'question';
	
	/**
	 * 操作池类型：题目材料
	 *
	 * @var int
	 */
	const POOL_TYPE_QUESTION_MATERIAL = 'question_material';
	
	/**
	 * 操作池类型：贴标签
	 *
	 * @var int
	 */
	const POOL_TYPE_ADDTAG = 'addTag';
	
	/**
	 * 操作池类型：写解析
	 *
	 * @var int
	 */
	const POOL_TYPE_ANALYZE_EDIT = 'analyzeEdit';
	
	/**
	 * 操作池类型：排重
	 *
	 * @var int
	 */
	const POOL_TYPE_REPLACE = 'repeat';
	
	/**
	 * 操作池类型：题目反馈搜索
	 *
	 * @var int
	 */
	const POOL_TYPE_FEEDBACK = 'feedback';
	
	/**
	 * 操作池类型：题目导入
	 *
	 * @var int
	 */
	const POOL_TYPE_QUESTIONEXPORT = 'questionExport';
}