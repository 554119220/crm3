<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act'] == 'menu')
{
     $file = strstr(basename($_SERVER['PHP_SELF']), '.', true);
     $nav = list_nav();
     $smarty->assign('nav_2nd', $nav[1][$file]);
     $smarty->assign('nav_3rd', $nav[2]);
     $smarty->assign('file_name', $file);

     die($smarty->fetch('left.htm'));
}

/*
 * 问题界面
 */
elseif ($_REQUEST['act'] == 'question')
{
    $user_id = $_SESSION['admin_id'];  
    $mobile = $GLOBALS['db']->query("SELECT mobile FROM $GLOBALS['ecs']->table(admin_user) WHERE user_id=$admin_id");
}

?>
