<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络 
// +----------------------------------------------------------------------

namespace app\admin\model;

/**
 * 导航模型 
 */

class Channel extends Base {
    protected $insert = ['status'=>1];
    /**
     * 获取导航详细信息
     * @param  milit   $id 导航ID
     * @param  boolean $field 查询字段
     * @return array     分类信息
     */
    public function info($id, $field = true){
        $map['id'] = $id;
        return $this->field($field)->where($map)->find()->toArray();
    }
    /**
     * 获取导航树，指定导航则返回指定分类极其子导航，不指定则返回所有导航树
     * @param  integer $id    导航ID
     * @param  boolean $field 查询字段
     * @return array          导航树
     */
    public function getTree($id = 0, $field = true){
        /* 获取当前分类信息 */
        if($id){
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有分类 */
        $map  = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('sort')->select();
        if(is_object($list))
            $list = $list->toArray();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);

        /* 获取返回数据 */
        if(isset($info)){ //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }
}
