<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
use think\Db;
include('field.php'); 
/**
 * 实例化模型分层
 * @param string    $name Model名称
 * @param string    $layer 业务层名称
 * @param string    $default 默认模型
 * @auth 小矮人  82550565@qq.com
 */
function Model_hierarchy($name = '', $layer = 'model', $default = 'Base')
{
    if(!$name)
        return false;
    //判断模型是否存在
    $module = request()->module();

    $class = \think\Loader::parseClass($module, $layer, $name, config('class_suffix'));
    if (!class_exists($class)) {
        $class = \think\Loader::parseClass('common', $layer, $name, config('class_suffix'));
        if(empty($default))
            return false;
        if (!class_exists($class)) {
            $class = \think\Loader::parseClass($module, $layer, $default, config('class_suffix'));
            if(!class_exists($class)){
                $class = 'app\common\\'.$layer.'\\'.$default;
            }
            return new $class(['twothink_name'=>$name]);
        }else{
            return model($name,$layer);
        }
    }else{
        return model($name,$layer);
    }
}
/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 * @auth 小矮人  82550565@qq.com
 */
function get_config_type($type=0){
    $list = config('config_type_list');
    return $list[$type];
}
/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 * @auth 小矮人  82550565@qq.com
 */
function get_config_group($group=0){
    $list = config('config_group_list');
    return $group?$list[$group]:'';
}
/*
 * 数组转字符串 下标:值,下标:值
 * @param  string  $str1 key value 连接符
 * @param  string  $str2 数据连接符
 */
function arraykvstring($array,$str1 = ':',$str2 = ','){
    $string = [];
    if($array && is_array($array)){
        foreach ($array as $key=> $value){
            $string[] = $key.$str1.$value;
        }
    }

    return implode($str2,$string);
}
// 获取模型名称
function get_model_by_id($id){
    return $model = \think\Db::name('Model')->where('id',$id)->value('title');
}

// 获取属性类型信息
function get_attribute_type($type=''){
    $_type = config('attribute_type');
    return $type?$_type[$type][0]:$_type;
}
/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @author 艺品网络  <twothink.cn>
 */
function get_action($id = null, $field = null){
    if(empty($id) && !is_numeric($id)){
        return false;
    }
    $list = cache('action_list');
    if(empty($list[$id])){
        $map = array('status'=>array('gt', -1), 'id'=>$id);
        $list[$id] = db('Action')->where($map)->field(true)->find();
    }
    return empty($field) ? $list[$id] : $list[$id][$field];
}
/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 艺品网络  <twothink.cn>
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 获取参数的所有父级分类
 * @param int $cid 分类id
 * @return array 参数分类和父类的信息集合
 * @author 艺品网络  <twothink.cn>
 */
function get_parent_category($cid){
    if(empty($cid)){
        return false;
    }
    $cates  =   db('Category')->where(array('status'=>1))->field('id,title,pid')->order('sort')->select();
    $child  =   get_category($cid); //获取参数分类的信息
    $pid    =   $child['pid'];
    $temp   =   array();
    $res[]  =   $child;
    while(true){
        foreach ($cates as $key=>$cate){
            if($cate['id'] == $pid){
                $pid = $cate['pid'];
                array_unshift($res, $cate); //将父分类插入到数组第一个元素前
            }
        }
        if($pid == 0){
            break;
        }
    }
    return $res;
}
/**
 * 动态扩展左侧菜单,base.html里用到 * @author 艺品网络 <twothink.cn>
 */
function extra_menu($extra_menu,&$base_menu){
    foreach ($extra_menu as $key=>$group){
        if( isset($base_menu['child'][$key]) ){
            $base_menu['child'][$key] = array_merge( $base_menu['child'][$key], $group);
        }else{
            $base_menu['child'][$key] = $group;
        }
    }
}
/**
 * 验证分类是否允许发布内容
 * @param  integer $id 分类ID
 * @return boolean     true-允许发布内容，false-不允许发布内容
 */
function check_category($id){
    if (is_array($id)) {
        $id['type']	=	!empty($id['type'])?$id['type']:2;
        $type = get_category($id['category_id'], 'type');
        $type = explode(",", $type);
        return in_array($id['type'], $type);
    } else {
        $publish = get_category($id, 'allow_publish');
        return $publish ? true : false;
    }
}

/**
 * 检测分类是否绑定了指定模型
 * @param  array $info 模型ID和分类ID数组
 * @return boolean     true-绑定了模型，false-未绑定模型
 */
function check_category_model($info){
    $cate   =   get_category($info['category_id']);
    $array  =   explode(',', $info['pid'] ? $cate['model_sub'] : $cate['model']);
    return in_array($info['model_id'], $array);
}
/**
 * 获取属性信息并缓存
 * @param  integer $id    属性ID
 * @param  string  $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true,$fields=true){
    static $list;
    /* 非法ID */
    if(empty($model_id) || !is_numeric($model_id)){
        return '';
    }
    /* 获取属性 */
    if(!isset($list[$model_id])){
        $map = array('model_id'=>$model_id);
        $extend = Db::name('Model')->getFieldById($model_id,'extend');

        if($extend){
            $map = array('model_id'=> array("in", array($model_id, $extend)));
        }
        $info = Db::name('Attribute')->where($map)->field($fields)->select();
        $list[$model_id] = $info;
    }

    $attr = array();
    if($group){
        foreach ($list[$model_id] as $value) {
            $attr[$value['id']] = $value;
        }
        $model     = Db::name("Model")->field("field_sort,attribute_list,attribute_alias")->find($model_id);
        $attribute = explode(",", $model['attribute_list']);
        if (empty($model['field_sort'])) { //未排序
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($model['field_sort'], true);
            $keys = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                foreach ($attr as $key => $val) {
                    if (!in_array($val['id'], $attribute)) {
                        unset($attr[$key]);
                    }
                }
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        if (!empty($model['attribute_alias'])) {
            $alias  = preg_split('/[;\r\n]+/s', $model['attribute_alias']);
            $fields = array();
            foreach ($alias as &$value) {
                $val             = explode(':', $value);
                $fields[$val[0]] = $val[1];
            }
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    if (!empty($fields[$val['name']])) {
                        $value[$key]['title'] = $fields[$val['name']];
                    }
                }
            }
        }
        $attr = $group;
    }else{
        foreach ($list[$model_id] as $value) {
            $attr[$value['name']] = $value;
        }
    }
    return $attr;
}
/**
 * 获取当前分类的文档类型
 * @param int $id
 * @return array 文档类型数组
 * @author 艺品网络  <twothink.cn>
 */
function get_type_bycate($id = null){
    if(empty($id)){
        return false;
    }
    $type_list  =   config('document_model_type');
    $model_type =   db('Category')->getFieldById($id, 'type');
    $model_type =   explode(',', $model_type);
    foreach ($type_list as $key=>$value){
        if(!in_array($key, $model_type)){
            unset($type_list[$key]);
        }
    }
    return $type_list;
}
/**
 * 获取数据的所有子孙数据的id值
 * @author 艺品网络  <twothink.cn>
 *
 */

//TODO 该方法暂未实现递归查询，但不影响使用。
function get_stemma($pids,$model, $field='id'){
    $collection = array();

    //非空判断
    if(empty($pids)){
        return $collection;
    }

    if( is_array($pids) ){
        $pids = trim(implode(',',$pids),',');
    }
    $result     = $model->field($field)->where(array('pid'=>array('IN',(string)$pids)))->select();
    $child_ids  = array_column ((array)$result,'id');
    while( !empty($child_ids) ){
        $collection = array_merge($collection,$result);
        $result     = $model->field($field)->where( array( 'pid'=>array( 'IN', $child_ids ) ) )->select();
        $child_ids  = array_column((array)$result,'id');
    }
    return $collection ? $collection : [];
}
//基于数组创建目录和文件
function create_dir_or_files($files){
    if(is_dir($files[0]))
        return false;
    foreach ($files as $key => $value) {
        if(substr($value, -1) == '/'){
            mkdir($value);
        }else{
            @file_put_contents($value, '');
        }
    }
    return true;
}
/* 查询插件的钩子 */
function  addons_hook($name,$field = true){
    $data = Db::name('Hooks')->cache(false)->field($field)->where(['addons'=>['like','%'.$name.'%']])->find();
    return $data;
}