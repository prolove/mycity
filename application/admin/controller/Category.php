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
  * @title内容分类控制器
  * @Author 小矮人 <82550565@qq.com>
  */
class Category extends Admin {
    public $model_info = [
        'default'=>[
            'meta_title' => '分类管理',
            'title_bar'  => '分类管理',
            //表单提交地址
            'url' => 'updates',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[id]','edit?id=[id]']],
            //表名
            'name' => 'Category',
            //主键
            'pk' => 'id',
            //表单显示分组
            'field_group'=>'1:基础,2:高级',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>':[id]','is_show'=>4],
                    ['name'=>'pid','title'=>'pid','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'','title'=>'上级分类','type'=>'disabled','extra'=>':Field_category_title()','remark'=>'','is_show'=>1],
                    ['name'=>'title','title'=>'分类名称','type'=>'string','remark'=>'名称不能为空','is_show'=>1],
                    ['name'=>'name','title'=>'分类标识','type'=>'string','remark'=>'英文字母','is_show'=>1],
                    ['name'=>'groups','title'=>'分组定义','type'=>'textarea','value'=>'','remark'=>'','is_show'=>1],
                    ['name'=>'allow_publish','title'=>'发布内容','type'=>'radio','extra'=>'0:不允许,1:仅允许后台,2:允许前后台','value'=>'1','remark'=>'是否允许发布内容','is_show'=>1],
                    ['name'=>'check','title'=>'是否审核','type'=>'radio','extra'=>'0:不需要,1:需要','remark'=>'在该分类下发布的内容是否需要审核','is_show'=>1],
                    ['name'=>'type','title'=>'允许文档类型','type'=>'checkbox','extra'=>'[document_model_type]','remark'=>'如果是枚举型 需要配置该项','is_show'=>1],
                    ['name'=>'model','title'=>'列表绑定文档模型','type'=>'checkbox','extra'=>':Field_get_document_model()','remark'=>'列表支持发布的文档模型','is_show'=>1],
                    ['name'=>'model_sub','title'=>'子文档绑定绑定模型','type'=>'checkbox','extra'=>':Field_get_document_model()','remark'=>'子文档支持发布的文档模型','is_show'=>1],
                ],
                '2'=>[
                    ['name'=>'display','title'=>'可见性','type'=>'select','extra'=>'1:所有人可见,0:不可见,2:管理员可见','remark'=>'是否对用户可见，针对前台','is_show'=>1],
                    ['name'=>'sort','title'=>'排序','type'=>'string','remark'=>'仅对当前层级分类有效','value'=>0,'is_show'=>1],
                    ['name'=>'list_row','title'=>'列表行数','type'=>'string','value'=>10,'is_show'=>1],
                    ['name'=>'meta_title','title'=>'网页标题','type'=>'string','is_show'=>1],
                    ['name'=>'keywords','title'=>'关键字','type'=>'textarea','is_show'=>1],
                    ['name'=>'description','title'=>'描述','type'=>'textarea','is_show'=>1],
                    ['name'=>'template_index','title'=>'频道模版','type'=>'string','is_show'=>1],
                    ['name'=>'template_lists','title'=>'列表模版','type'=>'string','is_show'=>1],
                    ['name'=>'template_detail','title'=>'详情模版','type'=>'string','is_show'=>1],
                    ['name'=>'template_edit','title'=>'编辑模版','type'=>'string','is_show'=>1],

                ]
            ]
        ],
        'add'=>[
            'meta_title' => '添加分类',
            'title_bar'  => '添加分类',
        ],
        'edit'=>[
            'meta_title' => '编辑分类',
            'title_bar'  => '编辑分类',
        ]
    ];
    /**
     * @title 分类管理列表
     * @Author 小矮人 <82550565@qq.com>
     */
    public function index(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['pid'] = $this->request->param('pid',0);
            $map['pid'] = $data['pid'];
            $map['sort']= $data['sort'];
            $db = Db::name('Category');

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
            $tree = model('Category')->getTree(0,'id,name,title,sort,pid,allow_publish,status');
            config('_sys_get_category_tree', true); //标记系统获取分类树模板
            $this->assign('tree',$tree);
            $this->assign('meta_title','分类管理');
            return $this->fetch();
        }
    }
    /**
     * @title 删除一个分类
     * @param  array $tree 分类树
     * @Author 小矮人 <82550565@qq.com>
     */
    public function remove(){
        $cate_id = $this->request->param('id');
        if(empty($cate_id)){
            $this->error('参数错误!');
        }

        //判断该分类下有没有子分类，有则不允许删除
        $child = Db::name('Category')->where(array('pid'=>$cate_id))->field('id')->select();
        if(!empty($child)){
            $this->error('请先删除该分类下的子分类');
        }

        //判断该分类下有没有内容
        $document_list = Db::name('Document')->where(array('category_id'=>$cate_id))->field('id')->select();
        if(!empty($document_list)){
            $this->error('请先删除该分类下的文章（包含回收站）');
        }

        //删除该分类信息
        $res = Db::name('Category')->delete($cate_id);
        if($res !== false){
            //记录行为
            action_log('update_category', 'category', $cate_id, UID);
            $this->success('删除分类成功！');
        }else{
            $this->error('删除分类失败！');
        }
    }
    /**
     * 操作分类初始化
     * @param string $type
     */
    public function operate($type = 'move'){
        //检查操作参数
        if(strcmp($type, 'move') == 0){
            $operate = '移动';
        }elseif(strcmp($type, 'merge') == 0){
            $operate = '合并';
        }else{
            $this->error('参数错误！');
        }
        $from = intval(input('from'));
        empty($from) && $this->error('参数错误！');

        //获取分类
        $map = array('status'=>1, 'id'=>array('neq', $from));
        $list = Db::name('Category')->where($map)->field('id,pid,title')->select();


        //移动分类时增加移至根分类
        if(strcmp($type, 'move') == 0){
            //不允许移动至其子孙分类
            $list = tree_to_list(list_to_tree($list));

            $pid = Db::name('Category')->getFieldById($from, 'pid');
            $pid && array_unshift($list, array('id'=>0,'title'=>'根分类'));
        }

        $this->assign('type', $type);
        $this->assign('operate', $operate);
        $this->assign('from', $from);
        $this->assign('list', $list);
        $this->assign('meta_title', $operate.'分类');
        return $this->fetch();
    }
    /**
     * 合并分类
     */
    public function merge(){
        $to = $this->request->post('to');
        $from = $this->request->post('from');
        $Model = Db::name('Category');

        //检查分类绑定的模型
        $from_models = explode(',', $Model->getFieldById($from, 'model'));
        $to_models = explode(',', $Model->getFieldById($to, 'model'));
        foreach ($from_models as $value){
            if(!in_array($value, $to_models)){
                $this->error('请给目标分类绑定' . get_document_model($value, 'title') . '模型');
            }
        }

        //检查分类选择的文档类型
        $from_types = explode(',', $Model->getFieldById($from, 'type'));
        $to_types = explode(',', $Model->getFieldById($to, 'type'));
        foreach ($from_types as $value){
            if(!in_array($value, $to_types)){
                $types = config('document_moel_type');
                $this->error('请给目标分类绑定文档类型：' . $types[$value]);
            }
        }

        //合并文档
        $res = Db::name('Document')->where(array('category_id'=>$from))->setField('category_id', $to);

        if($res !== false){
            //删除被合并的分类
            Db::name('Category')->delete($from);
            $this->success('合并分类成功！', url('index'));
        }else{
            $this->error('合并分类失败！');
        }

    }
    /**
     * @title 显示分类树，仅支持内部调
     * @param  array $tree 分类树
     * @Author 小矮人 <82550565@qq.com>
     */
    public function tree($tree = null){
        config('_sys_get_category_tree') || $this->_empty();
        $this->assign('tree', $tree);
        return $this->fetch('tree');
    }
}