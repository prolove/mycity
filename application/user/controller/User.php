<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\user\controller;
use app\common\api\Uc;

/**
 * 用户控制器
 */
class User extends Base {
    /**
     * 修改密码提交
     * @author 艺品网络  <twothink.cn>
     */
    public function profile(){
		if ( !is_login() ) {
			$this->error( '您还没有登陆',url('User/login') );
		}
        if ($this->request->isPost()) {
            //获取参数
            $uid        =   is_login();
            $data = input('param.'); 
            $password   =  $data['old'];;
            $repassword = $data['repassword'];
            $data['password'] = $data['password'];
            empty($password) && $this->error('请输入原密码');
            empty($data['password']) && $this->error('请输入新密码');
            empty($repassword) && $this->error('请输入确认密码');

            if($data['password'] !== $repassword){
                $this->error('您输入的新密码与确认密码不一致');
            }

            $Api = new Uc();
            $res = $Api->updateInfo($uid, $password, $data);
            if($res['status']){
                model('Member')->logout();
                $this->success('密码修改成功,请使用新密码登入！', url('User/login'));
            }else{
                $this->error($res['info']);
            }
        }else{
		    $this->assign('meta_title','修改密码');
            return $this->fetch();
        }
    }

}
