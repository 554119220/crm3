<?php
/*=============================================================================
#     FileName: feedback.php
#         Desc: feedback for crm
#       Author: YuanZaifang
#        Email: 81330053@qq.com
#     HomePage: http://www.kjrs365.com
#      Version: 0.0.1
#   LastChange: 2014-08-27 15:49:18
#      History:
=============================================================================*/
define('IN_ECS',true);
require(dirname(__FILE__).'/includes/init.php');
include_once(ROOT_PATH."includes/fckeditor/fckeditor.php");
date_default_timezone_set('Asia/Shanghai');

$action = empty($_REQUEST['act']) ? 'feedback_form' : mysql_real_escape_string($_REQUEST['act']);

if('feedback_form' == $action){
    create_html_editor('FCKeditor1');   
    $smarty->assign('behave','upload');
    $smarty->display('feedback.htm');
}

//提交反馈
elseif('feedback_done' == $action){

    $feedback_class = isset($_REQUEST['feedback_class']) ? intval($_REQUEST['feedback_class']) : 0;
    $message        = isset($_REQUEST['FCKeditor1']) ? $_REQUEST['FCKeditor1'] : '';
    $title          = isset($_REQUEST['title']) ? mysql_real_escape_string($_REQUEST['title']) : '';
    $res['code']    = false;

    if(!empty($message)){
        $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('admin_message').
           " WHERE sender_id={$_SESSION['admin_id']} AND receiver_id=1 AND message='$message'  "; 
        if($GLOBALS['db']->getOne($sql_select)){
            $res['message'] = '请勿重复添加';
        }else{
            $sql_insert = 'INSERT INTO '.$GLOBALS['ecs']->table('admin_message').
                '(sender_id,receiver_id,sent_time,message_class,title,message)VALUES('.
                "{$_SESSION['admin_id']},1,{$_SERVER['REQUEST_TIME']},$feedback_class,'$title','$message')";
            $res['code']    = $GLOBALS['db']->query($sql_insert);
            $res['message'] = $res['code'] ? '感谢你的反馈和支持，我们会在最快的时间内解决你的问题' : '不好意思，反馈提交失败';
        }
    }else{
        $res['message'] = '不好意思，反馈提交失败';
    }

    $smarty->assign('res',$res);
    $smarty->assign('behave','upload');
    create_html_editor('FCKeditor1',$message);
    $smarty->display('feedback.htm');
}

elseif('feedback_more' == $action){
    $message_id = isset($_REQUEST['message_id']) ? intval($_REQUEST['message_id']) : 0;

    if($message_id){
        $sql_select = 'SELECT title,message FROM '.$GLOBALS['ecs']->table('admin_message').
            " WHERE message_id=$message_id";
       $feedback = $GLOBALS['db']->getRow($sql_select); 
    }

    $smarty->assign('feedback',$feedback);
    $smarty->assign('behave','see_more');
    $smarty->display('feedback.htm');
}

?>
