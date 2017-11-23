<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\controller;

/**
 * 行为控制器
 * @author 艺品网络  <twothink.cn>
 */
class Action extends Admin {
    public $model_info = [
        'default'=>[
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'','class'=>'ajax-get iframe btn-success','ExtraHTML'=>''],
                ['title'=>'删除','url'=>'del','icon'=>'','class'=>'btn-danger ajax-post confirm','ExtraHTML'=>'target-form="ids"'],
            ],
            //表名
            'name' => 'ActionLog',
            //主键
            'pk' => 'id',
            //列表定义
            'list_grid'  => 'id:编号;id|get_action([action_id] title):行为名称;user_id|get_nickname:执行者;create_time|time_format:执行时间;id:操作:Action/edit?id=[id]|详细,del?ids=[id]|删除',
        ],
        'actionlog'=>[
            'meta_title' => '行为日志',
            'title_bar'  => '行为日志',
            'action'=>'_index',
        ],
        'edit'=>[
            'meta_title' => '查看行为日志',
            'title_bar'  => '查看行为日志',
            'field_group'=>'1:基础',
            'url' => false,
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'action_id','title'=>'行为名称','type'=>'disabled','extra'=>':get_action([action_id],title)','remark'=>'','is_show'=>1],
                    ['name'=>'action_id','title'=>'执行者','type'=>'disabled','extra'=>':get_nickname([user_id])','remark'=>'','is_show'=>1],
                    ['name'=>'action_id','title'=>'执行IP','type'=>'disabled','extra'=>':long2ip([action_ip])','remark'=>'','is_show'=>1],
                    ['name'=>'create_time','title'=>'执行时间','type'=>'disabled','extra'=>'','remark'=>'','is_show'=>1],
                    ['name'=>'remark','title'=>'备注','type'=>'textarea','extra'=>'','remark'=>'','is_show'=>1],
                ]
            ]
        ]
    ];
    public function edit(){
        $this->two_assign['highlight_subnav'] = url('action/actionlog');
        return parent::_edit();
    }
}