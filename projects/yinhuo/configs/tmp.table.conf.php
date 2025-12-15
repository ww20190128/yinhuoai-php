<?php
return array (
  'desktop' => 
  array (
    'department' => 
    array (
      'primary' => 
      array (
        0 => 'id',
      ),
      'indexStr' => 
      array (
        'pid' => 'parentId',
      ),
      'indexArr' => 
      array (
        'pid' => 
        array (
          0 => 'parentId',
        ),
        'PRIMARY' => 
        array (
          0 => 'id',
        ),
      ),
      'column' => 
      array (
        'id' => NULL,
        'name' => '',
        'leaderUserId' => '0',
        'parentId' => '0',
        'index' => '0',
        'status' => '0',
        'createTime' => '0',
      ),
      'comment' => 
      array (
        'id' => '主键id',
        'name' => '部门名称',
        'leaderUserId' => '部门负责人id',
        'parentId' => '父id',
        'index' => '次序',
        'status' => '状态',
        'createTime' => '创建时间',
      ),
      'fieldIndexInfo' => 
      array (
        'id' => 
        array (
          0 => 'PRIMARY',
        ),
        'parentId' => 
        array (
          0 => 'pid',
        ),
      ),
    ),
    'departmentUser' => 
    array (
      'primary' => 
      array (
        0 => 'id',
      ),
      'indexStr' => 
      array (
        'departmentUser' => 'departmentId,userId',
        'departmentId' => 'departmentId',
        'userId' => 'userId',
      ),
      'indexArr' => 
      array (
        'departmentUser' => 
        array (
          0 => 'departmentId',
          1 => 'userId',
        ),
        'departmentId' => 
        array (
          0 => 'departmentId',
        ),
        'userId' => 
        array (
          0 => 'userId',
        ),
        'PRIMARY' => 
        array (
          0 => 'id',
        ),
      ),
      'column' => 
      array (
        'id' => NULL,
        'departmentId' => '0',
        'userId' => NULL,
        'isLeader' => NULL,
      ),
      'comment' => 
      array (
        'id' => '主键ID',
        'departmentId' => '部门ID',
        'userId' => '用户ID',
        'isLeader' => '是否是部门管理员：0.不是  1.是',
      ),
      'fieldIndexInfo' => 
      array (
        'id' => 
        array (
          0 => 'PRIMARY',
        ),
        'departmentId' => 
        array (
          0 => 'departmentUser',
          1 => 'departmentId',
        ),
        'userId' => 
        array (
          0 => 'departmentUser',
          1 => 'userId',
        ),
      ),
    ),
    'map_taskType' => 
    array (
      'primary' => 
      array (
        0 => 'id',
      ),
      'indexStr' => 
      array (
      ),
      'indexArr' => 
      array (
        'PRIMARY' => 
        array (
          0 => 'id',
        ),
      ),
      'column' => 
      array (
        'id' => NULL,
        'name' => '0',
        'createTime' => '0',
      ),
      'comment' => 
      array (
        'id' => '主键id',
        'name' => '类型',
        'createTime' => '创建时间',
      ),
      'fieldIndexInfo' => 
      array (
        'id' => 
        array (
          0 => 'PRIMARY',
        ),
      ),
    ),
    'task' => 
    array (
      'primary' => 
      array (
        0 => 'id',
      ),
      'indexStr' => 
      array (
      ),
      'indexArr' => 
      array (
        'PRIMARY' => 
        array (
          0 => 'id',
        ),
      ),
      'column' => 
      array (
        'id' => NULL,
        'status' => '0',
        'type' => '0',
        'parentId' => '0',
        'title' => '',
        'desc' => NULL,
        'executeUserIds' => '0',
        'followUserIds' => '',
        'teamId' => '0',
        'deadline' => '0',
        'completionTime' => '0',
        'createUserId' => '0',
        'createTime' => '0',
      ),
      'comment' => 
      array (
        'id' => '主键id',
        'status' => '状态 ',
        'type' => '类型',
        'parentId' => '父任务id',
        'title' => '标题',
        'desc' => '描述',
        'executeUserIds' => '执行者用户id，多个逗号分割',
        'followUserIds' => '关注者用户id',
        'teamId' => '所属项目组id',
        'deadline' => '预计截止时间',
        'completionTime' => '实际完成时间',
        'createUserId' => '创建者用户id',
        'createTime' => '创建时间',
      ),
      'fieldIndexInfo' => 
      array (
        'id' => 
        array (
          0 => 'PRIMARY',
        ),
      ),
    ),
    'taskAttachment' => 
    array (
      'primary' => 
      array (
        0 => 'id',
      ),
      'indexStr' => 
      array (
      ),
      'indexArr' => 
      array (
        'PRIMARY' => 
        array (
          0 => 'id',
        ),
      ),
      'column' => 
      array (
        'id' => NULL,
        'taskId' => '0',
        'file' => '',
        'createTime' => '0',
      ),
      'comment' => 
      array (
        'id' => '主键id',
        'taskId' => '任务id',
        'file' => '文件',
        'createTime' => '创建时间',
      ),
      'fieldIndexInfo' => 
      array (
        'id' => 
        array (
          0 => 'PRIMARY',
        ),
      ),
    ),
    'user' => 
    array (
      'primary' => 
      array (
        0 => 'userId',
      ),
      'indexStr' => 
      array (
        'userName' => 'userName',
        'status' => 'status',
        'phone' => 'phone',
      ),
      'indexArr' => 
      array (
        'userName' => 
        array (
          0 => 'userName',
        ),
        'status' => 
        array (
          0 => 'status',
        ),
        'phone' => 
        array (
          0 => 'phone',
        ),
        'PRIMARY' => 
        array (
          0 => 'userId',
        ),
      ),
      'column' => 
      array (
        'userId' => NULL,
        'userName' => '',
        'status' => '0',
        'icon' => '',
        'phone' => '',
        'password' => '',
        'departmentId' => '',
        'createUserId' => '0',
        'loginKey' => '',
        'lastLoginTime' => '0',
        'createTime' => '0',
      ),
      'comment' => 
      array (
        'userId' => '用户id',
        'userName' => '用户名',
        'status' => '状态 0 正常 1 禁用',
        'icon' => '头像',
        'phone' => '手机号',
        'password' => '密码',
        'departmentId' => '部门ID',
        'createUserId' => '创建者用户id',
        'loginKey' => '最近一次登录的key',
        'lastLoginTime' => '最近一次登录时间',
        'createTime' => '创建时间',
      ),
      'fieldIndexInfo' => 
      array (
        'userId' => 
        array (
          0 => 'PRIMARY',
        ),
        'userName' => 
        array (
          0 => 'userName',
        ),
        'status' => 
        array (
          0 => 'status',
        ),
        'phone' => 
        array (
          0 => 'phone',
        ),
      ),
    ),
  ),
);
