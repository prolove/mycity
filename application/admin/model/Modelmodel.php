<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\admin\model;
use think\Db;

/**
 * 文档基础模型
 */
class Modelmodel extends Base {
	// 设置当前模型名称
	protected $name = 'model';
	protected $auto = ['field_sort','attribute_list'];
	protected $insert = ['name','status'=>1];
// 	protected $update = ['create_time'];
	protected function setCreateTimeAttr($value){
		return time();
	}
	protected function setNameAttr($value){
		return strtolower($value);
	}
//	protected function setFieldSortAttr($value){
//		return empty($value) ? '' : json_encode($value);
//	}
	protected function setAttributeListAttr($fields) {
		return empty($fields) ? '' : implode(',', $fields);
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
        $data['field_sort'] = isset($data['field_sort'])?json_encode($data['field_sort']):'';
        if (empty($data['id'])) {//新增数据
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
        // 清除模型缓存数据
        cache('document_model_list', null);
        //记录行为
        action_log('update_model','model',$id,UID);
        return $id;
    }

    /**
     * 获取指定数据库的所有表名
     */
    public function getTables(){
        return Db::connect()->getTables();
    }

    /**
     * 根据数据表生成模型及其属性数据
     * @author 艺品网络  <twothink.cn>
     */
    public function generate($table,$name='',$title=''){
        //新增模型数据

        if(empty($name)){
            $name = $title = substr($table, strlen(config('prefix')));
        }
        $data = array('name'=>$name, 'title'=>$title);
        $res = $this->create($data);
        if(!$res){
                return false;
        }
        //新增属性
        $fields = db()->query('SHOW FULL COLUMNS FROM '.$table);
        foreach ($fields as $key=>$value){
            $value  =   array_change_key_case($value);
            //不新增id字段
            if(strcmp($value['field'], 'id') == 0){
                continue;
            }

            //生成属性数据
            $data = array();
            $data['name'] = $value['field'];
            $data['title'] = $value['comment'];
            $data['type'] = 'string';	//TODO:根据字段定义生成合适的数据类型
            //获取字段定义
            $is_null = strcmp($value['null'], 'NO') == 0 ? ' NOT NULL ' : ' NULL ';
            $data['field'] = $value['type'].$is_null;
            $data['value'] = $value['default'] == null ? '' : $value['default'];
            $data['model_id'] = $res->id;
            $_POST = $data;		//便于自动验证
            model('Attribute')->updates($data, false);
        }

        return $res;
    }

    /**
     * 删除一个模型
     * @param integer $id 模型id
     * @author 艺品网络  <twothink.cn>
     */
    public function del($id){
        //获取表名
        $model = Db::name($this->name)->field('name,extend')->find($id);
        if($model['extend'] == 0){
            $table_name = config('database.prefix').strtolower($model['name']);
        }else{
        	$model_jc = Db::name($this->name)->field('name')->find($model['extend']);
            $table_name = config('database.prefix').$model_jc['name'].'_'.strtolower($model['name']);
        }
        //删除属性数据
        db('Attribute')->where(array('model_id'=>$id))->delete();
        //删除模型数据
        $this->where(['id'=>$id])->delete();
        //检查数据表是否存在
        $sql = <<<sql
                SHOW TABLES LIKE '{$table_name}';
sql;
        $res = db()->query($sql);
        if(!count($res))
        	return true;
        //删除该表
        $sql = <<<sql
                DROP TABLE {$table_name};
sql;
        $res = db()->execute($sql);
        return $res !== false;
    }
}
