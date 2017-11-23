<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\common\behavior;

use think\Config;
use think\Db;
use think\Hook;

// 初始化钩子信息
class InitHook{

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        // 获取系统配置
        $data = Config::get('app_debug') ? [] : cache('hooks');
        if (empty($data)) {
            $hooks = Db::name('Hooks')->column('name,addons');
            foreach ($hooks as $key => $value) {
                if($value){
                    $map['status']  =   1;
                    $names          =   explode(',',$value);
                    $map['name']    =   array('IN',$names);
                    $data = Db::name('Addons')->where($map)->column('id,name');
                    if($data){
                        $addons_arr = array_intersect($names, $data);
                        $addons[$key] = array_map('get_addon_class',$addons_arr); 
                        Hook::add($key,$addons[$key]);
                    }
                }
            }
            cache('hooks',$addons);
        } else {
            Hook::import($data, false);
        }
    }
}