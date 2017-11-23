<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\model;

/**
 * 配置模型
 */
class Config extends Base
{
    protected $autoWriteTimestamp = true;
    protected $type = array(
        'id' => 'integer',
    );

    protected $auto = ['name', 'update_time', 'status' => 1];
    protected $insert = ['create_time'];

    protected function setNameAttr($value)
    {
        return strtolower($value);
    }

    protected function getTypeTextAttr($value, $data)
    {
        $type = config('config_type_list');
        $type_text = explode(',', $type[$data['type']]);
        return $type_text[0];
    }

}