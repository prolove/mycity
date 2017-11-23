<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\api\Uc;
use think\Db;

class User extends Admin {
    public $model_info = [
        'default'=>[
            'meta_title' => '用户管理',
            //表单提交地址
            'url' => 'updates',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[uid]','edit?id=[uid]']],
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'','class'=>'ajax-get iframe btn-success','ExtraHTML'=>''],
                ['title'=>'启用','url'=>'changestatus?method=resumeUser','icon'=>'','class'=>'ajax-post btn-info','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'禁用','url'=>'changestatus?method=forbidUser','icon'=>'','class'=>'ajax-post btn-danger','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'删除','url'=>'changestatus?method=deleteUser','icon'=>'','class'=>'ajax-post confirm btn-success','ExtraHTML'=>'target-form="ids"'],
            ],
            //表名
            'name' => 'Member',
            'pk' => 'uid',
            'list_grid'  => 'uid:UID;nickname:昵称;score:积分;login:登录次数;last_login_time|time_format:最后登入时间;last_login_ip|long2ip:最后登入IP;status|get_status_title:状态;uid:操作:[status]|{0.启用.changestatus?method=resumeUser&uid=[uid] 1.禁用.changestatus?method=forbidUser&uid=[uid] -1.假删除},auth_manager/group?uid=[uid]|授权,changestatus?method=deleteUser&uid=[uid]|删除',
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'username','title'=>'用户名','type'=>'string','remark'=>'用户名会作为默认的昵称','is_show'=>1],
                    ['name'=>'password','title'=>'密码','type'=>'string','remark'=>'用户密码不能少于6位','is_show'=>1],
                    ['name'=>'repassword','title'=>'确认密码','type'=>'string','remark'=>'','is_show'=>1],
                    ['name'=>'email','title'=>'邮箱','type'=>'string','remark'=>'用户邮箱，用于找回密码等安全操作','is_show'=>1],
                ]
            ],
            'where_solid' => ['status'=>['gt','-1']],
        ],
        'index'=>['url'=>''],
        'add'=>[
            'meta_title' => '新增用户',
            'url' => 'add',
        ],
        'action'=>[
            'url'=>'',
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[id]','user/editaction?id=[id]']],
            'meta_title' => '用户行为',
            'title_bar'  => '行为列表',
            'action' =>'_index',
            'name' => 'action',
            'pk' => 'id',
            'button'     => [
                ['title'=>'新增','url'=>'addaction','icon'=>'iconfont icon-xinzeng','class'=>'list_add btn-success','ExtraHTML'=>''],
                ['title'=>'启用','url'=>'setstatus?Model=Action&pk=id&status=1','icon'=>'iconfont icon-xinzeng','class'=>'ajax-post btn-info','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'禁用','url'=>'setstatus?Model=Action&pk=id&status=0','icon'=>'iconfont icon-xinzeng','class'=>'ajax-post btn-danger','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'删除','url'=>'setstatus?Model=Action&pk=id&status=-1','icon'=>'iconfont icon-xinzeng','class'=>'ajax-post confirm btn-success','ExtraHTML'=>'target-form="ids"'],
            ],
            'list_grid'  => 'id:编号;name:标识;title:名称:[EDIT];type|get_action_type:类型;remark:规则;status|get_status_title:状态;id:操作:[EDIT]|编辑,[status]|{0.启用.User/setstatus?model=action&status=1&ids=[id] 1.禁用.User/setStatus?model=action&status=0&ids=[id]},User/setstatus?model=action&status=-1&ids=[id]|删除',
        ],
        'editaction'=>[
            'meta_title' => '编辑行为',
            'name' => 'action',
            'pk' => 'id',
            'url' => 'editaction',
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'ID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'行为标识','type'=>'string','remark'=>'输入行为标识 英文字母','is_show'=>1],
                    ['name'=>'title','title'=>'行为名称','type'=>'string','remark'=>'输入行为名称','is_show'=>1],
                    ['name'=>'type','title'=>'行为类型','type'=>'string','remark'=>'选择行为类型','is_show'=>1],
                    ['name'=>'remark','title'=>'行为描述','type'=>'textarea','remark'=>'输入行为描述','is_show'=>1],
                    ['name'=>'rule','title'=>'行为规则','type'=>'textarea','remark'=>'输入行为规则，不写则只记录日志','is_show'=>1],
                    ['name'=>'log','title'=>'日志规则','type'=>'textarea','remark'=>'记录日志备注时按此规则来生成，支持[变量|函数]。目前变量有：user,time,model,record,data','is_show'=>1],
                ]
            ]
        ],
        'update_password'=>[
            'meta_title' => '修改密码',
            'action' => '_add',
            'url' =>'submitPassword',
            'field_group'=>'',
            'fields' =>[
                '1'=>[
                    ['name'=>'old','title'=>'原密码','type'=>'string','is_show'=>1],
                    ['name'=>'password','title'=>'新密码','type'=>'string','is_show'=>1],
                    ['name'=>'password_confirm','title'=>'确认密码','type'=>'string','is_show'=>1],
                ]
            ]
        ],
        'update_nickname'=>[
            'meta_title' => '修改昵称',
            'action' => '_add',
            'url' =>'submitNickname',
            'field_group'=>'',
            'fields' =>[
                '1'=>[
                    ['name'=>'password','title'=>'密码','type'=>'string','is_show'=>1],
                    ['name'=>'nickname','title'=>'昵称','type'=>'string','extra'=>':get_username()','is_show'=>1]
                ]
            ]
        ]
    ];
    /**
     * @title 用新增用户
     * @author 小矮人 <82550565@qq.com>
     */
    public function add($username = '', $password = '', $repassword = '', $email = ''){
        if($this->request->isPost()){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $User   =   new Uc;
            $uid    =   $User->register($username, $password, $email,'','admin');
            if(0 < $uid){ //注册成功
                $user = array('uid' => $uid, 'nickname' => $username, 'status' => 1);
                if(!db('Member',[],false)->insert($user)){
                    $this->error('用户添加失败！');
                } else {
                    $this->success('用户添加成功！',url('index'));
                }
            } else { //注册失败，显示错误信息
                $this->error($uid);
            }
        } else {
            return parent::_add();
        }
    }
    /**
     * @title 用户行为列表
     * @author 小矮人 <82550565@qq.com>
     */
    public function editaction(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $status = Db::name('action')->update($data);
            if (false === $status) {
                $this->error('操作失败！');
            }else{
                cache('action_list', null);
                $this->success('操作成功');
            }
        }else{
            return parent::_edit();
        }
    }
    /**
     * @title 会员状态修改
     * @author 小矮人 <82550565@qq.com>
     */
    public function changeStatus($method=null){
        $data=input('uid/a');
        $id = array_unique($data);
        if( in_array(config('user_administrator'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }

        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('member', $map );
                break;
            case 'resumeuser':
                $this->resume('member', $map );
                break;
            case 'deleteuser':
                $this->delete('member', $map );
                break;
            default:
                $this->error('参数非法');
        }
    }
    /**
     * 管理员修改密码提交
     * @author 小矮人 <82550565@qq.com>
     */
    public function submitPassword(){
        $param = $this->request->param();
        $rule =[
            ['old', 'require', '请输入原密码'],
            ['password', 'require|confirm', '请输入新密码|您输入的新密码与确认密码不一致']
        ];
        $result = $this->validate($param,$rule);
        if(true !== $result){
            $this->error($result);
        }
        $Api    =   new Uc();
        $res    =   $Api->updateInfo(UID, $param['old'], $param);
        if($res['status']){
            model('Member')->logout();
            session('[destroy]');
            $this->success('修改密码成功 请重新登入','login/index');
        }else{
            $this->error($res['info']);
        }
    }
    /**
     * 修改昵称提交
     * @author 小矮人 <82550565@qq.com>
     */
    public function submitNickname(){
        $param = $this->request->param();
        $rule =[
            ['password', 'require', '请输入密码'],
            ['nickname', 'require', '请输入昵称']
        ];
        $result = $this->validate($param,$rule);
        if(true !== $result){
            $this->error($result);
        }

        //密码验证
        $User   =   new Uc();
        $uid    =   $User->login(UID, $param['password'], 4);
        ($uid == -2) && $this->error('密码不正确');

        $res = db('member')->where('uid','eq',$uid)->update(['nickname'=>$param['nickname']]);

        if($res){
            $user               =   session('user_auth');
            $user['username']   =   $param['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }
}
