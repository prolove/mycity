<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  <82550565@qq.com>
// +----------------------------------------------------------------------
// TwoThink常量定义
define('TWOTHINK_VERSION','2.0.3 20171019' );
define('TWOTHINK_ADDON_PATH', ROOT_PATH. 'addons/' );

use think\Db;

function Modelinfo(){
    return new \app\common\controller\Modelinfo();
}
/**
 * 执行SQL文件
 * $sql_path sql文件路径
 * $prefix 替换表前缀
 * @auth 小矮人 82550565@qq.com
 */
function execute_sql_file($sql_path, $prefix = '')
{
    //读取SQL文件
    $sql = file_get_contents($sql_path);
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    //替换表前缀
    $orginal = config('database.prefix');
    if(empty($prefix))
        $prefix = $orginal;
    $sql     = str_replace(" `{$orginal}", " `{$prefix}", $sql);

    //开始安装
    foreach ($sql as $value) {
        $value = trim($value);
        if (empty($value)) {
            continue;
        }
        Db()->execute($value);
    }
}
/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login(){
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 */
function is_administrator($uid = null){
    $uid = is_null($uid) ? is_login() : $uid;
    return $uid && (intval($uid) === config('user_administrator'));
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str  要分割的字符串
 * @param  string $glue 分割符
 * @return array
 * @author 艺品网络  <twothink.cn>
 */
function str2arr($str, $glue = ','){
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array  $arr  要连接的数组
 * @param  string $glue 分割符
 * @return string
 * @author 艺品网络  <twothink.cn>
 */
function arr2str($arr, $glue = ','){
    if(is_string($arr))
        return $arr;
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice.'...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * @return string
 * @author 艺品网络  <twothink.cn>
 */
function think_encrypt($data, $key = '', $expire = 0) {
    $key  = md5(empty($key) ? config('data_auth_key') : $key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time():0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key  加密密钥
 * @return string
 * @author 艺品网络
 */
function think_decrypt($data, $key = ''){
    $key    = md5(empty($key) ? config('data_auth_key') : $key);
    $data   = str_replace(array('-','_'),array('+','/'),$data);
    $mod4   = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data,0,10);
    $data   = substr($data,10);

    if($expire > 0 && $expire < time()) {
        return '';
    }
    $x      = 0;
    $len    = strlen($data);
    $l      = strlen($key);
    $char   = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 * @author 艺品网络  <twothink.cn>
 */
function data_auth_sign($data) {
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string  $name 格式 [模块名]/接口名/方法名
 * @param  array|string  $vars 参数
 */
function api($name,$vars=[]){
    $array     = explode('/',$name);
    $method    = array_pop($array);
    $classname = array_pop($array);
    $module    = $array? array_pop($array) : 'common';
    $callback  = 'app\\'.$module.'\\api\\'.$classname.'::'.$method;
    if(is_string($vars)) {
        parse_str($vars,$vars);
    }
    return call_user_func_array($callback,$vars);
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 */
function get_client_ip($type = 0, $adv = false) {
    $type      = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}
/* 不区分大小写的in_array实现
 * string $value 搜索数组中是否存在指定的值
 * array  $array 被搜索数组
 * auth 小矮人 82550565@qq.com
 */
function in_array_case($value, $array) {
    return in_array(strtolower($value), array_map('strtolower', $array));
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 * @author 艺品网络  <twothink.cn>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null){

    //参数检查
    if(empty($action) || empty($model) || empty($record_id)){
        return '参数不能为空';
    }
    if(empty($user_id)){
        $user_id = is_login();
    }
    //查询行为,判断是否执行
    $action_info = Db::name('Action')->getByName($action);
    if($action_info['status'] != 1){
        return '该行为被禁用或删除';
    }
    $now_time=time();
    //插入行为日志
    $data['action_id']      =   $action_info['id'];
    $data['user_id']        =   $user_id;
    $data['action_ip']      =   ip2long(get_client_ip());
    $data['model']          =   $model;
    $data['record_id']      =   $record_id;
    $data['create_time']    =   $now_time;

    //解析日志规则,生成日志备注
    if(!empty($action_info['log'])){
        if(preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)){
            $log['user']    =   $user_id;
            $log['record']  =   $record_id;
            $log['model']   =   $model;
            $log['time']    =   $now_time;
            $log['data']    =   array('user'=>$user_id,'model'=>$model,'record'=>$record_id,'time'=>$now_time);
            foreach ($match[1] as $value){
                $param = explode('|', $value);
                if(isset($param[1])){
                    $replace[] = call_user_func($param[1],$log[$param[0]]);
                }else{
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] =   str_replace($match[0], $replace, $action_info['log']);
        }else{
            $data['remark'] =   $action_info['log'];
        }
    }else{
        //未定义日志规则，记录操作url
        $data['remark']     =   '操作url：'.$_SERVER['REQUEST_URI'];
    }

    Db::name('ActionLog')->insert($data);

    if(!empty($action_info['rule'])){
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}
/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author 艺品网络  <twothink.cn>
 */
function parse_action($action , $self){
    if(empty($action)){
        return false;
    }

    //参数支持id或者name
    if(is_numeric($action)){
        $map = array('id'=>$action);
    }else{
        $map = array('name'=>$action);
    }

    //查询行为信息
    $info = db('Action')->where($map)->find();
    if(!$info || $info['status'] != 1){
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key=>&$rule){
        if(empty($rule))
            continue;
        $rule = explode('|', $rule);
        foreach ($rule as $k=>$fields){
            $field = empty($fields) ? array() : explode(':', $fields);
            if(!empty($field)){
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])){
            unset($return[$key]['cycle'],$return[$key]['max']);
        }
    }

    return $return;
}
/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author 艺品网络  <twothink.cn>
 */
function execute_action($rules = false, $action_id = null, $user_id = null){
    if(!$rules || empty($action_id) || empty($user_id)){
        return false;
    }

    $return = true;
    foreach ($rules as $rule){

        //检查执行周期
        $map = array('action_id'=>$action_id, 'user_id'=>$user_id);
        $map['create_time'] = array('gt', time() - intval($rule['cycle']) * 3600);
        $exec_count = db('ActionLog')->where($map)->count();
        if($exec_count > $rule['max']){
            continue;
        }

        //执行数据库操作
        $Model = db(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

        if(!$res){
            $return = false;
        }
    }
    return $return;
}
/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 艺品网络
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[$data['id']] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][$data['id']] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author 艺品网络  <twothink.cn>
 */
function tree_to_list($tree, $child = '_child', $order='id', &$list = array()){
    if(is_array($tree)) {
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if(isset($reffer[$child])){
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby='asc');
    }
    return $list;
}
/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list,$field, $sortby='asc') {
    if(is_array($list)){
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ( $refer as $key=> $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}
/**
 * 获取对应状态的文字信息
 * @param int $status
 * @return string 状态文字 ，false 未获取到
 * @author 艺品网络  <twothink.cn>
 */
function get_status_title($status = null){
    if(!isset($status)){
        return false;
    }
    switch ($status){
        case -1 : return    '已删除';   break;
        case 0  : return    '禁用';     break;
        case 1  : return    '正常';     break;
        case 2  : return    '待审核';   break;
        default : return    false;      break;
    }
}

// 获取数据的状态操作
function show_status_op($status) {
    switch ($status){
        case 0  : return    '启用';     break;
        case 1  : return    '禁用';     break;
        case 2  : return    '审核';       break;
        default : return    false;      break;
    }
}
/**
 * 获取行为类型
 * @param intger $type 类型
 * @param bool $all 是否返回全部类型
 * @author 艺品网络  <twothink.cn>
 */
function get_action_type($type, $all = false){
    $list = array(
        1=>'系统',
        2=>'用户',
    );
    if($all){
        return $list;
    }
    return $list[$type];
}
/**
 * 获取插件的模型名
 * @param strng $name 插件名
 * * @param strng $model 模型名
 */
function get_addon_model($name,$model){
    $class = "addons\\{$name}\model\\{$model}";
    return $class;
}
/**
 * 获取插件类的类名
 * @param $name 插件名
 * @param string $type 返回命名空间类型
 * @param string $class 当前类名
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    $name = \think\Loader::parseName($name);
    $class = \think\Loader::parseName(is_null($class) ? $name : $class, 1);
    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        default:
            $namespace = "\\addons\\" . $name . "\\" . $class;
    }
    // return class_exists($namespace) ? $namespace : '';
    return $namespace;
}
/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 */
function addons_url($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? \think\Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? \think\Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    /* 基础参数 */
    $params = array(
        '_addons'     => $addons,
        '_controller' => $controller,
        '_action'     => $action,
    );
    $params = array_merge($params, $param); //添加额外参数

    return url(request()->module()."/addons/execute", $params);
}
/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = [])
{
    \think\Hook::listen($hook, $params);
}
/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_username($uid = 0){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        return session('user_auth.username');
    }

    /* 获取缓存数据 */
    if(empty($list)){
        $list = cache('sys_active_user_list');
    }
    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $User = new app\common\api\Uc();
        $info = $User->info($uid);
        if($info && isset($info[1])){
            $name = $list[$key] = $info[1];
            /* 缓存用户 */
            $count = count($list);
            $max   = config('user_max_cache');
            while ($count-- > $max) {
                array_shift($list);
            }
            cache('sys_active_user_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname($uid = 0){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        return session('user_auth.username');
    }

    /* 获取缓存数据 */
    if(empty($list)){
        $list = cache('sys_user_nickname_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $info = db('Member')->field('nickname')->find($uid);
        if($info !== false && $info['nickname'] ){
            $nickname = $info['nickname'];
            $name = $list[$key] = $nickname;
            /* 缓存用户 */
            $count = count($list);
            $max   = config('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            cache('sys_user_nickname_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 检查$pos(推荐位的值)是否包含指定推荐位$contain
 * @param number $pos 推荐位的值
 * @param number $contain 指定推荐位
 * @return boolean true 包含 false 不包含
 */
function check_document_position($pos = 0, $contain = 0){
    if(empty($pos) || empty($contain)){
        return false;
    }
    //将两个参数进行按位与运算，不为0则表示$contain属于$pos
    $res = $pos & $contain;
    if($res !== 0){
        return true;
    }else{
        return false;
    }
}
/**
 * 获取文档封面图片
 * @param int $cover_id
 * @param string $field
 * @return 完整的数据  或者  指定的$field字段值
 * @author 艺品网络  <twothink.cn>
 */
function get_cover($cover_id, $field = null){
    if(empty($cover_id)){
        return false;
    }
    $picture = Db::name('Picture')->where(['status'=>1])->getById($cover_id);
    if($field == 'path'){
        if(!empty($picture['url'])){
            $picture['path'] = $picture['url'];
        }else{
            $picture['path'] = $picture['path'];
        }
    }
    return empty($field) ? $picture : $picture[$field];
}
//获得文章封面图片地址
function get_cover_path($id){
    $result = model('Picture')->where('id',$id)->value('path');
    return $result ? $result:'/static/static/image/nopic.jpg';
}
/**
 * 获取分类信息并缓存分类
 * @param  integer $id    分类ID
 * @param  string  $field 要获取的字段名
 * @return string         分类信息
 * @author   艺品网络
 */
function get_category($id = null, $field = null){
    static $list;
    /* 非法分类ID */
    if(!empty($id)){
        if(!is_numeric($id))
            return false;
    }
    /* 读取缓存数据 */
    if(empty($list)){
        $list = cache('sys_category_list');
    }
    /* 获取分类名称 */
    if(empty($list)){
        $data = db('Category')->select();
        foreach ($data as $key => $value) {
            $list[$value['id']] = $value;
        }
        cache('sys_category_list',$list);
    }
    if(empty($id)){
        return $list;
    }else{
        if(isset($list[$id])){
            if(1 != $list[$id]['status']){ //不存在分类，或分类被禁用
                return '';
            }
            return is_null($field) ? $list[$id] : $list[$id][$field];
        }
        return false;
    }
}
/**
 * 获取分类树tree
 * @param  string  是否获取同级分类即同pid数据(true $id=pid)
 * @param  integer $id    分类ID
 * @param  string  $field 要获取的字段名
 * @param  string  $sor 排序字段
 * @param  string  $sortby 排序方式
 * @return string         分类信息
 * @author   艺品网络
 */
function get_category_tree($child=false,$id = null, $field = null, $sor = 'id', $sortby = 'desc'){
    if($child){
        $child_id = $id;
        $id = null;
    }
    if(!$list = get_category($id,$field)){
        return false;
    }
    //进行排序
    if(empty($id))
        $data=list_sort_by($list,$sor,$sortby);
    //转成tree
    if(!isset($list['id']))
        $list=list_to_tree($list);
    if($child)
        return $list[$child_id]['_child'];
    return $list;
}

/* 根据ID获取分类标识 */
function get_category_name($id){
    return get_category($id, 'name');
}

/* 根据ID获取分类名称 */
function get_category_title($id){
    return get_category($id, 'title');
}
/**
 * 获取文档扩展模型对象
 * @param  integer $model_id 模型编号
 * @param string   默认公共模型 base基础模型 Independent独立模型公共模型 Document 继承模型公共模型
 * @return object         模型对象
 */
function logic($model_id,$Base='Base'){
    $name  = get_document_model($model_id);
    if($name['extend'] != 0){
        $name  = get_document_model($name['extend'], 'name').'_'.$name['name'];
    }else{
        $name = $name['name'];
    }
    //判断模型是否存在
    $module = request()->module();
    $class = \think\Loader::parseClass($module, 'logic', $name, config('class_suffix'));
    if (!class_exists($class)) {
        $class = str_replace('\\' . $module . '\\', '\\common\\', $class);
        if (!class_exists($class)) {
            $class = \think\Loader::parseClass($module, 'logic', $Base, config('class_suffix'));
            if(!class_exists($class)){
                $class = 'app\common\logic\\'.$Base;
                $class = str_replace('\\' . $module . '\\', '\\common\\', $class);
            }
            $obj = new $class(['twothink_name'=>$name]);
            return $obj;
        }else{
            return model($name,'logic');
        }
    }else{
        return model($name,'logic');
    }
}
/**
 * 获取文档模型信息
 * @param  integer $id    模型ID
 * @param  string  $field 模型字段
 * @return array
 */
function get_document_model($id = null, $field = null){
    static $list;

    /* 非法分类ID */
    if(!(is_numeric($id) || is_null($id))){
        return '';
    }
    /* 读取缓存数据 */
    if(empty($list)){
        $list = cache('document_model_list');
    }
    /* 获取模型名称 */
    if(empty($list)){
        // $map   = array('status' => 1, 'extend' => 1);
        $map   = array('status' => 1);
        $model = \think\Db::name('Model')->where($map)->field(true)->select();
        foreach ($model as $value) {
            $list[$value['id']] = $value;
        }
        cache('document_model_list', $list); //更新缓存
    }
    /* 根据条件返回数据 */
    if(is_null($id)){
        return $list;
    } elseif(is_null($field)){
        return $list[$id];
    } else {
        return $list[$id][$field];
    }
}
/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 */
function get_table_name($model_id = null){
    if(empty($model_id)){
        return false;
    }
    $Model = Db::name('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if($info['extend'] != 0){
        $name = $Model->getFieldById($info['extend'], 'name').'_';
    }
    $name .= $info['name'];
    return $name;
}
/**
 * @title array_column函数兼容php5.4版本
 * @return array
 * @Author 小矮人
 */
if( ! function_exists('array_column'))
{
    function array_column($input, $columnKey, $indexKey = NULL)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
        $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
        $result = array();

        foreach ((array)$input AS $key => $row)
        {
            if ($columnKeyIsNumber)
            {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
            }
            else
            {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
            }
            if ( ! $indexKeyIsNull)
            {
                if ($indexKeyIsNumber)
                {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
                    $key = is_null($key) ? 0 : $key;
                }
                else
                {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }

            $result[$key] = $tmp;
        }

        return $result;
    }
}
/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author 艺品网络  <twothink.cn>
 */
function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? $_SERVER['REQUEST_TIME'] : intval($time);
    return date($format, $time);
}




/**
 * 获取插件模版文件 格式 资源://模块@主题/控制器/操作
 * @param string $template 模版资源地址
 * @return string
 */
function T($template = '')
{
    // 解析模版资源地址
    if (false === strpos($template, '://')) {
        $template = 'http://' . str_replace(':', '/', $template);
    }
    $info   = parse_url($template);
    $file   = $info['host'] . (isset($info['path']) ? $info['path'] : '');
    $module = isset($info['user']) ? $info['user'] . '/' : request()->module() . '/';
    $extend = $info['scheme'];
    $layer  = 'view';

    // 获取当前模块的模版路径
    $auto = config('root_namespace');
    if ($auto && isset($auto[$extend])) {
        // 扩展资源
        $baseUrl = $auto[$extend] . $module . $layer . '/';
    }else{
        $baseUrl = APP_PATH . $module . $layer . '/';
    }
    return $baseUrl . $file.'.html';
}


/**
 * 远程调用插件控制器的操作方法 URL 参数格式 [资源://][模块/]控制器/操作
 * @param string $url 调用地址
 * @param string|array $vars 调用参数 支持字符串和数组
 * @param string $layer 要调用的控制层名称
 * @return mixed
 */
function addons_action($url,$vars=[],$layer='controller') {
    $info   =   pathinfo($url);
    $action =   $info['basename'];
    $module =   $info['dirname'];
    $class  =   addons_controller($module,$layer);
    if($class){
        if(is_string($vars)) {
            parse_str($vars,$vars);
        }
        return call_user_func_array(array(&$class,$action),$vars);
    }else{
        return false;
    }
}
/**
 * 实例化插件多层控制器 格式：[资源://][模块/]控制器
 * @param string $name 资源地址
 * @param string $layer 控制层名称
 * @return obj|false
 */
function addons_controller($name,$layer='controller') {
    $class  =   parse_res_name($name,$layer);
    if(class_exists($class)) {
        $action             =   new $class();
        return $action;
    }else {
        return false;
    }
}
/**
 * 解析资源地址并导入类库文件
 * 例如 module/controller addons://module/behavior
 * @param string $name 资源地址 格式：[扩展://][模块/]资源名
 * @param string $layer 分层名称
 * @return string
 */
function parse_res_name($name,$layer){
    if(strpos($name,'://')) {// 指定扩展资源
        list($extend,$name)  =   explode('://',$name);
    }else{
        $extend  =   '';
    }
    if(strpos($name,'/')){ // 指定模块
        list($module,$name) =  explode('/',$name,2);
    }else{
        $module =   config('default_module') ? config('default_module') : '' ;
    }
    $array  =   explode('/',$name);
    $class  =   $module.'\\'.$layer;
    foreach($array as $name){
        $class  .=   '\\'.parse_name($name, 1);
    }
    // 导入资源类库
    if($extend){ // 扩展资源
        $class      =   $extend.'\\'.$class;
    }
    return $class;
}
/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type=0) {
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($match){return strtoupper($match[1]);}, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}





// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
    if(is_array($string)){
        return $string;
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

/*分析枚举类型字段值 格式 a:名称1,b:名称2
 * 暂时和 parse_config_attr功能相同
 * 但请不要互相使用，后期会调整
 * @$string 格式规则
 * @$data   数据集
 * @$value   当前指段内容或者默认值
 */
function parse_field_attr($string,$data=false,$value='') {
    if(!$data){
        $data = request()->param();
    }
    //默认值或者当前值
    if(empty($string))
        return $value;
    //支持数组 [$key=>$v,$key=>$v]
    if(is_array($string)){
        return $string;
    }
    if(0 === strpos($string,':')){// 采用函数定义
        $str = substr($string,1);
        if(0 === strpos($str,'[')){
            return $str = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return isset($data[$match[1]])?$data[$match[1]]:'';}, $str);
        }
        if(preg_match('/(.*?)\((.*)\)/',$str,$matches)){ //自定义函数
            if(empty($matches[2]))
                return   eval('return '.$str.';');
            //自定义参数模式
            $matches[2] = str_replace(",","<{@%$}>",$matches[2]);
            // 替换数据变量
            $param  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $matches[2]);
            $param_arr = explode('<{@%$}>',$param);
            if(in_array('[DATA]',$param_arr)){
                $arr_key = array_search('[DATA]',$param_arr);
                $param_arr[$arr_key] = $data;
            }
            foreach ($param_arr as $k=>$v){ //函数参数支持
                if(is_string($v) && preg_match('/(.*?)\((.*)\)/',$v,$news) && strpos($v,'(')){
                    if(empty($news[2]))
                        $param_arr[$k] = eval('return '.$v.';');
                    $param_arr[$k] = parse_field_attr(':'.$v);
                }
            }
            $stmp = call_user_func_array($matches[1], $param_arr);
            return $stmp;
        }
    }elseif(0 === strpos($string,'[')){
        // 支持读取配置参数（必须是数组类型）
        return config(substr($string,1,-1));
    }

    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}
/* 扩展字段value函数和变量获取支持
 * 暂时和 parse_field_attr功能相同 但是不支持 格式 a:名称1,b:名称2 的解析
 * 但请不要互相使用，后期会调整
 * @$string 格式规则
 * @$data   数据集
 */
function parse_field_value($string,$data=false) {
    if(!$data){
        $data = request()->param();
    }
    //支持数组 [$key=>$v,$key=>$v]
    if(is_array($string)){
        return $string;
    }
    if(0 === strpos($string,':')){// 采用函数定义
        $str = substr($string,1);
        if(0 === strpos($str,'[')){
            return $str = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return isset($data[$match[1]])?$data[$match[1]]:'';}, $str);
        }
        if(preg_match('/(.*?)\((.*)\)/',$str,$matches)){
            if(empty($matches[2]))
                return   eval('return '.$str.';');
            //自定义参数模式
            $matches[2] = str_replace(",","<{@%$}>",$matches[2]);
            // 替换数据变量
            $param  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $matches[2]);
            $param_arr = explode('<{@%$}>',$param);
            if(in_array('[DATA]',$param_arr)){
                $arr_key = array_search('[DATA]',$param_arr);
                $param_arr[$arr_key] = $data;
            }
            foreach ($param_arr as $k=>$v){
                if(preg_match('/(.*?)\((.*)\)/',$v,$news) && strpos($v,'(')){
                    if(empty($news[2]))
                        $param_arr[$k] = eval('return '.$v.';');
                    $param_arr[$k] = parse_field_attr(':'.$v);
                }
            }
            return $temp = call_user_func_array($matches[1], $param_arr);
        }
    }elseif(0 === strpos($string,'[')){
        // 支持读取配置参数（必须是数组类型）
        return config(substr($string,1,-1));
    }
    return $string;
}
/* 解析列表定义规则(非文档模型解析)
 * $replace [['[DELETE]','[EDIT]',['[LIST]'],'DELETE','EDIT','LIST']]
 */
function intent_list_field($data, $grid,$replace = [['[DELETE]','[EDIT]','[LIST]'],['setstatus?status=-1&ids=[id]&cate_id=[category_id]','edit?id=[id]&model=[model_id]&cate_id=[category_id]','index?pid=[id]&model=[model_id]&cate_id=[category_id]']]){
    // 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  =   explode('|',$field);
        $temp  =    $data[$array[0]];
        // 函数支持
        if(isset($array[1]) && preg_match('#(.*?)\((.*?)\)#',$array[1],$matches)){ //自定义参数模式
            $matches[2] = str_replace(" ","<{@%$}>",$matches[2]);
            // 替换数据变量
            $param  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $matches[2]);
            $param_arr = explode('<{@%$}>',$param);
            if(in_array('[DATA]',$param_arr)){
                $arr_key = array_search('[DATA]',$param_arr);
                $param_arr[$arr_key] = $data;
            } ;
            $temp = call_user_func_array($matches[1], $param_arr);
        }elseif(isset($array[1]) && preg_match('#\{(.*?)\}#',$array[1],$matches)){
            $switch_arr = explode(' ',$matches[1]);
            foreach ($switch_arr as $value){
                $value_arr = explode('.',$value);
                $arr[$value_arr[0]] = $value_arr;
            }
            $var_key = $data[$array[0]];
            $show   =   $arr[$var_key][1];
            // 替换数据变量
            $href   = isset($arr[$var_key][2]) ? preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $arr[$var_key][2]):'';
            $temp =   isset($arr[$var_key][2]) ?'<a href="'.url($href).'">'.$show.'</a>':$show;
        }elseif(isset($array[1])){ //默认参数模式
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  =   implode(' ',$data2);
    }
    if(!empty($grid['href'])){
        $links  =   explode(',',$grid['href']);
        foreach($links as $link){
            $array  =   explode('|',$link);
            $href   =   $array[0];
            $switch = isset($array[1])?$array[1]:'';
            if(preg_match('#\{(.*?)\}#',$switch,$matches)){// switch 格式解析 列:[status]|{1.启用 2.禁用} 即: [字段]|{值.标题.链接(多个用空格分割)}
                $switch_arr = explode(' ',$matches[1]);
                foreach ($switch_arr as $value){
                    $value_arr = explode('.',$value);
                    $arr[$value_arr[0]] = $value_arr;
                }
                preg_match('/^\[([a-z_]+)\]$/',$array[0],$matches);
                $data_val = $data[$matches[1]];
                $show   =   $arr[$data_val][1];

                // 替换系统特殊字符串
                $href   = isset($arr[$data_val][2]) ? str_replace($replace['0'],$replace['1'],$arr[$data_val][2]):'';
                // 替换数据变量
                $href   = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];},$href);
                $val[]  =   '<a href="'.url($href).'">'.$show.'</a>';
            }elseif(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){ //直接显示内容
                $val[]  =   $data2[$matches[1]];
            }elseif(preg_match('#\[function\@(.*?)\]#',$href,$matches)){ //函数支持
                $val[] = call_user_func($matches[1], $data);
            }else{
                $show   =   isset($array[1])?$array[1]:$value;
                // 替换系统特殊字符串
                $href   =   str_replace($replace['0'],$replace['1'],$href);
                // 替换数据变量
                $href   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $href);
                $val[]  =   '<a href="'.url($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
    }
    return $value;
}
/**
 * @title 分析模型字段函数类型和插件类型配置
 * @param array $value 数据集
 * @param string $string 函数方法(钩子,widget控制器)|参数:值,参数:[VALUE]   [VALUE]字段名 (widget)
 * @auth 小矮人  82550565@qq.com
 */
function parse_function_attr($string,$data){
    $arr = explode('|',$string);
    $arr_new=[];
    if($arr){
        $arr_new['name'] = $arr[0];
    }
    if(isset($arr[1])){
        // 替换数据变量
        $arr[1]   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $arr[1]);
        $arr_csu = explode(',',$arr[1]);
        foreach ($arr_csu as $value){
            $arr_csu_s = explode(':',$value);
            $arr_new['parameter'][$arr_csu_s[0]] = $arr_csu_s[1];
        }
    }else{
        $arr_new['parameter']  = $data;
    }
    return $arr_new?$arr_new:[];
}
/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       ) * @author 艺品网络 <twothink.cn>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
    if($data === false || $data === null ){
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row){
        foreach ($map as $col=>$pair){
            if(isset($row[$col]) && isset($pair[$row[$col]])){
                $data[$key][$col.'_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}
/*
 * @title 数组下标映射
 * @param  array  $data 数据
 * @paran  strng  $key  数据中下标
 * @paran  strng  $vaule  数据中下标
 * @return array 映射后的数组
 * @author 艺品网络  593657688@qq.com <twothink.cn>
 */
function Array_mapping($data,$key,$vaule = false){
    if(!is_array($data))
        return false;
    $data_name = array_column($data,$key);
    $data_vaue = $vaule ? array_column($data,$vaule) : $data;
    return array_combine($data_name,$data_vaue);
}