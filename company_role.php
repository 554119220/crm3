<?php 
define('IN_ECS', true);
date_default_timezone_set('Asia/Shanghai');
require(dirname(__FILE__) . '/includes/init.php');

//公司规章制度详情
if($_REQUEST['act'] == 'get_rule')
{
    $id = intval($_REQUEST['id']);
    $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('company_system').
        " WHERE id=$id"; 
    $res = $GLOBALS['db']->getRow($sql_select);

    $smarty->assign('company_role',$res);
    $res['main'] = $smarty->fetch('com_rule_content.htm');

    die($json->encode($res));
}

?>
