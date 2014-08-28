<?php
define('IN_ESC',true);

require(dirname(__FILE__).'/includes/init.php');
date_default_timezone_set('Asia/Shanghai');

$act = mysql_real_escape_string($_REQUEST['act']);

/*-- 服务子菜单 --*/
if ($_REQUEST['act'] == 'menu')
{
    $file = strstr(basename($_SERVER['PHP_SELF']), '.', true);
    $nav = list_nav();
    $smarty->assign('nav_2nd', $nav[1][$file]);
    $smarty->assign('nav_3rd', $nav[2]);
    $smarty->assign('file_name', $file);

    die($smarty->fetch('left.htm'));
}

//档案查询
elseif ($act == 'sch_healthy_archive')
{
    
}

?>
