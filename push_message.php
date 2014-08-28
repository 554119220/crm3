<?php
/*
 *  消息机制
 *
 */

define('IN_ECS',true);
require(dirname(__FILE__).'/includes/init.php');
require(dirname(__FILE__).'/includes/init.php');
date_default_timezone_set('Asia/Shanghai');

$action = isset($_REQUEST['act']) ? mysql_real_escape_string($_REQUEST['act']) : 'push_msg';

/*推送消息*/
if('push_msg' == $action){
    $sql_select = 'SELECT s.message_id,s.title,s.message,s.send_time,a.user_name AS sender FROM '.$GLOBALS['ecs']->table('message_machanism').
        ' s LEFT JOIN '.$GLOBALS['ecs']->table('admin_user').
        ' a ON s.sender_id=a.user_id '.
        " WHERE readed=0 AND delete=0 AND receiver_id={$_SESSION['admin_id']}";
    $msg_arr    = $GLOBALS['db']->getAll($sql_select);

    if(count($msg_arr)){
        $res['msg_arr'] = $msg_arr;
        $res['main']    = $smarty->fetch('push_message.htm');
    }

    die($json->encode($res));
}

//添加消息到消息库
elseif('add_msg' == $action){

    $message     = isset($_REQUEST['message']) ? trim(mysql_real_escape_string($_REQUEST['message'])) : '';
    $receiver_id = isset($_REQUEST['receiver_id']) ? intval($_REQUEST['receiver_id']) : 0;
    $title       = isset($_REQUEST['title']) ? mysql_real_escape_string($_REQUEST['title']) : '';

    $res = array(
        'req_msg' => true,
        'code'    => false,
        'timeout' => 2000,
        'message' => ''
    )

    if(!empty($message) && !empty($receiver_id)){
        $sql_insert = 'INSERT INTO '.$GLOBALS['ecs']->table('admin_message').
            '(sender_id,receiver_id,sent_time,title,message)'.
            "VALUES({$_SESSION['admin_id'],$receiver_id,{$_SERVER['REQUEST_TIME']},'$title','$message')";

        $res['code']    = $GLOBALS['db']->query($sql_insert);
        $res['message'] = $res['code'] ? '添加成功' : '添加失败';
    }else{
        $res['message'] = '添加失败';
    }

    die($json->encode($res));
}

?>
