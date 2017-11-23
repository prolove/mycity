<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 小矮人  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\common\model;
use think\Model;
use think\Request;

/**
 * 插件基类
 */
class AddonsBase extends Model {
    protected $autoWriteTimestamp = false; // 关闭自动写入时间戳字段
    /**
     * 获取详细信息
     * @param  array    $where
     * @return array       当前数据详细信息
     */
    public function detail($where) {
        $data = $this->field(true)->where($where)->find();
        if (is_object($data))
            $data = $data->toArray();
        return $data;
    }
    /**
     * 新增或添加数据
     * @param  array  $data 请求数据
     * @param  number $id 数据ID
     * @return boolean    false-操作失败   操作成 - 当前数据id
     */
    public function editData($data = false ,$id = '') {
        if(empty($data))
            $data = Request()->param();
        if (empty($data['id'])) {//新增数据
            if(!empty($id)){ $data['id'] = $id;  }
            $id = $this->data($data)->allowField(true)->save();
            if (!$id) {
                $this->error = '新增失败！';
                return false;
            }
            $id = $this->id;
        } else { //更新数据
            $id = $data['id'];
            $status = $this->allowField(true)->save($data,['id'=>$id]);
            if (false === $status) {
                $this->error = '更新失败！';
                return false;
            }
        }
        return $id;
    }

    /*
     * @title 获取对象值
     * @$param 要获取的参数 支持多级  a.b.c
     * @return array
     * @author 艺品网络 593657688@qq.com
     */
    public function getparam($param = false){
        if($param){
            if (is_string($param)) {
                if (!strpos($param, '.')) {
                    return $this->$param;
                }
                $name = explode('.', $param);
                $arr = $this->toArray();
                foreach ($name as $value){
                    $arr = $arr[$value];
                }
                return $arr;
            }
        }else{
            return $this->toArray();
        }
    }
    //对象转数组
    public function toArray(){
        return (array)$this;
    }
}
