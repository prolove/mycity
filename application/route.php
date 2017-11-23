<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// use think\Route;
// Route::domain('admin.whcrh.zzy','admin');
// Route::domain('whcrh.zzy','home');
return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

    // '[index]'     => [
    //     ':name' => ['Index/:name'],
    //     '' => ['Index/index'],
    // ],

    // '[:controller]'     => [
    //     ':name' => [':controller/:name'],
    //     '' => [':controller/index'],
    // ],


    '[xinwen]'     => [
        ':name' => ['Xinwen/:name'],
        '' => ['Xinwen/index'],
    ],
    '[wechat]'     => [
        ':name' => ['Wechat/:name'],
        '' => ['Wechat/index'],
    ],
    '[moments]'     => [
        ':name' => ['Moments/:name'],
        '' => ['Moments/index'],
    ],
    '[weibo]'     => [
        ':name' => ['Weibo/:name'],
        '' => ['Weibo/index'],
    ],
    '[netred]'     => [
        ':name' => ['Netred/:name'],
        '' => ['Netred/index'],
    ],
    '[netred]'     => [
        ':name' => ['Netred/:name'],
        '' => ['Netred/index'],
    ],



    '[about]'     => [
        ':name' => ['About/:name'],
        '' => ['About/index'],
    ],
    '[service]'     => [
        ':name' => ['Service/:name'],
        '' => ['Service/index'],
    ],
    '[map]'     => [
        ':name' => ['Map/:name'],
        '' => ['Map/index'],
    ],
    '[news]'     => [
        ':name' => ['News/:name'],
        '' => ['News/index'],
    ],


    'home/addexe/:_addons/:_controller/:_action' => 'home/addons/execute',//插件执行路由
    'admin/addexe/:_addons/:_controller/:_action' => 'admin/addons/execute',//插件执行路由
];
