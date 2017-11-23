<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\admin\model;
use think\Model;
use think\Request;

/**
 * 模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class Base extends Model {
    protected $autoWriteTimestamp = false; // 关闭自动写入时间戳字段
    protected $FormData; //接收表单数据
    public function __construct($name=''){
        if(!empty($name) && !empty($name['twothink_name']) && count($name) == 1){
            $this->name=$name['twothink_name'];
            parent::__construct();
        }else{
            if(!empty($name)){
                parent::__construct($name);
            }else{
                parent::__construct();
            }
        }
    }
    /**
     * 获取详细信息
     * @param  array    $where
     * @return array       当前数据详细信息
     */
    public function detail($where) {
        $data = $this->field(true)->where($where)->find();
        return $data;
    }
    /**
     * 新增或添加模型数据
     * @param  array  $data 请求数据
     * @param  number $id 文章ID
     * @return boolean    false-操作失败   操作成 - 当前数据id
     */
    public function editData($data = false ,$id = '') {
        if(empty($data))
            $data = Request()->param();
        if (empty($data['id'])) {//新增数据
            if(!empty($id)){ $data['id'] = $id;  }
            $id = $this->data($data)->allowField(true)->save();
            if (!$id) {
                $this->error = '新增数据失败！';
                return false;
            }
            $id = $this->id;
        } else { //更新数据
            $id = $data['id'];
            $status = $this->save($data,['id'=>$id]);
            if (false === $status) {
                $this->error = '更新数据失败！';
                return false;
            }
        }
        return $id;
    }
}
