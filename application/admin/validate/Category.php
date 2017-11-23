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
*  分类验证模型
*/
class Category extends Validate{
    // 验证规则
    protected $rule = [
        ['name', 'require|unique:category', '标识不能为空|标识已经存在'],
        ['title', 'require', '名称不能为空'],
        ['meta_title', 'length:1,50', '网页标题不能超过50个字符'],
        ['keywords', 'length:1,255', '网页关键字不能超过255个字符'],
        ['description', 'length:1,255', '网页描述不能超过255个字符'],
    ];  
    protected $scene = array(
        'edit'     => ['name'=>'unique:category','title','meta_title','keywords','description'],//更新
        // 'password' => 'password,repassword'
    );

}