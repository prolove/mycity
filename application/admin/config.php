<?php
return [
    // +----------------------------------------------------------------------
    // | 模型属性类型信息
    // +----------------------------------------------------------------------
    'attribute_type' =>[
        'num'       =>  ['数字','int(10) UNSIGNED NOT NULL'],
        'string'    =>  ['字符串','varchar(255) NOT NULL'],
        'textarea'  =>  ['文本框','text NOT NULL'],
        'date'      =>  ['日期','int(10) NOT NULL'],
        'datetime'  =>  ['时间','int(10) NOT NULL'],
        'bool'      =>  ['布尔','tinyint(2) NOT NULL'],
        'select'    =>  ['枚举','char(50) NOT NULL'],
        'radio'     =>  ['单选','char(10) NOT NULL'],
        'checkbox'  =>  ['多选','varchar(100) NOT NULL'],
        'editor'    =>  ['编辑器','text NOT NULL'],
        'picture'   =>  ['上传图片','int(10) UNSIGNED NOT NULL'],
        'file'      =>  ['上传附件','int(10) UNSIGNED NOT NULL'],
        'pice'      =>  ['价格','decimal(5,2) NOT NULL'],
        'function'  =>  ['函数','int(10) UNSIGNED NOT NULL'],
        'hook'      =>  ['插件','int(10) UNSIGNED NOT NULL'],
        'disabled'  =>  ['不可编辑','int(10) UNSIGNED NOT NULL'],
    ],
    // +----------------------------------------------------------------------
    // | 模型生成 表字段转模型属性信息 (字段类型=>对应上面的属性配置下标)
    // +----------------------------------------------------------------------
    'attribute_from_type' =>[
        'int'       => 'num',
        'varchar'   => 'string',
        'text'      => 'textarea',
        'tinyint'   => 'bool',
        'char'      => 'select',
        'decimal'   => 'pice',
        'smallint'  => 'string'
    ], 
    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------
    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => 'session_id',
        // SESSION 前缀
        'prefix'         => 'twothink_admin',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],
    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => 'twothink_admin_',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
    // +----------------------------------------------------------------------
    // | 编辑器图片上传相关配置
    // +----------------------------------------------------------------------
    'editor_upload' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub'  => true, //自动子目录保存文件
        'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './static/uploads/editor/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => false, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ),

];
