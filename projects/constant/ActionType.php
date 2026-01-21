<?php
namespace constant;

/**
 * 行为类型
 *
 * @author wangwei
 */
class ActionType
{
	/**
	 * 类型：操作恢复
	 *
	 * @var int
	 */
	const TYPE_LOG_RECOVER = 999;
	
	/**
	 * 类型：账号-登录(done)
	 *
	 * @var int
	 */
	const TYPE_USER_LOGIN = 101;
	
	/**
	 * 类型：账号-退出登录(done)
	 * 
	 * @var int
	 */
	const TYPE_USER_LOGOUT = 102;
	
	/**
	 * 类型：账号-创建(done)
	 * 
	 * @var int
	 */
	const TYPE_USER_CREATE = 103;
	
	/**
	 * 类型：账号-删除(done)
	 *  
	 * @var int
	 */
	const TYPE_USER_DELETE = 104;
	
	/**
	 * 类型：账号-修改(done)
	 *  
	 * @var int
	 */
	const TYPE_USER_REVISE = 105;
	
	/**
	 * 类型：任务-指派(done)
	 * 
	 * @var int
	 */
	const TYPE_USER_APPOINT = 201;
	
	/**
	 * 类型：题目-修改(done)
	 *
	 * @var int
	 */
	const TYPE_QUESTION_REVISE = 301;
	
	/**
	 * 类型：题目-删除(done)
	 *  
	 * @var int
	 */
	const TYPE_QUESTION_DELETE = 302;
	
	/**
	 * 类型：题目-移除标签
	 *
	 * @var int
	 */
	const TYPE_QUESTION_REMOVE_TAG = 303;
	
	/**
	 * 类型：题目-添加标签
	 * 
	 * @var int
	 */
	const TYPE_QUESTION_ADD_TAG = 304;
	
	/**
	 * 类型：题目-批量操作标签
	 *
	 * @var int
	 */
	const TYPE_QUESTION_BATCH_TAG = 305;
	
	/**
	 * 类型：题目-材料-修改
	 *  
	 * @var int
	 */
	const TYPE_MATERIAL_REVISE_QUESTION = 308;
	
	/**
	 * 类型：题目-排重
	 *
	 * @var int
	 */
	const TYPE_QUESTION_DOREPEAT = 309;
//== 抓取的题目
	/**
	 * 类型：采集-记录-删除
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_RECORD_DELETE = 401;
	
	/**
	 * 类型：采集-题目-删除
	 * 
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_DELETE = 402;
	
	/**
	 * 类型：采集-题目-修改
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_REVISE = 403;
	
	/**
	 * 类型：采集-记录-修改
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_RECORD_REVISE = 404;
	
	/**
	 * 类型：采集-记录-创建
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_RECORD_CREATE = 405;
	
	/**
	 * 类型：采集-题目-创建
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GRAB_CREATE = 406;
	
//== 题组
	/**
	 * 类型：题组-创建
	 * 
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_CREATE = 501;
	
	/**
	 * 类型：题组-修改
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_REVISE = 502;
	
	/**
	 * 类型：题组-删除
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_DELETE = 503;
	
	/**
	 * 类型：题组-复制
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_COPY = 504;
	
	/**
	 * 类型：题组-题目移出题组
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_REMOVE_QUESTION = 505;
	
	/**
	 * 类型：题组-将题目加入题组
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_ADD_QUESTION = 506;
	
	/**
	 * 类型：题组-调整题序
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_CHANGE_INDEX = 507;

	/**
	 * 类型：题组-试卷转换为题组
	 *
	 * @var int
	 */
	const TYPE_QUESTION_GROUP_FROM_EXAMPAPER = 508;
// 试卷
	/**
	 * 类型：试卷-创建
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_CREATE = 601;
	
	/**
	 * 类型：试卷-修改
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_REVISE = 602;
	
	/**
	 * 类型：试卷-删除
	 * 
	 * @var int
	 */
	const TYPE_EXAMPAPER_DELETE = 603;
	
	/**
	 * 类型：试卷-题目移出试卷
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_REMOVE_QUESTION = 604;
	
	/**
	 * 类型：试卷-将题目加入试卷
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_ADD_QUESTION = 605;
	
	/**
	 * 类型：试卷-调整题序
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_CHANGE_INDEX = 606;
	
	/**
	 * 类型：试卷-调整分数
	 * 
	 * @var int
	 */
	const TYPE_EXAMPAPER_CHANGE_SCORE = 607;
	
	/**
	 * 类型：试卷-题目分类修改
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_CHANGE = 608;
	
	/**
	 * 类型：试卷-复制
	 *
	 * @var int
	 */
	const TYPE_EXAMPAPER_COPY = 609;
	
// 知识点
	/**
	 * 类型：知识点-创建
	 *  
	 * @var int
	 */
	const TYPE_KNOWLEDE_CREATE = 701;
	
	/**
	 * 类型：知识点-删除
	 *  
	 * @var int
	 */
	const TYPE_KNOWLEDE_DELETE = 702;
	
	/**
	 * 类型：知识点-修改
	 *
	 * @var int
	 */
	const TYPE_KNOWLEDE_REVISE = 703;
	
	/**
	 * 类型：学科-修改
	 *  
	 * @var int
	 */
	const TYPE_KNOWLEDE_TREE_REVISE = 704;
	
// 标签
	/**
	 * 类型：标签-创建
	 *
	 * @var int
	 */
	const TYPE_TAG_CREATE = 801;
	
	/**
	 * 类型：标签-删除
	 *
	 * @var int
	 */
	const TYPE_TAG_DELETE = 802;
	
	/**
	 * 类型：标签-修改
	 *  
	 * @var int
	 */
	const TYPE_TAG_REVISE = 803;
	
	/**
	 * 类型：标签树-修改
	 *  
	 * @var int
	 */
	const TYPE_TAG_TREE_REVISE = 804;
	
	/**
	 * 类型：标签树-创建
	 *  
	 * @var int
	 */
	const TYPE_TAG_TREE_CREATE = 805;
	
	/**
	 * 类型：标签树-删除
	 * 
	 * @var int
	 */
	const TYPE_TAG_TREE_DELETE = 806;
	
	/**
	 * 类型：标签-批量导入
	 * 
	 * @var int
	 */
	const TYPE_TAG_IMPORT = 807;
	
// 导出
	/**
	 * 类型：导出-题目-excel
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_QUESTION_EXCEL = 901;
	
	/**
	 * 类型：导出-试卷-ppt
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_EXAM_PPT = 911;
	
	/**
	 * 类型：导出-试卷-word
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_EXAM_WORD = 912;
	
	/**
	 * 类型：导出-试卷-Excel
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_EXAM_EXCEL = 913;
	
	/**
	 * 类型：导出-题组-ppt
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_GROUP_PPT = 921;
	
	/**
	 * 类型：导出-题组-word
	 * 
	 * @var int
	 */
	const TYPE_EXPORT_GROUP_WORD = 922;
	
	/**
	 * 类型：导出-题组-Excel
	 *
	 * @var int
	 */
	const TYPE_EXPORT_GROUP_EXCEL = 923;

    /**
     * 类型：导出-课件-Excel
     *
     * @var int
     */
    const TYPE_EXPORT_COURSEWARE_EXCEL = 924;
    
    
    /**
     * 类型：导出-课件-ppt
     *
     * @var int
     */
    const TYPE_EXPORT_COURSEWARE_PPT = 21021;

	public static $typeMap = array(
	    \constant\ActionType::TYPE_USER_LOGIN => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_LOGIN,
	        'name' 		=> '账号-登录',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_USER_LOGOUT => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_LOGOUT,
	        'name' 		=> '账号-注销',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_USER_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_CREATE,
	        'name' 		=> '账号-创建',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_USER_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_DELETE,
	        'name' 		=> '账号-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_USER_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_REVISE,
	        'name' 		=> '账号-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_USER_APPOINT => array(
	        'id' 		=> \constant\ActionType::TYPE_USER_APPOINT,
	        'name' 		=> '任务-指派',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_REVISE,
	        'name' 		=> '题目-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_DELETE,
	        'name' 		=> '题目-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_REMOVE_TAG => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_REMOVE_TAG,
	        'name' 		=> '题目-移除标签',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_ADD_TAG => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_ADD_TAG,
	        'name' 		=> '题目-添加标签',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_BATCH_TAG => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_BATCH_TAG,
	        'name' 		=> '题目-批量操作标签',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_MATERIAL_REVISE_QUESTION => array(
	        'id' 		=> \constant\ActionType::TYPE_MATERIAL_REVISE_QUESTION,
	        'name' 		=> '题目-修改材料',
	        'recover'	=> 0,
	    ),
		\constant\ActionType::TYPE_QUESTION_DOREPEAT => array(
			'id' 		=> \constant\ActionType::TYPE_QUESTION_DOREPEAT,
			'name' 		=> '题目-排重',
			'recover'	=> 0,
		),
	    \constant\ActionType::TYPE_QUESTION_GRAB_RECORD_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GRAB_RECORD_REVISE,
	        'name' 		=> '采集-记录-修改',
	        'recover'	=> 0,
	    ),
		\constant\ActionType::TYPE_QUESTION_GRAB_RECORD_CREATE => array(
			'id' 		=> \constant\ActionType::TYPE_QUESTION_GRAB_RECORD_CREATE,
			'name' 		=> '采集-记录-创建',
			'recover'	=> 0,
		),
	    \constant\ActionType::TYPE_QUESTION_GRAB_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GRAB_REVISE,
	        'name' 		=> '采集-题目-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GRAB_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GRAB_DELETE,
	        'name' 		=> '采集-题目-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GRAB_RECORD_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GRAB_RECORD_DELETE,
	        'name' 		=> '采集-记录-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_CREATE,
	        'name' 		=> '题组-创建',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_REVISE,
	        'name' 		=> '题组-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_DELETE,
	        'name' 		=> '题组-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_COPY => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_COPY,
	        'name' 		=> '题组-复制',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_REMOVE_QUESTION => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_REMOVE_QUESTION,
	        'name' 		=> '题组-将题目移出题组',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_ADD_QUESTION => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_ADD_QUESTION,
	        'name' 		=> '题组-将题目加入题组',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_QUESTION_GROUP_CHANGE_INDEX => array(
	        'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_CHANGE_INDEX,
	        'name' 		=> '题组-调整题序',
	        'recover'	=> 0,
	    ),
		\constant\ActionType::TYPE_QUESTION_GROUP_FROM_EXAMPAPER => array(
			'id' 		=> \constant\ActionType::TYPE_QUESTION_GROUP_FROM_EXAMPAPER,
			'name' 		=> '题组-来自试卷转换',
			'recover'	=> 0,
		),
	    \constant\ActionType::TYPE_EXAMPAPER_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_CREATE,
	        'name' 		=> '试卷-创建试卷',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_REVISE,
	        'name' 		=> '试卷-修改试卷',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_DELETE,
	        'name' 		=> '试卷-删除试卷',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_REMOVE_QUESTION => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_REMOVE_QUESTION,
	        'name' 		=> '试卷-将题目移出试卷',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_ADD_QUESTION => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_ADD_QUESTION,
	        'name' 		=> '试卷-将题目加入试卷',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_CHANGE_INDEX => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_CHANGE_INDEX,
	        'name' 		=> '试卷-调整题序',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_CHANGE_SCORE => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_CHANGE_SCORE,
	        'name' 		=> '试卷-调整分数',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXAMPAPER_CHANGE => array(
	        'id' 		=> \constant\ActionType::TYPE_EXAMPAPER_CHANGE,
	        'name' 		=> '试卷-题目分类修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_KNOWLEDE_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_KNOWLEDE_CREATE,
	        'name' 		=> '知识点-创建',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_KNOWLEDE_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_KNOWLEDE_DELETE,
	        'name' 		=> '知识点-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_KNOWLEDE_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_KNOWLEDE_REVISE,
	        'name' 		=> '知识点-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_KNOWLEDE_TREE_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_KNOWLEDE_TREE_REVISE,
	        'name' 		=> '学科-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_CREATE,
	        'name' 		=> '标签-创建',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_DELETE,
	        'name' 		=> '标签-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_REVISE,
	        'name' 		=> '标签-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_TREE_REVISE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_TREE_REVISE,
	        'name' 		=> '标签树-修改',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_TREE_CREATE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_TREE_CREATE,
	        'name' 		=> '标签树-创建',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_TREE_DELETE => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_TREE_DELETE,
	        'name' 		=> '标签树-删除',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_TAG_IMPORT => array(
	        'id' 		=> \constant\ActionType::TYPE_TAG_IMPORT,
	        'name' 		=> '标签-批量导入',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_QUESTION_EXCEL => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_QUESTION_EXCEL,
	        'name' 		=> '导出-题目-excel',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_EXAM_PPT => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_EXAM_PPT,
	        'name' 		=> '导出-试卷-ppt',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_EXAM_WORD => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_EXAM_WORD,
	        'name' 		=> '导出-试卷-word',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_EXAM_EXCEL => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_EXAM_EXCEL,
	        'name' 		=> '导出-试卷-Excel',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_GROUP_PPT => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_GROUP_PPT,
	        'name' 		=> '导出-题组-ppt',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_GROUP_WORD => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_GROUP_WORD,
	        'name' 		=> '导出-题组-word',
	        'recover'	=> 0,
	    ),
	    \constant\ActionType::TYPE_EXPORT_GROUP_EXCEL => array(
	        'id' 		=> \constant\ActionType::TYPE_EXPORT_GROUP_EXCEL,
	        'name' 		=> '导出-题组-Excel',
	        'recover'	=> 0,
	    ),
        \constant\ActionType::TYPE_EXPORT_COURSEWARE_EXCEL => array(
            'id' 		=> \constant\ActionType::TYPE_EXPORT_COURSEWARE_EXCEL,
            'name' 		=> '导出-课件-Excel',
            'recover'	=> 0,
        ),
		\constant\ActionType::TYPE_EXPORT_COURSEWARE_PPT => array(
			'id' 		=> \constant\ActionType::TYPE_EXPORT_COURSEWARE_PPT,
			'name' 		=> '导出-课件-PPT',
			'recover'	=> 0,
		),
	);
}