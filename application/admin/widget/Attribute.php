<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\widget;

use think\Controller;
class Attribute extends Controller {
    //字段类型
    public function type($data){
        if(!is_array($data)){
            $data = [];
            $data['type'] = '';
        }
        $this->assign('data',$data);
        return $this->fetch('widget/attribute_type');
    }
}