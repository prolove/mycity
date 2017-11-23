<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\validate;
use think\Validate;
/**
 *  配置验证模型
 */
class Config extends Base {
    // 验证规则
    protected $rule = [
        ['name', 'require|unique:config', '标识不能为空|标识已经存在'],
        ['title', 'require', '名称不能为空']
    ];

}