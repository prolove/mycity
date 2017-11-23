<?php
 
namespace app\admin\validate;

class Channel extends Base {
     
    protected $rule = [ 
        ['title', 'require', '标题不能为空'],
        ['url', 'require', 'URL不能为空'], 
    ];   
}