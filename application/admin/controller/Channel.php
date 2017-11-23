<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;

/*
 * @title 导航管理
 * @Author 小矮人 82550565@qq.com
 */
class Channel extends Admin {
    public $model_info = [
        'default'=>[
            'meta_title' => '配置管理',
            'title_bar'  => '配置管理',
            //表单提交地址
            'url' => 'updates',
            //表名
            'name' => 'Channel',
            //主键
            'pk' => 'id',
            //表单显示分组
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'pid','title'=>'pid','type'=>'string','extra'=>':[pid]','remark'=>',','is_show'=>4],
                    ['name'=>'title','title'=>'导航标题','type'=>'string','remark'=>'用于显示的文字','is_show'=>1],
                    ['name'=>'url','title'=>'导航连接','type'=>'string','remark'=>'用于调转的URL，支持带http://的URL或U函数参数格式','is_show'=>1],
                    ['name'=>'target','title'=>'新窗口打开','type'=>'select','extra'=>'0:是,1:否','value'=>'0','remark'=>'是否新窗口打开链接','is_show'=>1],
                    ['name'=>'sort','title'=>'优先级','type'=>'string','remark'=>'导航显示顺序','is_show'=>1],
                ]
            ]
        ],
        'add'=>[
            'meta_title' => '新增导航',
            'title_bar'  => '新增导航',
        ],
        'edit'=>[
            'meta_title' => '编辑导航',
            'title_bar'  => '编辑导航',
        ]
    ];

    /*
     * @title 导航列表
     * @Author 小矮人 82550565@qq.com
     */
    public function index(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['pid'] = $this->request->param('pid',0);
            $map['pid'] = $data['pid'];
            $map['sort']= $data['sort'];
            $db = Db::name('Channel');

            //移动
            $res = $db->where(['id'=>$data['id']])->setField('pid', $data['pid']);

            //排序
            $res_sort = $db->where(['pid'=>$data['pid']])->order('sort')->column('id');
            if($res === false){
                $this->error('移动失败');
            }
            $key = array_search($data['id'], $res_sort);
            if ($key !== false)
                array_splice($res_sort, $key, 1);
            $res_sort_new = [];
            foreach ($res_sort as $k=>$v){
                $res_sort_new[$k+1] = $v;
            }
            $new_key = array_search($data['sort'],$res_sort_new);
            array_splice($res_sort_new,$new_key,0,$data['id']);
            foreach ($res_sort_new as $k=>$v){
                if($db->where(['id'=>$v])->setField('sort', $k) === false){
                    $this->error('排序失败');
                }
            }
            $this->success('操作成功');
        }else{
            $tree = model('Channel')->getTree();
            config('_sys_get_channel_tree', true); //标记系统获取分类树模板
            $this->assign('tree',$tree);
            $this->assign('meta_title','导航管理');
            return $this->fetch();
        }
    }
    /**
     * @title 显示分类树，仅支持内部调
     * @param  array $tree 分类树
     * @Author 小矮人 <82550565@qq.com>
     */
    public function tree($tree = null){
        config('_sys_get_channel_tree') || $this->_empty();
        $this->assign('tree', $tree);
        return $this->fetch('tree');
    }
}