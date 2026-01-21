<?php
namespace constant;

/**
 * 用户	常量
 * 
 * @author wangwei
 */
class User 
{ 
// 权限类型 

	/**
	 * 类型：账号管理
	 *
	 * @var int
	 */
	const PRIVILEGE_ACCOUNT_MANAGER = '账号管理';

// 用户状态
	/**
	 * 用户状态：正常
	 *
	 * @var int
	 */
	const USER_STATUS_NORMAL = 0;
	
	/**
	 * 用户状态：禁用
	 *
	 * @var int
	 */
	const USER_STATUS_CLOSURE = 1;
}