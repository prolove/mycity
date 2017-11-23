<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络 82550565@qq.com <http://www.twothink.cn>
// +----------------------------------------------------------------------

/**
 * 安装程序配置文件
 */

define('INSTALL_APP_PATH', realpath('./') . '/');

return array(
    'ORIGINAL_TABLE_PREFIX' => 'twothink_', //默认表前缀
    // +----------------------------------------------------------------------
    // | 模板替换
    // +----------------------------------------------------------------------
    'view_replace_str'  =>  [
    		'__PUBLIC__'=> '/static',
    		'__STATIC__' => '/static/static',
    		'__IMG__'    => '/static/install/images',
    		'__CSS__'    => '/static/install/css',
    		'__JS__'     => '/static/install/js',
    ],

    'app_init'=>[],

);
