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
 * @title 菜单管理
 * @Author 小矮人 82550565@qq.com
 */
class Menu extends Admin {
    public $model_info = [
        'default'=>[
            'meta_title' => '菜单管理',
            'title_bar'  => '菜单管理',
            //表单提交地址
            'url' => 'updates',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[id]','edit?id=[id]']],
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'','class'=>'ajax-get btn-success','ExtraHTML'=>''],
            ],
            //表名
            'name' => 'menu',
            'pk' => 'id',
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'title','title'=>'标题','type'=>'string','remark'=>'用于后台显示的配置标题','is_show'=>1],
                    ['name'=>'icon','title'=>'图标','type'=>'string','remark'=>'用于模版的显示<a href="http://v3.bootcss.com/components/" target="_blank">Bootstrap自带图标</a> <a href="http://www.iconfont.cn/" target="_blank">阿里图标</a>','is_show'=>1],
                    ['name'=>'sort','title'=>'排序','type'=>'string','value'=>'0','remark'=>'用于分组显示的顺序','is_show'=>1],
                    ['name'=>'url','title'=>'链接','type'=>'string','remark'=>'url函数解析的URL或者外链','is_show'=>1],
                    ['name'=>'pid','title'=>'上级菜单','type'=>'select','extra'=>':Field_menu_tree()','value'=>':[pid]','remark'=>'所属的上级菜单','is_show'=>1],
                    ['name'=>'group','title'=>'分组','type'=>'string','value'=>'','remark'=>'用于左侧分组二级菜单','is_show'=>1],
                    ['name'=>'hide','title'=>'是否隐藏','type'=>'radio','extra'=>'1:是,0:否','is_show'=>1],
                    ['name'=>'is_dev','title'=>'仅开发者模式可见','type'=>'radio','extra'=>'1:是,0:否','is_show'=>1],
                    ['name'=>'tip','title'=>'说明','type'=>'string','remark'=>'菜单详细说明','is_show'=>1],
                ]
            ]
        ],
        'add'=>[
            'meta_title' => '新增菜单',
            'title_bar'  => '新增菜单',
        ],
        'edit'=>[
            'meta_title' => '编辑菜单',
            'title_bar'  => '编辑菜单',
        ]
    ];
    /*
     * @title 菜单列表
     * @Author 小矮人 82550565@qq.com
     */
    public function index(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['pid'] = $this->request->param('pid',0);
            $map['pid'] = $data['pid'];
            $map['sort']= $data['sort'];
            $db = Db::name('Menu');

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
            $tree = model('menu')->getTree();
            config('_sys_get_menu_tree', true); //标记系统获取菜单树模板
            $this->assign('tree',$tree);
            $this->assign('meta_title','导航管理');
            return $this->fetch();
        }
    }
    /**
     * @title 显示菜单树，仅支持内部调
     * @param  array $tree 菜单树
     * @Author 小矮人 <82550565@qq.com>
     */
    public function tree($tree = null){
        config('_sys_get_menu_tree') || $this->_empty();
        $this->assign('tree', $tree);
        return $this->fetch('tree');
    }
    /**
     * @title 显示分类树，仅支持内部调
     * @param  array $tree 菜单树
     * @Author 小矮人 <82550565@qq.com>
     */
    public function sidetree($tree = null,$tpl = 'menu/sidetree'){
        $this->assign('tree', $tree);
        return $this->fetch($tpl);
    }
}