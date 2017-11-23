<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
use think\Db;
/*
 * 列表定义函数集
 */
/********Confing********/
function config_group_list(){
    $data[0] = '不分组';
    $data += config('config_group_list');
    return $data;
}
function config_type_list(){
    return config('config_type_list');
}
/********Model********/
//获取所有的模型
function Field_models(){
    $models = Db::name('Model')->where(['extend'=>0])->field('id,title')->select();
    $data[0] = '独立模型';
    $data += Array_mapping($models,'id','title');
    return $data;
}
//获取所有数据表
function Field_ModelTables(){
    $tables = model('Modelmodel')->getTables();
    $tables = array_combine($tables,$tables);
    return $tables?:[];
}
/********User********/
//启用 禁用
function user_status($data=[]){
    if(!$data)
        return false;
    switch ($data['status']){
        case 1 :
            $data['method'] = 'forbiduser';
            $title = '禁用';
            break;
        default :
            $data['method'] = 'resumeuser';
            $title = '启用';
            break;
    }
    return "<a href='".url('changestatus',['method'=>$data['method'],'id'=>$data['id']])."' class=\"ajax-get caozuo_status".$data['status']."\">".$title."</a>";
}
/********Category********/
//列表绑定文档模型
function Field_get_document_model(){
    $data = get_document_model();
    $data = Array_mapping($data,'id','title');
    return $data;
}
/* 获取上级分类信息 */
function Field_category_title($pid=null){
    $pid = !empty($pid)? $pid:request()->param('pid');
    if(empty($pid)){
        return '无';
    }
    $data = model('Category')->info($pid, 'id,name,title,status');
    return $data?$data['title']:'无';
}
/********menu********/
function Field_menu_tree(){
    $menus = Db::name('Menu')->field(true)->select();
    $menus = model('Common/Tree')->toFormatTree($menus);
    $menus = array_merge(array(0=>array('id'=>0,'title_show'=>'顶级菜单')), $menus);
    return Array_mapping($menus,'id','title_show');
}